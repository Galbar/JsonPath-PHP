<?php
/**
 * Copyright 2018 Alessio Linares
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

namespace Utilities;

class ArraySlice
{
    /**
     * Implements the Python slice behaviour
     *
     * * a[1:2:3] => slice($a, 1, 2, 3)
     * * a[1:4]   => slice($a, 1, 4)
     * * a[3::2]  => slice($a, 3, null, 2)
     *
     * If $byReference is true, then the elements of
     * the resulting array will be references to the initial
     * array.
     *
     * @param array $array array
     * @param int|null $start start
     * @param int|null $stop stop
     * @param int|null $step step
     * @param bool $byReference byReference
     *
     * @return void
     */
    public static function slice(&$array, $start = null, $stop = null, $step = null, $byReference = false)
    {
        $result = array();
        $indexes = self::sliceIndices(count($array), $start, $stop, $step);
        if ($byReference) {
            foreach ($indexes as $i) {
                $result[] = &$array[$i];
            }
        } else {
            foreach ($indexes as $i) {
                $result[] = $array[$i];
            }
        }
        return $result;
    }

    private static function adjustEndpoint($length, $endpoint, $step)
    {
        if ($endpoint < 0) {
            $endpoint += $length;
            if ($endpoint < 0) {
                $endpoint = ($step < 0 ? -1 : 0);
            }
        } else if ($endpoint >= $length) {
            $endpoint = ($step < 0 ? $length - 1 : $length);
        }
        return $endpoint;
    }

    private static function adjustSlice($length, &$start, &$stop, &$step)
    {
        if ($step === null) {
            $step = 1;
        } else if ($step === 0) {
            throw new \Exception("Step cannot be 0");
        }

        if ($start === null) {
            $start = ($step < 0 ? $length - 1 : 0);
        } else {
            $start = self::adjustEndpoint($length, $start, $step);
        }

        if ($stop === null) {
            $stop = ($step < 0 ? -1 : $length);
        } else {
            $stop = self::adjustEndpoint($length, $stop, $step);
        }
    }

    private static function sliceIndices($length, $start, $stop, $step)
    {
        $result = array();
        self::adjustSlice($length, $start, $stop, $step);
        $i = $start;
        while ($step < 0 ? ($i > $stop) : ($i < $stop)) {
            $result[] = $i;
            $i += $step;
        }
        return $result;
    }
}
