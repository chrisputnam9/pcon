<?php
/**
 * HJSON PHP Vendor Dependency
 *
 * - This tool helps PHP read and write HJSON format for config and data files
 *
 * @package   hjsonphp
 * @see       https://github.com/hjson/hjson-php
 * @author    hjson
 * @copyright Unknown
 * @license   MIT
 * @version   2.1.0
 *
 * @ignore Since this is a third-party library with documentation of its own
 * phpcs:disable
 */

if (!class_exists("HJSONException"))
{
    class HJSONException extends \Exception
    {
    }
}

if (!class_exists("HJSONParser"))
{
    class HJSONParser
    {

        private $text_array;
        private $text_length_chars;
        private $at;   // The index of the current character
        private $ch;   // The current character
        private $escapee = [];
        private $keepWsc; // keep whitespace

        public function __construct()
        {
            $this->escapee = [
                '"'  => '"',
                '\'' => '\'',
                "\\" => "\\",
                '/'  => '/',
                'b'  => chr(8),
                'f'  => chr(12),
                'n'  => "\n",
                'r'  => "\r",
                't'  => "\t"
            ];
        }

        public function parse($source, $options = [])
        {
            $this->keepWsc = $options && isset($options['keepWsc']) && $options['keepWsc'];
            $this->text_array = preg_split("//u", $source, -1, PREG_SPLIT_NO_EMPTY);
            $this->text_length_chars = count($this->text_array);

            $data = $this->rootValue();

            if ($options && isset($options['assoc']) && $options['assoc']) {
                $data = json_decode(json_encode($data), true);
            }

            return $data;
        }

        private function resetAt()
        {
            $this->at = 0;
            $this->ch = ' ';
        }

        public function parseWsc($source, $options = [])
        {
            return $this->parse($source, array_merge($options, ['keepWsc' => true]));
        }

        private function isPunctuatorChar($c)
        {
            return $c === '{' || $c === '}' || $c === '[' || $c === ']' || $c === ',' || $c === ':';
        }

        private function checkExit($result)
        {
            $this->white();
            if ($this->ch !== null) {
                $this->error("Syntax error, found trailing characters!");
            }
            return $result;
        }

        private function rootValue()
        {
            // Braces for the root object are optional

            $this->resetAt();
            $this->white();
            switch ($this->ch) {
                case '{':
                    return $this->checkExit($this->object());
                case '[':
                    return $this->checkExit($this->_array());
            }

            try {
            // assume we have a root object without braces
                return $this->checkExit($this->object(true));
            } catch (HJSONException $e) {
                // test if we are dealing with a single JSON value instead (true/false/null/num/"")
                $this->resetAt();
                try {
                    return $this->checkExit($this->value());
                } catch (HJSONException $e2) {
                    throw $e;
                } // throw original error
            }
        }

        private function value()
        {
            $this->white();
            switch ($this->ch) {
                case '{':
                    return $this->object();
                case '[':
                    return $this->_array();
                case '"':
                    return $this->string('"');
                case '\'':
                    if ($this->peek(0) !== '\'' || $this->peek(1) !== '\'') {
                        return $this->string('\'');
                    }
                    // Falls through on multiline strings
                default:
                    return $this->tfnns();
            }
        }

        private function string($quote)
        {
            // Parse a string value.
            $hex;
            $string = '';
            $uffff;

            // When parsing for string values, we must look for " and \ characters.
            if ($this->ch === $quote) {
                while ($this->next() !== null) {
                    if ($this->ch === $quote) {
                        $this->next();
                        return $string;
                    }
                    if ($this->ch === "\\") {
                        $this->next();
                        if ($this->ch === 'u') {
                            $uffff = '';
                            for ($i = 0; $i < 4; $i++) {
                                $uffff .= $this->next();
                            }
                            if (!ctype_xdigit($uffff)) {
                                $this->error("Bad \\u char");
                            }
                            $string .= mb_convert_encoding(pack('H*', $uffff), 'UTF-8', 'UTF-16BE');
                        } elseif (@$this->escapee[$this->ch]) {
                            $string .= $this->escapee[$this->ch];
                        } else {
                            break;
                        }
                    } else {
                        $string .= $this->ch;
                    }
                }
            }
            $this->error("Bad string");
        }

        private function _array()
        {
            // Parse an array value.
            // assumeing ch === '['

            $array = [];
            $kw = null;
            $wat = null;

            if ($this->keepWsc) {
                $array['__WSC__'] = [];
                $kw = &$array['__WSC__'];
            }

            $this->next();
            $wat = $this->at;
            $this->white();
            if ($kw !== null) {
                $c = $this->getComment($wat);
                if (trim($c)) {
                    $kw[] = $c;
                }
            }

            if ($this->ch === ']') {
                $this->next();
                return $array;  // empty array
            }

            while ($this->ch !== null) {
                $array[] = $this->value();
                $wat = $this->at;
                $this->white();
                // in Hjson the comma is optional and trailing commas are allowed
                if ($this->ch === ',') {
                    $this->next();
                    $wat = $this->at;
                    $this->white();
                }
                if ($kw !== null) {
                    $c = $this->getComment($wat);
                    if (trim($c)) {
                        $kw[] = $c;
                    }
                }
                if ($this->ch === ']') {
                    $this->next();
                    return $array;
                }
                $this->white();
            }

            $this->error("End of input while parsing an array (did you forget a closing ']'?)");
        }

        private function object($withoutBraces = false)
        {
            // Parse an object value.
            $key = null;
            $object = new \stdClass;
            $kw = null;
            $wat = null;
            if ($this->keepWsc) {
                $kw = new \stdClass;
                $kw->c = new \stdClass;
                $kw->o = [];
                $object->__WSC__ = $kw;
                if ($withoutBraces) {
                    $kw->noRootBraces = true;
                }
            }

            if (!$withoutBraces) {
                // assuming ch === '{'
                $this->next();
                $wat = $this->at;
            } else {
                $wat = 1;
            }

            $this->white();
            if ($kw) {
                $this->pushWhite(" ", $kw, $wat);
            }
            if ($this->ch === '}' && !$withoutBraces) {
                $this->next();
                return $object;  // empty object
            }
            while ($this->ch !== null) {
                $key = $this->keyname();
                $this->white();
                $this->next(':');
                // duplicate keys overwrite the previous value
                if ($key !== '') {
                    $object->$key = $this->value();
                }
                $wat = $this->at;
                $this->white();
                // in Hjson the comma is optional and trailing commas are allowed
                if ($this->ch === ',') {
                    $this->next();
                    $wat = $this->at;
                    $this->white();
                }
                if ($kw) {
                    $this->pushWhite($key, $kw, $wat);
                }
                if ($this->ch === '}' && !$withoutBraces) {
                    $this->next();
                    return $object;
                }
                $this->white();
            }

            if ($withoutBraces) {
                return $object;
            } else {
                $this->error("End of input while parsing an object (did you forget a closing '}'?)");
            }
        }

        private function pushWhite($key, &$kw, $wat)
        {
            $kw->c->$key = $this->getComment($wat);
            if (trim($key)) {
                $kw->o[] = $key;
            }
        }

        private function white()
        {
            while ($this->ch !== null) {
                // Skip whitespace.
                while ($this->ch && $this->ch <= ' ') {
                    $this->next();
                }
                // Hjson allows comments
                if ($this->ch === '#' || $this->ch === '/' && $this->peek(0) === '/') {
                    while ($this->ch !== null && $this->ch !== "\n") {
                        $this->next();
                    }
                } elseif ($this->ch === '/' && $this->peek(0) === '*') {
                    $this->next();
                    $this->next();
                    while ($this->ch !== null && !($this->ch === '*' && $this->peek(0) === '/')) {
                        $this->next();
                    }
                    if ($this->ch !== null) {
                        $this->next();
                        $this->next();
                    }
                } else {
                    break;
                }
            }
        }

        private function error($m)
        {
            $col=0;
            $colBytes = 0;
            $line=1;

            // Start with where we're at now, count back to most recent line break
            // - to determine "column" of error hit
            $i = $this->at;
            while ($i > 0) {

                // Mimic old behavior with mb_substr
                if ($i >= $this->text_length_chars)
                {
                    $ch = "";
                }
                else
                {
                    $ch = $this->text_array[$i];
                }

                --$i;
                if ($ch === "\n") {
                    break;
                }

                $col++;
            }

            // Count back line endings from there to determine line# of error hit
            for (; $i > 0; $i--) {
                if ($this->text_array[$i] === "\n") {
                    $line++;
                }
            }

            throw new HJSONException("$m at line $line, $col >>>". implode(array_slice($this->text_array, $this->at - $col, 20)) ." ...");
        }

        private function next($c = false)
        {
            // If a c parameter is provided, verify that it matches the current character.

            if ($c && $c !== $this->ch) {
                $this->error("Expected '$c' instead of '{$this->ch}'");
            }

            // Get the next character. When there are no more characters,
            // return the empty string.
            $this->ch = ($this->text_length_chars > $this->at) ? $this->text_array[$this->at] : null;
            ++$this->at;
            return $this->ch;
        }

        /**
        * Peek at character at given offset from current "at"
        *  - >=0 - ahead of "at"
        *  - <0 = before "at"
        */
        private function peek($offs)
        {
            $index = $this->at + $offs;

            // Mimic old behavior with mb_substr
            if ($index < 0) $index = 0;
            if ($index >= $this->text_length_chars) return "";

            return $this->text_array[$index];
        }

        private function skipIndent($indent)
        {
            $skip = $indent;
            while ($this->ch && $this->ch <= ' ' && $this->ch !== "\n" && $skip-- > 0) {
                $this->next();
            }
        }

        private function mlString()
        {
            // Parse a multiline string value.
            $string = '';
            $triple = 0;

            // we are at ''' +1 - get indent
            $indent = 0;
            while (true) {
                $c = $this->peek(-$indent-5);
                if ($c === null || $c === "\n") {
                    break;
                }
                $indent++;
            }

            // skip white/to (newline)
            while ($this->ch !== null && $this->ch <= ' ' && $this->ch !== "\n") {
                $this->next();
            }
            if ($this->ch === "\n") {
                $this->next();
                $this->skipIndent($indent);
            }

            // When parsing multiline string values, we must look for ' characters.
            while (true) {
                if ($this->ch === null) {
                    $this->error("Bad multiline string");
                } elseif ($this->ch === '\'') {
                    $triple++;
                    $this->next();
                    if ($triple === 3) {
                        if (substr($string, -1) === "\n") {
                            $string = mb_substr($string, 0, -1); // remove last EOL
                        }
                        return $string;
                    } else {
                        continue;
                    }
                } else {
                    while ($triple > 0) {
                        $string .= '\'';
                        $triple--;
                    }
                }
                if ($this->ch === "\n") {
                    $string .= "\n";
                    $this->next();
                    $this->skipIndent($indent);
                } else {
                    if ($this->ch !== "\r") {
                        $string .= $this->ch;
                    }
                    $this->next();
                }
            }
        }

        private function keyname()
        {
            // quotes for keys are optional in Hjson
            // unless they include {}[],: or whitespace.

            if ($this->ch === '"') {
                return $this->string('"');
            } else if ($this->ch === '\'') {
                return $this->string('\'');
            }

            $name = "";
            $start = $this->at;
            $space = -1;

            while (true) {
                if ($this->ch === ':') {
                    if ($name === '') {
                        $this->error("Found ':' but no key name (for an empty key name use quotes)");
                    } elseif ($space >=0 && $space !== mb_strlen($name)) {
                        $this->at = $start + $space;
                        $this->error("Found whitespace in your key name (use quotes to include)");
                    }
                    return $name;
                } elseif ($this->ch <= ' ') {
                    if (!$this->ch) {
                        $this->error("Found EOF while looking for a key name (check your syntax)");
                    } elseif ($space < 0) {
                        $space = mb_strlen($name);
                    }
                } elseif ($this->isPunctuatorChar($this->ch)) {
                    $this->error("Found '{$this->ch}' where a key name was expected (check your syntax or use quotes if the key name includes {}[],: or whitespace)");
                } else {
                    $name .= $this->ch;
                }
                $this->next();
            }
        }

        private function tfnns()
        {
            // Hjson strings can be quoteless
            // returns string, true, false, or null.

            if ($this->isPunctuatorChar($this->ch)) {
                $this->error("Found a punctuator character '{$this->ch}' when expecting a quoteless string (check your syntax)");
            }

            $value = $this->ch;
            while (true) {
                $isEol = $this->next() === null;
                if (mb_strlen($value) === 3 && $value === "'''") {
                    return $this->mlString();
                }
                $isEol = $isEol || $this->ch === "\r" || $this->ch === "\n";

                if ($isEol || $this->ch === ',' ||
                    $this->ch === '}' || $this->ch === ']' ||
                    $this->ch === '#' ||
                    $this->ch === '/' && ($this->peek(0) === '/' || $this->peek(0) === '*')
                ) {
                    $chf = $value[0];
                    switch ($chf) {
                        case 'f':
                            if (trim($value) === "false") {
                                return false;
                            }
                            break;
                        case 'n':
                            if (trim($value) === "null") {
                                return null;
                            }
                            break;
                        case 't':
                            if (trim($value) === "true") {
                                return true;
                            }
                            break;
                        default:
                            if ($chf === '-' || $chf >= '0' && $chf <= '9') {
                                $n = HJSONUtils::tryParseNumber($value);
                                if ($n !== null) {
                                    return $n;
                                }
                            }
                    }
                    if ($isEol) {
                        // remove any whitespace at the end (ignored in quoteless strings)
                        return trim($value);
                    }
                }
                $value .= $this->ch;
            }
        }

        private function getComment($wat)
        {
            $i;
            $wat--;
            // remove trailing whitespace
            for ($i = $this->at - 2; $i > $wat && $this->text_array[$i] <= ' ' && $this->text_array[$i] !== "\n"; $i--) {
            }

            // but only up to EOL
            if ($this->text_array[$i] === "\n") {
                $i--;
            }
            if ($this->text_array[$i] === "\r") {
                $i--;
            }

            $res = array_slice($this->text_array, $wat, $i-$wat+1);
            $res_len = count($res);
            for ($i = 0; $i < $res_len; $i++) {
                if ($res[$i] > ' ') {
                    return $res;
                }
            }

            return "";
        }
    }
}

/**
 * NOTE: this may return an empty string at the end of the array when the input
 * string ends with a newline character
 */
if (!function_exists("HJSON_mb_str_split"))
{
    function HJSON_mb_str_split($string)
    {
        return preg_split('/(?<!^)/u', $string);
    }
}

if (!class_exists("HJSONStringifier"))
{
    class HJSONStringifier
    {

        // needsEscape tests if the string can be written without escapes
        private $needsEscape = '/[\\\"\x00-\x1f\x7f-\x9f\x{00ad}\x{0600}-\x{0604}\x{070f}\x{17b4}\x{17b5}\x{200c}-\x{200f}\x{2028}-\x{202f}\x{2060}-\x{206f}\x{feff}\x{fff0}-\x{ffff}\x]/u';
        // needsQuotes tests if the string can be written as a quoteless string (includes needsEscape but without \\ and \")
        private $needsQuotes = '/^\\s|^"|^\'|^\'\'\'|^#|^\\/\\*|^\\/\\/|^\\{|^\\}|^\\[|^\\]|^:|^,|\\s$|[\x00-\x1f\x7f-\x9f\x{00ad}\x{0600}-\x{0604}\x{070f}\x{17b4}\x{17b5}\x{200c}-\x{200f}\x{2028}-\x{202f}\x{2060}-\x{206f}\x{feff}\x{fff0}-\x{ffff}\x]/u';
        // needsEscapeML tests if the string can be written as a multiline string (includes needsEscape but without \n, \r, \\ and \")
        private $needsEscapeML = '/^\\s+$|\'\'\'|[\x00-\x08\x0b\x0c\x0e-\x1f\x7f-\x9f\x{00ad}\x{0600}-\x{0604}\x{070f}\x{17b4}\x{17b5}\x{200c}-\x{200f}\x{2028}-\x{202f}\x{2060}-\x{206f}\x{feff}\x{fff0}-\x{ffff}\x]/u';
        private $startsWithKeyword = '/^(true|false|null)\s*((,|\]|\}|#|\/\/|\/\*).*)?$/';
        private $needsEscapeName = '/[,\{\[\}\]\s:#"\']|\/\/|\/\*|\'\'\'/';
        private $gap = '';
        private $indent = '  ';
		private $meta = null;


        // options
        private $eol;
        private $keepWsc;
        private $bracesSameLine;
        private $quoteAlways;
        private $forceKeyQuotes;
        private $emitRootBraces;

        private $defaultBracesSameLine = false;

        public function __construct()
        {
            $this->meta = [
                "\t" => "\\t",
                "\n" => "\\n",
                "\r" => "\\r",
                '"'  => '\\"',
                '\''  => '\\\'',
                '\\' => "\\\\"
            ];
            $this->meta[chr(8)] = '\\b';
            $this->meta[chr(12)] = '\\f';
        }


        public function stringify($value, $opt = [])
        {
            $this->eol = PHP_EOL;
            $this->indent = '  ';
            $this->keepWsc = false;
            $this->bracesSameLine = $this->defaultBracesSameLine;
            $this->quoteAlways = false;
            $this->forceKeyQuotes = false;
            $this->emitRootBraces = true;
            $space = null;

            if ($opt && is_array($opt)) {
                if (@$opt['eol'] === "\n" || @$opt['eol'] === "\r\n") {
                    $this->eol = $opt['eol'];
                }
                $space = @$opt['space'];
                $this->keepWsc = @$opt['keepWsc'];
                $this->bracesSameLine = @$opt['bracesSameLine'] || $this->defaultBracesSameLine;
                $this->emitRootBraces = @$opt['emitRootBraces'];
                $this->quoteAlways = @$opt['quotes'] === 'always';
                $this->forceKeyQuotes = @$opt['keyQuotes'] === 'always';
            }

            // If the space parameter is a number, make an indent string containing that
            // many spaces. If it is a string, it will be used as the indent string.
            if (is_int($space)) {
                $this->indent = '';
                for ($i = 0; $i < $space; $i++) {
                    $this->indent .= ' ';
                }
            } elseif (is_string($space)) {
                $this->indent = $space;
            }

            // Return the result of stringifying the value.
            return $this->str($value, null, true, true);
        }

        public function stringifyWsc($value, $opt = [])
        {
            return $this->stringify($value, array_merge($opt, ['keepWsc' => true]));
        }

        private function isWhite($c)
        {
            return $c <= ' ';
        }

        private function quoteReplace($string)
        {
            mb_ereg_search_init($string, $this->needsEscape);
            $r = mb_ereg_search();
            $chars = HJSON_mb_str_split($string);
            $chars = array_map(function ($char) {
                if (preg_match($this->needsEscape, $char)) {
                    $a = $char;
                    $c = @$this->meta[$a] ?: null;
                    if (gettype($c) === 'string') {
                        return $c;
                    } else {
                        return $char;
                    }
                } else {
                    return $char;
                }
            }, $chars);

            return implode('', $chars);
        }

        private function quote($string = null, $gap = null, $hasComment = null, $isRootObject = null)
        {
            if (!$string) {
                return '""';
            }

            // Check if we can insert this string without quotes
            // see hjson syntax (must not parse as true, false, null or number)
            if ($this->quoteAlways || $hasComment ||
                preg_match($this->needsQuotes, $string) ||
                HJSONUtils::tryParseNumber($string, true) !== null ||
                preg_match($this->startsWithKeyword, $string)) {
                // If the string contains no control characters, no quote characters, and no
                // backslash characters, then we can safely slap some quotes around it.
                // Otherwise we first check if the string can be expressed in multiline
                // format or we must replace the offending characters with safe escape
                // sequences.

                if (!preg_match($this->needsEscape, $string)) {
                    return '"' . $string . '"';
                } elseif (!preg_match($this->needsEscapeML, $string) && !$isRootObject) {
                    return $this->mlString($string, $gap);
                } else {
                    return '"' . $this->quoteReplace($string) . '"';
                }
            } else {
                // return without quotes
                return $string;
            }
        }

        private function mlString($string, $gap)
        {
            // wrap the string into the ''' (multiline) format

            $a = explode("\n", mb_ereg_replace("\r", "", $string));
            $gap .= $this->indent;

            if (count($a) === 1) {
                // The string contains only a single line. We still use the multiline
                // format as it avoids escaping the \ character (e.g. when used in a
                // regex).
                return "'''" . $a[0] . "'''";
            } else {
                $res = $this->eol . $gap . "'''";
                for ($i = 0; $i < count($a); $i++) {
                    $res .= $this->eol;
                    if ($a[$i]) {
                        $res .= $gap . $a[$i];
                    }
                }
                return $res . $this->eol . $gap . "'''";
            }
        }

        private function quoteName($name)
        {
            if (!$name) {
                return '""';
            }

            // Check if we can insert this name without quotes
            if (preg_match($this->needsEscapeName, $name)) {
                return '"' . (preg_match($this->needsEscape, $name) ? $this->quoteReplace($name) : $name) . '"';
            } else {
                // return without quotes
                return $name;
            }
        }

        private function str($value, $hasComment = null, $noIndent = null, $isRootObject = null)
        {
            // Produce a string from value.

            $startsWithNL = function ($str) {
                return $str && $str[$str[0] === "\r" ? 1 : 0] === "\n";
            };
            $testWsc = function ($str) use ($startsWithNL) {
                return $str && !$startsWithNL($str);
            };
            $wsc = function ($str) {
                if (!$str) {
                    return "";
                }
                for ($i = 0; $i < mb_strlen($str); $i++) {
                    $c = $str[$i];
                    if ($c === "\n" ||
                        $c === '#' ||
                        $c === '/' && ($str[$i+1] === '/' || $str[$i+1] === '*')) {
                        break;
                    }
                    if ($c > ' ') {
                        return ' # ' . $str;
                    }
                }
                return $str;
            };

            // What happens next depends on the value's type.
            switch (gettype($value)) {
                case 'string':
                    $str = $this->quote($value, $this->gap, $hasComment, $isRootObject);
                    return $str;

                case 'integer':
                case 'double':
                    return is_numeric($value) ? str_replace('E', 'e', "$value") : 'null';

                case 'boolean':
                    return $value ? 'true' : 'false';

                case 'NULL':
                    return 'null';

                case 'object':
                case 'array':
                    $isArray = is_array($value);

                    $isAssocArray = function (array $arr) {
                        if (array() === $arr) {
                            return false;
                        }
                        return array_keys($arr) !== range(0, count($arr) - 1);
                    };
                    if ($isArray && $isAssocArray($value)) {
                        $value = (object) $value;
                        $isArray = false;
                    }

                    $kw = null;
                    $kwl = null; // whitespace & comments
                    if ($this->keepWsc) {
                        if ($isArray) {
                            $kw = @$value['__WSC__'];
                        } else {
                            $kw = @$value->__WSC__;
                        }
                    }

                    $showBraces = $isArray || !$isRootObject || ($kw ? !@$kw->noRootBraces : $this->emitRootBraces);

                    // Make an array to hold the partial results of stringifying this object value.
                    $mind = $this->gap;
                    if ($showBraces) {
                        $this->gap .= $this->indent;
                    }
                    $eolMind = $this->eol . $mind;
                    $eolGap = $this->eol . $this->gap;
                    $prefix = $noIndent || $this->bracesSameLine ? '' : $eolMind;
                    $partial = [];

                    $k;
                    $v; // key, value

                    if ($isArray) {
                        // The value is an array. Stringify every element. Use null as a placeholder
                        // for non-JSON values.

                        $length = count($value);
                        if (array_key_exists('__WSC__', $value)) {
                            $length--;
                        }

                        for ($i = 0; $i < $length; $i++) {
                            if ($kw) {
                                $partial[] = $wsc(@$kw[$i]) . $eolGap;
                            }
                            $str = $this->str($value[$i], $kw ? $testWsc(@$kw[$i+1]) : false, true);
                            $partial[] = $str !== null ? $str : 'null';
                        }
                        if ($kw) {
                            $partial[] = $wsc(@$kw[$i]) . $eolMind;
                        }

                        // Join all of the elements together, separated with newline, and wrap them in
                        // brackets.
                        if ($kw) {
                            $v = $prefix . '[' . implode('', $partial) . ']';
                        } elseif (count($partial) === 0) {
                            $v = '[]';
                        } else {
                            $v = $prefix . '[' . $eolGap . implode($eolGap, $partial) . $eolMind . ']';
                        }
                    } else {
                        // Otherwise, iterate through all of the keys in the object.

                        if ($kw) {
                            $emptyKey = " ";
                            $kwl = $wsc($kw->c->$emptyKey);
                            $keys = $kw->o;
                            foreach ($value as $k => $vvv) {
                                $keys[] = $k;
                            }
                            $keys = array_unique($keys);

                            for ($i = 0, $length = count($keys); $i < $length; $i++) {
                                $k = $keys[$i];
                                if ($k === '__WSC__') {
                                    continue;
                                }
                                if ($showBraces || $i>0 || $kwl) {
                                    $partial[] = $kwl . $eolGap;
                                }
                                $kwl = $wsc($kw->c->$k);
                                $v = $this->str($value->$k, $testWsc($kwl));
                                if ($v !== null) {
                                    $partial[] = $this->quoteName($k) . ($startsWithNL($v) ? ':' : ': ') . $v;
                                }
                            }
                            if ($showBraces || $kwl) {
                                $partial[] = $kwl . $eolMind;
                            }
                        } else {
                            foreach ($value as $k => $vvv) {
                                $v = $this->str($vvv);
                                if ($v !== null) {
                                    $partial[] = $this->quoteName($k) . ($startsWithNL($v) ? ':' : ': ') . $v;
                                }
                            }
                        }

                        // Join all of the member texts together, separated with newlines
                        if (count($partial) === 0) {
                            $v = '{}';
                        } elseif ($showBraces) {
                            // and wrap them in braces
                            if ($kw) {
                                $v = $prefix . '{' . implode('', $partial) . '}';
                            } else {
                                $v = $prefix . '{' . $eolGap . implode($eolGap, $partial) . $eolMind . '}';
                            }
                        } else {
                            $v = implode($kw ? '' : $eolGap, $partial);
                        }
                    }

                    $this->gap = $mind;
                    return $v;
            }
        }
    }
}

if (!class_exists("HJSONUtils"))
{
    class HJSONUtils
    {

        public static function tryParseNumber($text, $stopAtNext = null)
        {
            // Parse a number value.
            $number = null;
            $string = '';
            $leadingZeros = 0;
            $testLeading = true;
            $at = 0;
            $ch = null;
            
            $next = function () use ($text, &$ch, &$at) {
                $ch = mb_strlen($text) > $at ? $text[$at] : null;
                $at++;
                return $ch;
            };

            $next();

            if ($ch === '-') {
                $string = '-';
                $next();
            }

            while ($ch !== null && $ch >= '0' && $ch <= '9') {
                if ($testLeading) {
                    if ($ch == '0') {
                        $leadingZeros++;
                    } else {
                        $testLeading = false;
                    }
                }
                $string .= $ch;
                $next();
            }
            if ($testLeading) {
                $leadingZeros--; // single 0 is allowed
            }
            if ($ch === '.') {
                $string .= '.';
                while ($next() !== null && $ch >= '0' && $ch <= '9') {
                    $string .= $ch;
                }
            }
            if ($ch === 'e' || $ch === 'E') {
                $string .= $ch;
                $next();
                if ($ch === '-' || $ch === '+') {
                    $string .= $ch;
                    $next();
                }
                while ($ch !== null && $ch >= '0' && $ch <= '9') {
                    $string .= $ch;
                    $next();
                }
            }

            // skip white/to (newline)
            while ($ch !== null && $ch <= ' ') {
                $next();
            }

            if ($stopAtNext) {
                // end scan if we find a control character like ,}] or a comment
                if ($ch === ',' || $ch === '}' || $ch === ']' ||
                    $ch === '#' || $ch === '/' && ($text[$at] === '/' || $text[$at] === '*')) {
                    $ch = null;
                }
            }

            $number = $string;
            if (is_numeric($string)) {
                $number = 0+$string;
            }


            if ($ch !== null || $leadingZeros || !is_numeric($number)) {
                return null;
            } else {
                return $number;
            }
        }
    }
}

// Note: leave the end tag for packaging
?>
