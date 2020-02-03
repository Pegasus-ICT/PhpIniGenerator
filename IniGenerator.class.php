<?php //declare(strict_types=1);

namespace {
    const ARRAY_IS       = 0b0000;
    const ARRAY_IS_SEQ   = 0b0001;
    const ARRAY_IS_ASSOC = 0b0010;
    const ARRAY_IS_NUM   = 0b0100;
    const ARRAY_IS_EMPTY = 0b1000;
    const ARRAY__IS = [
        ARRAY_IS_SEQ    => "sequential",
        ARRAY_IS_NUM    => "numerical",
        ARRAY_IS_ASSOC  => "associative",
        ARRAY_IS_EMPTY  =>"empty",
        ARRAY_IS        =>"what?"
    ];
}

namespace PegasusICT\PhpHelpers {

    /**
     * Class timestamp
     *
     * @package PegasusICT\PhpHelpers
     */
    trait MyTimestamp{
        /**
         * @param string|null $format
         * @param float|null  $microtime
         *
         * @return false|string
         */
        static function timestamp( string $format=null, float $microtime=null ) {
            $microtime = $microtime ?? microtime(true);
            $format    = $format ?? "Y-m-d H:i:s,u T";

            $timestamp    = (int)floor($microtime);
            $milliseconds = round(( $microtime - $timestamp) * 1000000);

            return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
        }
    }

    /**
     * Trait IniLog
     *
     * @package PegasusICT\PhpHelpers
     *
     * @method static void critical( string $__FUNCTION__, string $line = '' )
     * @method static void error   ( string $__FUNCTION__, string $line = '' )
     * @method static void warning ( string $__FUNCTION__, string $line = '' )
     * @method static void notice  ( string $__FUNCTION__, string $line = '' )
     * @method static void info    ( string $__FUNCTION__, string $line = '' )
     * @method static void verbose ( string $__FUNCTION__, string $line = '' )
     * @method static void debug   ( string $__FUNCTION__, string $line = '' )
     *
     * @method static string printErrors ()
     * @method static printWarnings ()
     * @method static printMessages ()
     * @method static printAll ()
     *
     * @method static setLevel ( string $level = 'debug' )
     */
    trait IniLog {
        use MyTimestamp;
        private static $_level = 'debug';
        private static $_levels = [ "critical", "error", "warning", "notice", "info", "verbose", "debug" ];
        private static $_logs = [];
        private static $_subjects = [
            'All' => [
                'min' => 'critical',
                'max' => 'debug'
            ],
            'Errors' => [
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

        /**
         * @param $name
         * @param $arguments
         */
        public static function __callStatic( $name, $arguments ) {
// Initialize Logs Array if necessary
            if( empty( self::$_logs ) ) foreach( array_keys( self::$_subjects ) as $subject ) self::$_logs[ $subject ] = '';
// message to log(s)
            if( in_array( $name, self::$_levels ) ) self::_log( $name, $arguments[0]??"unknown function", $arguments[ 1 ] ?? "" );
// print log messages
            elseif ( strpos( $name, 'print' ) === 0 )
                print self::$_logs[ ( in_array( substr( $name, 5 ),array_keys( self::$_logs ) ) ) ? substr( $name, 5 ) : 'All' ];
// set maximum log level
            elseif( $name == 'setLevel' ) self::$_level=( in_array( $arguments[ 1 ], self::$_levels ) ) ? $arguments[ 1 ] : "debug";
        }

        /**
         * @param string $function
         * @param string $line
         * @param string $level
         */
        private static function _log( string $level, string $function, string $line="" ) {
            foreach( self::$_subjects as $subject => $levels ) {
                $levelIndexes = array_flip( self::$_levels );
                $min        = $levelIndexes[ $levels[ 'min' ] ];
                $max        = $levelIndexes[ $levels[ 'max' ] ];
                $levelIndex = $levelIndexes[ $level ];
                $maxLevel   = $levelIndexes[ self::$_level ];
                if( $levelIndex >= $min && $levelIndex <= $max && $levelIndex <= $maxLevel )
                    self::$_logs[ $subject ] .= MyTimestamp::timestamp( "H:i:s,u" ) . " [".strtoupper( $level ) . "] $function(): $line\n";
            }
        }
    }

    /**
     * Class IniGenerator
     *
     * @package PegasusICT\PhpHelpers
     */
    class IniGenerator {
        public static function array2ini( array $array = [], string $section = null, $level = 0 ) {
            IniLog::debug( __FUNCTION__ );
            $result = '';
            if( empty( $array ) ) { IniLog::notice( __FUNCTION__, "array is empty" );
            } else {
                uasort($array, __CLASS__ . '::_sortValueBeforeSubArray' );
                foreach( $array as $key => $value ) {
                    if( !is_int($key)&&strpos( $key, ';' ) === 0 ) {
                        IniLog::debug( __FUNCTION__ ,"inserting comment line");
                        $result .= "; " . preg_replace("/[@]{3}/", date("Y-m-d H:i:s T"), $value) . "\n";
                    } else {
                        if( $level >= 1 && is_array( $value ) ) {
                            if( self::_testArray( $value ) === ARRAY_IS_ASSOC ) {
                                IniLog::debug( __FUNCTION__, "$key = associative" );
                                $key = $section . "[" . $key . "]";
                            } elseif( self::_testArray( $value ) === ARRAY_IS_NUM ) {
                                IniLog::debug( __FUNCTION__, "$key = numeric" );
                                $key = $section . "[" . $key . "]";
                            } else {
                                IniLog::debug( __FUNCTION__, "$key = sequential" );
                                $key = $section . "[]";
                            }
                        }
                        if( is_array( $value ) ) $result .= self::_processSubArray( $key, $value, $section, $level );
                        else {
                            switch( gettype( $value ) ) {
                                case 'boolean': $result .= "$key = " . ( $value ? 'true' : 'false' ) . "\n"; break;
                                case 'integer': // treat same as 'double'
                                case 'double': $result .= "$key = $value\n"; break;
                                case 'string': $result .= "$key = \"$value\"\n"; break;
                                case 'array': break; // skip
                                default: $result .= "$key = null\n"; break; // "NULL", "object", "resource", "resource (closed)", "unknown type"
                            }
                        }
                    }
                }
            }

            return $result;
        }

        /**
         * @param string      $key
         * @param array       $value
         * @param string|null $section
         * @param int         $level
         *
         * @return string
         */
        private static function _processSubArray( string $key = '', array $value = [], string $section = null, int $level = 0 ) {
            IniLog::debug( __FUNCTION__, "key = $key, value has " . count( $value ) . " elements, section = " . ( $section ?: "null" ) . " level = $level" );
            $result = "";
            if( $level === 0 ) {
                if( ( $section === null || $key === $section ) && !empty( $value ) )  $result .= "\n[$key]\n" . self::array2ini( $value, null, 1 );
            } else $result .= "$key = ".self::array2ini( $value, $key, $level + 1 );

            return $result;
        }

        /**
         * @param array $array
         * @param int   $test
         *
         * @return int
         */
        private static function _testArray( array $array, int $test = ARRAY_IS ) {
            IniLog::debug( __FUNCTION__, "test = ".ARRAY__IS[$test] );
            if( array() === $array ) $result = ARRAY_IS_EMPTY;
            elseif( array_keys( $array ) === range( 0, count( $array) - 1 ) ) $result = ARRAY_IS_SEQ;
            elseif( count( array_filter( array_keys( $array ), 'is_string' ) ) > 0 ) $result = ARRAY_IS_ASSOC;
            else $result = ARRAY_IS_NUM;

            IniLog::debug( __FUNCTION__, "array = ".ARRAY__IS[$result] );

            if( $test === ARRAY_IS ) return $result;
            else return ( $test | $result );
        }

        /**
         * @param mixed $a
         * @param mixed $b
         *
         * @return int
         */
        private static function _sortValueBeforeSubArray( $a, $b ) {
            IniLog::debug( __FUNCTION__ );
            $aa = is_array( $a );
            $bb = is_array( $b );

            return ( $aa == $bb ) ? 0 : ( ( $aa < $bb ) ? -1 : 1 );
        }

        /**
         * @param string $iniFile
         *
         * @return array
         */
        public static function ini2array( string $iniFile ): array {
            return parse_ini_file( $iniFile, true, INI_SCANNER_TYPED );
        }

        /**
         * @param array  $configData
         * @param string $cfgFile
         * @param string $configDir
         */
        public static function generateIniFile( $configData = [], $cfgFile = "", $configDir = "cfg", $fileHeader = null, $timestamp = true ): void {
            Tools::checkDir($configDir);

            file_put_contents( $configDir . $cfgFile, ( $fileHeader ? : "# Config file generated at " ) . ( $timestamp ? Tools::uDate( "Y-m-d H:i:s.u T") : '' ) . "\n" . self::array2ini( $configData ) );
        }
    }
}
namespace{
    $cfgData =[
        ';10' => "made with ini generator by Mattijs Snepvangers",
        ';20' => "Generated at @@@",
        'log_level'=> 'debug',
        'log_type' => 'file',
        'log_file' => [
            'split' => true,
            'filename_format' => 'base_name sub_name date',
            'rotate' => 'day',
            'base_name' => 'phplog',
            'sub_name' => [     // TODO here is where things go wrong...
                'errors' => [ 'critical', 'error' ],
                'messages' => [ 'warning', 'info' ],
                'debug' => [ 'verbose', 'debug' ]
            ],
            'date' => 'Y-m-d',
        ],
        'log_line' => 'timestamp [level] class->function(): message',
        'timestamp' => 'H:i:s,u'
    ];

    print PegasusICT\PhpHelpers\IniGenerator::array2ini($cfgData);
    echo"\n";
    echo"\n";
    print PegasusICT\PhpHelpers\IniLog::printAll();
}