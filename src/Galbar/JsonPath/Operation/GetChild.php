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

class GetChild
{
    public static function apply(&$jsonObject, $childName, $createInexistent = false)
    {
        if (!is_array($jsonObject)) {
            return array(array(), false);
        }
        $result = array();
        $hasDiverged = false;
        if ($childName === Language\Token::ALL) {
            $hasDiverged = true;
            foreach ($jsonObject as $key => $_) {
                $result[] = &$jsonObject[$key];
            }
        } else if (array_key_exists($childName, $jsonObject)) {
            $result[] = &$jsonObject[$childName];
        } else if ($createInexistent) {
            $jsonObject[$childName] = array();
            $result[] = &$jsonObject[$childName];
        }
        return array($result, $hasDiverged);
    }
}
