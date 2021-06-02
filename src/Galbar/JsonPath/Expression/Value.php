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
use JsonPath\Operation;

class Value
{
    public static function evaluate(&$root, &$partial, $expression)
    {
        if ($expression === Language\Token::VAL_NULL) {
            return null;
        } else if ($expression === Language\Token::VAL_TRUE) {
            return true;
        } else if ($expression === Language\Token::VAL_FALSE) {
            return false;
        } else if (is_numeric($expression)) {
            return floatval($expression);
        } else if (preg_match(Language\Regex::EXPR_STRING, $expression)) {
            return substr($expression, 1, strlen($expression) - 2);
        } else if (preg_match(Language\Regex::EXPR_REGEX, $expression)) {
            return $expression;
        } else {
            $match = array();
            $length = preg_match(Language\Regex::LENGTH, $expression, $match);
            if ($length) {
                $expression = $match[1];
            }
            $result = false;
            if ($expression[0] === Language\Token::ROOT){
                list($result, $_) = \JsonPath\JsonPath::subtreeGet($root, $root, $expression);
            }
            else if ($expression[0] === Language\Token::CHILD) {
                $expression[0] = Language\Token::ROOT;
                list($result, $_) = \JsonPath\JsonPath::subtreeGet($root, $partial, $expression);
            }
            if ($result !== false) {
                if ($length) {
                    if (is_array($result[0])) {
                        return (float) count($result[0]);
                    }
                    if (is_string($result[0])) {
                        return (float) strlen($result[0]);
                    }
                    return false;
                }
                if (is_float($result[0]) || is_int($result[0])) {
                    $result[0] = (float) $result[0];
                }
                return $result[0];
            }
            return $result;
        }
    }
}
