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

namespace JsonPath\Operation;

use JsonPath\Language;
use JsonPath\Expression;

class SelectChildren
{
    public static function apply(&$root, &$partial, $contents, $createInexistent = false)
    {
        if (!is_array($partial)) {
            return array(array(), false);
        }

        $result = array();
        $hasDiverged = false;
        $match = array();
        $contentsLen = strlen($contents);
        if ($contents === Language\Token::ALL) {
            $hasDiverged = true;
            foreach ($partial as $key => $item) {
                $result[] = &$partial[$key];
            }
        } else if (preg_match(Language\Regex::CHILD_NAME_LIST, $contents, $match)) {
            $names = array_map(
                function($x) { return trim($x, " \t\n\r\0\x0B'\""); },
                explode(Language\Token::COMA, $contents)
            );
            if (count($names) > 1) {
                $hasDiverged = true;
            }
            $result = Expression\ChildNameList::evaluate($partial, $names, $createInexistent);
        } else if (preg_match(Language\Regex::INDEX_LIST, $contents)) {
            $indexes = array_map(
                function($x) { return intval(trim($x)); },
                explode(Language\Token::COMA, $contents)
            );
            if (count($indexes) > 1) {
                $hasDiverged = true;
            }
            $result = Expression\IndexList::evaluate($partial, $indexes, $createInexistent);
        } else if (preg_match(Language\Regex::ARRAY_INTERVAL, $contents, $match)) {
            // end($match) has the matched group with the interval
            $numbers = explode(Language\Token::COLON, end($match));
            $hasDiverged = true;
            $result = Expression\ArrayInterval::evaluate($partial, $numbers);
        } else if ($contents[0] === Language\Token::BOOL_EXPR
            && $contents[1] === Language\Token::EXPRESSION_BEGIN
            && $contents[$contentsLen - 1] === Language\Token::EXPRESSION_END
        ) {
            $hasDiverged = true;
            $subexpr = substr($contents, 2, $contentsLen - 3);
            foreach ($partial as &$child) {
                if (Expression\BooleanExpression::evaluate($root, $child, $subexpr)) {
                    $result[] = &$child;
                }
            }
        } else {
            throw new \JsonPath\InvalidJsonPathException($contents);
        }
        return array($result, $hasDiverged);
    }
}
