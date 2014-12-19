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

class CodeYellow_Queue_001_Create
{
    public function up()
    {
        \DBUtil::create_table('queue', array(
            'id' => array(
                'type' => 'int',
                'constraint' => 10,
                'auto_increment' => true,
                'unsigned' => true
            ),
            'queue' => array(
                'type' => 'varchar',
                'constraint' => 40
            ),
            'priority' => array(
                'type' => 'int',
                'constraint' => 1,
                'default' => 5
            ),
            'payload' => array(
                'type' => 'text',
            ),
        ), array(
            'id'
        ));
    }

    public function down()
    {
        \DBUtil::drop_table('queue');
    }
}
