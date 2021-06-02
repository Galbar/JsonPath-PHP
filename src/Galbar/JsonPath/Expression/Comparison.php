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

class Comparison
{
    public static function evaluate(&$root, &$partial, $leftExpr, $comparator, $rightExpr)
    {
        $left = Value::evaluate($root, $partial, trim($leftExpr));
        $right = Value::evaluate($root, $partial, trim($rightExpr));
        if ($comparator === Language\Token::COMP_EQ) {
            return $left === $right;
        } else if ($comparator === Language\Token::COMP_NEQ) {
            return $left !== $right;
        } else if ($comparator === Language\Token::COMP_LT) {
            return $left < $right;
        } else if ($comparator === Language\Token::COMP_GT) {
            return $left > $right;
        } else if ($comparator === Language\Token::COMP_LTE) {
            return $left <= $right;
        } else if ($comparator === Language\Token::COMP_GTE) {
            return $left >= $right;
        } else { // $comparator === Language\Token::COMP_RE_MATCH
            if (is_string($right) && is_string($left)) {
                return (bool) preg_match($right, $left);
            }
            return false;
        }
    }
}
