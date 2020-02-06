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
 * Class IniLogger
 *
 * To set the loglevel, set a global constant named "INI_LOG_LEVEL" to any of the level names specified in IniLog::$levels
 *
 * @package PegasusICT\PhpIniGenerator
 *
 * @method  void critical( string $__FUNCTION__, string $line = '' )
 * @method  void error   ( string $__FUNCTION__, string $line = '' )
 * @method  void warning ( string $__FUNCTION__, string $line = '' )
 * @method  void notice  ( string $__FUNCTION__, string $line = '' )
 * @method  void info    ( string $__FUNCTION__, string $line = '' )
 * @method  void verbose ( string $__FUNCTION__, string $line = '' )
 * @method  void debug   ( string $__FUNCTION__, string $line = '' )
 *
 * @method  void printErrors ()
 * @method  string returnErrors ()
 * @method  void printWarnings ()
 * @method  string returnWarnings ()
 * @method  void printMessages ()
 * @method  string returnMessages ()
 * @method  void printAll ()
 * @method  string returnAll ()
 */
class IniLogger {

    use Timestamp;
    const LEVELS = [ "disabled", "critical", "error", "warning", "notice", "info", "verbose", "debug" ];
    private $level = 'debug';
    private $logs  = [];
    private $subjects = [
        'All'      => [ 'min' => 'critical', 'max' => 'debug', ],
        'Errors'   => [ 'min' => 'critical', 'max' => 'error', ],
        'Warnings' => [ 'min' => 'warning', 'max' => 'notice', ],
        'Messages' => [ 'min' => 'info', 'max' => 'debug', ],
    ];

    /**
     * IniLogger constructor.
     *
     * @param string|null $loglevel
     * @param string|null $logdir
     *
     * @return \PegasusICT\PhpIniGenerator\IniLogger
     */
    public function __construct( ?string $loglevel, ?string $logdir ) {
        $this->outputdir = $logdir??null;
        $this->level = $loglevel??'debug';
        $this->_init();

        return $this;
    }

    /**
     *  Setting the maximum level to process
     *
     * @param string|null $level
     *
     * @return \PegasusICT\PhpIniGenerator\IniLogger
     */
    public function setLevel( ?string $level ): IniLogger {
        $this->level = $level;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLevel(): ?string {
        return $this->level;
    }

    /**
     * This function acts as a validator & switchboard for:
     *  - sending messages to the log stack,        <level>(__FUNCTION__ <message>)
     *  - returning or printing log messages        return|print<stack>
     *
     * @param string     $name          actual call made
     * @param array|null $arguments     array of optional arguments
     *
     * @return \PegasusICT\PhpIniGenerator\IniLogger|string     if return<All|Errors|Messages|Debug> is called the
     */
    public function __call( string $name, ?array $arguments ) {
// message to log(s)
        if( in_array( $name, self::LEVELS, false ) ) {
            if(array_flip( self::LEVELS)[ $name ] <= array_flip( self::LEVELS )[ $this->level ]) {
                self::_log( $name, $arguments[ 0 ] ?? "unknown function", $arguments[ 1 ] ?? "" );
            }
            return $this;
        }
// print or return log messages
        $actions = [ 'print', 'return' ];
        foreach( $actions as $action ) {
            $length = strlen( $action );
            if( strncmp( $name, $action, $length ) === 0 ) {
                $result = "Nothing to report.\n";
                $category = substr( $name, $length );
                if( !array_key_exists( $category, $this->logs ) ) {
                    $category = 'All';
                }
                if( !empty( $this->logs[$category] ) ) {
                    $result = $this->logs[$category];
                }
                if( $action === "return" ) {
                    return $result;
                }
                print $result;
            }
        }
        return $this;
    }

    /**
     * Initializes the logs array if necessary
     */
    private  function _init() {
        // Initialize Logs Array if necessary
        if( empty( $this->logs ) ) {
            foreach( array_keys( $this->subjects ) as $subject ) {
                $this->logs[$subject] = '';
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
    private  function _log( string $level, string $function, string $line = "" ) {
        foreach( $this->subjects as $subject => $levels ) {
            $levelIndexes = array_flip( $this->levels );
            $min = $levelIndexes[$levels['min']];
            $max = $levelIndexes[$levels['max']];
            $levelIndex = $levelIndexes[$level];
            $maxLevel = $levelIndexes[$this->level];
            if( $levelIndex >= $min && $levelIndex <= $max && $levelIndex <= $maxLevel ) {
                $this->logs[$subject] .=
                    Timestamp::timestamp( "H:i:s,u" ) . " [" . strtoupper( $level ) . "] $function(): $line\n";
            }
        }
    }

}
