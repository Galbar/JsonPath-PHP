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


namespace JsonPath;

use JsonPath\Expression;
use JsonPath\Language;
use JsonPath\Operation;

class JsonPath
{
    public static function get(&$root, $jsonPath, $createInexistent = false)
    {
        return JsonPath::subtreeGet($root, $root, $jsonPath, $createInexistent);
    }

    public static function subtreeGet(&$root, &$partial, $jsonPath, $createInexistent = false)
    {
        $match = array();
        if (preg_match(Language\Regex::ROOT_OBJECT, $jsonPath, $match) === 0) {
            throw new \JsonPath\InvalidJsonPathException($jsonPath);
        }
        $hasDiverged = false;
        $jsonPath = $match[1];
        $selection = array(&$partial);
        while (strlen($jsonPath) > 0 and count($selection) > 0) {
            $newSelection = array();
            $newHasDiverged = false;
            if (preg_match(Language\Regex::CHILD_NAME, $jsonPath, $match)) {
                foreach ($selection as &$partial) {
                    list($result, $newHasDiverged) = Operation\GetChild::apply($partial, $match[1], $createInexistent);
                    $newSelection = array_merge($newSelection, $result);
                }
                if (empty($newSelection) && preg_match(Language\Regex::PARENT_LENGTH, $match[0], $lengthMatch)) {
                    if (count($selection) > 1) {
                        $newSelection = [];
                        /** .length of each array/string in array of arrays $item */
                        foreach ($selection as $item) {
                            if (is_array($item)) {
                                array_push($newSelection,count($item));
                            } else{
                                array_push($newSelection,strlen($item));
                            }
                        }
                    } elseif (count($selection) == 1) {
                        if (is_array($selection[0])) {
                            $newSelection = count($selection[0]);
                        } else {
                            $newSelection = strlen($selection[0]);
                        }
                    }
                }
                if (empty($newSelection)) {
                    $selection = false;
                    break;
                } else {
                    $jsonPath = $match[2];
                }
            } else if (Language\ChildSelector::match($jsonPath, $match)) {
                $contents = $match[0];
                foreach ($selection as &$partial) {
                    list($result, $newHasDiverged) = Operation\SelectChildren::apply($root, $partial, $contents, $createInexistent);
                    $newSelection = array_merge($newSelection, $result);
                }
                if (empty($newSelection)) {
                    $selection = false;
                    break;
                } else {
                    $jsonPath = $match[1];
                }
            } else if (preg_match(Language\Regex::RECURSIVE_SELECTOR, $jsonPath, $match)) {
                list($result, $newHasDiverged) = Operation\GetRecursive::apply($partial, $match[1]);
                $newSelection = array_merge($newSelection, $result);
                if (empty($newSelection)) {
                    $selection = false;
                    break;
                } else {
                    $jsonPath = $match[2];
                }
            } else {
                throw new \JsonPath\InvalidJsonPathException($jsonPath);
            }
            $selection = $newSelection;
            $hasDiverged = $hasDiverged || $newHasDiverged;
        }

        return array($selection, $hasDiverged);
    }
}
