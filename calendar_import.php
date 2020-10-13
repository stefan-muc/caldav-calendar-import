<?php

define('TIME_START', time());

use Sabre\VObject;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
include 'vendor/autoload.php';
include 'include/logger.php';
include 'include/vevent_hash.php';

const DELETE = 0;
const KEEP = 1;
const CREATE = 2;

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

if(PHP_SAPI != 'cli' and $config['autopre']) echo '<pre>' ."\n"; // asuming a browser will show output
$log->trace('PHP version ' . phpversion());

$cdc = new CalDAVClient($config['CalDAV']['url'], $config['CalDAV']['username'], $config['CalDAV']['password']);

$log->trace('Probing CalDAV URL ' . $config['CalDAV']['url']);
if (preg_match('/HTTP\/\d\.\d (\d{3})/', $cdc->DoRequest($config['CalDAV']['url']), $status))
{
    switch (intval($status[1])) {
        case 401:
            $log->error('Username and/or password you provided is wrong. Can\'t access CalDAV URL');
            exit();
        case 404:
            $log->error('Calendar URL not found. Please make sure that your calendar URL is correct - did you use correct calendar ID?');
            exit();
    }
}

$log->trace('Connecting to CalDAV server and fetching info');
$cdc->SetDebug($config['loglevel'] < MyLogger::DEBUG);
$details = $cdc->GetCalendarDetails();
$log->debug('Calendar info - displayname: ' . $details->displayname);
$log->debug('Calendar info - getctag: ' . $details->getctag);

$cdc->SetDepth( $depth = '1');
$events = $cdc->GetEvents();

$log->debug('Found ' . count($events) . ' events in CalDAV calendar');
// delete all events in this calendar

$all_events = array();

// read in all events and set for deletion
foreach($events as $event)
{
    $vevent = VObject\Reader::read($event['data'], VObject\Reader::OPTION_FORGIVING)->VEVENT;
    $hash = vevent_hash($vevent);

    $all_events[$hash]['type'] = DELETE; // set as default - KEEP will be set later
    $all_events[$hash]['url'] = $details->url . $event['href'];
    $all_events[$hash]['vevent'] = $vevent;
}

unset($events);

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

// decide on which events to write to calendar
foreach($vcalendar->VEVENT as $event)
{
    $hash = vevent_hash($event);

    if(isset($all_events[$hash]))
    {
        $all_events[$hash]['type'] = KEEP;
        unset($all_events[$hash]['vevent']); // memory optimisation
    }
    else
    {
        $all_events[$hash]['type'] = CREATE;
        $all_events[$hash]['vevent'] = $event;
    }
}

unset($vcalendar);

$stats[DELETE] = count(array_filter($all_events, function($v) { return $v['type'] == DELETE; }));
$stats[KEEP]   = count(array_filter($all_events, function($v) { return $v['type'] == KEEP;   }));
$stats[CREATE] = count(array_filter($all_events, function($v) { return $v['type'] == CREATE; }));

// execute all changes on server
foreach($all_events as $event)
{
    switch ($event['type']){
        case DELETE:
            $log->trace('Delete event "' . $event['vevent']->SUMMARY . '"');
            $cdc->DoDELETERequest($event['url']);
            break;
        case CREATE:
            $log->trace('Create event "' . $event['vevent']->SUMMARY . '"');
            $cdc->DoPUTRequest($details->url . 'import-' . random_str(21) . ".ics", "BEGIN:VCALENDAR\n" .  $event['vevent']->serialize() . "END:VCALENDAR", '*');
            break;
    }
}

unset($all_events);

$log->info("Deleted " .     $stats[DELETE] . " events");
$log->info("Not touched " . $stats[KEEP]   . " events");
$log->info("Created " .     $stats[CREATE] . " events");
$log->debug("Script ran " . (time() - TIME_START) . " seconds");
if(PHP_SAPI != 'cli' and $config['autopre']) echo '</pre>' ."\n";

?>
