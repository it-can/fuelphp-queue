<?php
/**
 * Core class
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
 * Core class, handles all the functions for the core.
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 */
class Core
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
     * Get the next job to be executed
     *
     * @return Job Job to be executed or null if no job is available
     */
    public static function getNextJob()
    {
        $jobArray = static::$driver->getNextJob();

        if ($jobArray == null) {
            return null;
        }

        $job = new Job();
        $job->loadFromArray($jobArray);

        return $job;
    }

    /**
     * Excecutes job
     *
     * @param Job $job job to be performed
     *
     * @return int result
     * @post \codeyellow\Job is either done or failed
     */
    public static function executeJob($job)
    {
        // Just to get a global indicator of memory usage & time used by job
        $memoryStart = memory_get_usage();
        $timeStart   = microtime(true);
        $result = null;

        try {
            // Update threshold for queue
            $job->getQueue()->updateExecuteAfter();

            // Set job status
            $job->setStatus(Job::STATUS_RUNNING);

            $class  = $job->getClass();
            $method = $job->getFunction();

            call_user_func_array(
               $job->getClass() . '::' . $job->getFunction(),
               $job->getArgs()
            );

            // Call class and pass array
            //$result = $class::$method($job->getArgs());

            $job->setStatus(Job::STATUS_DONE);
            $job->setTimeExecuted();

            // Log the job properly
            $return = true;
        } catch (\Exception $e) {
            // Set to failed and log exception
            $job->setStatus(Job::STATUS_FAILED);
            $error = new Error;
            $error->create($job->getId(), print_r($e->getMessage(), true));
            $job->setTimeExecuted();

            $return = false;
        }
        self::logJob($job, \Config::get('queue.log_option'));

        // Save memory + time usage to job
        $job->setUsage($memoryStart, $timeStart);

        unset($job);

        return $return;
    }

    private static function logJob($job, $option)
    {
        switch ($option) {
            case 'database':
                //It is saved in thed databse already. nothing to be done
                return;
                break;
            case 'file':
                static::writeJob($job);
                //no break, jobs have to be deleted
            case 'none':
                $job->delete();
                break;
            default:
                throw new Exception\InvalidArguments(
                    'Can not log a job with option "'. $option . ' Using database instead'
                );
        }
    }

    private static function writeJob($job)
    {
        $logfile = \Config::get('queue.log_file');
        $fh = fopen($logfile, 'a+');
        fwrite($fh, 'Job executed: ' . "\n\n" .  print_r($job, true) . "\n\n");
        fclose($fh);
    }

    /**
     * Get all the jobs
     *
     * @return array all the jobs
     */
    public static function getJobs()
    {
        return static::$driver->getJobs();
    }
}
