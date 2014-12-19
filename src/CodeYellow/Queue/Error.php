<?php
/**
 * Error class
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

/**
 * Error class, stores and fetches errors
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 */
class Error
{
    /**
     * The driver that is loaded
     */
    private static $driver = null;

    /**
     * Load the driver
     *
     * @post driver variable contains the driver
     */
    public function __construct()
    {
        static::$driver = new Driver_Db;
    }

    /**
     * Creates an error in the database
     *
     * @param int       $jobId     the job that caused the error
     * @param exception $exception the exception that occurred
     *
     * @return void
     * @post exception is saved in database
     */
    public static function create($jobId, $exception)
    {
        static::$driver->addError($jobId, $exception);
    }

    /**
     * Gets the exception for a specific job id from the database
     *
     * @param int $jobId the job that we want to search errors for
     *
     * @return array exception of a job id or null if it doesn't exist
     */
    public static function getErrorByJobId($jobId)
    {
        return static::$driver->getErrorByJobId($jobId);
    }
}
