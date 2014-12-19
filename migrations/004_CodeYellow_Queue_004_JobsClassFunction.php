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

class CodeYellow_Queue_004_JobsClassFunction
{
    function up()
    {

        //Akters jobs table
        \DBUtil::add_fields('queue_jobs', array(
            'class' => array(
                'type' => 'varchar',
                'constraint' => 255,
            ),
            'function' => array(
                'type' => 'varchar',
                'constraint' => 255, 
            ),
        ));

        //Drop legacy fields
        \DBUtil::drop_fields('queue_jobs',array(
            'queue'
        ));
    }

    function down()
    {
        //Revert changes to queue table
        \DBUtil::drop_fields('queue_jobs', array(
            'class',
            'function',
        ));

        \DBUtil::add_fields('queue_jobs',array(
            'queue' => array(
                'type' => 'varchar', 
                'constraint' => '40'
            ),
        ));
    }
}