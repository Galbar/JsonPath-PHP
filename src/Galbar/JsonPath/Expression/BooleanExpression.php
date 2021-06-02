<?php
/**
 * Copyright 2021 Alessio Linares
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JsonPath\Expression;

use JsonPath\Language;

class BooleanExpression
{
    public static function evaluate(&$root, &$partial, $expression)
    {
        $ands = preg_split(Language\Regex::BINOP_OR, $expression);
        foreach ($ands as $subexpr) {
            if (BooleanExpression::booleanExpressionAnds($root, $partial, $subexpr)) {
                return true;
            }
        }
        return false;
    }

    private static function booleanExpressionAnds(&$root, &$partial, $expression)
    {
        $values = preg_split(Language\Regex::BINOP_AND, $expression);
        $match = array();
        foreach ($values as $subexpr) {
            $not = false;
            if (preg_match(Language\Regex::OP_NOT, $subexpr, $match)) {
                $subexpr = $match[2];
                $not = true;
            }

            $result = false;
            if (preg_match(Language\Regex::BINOP_COMP, $subexpr, $match)) {
                $result = Comparison::evaluate($root, $partial, $match[1], $match[2], $match[3]);
            }
            else {
                $result = Value::evaluate($root, $partial, $subexpr);
            }
            if ($not) {
                if ($result !== false) {
                    return false;
                }
            } else {
                if ($result === false) {
                    return false;
                }
            }
        }
        return true;
    }
}
