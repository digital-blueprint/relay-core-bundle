# Configuration

Created via `./bin/console config:dump-reference DbpRelayCoreBundle | sed '/^$/d'`

```yaml
# Default configuration for "DbpRelayCoreBundle"
dbp_relay_core:
    # Some string identifying the current build (commit hash)
    build_info:           ~ # Example: deadbeef
    # Some URL identifying the current build (URL to the commit on some git web interface)
    build_info_url:       ~ # Example: 'https://github.example.com/project/-/commit/deadbeef'
    # Path to the logo (256x256) of the API frontend
    logo_path:            ~ # Example: bundles/dbprelaycore/logo.png
    # The title text of the API docs page
    docs_title:           'Relay API Gateway'
    # The description text of the API docs page (supports markdown)
    docs_description:     '*part of the [digital blueprint](https://www.digital-blueprint.org) project*'
    # See https://symfony.com/doc/5.3/messenger.html#redis-transport
    queue_dsn:            '' # Example: 'redis://redis:6379'
    # https://symfony.com/doc/5.3/components/lock.html
    lock_dsn:             '' # Example: 'redis://redis:6379'
```
