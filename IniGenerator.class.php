<?php declare(strict_types = 1);

namespace {
    const DELIMITER      = "#";
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
}

namespace PegasusICT\PhpHelpers {
    use function array_key_exists, in_array, gettype;

    trait MyTimestamp {
        /**
         * @param string|null $format
         * @param float|null  $microtime
         *
         * @return false|string
         */
        static function timestamp(string $format = null, float $microtime = null) {
            $microtime = $microtime ?? microtime(true);
            $format    = $format ?? "Y-m-d H:i:s,u T";

            $timestamp    = (int)floor($microtime);
            $milliseconds = round(($microtime - $timestamp) * 1000000);

            return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
        }
    }
    /**
     * Trait IniLog
     *
     * @package PegasusICT\PhpHelpers
     *
     * @method static void critical(string $__FUNCTION__, string $line = '')
     * @method static void error   (string $__FUNCTION__, string $line = '')
     * @method static void warning (string $__FUNCTION__, string $line = '')
     * @method static void notice  (string $__FUNCTION__, string $line = '')
     * @method static void info    (string $__FUNCTION__, string $line = '')
     * @method static void verbose (string $__FUNCTION__, string $line = '')
     * @method static void debug   (string $__FUNCTION__, string $line = '')
     *
     * @method static void printErrors ()
     * @method static string returnErrors ()
     * @method static void printWarnings ()
     * @method static string returnWarnings ()
     * @method static void printMessages ()
     * @method static string returnMessages ()
     * @method static void printAll ()
     * @method static string returnAll ()
     *
     * @method static void setLevel (string $level = 'debug')
     */
    trait IniLog {
        use MyTimestamp;
        private static $_level    = 'debug';
        private static $_levels   = ["disabled", "critical", "error", "warning", "notice", "info", "verbose", "debug"];
        private static $_logs     = [];
        private static $_subjects = [
            'All'      => [
                'min' => 'critical',
                'max' => 'debug'
            ],
            'Errors'   => [
                'min' => 'critical',
                'max' => 'error'
            ],
            'Warnings' => [
                'min' => 'warning',
                'max' => 'notice'
            ],
            'Messages' => [
                'min' => 'info',
                'max' => 'debug'
            ]
        ];

        private static function _init(){
            // Initialize Logs Array if necessary
            if(empty(self::$_logs)) {
                foreach(array_keys(self::$_subjects) as $subject) {
                    self::$_logs[$subject] = '';
                }
            }
        }
        /**
         * @param string     $name
         * @param array|null $arguments
         *
         * @return void|string
         */
        public static function __callStatic(string $name, ?array $arguments) {
            self::_init();
// message to log(s)
            if(in_array($name, self::$_levels, false)) {
                self::_log($name, $arguments[0] ?? "unknown function", $arguments[1] ?? "");
            } // set maximum log level
            elseif($name === 'setLevel') {
                self::$_level = in_array($arguments[1], self::$_levels, false) ? $arguments[1] : "debug";
            } // print or return log messages
            else {
                $actions = ['print', 'return'];
                foreach($actions as $action) {
                    $length = strlen($action);
                    if(strncmp($name, $action, $length) === 0) {
                        $category = substr($name, $length);
                        if( ! array_key_exists($category, self::$_logs)) {
                            $category = 'All';
                        }
                        if( ! empty(self::$_logs[$category])) {
                            $result = self::$_logs[$category];
                        } else {
                            $result = "Nothing to report.\n";
                        }

                        if($action === "print") {
                            print $result;
                        } else {
                            return $result;
                        }
                    }
                }
            }
        }

        /**
         * @param string $function
         * @param string $line
         * @param string $level
         */
        private static function _log(string $level, string $function, string $line = "") {
            foreach(self::$_subjects as $subject => $levels) {
                $levelIndexes = array_flip(self::$_levels);
                $min          = $levelIndexes[$levels['min']];
                $max          = $levelIndexes[$levels['max']];
                $levelIndex   = $levelIndexes[$level];
                $maxLevel     = $levelIndexes[self::$_level];
                if($levelIndex >= $min && $levelIndex <= $max && $levelIndex <= $maxLevel) {
                    self::$_logs[$subject] .= MyTimestamp::timestamp("H:i:s,u") . " [" . strtoupper($level) .
                                              "] $function(): $line\n";
                }
            }
        }
    }
    /**
     * Class IniGenerator
     *
     * @package PegasusICT\PhpHelpers
     */
    class IniGenerator {
        private static $_level = 0;

        public static function array2ini(array $array = [], string $section = null) {
            IniLog::debug(__FUNCTION__, "Level: " . self::$_level);
            $result = '';
            if(empty($array)) {
                IniLog::notice(__FUNCTION__, "array is empty");
            } else {
                uasort($array, __CLASS__ . '::_sortValueBeforeSubArray');
                self::$_level++;
                foreach($array as $key => $value) {
                    if(strncmp($key, ';', 1) === 0) {
                        IniLog::debug(__FUNCTION__, "inserting comment line");
                        $result .= "; " . preg_replace("/[@]{3}/", date("Y-m-d H:i:s T"), $value) . "\n";
                    } else {
                        if(is_array($value)) {
                            if(self::$_level == 0) {
                                if(null !== $section || $key === $section) {
                                    $result .= "[" . $key . "]\n";
                                }
                                $result .= self::_processSecondaryArray($key, $value);
                            } elseif(self::$_level == 3) {
                                if(null !== $section) {
                                    $key = $section . "[" . $key . "]";
                                }
                                $result .= $key . " = \"" . implode(DELIMITER, $value) . "\"\n";
                            } else {
                                $result .= self::_processSubArray($key, $value, $section);
                            }
                        } else {
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
                    }
                }
            }
            return $result;
        }

        private static function _processSecondaryArray(string $label, array $array): string {
            IniLog::debug(__FUNCTION__, "Level: " . self::$_level++);
            $result     = '';
            $testResult = self::_testArray($array);
            if($testResult === ARRAY_IS_ASSOC || $testResult === ARRAY_IS_NUM) {
                IniLog::debug(__FUNCTION__,
                              "$label = " . ($testResult == ARRAY_IS_ASSOC) ? "associative" : "numerical");

                foreach($array as $subKey => $subValue) {
                    if(is_array($subValue)) {
                        $result .= self::_processSecondaryArray($label . "[" . $subKey . "]", $subValue);
                    } else {
                        $result .= $label . "[" . $subKey . "] = $subValue\n";
                    }
                }
            } else {
                IniLog::debug(__FUNCTION__, "$label = sequential");

                foreach($array as $subKey => $subValue) {
                    if(is_array($subValue)) {
                        $result .= self::_processSecondaryArray($label . "[]", $subValue);
                    } else {
                        $result .= $label . "[] = $subValue\n";
                    }
                }
            }
            self::$_level--;
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
                          " level = " . self::$_level);
            $result = "";
            if(self::$_level === 1) {
                if(($section === null || $key === $section) && ! empty($value)) {
                    $result .= "\n[$key]\n" . self::array2ini($value, null);
                }
            } else {
                $result .= /*"$key = ". */
                    self::array2ini($value, $key);
            }
            return $result;
        }

        /**
         * @param array $array
         * @param int   $test
         *
         * @return int
         */
        private static function _testArray(array $array, int $test = ARRAY_IS) {
            IniLog::debug(__FUNCTION__, "test = " . ARRAY_RESULTS[$test]);
            if(array() === $array) {
                $result = ARRAY_IS_EMPTY;
            } elseif(array_keys($array) === range(0, count($array) - 1)) {
                $result = ARRAY_IS_SEQ;
            } elseif(count(array_filter(array_keys($array), 'is_string')) > 0) {
                $result = ARRAY_IS_ASSOC;
            } else {
                $result = ARRAY_IS_NUM;
            }
            IniLog::debug(__FUNCTION__, "array = " . ARRAY_RESULTS[$result]);

            if($test === ARRAY_IS) {
                return $result;
            }
            return ($test | $result);
        }

        /**
         * @param mixed $a
         * @param mixed $b
         *
         * @return int
         */
        private static function _sortValueBeforeSubArray($a, $b) {
            IniLog::debug(__FUNCTION__);
            $aa = is_array($a);
            $bb = is_array($b);

            if($aa == $bb) {
                return 0;
            }
            return ($aa < $bb) ? -1 : 1;
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
                } else {
                    if(is_string($value) && strpos($value, DELIMITER) !== false) {
                        $result[$key] = explode(DELIMITER, $value) ? : $value;
                        //     print "exploded array: ". explode(DELIMITER, $value);
                    } else {
                        $result[$key] = $value;
                    }
                }
            }
            return $result;
        }

        /**
         * @param string $iniString
         *
         * @return array
         */
        public static function ini2array(string $ini, $isFile = false): array {
            IniLog::debug(__FUNCTION__, "Parsing a ".($isFile?"file":"string").".");
            if($isFile) { return self::_expandArray(parse_ini_file($ini, true, INI_SCANNER_TYPED)); }
            return self::_expandArray(parse_ini_string($ini, true, INI_SCANNER_TYPED));
        }

        /**
         * @param array  $configData
         * @param string $cfgFile
         * @param string $configDir
         */
        public static function generateIniFile($configData = [], $cfgFile = "", $configDir = "cfg", $fileHeader = null,
                                               $timestamp = true): void {
            IniLog::debug(__FUNCTION__);
            //Tools::checkDir($configDir);
            file_put_contents($configDir . $cfgFile, ($fileHeader ? : "# Config file generated at ") .
                                                     ($timestamp ? MyTimestamp::timestamp() : '') . "\n" .
                                                     self::array2ini($configData));
        }
    }
}

namespace {
    $cfgData = [
        //';10'       => "made with ini generator by Mattijs Snepvangers",
        //';20'       => "Generated at @@@",
        'log_level' => 'debug',
        'log_type'  => 'file',
        'log_file'  => [
            'split'           => true,
            'filename_format' => 'base_name sub_name date',
            'rotate'          => 'day',
            'base_name'       => 'phplog',

            'sub_name' => ['errors'   => ['critical', 'error'],
                           'messages' => ['warning', 'info'],
                           'debug'    => ['verbose', 'debug']
            ],
            'date'     => 'Y-m-d',
        ],
        'log_line'  => 'timestamp [level] class->function(): message',
        'timestamp' => 'H:i:s,u'
    ];
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $iniString = PegasusICT\PhpHelpers\IniGenerator::array2ini($cfgData);
    echo (PegasusICT\PhpHelpers\IniGenerator::ini2array($iniString) == $cfgData) ? "success" :"fail";
// only works if you leave out the comment lines, need to work on that...
    // TODO: parse iniString/File comment lines into ini2array, sort arrays before comparing is necessary
}
