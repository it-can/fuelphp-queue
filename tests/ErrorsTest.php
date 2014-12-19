<?php
/**
 * Unittests for Errors
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
 * @group queue-errors
 */
class Test_Errors extends \PHPUnit_Framework_TestCase
{
    static $queue = null;

    // Prevents double queues from forming
    public function __construct() 
    {
        if (static::$queue !== null) {
            return;
        }

        $queueName = time() . '_Errors';
        static::$queue = new Queue;
        static::$queue->create($queueName);
    }

    /**
      * Creates a new job
      * 
      * @return Job a new job
      */
    private function getNewJob()
    {
        $class = "\Dependency";
        $method = "noException";
        $args = array('testarg' => 'test');
        $queueId = static::$queue->getId();
        $priority = Job::PRIORITY_HIGH;
        $executeAfter = 10;
        $startTime = time();

        $job = new Job;
        $job->create($class, $method,
             $args, $queueId, $priority, $executeAfter);
        return $job;
    }


    ///////////////////////////TESTS////////////////////////////////

    //Test If an error is correctly set and can be loaded again
    public function testErrorCreatedLoad()
    {
        $job = static::getNewJob();
        $error = new Error;
        $error->create($job->getId(), new \Exception('test'));
        $me = $error->getErrorByJobId($job->getId());
        $this->assertEquals($job->getId(), $me['job_id']);
        $this->assertNotEmpty($me['error_messages']);
    }  
}
