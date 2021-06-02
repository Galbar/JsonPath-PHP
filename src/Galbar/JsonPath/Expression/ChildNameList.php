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

class ChildNameList
{
    public static function evaluate(&$partial, $names, $createInexistent = false) {
        $names = array_filter(
            $names,
            function($x) use ($createInexistent, $partial) {
                return $createInexistent || array_key_exists($x, $partial);
            }
        );
        $result = array();
        foreach ($names as $name) {
            if (!array_key_exists($name, $partial)) {
                $partial[$name] = array();
            }
            $result[] = &$partial[$name];
        }
        return $result;
    }
}
