# AutoCrontabJob

[![Latest Version](https://img.shields.io/packagist/v/battis/autocrontabjob.svg)](https://packagist.org/packages/battis/autocrontabjob)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/battis/autocrontabjob/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/battis/autocrontabjob/?branch=master)

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

*Gotcha warning:* Remember that, when a script is run by cron, it is _not_ run by Apache, so it will not generate output to the Apache log files. Handily, there is a log built-in that you can use (see below). By default, the log will be generated in the same directory as the script and be similarly named (`.log` instead of `.php`).

A sample script file (which is an all-in-one, also extending the abstract class), called `MyJob.php`:

```PHP
<?php

require_once('vendor/autoload.php');

// extend the abstract class with our own scheduledJob() method
class MyJob extends Battis\AutoCrontabJob {

	public function scheduledJob() {
		$this->log->log('I did something!');
	}
}

// instantiate our class
$job = new MyJob(
	'example',
	__FILE__, // *this* file will be called by crontab
	'*/5 * * * *' // run every five minutes (woo hoo!)
);

// fire the scheduledJob() method
$job->scheduledJob();

?>
```
