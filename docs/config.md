# Bundle Configuration

Created via `./bin/console config:dump-reference DbpRelayCoreBundle | sed '/^$/d'`

```yaml
# Default configuration for "DbpRelayCoreBundle"
dbp_relay_core:
    # Some string identifying the current build (commit hash)
    build_info:           ~ # Example: deadbeef
    # Some URL identifying the current build (URL to the commit on some git web interface)
    build_info_url:       ~ # Example: 'https://gitlab.example.com/project/-/commit/deadbeef'
    # The title text of the API docs page
    docs_title:           'Relay API Gateway'
    # The description text of the API docs page (supports markdown)
    docs_description:     '*part of the [Digital Blueprint](https://gitlab.tugraz.at/dbp) project*'
    messenger_transport_dsn: '' # Deprecated (Since dbp/relay-core-bundle 0.1.20: Use "queue_dsn" instead.)
    # See https://symfony.com/doc/5.3/messenger.html#redis-transport
    queue_dsn:            '' # Example: 'redis://localhost:6379'
    # https://symfony.com/doc/5.3/components/lock.html
    lock_dsn:             '' # Example: 'redis://redis:6379'
```

## Locking

To handle [locking](https://symfony.com/doc/current/components/lock.html) you need to set above `lock_dsn` config,
for example as `lock_dsn: '%env(LOCK_DSN)%'` with an environment variable `LOCK_DSN` in your `.env` file or by any other means.

For example, you could use [Redis](https://redis.io/) for distributed locking or `semaphore` for local locking.

Example:

```dotenv
# Redis (distributed locking)
LOCK_DSN=redis://redis:6379/

# Semaphore (local locking)
LOCK_DSN=semaphore
```
