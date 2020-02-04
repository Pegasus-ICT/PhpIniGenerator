<?php declare(strict_types=1);
/**
 * PHP Ini Generator
 *
 * PHP version 7.2+
 *
 * @category  configuration generator
 * @package   PhpIniGenerator
 * @author    Mattijs Snepvangers <pegasus.ict@gmail.com>
 * @copyright 2019-2020 Pegasus ICT Dienstverlening
 * @license   MIT License
 * @link      https://github.com/Pegasus-ICT/PhpIniGenerator/
 */
namespace PegasusICT\PhpIniGenerator;

/**
 * Trait MyTimestamp
 *
 * @package PegasusICT\PhpIniGenerator
 */
trait Timestamp {
    /**
     * @param string $format
     * @param float  $microTime
     *
     * @return false|string
     */
    static function timestamp(string $format = null, float $microTime = null): string {
        $microTime = $microTime ?? microtime(true);
        $format    = $format ?? "Y-m-d H:i:s,u T";

        $timestamp    = (int)floor($microTime);
        $milliseconds = round(($microTime - $timestamp) * 1000000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }
}