<?php declare( strict_types = 1 );
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

}
