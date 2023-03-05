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

class Regex
{
    // Root regex
    const ROOT_OBJECT = '/^\$(.*)/';

    // Child regex
    const CHILD_NAME = '/^\.([\p{L}\p{N}\_\$][\p{L}\p{N}\_\-\$]*|\*)(.*)/u';
    const RECURSIVE_SELECTOR = '/^\.\.(.+)/u';

    // Array expressions
    const ARRAY_INTERVAL = '/^(?:(-?\d*:-?\d*)|(-?\d*:-?\d*:-?\d*))$/';
    const INDEX_LIST = '/^(-?\d+)(\s*,\s*-?\d+)*$/';
    const LENGTH = '/^(.*)\.length$/';

    // Object expression
    const CHILD_NAME_LIST = '/^(?:([\p{L}\p{N}\_\$][\p{L}\p{N}\_\-\$]*|".*?"|\'.*?\')(\s*,\s*([\p{L}\p{N}\_\$][\p{L}\p{N}\_\-\$]*|".*?"|\'.*?\'))*)$/u';

    // Conditional expressions
    const EXPR_STRING = '/^(?:\'(.*)\'|"(.*)")$/';
    const EXPR_REGEX = '/^\/.*\/i?x?$/';
    const BINOP_COMP = '/^(.+)\s*(==|!=|<=|>=|<|>|=\~)\s*(.+)$/';
    const BINOP_IN_ARRAY = '/^(.+)\s*in\s*\[(.+)\]$/';
    const BINOP_OR = '/\s+(or|\|\|)\s+/';
    const BINOP_AND = '/\s+(and|&&)\s+/';
    const OP_NOT = '/^(not|!)\s+(.*)/';
    const NEXT_SUBEXPR = '/.*?(\(|\)|\[|\])/';
}
