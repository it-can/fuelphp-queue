<?php
/**
 * OverView_Viewer class 
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
 * Parses the overview template
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 */
class Job
{
    const STATUS_IN_QUEUE = 0;
    const STATUS_RUNNING = 1;
    const STATUS_DONE = 2;
    const STATUS_FAILED = 3;
    const STATUS_DELETED = 4;

    const PRIORITY_VERYLOW = 0;
    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_VERYHIGH = 4;
    const PRIORITY_SYSTEM = 5;

    /**
     * The driver that is used
     */
    private static $driver = null;
    
    /**
     * The id of the loaded job
     */
    private $jobId = null;

    /**
     * The id of the loaded job
     */
    private $function = null;

    /**
     * The class of the loaded job
     */
    private $class = null;

    /**
     * The arguments of the loaded job
     */
    private $args = null;

    /**
     * The queueId of the loaded job
     */
    private $queueId = null;

    /**
     * The time after which the loaded job may be executed
     */
    public $executeAfter = null;

    /**
     * The time at which the job was created
     */
    public $timeAdded = null;
    
    /**
     * The time at which the job was executed
     */
    public $timeExecuted = null;

    /**
     * The priority of the loaded job
     */
    public $priority = null;

    /**
     * The status of the current job
     */
    public $status = null;

    /**
     * Load the driver
     *
     * @param string $class        The class that needs to be called
     * @param string $method       The method that needs to be called
     * @param array  $args         The arguments that need to be passed
     * @param int    $queueId      The queue this job is in
     * @param int    $priority     The priority of this queue
     * @param int    $executeAfter The timea after which this job may be executed
     * @post driver variable contains the driver
     */
    public function __construct(
        $class = null,
        $method = null,
        $args = null,
        $queueId = null,
        $priority = Job::PRIORITY_MEDIUM,
        $executeAfter = 0
    ) {
        static::$driver = new Driver_Db;
        if ($class != null) {
            $this->create($class, $method, $args, $queueId, $priority, $executeAfter);
        }
    }

    /**
     * Add a new job
     *
     * @param string $class        class to be executed
     * @param string $method       class method to be executed
     * @param array  $args         array of arguments
     * @param int    $queueId      id of the queue or name of queue
     * @param int    $priority     priority of the job
     * @param int    $executeAfter time after which the job needs to be executed
     *
     * @return id
     * @throws Exception_InvalidArguments when the args parameter is not an array
     * @throws Exception_QueueNotExist when the queue doesn't exists
     */
    public function create(
        $class,
        $method,
        $args,
        $queueId,
        $priority = Job::PRIORITY_MEDIUM,
        $executeAfter = 0
    ) {
        //Load Config file
        Core::loadConfig();

        //Check if we deal with a valid job
        if ($args != null && !is_array($args)) {
            throw new Exception\InvalidArguments();
        }

        if (! is_int($queueId)) {
            $name = $queueId;
            $queueId = static::$driver->selectQueueIdByName($name);
        }

        if (static::$driver->getQueueById($queueId) == null) {
            // If implicit queue creation is set to on
            if (\Config::get('queue.implicit_queue_creation') && isset($name)) {
                $queue = new Queue($name);
                $queueId = $queue->getId();
            } else {
                throw new Exception\QueueNotExists();
            }
        }
    
        $this->class = $class;
        $this->function = $method;
        $this->args = $args;
        $this->queueId = $queueId;
        $this->executeAfter = $executeAfter;
        $this->timeAdded = time();
        $this->timeExecuted = 0;
        $this->priority = $priority;
        $this->status = static::STATUS_IN_QUEUE;

        $this->jobId = static::$driver->createJob(
            array(
                'class' => $this->class,
                'function' => $this->function,
                'args' => $this->encodeArgs($this->args),
                'queue_id' => $this->queueId,
                'priority' => $this->priority,
                'execute_after' => $this->executeAfter,
                'status' => $this->status,
                'time_executed' => $this->timeExecuted,
                'time_added' => $this->timeAdded,
            )
        );

        return $this;
    }

    /**
     * Loads a job by id
     *
     * @param int $jobId job id to be loaded
     *
     * @return void
     * @post job is loaded to the class
     * @throws Exception_JobNotFound when a job wasn't found
     */
    public function load($jobId)
    {
        $job = static::$driver->getJobById($jobId);
        if ($job == null) {
            throw new Exception\JobNotFound();
        }

        $this->loadFromArray($job);
        return $this;
    }

    /**
     * Loads a job from an array
     *
     * @param array $job options to be loaded
     * 
     * @return self
     * @post job is loaded into class
     */
    public function loadFromArray($job)
    {
        $this->jobId = $job['id'];
        $this->priority = $job['priority'];
        $this->args = $this->decodeArgs($job['payload']);
        $this->executeAfter = $job['execute_after'];
        $this->queueId = $job['queue_id'];
        $this->timeAdded = $job['time_added'];
        $this->timeExecuted = $job['time_executed'];
        $this->status = $job['status'];
        $this->class = $job['class'];
        $this->function = $job['function'];
        return $this;
    }

    ////////////////Payload handling functions///////////////////////////
    /**
     * Encodes the args such that arrays and objects can be differentiated
     */
    private function encodeArgs($args)
    {
        $this->encodeArgsRec($args);
        return json_encode($args);
    }

    /**
     * Recrusive encodeArgs function.
     * Only to be called from encodeArgs
     */
    private function encodeArgsRec(&$args)
    {
        // Only arrays and objects need to be encoded;
        if (!is_array($args) && !is_object($args)) {
            return;
        }

        foreach ($args as &$value) {
            $this->encodeArgsRec($value);
        }
        if (is_array($args)) {
            $args['__queue_type'] = "array";
        }
    }


    /**
     * Decodes the args such that arrays and objects can be differentiated
     */
    private function decodeArgs($args)
    {
        $args = json_decode($args, true);
        $this->decodeArgsRec($args);
        return (array)$args;
    }

    /**
     * Recrusive decodeArgs function.
     * Only to be called from decodeArgs
     */
    private function decodeArgsRec(&$args)
    {
        // Only arrays and objects need to be encoded;
        if (!is_array($args) && !is_object($args)) {
            return;
        }

        foreach ($args as &$value) {
            $this->decodeArgsRec($value);
        }
        if (isset($args['__queue_type'])) { // assume array
            unset($args['__queue_type']);
        } else {
            $args = (object) $args;
        }
    }

    ////////////////Common getters and setters///////////////////////////
    /**
     * Get the jobid
     *
     * @return int jobid
     */
    public function getId()
    {
        return $this->jobId;
    }

    /**
     * Get the Class
     *
     * @return string class name
     */
    public function getClass()
    {
        return (string) $this->class;
    }

    /**
     * Set the class
     *
     * @param string $class class name to be used in the job 
     * 
     * @return self
     * @post class name is updated
     */
    public function setClass($class)
    {
        $this->class = $class;
        $this->save();
        return $this;
    }


    /**
     * Get the function
     *
     * @return string function name
     */
    public function getFunction()
    {
        return (string) $this->function;
    }

    /**
     * Set the function
     *
     * @param string $function function to be executed
     *
     * @post function name is updated
     * @return self
     */
    public function setFunction($function)
    {
        $this->function = $function;
        $this->save();
        return $this;
    }

    /**
     * Get the argument array
     *
     * @return array arguments
     */
    public function getArgs()
    {
        return (array) $this->args;
    }

    /**
     * Set the argument array
     *
     * @param array $args arguments to be set
     *
     * @return self
     * @post arguments are saved
     */
    public function setArgs(array $args)
    {
        $this->args = $args;
        $this->save();
        return $this;
    }

    /**
     * Get the execute after time
     *
     * @return int execute after
     */
    public function getExecuteAfter()
    {
        return (int) $this->executeAfter;
    }

    /**
     * Set the execute after time
     *
     * @param int $time time after which this function is executed
     *
     * @post execute after time is saved
     * @return self
     */
    public function setExecuteAfter($time)
    {
        $this->executeAfter = $time;
        $this->save();
        return $this;
    }

    /**
     * Get the jobid
     *
     * @return int jobid
     */
    public function getQueueId()
    {
        return (int) $this->queueId;
    }

    /**
     * Gets the queue
     *
     * @return Queue queue this job is assigned to
     */
    public function getQueue()
    {
        return new Queue($this->getQueueId());
    }

    /**
     * set the queueid
     *
     * @param int $queueId the identifier of this queue
     *
     * @post job is assigned to new queue id
     * @return self
     */
    public function setQueueId($queueId)
    {
        $this->queueId = $queueId;
        $this->save();
        return $this;
    }

    /**
     * Get the time the job was added
     *
     * @return int time job was added
     */
    public function getTimeAdded()
    {
        return (int) $this->timeAdded;
    }

    /**
     * Get the status of the job
     *
     * @return int status of the job
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets status to running
     *
     * @param int $newStatus the new status of this queue
     *
     * @post the job with the new status is saved
     * @return self
     */
    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->save();
        return $this;
    }

    /**
     * Get the time the job was executed
     *
     * @return int time job was executed
     */
    public function getTimeExecuted()
    {
        return (int) $this->timeExecuted;
    }

    /**
     * Sets finish time
     *
     * @post the job with this moment as executed time is saved
     * @return self
     */
    public function setTimeExecuted()
    {
        $this->timeExecuted = time();
        $this->save();
        return $this;
    }

    /**
     * Get the priority of this job
     *
     * @return int priority of this job
     */
    public function getPriority()
    {
        return (int) $this->priority;
    }

    /**
     * Set the priority of this job
     *
     * @param int $priority the new priority of the job
     *
     * @return self
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        $this->save();
        return $this;
    }

    /**
     * Delete this job from the database
     */
    public function delete()
    {
        static::$driver->deleteJob($this->jobId);
    }

    ///////////////////Common private function//////
    /**
     * Saves the job
     *
     * @post This job is stored in the database
     * @return void
     */
    private function save()
    {
        static::$driver->saveJob(
            $this->jobId,
            array(
                'class' => $this->class,
                'function' => $this->function,
                'args' => $this->encodeArgs($this->args),
                'queue_id' => $this->queueId,
                'priority' => $this->priority,
                'execute_after' => $this->executeAfter,
                'status' => $this->status,
                'time_executed' => $this->timeExecuted,
                'time_added' => $this->timeAdded,
            )
        );
    }
}
