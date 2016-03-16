<?php

require_once('common.inc.php');

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

// fire the collectData() method
$job->scheduledJob();
	
?>