<?php
/**
 * Unittests for Queue
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
 * @group queue
 * @group queue-queues
 */
class Test_Queues extends \PHPUnit_Framework_TestCase
{

    public static $queue_prefix;
    public static $queue_id;

    // Prevents double queues from forming
    public function __construct()
    {
        static::$queue_prefix = time();
        static::$queue_id = 0;
    }

    // Gets a unique queue name
    public function getUniqueName()
    {
        return static::$queue_prefix . '_' . static::$queue_id++;
    }

    ///////////////////////////TESTS///////////////////////////////////

    // Create a new queue
    // And load a queue
    // Test if both create and load function work
    public function testCreateLoad()
    {
        $name = $this->getUniqueName();
        $threshold = 10;
        $timespanThreshold = 3600;

        (new Queue)->create($name, $threshold, $timespanThreshold);
        $testQueue = new Queue;
        $testQueue->load($name);

        $this->assertEquals($name, $testQueue->getName());
        $this->assertEquals($threshold, $testQueue->getThreshold()['threshold']);
        $this->assertEquals($timespanThreshold, $testQueue->getThreshold()['timeperiod']);
    }

    // Tests if saving the queue works
    /**
    * @group queue-queues-save
     */
    public function testSaveQueue()
    {
        $nameBefore = $this->getUniqueName();
        $threshold = 10;
        $timespanThreshold = 3600;

        $nameAfter = $this->getUniqueName();
        $statusAfter = Queue::STATUS_DELETED;
        $thresholdAfter = 20;
        $timespanThresholdAfter = 7200;

        $testQueue = new Queue; // Used to create and edit a queue
        $testQueue2 = new Queue; // Test if the changes are saved to the database

        $testQueue->create($nameBefore, $threshold, $timespanThreshold);
        $testQueue2->load($nameBefore);

        // Check if values of a newly created queue are correct
        $this->assertEquals($nameBefore, $testQueue2->getName());
        $this->assertEquals($threshold, $testQueue2->getThreshold()['threshold']);
        $this->assertEquals($timespanThreshold, $testQueue2->getThreshold()['timeperiod']);
        $this->assertEquals(Queue::STATUS_OFF, $testQueue2->getStatus());

        // Alter, save, and check if the correct values are saved
        // Working save function is implicit from this function
        $testQueue->setThreshold($thresholdAfter, $timespanThresholdAfter);
        $testQueue->setName($nameAfter);
        $testQueue->deleteQueue();

        $testQueue2->load($testQueue2->getId());
        $this->assertEquals($thresholdAfter, $testQueue2->getThreshold()['threshold']);
        $this->assertEquals($timespanThresholdAfter, $testQueue2->getThreshold()['timeperiod']);
        $this->assertEquals($statusAfter, $testQueue2->getStatus());

    }

    // Check if the status functions work on the queue
    public function testStatusQueue()
    {
        $name = $this->getUniqueName();
        (new Queue)->create($name, 0);

        $testQueue = new Queue;    // To check it is saved locally
        $testQueue->load($name);
        $testQueue2 = new Queue;    // To check it is saved globally
        $this->assertEquals(Queue::STATUS_OFF, $testQueue->getStatus());

        $testQueue->startQueue();
        $testQueue2->load($name);
        $this->assertEquals(Queue::STATUS_ON, $testQueue->getStatus());
        $this->assertEquals(Queue::STATUS_ON, $testQueue2->getStatus());

        $testQueue->stopQueue();
        $testQueue2->load($name);
        $this->assertEquals(Queue::STATUS_OFF, $testQueue->getStatus());
        $this->assertEquals(Queue::STATUS_OFF, $testQueue2->getStatus());

        $testQueue->deleteQueue();
        $testQueue2->load($name);
        $this->assertEquals(Queue::STATUS_DELETED, $testQueue->getStatus());
        $this->assertEquals(Queue::STATUS_DELETED, $testQueue2->getStatus());
    }

    // Check if the checkQueueSelected function is working correct
    /**
     * @expectedException \CodeYellow\Queue\Exception\NoQueueSelected
     */
    public function testQueueSelectedException()
    {
        $testQueue = new Queue;
        $testQueue->startQueue();
    }

    public function testQueueSelectedNoException()
    {
        $testQueue = new Queue;
        $testQueue->create($this->getUniqueName());
        $testQueue->startQueue();
    }

    /**
    * @expectedException \CodeYellow\Queue\Exception\QueueAlreadyExists
    */
    public function testRenameToAlreadyExistingNameThrowsException()
    {
        $name1 = $this->getUniqueName();
        $name2 = $this->getUniqueName();
        $testQueue1 = new Queue;
        $testQueue1->create($name1);
        $testQueue1->startQueue();

        $testQueue2 = new Queue;
        $testQueue2->create($name2);
        $testQueue2->startQueue();

        $testQueue2->setName($name1);
    }

    // Check if chaining works
    public function testChaining()
    {
        $name = $this->getUniqueName();
        $name2 = $this->getUniqueName();

        $queue = (new Queue())
                    ->create($name);

        $this->assertEquals($queue->getName(), $name);
        $queue = $queue->setName($name2)
            ->setThreshold(10, 10)
            ->startQueue()
            ->stopQueue()
            ->deleteQueue();
        $this->assertEquals($queue->getName(), $name2);
    }

    // Test if implicit construction works
    public function testImplicitCreation()
    {
        \Config::set('queue.implicit_queue_creation', true);
        $name = $this->getUniqueName();
        $queue = new Queue($name);
        $queue2 = new Queue($name);
        $this->assertEquals($queue->getName(), $queue2->getName());
    }

    // Test if an error is thrown if queue not exists and
    // implicit construction is switched off
    /**
     * @expectedException \CodeYellow\Queue\Exception\QueueNotExists
     */
    public function testQueueNotExists()
    {
        \Config::set('queue.implicit_queue_creation', false);
        $name = $this->getUniqueName();
        new Queue($name);
    }

    /**
     * Test if implicit_queue_start_automatically works
     */
    public function testImplicitQueueStart()
    {
        $name = $this->getUniqueName();
        $name2 = $this->getUniqueName();
        \Config::set('queue.implicit_queue_creation', true);
        \Config::set('queue.implicit_queue_start_automatically', false);
        $queue = new Queue($name);
        $this->assertEquals(Queue::STATUS_OFF, $queue->getStatus());

        \Config::set('queue.implicit_queue_start_automatically', true);
        $queue = new Queue($name2);
        $this->assertEquals(Queue::STATUS_ON, $queue->getStatus());
    }

    /**
     * Tests the threshold facilities of queue
     */
    public function testThreshold()
    {
        $queue = (new Queue())->create(static::getUniqueName(), 10, 3600)->updateExecuteAfter();

        // Test if the updateExecuteAfter works;
        $this->assertGreaterThanOrEqual(359, $queue->getExecuteAfter() - time());
        $this->assertLessThanOrEqual(360, $queue->getExecuteAfter() - time());
    }
}
