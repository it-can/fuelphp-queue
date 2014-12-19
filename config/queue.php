<?php
/**
 * Config file for Queue
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


return array(
    /*
    * Not database related config options
    */
    'base_url' => 'queue',    // The url that the queue page is shown upon

    /**
     * If enabled, queues that don't exist will be created if loaded
     */
    'implicit_queue_creation' => true,
    /**
     * If enabled, implicitely created queues will start automatically
     */
    'implicit_queue_start_automatically' => true,

    /**
     * The command that is needed to start the queue from the database.
     */
    'queue_command' => 'php oil r queue',

    /**
     * Where to log the done jobs. Possible values: 'database', 'file', 'none'
     */
    'log_option' => 'database',

    // If log_option = 'file' set the logfile location
    'log_file' => './queue.log'
);
