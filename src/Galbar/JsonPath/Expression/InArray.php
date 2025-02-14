<?php

namespace JsonPath\Expression;

use JsonPath\Language;

class InArray
{
    const SEPARATOR = ',';

    public static function evaluate(&$root, &$partial, $attribute, $listExpression)
    {
        $value = Value::evaluate($root, $partial, trim($attribute));
        $list = self::prepareList($root, $partial, $listExpression);

        return in_array($value, $list, true);
    }

    private static function prepareList(&$root, &$partial, $expression)
    {
        if (strpos($expression, self::SEPARATOR) === false) {
            if ($expression[0] === Language\Token::ROOT){
                list($result, $_) = \JsonPath\JsonPath::subtreeGet($root, $root, $expression);
                if (!$_) {
                    $result = reset($result);
                }
                return $result;
            }
            else if ($expression[0] === Language\Token::CHILD) {
                $expression[0] = Language\Token::ROOT;
                list($result, $_) = \JsonPath\JsonPath::subtreeGet($root, $partial, $expression);
                if (!$_) {
                    $result = reset($result);
                }
                return $result;
            }

            return [Value::evaluate($root, $partial, trim($expression))];
        }

        return array_map(
            function ($value) use ($root, $partial) { return Value::evaluate($root, $partial, trim($value)); },
            explode(self::SEPARATOR, $expression)
        );
    }
}

