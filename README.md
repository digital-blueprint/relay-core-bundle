# DBP Relay Core Bundle

[GitLab](https://gitlab.tugraz.at/dbp/relay/dbp-relay-core-bundle) | [Packagist](https://packagist.org/packages/dbp/relay-core-bundle)

## Bundle Config

Created via `./bin/console config:dump-reference DbpRelayCoreBundle | sed '/^$/d'`

```yaml
# Default configuration for "DbpRelayCoreBundle"
dbp_relay_core:
    # Some string identifying the current build (commit hash)
    build_info:              ~ # Example: deadbeef
    # Some URL identifying the current build (URL to the commit on some git web interface)
    build_info_url:          ~ # Example: 'https://gitlab.example.com/project/-/commit/deadbeef'
    # The title text of the API docs page
    docs_title:              'Relay API Gateway'
    # The description text of the API docs page (supports markdown)
    docs_description:        '*part of the [Digital Blueprint](https://gitlab.tugraz.at/dbp) project*'
    lock_dsn:                ''
    messenger_transport_dsn: '%env(MESSENGER_TRANSPORT_DSN)'
```

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

For projects that also use the [Symfony Messenger](https://symfony.com/doc/current/components/messenger.html)
you need to set above `messenger_transport_dsn` config, for example as `messenger_transport_dsn: '%env(MESSENGER_TRANSPORT_DSN)%'`
with an environment variable `MESSENGER_TRANSPORT_DSN` in your `.env` file or by any other means.

[Redis](https://redis.io/) is also a way for doing this.

Example:

```dotenv
MESSENGER_TRANSPORT_DSN=redis://redis:6379/local-messages/symfony/consumer?auto_setup=true&serializer=1&stream_max_entries=0&dbindex=0
```
