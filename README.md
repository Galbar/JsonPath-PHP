
JsonPath
========
[![Build Status](https://travis-ci.org/Galbar/JsonPath-PHP.svg)](https://travis-ci.org/Galbar/JsonPath-PHP)
[![Coverage Status](https://coveralls.io/repos/Galbar/JsonPath-PHP/badge.svg?branch=master&service=github)](https://coveralls.io/github/Galbar/JsonPath-PHP?branch=master)
[![Latest Stable Version](https://poser.pugx.org/galbar/jsonpath/v/stable)](https://packagist.org/packages/galbar/jsonpath)
[![License](https://poser.pugx.org/galbar/jsonpath/license)](https://packagist.org/packages/galbar/jsonpath)

This is a [JSONPath](http://goessner.net/articles/JsonPath/) implementation for PHP. 

This implementation features all elements in the specification except the `()` operator (in the spcecification there is the `$..a[(@.length-1)]`, but this can be achieved with `$..a[-1]` and the latter is simpler).

On top of this it implements some extended features:
* Regex match comparisons (p.e. `$.store.book[?(@.author =~ /.*Tolkien/)]`)
* For the child operator `[]` there is no need to surround child names with quotes (p.e. `$.[store][book, bicycle]`) except if the name of the field is a non-valid javascript variable name.
* `.length` can be used to get the length of a string, get the length of an array and to check if a node has children.

Features
========
This implementation has the following features:
* Object oriented implementation.
* __Get__, __set__ and __add__ operations.
* Magic methods implemented:
    * `__get`: `$obj->{'$...'}`.
    * `__set`: `$obj->{'$...'} = $val`.
    * `__toString`: `echo $obj` prints the json representation of the JsonObject.
* Not using `eval()`.

Installation
=====

To install JsonPath you will need to be using Composer in your project. To install it please see the [docs](https://getcomposer.org/download/).

```bash
composer require galbar/jsonpath
```


Usage
=====
In every file you use it add:  
```php
use JsonPath\JsonObject;
```

Now you can create an instance of JsonObject and use it:
```php
// $json can be a string containing json, a PHP array, a PHP object or null.
// If $json is null (or not present) the JsonObject will be empty.
$jsonObject = new JsonObject();
// or
$jsonObject = new JsonObject($json);

// get
$obj->get($jsonPath);
$obj->{'$.json.path'};

// set
$obj->set($jsonPath, $value);
$obj->{'$.json.path'} = $value;

// get the json representation
$obj->getJson();
$str = (string)$obj;
echo $obj;

// get the PHP array representation
$obj->getValue();

// add values
$obj->add($jsonPath, $value[, $field]);

// remove values
$obj->remove($jsonPath, $field);
```

SmartGet
--------

When creating a new instance of JsonObject, you can pass a second parameter to the constructor. 
This sets the behaviour of the instance to use SmartGet.

What SmartGet does is to determine if the given JsonPath branches at some point, if it does it behaves as usual; 
otherwise, it will directly return the value pointed by the given path (not the array containing it).

GetJsonObjects
--------------

Sometimes you need to access multiple values of a subobject that has a long prefix (p.e. `$.a.very.long.prefix.for.[*].object`), in this case you would first get said object 
and make a JsonObject of it and then access its properties.

Now if you want to edit the object (set or add values) and want these changes to affect the original object, the way of doing this is by using 
`JsonObject::getJsonObjects($jsonpath)`. This method works the same way get does, but it will return the results as JsonObject instances containing a reference to the value in the source JsonObject.

JsonPath Language
=================
This library implements the following specification:
```
var_name    = [\w\_\$^\d][\w\-\$]*
number      = ([0-9]+(\.[0-9]*) | ([0-9]*\.[0-9]+))
string      = ('\''.*?'\'' | '"'.*?'"')
boolean     = ('true' | 'false')
regpattern  = '/'.*?'/'
null        = 'null'
index       = -?[0-9]+

jsonpath    = '$' operator*
childpath   = '@' operator*

operator    = (childname | childfilter | recursive) operator*

childname   = '.' (var_name | '*')
recursive   = '..' (var_name | '*')
childfilter = '[' ('*' | namelist | indexlist | arrayslice | filterexpr) ']'

namelist    = var_name (',' (var_name | '\'' .*? '\'' | '"' .*? '"'))*
indexlist   = index (',' index)*
arrayslice  = index? ':' index? ':' index?
filterexpr  = '?(' ors ')'

ors         = ands (' ' ( 'or' | '\|\|' ) ' ' ands)*
ands        = expr (' ' ( 'and' | '&&' ) ' ' expr)*
expr        = ( 'not ' | '! ' )? (value | comp)
comp        = value ('==' | '!=' | '<' | '>' | '<=' | '>=' | '=~') value
value       = (jsonpath | childpath | number | string | boolean | regpattern | null | length)
length      = (jsonpath | childpath) '.length'
```

### Limitations on the specification:  
* The jsonpath inside _value_ cannot contain `or`, `and` or any comparator.
* Jsonpaths in _value_ return the first element of the set or false if no result.
* Boolean operations can't be grouped with parethesis.
* `and`s are run before `or`s. That means that `a and 1 = b or c != d` is the same
as `(a and 1) or (c != d)`

__The `.length` operator__ can be used to:
* Get the number of childs a node in the JsonObject has: `$..*[?(@.length > 3)]`
* Filter for nodes that have childs: `$..*[?(@.length)]`
* Or filter for nodes that don't have childs (leaves): `$..*[?(not @.length)]`
* Check the length of a string: `$.path.to[?(@.a.string.length > 10)]`
* Get the length of a string: `$.path.to.field.length`
* Get the length of an array: `$.path.to.array.length`
* Get the length of each array inside an array of arrays: `$.path.to.array[*].array[*].length`
* Get the length of each string inside an array of strings: `$.path.to.array[*].array[*].key.length`

__The comparators__:  
`==`, `!=`, `<`, `>`, `<=`, `>=` do what expected (compare by type and value).  
`=~` is a regex comparator, matches the left operand with the pattern in the right operand. The value on the left must be a _string_ and on 
the right _regpattern_. Other wise returns `false`.

JsonPath Example
================
Consider the following json:
```json
{ "store": {
    "book": [
      { "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century",
        "price": 8.95,
        "available": true
      },
      { "category": "fiction",
        "author": "Evelyn Waugh",
        "title": "Sword of Honour",
        "price": 12.99,
        "available": false
      },
      { "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3",
        "price": 8.99,
        "available": true
      },
      { "category": "fiction",
        "author": "J. R. R. Tolkien",
        "title": "The Lord of the Rings",
        "isbn": "0-395-19395-8",
        "price": 22.99,
        "available": false
      }
    ],
    "bicycle": {
      "color": "red",
      "price": 19.95,
      "available": true
    }
  },
  "authors": [
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
  ]
}
```

JsonPath | Result
---------|--------
`$.store.bicycle.price` | The price of the bicycle.
`$.store.book[*]` | All books.
`$.store.book[1,3]` | The second and fourth book.
`$.store.book[1:3]` | From the second book to the third.
`$.store.book[:3]` | From the first book to the third.
`$.store.book[x:y:z]` | Books from x to y with a step of z.
`$..book[?(@.category == 'fiction')]` | All books with category == 'fiction'.
`$..*[?(@.available == true)].price` | All prices of available products.
`$..book[?(@.price < 10)].title` | The title of all books with price lower than 10.
`$..book[?(@.author==$.authors[3])]` | All books by "J. R. R. Tolkien"
`$[store]` | The store.
`$['store']` | The store.
`$..book[*][title, 'category', "author"]` | title, category and author of all books.

Test
====
To run tests, from the project root folder:  
`php app/test.php <jsonpath> [<file to json file>]`

If no json file is given it defaults to the json object described previously in this file.

For example:  
`php app/test.php "$..*[?(@.category == 'fiction' and @.price < 10 or @.color == \"red\")].price"`  
Result should be:  
`[19.95,8.99]`

Ready to code
=============
you can open the project in your browser and can start coding
[![Gitpod ready-to-code](https://img.shields.io/badge/Gitpod-ready--to--code-blue?logo=gitpod)](https://gitpod.io/#https://github.com/Galbar/JsonPath-PHP)


Docs
====
To generate the docs, from the project root folder:  
`php vendor/bin/sami.php update app/docgen.php`
