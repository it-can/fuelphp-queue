# Introduction
Fuel-Queue is a queue system for FuelPHP's Oil Module. It is inspired by the fuel-queue package from Kavinsky (https://github.com/kavinsky/fuel-queue) and (https://bitbucket.org/codeyellow/queue).

# Features
* Simple usage, easy to integrate in your project.
* Different queues in parallel that can be switched on and off.
* Executed any static function, with arguments.
* Set priorities for the job.
* Delayed execution.
* Detailed error messages.
* Status panel showing all the jobs.


# Installation
Make a new file in fuel/app/tasks with the name queue.php on your application with the follow code:

```php
<?php
include_once VENDORPATH . 'codeyellow' . DS . 'queue' . DS . 'tasks' . DS . 'queue.php';
```
Run migrations from the command line:

```
php oil r migrate --all
```

Now try on the command line:

```
php oil refine queue
```

## Status panel
The Queue package provides a status panel which shows the progress of the different queues. To install this:

* Create a controller which extends the \Queue\Overview controller. E.g.:

```php
<?php
class Controller_Queue extends \Queue\Overview_Controller { }
```

* Create a view in app/views with the same base URL as the controller. In this view paste the following code where you want to display the overview:

```php
<?php
\Queue\Overview_Viewer::display($options);
```

# Usage

## Queues

There are different queues to be executed. A queue can be created with:

```php
<?php
$queue = new \CodeYellow\Queue\Queue;
$queue->create(string $name, $max_item_hour = 0);

// Other Queue functions:
$queue->load(string $name); // Will load a queue from name
$queue->load(int $id);  // Will load a queue from id
$queue->startQueue();   // Will start the queue
$queue->stopQueue();    // Will stop the queue
$queue->deleteQueue();  // Will delete the queue
```

Or if implicit queue creation is enabled with:
```php
$queue = new \CodeYellow\Queue\Queue(string $name);
```

## Jobs

A job can be created easily be created from the constructor

Priority options:
- \CodeYellow\Queue\Job::PRIORITY_VERYLOW
- \CodeYellow\Queue\Job::PRIORITY_LOW
- \CodeYellow\Queue\Job::PRIORITY_MEDIUM
- \CodeYellow\Queue\Job::PRIORITY_HIGH
- \CodeYellow\Queue\Job::PRIORITY_VERYHIGH
- \CodeYellow\Queue\Job::PRIORITY_SYSTEM

*executeAfter* option needs a timestamp.
```php
$executeAfter = strtotime('now +5 minutes');
```

```php
<?php
$job = new \CodeYellow\Queue\Job(string $class, string $method, array $args, int $queueId, $priority, $executeAfter = 0);
```

## Errors

When an exception occurs while a job is processed, the error is logged. The error for a job can easily be retrieved:

```php
<?php
$error = new \CodeYellow\Queue\Error;
$message = $error->getErrorByJobId(int $jobId);
```
$message will now consists of the error message of the job, or null no error has occurred for the job.

## Running a job
Assuming that a Queue named default is made, the minimal code to run a job is:

```
$queue = new \CodeYellow\Queue\Queue('default');
$queue->startQueue(); // not needed if you set "implicit_queue_creation" to true and "implicit_queue_start_automatically" is set to true in the config file

$job = new \CodeYellow\Queue\Job(
    'Controller_Test',
    'test_function',
    array('argument1'),
    $queue->getId(),
    Job::PRIORITY_MEDIUM,
    strtotime('now + 5 minutes')
);
```

# Flow Chart
Beneath is the flow chart of the major use cases:

![Alt text](http://codeyellow.nl/images/queueFlowDiagram.svg)
