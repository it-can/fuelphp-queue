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

class CodeYellow_Queue_005_Threshold
{
    public function up()
    {

        //Akters jobs table
        \DBUtil::add_fields('queue_queues', array(
            'threshold' => array(
                'type' => 'int',
                'constraint' => 255,
                'default' => 0

            ),
            'timeperiod_threshold' => array(
                'type' => 'int',
                'constraint' => 255,
                'default' => 0
            ),
            'execute_after' => array (
                'type' => 'int',
                'constaint' => 255,
                'default' => 0
            )
        ));

        //Drop legacy fields
        \DBUtil::drop_fields(
            'queue_queues',
            array(
                'max_items_hour'
            )
        );
    }

    public function down()
    {
        \DbUtil::add_fields(
            'queue_queues',
            array(
                'max_items_hour' => array(
                    'type' => 'int',
                    'constraint' => 255,
                    'default' => 0
                ),
            )
        );

        \DbUtil::drop_fields(
            'queue_queues',
            array(
                'threshold',
                'timeperiod_threshold',
                'execute_after'
            )
        );
    }
}
