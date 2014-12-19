<?php
/**
 * Controller for the template
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
 * Controller for the template
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class Overview_Controller extends \Controller
{
    /**
     * Load the config file
     *
     * @post queue config is available
     */
    public function __construct()
    {
        \Config::load(
            __DIR__ . DS . '..' . DS .'..' . DS . '..' . DS . 'config'
            . DS . 'queue.php',
            'queue'
        );
    }

    /**
     * Display the overview of all the jobs
     *
     * @return view The view to be displayed
     */
    public function action_index()
    {
        $core = new Core;
        $data['options'] = array();
        $data['options']['jobs'] = $core->getJobs();
        $view = \View::forge(\Config::get('queue.base_url'), $data);

        return $view;
    }

    /**
     * Deletes a job from the queue.
     *
     * @param int $jobId id of the queue entry to be deleted
     *
     * @return void
     * @throws Exception when the job can not be deleted
     * @postcondition user is redirected to the overview
     */
    public function action_delete($jobId = null)
    {
        $jobId == null && \Response::redirect(\Config::get('queue.base_url'));

        // Find the job
        $job = new Job;
        $job->load($jobId);
        
        if ($job->status != Job::STATUS_IN_QUEUE) {
            throw new Exception(
                'Only jobs which have' .
                ' not yet begun can be deleted'
            );
        }

        $job->setStatus(Job::STATUS_DELETED);
        \Response::redirect(\Config::get('queue.base_url'));

    }
}
