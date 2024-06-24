# Changelog

## v0.1.167

* Improvements to the fix in v0.1.166 for the API docs auto-login

## v0.1.166

* Fix the API docs auto-login with api-platform v3.3.6+

## v0.1.165

* Update the vendored JS bundle containing the API docs login component. Most importantly
  this updates the Keycloak JS adapter to the latest version.

## v0.1.164

* Remove remaining internal uses of Doctrine annotations and related dependencies

## v0.1.163

* Make TestUserSession public again (it had users after all)

## v0.1.162

* facilitate usage of DataProvider/DataProcessorTester

## v0.1.161

* add support for GET item operation without (or constant) identifier to AbstractDataProvider 

## v0.1.160

* add isPolicy/AttributeDefined and getPolicy/AttributeNames access functions to AbstractAuthorizationService

## v0.1.159

* The core bundle now configures Symfony to log PHP errors in all cases, not just
  when the environment is "dev". In addition the logging levels of each PHP error
  is synced with the improved Symfony 7 defaults.
* The worker queue now configures a failure transport by default in all cases. This
  means after a message failed multiple times it will not be thrown out but moved
  to the failure transport. The failure transport uses the same connection as the
  main transport so no configuration change is needed.

## v0.1.158

* Fix various Symfony/api-platform deprecation warnings

## v0.1.157

* Add support for monolog v3

## v0.1.156

* Fix a regression from v0.1.154 where UserAuthTrait (this is used in unit tests
  only) would log the user in before the authentication.

## v0.1.155

* Restore support for api-platform 3.2

## v0.1.154

* Remove support for api-platform 3.2 for now as some regressions have surfaced.
* Add regression tests to avoid similar issues in the future.

## v0.1.153

* Add preliminary support for api-platform 3.2

## v0.1.152

* Conflict with carbonphp/carbon-doctrine-types v3 to work around composer issues

## v0.1.151

* Conflict with doctrine/dbal v4 to work around composer issues

## v0.1.150

* Set a timeout of 20min for database migrations, because some migrations take a long time

## v0.1.149

* Add conflict for broken api-platform release

## v0.1.148

* Remove dependency on symfony/redis-messenger and ext-redis

## v0.1.147

* Add support for Symfony 6

## v0.1.146

* Restrict to monolog/monolog v2
* dev: replace abandoned composer-git-hooks with captainhook.
  Run `vendor/bin/captainhook install -f` to replace the old hooks with the new ones
  on an existing checkout.

## v0.1.145

* Various cleanups to remove deprecation warnings with Symfony 6

## v0.1.144

* Fix various Symfony 6 deprecation warnings

## v0.1.142

* composer: allow psr/http-message v1 again, to make rdepend updates easier

## v0.1.141

* Drop support for PHP 7.4/8.0

## v0.1.140

* Update to psalm v5

## v0.1.135

* Removed legacy API-Platform API: Dbp\Relay\CoreBundle\DataProvider\AbstractDataProvider
  Use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider instead.

## v0.1.131

* Drop support for PHP 7.3

## v0.1.128

* health-checks: show a summary of all failed checks at the end
* Support kevinrob/guzzle-cache-middleware v5

## v0.1.125

* ExtensionTrait::addPathToHide() now optionally allows passing a method, for hiding
  things other then GET.

## v0.1.123

* Fix WholeResultPaginator::getItems(), it would return all items instead of the page
* AuthorizationConfigDefinition now sets all default policies automatically even if the
  bundle documentation contains no authorization section.

## v0.1.106

* Deprecate AbstractDataProvider

## v0.1.105

* Implement a workaround for Symfony not auto-creating the application cache for
  PDO backends, leading to lots of SQL errors in the logs on cache accesses.
* Add a health check for the application cache.

## v0.1.104

* Use the global "cache.app" adapter for caching instead of always using the filesystem adapter

## v0.1.103

* Sets 'metadata_backward_compatibility_layer' for api-platform to 'false'. This disables some legacy services and
  enables the new metadata system, which is the default in api-platform 3.x.
  See https://api-platform.com/docs/core/upgrade-guide/#the-metadata_backward_compatibility_layer-flag
  for more information in case there are any incompatibilities with your code.

## v0.1.98

* Update and require api-platform 2.7
  See https://api-platform.com/docs/core/upgrade-guide/ for changes and
  how to migrate to the newer systems.
  Note that we don't set metadata_backward_compatibility_layer to false yet,
  so all old interfaces should still work.

## v0.1.95

* The OpenAPI docs now use their own Keycloak client Javascript module instead of using the one from the configured Keycloak server. This change was required due to Keycloak 22 planning to no longer provide the Javscript module.

## v0.1.82

* extension: add registerLoggingChannel() for registering a new logging channel
* logging: Allow disabling log masking via registerLoggingChannel() for specific channels
* bundle: this bundle now depends on the monolog bundle and requires it to be loaded

## v0.1.76

* cron: The `dbp:relay:core:cron` command will no longer run all jobs the first
  time it is called when the cache is empty.
* cron: The `dbp:relay:core:cron` command gained `--force` option which forces
  it to run all jobs, independent of their schedule.
* cron: There is a new `dbp:relay:core:cron:list` command which lists all
  registered cron jobs and related meta data.

## v0.1.75

* The logging context now includes the active symfony route name

## v0.1.59

* api-docs: compatibility fixes for relay-auth-bundle v0.1.12

## v0.1.52

* new Locale service for setting a locale from a requests and forwarding
  to other services

## v0.1.45

* dbp:relay:core:migrate: Work around issues in DoctrineMigrationsBundle which
  sometimes led to migrations being skipped.

## v0.1.44

* new dbp:relay:core:migrate command
* start of a new authorization framework

## v0.1.43

* Temporarily restrict api-platform/core to < 2.7.0

## v0.1.40

* extension: add registerEntityManager() for registering doctrine entity managers

## v0.1.39

* The core bundle will now set the Symfony locale based on the "Accept-Language" HTTP header.
* Moved the documentation to https://gitlab.tugraz.at/dbp/handbook to avoid
  general documentation being split/duplicated in two places.
