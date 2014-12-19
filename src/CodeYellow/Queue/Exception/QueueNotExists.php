<?php
/**
 * QueueNotExists  exception
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

namespace CodeYellow\Queue\Exception;

/**
 * QueueNotExists  thrown when an action is performed on a
 * non existent queue.
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 */
class QueueNotExists extends \Exception
{
}
