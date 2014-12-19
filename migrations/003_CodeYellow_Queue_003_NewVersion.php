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

/**
 * new DB schema for the new version
 */
class CodeYellow_Queue_003_NewVersion
{
    public function up()
    {

        //Queue job table. Raname + add_fields
        \DBUtil::rename_table('queue','queue_jobs');

        \DBUtil::add_fields('queue_jobs', array(
            'queue_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true
            ),
            'time_added' => array(
                'type' => 'int',
                'constraint' => 100
            ),
            'time_executed' => array(
                'type' => 'int',
                'constraints' => 100
            ),
            'status' => array(
                'type' => 'int',
                'constaints' => 5
            )
        ));

        //queue_queues table
        \DBUtil::create_table('queue_queues', array(
            'id' => array(
                'type' => 'int',
                'constraint' => 10,
                'auto_increment' => true,
                'unsigned' => true
            ),
            'name' => array(
                'type' => 'varchar',
                'constraint' => 255
            ),
            'max_items_hour' => array(
                'type' => 'int',
                'constraint' => 255,
                'default' => 0
            ),
            'status' => array(
                'type' => 'int',
                'constraint' => 2,
                'default' => 0
            ),
        ), array(
            'id'
        ));

        //queue_error
        \DBUtil::create_table('queue_errors', array(
            'id' => array(
                'type' => 'int',
                'constraint' => 10,
                'auto_increment' => true,
                'unsigned' => true
            ),
            'job_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true
            ),
            'error_messages' => array(
                'type' => 'text',
            ),
        ), array(
            'id'
        ));

        //Add foreign keys
        \DBUtil::add_foreign_key('queue_jobs',array(
            'constraint' => 'queue_job_queue',
            'key' => 'queue_id',
            'reference' => array(
                'table' => 'queue_queues',
                'column' => 'id',
            ),
        ));

        \DBUtil::add_foreign_key('queue_errors',array(
            'constraint' => 'queue_job_error',
            'key' => 'job_id',
            'reference' => array(
                'table' => 'queue_jobs',
                'column' => 'id',
            ),
        ));
    }

    public function down()
    {
        //Drop foreign keys
        \DBUtil::drop_foreign_key('queue_jobs','queue_job_queue');
        \DBUtil::drop_foreign_key('queue_errors','queue_job_error');

        //Drop error table
        \DBUtil::drop_table('queue_errors');

        //Drop queues table
        \DBUtil::drop_table('queue_queues');

        //Revert changes to queue table
        \DBUtil::drop_fields('queue_jobs', array(
            'time_added',
            'time_executed',
            'status',
            'queue_id'
        ));
        \DBUtil::rename_table('queue_jobs','queue');
    }
}
