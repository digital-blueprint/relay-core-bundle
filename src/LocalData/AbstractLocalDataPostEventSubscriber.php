<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

/**
 * @deprecated Use AbstractLocalDataEventSubscriber
 *
 * Abstract implementation of a configurable local data provider post event subscriber.
 * It is intended to be derived by local data aware entity post event subscribers.
 * A mapping between source attribute and local data attribute,
 * and default values for the attributes can be specified by means of the deriving event subscriber's bundle config.
 * If no default value is specified, an exception is thrown in the case the mapped source attribute is not found.
 */
abstract class AbstractLocalDataPostEventSubscriber extends AbstractLocalDataEventSubscriber
{
    protected static function getSubscribedEventNames(): array
    {
        return [static::getSubscribedEventName()];
    }

    public static function getSubscribedEventName(): string
    {
        throw new \RuntimeException(sprintf('child classes must implement the \'%s\' method', __METHOD__));
    }
}
