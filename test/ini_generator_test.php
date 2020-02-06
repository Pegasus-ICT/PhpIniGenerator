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
const INI_LOG_LEVEL = 'debug';
$cfgData = [
    ';10'       => "made with ini generator by Mattijs Snepvangers",
    ';20'       => "Generated at @@@",
    'log_level' => 'debug',
    'log_type'  => 'file',
    'log_file'  => [
        'split'           => true,
        'filename_format' => 'base_name sub_name date',
        'rotate'          => 'day',
        'base_name'       => 'phplog',
        'sub_name'        => ['errors'   => ['critical', 'error'],
                              'messages' => ['warning', 'info'],
                              'debug'    => ['verbose', 'debug']
        ],
        'date'            => 'Y-m-d',
    ],
    'log_line'  => 'timestamp [level] class->function(): message',
    'timestamp' => 'H:i:s,u'
];
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once __DIR__ . "/../PegasusICT/PhpIniGenerator/src/phpinigenerator.class.php";
$iniGenerator = new PegasusICT\PhpIniGenerator\IniGenerator();
$iniString = $iniGenerator->array2ini($cfgData);

// testing only works if you leave out the comment lines, need to work on that...
$testArray=[];
foreach($cfgData as $key => $value) {
    if( strpos( $key, ";" ) !== 0 ) {
        $testArray[ $key ] = $value;
    }
}
echo ($iniGenerator->ini2array($iniString) == $testArray) ? "success" : "fail";

// TODO: parse iniString/File comment lines into ini2array, sort arrays before comparing if necessary
