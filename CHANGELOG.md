# Changelog

## Unreleased

## v0.1.223

- Inject the `EntityNormalizer` into `AbstractAuthorizationService` for testing to avoid null references
- TestClient: offer a method to reset the request caches of a given `AbstractAuthorizationService` service

## v0.1.222

- Restore missing dependency on symfony/asset

## v0.1.221

- api-docs: update vendored bundle, move from rollup to rolldown for bundling
- Drop support for api-platform v3
- Move dependencies to split api-platform packages

## v0.1.220

- Add support for kevinrob/guzzle-cache-middleware v7, drop support for v4

## v0.1.219

- Allow configuration of identifier name for `DataProviderTester` and `DataProcessorTester`
- Remove strict dependency on doctrine migrations bundle

## v0.1.218

- Allow HEAD for CORS requests as well
- Drop workaround for broken CORS headers with Apache <=2.4.47 for 304 responses
  since no one should be using such old Apache versions anymore.

## v0.1.217

- AbstractAuthorizationService: add authorization expressions on setupAccessControlPolicies instead of replacing them 
(allows subclasses of AbstractDataProvider to define authorization expressions without being replaced internally)
- AbstractLocalDataEventSubscriber: map local data attribute paths in filter to their respective source attribute paths
- add FilterTools
- Decorate the 'doctrine.migrations.migrations_factory' service to be able to inject the service container into doctrine
migration subclasses. 
- Add AbstractEntityManagerMigration which gets injected the service container and is meant to be subclassed by doctrine migrations versions

## v0.1.215

- Rework user attribute provision: Change UserAttributeProviderInterface from "get-all-available-attributes" to "has-get-attribute"

## v0.1.214

- Deprecate QueryHelper::getEntities and add  QueryHelper::getEntitiesPageNumberBased and QueryHelper::getEntitiesPageStartIndexBased and 

## v0.1.213

- rest/query/filters: validate filter operators and values on construction of ConditionNode

## v0.1.212

- deprecate Pagination::getAllResults and introduce Pagination::getAllResultsPageNumberBased and Pagination::getAllResultsPageStartIndexBased

## v0.1.211

- Fix an internal error when requesting /errors/XXX

## v0.1.210

- support both api-platform 3 and 4

## v0.1.209

- add MigratePostEvent that is fired after a successful DB migration
- support both api-platform 3 and 4

## v0.1.208

- Add support for Api-Platform v4

## v0.1.207

- filter query parameter syntax: require string values to be quoted with double quotes to be able to
distinguish between strings and numeric/boolean values and be type safe
- force use filters (where `force_use_policy` evaluates to `true`) for get item operations as well,
for get item and get collection to be consistent. NOTE: implementors are responsible to apply those filters
in their overrides of `getItemById` (filters are found in the `$options` parameter, like for `getPage`).

## v0.1.206

- Drop support for PHP 8.1
- Drop support for Symfony 5
- Drop support for api-platform 3.2/3.3

## v0.1.205

- Add Rest/Tools: Centralize the fix for multipart/form-data PATCH requests (where parameters are not present by default)

## v0.1.204

- Remove enforced filters (as introduced in v0.1.200) and update prepared filters such that they now have a `use_policy` (is the current user
allowed to request the filter?) and a `force_use_policy` (is the usage of the filter forced for the current user?) 

## v0.1.203

- Add Rest/Query/Parameters:getBool helper function and tests
- Removed long deprecated GuzzleTools class
- Removed long deprecated Helpers\Locale class

## v0.1.202

- Rest/Query/Filter: automatically simplify the filter tree on filter creation to simplify the filter and remove
constant nodes which might not be supported by the data source system

## v0.1.201

- Rest/Query/Filter: Append a constant 'false' node when an 'inArray' condition with an empty array is appended to a filter tree

## v0.1.200

- Add enforced filters which, when configured, are always applied for collection GET requests and allows
for the restriction of results based on the logger-in user's authorization attributes

## v0.1.199

- Make QueryHelper::addFilter work for JOINS (where there are multiple entity aliases)
- Add preserveItemKeys filter to Pagination::getPage()
- Remove UserAttributeService::getUserAttribute method. Note that the service is only intended to 
deliver the currently logged-in user's attributes

## v0.1.198

- TestUserSession: allow setting up a service account user session for unit tests

## v0.1.197

- cron: print information about the cron jobs being run and their status to
  stdout instead of just logging them.
- The UserSessionInterface set up via UserAuthWebTrait and UserAuthTrait now
  behaves like a real user session in more cases, fixing various
  inconsistencies.
- Drop support for psalm

## v0.1.196

- Add method isGetRequest to AbstractStateProvider and isRootGetRequest to StateTrait
- Refactor tests

## v0.1.195

- Add Doctrine/QueryHelper utility class
- Update test utils

## v0.1.194

- Add DoctrineConfiguration to help bundles with the Doctrine setup
- Add test utilities (like the TestEntityManager) to help bundles to test using an in-memory database 

## v0.1.193

- Add support for PHP 8.4
- Fix EntityNormalizer to work with entity relations (child entities)
- The deprecated bundle config key "messenger_transport_dsn" has been removed. Use "queue_dsn" instead.
- Fix install scripts failing in some cases where a worker queue was required but
  none was configured. Instead fail in the health checks only, in all cases.

## v0.1.192

- Rename to AuthorizationConfigDefinition::addResourcePermission
- Expose test token constant

## v0.1.191

- Update the vendored JS bundle containing the API docs login component. Most importantly
  this updates the Keycloak JS adapter to the latest version.

## v0.1.190

- Divide access control policies into 'roles' (resource dependent) and 'resource permissions' (resource dependent),
  deprecating 'policies', which can be both. This helps to do things more intelligently and efficiently,
  since 'roles' can be evaluated per class, as opposed 'resource permissions' which are evaluated per resource instance.
- Add support for showing additional output (normalization) groups depending on
  roles/resource permissions/arbitrary conditions

## v0.1.189

- Work around Symfony doctrine annotation detection getting a false positive in this bundle.
  It would try to load the annotations driver in that case, which no longer exists in newer
  doctrine.

## v0.1.188

- Remove conflict with doctrine/dbal v4, now that all dependencies are compatible with it

## v0.1.187

- Restore default ErrorProvider behavior which leaves exceptions that already are api-platform resources untouched 

## v0.1.186

- hide 'description' attribute when serialization API errors
- Restore the ApiError API from v0.1.182
- Fix ApiError not serializing the description in debug mode for 5xx errors

## v0.1.185

- inject custom ErrorProvider to continue sending 'hydra:' error attributes which were removed in api-platfrom 3.4.2

## v0.1.183

- make ApiError an api-platform ErrorResource, remove the custom ApiError normalizer, and set rfc_7807_compliant_errors to true
  (backwards compatibility flag which will be removed in api-platform 4)
- Dropped support for api-platform v2. v3.2 is now the oldest supported version.

## v0.1.182

- Fix filters (query parameters) not being passed to implementors of AbstractDataProcessor
- Add CustomControllerTrait to facilitate custom controller to require authentication

## v0.1.181

- Fix fetching of user attributes in an unauthorized context.

## v0.1.180

- UserSessionInterface gained a new isServiceAccount() method to allow for
  distinguishing between user and service accounts.
- UserSessionInterface gained a new getSessionCacheTTL() which is the same as
  the old getSessionTTL() method, but with a more descriptive name.

Note that the OIDC connector needs to be updated to be compatible with this new
version.

## v0.1.179

- Ensure that for ApiError the errorDetails property is always serialized as an object
- For ApiError make sure that either both errorId and errorDetails are set or none of them
  when serialized as JSON.

## v0.1.178

- Remove dependency on symfony/contracts
- Port to PHPUnit 10

## v0.1.177

- add support for sort query parameter for GET collection requests (for extenders of AbstractDataProvider)
- allow enabling/disabling filter/prepared filter/sort via AbstractDataProvider config

## v0.1.175

- The "dbp/relay-auth-bundle" has been replaced by the "dbp/relay-core-connector-oidc-bundle".
  Check out the https://github.com/digital-blueprint/relay-core-connector-oidc-bundle/blob/main/CHANGELOG.md
  for a migration guide.
- composer: Add a conflict with the old "dbp/relay-auth-bundle".

## v0.1.174

- modernize code
- add Filter:mapConditionNodes method to be able to replace certain condition nodes (e.g. for local data mapping)

## v0.1.173

- Enhance test utilities
- Add unit tests for DataProviderTester and DataProcessorTester
- Allow setup with custom user identifier and attributes for DataProviderTester and DataProcessorTester

## v0.1.172

- AbstractDataProvider: Do not require AbstractDataProvider::isUserGrantedOperationAccess to be overridden anymore.
Instead, assume that all operations require the user to be authenticated, throwing 401 otherwise. 
This behavior can be overridden using AbstractDataProvider::requiresAuthentication. Introduce a new method 
AbstractDataProvider::isCurrentUserGrantedOperationAccess that can be overridden to forbid access to certain operations
(throwing 403)
- add TestClient to TestUtils, which can be used for internal API testing

## v0.1.171

- Update code style
- TestAuthenticator: Return 401 instead of 403 on authentication failure

## v0.1.169

- replace deprecate ContextAwareNormalizeInterface by NormalizedInterface

## v0.1.168

- Add UserAttributeProviderExInterface to allow for more flexible attribute providers

## v0.1.167

- Improvements to the fix in v0.1.166 for the API docs auto-login

## v0.1.166

- Fix the API docs auto-login with api-platform v3.3.6+

## v0.1.165

- Update the vendored JS bundle containing the API docs login component. Most importantly
  this updates the Keycloak JS adapter to the latest version.

## v0.1.164

- Remove remaining internal uses of Doctrine annotations and related dependencies

## v0.1.163

- Make TestUserSession public again (it had users after all)

## v0.1.162

- facilitate usage of DataProvider/DataProcessorTester

## v0.1.161

- add support for GET item operation without (or constant) identifier to AbstractDataProvider 

## v0.1.160

- add isPolicy/AttributeDefined and getPolicy/AttributeNames access functions to AbstractAuthorizationService

## v0.1.159

- The core bundle now configures Symfony to log PHP errors in all cases, not just
  when the environment is "dev". In addition the logging levels of each PHP error
  is synced with the improved Symfony 7 defaults.
- The worker queue now configures a failure transport by default in all cases. This
  means after a message failed multiple times it will not be thrown out but moved
  to the failure transport. The failure transport uses the same connection as the
  main transport so no configuration change is needed.

## v0.1.158

- Fix various Symfony/api-platform deprecation warnings

## v0.1.157

- Add support for monolog v3

## v0.1.156

- Fix a regression from v0.1.154 where UserAuthTrait (this is used in unit tests
  only) would log the user in before the authentication.

## v0.1.155

- Restore support for api-platform 3.2

## v0.1.154

- Remove support for api-platform 3.2 for now as some regressions have surfaced.
- Add regression tests to avoid similar issues in the future.

## v0.1.153

- Add preliminary support for api-platform 3.2

## v0.1.152

- Conflict with carbonphp/carbon-doctrine-types v3 to work around composer issues

## v0.1.151

- Conflict with doctrine/dbal v4 to work around composer issues

## v0.1.150

- Set a timeout of 20min for database migrations, because some migrations take a long time

## v0.1.149

- Add conflict for broken api-platform release

## v0.1.148

- Remove dependency on symfony/redis-messenger and ext-redis

## v0.1.147

- Add support for Symfony 6

## v0.1.146

- Restrict to monolog/monolog v2
- dev: replace abandoned composer-git-hooks with captainhook.
  Run `vendor/bin/captainhook install -f` to replace the old hooks with the new ones
  on an existing checkout.

## v0.1.145

- Various cleanups to remove deprecation warnings with Symfony 6

## v0.1.144

- Fix various Symfony 6 deprecation warnings

## v0.1.142

- composer: allow psr/http-message v1 again, to make rdepend updates easier

## v0.1.141

- Drop support for PHP 7.4/8.0

## v0.1.140

- Update to psalm v5

## v0.1.135

- Removed legacy API-Platform API: Dbp\Relay\CoreBundle\DataProvider\AbstractDataProvider
  Use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider instead.

## v0.1.131

- Drop support for PHP 7.3

## v0.1.128

- health-checks: show a summary of all failed checks at the end
- Support kevinrob/guzzle-cache-middleware v5

## v0.1.125

- ExtensionTrait::addPathToHide() now optionally allows passing a method, for hiding
  things other then GET.

## v0.1.123

- Fix WholeResultPaginator::getItems(), it would return all items instead of the page
- AuthorizationConfigDefinition now sets all default policies automatically even if the
  bundle documentation contains no authorization section.

## v0.1.106

- Deprecate AbstractDataProvider

## v0.1.105

- Implement a workaround for Symfony not auto-creating the application cache for
  PDO backends, leading to lots of SQL errors in the logs on cache accesses.
- Add a health check for the application cache.

## v0.1.104

- Use the global "cache.app" adapter for caching instead of always using the filesystem adapter

## v0.1.103

- Sets 'metadata_backward_compatibility_layer' for api-platform to 'false'. This disables some legacy services and
  enables the new metadata system, which is the default in api-platform 3.x.
  See https://api-platform.com/docs/core/upgrade-guide/#the-metadata_backward_compatibility_layer-flag
  for more information in case there are any incompatibilities with your code.

## v0.1.98

- Update and require api-platform 2.7
  See https://api-platform.com/docs/core/upgrade-guide/ for changes and
  how to migrate to the newer systems.
  Note that we don't set metadata_backward_compatibility_layer to false yet,
  so all old interfaces should still work.

## v0.1.95

- The OpenAPI docs now use their own Keycloak client Javascript module instead of using the one from the configured Keycloak server. This change was required due to Keycloak 22 planning to no longer provide the Javscript module.

## v0.1.82

- extension: add registerLoggingChannel() for registering a new logging channel
- logging: Allow disabling log masking via registerLoggingChannel() for specific channels
- bundle: this bundle now depends on the monolog bundle and requires it to be loaded

## v0.1.76

- cron: The `dbp:relay:core:cron` command will no longer run all jobs the first
  time it is called when the cache is empty.
- cron: The `dbp:relay:core:cron` command gained `--force` option which forces
  it to run all jobs, independent of their schedule.
- cron: There is a new `dbp:relay:core:cron:list` command which lists all
  registered cron jobs and related meta data.

## v0.1.75

- The logging context now includes the active symfony route name

## v0.1.59

- api-docs: compatibility fixes for relay-auth-bundle v0.1.12

## v0.1.52

- new Locale service for setting a locale from a requests and forwarding
  to other services

## v0.1.45

- dbp:relay:core:migrate: Work around issues in DoctrineMigrationsBundle which
  sometimes led to migrations being skipped.

## v0.1.44

- new dbp:relay:core:migrate command
- start of a new authorization framework

## v0.1.43

- Temporarily restrict api-platform/core to < 2.7.0

## v0.1.40

- extension: add registerEntityManager() for registering doctrine entity managers

## v0.1.39

- The core bundle will now set the Symfony locale based on the "Accept-Language" HTTP header.
- Moved the documentation to https://gitlab.tugraz.at/dbp/handbook to avoid
  general documentation being split/duplicated in two places.
