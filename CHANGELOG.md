# Changelog

## Version 0.3.x

### Version 0.3.2

* probe CalDAV URL before accessing events - this is better error handling and makes the script more robust

### Version 0.3.1

* make adding of <pre> tags optional via configuration as this might not be perfect for all use cases

### Version 0.3.0

* add basic sync functionality to reduce number of delete+create actions that recreate the same event.

## Version 0.2.x

### Version 0.2.0

* update dependencies (``sabre/vobject`` 4.3 is out)
* add ``Monolog`` for logging
* move configuration to ``config/config.inc.php`` and provide example

## Version 0.1.x

### Version 0.1.2

* add missing ``caldav-client-v2`` and dependencies from *Andrew's Web Libraries v0.54* ([GH#2](https://github.com/stefan-muc/caldav-calendar-import/issues/2))
* move those dependencies from ``vendor`` to ``include``

### Version 0.1.1

* fix missing newlines in debug mode ([GH#3](https://github.com/stefan-muc/caldav-calendar-import/issues/3))
* introduce changelog
* fill out motivation in README.md

### Version 0.1.0

* Initial release
