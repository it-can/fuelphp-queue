<?php
/**
 * Driver interface 
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

// Ignore no camelCase class name in this case
// @codingStandardsIgnoreStart
/**
 * Driver interface
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 */
interface Driver_DbInterface
{
    // @codingStandardsIgnoreEnd
    /*****************VIEW****************************************************/
    /**
     * Gets all the jobs that are busy
     *
     * @return array array of all the jobs
     */
    public function getJobs();
    

    /****************Errors***************************************************/
    /**
     * Add an error to the database
     *
     * @param int    $jobId the job id that triggered the error
     * @param string $error the exception that occurred
     *
     * @post error is added to the database
     * @return void
     */
    public function addError($jobId, $error);

    /**
     * Fetch an error from the database
     *
     * @param int $jobId the job id that we want to search an exception for
     *
     * @return array exception of a job id or null if it doesn't exist
     */
    public function getErrorByJobId($jobId);
    
    /*****************JOBS*********************************************/
    /**
     * Creates a new job
     *
     * @param array $options options of the new job
     *
     * @return int new job id
     * @post job is inserted to array
     */
    public function createJob(array $options);

    /**
     * Saves a job after editing
     *
     * @param int   $jobId   the job id to be saved
     * @param array $options new values for the job
     *
     * @post Job is saved to the database
     * @return void
     */
    public function saveJob($jobId, $options);

    /**
     * Finds a job by id
     *
     * @param int $jobId job id to be fetched
     *
     * @return array job arguments or null if the job isn't found
     */
    public function getJobById($jobId);

    /**
     * Finds the next job in line to be executed
     *
     * @param array $excludedQueues queues that need to be excluded.
     * @return int job id or null if no next job is available
     */
    public function getNextJob();


    /**
     * Delete a job from the database
     *
     * @param int JobId
     */
    public function deleteJob($jobId);

    /*****************QUEUES*********************************************/
    /**
     * adds a new queue
     *
     * @param string $name        queue string
     * @param int    $status      status of the queue
     * @param int    $threshold           the theshold of max items executed of the queue
     * @param int    $timeperiodThreshold the timeperiod for which the threshold holds
     *
     * @return int new queue id
     * @post queue is created in database
     */
    public function addNewQueue($name, $status, $threshold, $timeperiodThreshold);

    /**
     * returns the queue id of a queue
     *
     * @param string $name the name of the queue
     *
     * @return int id of queue w/ name or null if no such queue exists
     */
    public function selectQueueIdByName($name);

    /**
     * Saves a queue
     *
     * @param int    $queueId             queue id
     * @param string $name                new queue name
     * @param int    $status              new queue status
     * @param int    $threshold           the theshold of max items executed of the queue
     * @param int    $timeperiodThreshold the timeperiod for which the threshold holds
     * 
     * @return void
     * @post queue is saved to database
     */
    public function saveQueue($queueId, $name, $status, $threshold, $timeperiodThreshold);

    /**
     * finds a queue by id
     *
     * @param int $queueId queue id to be fetched
     *
     * @return array queue information or null if the queue doesn't exist
     */
    public function getQueueById($queueId);

    /**
     * Updates the execute after time
     * 
     * @param int $queueId      The queue to be updated
     * @param int $executeAfter The time after which the next job may be executed
     */
    public function setExecuteAfter($queueId, $executeAfter);
}
