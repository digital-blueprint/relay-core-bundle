# Cron Jobs

The API gateway provides one shared cron command which you should call every few
minutes:

```bash
./bin/console dbp:relay:core:cron
```

For example in crontab, every 5 minutes:

```bash
*/5 * * * * /srv/api/bin/console dbp:relay:core:cron
```

This cron job will regularly prune caches and dispatch a cron event which can be
handled by different bundles.

To get access to such an event you have to implement an event listener:

```yaml
  Dbp\Relay\MyBundle\Cron\CleanupJob:
    tags:
      - { name: kernel.event_listener, event: dbp.relay.cron }
```

The listener gets called with a `CronEvent` object. By calling
`CronEvent::isDue()` and passing an ID for logging and a  [cron
expression](https://en.wikipedia.org/wiki/Cron) you get told when it is time to
run:

```php
class CleanupJob
{
    public function onDbpRelayCron(CronEvent $event)
    {
        if ($event->isDue('mybundle-cleanup', '0 * * * *')) {
            // Do cleanup things here..
        }
    }
}
```
