<?php
/**
 * Driver implementation
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

// Ignore no camel case class name in this case
// @codingStandardsIgnoreStart
/**
 * Driver implementation using fuelphp's \DB driver
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class Driver_Db implements Driver_DbInterface
{
    // @codingStandardsIgnoreEnd

    /*****************VIEW*******************************************/
    /**
     * Gets all the jobs that are busy
     *
     * @return array array of all the jobs
     */
    public function getJobs()
    {
        return \DB::select('queue_jobs.*', 'queue_queues.name')
            ->from('queue_jobs')
            ->join('queue_queues')
            ->on('queue_queues.id', '=', 'queue_jobs.queue_id')
            ->limit(500)
            ->order_by('id', 'desc')
            ->execute()
            ->as_array();
    }

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
    public function addError($jobId, $error)
    {
        \DB::insert('queue_errors', array('job_id', 'error_messages'))
            ->values(array($jobId,$error))
            ->execute();
    }

    /**
     * Fetch an error from the database
     *
     * @param int $jobId the job id that we want to search an exception for
     *
     * @return array exception of a job id or null if it doesn't exist
     */
    public function getErrorByJobId($jobId)
    {
        $sql = \DB::select()
                    ->from('queue_errors')
                    ->where('job_id', '=', $jobId)
                    ->execute()
                    ->as_array();

        return isset($sql[0]) ? $sql[0] : null;
    }

    /*****************JOBS*********************************************/
    /**
     * Creates a new job
     *
     * @param array $options options of the new job
     *
     * @return int new job id
     * @post job is inserted to array
     */
    public function createJob(array $options)
    {
        $jobId = \DB::insert('queue_jobs')
            ->columns(
                array(
                    'priority',
                    'payload',
                    'execute_after',
                    'queue_id',
                    'time_added',
                    'time_executed',
                    'status',
                    'class',
                    'function',
                    'memory',
                )
            )
            ->values(
                array(
                    $options['priority'],
                    $options['args'],
                    $options['execute_after'],
                    $options['queue_id'],
                    $options['time_added'],
                    $options['time_executed'],
                    $options['status'],
                    $options['class'],
                    $options['function'],
                    $options['memory'],
                )
            )
            ->execute();

        return $jobId[0];
    }

    /**
     * Saves a job after editing
     *
     * @param int   $jobId   the job id to be saved
     * @param array $options new values for the job
     *
     * @post Job is saved to the database
     * @return void
     */
    public function saveJob($jobId, $options)
    {
        \DB::update('queue_jobs')
            ->set(
                array(
                    'priority'      => $options['priority'],
                    'payload'       => $options['args'],
                    'execute_after' => $options['execute_after'],
                    'queue_id'      => $options['queue_id'],
                    'time_added'    => $options['time_added'],
                    'time_executed' => $options['time_executed'],
                    'status'        => $options['status'],
                    'class'         => $options['class'],
                    'function'      => $options['function'],
                    'memory'        => $options['memory'],
                )
            )
            ->where('id', $jobId)
            ->execute();
    }

    /**
     * Finds a job by id
     *
     * @param int $jobId job id to be fetched
     *
     * @return array job arguments or null if the job isn't found
     */
    public function getJobById($jobId)
    {
        $sql = \DB::select()
            ->from('queue_jobs')
            ->where('id', '=', $jobId)
            ->execute();

        return $sql->current();
    }

    /**
     * Finds the next job in line to be executed
     *
     * @return int job id or null if no next job is available
     */
    public function getNextJob()
    {
        $sql = \DB::select('queue_jobs.*')
                    ->from('queue_jobs')
                    ->join('queue_queues')
                    ->on('queue_jobs.queue_id', '=', 'queue_queues.id')
                    ->where('queue_queues.status', '=', Queue::STATUS_ON)
                    ->where('queue_jobs.status', '=', Job::STATUS_IN_QUEUE)
                    ->where('queue_jobs.execute_after', '<', time())
                    ->where('queue_queues.execute_after', '<', time())
                    ->order_by('priority', 'desc')
                    ->order_by('time_added', 'desc')
                    ->order_by('id', 'desc')
                    ->limit(1)
                    ->execute()
                    ->as_array();

        // Sql is not necessarily an object. Do not change this to $sql->current()
        return isset($sql[0]) ? $sql[0] : null;
    }

    /**
     * Delete a job from the database
     *
     * @param int JobId
     */
    public function deleteJob($jobId)
    {
        \DB::delete('queue_errors')->where('job_id', $jobId)->execute();
        \DB::delete('queue_jobs')->where('id', $jobId)->execute();
    }

    /*****************QUEUES*********************************************/
    /**
     * adds a new queue
     *
     * @param string $name                queue string
     * @param int    $status              status of the queue
     * @param int    $threshold           the theshold of max items executed of the queue
     * @param int    $timeperiodThreshold the timeperiod for which the threshold holds
     *
     * @return int new queue id
     * @post queue is created in database
     */
    public function addNewQueue($name, $status, $threshold, $timeperiodThreshold)
    {
        \DB::insert('queue_queues')
            ->columns(array('name', 'status', 'threshold', 'timeperiod_threshold'))
            ->values(array($name, $status, $threshold, $timeperiodThreshold))
            ->execute();

        return $this->selectQueueIdByName($name);

    }

    /**
     * returns the queue id of a queue
     *
     * @param string $name the name of the queue
     *
     * @return int id of queue w/ name or null if no such queue exists
     */
    public function selectQueueIdByName($name)
    {
        $sql = \DB::select('id')
            ->from('queue_queues')
            ->where('name', '=', $name)
            ->execute();

        return $sql->get('id');
    }

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
    public function saveQueue($queueId, $name, $status, $threshold, $timeperiodThreshold)
    {
        \DB::update('queue_queues')
            ->set(
                array(
                    'name'                 => $name,
                    'status'               => $status,
                    'threshold'            =>  $threshold,
                    'timeperiod_threshold' => $timeperiodThreshold
                )
            )
            ->where('id', $queueId)
            ->execute();
    }

    /**
     * finds a queue by id
     *
     * @param int $queueId queue id to be fetched
     *
     * @return array queue information or null if the queue doesn't exist
     */
    public function getQueueById($queueId)
    {
        $sql = \DB::select()
                    ->from('queue_queues')
                    ->where('id', '=', $queueId)
                    ->execute();

        return $sql->current();
    }

    /**
     * Updates the execute after time
     *
     * @param int $queueId      The queue to be updated
     * @param int $executeAfter The time after which the next job may be executed
     */
    public function setExecuteAfter($queueId, $executeAfter)
    {
        \DB::update('queue_queues')
            ->set(array('execute_after' => $executeAfter))
            ->where('id', $queueId)
            ->execute();
    }
}
