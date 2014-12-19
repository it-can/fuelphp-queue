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

namespace Fuel\Tasks;

/**
 * Queue class, handles the run() function for a task
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 */
class Queue
{
    /** The task to be executed
      * Called by php oil r queue
      *
      * @return void
      */
    public static function run($maxJobs = -1)
    {
        // Initialize the core
        $core = new \CodeYellow\Queue\Core;

        $cmd = \Config::get('queue.queue_command');
        $sleeptime = \Config::get('queue.sleep', 10);

        \Cli::write('Starting Queue');
        while ($maxJobs-- != 0) {

            // Get the next job
            $job = $core->getNextJob();

            // Check if a job is available. If not, wait and try again
            if ($job == null) {
                \Cli::write("No job found");
                sleep($sleeptime);
                // Sleep disabled for testing
                continue;
            }

            \Cli::write('Job fetched. Try the job!');

            // Execute job
            $start = time();
            $status = "";
            exec($cmd . ':executeJob ' . (int) $job->getId(), $status);

            // Write all the debug information from the job itself
            $i = 0;
            for ($i = 0; $i < count($status) -1; $i++) {
                \Cli::write($status[$i]);
            }

            //$status = $core->executeJob($job);
            $finish = time();
            \Cli::write($status[$i] == 1 ? "Job succesfully executed!" : "Job failed!");
            \Cli::write(
                'Time elapsed:' . ($finish - $start) .
                ' Memory usage:  ' . memory_get_usage()
            );

            // Also if a job was completed succesfully, give it a 1 second rest to easy down the cpu
            sleep($sleeptime);
        }
    }

    public static function executeJob($jobId)
    {
        $core = new \CodeYellow\Queue\Core;
        $job = new \CodeYellow\Queue\Job();
        $job->load($jobId);
        $status = $core->executeJob($job);
        return "\n" . $status;
    }
}
