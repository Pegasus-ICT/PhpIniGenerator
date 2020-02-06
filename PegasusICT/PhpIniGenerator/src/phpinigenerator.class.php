<?php declare(strict_types = 1);
/**
 * PHP Ini Generator
 *
 * PHP version ^7.2
 *
 * @category  Configuration_Generator
 * @package   PhpIniGenerator
 * @author    Mattijs Snepvangers <pegasus.ict@gmail.com>
 * @copyright 2019-2020 Pegasus ICT Dienstverlening
 * @license   MIT License
 * @link      https://github.com/Pegasus-ICT/PhpIniGenerator/
 */
namespace PegasusICT\PhpIniGenerator {
    use function gettype;

    require_once __DIR__ . "/timestamp.trait.php";
    require_once __DIR__ . "/inilogger.class.php";
    require_once __DIR__ . "/iniconstants.php";

    /**
     * Class IniGenerator
     *
     * @package PegasusICT\PhpIniGenerator
     */
    class IniGenerator {
        private $level = 0;
        private $delimiter    = "#";
        private $logger;

        /**
         * IniGenerator constructor.
         */
        public function __construct() {
            $this->logger = new IniLogger();
            return $this;
        }


        /**
         * @param string $delimiter
         */
        public function setDelimiter( string $delimiter ): IniGenerator {
            $this->delimiter = $delimiter;
            return $this;
        }

        /**
         * @return string
         */
        public function getDelimiter(): string {
            return $this->delimiter;
        }


        /**
         * @param array       $array        array with configuration data
         * @param string|null $section      section to include exclusively
         *
         * @return string                   generated ini string
         */
        public function array2ini(array $array = [], ?string $section = null) :IniGenerator{
            IniLog::debug(__FUNCTION__, "Level: " . $this->level);
            $result = '';
            if( ! empty($array)) {
                uasort($array, "PegasusICT\\PhpIniGenerator\\::sortValueBeforeSubArray");
                $this->level++;
                foreach($array as $key => $value) {
                    if(strncmp($key, ';', 1) === 0) {
                        IniLog::debug(__FUNCTION__, "inserting comment line");
                        $result .= "; " . preg_replace("/[@]{3}/", date("Y-m-d H:i:s T"), $value) . "\n";
                        continue;
                    }
                    if(is_array($value)) {
                        if($this->level == 0) {
                            if(null !== $section || $key === $section) {
                                $result .= "[" . $key . "]\n";
                            }
                            $result .= $this->_processSecondaryArray($key, $value);
                        } elseif($this->level == 3) {
                            if(null !== $section) {
                                $key = $section . "[" . $key . "]";
                            }
                            $result .= $key . " = \"" . implode($this->delimiter, $value) . "\"\n";
                            continue;
                        }
                        $result .= $this->_processSubArray($key, $value, $section);
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

        private function _processSecondaryArray(string $label, array $array): string {
            IniLog::debug(__FUNCTION__, "Level: " . $this->level++);
            $result     = '';
            $testResult = $this->_testArray($array);
            if($testResult === ARRAY_IS_ASSOC || $testResult === ARRAY_IS_NUM) {
                IniLog::debug(__FUNCTION__, "$label = " . ($testResult == ARRAY_IS_ASSOC) ? "associative" : "numerical");
                foreach($array as $subKey => $subValue) {
                    if(is_array($subValue)) {
                        $result .= $this->_processSecondaryArray($label . "[" . $subKey . "]", $subValue);
                        continue;
                    }
                    $result .= $label . "[" . $subKey . "] = $subValue\n";
                }
                $this->level--;
                return $result;
            }
            IniLog::debug(__FUNCTION__, "$label = sequential");

            foreach($array as $subKey => $subValue) {
                if(is_array($subValue)) {
                    $result .= $this->_processSecondaryArray($label . "[]", $subValue);
                    continue;
                }
                $result .= $label . "[] = $subValue\n";
            }

            $this->level--;
            return $result;
        }

        /**
         * @param string      $key
         * @param array       $value
         * @param string|null $section
         *
         * @return string
         */
        private function _processSubArray(string $key = '', array $value = [], string $section = null) {
            IniLog::debug(__FUNCTION__,
                          "key = $key, value has " . count($value) . " elements, section = " . ($section ? : "null") .
                          " level = " . $this->level);

            if($this->level !== 1) {
                return $this->array2ini($value, $key);
            }

            if(($section === null || $key === $section) && ! empty($value)) {
                return "\n[$key]\n" . $this->array2ini($value, null);
            }
            return '';
        }

        /**
         * @param array $array
         * @param int   $test
         *
         * @return int
         */
        private function _testArray(array $array, int $test = ARRAY_IS) {
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
        public function sortValueBeforeSubArray($varA, $varB) {
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
        private function _expandArray($array) {
            $result = [];
            foreach($array as $key => $value) {
                if(is_array($value)) {
                    $result[$key] = $this->_expandArray($value);
                } elseif(is_string($value) && strpos($value, $this->delimiter) !== false) {
                    $result[$key] = explode($this->delimiter, $value) ? : $value;

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
        public function ini2array(string $ini, $isFile = false): array {
            IniLog::debug(__FUNCTION__, "Parsing a " . ($isFile ? "file" : "string") . ".");

            if($isFile) {
                return $this->_expandArray(parse_ini_file($ini, true, INI_SCANNER_TYPED));
            }

            return $this->_expandArray(parse_ini_string($ini, true, INI_SCANNER_TYPED));
        }

        /**
         * @param array  $configData
         * @param string $cfgFile
         * @param string $configDir
         * @param null   $fileHeader
         * @param bool   $timestamp
         */
        public function generateIniFile($configData = [], $cfgFile = "", $configDir = "cfg", $fileHeader = null,
                                               $timestamp = true): void {
            IniLog::debug(__FUNCTION__);
            //check: Tools::checkDir($configDir);
            file_put_contents($configDir . $cfgFile, ($fileHeader ? : "; Config file generated at ") .
                                                   ($timestamp ? Timestamp::timestamp() : '') . "\n" .
                                                   $this->array2ini($configData));
        }
    }
    /**
     * @param string $format
     * @param float  $microTime
     *
     * @return false|string
     */
    private function timestamp( string $format = NULL, float $microTime = NULL ): string {
        $microTime = $microTime ?? microtime( TRUE );
        $format    = $format ?? "Y-m-d H:i:s,u T";

        $timestamp    = (int)floor( $microTime );
        $milliseconds = round( ( $microTime - $timestamp ) * 1000000 );

        return date( preg_replace( '`(?<!\\\\)u`', $milliseconds, $format ), $timestamp );
    }
}
