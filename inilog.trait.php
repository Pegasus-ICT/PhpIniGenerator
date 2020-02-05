<?php declare( strict_types=1 );
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
namespace PegasusICT\PhpIniGenerator;

use function array_key_exists, in_array;

/**
 * Trait IniLog
 *
 * To set the loglevel, set a global constant named "INI_LOG_LEVEL" to any of the level names specified in IniLog::$levels
 *
 * @package PegasusICT\PhpIniGenerator
 *
 * @method static void critical( string $__FUNCTION__, string $line = '' )
 * @method static void error   ( string $__FUNCTION__, string $line = '' )
 * @method static void warning ( string $__FUNCTION__, string $line = '' )
 * @method static void notice  ( string $__FUNCTION__, string $line = '' )
 * @method static void info    ( string $__FUNCTION__, string $line = '' )
 * @method static void verbose ( string $__FUNCTION__, string $line = '' )
 * @method static void debug   ( string $__FUNCTION__, string $line = '' )
 *
 * @method static void printErrors ()
 * @method static string returnErrors ()
 * @method static void printWarnings ()
 * @method static string returnWarnings ()
 * @method static void printMessages ()
 * @method static string returnMessages ()
 * @method static void printAll ()
 * @method static string returnAll ()
 */
trait IniLog {

    use Timestamp;
    public static  $_levels   = [ "disabled", "critical", "error", "warning", "notice", "info", "verbose", "debug" ];
    private static $_level    = 'debug';
    private static $_logs     = [];
    private static $_subjects = [
        'All'      => [ 'min' => 'critical', 'max' => 'debug', ],
        'Errors'   => [ 'min' => 'critical', 'max' => 'error', ],
        'Warnings' => [ 'min' => 'warning', 'max' => 'notice', ],
        'Messages' => [ 'min' => 'info', 'max' => 'debug', ],
    ];

    /**
     * This function acts as a validator & switchboard for:
     *  - sending messages to the log stack,        <level>(__FUNCTION__ <message>)
     *  - returning or printing log messages        return|print<stack>
     *  - setting the maximum level to process      e.g. setLevel(<level>)
     *
     * @param string     $name      actual call made
     * @param array|null $arguments array of optional arguments
     *
     * @return void|string              if return<All|Errors|Messages|Debug> is called
     */
    public static function __callStatic( string $name, ?array $arguments ) {
        self::_init();
// message to log(s)
        if( in_array( $name, self::$_levels, false ) ) {
            self::_log( $name, $arguments[0] ?? "unknown function", $arguments[1] ?? "" );

            return;
        }
// set maximum log output level
        elseif( $name === 'setLevel' ) {
            self::$_level = in_array( $arguments[1], self::$_levels, false ) ? $arguments[1] : "debug";

            return;
        }
// print or return log messages
        $actions = [ 'print', 'return' ];
        foreach( $actions as $action ) {
            $length = strlen( $action );
            if( strncmp( $name, $action, $length ) === 0 ) {
                $result = "Nothing to report.\n";
                $category = substr( $name, $length );
                if( !array_key_exists( $category, self::$_logs ) ) {
                    $category = 'All';
                }
                if( !empty( self::$_logs[$category] ) ) {
                    $result = self::$_logs[$category];
                }
                if( $action === "return" ) {
                    return $result;
                }
                print $result;
            }
        }
    }

    /**
     * Initializes the logs array if necessary
     */
    private static function _init() {
        // Initialize Logs Array if necessary
        if( empty( self::$_logs ) ) {
            foreach( array_keys( self::$_subjects ) as $subject ) {
                self::$_logs[$subject] = '';
            }
        }
    }

    /**
     * The actual logging function
     *
     * @param string $function
     * @param string $line
     * @param string $level
     */
    private static function _log( string $level, string $function, string $line = "" ) {
        foreach( self::$_subjects as $subject => $levels ) {
            $levelIndexes = array_flip( self::$_levels );
            $min = $levelIndexes[$levels['min']];
            $max = $levelIndexes[$levels['max']];
            $levelIndex = $levelIndexes[$level];
            $maxLevel = $levelIndexes[self::$_level];
            if( $levelIndex >= $min && $levelIndex <= $max && $levelIndex <= $maxLevel ) {
                self::$_logs[$subject] .=
                    Timestamp::timestamp( "H:i:s,u" ) . " [" . strtoupper( $level ) . "] $function(): $line\n";
            }
        }
    }

}
