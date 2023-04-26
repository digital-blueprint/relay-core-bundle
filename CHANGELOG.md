# v0.1.95

* The OpenAPI docs now use their own Keycloak client Javascript module instead of using the one from the configured Keycloak server. This change was required due to Keycloak 22 planning to no longer provide the Javscript module.

# v0.1.82

* extension: add registerLoggingChannel() for registering a new logging channel
* logging: Allow disabling log masking via registerLoggingChannel() for specific channels
* bundle: this bundle now depends on the monolog bundle and requires it to be loaded

# v0.1.76

* cron: The `dbp:relay:core:cron` command will no longer run all jobs the first
  time it is called when the cache is empty.
* cron: The `dbp:relay:core:cron` command gained `--force` option which forces
  it to run all jobs, independent of their schedule.
* cron: There is a new `dbp:relay:core:cron:list` command which lists all
  registered cron jobs and related meta data.

# v0.1.75

* The logging context now includes the active symfony route name

# v0.1.59

* api-docs: compatibility fixes for relay-auth-bundle v0.1.12

# v0.1.52

* new Locale service for setting a locale from a requests and forwarding
  to other services

# v0.1.45

* dbp:relay:core:migrate: Work around issues in DoctrineMigrationsBundle which
  sometimes led to migrations being skipped.

# v0.1.44

* new dbp:relay:core:migrate command
* start of a new authorization framework

# v0.1.43

* Temporarily restrict api-platform/core to < 2.7.0

# v0.1.40

* extension: add registerEntityManager() for registering doctrine entity managers

# v0.1.39

* The core bundle will now set the Symfony locale based on the "Accept-Language" HTTP header.
* Moved the documentation to https://gitlab.tugraz.at/dbp/handbook to avoid
  general documentation being split/duplicated in two places.
