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

class CodeYellow_Queue_006_PayloadSize
{
    function up()
    {
        \DBUtil::modify_fields('queue_jobs', array(
            'payload' => array(
                'type' => 'longtext',
            ),
        ));
    }

    function down()
    {
        \DBUtil::modify_fields('queue_jobs', array(
            'payload' => array(
                'type' => 'text',
            ),
        ));
    }
}
