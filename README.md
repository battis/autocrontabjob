# CanvasDataCollector

An object-oriented approach to scheduling regular data collection from Canvas

### Install

Include in your `composer.json`:

```JSON
"require": {
  "smtech/canvasdatacollector": "~1.0"
}
```

### Use

This object requires a bit of prior preparation before use:

  1. Create a MySQL schema file to create the database tables in which you will be storing data.
  2. Extend the abstract `CanvasDataCollector` class and implement the abstract `collectData()` method. Your class has access to the API via `$this->api` and the MySQL connection via `$this->sql`. It is recommended that you log significant errors and events via `$this->log->log()`.
  3. Create a data collection script that will be run regularly as a Cron job, that instantiates your class and calls its `collectData()` method.

For example, here is a sample schema file, named `schema.sql`:

```SQL
CREATE TABLE IF NOT EXISTS `test` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `data` text,
  PRIMARY KEY (`id`)
)
```

And then a sample script file (which is an all-in-one, also extending the abstract class), called `MyDataCollector.php`:

```PHP
<?php

// Composer is awfully handy -- https://getcomposer.org
require_once('vendor/autoload.php');

// extend the abstract class with our own colletData() method
class MyDataCollector extends CanvasDataCollector {
  public function collectData() {
    $response = $this->api->get('users/self/profile');
    $this->sql->query("INSERT INTO `test` (`data`) VALUES ('" . $this->sql->real_escape_string($response) . "')");
    $this->log->log('Data collected.');
  }
}

// instantiate our class
$dataCollector = new MyDataCollector(
  'example',
  new CanvasPest(
    'https://canvas.instructure.com/api/v1', // Canvas API url
    's00perS3kr3tAPIaccessToken' // your Canvas access token
  ),
  new mysqli(
    'localhost', // MySQL server hostname
    'user', // MySQL username
    's00perS3kr3t', // MySQL password
    'db' // MySQL database
  ),
  __FILE__, // *this* file will be called by crontab
  __DIR__ . '/schema.sql', // assuming schema.sql is in the same directory
  __DIR__ . '/data-collector.log' // and a log file in this directory too
);

// fire the collectData() method
$dataCollector->collectData();

?>
```
