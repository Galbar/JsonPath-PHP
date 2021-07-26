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

namespace JsonPath\Language;

class ChildSelector
{
    public static function match($jsonPath, &$match, $offset=0)
    {
        if ($jsonPath[$offset] != Token::SELECTOR_BEGIN) {
            return false;
        }
        $initialOffset = $offset;
        $offset += 1;
        $parenCount = 0;
        $bracesCount = 1;
        // $count is a reference to the counter of the $startChar type
        $match = array();
        while ($bracesCount > 0 and $parenCount >= 0) {
            if (preg_match(Regex::NEXT_SUBEXPR, $jsonPath, $match,  PREG_OFFSET_CAPTURE, $offset)) {
                $c = $match[1][0];
                if ($c === Token::EXPRESSION_BEGIN) {
                    $parenCount += 1;
                } else if ($c === Token::EXPRESSION_END) {
                    $parenCount -= 1;
                } else if ($c === Token::SELECTOR_BEGIN) {
                    $bracesCount += 1;
                } else if ($c === Token::SELECTOR_END) {
                    $bracesCount -= 1;
                }
                $offset = $match[1][1] + 1;
            } else {
                break;
            }
        }
        if ($bracesCount == 0 && $parenCount == 0) {
            $match = array(
                substr($jsonPath, $initialOffset + 1, $offset - $initialOffset - 2),
                substr($jsonPath, $offset - $initialOffset)
            );
            return 1;
        }
        $match = array();
        return 0;
    }
}
