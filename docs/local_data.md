#Local Data

Local data provides a mechanism to extend base-entities by attributes which are not part of the entities default set of attributes. Local data can be added in custom base-entity (post-)event subscribers.

## Local Data requests

Local data can be requested using the `inlucde` parameter provided by base-entity GET operations by default. The format is the following:

```php
include=<ResourceName>.<attributeName>,...
```

It is a comma-separated list of 0 ... n `<ResourceName>.<attributeName>` pairs. Note that `ResourceName` is the `shortName` defined in the `ApiResource` annotation of an entity. The backend will return an error if
* The format of the `include` parameter is invalid
* Any of the requested attributes could not be provided
* The backend tries to set an attribute which was not requested

##Adding local data attributes

Integraters have to make sure that local attributes requested by their client applications are added in the backend. This can be done in custom base-entity event subscribers.

```php
class BaseEntityEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BaseEntityPostEvent::NAME => 'onPost',
         ];
    }

    public function onPost(BaseEntityPostEvent $event)
    {
        $data = $event->getBaseEntityData();
        if ($event->isLocalDataAttributeRequested('foo')) {
            $event->setLocalDataAttribute('foo', $data->getFoo());
        }
    }
}
```

##Creating Local Data aware Entities

You can easily add Local Data to your Entity (`MyEntity`) by:

* Using the `LocalDataAwareTrait` in `MyEntity`
* Implementing the `LocalDataAwareInterface` in `MyEntity`
* Adding the `LocalData:output` group to the normalization context of `MyEntity`
* Adding an event dispatcher of type `LocalDataAwareEventDispatcher` to your Entity provider
* On GET-requests, passing the value of the `include` parameter to the event dispatcher
```php
$this->eventDispatcher->initRequestedLocalDataAttributes($includeParameter);
```
* Creating a (post-)event `MyEntityPostEvent` extending the `LocalDataAwareEvent`, which you pass to the event dispatcher once your Entity provider is done setting up a new instance of `MyEntity`:
```php
// get some data
$myEntityData = $externalApi->getEntityData($identifier);
$myEntity = new MyEntity();
// first, set the default attributes:
$myEntity->setIdentifier($myEntityData->getIdentifier());
$myEntity->setName($myEntityData->getName());
// now, for custom attributes:
$postEvent = new MyEntityPostEvent($myEntity, $myEntityData);
$this->eventDispatcher->dispatch($postEvent);
```