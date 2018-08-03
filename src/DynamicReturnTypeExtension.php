<?php declare(strict_types = 1);

namespace CZechBoY\PHPStanMabeEnum;

use MabeEnum\Enum;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class EnumGetValueDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    /** @var \PHPStan\Type\Type[] */
    private $enumTypes = [];

    public function getClass(): string
    {
        return Enum::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getValue';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $enumType = $scope->getType($methodCall->var);
        if (count($enumType->getReferencedClasses()) !== 1) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        /** @var string $enumClass */
        $enumClass = $enumType->getReferencedClasses()[0];
        if (array_key_exists($enumClass, $this->enumTypes)) {
            return $this->enumTypes[$enumClass];
        }
        $types = array_map(function ($value): Type {
            return $this->getTypeFromValue($value);
        }, array_values($enumClass::getValues()));

        $this->enumTypes[$enumClass] = TypeCombinator::union(...$types);

        return $this->enumTypes[$enumClass];
    }

    /**
     * @param mixed $value
     */
    private function getTypeFromValue($value): Type
    {
        if (is_int($value)) {
            return new IntegerType();
        } elseif (is_float($value)) {
            return new FloatType();
        } elseif (is_bool($value)) {
            return new BooleanType();
        } elseif (is_string($value)) {
            return new StringType();
        }

        throw new ShouldNotHappenException();
    }

}
