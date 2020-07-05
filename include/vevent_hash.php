<?php declare(strict_types=1);
    /*
    * This file provides functions to hash a calendar event to find duplicates.
    * It intentionally doesn't compare UID as these might be random.
    */

    use Sabre\VObject;

    function vevent_hash(Sabre\VObject\Component\VEvent $event)
    {
        return hash("sha256",
            (string)$event->SUMMARY .
            (string)$event->DTSTART .
            (string)$event->DTEND .
            (string)$event->LOCATION .
            (string)$event->DESCRIPTION
        );
    }

?>
