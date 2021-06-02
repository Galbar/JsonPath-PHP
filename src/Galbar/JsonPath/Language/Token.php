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

class Token
{
    const ROOT = '$';
    const CHILD = '@';
    const SELECTOR_BEGIN = '[';
    const SELECTOR_END = ']';
    const BOOL_EXPR = '?';
    const EXPRESSION_BEGIN = '(';
    const EXPRESSION_END = ')';
    const ALL = '*';
    const COMA = ',';
    const COLON = ':';
    const COMP_EQ = '==';
    const COMP_NEQ = '!=';
    const COMP_LT = '<';
    const COMP_GT = '>';
    const COMP_LTE = '<=';
    const COMP_GTE = '>=';
    const COMP_RE_MATCH = '=~';
    const VAL_TRUE = 'true';
    const VAL_FALSE = 'false';
    const VAL_NULL = 'null';
    const LENGTH = 'length';
}
