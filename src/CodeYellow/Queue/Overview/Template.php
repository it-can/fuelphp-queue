<?php
/**
 * Template to show the status of the queue
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
 * @SuppressWarnings(PHPMD)
 */
?>
<h2>Status Page:</h2>
<html>
    <body>
        <table>
            <tr>
                <th>Id:</th>
                <th>Queue:</th>
                <th>Class:</th>
                <th>Function:</th>
                <th>Arguments:</th>
                <th>Time Added:</th>
                <th>Time Finished:</th>
                <th>Status:</th>
            </tr>
            <?php foreach($options['jobs'] as $job): ?>
            <tr>
                <td><?php echo $job['id'];?></td>
                <td><?php echo $job['name'];?></td>
                <td><?php echo $job['class'];?></td>
                <td><?php echo $job['function'];?></td>
                <td><?php echo $job['payload'];?></td>
                <td><?php echo $job['time_added'];?></td>
                <td><?php echo $job['time_executed'];?></td>
                <td><?php
                        switch($job['status']):
                            case \CodeYellow\Queue\Job::STATUS_IN_QUEUE: echo 'Waiting'; break;
                            case \CodeYellow\Queue\Job::STATUS_RUNNING: echo 'Busy'; break;
                            case \CodeYellow\Queue\Job::STATUS_DONE: echo 'Done'; break;
                            case \CodeYellow\Queue\Job::STATUS_FAILED: echo 'Failed'; break;
                            case \CodeYellow\Queue\Job::STATUS_DELETED: echo 'deleted'; break;
                        endswitch;
                    ?></td>
                <?php if($job['status'] == \CodeYellow\Queue\Job::STATUS_IN_QUEUE):?>
                <td><a href="queue/delete/<?php echo $job['id'];?>">Delete</a></td>
                <?php endif;?>
            </tr>
        <?php endforeach;?>
        </table>
    </body>
</html>
