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

require_once __DIR__."/phpinigenerator.class.php";
    $iniString = PegasusICT\PhpIniGenerator\IniGenerator::array2ini($cfgData);
    echo (PegasusICT\PhpIniGenerator\IniGenerator::ini2array($iniString) == $cfgData) ? "success" : "fail";// only works if you leave out the comment lines, need to work on that...

// TODO: parse iniString/File comment lines into ini2array, sort arrays before comparing if necessary
