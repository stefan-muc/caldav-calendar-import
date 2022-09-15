# CalDAV Calendar Import

- Delete everything in target caldav
- Pull events from ICS URL
- Filter out events further than 90 days in the past/future
- Remove alarm for remaining events
- Add remaining events to target caldav

## Usage

```sh
docker build -t calsync .
# set up config in config/config.inc.php
docker run -p 80:80 -v $PWD/config:/var/www/html/config calsync
```
