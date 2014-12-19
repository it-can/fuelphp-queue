<?php
/**
 * OverView_Viewer class
 *
 * PHP version 5
 *
 * @category  Utilities
 * @package   Queue
 * @author    Stefan Majoor <stefan@codeyellow.nl>
 * @copyright 2014 Code Yellow BV
 * @license   MIT License
 * @link      https://bitbucket.org/codeyellow/fuelphp-queue/
 *
 */

namespace CodeYellow\Queue;

/**
 * Parses the overview template
 *
 * @category Utilities
 * @package  Queue
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT License
 * @link     https://bitbucket.org/codeyellow/fuelphp-queue/
 * @SuppressWarnings(PHPMD)
 */
class Overview_Viewer
{
    /**
     * Simply copy paste the template file into the viewer
     *
     * @param array $options values for the template
     *
     * @return void
     * @post template is shown.
     */
    public static function display($options)
    {
        include 'Template.php';
    }
}
