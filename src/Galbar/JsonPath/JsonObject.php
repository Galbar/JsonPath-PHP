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

use Utilities\ArraySlice;
use JsonPath\InvalidJsonException;
use JsonPath\InvalidJsonPathException;

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
    // Root regex
    const RE_ROOT_OBJECT = '/^\$(.*)/';

    // Child regex
    const RE_CHILD_NAME = '/^\.([a-zA-Z\_\$][\w\-\$]*|\*)(.*)/';
    const RE_RECURSIVE_SELECTOR = '/^\.\.([a-zA-Z\_\$][\w\-\$]*|\*)(.*)/';

    // Array expressions
    const RE_ARRAY_INTERVAL = '/^(?:(-?\d*:-?\d*)|(-?\d*:-?\d*:-?\d*))$/';
    const RE_INDEX_LIST = '/^(-?\d+)(\s*,\s*-?\d+)*$/';
    const RE_LENGTH = '/^(.*)\.length$/';

    // Object expression
    const RE_CHILD_NAME_LIST = '/^(:?([a-zA-Z\_\$][\w\-\$]*?|".*?"|\'.*?\')(\s*,\s*([a-zA-Z\_\$][\w\-\$]*|".*?"|\'.*?\'))*)$/';

    // Conditional expressions
    const RE_COMPARISON = '/^(.+)\s*(==|!=|<=|>=|<|>|=\~)\s*(.+)$/';
    const RE_STRING = '/^(?:\'(.*)\'|"(.*)")$/';
    const RE_REGEX_EXPR = '/^\/.*\/$/';
    const RE_NEXT_SUBEXPR = '/.*?(\(|\)|\[|\])/';
    const RE_OR = '/\s+or\s+/';
    const RE_AND = '/\s+and\s+/';
    const RE_NOT = '/^not\s+(.*)/';

    // Tokens
    const TOK_ROOT = '$';
    const TOK_CHILD = '@';
    const TOK_SELECTOR_BEGIN = '[';
    const TOK_SELECTOR_END = ']';
    const TOK_BOOL_EXPR = '?';
    const TOK_EXPRESSION_BEGIN = '(';
    const TOK_EXPRESSION_END = ')';
    const TOK_ALL = '*';
    const TOK_COMA = ',';
    const TOK_COLON = ':';
    const TOK_COMP_EQ = '==';
    const TOK_COMP_NEQ = '!=';
    const TOK_COMP_LT = '<';
    const TOK_COMP_GT = '>';
    const TOK_COMP_LTE = '<=';
    const TOK_COMP_GTE = '>=';
    const TOK_COMP_RE_MATCH = '=~';
    const TOK_TRUE = 'true';
    const TOK_FALSE = 'false';
    const TOK_NULL = 'null';

    private $jsonObject = null;
    private $smartGet = false;
    private $hasDiverged = false;

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
        $this->hasDiverged = false;
        $result = $this->getReal($this->jsonObject, $jsonPath);
        if ($this->smartGet && $result !== false && !$this->hasDiverged) {
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
        $this->hasDiverged = false;
        $result = $this->getReal($this->jsonObject, $jsonPath);
        if ($result !== false) {
            $objs = array();
            foreach($result as &$value) {
                $jsonObject = new JsonObject(null, $this->smartGet);
                $jsonObject->jsonObject = &$value;
                $objs[] = $jsonObject;
            }
            if ($this->smartGet && !$this->hasDiverged) {
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
        $result = $this->getReal($this->jsonObject, $jsonPath, true);
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
        $result = $this->getReal($this->jsonObject, $jsonPath, true);
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
        $result = $this->getReal($this->jsonObject, $jsonPath);
        foreach ($result as &$element) {
            if (is_array($element)) {
                unset($element[$field]);
            }
        }
        return $this;
    }

    private function expressionValue(&$jsonObject, $expression)
    {
        if ($expression === self::TOK_NULL) {
            return null;
        } else if ($expression === self::TOK_TRUE) {
            return true;
        } else if ($expression === self::TOK_FALSE) {
            return false;
        } else if (is_numeric($expression)) {
            return floatval($expression);
        } else if (preg_match(self::RE_STRING, $expression)) {
            return substr($expression, 1, strlen($expression) - 2);
        } else if (preg_match(self::RE_REGEX_EXPR, $expression)) {
            return $expression;
        } else {
            $match = array();
            $length = preg_match(self::RE_LENGTH, $expression, $match);
            if ($length) {
                $expression = $match[1];
            }
            $result = false;
            if ($expression[0] === self::TOK_ROOT){
                $result = $this->getReal($this->jsonObject, $expression);
            }
            else if ($expression[0] === self::TOK_CHILD) {
                $expression[0] = self::TOK_ROOT;
                $result = $this->getReal($jsonObject, $expression);
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

    private function booleanExpressionComparison(&$jsonObject, $leftExpr, $comparator, $rightExpr)
    {
        $left = $this->expressionValue($jsonObject, trim($leftExpr));
        $right = $this->expressionValue($jsonObject, trim($rightExpr));
        if ($comparator === self::TOK_COMP_EQ) {
            return $left === $right;
        } else if ($comparator === self::TOK_COMP_NEQ) {
            return $left !== $right;
        } else if ($comparator === self::TOK_COMP_LT) {
            return $left < $right;
        } else if ($comparator === self::TOK_COMP_GT) {
            return $left > $right;
        } else if ($comparator === self::TOK_COMP_LTE) {
            return $left <= $right;
        } else if ($comparator === self::TOK_COMP_GTE) {
            return $left >= $right;
        } else { // $comparator === self::TOK_COMP_RE_MATCH
            if (is_string($right) && is_string($left)) {
                return (bool) preg_match($right, $left);
            }
            return false;
        }
    }

    private function booleanExpressionAnds(&$jsonObject, $expression)
    {
        $values = preg_split(self::RE_AND, $expression);
        $match = array();
        foreach ($values as $subexpr) {
            $not = false;
            if (preg_match(self::RE_NOT, $subexpr, $match)) {
                $subexpr = $match[1];
                $not = true;
            }

            $result = false;
            if (preg_match(self::RE_COMPARISON, $subexpr, $match)) {
                $result = $this->booleanExpressionComparison($jsonObject, $match[1], $match[2], $match[3]);
            }
            else {
                $result = $this->expressionValue($jsonObject, $subexpr);
            }
            if ($not) {
                if ($result !== false) {
                    return false;
                }
            } else {
                if ($result === false) {
                    return false;
                }
            }
        }
        return true;
    }

    private function booleanExpression(&$jsonObject, $expression)
    {
        $ands = preg_split(self::RE_OR, $expression);
        foreach ($ands as $subexpr) {
            if ($this->booleanExpressionAnds($jsonObject, $subexpr)) {
                return true;
            }
        }
        return false;
    }

    private function matchValidExpression($jsonPath, &$result, $offset=0)
    {
        if ($jsonPath[$offset] != self::TOK_SELECTOR_BEGIN) {
            return false;
        }
        $initialOffset = $offset;
        $offset += 1;
        $parenCount = 0;
        $bracesCount = 1;
        // $count is a reference to the counter of the $startChar type
        $match = array();
        while ($bracesCount > 0 and $parenCount >= 0) {
            if (preg_match(self::RE_NEXT_SUBEXPR, $jsonPath, $match,  PREG_OFFSET_CAPTURE, $offset)) {
                $c = $match[1][0];
                if ($c === self::TOK_EXPRESSION_BEGIN) {
                    $parenCount += 1;
                } else if ($c === self::TOK_EXPRESSION_END) {
                    $parenCount -= 1;
                } else if ($c === self::TOK_SELECTOR_BEGIN) {
                    $bracesCount += 1;
                } else if ($c === self::TOK_SELECTOR_END) {
                    $bracesCount -= 1;
                }
                $offset = $match[1][1] + 1;
            } else {
                break;
            }
        }
        if ($bracesCount == 0 && $parenCount == 0) {
            $result = array(
                substr($jsonPath, $initialOffset + 1, $offset - $initialOffset - 2),
                substr($jsonPath, $offset - $initialOffset)
            );
            return 1;
        }
        $result = array();
        return 0;
    }

    private function opChildName(&$jsonObject, $childName, &$result, $createInexistent = false)
    {
        if (is_array($jsonObject)) {
            if ($childName === self::TOK_ALL) {
                $this->hasDiverged = true;
                foreach ($jsonObject as $key => $item) {
                    $result[] = &$jsonObject[$key];
                }
            } else if (array_key_exists($childName, $jsonObject)) {
                $result[] = &$jsonObject[$childName];
            } else if ($createInexistent) {
                $jsonObject[$childName] = array();
                $result[] = &$jsonObject[$childName];
            }
            return true;
        }
        return false;
    }

    private function opChildSelector(&$jsonObject, $contents, &$result, $createInexistent = false)
    {
        if (is_array($jsonObject)) {
            $match = array();
            $contentsLen = strlen($contents);
            if ($contents === self::TOK_ALL) {
                $this->hasDiverged = true;
                foreach ($jsonObject as $key => $item) {
                    $result[] = &$jsonObject[$key];
                }
            } else if (preg_match(self::RE_CHILD_NAME_LIST, $contents, $match)) {
                $names = array_map(
                    function($x) {
                        return trim($x, " \t\n\r\0\x0B'\"");
                    },
                    explode(self::TOK_COMA, $contents)
                );
                if (count($names) > 1) {
                    $this->hasDiverged = true;
                }

                $names = array_filter(
                    $names,
                    function($x) use ($createInexistent, $jsonObject) {
                        return $createInexistent || array_key_exists($x, $jsonObject);
                    }
                );
                foreach ($names as $name) {
                    if (!array_key_exists($name, $jsonObject)) {
                        $jsonObject[$name] = array();
                    }
                    $result[] = &$jsonObject[$name];
                }
            } else if (preg_match(self::RE_INDEX_LIST, $contents)) {
                $index = array_map(
                    function($x) use ($jsonObject){
                        $i = intval(trim($x));
                        if ($i < 0) {
                            $n = count($jsonObject);
                            $i = $i % $n;
                            if ($i < 0) {
                                $i += $n;
                            }
                        }
                        return $i;
                    },
                    explode(self::TOK_COMA, $contents)
                );
                if (count($index) > 1) {
                    $this->hasDiverged = true;
                }

                $index = array_filter(
                    $index,
                    function($x) use ($createInexistent, $jsonObject) {
                        return $createInexistent || array_key_exists($x, $jsonObject);
                    }
                );
                foreach ($index as $i) {
                    if (!array_key_exists($i, $jsonObject)) {
                        $jsonObject[$i] = array();
                    }
                    $result[] = &$jsonObject[$i];
                }
            } else if (preg_match(self::RE_ARRAY_INTERVAL, $contents, $match)) {
                $this->hasDiverged = true;
                $begin = null;
                $step = null;
                $end = null;
                // end($match) has the matched group with the interval
                $numbers = explode(self::TOK_COLON, end($match));
                // $numbers has the different numbers of the interval
                // depending on if there are 2 (begin:end) or 3 (begin:end:step)
                // numbers $begin, $step, $end are reassigned
                if (count($numbers) === 3) {
                    $step = ($numbers[2] !== '' ? intval($numbers[2]) : $step);
                }
                $end = ($numbers[1] !== '' ? intval($numbers[1]) : $end);
                $begin = ($numbers[0] !== '' ? intval($numbers[0]) : $begin);

                $slice = ArraySlice::slice($jsonObject, $begin, $end, $step, true);
                foreach ($slice as $i => $x) {
                    if ($x !== null) {
                        $result[] = &$slice[$i];
                    }
                }
            } else if ($contents[0] === self::TOK_BOOL_EXPR
                && $contents[1] === self::TOK_EXPRESSION_BEGIN
                && $contents[$contentsLen - 1] === self::TOK_EXPRESSION_END
            ) {
                $this->hasDiverged = true;
                $subexpr = substr($contents, 2, $contentsLen - 3);
                foreach ($jsonObject as &$child) {
                    if ($this->booleanExpression($child, $subexpr)) {
                        $result[] = &$child;
                    }
                }
            } else {
                throw new InvalidJsonPathException($contents);
            }
            return true;
        }
        return false;
    }

    private function opRecursiveSelector(&$jsonObject, $childName, &$result)
    {
        $this->opChildName($jsonObject, $childName, $result);
        if (is_array($jsonObject)) {
            foreach ($jsonObject as &$item) {
                $this->opRecursiveSelector($item, $childName, $result);
            }
        }
    }

    private function getReal(&$jsonObject, $jsonPath, $createInexistent = false)
    {
        $match = array();
        if (preg_match(self::RE_ROOT_OBJECT, $jsonPath, $match) === 0) {
            throw new InvalidJsonPathException($jsonPath);
        }

        $jsonPath = $match[1];
        $rootObjectPrev = &$this->jsonObject;
        $this->jsonObject = &$jsonObject;
        $selection = array(&$jsonObject);
        while (strlen($jsonPath) > 0 and count($selection) > 0) {
            $newSelection = array();
            if (preg_match(self::RE_CHILD_NAME, $jsonPath, $match)) {
                foreach ($selection as &$jsonObject) {
                    $this->opChildName($jsonObject, $match[1], $newSelection, $createInexistent);
                }
                if (empty($newSelection)) {
                    $selection = false;
                    break;
                } else {
                    $jsonPath = $match[2];
                }
            } else if ($this->matchValidExpression($jsonPath, $match)) {
                $contents = $match[0];
                foreach ($selection as &$jsonObject) {
                    $this->opChildSelector($jsonObject, $contents, $newSelection, $createInexistent);
                }
                if (empty($newSelection)) {
                    $selection = false;
                    break;
                } else {
                    $jsonPath = $match[1];
                }
            } else if (preg_match(self::RE_RECURSIVE_SELECTOR, $jsonPath, $match)) {
                $this->hasDiverged = true;
                $this->opRecursiveSelector($selection, $match[1], $newSelection);
                if (empty($newSelection)) {
                    $selection = false;
                    break;
                } else {
                    $jsonPath = $match[2];
                }
            } else {
                throw new InvalidJsonPathException($jsonPath);
            }
            $selection = $newSelection;
        }

        $this->jsonObject = &$rootObjectPrev;
        return $selection;
    }
}
