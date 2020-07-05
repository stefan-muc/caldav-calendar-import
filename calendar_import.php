<?php

define('TIME_START', time());

use Sabre\VObject;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
include 'vendor/autoload.php';
include 'include/logger.php';

$log = new MyLogger('calendar_import');

include 'config/config.inc.php';
if(!defined('CONFIG_LOADED'))
{
    $formatter = new LineFormatter("%level_name%: %message%\n");
    $streamHandler = new StreamHandler('php://stdout', Logger::WARNING);
    $streamHandler->setFormatter($formatter);
    $log->pushHandler($streamHandler);
    $log->error('Could not find configuration. Please copy "config/config.sample.inc.php" to "config/config.inc.php" and fill out configuration to use this script.');
    exit();
}

include_once('include/caldav-client-v2.php');

if(PHP_SAPI != 'cli') echo '<pre>' ."\n"; // asuming a browser will show output
$log->trace('PHP version ' . phpversion());

$log->trace('Connecting to CalDAV Server ' . $config['CalDAV']['url']);
$cdc = new CalDAVClient($config['CalDAV']['url'] , $config['CalDAV']['username'], $config['CalDAV']['password']);

$cdc->SetDebug($config['loglevel'] < MyLogger::DEBUG);
$details = $cdc->GetCalendarDetails();
$log->debug('Calendar info - displayname: ' . $details->displayname);
$log->debug('Calendar info - getctag: ' . $details->getctag);

$cdc->SetDepth( $depth = '1');
$events = $cdc->GetEvents();

$log->debug('Found ' . count($events) . ' events in CalDAV calendar');
// delete all events in this calendar
foreach($events as $event) {
    $log->trace('Delete event ' . $details->url . $event['href']);
    $cdc->DoDELETERequest($details->url . $event['href']);
}

// https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
/**
 * Generate a random string, using a cryptographically secure
 * pseudorandom number generator (random_int)
 *
 * For PHP 7, random_int is a PHP core function
 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
 *
 * @param int $length      How many characters do we want?
 * @param string $keyspace A string of all possible characters
 *                         to select from
 * @return string
 */
function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}

// read in ics file to calendar
$log->trace('Reading ICS file from ' . $config['ICS']['url']);
try
{
    $vcalendar = VObject\Reader::read(fopen($config['ICS']['url'], 'r'), VObject\Reader::OPTION_FORGIVING);
}
catch (Exception $e)
{
    $log->critical('Loading and parsing of ICS failed: ' . $e->getMessage());
    $log->error('Exiting script now');
    exit();
}

$log->debug('Found ' . count($vcalendar->VEVENT) . ' events in ICS file');

// write calendar to server
foreach($vcalendar->VEVENT as $event) {
    $log->trace('Adding event "' . $event->SUMMARY . '"');
    $cdc->DoPUTRequest($details->url . 'import-' . random_str(21) . ".ics", "BEGIN:VCALENDAR\n" .  $event->serialize() . "END:VCALENDAR", '*');
}

$log->info("Deleted " . count($events) . " events");
$log->info("Created " . count($vcalendar->VEVENT) . " events");
$log->debug("Script ran " . (time() - TIME_START) . " seconds");
if(PHP_SAPI != 'cli') echo '</pre>' ."\n";

?>
