# CalDAV Calendar Import

Import a ``*.ics`` file (provided with WebDAV) into a CalDAV server as calendar events

## Usage

* copy files to destination folder
* run [Composer](https://getcomposer.org/)
* in ``config`` directory
  * rename ``config.sample.inc.php`` to ``config.inc.php`` and adjust according to your needs. Everything in ``$config`` is mandadtory (examples are based on a Nextcloud server, having calendar to update and ``*.ics`` file in same account)
  * maybe set ``$config['loglevel']`` (line 40) to the level of your desire
* call ``https://example.com/calendar_import.php`` and wait for response

**WARNING:** Script will delete content of provided calendar and replace it with events from provided ``*.ics`` file.
First test it on your development server, before going to production!

## Synchronisation

Script supports basic synchronisation: If an event in ``*.ics`` file has the same summary (title), start date, end date, location and description as an event on CalDAV server, it is considered the same and thus not processed any further for performance reasons. Keep in mind and extend this to your needs (see ``include/vevent_hash.php`` for details).

## Performance

Script is thought to be run as cronjob, so currently there are no optimizations regarding performance (everything is done sequentially, even though it could be parallelized).
As every action is done with one HTTP(S) request, whole process is quite slow.
The performance I achieve is roughly 2.6 events per second (delete and create)

## Motivation

I have an ``*.ics`` file which regularly gets updated with events. I want to have read access to these events through a Nextcloud calendar. Import using Nextcloud is possible, but only manually.

Using ownCloud there was a community provided script using some (internal PHP) ownCloud API do do that. This API got removed somewhere in development, and so that automatic import didn't work anymore - but I really wanted to have it.

So I thought a bit about that problem and finally decided, I would write my own script doing that - but this time using the public (and more stable) HTTP(S) API that ownCloud/Nextcloud provides: CalDAV and WebDAV. Positive side effect: there are already libraries out there that one can use.

## Technical Stuff

### Dependencies

* Sabre\VObject (done with [Composer](https://getcomposer.org/))
* ``allow_url_fopen`` enabled (if WebDAV used)

### Tested Versions

* PHP 7.2.13, 7.4.7
* Nextcloud 15.0.4, 15.0.5, 15.0.14

Should be compatible with other CalDAV and WebDAV servers

## Future Development

* validity/error checks and reporting (e.g. don't delete events if ``*.ics`` file can't be found)
* parallelizing the requests for performance (configurable number of workers)

Contributions (code/patches/pull requests) welcome

## Source Mirrors

Sources can be retrieved from these git repositories:

* <https://stefan.git.green-sparklet.de/caldav-calendar-import.git>
* <https://codeberg.org/stefan-muc/caldav-calendar-import.git>
* <https://gitlab.com/stefan-muc/caldav-calendar-import.git>
* <https://github.com/stefan-muc/caldav-calendar-import.git>