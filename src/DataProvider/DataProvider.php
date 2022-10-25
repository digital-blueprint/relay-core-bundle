<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\CoreBundle\Helpers\Locale;
use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Pagination\PartialPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class DataProvider extends AbstractController implements RestrictedDataProviderInterface, ItemDataProviderInterface, CollectionDataProviderInterface
{
    private const FILTERS_KEY = 'filters';

    /** @var Locale */
    private $locale;

    public function __construct(RequestStack $requestStack)
    {
        $this->locale = new Locale($requestStack);
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $resourceClass === $this->getResourceClass();
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): PartialPaginator
    {
        $filters = $context[self::FILTERS_KEY] ?? [];

        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        $options = [];
        LocalData::addOptions($options, $filters);
        $this->locale->addLanguageOption($options);

        return new PartialPaginator($this->getPage($currentPageNumber, $maxNumItemsPerPage, $filters, $options), $currentPageNumber, $maxNumItemsPerPage);
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?object
    {
        $filters = $context[self::FILTERS_KEY] ?? [];

        $options = [];
        LocalData::addOptions($options, $filters);
        $this->locale->addLanguageOption($options);

        return $this->getItemById($id, $options);
    }

    abstract protected function getResourceClass(): string;

    abstract protected function getItemById($id, array $options = []): object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;
}
