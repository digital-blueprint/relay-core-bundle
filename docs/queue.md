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

At the moment we only support the redis transport.

Example:

```yaml
queue_dsn: 'redis://localhost:6379'
```

## Run the workers

Start a worker using

```bash
./bin/console dbp:relay:queue:work my-worker-01
```

It will automatically exit after a specific amount 0f time or after a specific
number of processed tasks.

Note:

* You need to take care of restarting it automatically.
* Each active worker needs to have a unique name passed as the first argument


## Restart the workers

After deployment run

```bash
./bin/console dbp:relay:queue:restart
```

This will signal the workers to exit after the current task, which means they
will be restarted by supervisor and will run the newly deployed code.

Symfony recommends to use [Supervisor](http://supervisord.org/) to do this. You can use
[Supervisor configuration](https://symfony.com/doc/current/messenger.html#supervisor-configuration) to help you with the setup process.