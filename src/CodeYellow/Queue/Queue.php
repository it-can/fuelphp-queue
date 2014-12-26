<?php
/**
 * Queue class
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
 * Queue class, handles all the functions for the queues.
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 */
class Queue
{
    const STATUS_OFF     = 0;
    const STATUS_ON      = 1;
    const STATUS_DELETED = 2;

    /**
     * The driver we are using
     */
    private static $driver = null;

    // Queue vars
    /**
     * The queue that is loaded
     */
    private $queueId = null;

    /**
     * The name of the queue that is loaded
     */
    private $name = null;

    /**
     * The status of the queue that is loaded
     */
    private $status = null;

    /**
     * The value of the threshold of the queue that is loaded
     */
    private $threshold = null;

    /**
     * The timeperiod for which the threshold holds for the queue that is loaded
     */
    private $timeperiodThreshold = null;

    /**
     * The time that the next job may be executed for the queue that is loaded
     */
    private $executeAfter = null;

    /**
     * Load the driver
     *
     * @param string/int $name the name of the id of the queue that needs to be loaded
     *
     * @post driver variable contains the driver
     * @throws queueNotExists exception if a non existed queue
     *                        is opened, and explicit creation is disabled
     */
    public function __construct($name = null)
    {
        static::$driver = new Driver_Db;

        // Check if a queue needs to be loaded
        if ($name != null) {
            // Check if this queue exists
            if (static::$driver->selectQueueIdByName($name) != null) {
                $this->load($name);
            // Else, check if implicit queue creation is enabled
            } elseif (\Config::get('queue.implicit_queue_creation')) {
                $this->create($name);

                // If implicit queue start automatically is enabled
                if (\Config::get('queue.implicit_queue_start_automatically')) {
                    $this->startQueue();
                }
            } else {
                throw new Exception\QueueNotExists();
            }
        }
    }

    /**
     * Create a new queue
     *
     * @param string $name                The name of the queue
     * @param int    $threshold           The threshold for the queue
     * @param int    $timeperiodThreshold The timeperiod for twhich the threshold holds
     *
     * @return self
     * @throws Exception_QueueAlreadyExists when a queue already exists
     */
    public function create($name, $threshold = 0, $timeperiodThreshold = 0)
    {
        if (static::$driver->selectQueueIdByName($name) != null) {
            throw new Exception\QueueAlreadyExists();
        }

        $this->queueId = static::$driver->addNewQueue(
            $name,
            static::STATUS_OFF,
            $threshold,
            $timeperiodThreshold
        );

        $this->name                = $name;
        $this->status              = static::STATUS_OFF;
        $this->threshold           = $threshold;
        $this->timeperiodThreshold = $timeperiodThreshold;
        $this->executeAfter        = 0;

        return $this;
    }

    /**
     * Loads a queue
     *
     * @param int $identifier integer if id, or string if name
     *
     * @return id int
     */
    public function load($identifier)
    {
        $queue = null;
        if (is_int($identifier)) {
            $queue = static::$driver->getQueueById($identifier);
        } else {
            $queue = static::$driver->getQueueById(
                static::$driver->selectQueueIdByName($identifier)
            );
        }

        $this->queueId             = $queue['id'];
        $this->name                = $queue['name'];
        $this->status              = $queue['status'];
        $this->threshold           = $queue['threshold'];
        $this->timeperiodThreshold = $queue['timeperiod_threshold'];
        $this->executeAfter        = $queue['execute_after'];
    }

    ///////////////Common getters/setters///////////////////////
    /**
     * Gets the moment after which the queue may execute another job
     *
     * @return int time when next job may be executed
     */
    public function getExecuteAfter()
    {
        return $this->executeAfter;
    }

    /**
     * Gets the threshold settings of this queue
     *
     * @return array threshold parameters
     */
    public function getThreshold()
    {
        return array(
            'threshold' => $this->threshold,
            'timeperiod' => $this->timeperiodThreshold
        );
    }

    /**
     * Sets the threshold for this queue
     *
     * @param int $threshold  the new threshold of the queue
     * @param int $timeperiod the timeperiod of the threshold
     *
     * @return self
     */
    public function setThreshold($threshold, $timeperiod)
    {
        $this->threshold           = $threshold;
        $this->timeperiodThreshold = $timeperiod;
        $this->saveQueue();

        // Calculate new execute after
        if ($timeperiod == 0 || $timeperiod == 0) {
            // If threshold disabled, reset execute after
            static::$driver->setExecuteAfter($this->queueId, 0);
        } else {
            // Else, new execute after = min(timeleft, timePerItem)
            static::$driver->setExecuteAfter(
                $this->queueId,
                min(
                    $this->getExecuteAfter(),
                    time() + $this->timeperiodThreshold / $this->threshold
                )
            );
        }

        return $this;
    }

    /**
     * Get the queue id
     *
     * @return id of the queue
     */
    public function getId()
    {
        return (int) $this->queueId;
    }

    /**
     * Get a queue name
     *
     * @return name of loaded queue
     */
    public function getName()
    {
        return (string) $this->name;
    }

    /**
     * Sets the queue name
     *
     * @param string $name new name of queue
     *
     * @return self
     * @post new name is saved to the database
     */
    public function setName($name)
    {
        if (static::$driver->selectQueueIdByName($name) != null) {
            throw new Exception\QueueAlreadyExists();
        }

        $this->name = $name;
        $this->saveQueue();

        return $this;
    }

    /**
     * Get the items Status
     *
     * @return threshold of this queue
     */
    public function getStatus()
    {
        return $this->status;
    }

    ///////////////Queue Status management///////////////////
    /**
     * Deletes this queue
     *
     * @post queue status is set to deleted
     * @return self
     */
    public function deleteQueue()
    {
        $this->status = static::STATUS_DELETED;
        $this->saveQueue();

        return $this;
    }

    /**
     * Starts the queue
     *
     * @post queue status is set to on
     * @return self
     */
    public function startQueue()
    {
        $this->status = static::STATUS_ON;
        $this->saveQueue();

        return $this;
    }

    /**
     * Stops the queue
     *
     * @return self
     * @post queue status is set to off
     */
    public function stopQueue()
    {
        $this->status = static::STATUS_OFF;
        $this->saveQueue();

        return $this;
    }

    ///////////////Common private functions////////////////////

    /**
     * Saves this queue
     *
     * @return void
     * @post queue is saved in database
     */
    private function saveQueue()
    {
        $this->checkQueueSelected();
        static::$driver->saveQueue(
            $this->queueId,
            $this->name,
            $this->status,
            $this->threshold,
            $this->timeperiodThreshold
        );
    }

    /**
     * Checks if a queue is selected
     *
     * @throws Exception_NoQueueSelected when no queue is selected
     * @return void
     */
    private function checkQueueSelected()
    {
        if ($this->queueId == null) {
            throw new Exception\NoQueueSelected();
        }
    }

    /**
     * Takes the threshold, and updates the execute after in the database
     *
     * @return self
     */
    public function updateExecuteAfter()
    {
        // Check if a threshold is set. If not, do not do anything
        if ($this->timeperiodThreshold == 0 || $this->threshold == 0) {
            return $this;
        }

        // Else calculate the new execute after
        $this->executeAfter = time() + ceil($this->timeperiodThreshold / $this->threshold);
        static::$driver->setExecuteAfter($this->queueId, $this->executeAfter);

        return $this;
    }
}
