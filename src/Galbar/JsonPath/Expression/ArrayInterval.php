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

use Utilities\ArraySlice;
use JsonPath\Language;

class ArrayInterval
{
    public static function evaluate(&$partial, $numbers) {
        $begin = null;
        $step = null;
        $end = null;
        // $numbers has the different numbers of the interval
        // depending on if there are 2 (begin:end) or 3 (begin:end:step)
        // numbers $begin, $step, $end are reassigned
        if (count($numbers) === 3) {
            $step = ($numbers[2] !== '' ? intval($numbers[2]) : $step);
        }
        $end = ($numbers[1] !== '' ? intval($numbers[1]) : $end);
        $begin = ($numbers[0] !== '' ? intval($numbers[0]) : $begin);

        $slice = ArraySlice::slice($partial, $begin, $end, $step, true);
        $result = array();
        foreach ($slice as $i => $x) {
            if ($x !== null) {
                $result[] = &$slice[$i];
            }
        }
        return $result;
    }
}
