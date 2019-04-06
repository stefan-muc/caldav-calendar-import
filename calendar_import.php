<?php
$debug = false;

if($debug) $time_start = time();

use Sabre\VObject;

include 'vendor/autoload.php';

include_once('vendor/caldav-client-v2.php');

if($debug) echo '<pre>';
if($debug) echo 'PHP version ' . phpversion();

$cal_user = 'CalDAV.Username';
$cal_pass = 'secret_CalDAV_password';
$cal_url = 'https://nextcloud.example.net/remote.php/dav/calendars/' . $cal_user . '/calendar_id/';
$cdc = new CalDAVClient($cal_url, $cal_user, $cal_pass);

$ics_url = 'https://' . $cal_user . ':' . $cal_pass . '@' . 'nextcloud.example.net/remote.php/webdav/path/to/calendar.ics';


//$cdc->SetDebug(true);
$details = $cdc->GetCalendarDetails();
if($debug) print_r($details);

$cdc->SetDepth( $depth = '1');
$events = $cdc->GetEvents();

if($debug) echo "\ndeleting now:\n";
foreach($events as $event) {
	$cdc->DoDELETERequest($details->url . $event['href']);
}

//https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
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
$vcalendar = VObject\Reader::read(fopen($ics_url, 'r'), VObject\Reader::OPTION_FORGIVING);

// write calendar to server
foreach($vcalendar->VEVENT as $curevent) {
	$cdc->DoPUTRequest($details->url . 'import-' . random_str(21) . ".ics", "BEGIN:VCALENDAR\n" .  $curevent->serialize() . "END:VCALENDAR", '*');
}

if($debug) echo "\n";

if($debug) echo "deleted " . count($events) . " events, ";
if($debug) echo "created " . count($vcalendar->VEVENT) . " events, ";
if($debug) echo "took " . (time() - $time_start) . " seconds";
?>