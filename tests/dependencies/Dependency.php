<?php
/**
 * Class needed for unittests
 *
 * PHP version 5
 *
 * @category  Utilities
 * @package   Queue
 * @author    Stefan Majoor <stefan@codeyellow.nl>
 * @copyright 2014 Code Yellow BV
 * @license   MIT License
 * @link      https://bitbucket.org/codeyellow/fuelphp-queue/
 */

namespace CodeYellow\Queue;

class Dependency
{
    public static $infinity = array();

    public static function noException($a)
    {
        return true;
    }

    public static function withException($a)
    {
        throw new \Exception('This is a test exception');
    }

    public static function memoryOverflow($a)
    {
        for ($i = 0; $i < 100000; $i++) {
            self::$infinity[] = "I Want To Overflow The Memory!";
        }
    }

    public static function memoryOverflowWithException($a)
    {
        static::memoryOverflow();
        throw new Exception();
    }
    public $a = "0";
}
