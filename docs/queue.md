# Queued Tasks

The Relay API gateway optionally requires a queuing system, which means tasks
get queued in a central data store and worked on after a request has finished.
The tasks can be processes using one or more workers on multiple machines in
parallel.

This requires two extra deployment related tasks:

1) One or more worker tasks have to be run in the background and automatically
   restarted if they stop.
2) On deployment the worker processes have to be restarted to use the new code.

## Configuration

In the bundle configuration set the `queue_dsn` key to a DSN supported by the
[Symfony messenger component](https://symfony.com/doc/current/messenger.html)

At the moment we only support the redis and doctrine transports:

### Redis

This transport requires the Redis PHP extension (>=4.3) and a running Redis server (^5.0).

```yaml
# config/packages/dbp_relay_core.yaml
dbp_relay_core:
  # redis[s]://[pass@][ip|host|socket[:port]]
  queue_dsn: 'redis://localhost:6379'
```

This creates a redis stream automatically when active.

### Doctrine

In case of doctrine you have to install `symfony/doctrine-messenger`

```bash
composer require symfony/doctrine-messenger
```

then create a doctrine connection and point the `queue_dsn` to that connection:

```yaml
# config/packages/doctrine.yaml
doctrine:
  dbal:
    connections:
      my-queue-connection-name:
        url: 'mysql://db:secret@mariadb:3306/db'
```

```yaml
# config/packages/dbp_relay_core.yaml
dbp_relay_core:
  queue_dsn: 'doctrine://my-queue-connection-name'
```

I will automatically create a new database table when active.

## Run the workers

Start a worker using

```bash
./bin/console dbp:relay:core:queue:work my-worker-01
```

It will automatically exit after a specific amount 0f time or after a specific
number of processed tasks.

Note:

* You need to take care of restarting it automatically.
* Each active worker needs to have a unique name passed as the first argument
  which should stay the same across restarts.


## Restart the workers

After deployment run

```bash
./bin/console dbp:relay:core:queue:restart
```

This will signal the workers to exit after the current task, which means they
will be restarted by supervisor and will run the newly deployed code.


## Manage Workers with Supervisor

Symfony
[recommends](https://symfony.com/doc/current/messenger.html#supervisor-configuration)
to use [Supervisor](http://supervisord.org/) to handle worker jobs and restart them.

```bash
sudo apt-get install supervisor
```

```ini
;/etc/supervisor/conf.d/queue-worker.conf
[program:queue-work]
command=php /path/to/your/app/bin/console dbp:relay:core:queue:work "%(program_name)s_%(process_num)02d"
user=user
numprocs=2
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
```

Change `user` to the Unix user on your server.

## Testing your Setup

After everything is set up you can create a few dummy tasks and see if they get
handled by the workers:

```bash
./bin/console dbp:relay:core:queue:test --count 10 --delay 3
```
