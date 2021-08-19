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

use JsonPath\InvalidJsonException;
use JsonPath\Operation;

/**
 * This is a [JSONPath](http://goessner.net/articles/JsonPath/) implementation for PHP.
 *
 * This implementation features all elements in the specification except the `()` operator (in the spcecification there is the `$..a[(@.length-1)]`, but this can be achieved with `$..a[-1]` and the latter is simpler).
 *
 * On top of this it implements some extended features:
 *
 *  * Regex match comparisons (p.e. `$.store.book[?(@.author =~ /.*Tolkien/)]`)
 *  * For the child operator `[]` there is no need to surround child names with quotes (p.e. `$.[store][book, bicycle]`) except if the name of the field is a non-valid javascript variable name.
 *  * `.length` can be used to get the length of a string, get the length of an array and to check if a node has children.
 *
 * Features
 * ========
 * This implementation has the following features:
 *
 *  * Object oriented implementation.
 *  * __Get__, __set__ and __add__ operations.
 *  * Magic methods implemented:
 *    * `__get`: `$obj->{'$.json.path'}`.
 *    * `__set`: `$obj->{'$.json.path'} = $val`.
 *    * `__toString`: `echo $obj` prints the json representation of the JsonObject.
 *  * Not using `eval()`.
 *
 * Usage
 * =====
 *       // $json can be a string containing json, a PHP array, a PHP object or null.
 *       // If $json is null (or not present) the JsonObject will be empty.
 *       $jsonObject = new JsonObject();
 *       // or
 *       $jsonObject = new JsonObject($json);
 *
 *       // get
 *       $obj->get($jsonPath);
 *       $obj->{'$.json.path'};
 *
 *       // set
 *       $obj->set($jsonPath, $value);
 *       $obj->{'$.json.path'} = $value;
 *
 *       // get the json representation
 *       $obj->getJson();
 *       $str = (string)$obj;
 *       echo $obj;
 *
 *       // get the PHP array representation
 *       $obj->getArray();
 *
 *       // add values
 *       $obj->add($jsonPath, $value[, $field]);
 *
 *       // remove values
 *       $obj->remove($jsonPath, $field);
 *
 * SmartGet
 * --------
 *
 * When creating a new instance of JsonObject, you can pass a second parameter to the constructor.
 * This sets the behaviour of the instance to use SmartGet.
 *
 * What SmartGet does is to determine if the given JsonPath branches at some point, if it does it behaves as usual;
 * otherwise, it will directly return the value pointed to by the given path (not the array containing it).
 *
 *      $json = array(
 *          "a" => array(
 *              "b" => 3,
 *              "c" => 4
 *          )
 *      );
 *      $obj = new JsonObject($json, true);
 *      $obj->get('$.a.b'); // Returns int(3)
 *      $obj->get('$.a.*'); // Returns array(int(3), int(4))
 *
 *
 * GetJsonObjects
 * --------------
 *
 * Sometimes you need to access multiple values of a subobject that has a long prefix (p.e. `$.a.very.long.prefix.for.[*].object`), in this case you would first get said object
 * and make a JsonObject of it and then access its properties.
 *
 * Now if you want to edit the object (set or add values) and want these changes to affect the original object, the way of doing this is by using
 * `JsonObject::getJsonObjects($jsonpath)`. This method works the same way get does, but it will return the results as JsonObject instances containing a reference to the value in the source JsonObject.
 */
class JsonObject
{
    private $jsonObject = null;
    private $smartGet = false;

    /**
     * Class constructor.
     * If $json is null the json object contained
     * will be initialized empty.
     *
     * @param mixed $json json
     * @param bool $smartGet enable smart get
     *
     * @return void
     */
    function __construct($json = null, $smartGet = false)
    {
        if ($json === null) {
            $this->jsonObject = array();
        } else if (is_string($json)) {
            $this->jsonObject = json_decode($json, true);
            if ($this->jsonObject === null) {
                throw new InvalidJsonException("string does not contain a valid JSON object.");
            }
        } else if (is_array($json)) {
            $this->jsonObject = $json;
        } else if (is_object($json)){
            $this->jsonObject = json_decode(json_encode($json), true);
        } else {
            throw new InvalidJsonException("value does not encode a JSON object.");
        }
        $this->smartGet = $smartGet;
    }

    /**
     * Syntactic sugar for toJson() method.
     * Usage:
     *  $json = (string)$instance;
     * or
     *  echo $instance;
     *
     * @param string $jsonPath jsonPath
     *
     * @return (false|array)
     */
    function __toString()
    {
        return $this->getJson();
    }

    /**
     * Syntactic sugar for get() method. The starting '$' is not needed (implicit)
     * Usage: $obj->{'.json.path'};
     *
     * @param string $jsonPath jsonPath
     *
     * @return (false|array)
     */
    function __get($jsonPath)
    {
        return $this->get($jsonPath);
    }

    /**
     * Syntactic sugar for set() method. The starting '$' is not needed (implicit)
     * Usage: $obj->{'.json.path'} = $value;
     *
     * @param string $jsonPath jsonPath
     * @param mixed $value value
     *
     * @return JsonObject
     */
    function __set($jsonPath, $value)
    {
        return $this->set($jsonPath, $value);
    }

    /**
     * Returns the value of the json object as a PHP array.
     *
     *
     * @return array
     */
    public function &getValue()
    {
        return $this->jsonObject;
    }

    /**
     * Returns the json object encoded as string.
     * See http://php.net/manual/en/json.constants.php for more information on the $options bitmask.
     *
     * @param int $options json_encode options bitmask
     *
     * @return string
     */
    public function getJson($options=0)
    {
        return json_encode($this->jsonObject, $options);
    }

    /**
     * Returns an array containing references to the
     * objects that match the JsonPath. If the result is
     * empty returns false.
     *
     * If smartGet was set to true when creating the instance and
     * the JsonPath given does not branch, it will return the value
     * instead of an array of one element.
     *
     * @param string $jsonPath jsonPath
     *
     * @return mixed
     */
    public function get($jsonPath)
    {
        list($result, $hasDiverged) = JsonPath::get($this->jsonObject, $jsonPath);
        if ($this->smartGet && $result !== false && !$hasDiverged && is_array($result)) {
            return $result[0];
        }
        return $result;
    }

    /**
     * Return an array of new JsonObjects representing the results of the
     * given JsonPath. These objects contain references to the elements in the
     * original JsonObject.
     *
     * This is affected by smartGet the same way JsonObject::get is affected
     *  This can cause JsonObject to have actual values (not object/array) as root.
     *
     * This is useful when you want to work with a subelement of the root
     * object and you want to edit (add, set, remove) values in that subelement
     * and that these changes also affect the root object.
     *
     * @param string $jsonPath jsonPath
     *
     * @return mixed
     */
    public function getJsonObjects($jsonPath)
    {
        list($result, $hasDiverged) = JsonPath::get($this->jsonObject, $jsonPath);
        if ($result !== false) {
            $objs = array();
            foreach($result as &$value) {
                $jsonObject = new JsonObject(null, $this->smartGet);
                $jsonObject->jsonObject = &$value;
                $objs[] = $jsonObject;
            }
            if ($this->smartGet && !$hasDiverged && is_array($result)) {
                return $objs[0];
            }
            return $objs;
        }
        return $result;
    }

    /**
     * Sets all elements that result from the $jsonPath query
     * to $value. This method will create previously non-existent
     * JSON objects if the path contains them (p.e. if quering '$.a'
     * results in false, when setting '$.a.b' to a value '$.a' will be created
     * as a result of this).
     *
     * This method returns $this to enable fluent
     * interface.
     *
     *
     * @param string $jsonPath jsonPath
     * @param mixed $value value
     *
     * @return JsonObject
     */
    public function set($jsonPath, $value)
    {
        list($result, $_) = JsonPath::get($this->jsonObject, $jsonPath, true);
        if ($result !== false) {
            foreach ($result as &$element) {
                $element = $value;
            }
        }
        return $this;
    }

    /**
     * Append a new value to all json objects/arrays that match
     * the $jsonPath path. If $field is not null, the new value
     * will be added with $field as key (this will transform
     * json arrays into objects). This method returns $this to
     * enable fluent interface.
     *
     * @param string $jsonPath jsonPath
     * @param mixed $value value
     * @param string $field field
     *
     * @return JsonObject
     */
    public function add($jsonPath, $value, $field=null)
    {
        list($result, $_) = JsonPath::get($this->jsonObject, $jsonPath, true);
        foreach ($result as &$element) {
            if (is_array($element)) {
                if ($field == null) {
                    $element[] = $value;
                }
                else {
                    $element[$field] = $value;
                }
            }
        }
        return $this;
    }

    /**
     * Remove $field from json objects/arrays that match
     * $jsonPath path. This method returns $this to enable
     * fluent interface.
     *
     * @param mixed $jsonPath jsonPath
     * @param mixed $field field
     *
     * @return JsonObject
     */
    public function remove($jsonPath, $field)
    {
        list($result, $_) = JsonPath::get($this->jsonObject, $jsonPath);
        foreach ($result as &$element) {
            if (is_array($element)) {
                unset($element[$field]);
            }
        }
        return $this;
    }
}
