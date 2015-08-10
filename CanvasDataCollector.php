<?php

/** CanvasDataCollector and related classes */

use TiBeN\CrontabManager\CrontabAdapter as CrontabAdapter;
use TiBeN\CrontabManager\CrontabRepository as CrontabRepository;
use TiBeN\CrontabManager\CrontabJob as CrontabJob;


/**
 * An object-oriented approach to scheduling regular data collection from
 * Canvas.
 *
 * @author Seth Battis <SethBattis@stmarksschool.org>
 **/
abstract class CanvasDataCollector {
	
	/** @var CanvasPest Access to the Canvas API */
	protected $api = null;
	
	/** @var \mysqli MySQL connection */
	protected $sql = null;
	
	/** @var \CrontabRepository	Crontab management */
	private $cron = null;
	
	/** @var \Log Log file */
	protected $log = null;
	
	/**
	 * Construct a new CanvasDataCollector
	 *
	 * @param string $identifier Hopefully unique!
	 * @param CanvasPest $api
	 * @param mysqli $sql
	 * @param string $script Path to data collection script (probably __FILE__)
	 * @param string $schema Path to MySQL schema file
	 * @param string $log Path to log file
	 * @param string|CrontabJob Cron schedule or a complete CrontabJob
	 *
	 * @throws CanvasDataCollector_Exception CONSTRUCTOR_ERROR If parameters are not validated
	 **/
	public function __construct($identifier, $api, $sql, $script, $schema, $log, $schedule) {
		if ($api instanceof CanvasPest) {
			$this->api = $api;
			
			if ($sql instanceof mysqli) {
				$this->sql = $sql;
				
				if (file_exists($script)) {
					/* try to make the identifier truly unique to this instance */
					$_identifier  = $identifier . '.' . md5($identifier . $schema . $log . __FILE__ . __CLASS__);
					
					/* ensure that we're working with a valid Cron job */
					$newJob = null;
					if (is_string($schedule)) {
						$newJob = CrontabJob::createFromCrontabLine($schedule . " php $script");
					} elseif ($schedule instanceof CrontabJob) {
						$newJob = $schedule;
					} else {
						throw new CanvasDataCollector_Exception(
							'Expected a string or CrontabJob, received ' . print_r($schedule, true),
							CanvasDataCollector_Exception::CONSTRUCTOR_ERROR
						);
					}
					$newJob->comments = implode(' ', array($newJob->comments, "[Created by CanvasDataCollector (Job ID $_identifier) " . date('Y-m-d h:ia')  . ']'));
	
					
					/* update cron if this job already exists */
					$this->cron = new CrontabRepository(new CrontabAdapter());
					if (!empty($results = $this->cron->findJobByRegex("/$_identifier/"))) {
						$job = $results[0];
						$job->minutes = $newJob->minutes;
						$job->hours = $newJob->hours;
						$job->dayOfMonth = $newJob->dayOfMonth;
						$job->months = $newJob->months;
						$job->dayOfWeek = $newJob->dayOfWeek;
					
					/* ... or add this as a new job if it doesn't exist */
					} else {
						$this->cron->addJob($newJob);
					}
					
					/* set up logging */
					if (is_string($log)) {
						$this->log = Log::singleton('file', $log);
						if (!($this->log instanceof Log)) {
							throw new CanvasDataCollector_Exception(
								"Invalid log file location '$log'",
								CanvasDataCollector_Exception::CONSTRUCTOR_ERROR
							);
						}
					}
					
					/* load schema into database */
					if (file_exists($schema)) {
						$queries = explode(';', file_get_contents($schema));
						$created = true;
						foreach($queries as $query) {
							if (!empty(trim($query))) {
								if (!$this->sql->query($query)) {
									$this->log->log("MySQL error while trying to create data collection tables: {$this->sql->error}");
								}
							}
						}
					} else {
						throw new CanvasDataCollector_Exception(
							"SQL schema file '$schema' does not exist",
							CanvasDataCollector_Exception::CONSTRUCTOR_ERROR
						);
					}
					
					/* update cron to enable scheduled jobs */
					$this->cron->persist();
					
				} else {
					throw new CanvasDataCollector(
						"PHP script '$script' does not exist",
						CanvasDataCollector_Exception::CONSTRUCTOR_ERROR
					);
				}
			} else {
				throw new CanvasDataCollector_Exception(
					'Expected a mysqli object, received ' . print_r($sql, true),
					CanvasDataCollector_Exception::CONSTRUCTOR_ERROR
				);
			}
		} else {
			throw new CanvasDataCollector_Exception(
				'Expected a CanvasPest object, received ' . print_r($api, true),
				CanvasDataCollector_Exception::CONSTRUCTOR_ERROR
			);
		}
	}
	
	/**
	 * Collect data
	 *
	 * Override this method to collect data. All errors (and other information)
	 * should be output to the log file. Access the API via `$this->api` and MySQL
	 * via `$this->sql`.
	 *
	 ```PHP
	 $this->log->log('log entry');
	 ```
	 *
	 * @return void
	 **/
	public abstract function collectData();
}

/**
 * All exceptions thrown by CanvasDataCollector
 *
 * @author Seth Battis <SethBattis@stmarksschool.org>
 **/
class CanvasDataCollector_Exception extends Exception {

	/** Error constructing CanvasDataCollector */
	const CONSTRUCTOR_ERROR = 1;
	
	/** Error making a MySQL query */
	const MYSQL_ERROR = 2;
}
	
?>