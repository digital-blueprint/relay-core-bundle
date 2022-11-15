<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\Locale\Locale;
use Dbp\Relay\CoreBundle\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Pagination\PartialPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class AbstractDataProvider extends AbstractController implements RestrictedDataProviderInterface, ItemDataProviderInterface, CollectionDataProviderInterface
{
    protected const GET_COLLECTION_OPERATION = 1;
    protected const GET_ITEM_OPERATION = 2;

    private const FILTERS_KEY = 'filters';

    /** @var Locale */
    private $locale;

    /**
     * @deprecated Use default constructor
     */
    public function __construct(RequestStack $requestStack) /** @phpstan-ignore-line */
    {
    }

    /**
     * @required
     */
    public function setLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $resourceClass === $this->getResourceClass();
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): PartialPaginator
    {
        $this->onOperationStart(self::GET_COLLECTION_OPERATION);

        $filters = $context[self::FILTERS_KEY] ?? [];

        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        return new PartialPaginator(
            $this->getPage($currentPageNumber, $maxNumItemsPerPage, $filters, $this->getOptions($filters)),
            $currentPageNumber, $maxNumItemsPerPage);
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?object
    {
        $this->onOperationStart(self::GET_ITEM_OPERATION);

        $filters = $context[self::FILTERS_KEY] ?? [];

        return $this->getItemById($id, $this->getOptions($filters));
    }

    /**
     * @throws AccessDeniedException
     */
    protected function onOperationStart(int $operation)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    }

    abstract protected function getResourceClass(): string;

    abstract protected function getItemById($id, array $options = []): object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;

    private function getOptions(array $filters): array
    {
        $options = [];
        $options[Locale::LANGUAGE_OPTION] = $this->locale->getCurrentPrimaryLanguage();
        LocalData::addOptions($options, $filters);

        return $options;
    }
}
