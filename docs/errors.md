# API Errors and Error Handling

By default Symfony and API Platform convert `HttpException` and all subclasses
to HTTP errors with a matching status code. See
https://api-platform.com/docs/core/errors for details.

Since API Platform by default hides any message details for >= 500 and < 600 in
production and doesn't allow injecting any extra information into the resulting
JSON-LD error response we provide a special HttpException subclass which
provides those features.

The following will pass the error message to the client even in case the status
code is 5xx. Note that you have to be careful to not include any secrets in the
error message since they would be exposed to the client.

```php
use Dbp\Relay\CoreBundle\Exception\ApiError;

throw new APIError(500, 'My custom message');
```

which results in:

```json
{
  "@context": "/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "My custom message"
}
```

Further more you can include extra information like an error ID and some extra
information in form of an object:

```php
use Dbp\Relay\CoreBundle\Exception\ApiError;

throw new APIError::withDetails(500, 'My custom message', 'my-id', ['foo' => 42]);
```

which results in:

```json
{
  "@context": "/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "My custom message",
  "relay:errorId": "my-id",
  "relay:errorDetails": {
    "foo": 42
  }
```

If you are using status codes <= 400 and are fine with just the message, then
using any of the builtin exception types is fine.
