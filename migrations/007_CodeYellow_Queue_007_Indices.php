<?php
/**
 * Migrations
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

namespace Fuel\Migrations;

class Codeyellow_queue_007_Indices
{
    public function up()
    {
        \DBUtil::create_index(
            'queue_jobs',
            array(
                'status'
            ),
            'jobs_select_status'
        );

        \DBUtil::create_index(
            'queue_jobs',
            array(
                'execute_after' => 'asc'
            ),
            'jobs_select_execute_after'
        );
    }

    public function down()
    {
        \DBUtil::drop_index('queue_jobs', 'jobs_select_status');
        \DBUtil::drop_index('queue_jobs', 'jobs_select_execute_after');
    }
}
