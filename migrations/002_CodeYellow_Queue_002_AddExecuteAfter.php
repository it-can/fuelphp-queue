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
 * Add execute_after.
 */
class CodeYellow_Queue_002_AddExecuteAfter
{
    public function up()
    {
        \DBUtil::add_fields('queue', array(
            'execute_after' => array('constraint' => 11, 'type' => 'int')
        ));
    }

    public function down()
    {
        \DBUtil::drop_fields('queue', array(
            'execute_after'
        ));
    }
}
