<?php
/**
 * Unittests for Jobs
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
 * @group queue-jobs
 */
class Test_Jobs extends \PHPUnit_Framework_TestCase
{

    static $queue = null;
    static $queue2 = null;

    // Prevents double queues from forming
    public function __construct()
    {
        if (static::$queue !== null) {
            return;
        }

        $queueName = time() . '_Jobs';
        static::$queue = new Queue;
        static::$queue->create($queueName);

        $queueName = time() . '_Jobs2';
        static::$queue2 = new Queue;
        static::$queue2->create($queueName);
    }


    ///////////////////////////TESTS////////////////////////////////
    
    // Test if we can create and load a job
    // Implicitly tests the loadFromArray function
    public function testCreateLoad()
    {
        $class = "\Dependency";
        $method = "noException";
        $args = array('testarg' => 'test');
        $queueId = static::$queue->getId();
        $priority = Job::PRIORITY_HIGH;
        $executeAfter = 10;
        $startTime = time();

        $job = new Job;
        $jobid = $job->create(
            $class,
            $method,
            $args,
            $queueId,
            $priority,
            $executeAfter
        )->getId();

        $job2 = new Job;
        $job2->load($jobid);

        $this->assertEquals($jobid, $job2->getId());
        $this->assertEquals($method, $job2->getFunction());
        $this->assertEquals($args, $job2->getArgs());
        $this->assertEquals($queueId, $job2->getQueueId());
        $this->assertEquals($executeAfter, $job2->getExecuteAfter());

        //Less than to prevent errors caused by slow database
        $this->assertLessThan(5, $job2->getTimeAdded() - $startTime);
        $this->assertEquals(0, $job2->getTimeExecuted());
        $this->assertEquals($priority, $job2->getPriority());
        $this->assertEquals(Job::STATUS_IN_QUEUE, $job2->getStatus());
    }

    // Test if a non array for arguments creates an error
    /**
     * @expectedException \CodeYellow\Queue\Exception\InvalidArguments
     */
    public function testCreateInvalidArgumentsException()
    {
        $class = "\Dependency";
        $method = "noException";
        $args = 'testarg';
        $queueId = static::$queue->getId();
        $priority = Job::PRIORITY_HIGH;
        $executeAfter = 10;
        $startTime = time();

        $job = new Job;
        $jobid = $job->create(
            $class,
            $method,
            $args,
            $queueId,
            $priority,
            $executeAfter
        );
    }

    // Test if default priority of the queue is medium
    public function testCheckDefaultPriority()
    {
        $job = new Job('Test', 'Test', array(), static::$queue->getId());
        $this->assertEquals(Job::PRIORITY_MEDIUM, $job->getPriority());
    }


    // Test if args = null no exception occurs
    public function testCreateNullArgumentsNoException()
    {
        $class = "\Dependency";
        $method = "noException";
        $args = null;
        $queueId = static::$queue->getId();
        $priority = Job::PRIORITY_HIGH;
        $executeAfter = 10;
        $startTime = time();

        $job = new Job;
        $jobid = $job->create($class, $method, $args, $queueId, $priority, $executeAfter);
    }

    // Regression test: original code created "impossible arrays" which had
    // numerical-looking string indices, which are impossible to dereference.
    public function testIntegerKeysCreateValidArrays()
    {
        $class = "\Dependency";
        $method = "noException";
        $args = array('1' => 'one', 'foo' => 2);
        $queueId = static::$queue->getId();
        $priority = Job::PRIORITY_HIGH;
        $executeAfter = 10;
        $startTime = time();

        $job = new Job;
        $job = $job->create($class, $method, $args, $queueId, $priority, $executeAfter);
        $job->load($job->getId()); // reload, just in case
        $args = $job->getArgs();
        $this->assertEquals('one', $args[1]);
    }
    
    //Test if an exception occurs if we select an invalid queue
    /**
     * @expectedException \CodeYellow\Queue\Exception\QueueNotExists
     */
    public function testCreateQueueNotExistsException()
    {
        \Config::set('queue.implicit_queue_creation', false);
        $class = "\Dependency";
        $method = "noException";
        $args = null;
        $queueId = -1;
        $priority = Job::PRIORITY_HIGH;
        $executeAfter = 10;
        $startTime = time();

        $job = new Job;
        $job->create(
            $class,
            $method,
            $args,
            $queueId,
            $priority,
            $executeAfter
        );
    }

    //Test if an exception occurs if we load an invalid job
    /**
     * @expectedException \CodeYellow\Queue\Exception\JobNotFound
     */
    public function testLoadJobNotFoundException()
    {
        $job = new Job;
        $job->load(-1);
    }

    // Test all the getters and setters
    // Implicitly tests the save function
    public function testGettersSetters()
    {
        $class = "\Dependency";
        $newClass = "\Test";
        $method = "noException";
        $newMethod = "withException";
        $args = array('testarg' => 'test');
        $newArgs = array('a' => 'b');
        $queueId = static::$queue->getId();
        $newQueueId = static::$queue2->getId();
        $priority = Job::PRIORITY_HIGH;
        $newPriority = Job::PRIORITY_LOW;
        $executeAfter = 10;
        $newExecuteAfter = 100;
        $newStatus = Job::STATUS_DONE;

        $job = new Job;
        $jobid = $job->create(
            $class,
            $method,
            $args,
            $queueId,
            $priority,
            $executeAfter
        )->getId();

        // Set all variable to the new value
        $job->setClass($newClass);
        $job->setFunction($newMethod);
        $job->setArgs($newArgs);
        $job->setExecuteAfter($newExecuteAfter);
        $job->setQueueId($newQueueId);
        $job->setStatus($newStatus);
        $job->setPriority($newPriority);

        $job2 = new Job;
        $job2->load($jobid);

        // check if it is correctly saved locally
        $this->assertEquals($newClass, $job->getClass());
        $this->assertEquals($newMethod, $job->getFunction());
        $this->assertEquals($newArgs, $job->getArgs());
        $this->assertEquals($newQueueId, $job->getQueueId());
        $this->assertEquals($newStatus, $job->getStatus());
        $this->assertEquals($newPriority, $job->getPriority());

        // check if it is correctly saved in the database
        $this->assertEquals($newClass, $job2->getClass());
        $this->assertEquals($newMethod, $job2->getFunction());
        $this->assertEquals($newArgs, $job2->getArgs());
        $this->assertEquals($newQueueId, $job2->getQueueId());
        $this->assertEquals($newStatus, $job2->getStatus());
        $this->assertEquals($newPriority, $job2->getPriority());
    }

    // Test if a job creation with queue name works be found
    public function testCreateWithQueueName()
    {
        $class = "\Dependency";
        $method = "noException";
        $args = null;
        $queueId = static::$queue->getName();
        $priority = Job::PRIORITY_HIGH;
        $executeAfter = 10;
        $startTime = time();

        $job = new Job;
        $jobId = $job->create(
            $class,
            $method,
            $args,
            $queueId,
            $priority,
            $executeAfter
        );

        $this->assertEquals($job->getQueueId(), static::$queue->getId());
    }

    // Test if chaining works
    public function testChaining()
    {
        $job = (new Job())
            ->create(
                'TEST',
                'TEST',
                array(),
                static::$queue->getId()
            );
        $this->assertEquals($job->getQueueId(), static::$queue->getId());

        $job = $job
            ->setClass('test2')
            ->setFunction('testfunc')
            ->setArgs(array('a'=>'b'))
            ->setExecuteAfter(10)
            ->setQueueId(static::$queue2->getid())
            ->setStatus(Job::STATUS_IN_QUEUE)
            ->setTimeExecuted()
            ->setPriority(Job::PRIORITY_HIGH);

        $this->assertEquals($job->getClass(), 'test2');
        $this->assertEquals($job->getFunction(), 'testfunc');
        $this->assertEquals($job->getArgs(), array('a'=>'b'));
        $this->assertEquals($job->getExecuteAfter(), 10);
        $this->assertEquals($job->getQueueId(), static::$queue2->getid());
        $this->assertEquals($job->getStatus(), Job::STATUS_IN_QUEUE);
        $this->assertEquals($job->getPriority(), Job::PRIORITY_HIGH);
    }

    public function testCreateImplicitQueue()
    {
        \Config::set('queue.implicit_queue_creation', true);
        $class = "\Dependency";
        $method = "noException";
        $args = null;
        $queueId = "test";
        $priority = Job::PRIORITY_HIGH;
        $executeAfter = 10;
        $startTime = time();

        $job = new Job;
        $job->create(
            $class,
            $method,
            $args,
            $queueId,
            $priority,
            $executeAfter
        );


    }
}
