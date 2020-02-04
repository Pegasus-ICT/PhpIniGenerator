<?php declare(strict_types = 1);
/**
 * PHP Ini Generator
 *
 * PHP version 7.2+
 *
 * @category  Configuration_Generator
 * @package   PhpIniGenerator
 * @author    Mattijs Snepvangers <pegasus.ict@gmail.com>
 * @copyright 2019-2020 Pegasus ICT Dienstverlening
 * @license   MIT License
 * @link      https://github.com/Pegasus-ICT/PhpIniGenerator/
 */
namespace {
    const ARRAY_IS       = 0b0000;
    const ARRAY_IS_SEQ   = 0b0001;
    const ARRAY_IS_ASSOC = 0b0010;
    const ARRAY_IS_NUM   = 0b0100;
    const ARRAY_IS_EMPTY = 0b1000;
    const ARRAY_RESULTS  = [
        ARRAY_IS_SEQ   => "sequential",
        ARRAY_IS_NUM   => "numerical",
        ARRAY_IS_ASSOC => "associative",
        ARRAY_IS_EMPTY => "empty",
        ARRAY_IS       => "what?"
    ];

    require_once __DIR__ . "/timestamp.trait.php";
    require_once __DIR__ . "/inilog.trait.php";
}
namespace PegasusICT\PhpIniGenerator {

    use function gettype;

    /**
     * Class IniGenerator
     *
     * @package PegasusICT\PhpIniGenerator
     */
    class IniGenerator {
        private static $level     = 0;
        private static $delimiter = "#";

        /**
         * @param array       $array
         * @param string|null $section
         *
         * @return string
         */
        public static function array2ini(array $array = [], ?string $section = null) {
            IniLog::debug(__FUNCTION__, "Level: " . self::$level);
            $result = '';
            if( ! empty($array)) {
                uasort($array, "PegasusICT\\PhpIniGenerator\\::sortValueBeforeSubArray");
                self::$level++;
                foreach($array as $key => $value) {
                    if(strncmp($key, ';', 1) === 0) {
                        IniLog::debug(__FUNCTION__, "inserting comment line");
                        $result .= "; " . preg_replace("/[@]{3}/", date("Y-m-d H:i:s T"), $value) . "\n";
                        continue;
                    }
                    if(is_array($value)) {
                        if(self::$level == 0) {
                            if(null !== $section || $key === $section) {
                                $result .= "[" . $key . "]\n";
                            }
                            $result .= self::_processSecondaryArray($key, $value);
                        } elseif(self::$level == 3) {
                            if(null !== $section) {
                                $key = $section . "[" . $key . "]";
                            }
                            $result .= $key . " = \"" . implode(self::$delimiter, $value) . "\"\n";
                            continue;
                        }
                        $result .= self::_processSubArray($key, $value, $section);
                        continue;
                    }
                    switch(gettype($value)) {
                        case 'boolean':
                            $result .= "$key = " . ($value ? 'true' : 'false') . "\n";
                            break;
                        case 'integer': // treat same as 'double'
                        case 'double':
                            $result .= "$key = $value\n";
                            break;
                        case 'string':
                            $result .= "$key = \"$value\"\n";
                            break;
                        case 'array':
                            break; // skip
                        default:
                            $result .= "$key = null\n";
                            break; // "NULL", "object", "resource", "resource (closed)", "unknown type"
                    }
                }
                return $result;
            }

            IniLog::notice(__FUNCTION__, "array is empty");
            return $result;
        }

        private static function _processSecondaryArray(string $label, array $array): string {
            IniLog::debug(__FUNCTION__, "Level: " . self::$level++);
            $result     = '';
            $testResult = self::_testArray($array);
            if($testResult === ARRAY_IS_ASSOC || $testResult === ARRAY_IS_NUM) {
                IniLog::debug(__FUNCTION__, "$label = " . ($testResult == ARRAY_IS_ASSOC) ? "associative" : "numerical");
                foreach($array as $subKey => $subValue) {
                    if(is_array($subValue)) {
                        $result .= self::_processSecondaryArray($label . "[" . $subKey . "]", $subValue);
                        continue;
                    }
                    $result .= $label . "[" . $subKey . "] = $subValue\n";
                }
                self::$level--;
                return $result;
            }
            IniLog::debug(__FUNCTION__, "$label = sequential");

            foreach($array as $subKey => $subValue) {
                if(is_array($subValue)) {
                    $result .= self::_processSecondaryArray($label . "[]", $subValue);
                    continue;
                }
                $result .= $label . "[] = $subValue\n";
            }

            self::$level--;
            return $result;
        }

        /**
         * @param string      $key
         * @param array       $value
         * @param string|null $section
         *
         * @return string
         */
        private static function _processSubArray(string $key = '', array $value = [], string $section = null) {
            IniLog::debug(__FUNCTION__,
                          "key = $key, value has " . count($value) . " elements, section = " . ($section ? : "null") .
                          " level = " . self::$level);

            if(self::$level !== 1) {
                return self::array2ini($value, $key);
            }

            if(($section === null || $key === $section) && ! empty($value)) {
                return "\n[$key]\n" . self::array2ini($value, null);
            }
            return '';
        }

        /**
         * @param array $array
         * @param int   $test
         *
         * @return int
         */
        private static function _testArray(array $array, int $test = ARRAY_IS) {
            IniLog::debug(__FUNCTION__, "test = " . ARRAY_RESULTS[$test]);
            $result = ARRAY_IS_NUM;
            if(array() === $array) {
                $result = ARRAY_IS_EMPTY;
            } elseif(array_keys($array) === range(0, count($array) - 1)) {
                $result = ARRAY_IS_SEQ;
            } elseif(count(array_filter(array_keys($array), 'is_string')) > 0) {
                $result = ARRAY_IS_ASSOC;
            }
            IniLog::debug(__FUNCTION__, "array = " . ARRAY_RESULTS[$result]);

            if($test === ARRAY_IS) {
                return $result;
            }

            return ($test | $result);
        }

        /**
         * @param mixed $varA
         * @param mixed $varB
         *
         * @return int
         * @noinspection PhpUnused
         */
        public static function sortValueBeforeSubArray($varA, $varB) {
            $is_arrayA = is_array($varA);
            $is_arrayB = is_array($varB);

            if($is_arrayA == $is_arrayB) { return 0; }
            return ($is_arrayA < $is_arrayB) ? -1 : 1;
        }

        /**
         * @param $array
         *
         * @return array
         */
        private static function _expandArray($array) {
            $result = [];
            foreach($array as $key => $value) {
                if(is_array($value)) {
                    $result[$key] = self::_expandArray($value);
                } elseif(is_string($value) && strpos($value, self::$delimiter) !== false) {
                    $result[$key] = explode(self::$delimiter, $value) ? : $value;

                    continue;
                }
                $result[$key] = $value;
            }
            return $result;
        }

        /**
         * @param string $ini
         * @param bool   $isFile
         *
         * @return array
         */
        public static function ini2array(string $ini, $isFile = false): array {
            IniLog::debug(__FUNCTION__, "Parsing a " . ($isFile ? "file" : "string") . ".");

            if($isFile) {
                return self::_expandArray(parse_ini_file($ini, true, INI_SCANNER_TYPED));
            }

            return self::_expandArray(parse_ini_string($ini, true, INI_SCANNER_TYPED));
        }

        /**
         * @param array  $configData
         * @param string $cfgFile
         * @param string $configDir
         * @param null   $fileHeader
         * @param bool   $timestamp
         */
        public static function generateIniFile($configData = [], $cfgFile = "", $configDir = "cfg", $fileHeader = null,
                                               $timestamp = true): void {
            IniLog::debug(__FUNCTION__);
            //check: Tools::checkDir($configDir);
            file_put_contents($configDir . $cfgFile, ($fileHeader ? : "; Config file generated at ") .
                                                     ($timestamp ? Timestamp::timestamp() : '') . "\n" .
                                                     self::array2ini($configData));
        }
    }
}
