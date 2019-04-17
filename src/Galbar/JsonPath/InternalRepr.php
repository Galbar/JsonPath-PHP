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

namespace JsonPath;

class InternalRepr
{
    private $data = null;


    function __construct($data)
    {
        $this->data = &$data;
    }

    function isList()
    {
        return is_array($this->data);
    }

    function isObject()
    {
        return is_object($this->data);
    }

    function isCollection()
    {
        return $this->isList() || $this->isObject();
    }

    function items()
    {
        $items = array();
        foreach ($this->data as $key => &$value) {
            $wrapper = new InternalRepr(null);
            $wrapper->data = &$value;
            $items[] = array($key, $wrapper);
        }
        return $items;
    }

    function get($key)
    {
        $value = null;
        if ($this->isList())
        {
            $value = &$this->data[$key];
        }
        else if ($this->isObject())
        {
            $value = &$this->data->{$key};
        }
        $wrapper = new InternalRepr(null);
        $wrapper->data = &$value;
        return $wrapper;
    }

    function set($key, $value)
    {
        if ($this->isList())
        {
            $this->data[$key] = $value;
            if (is_string($key))
            {
                $new_data = new \stdClass();
                foreach($this->data as $key => &$val) {
                    $new_data->{$key} = &$val;
                }
                $this->data = $new_data;
            }
        }
        else if ($this->isObject())
        {
            $this->data->{$key} = $value;
        }
    }

    function hasKey($key)
    {
        if ($this->isList())
        {
            return array_key_exists($key, $this->data);
        }
        else if ($this->isObject())
        {
            return property_exists($this->data, $key);
        }
    }

    function &getRaw()
    {
        return $this->data;
    }
}
