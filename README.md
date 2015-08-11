# AutoCrontabJob

An object-oriented approach to scheduling regular cron jobs automatically

### Install

Include in your `composer.json`:

```JSON
"require": {
  "battis/autocrontabjob": "~1.0"
}
```

### Use

This object requires a bit of prior preparation before use:

  1. Extend the abstract `Battis\AutoCrontabJob` class and implement the abstract `scheduledJob()` method.
  2. Create a script that will be run regularly as a Cron job, that instantiates your class and calls its `scheduledJob()` method.

A sample script file (which is an all-in-one, also extending the abstract class), called `MyJob.php`:

```PHP
<?php

// Composer is awfully handy -- https://getcomposer.org
require_once('vendor/autoload.php');

// extend the abstract class with our own scheduledJob() method
class MyJob extends Battis\AutoCrontabJob {
  public function scheduledJob() {
	  echo 'I did something!';
  }
}

// instantiate our class
$job = new MyJob(
  'example',
  __FILE__, // *this* file will be called by crontab
  '*/5 * * * *' // run every five minutes (woo hoo!)
);

// fire the collectData() method
$job->scheduledJob();

?>
```
