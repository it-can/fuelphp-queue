<?php
/**
 * Unittests for Core
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
 * @group queue-core
 */
class Test_Core extends \PHPUnit_Framework_TestCase
{
    public static $queue = null;
    public static $core = null;

    // Prevents double queues from forming
    public function __construct()
    {
        static::$core = new Core;

        if (static::$queue !== null) {
            return;
        }

        $queueName = time() . '_Core';
        static::$queue = new Queue();
        static::$queue->create($queueName);
    }

    /**
       * Creates a new job
       *
       * @return Job a new job
       */
    private static function getNewJob()
    {
        $class = "\CodeYellow\Queue\Dependency";
        $method = "noException";
        $args = array('testarg' => 'test');
        $queueId = static::$queue->getId();
        $priority = Job::PRIORITY_HIGH;
        $executeAfter = 10;
        $startTime = time();
        $job = new Job;

        return $job->create($class, $method, $args, $queueId, $priority, $executeAfter);
    }

    /**
     * Deletes all the jobs
     *
     * @post all jobs are removed from the jobs table
     */
    private function truncateJobs()
    {
        \DB::delete('queue_errors')
            ->execute();

        \DB::delete('queue_jobs')
            ->execute();
    }

    ///////////////////////////TESTS////////////////////////////////

    // Test if the getJobs function works
    public function testGetJobs()
    {
        $this->truncateJobs();

        $amountTestCases = 5;    // Amount of jobs tested
        $jobLoad = array();        // The jobs as given
        $job = array();            // The actual jobs

        for ($i = $amountTestCases -1; $i >= 0; $i--) {
            $job[$i] = self::getNewJob();
            $jobLoad[$i] = new Job;
        }

        // Changes to job, You can assume that $job->setStatus works
        $job[1]->setStatus(Job::STATUS_DELETED);
        $job[2]->setStatus(Job::STATUS_DONE);

        $me = static::$core->getJobs();

        // Load all the jobs in the array
        // Check if they are equal to the jobs as we set them
        for ($i = $amountTestCases -1; $i >= 0; $i--) {
            $jobLoad[$i]->loadFromArray($me[$i]);
            $this->assertEquals($jobLoad[$i], $job[$i]);
        }
    }

    // Test if the getNextJob returns the correct new Job;
    public function testGetNextJob()
    {
        $this->truncateJobs();
        static::$queue->startQueue();

        // If we add two jobs, execute newest job first
        $job1 = static::getNewJob();
        $job2 = static::getNewJob();
        $nextJob = static::$core->getNextJob();
        $this->assertEquals($job2, $nextJob);

        // Check if a job is done it is not returned anymore
        $job2->setStatus(Job::STATUS_DONE);
        $nextJob = static::$core->getNextJob();
        $this->assertEquals($job1, $nextJob);

        // Check if a job has failed, it is not returned any more
        $job1->setStatus(Job::STATUS_FAILED);
        $nextJob = static::$core->getNextJob();
        $this->assertEquals(null, $nextJob);

        // Check if priority is checked correct
        $job1 = static::getNewJob();
        $job2 = static::getNewJob();
        $job1->setPriority(Job::PRIORITY_LOW);
        $job2->setPriority(Job::PRIORITY_HIGH);
        $nextJob = static::$core->getNextJob();
        $this->assertEquals($job2, $nextJob);

        // Check if only jobs from a queue that is on are returned
        static::$queue->stopQueue();
        $nextJob = static::$core->getNextJob();
        $this->assertEquals(null, $nextJob);

        static::$queue->deleteQueue();
        $nextJob = static::$core->getNextJob();
        $this->assertEquals(null, $nextJob);

        static::$queue->startQueue();
        $job1->setStatus(Job::STATUS_DONE);
        $job2->setStatus(Job::STATUS_DONE);

        // Check if only jobs that still need to be executed are done
        $job1 = static::getNewJob();
        $job1->setExecuteAfter(time() - 1);
        $nextJob = static::$core->getNextJob();
        $this->assertEquals($job1, $nextJob);

        $job1->setExecuteAfter(time() + 5);
        $nextJob = static::$core->getNextJob();

        $this->assertEquals(null, $nextJob);
    }

    public function testLongArgsJob()
    {
        $job1 = static::getNewJob();
        $range = array();
        for($i = 0; $i < 10; $i++) {
            $range["key_{$i}"] = $i;
        }
        $job1->setArgs($range);
        
        $job1Copy = (new Job())->load($job1->getId());
        $this->assertEquals($job1->getArgs(), $job1Copy->getArgs(), 'Medium-length argument should be save/restored correctly');

        $job2 = static::getNewJob();
        $range = array();
        for($i = 0; $i < 10000; $i++) { // Oddly enough, it works fine with length 1000!
            $range["key_{$i}"] = $i;
        }
        $job2->setArgs($range);
        $job2Copy = (new Job())->load($job2->getId());
        $this->assertEquals($job2->getArgs(), $job2Copy->getArgs(), 'Big-length argument should be save/restored correctly');
    }

    // Test if the executeJob function works
    public function testExecuteJob()
    {
        $job1 = static::getNewJob();
        $job2 = static::getNewJob();
        $job3 = static::getNewJob();

        // A good job should return true;
        $this->assertTrue(static::$core->executeJob($job1));
        $this->assertEquals($job1->getStatus(), Job::STATUS_DONE);

        // // A bad job  should return false
        $job2->setFunction('withException');
        $this->assertFalse(static::$core->executeJob($job2));
        $this->assertEquals($job2->getStatus(), Job::STATUS_FAILED);

        // A non existsent job should return false;
        $job3->setFunction('notExistent');
        $this->assertFalse(static::$core->executeJob($job3));
        $this->assertEquals($job3->getStatus(), Job::STATUS_FAILED);
    }

    // Make sure that the type of payload that is set, is the same type
    // that is given when actual execution happens
    public function testPayloadType()
    {
        $job1 = static::getNewJob(); // array
        $job2 = static::getNewJob(); // object

        $job1->setArgs(array('a' => array("a" => "b"), 'b' => 2));
        $job2->setArgs(array('a' => new Dependency(), 'b' => 2));

        // After loading typee should be the same
        $job1 = (new Job())->load($job1->getId());
        $job2 = (new Job())->load($job2->getId());

        $this->assertTrue(is_array($job1->getArgs()['a']), "'a' should be an array");
        $this->assertTrue(is_object($job2->getArgs()['a']), "'a' should be an object");
    }

    // Tests if the threshold works
    public function testThreshold()
    {
        // Set the threshold
        static::$queue->setThreshold(10, 3600)->updateExecuteAfter();

        // Get a new jobs
        $job1 = static::getNewJob();

        // Make sure that job1 is not executed, because of threshold
        $jobnew = static::$core->getNextJob();
        $this->assertEquals(null, $jobnew);

        // Now, delete threshold. Check if this is ok again
        static::$queue->setThreshold(0, 0)->updateExecuteAfter();

        $jobnew = static::$core->getNextJob();
        $this->assertEquals($job1, $jobnew);
    }

    // Test if log_option none works
    public function testLogNone()
    {
        $job = static::getNewJob();
        \Config::set('queue.log_option', 'none');
        \Config::set('queue.log_file', '/tmp/test');
        Core::executeJob($job);
        $results = \DB::select('*')->from('queue_jobs')->where('id', $job->getId())->execute();
        $this->assertEmpty($results);

        // Read the file
        $this->assertFalse(file_exists('/tmp/test'));
    }

    // Test if log_option none works
    public function testLogFile()
    {
        $job = static::getNewJob();
        \Config::set('queue.log_option', 'file');
        \Config::set('queue.log_file', '/tmp/test');
        Core::executeJob($job);
        $results = \DB::select('*')->from('queue_jobs')->where('id', $job->getId())->execute();
        $this->assertEmpty($results);

        // Read the file
        $file = file_get_contents('/tmp/test');
        unlink('/tmp/test');
        $this->assertNotEmpty($file);
    }

}
