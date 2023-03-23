<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\LocalData\AbstractLocalDataAuthorizationService;
use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\Locale\Locale;
use Dbp\Relay\CoreBundle\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Pagination\PartialPaginator;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

abstract class AbstractDataProvider extends AbstractLocalDataAuthorizationService implements RestrictedDataProviderInterface, ItemDataProviderInterface, CollectionDataProviderInterface
{
    protected const GET_COLLECTION_OPERATION = 1;
    protected const GET_ITEM_OPERATION = 2;

    private const FILTERS_KEY = 'filters';

    /** @var Locale */
    private $locale;

    /** @var Security */
    private $security;

    /**
     * @required
     */
    public function __injectLocaleAndSecurity(Locale $locale, Security $security): void
    {
        $this->locale = $locale;
        $this->security = $security;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $resourceClass === $this->getResourceClass();
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): PartialPaginator
    {
        $this->denyOperationAccessUnlessGranted(self::GET_COLLECTION_OPERATION);

        $filters = $context[self::FILTERS_KEY] ?? [];

        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        return new PartialPaginator(
            $this->getPage($currentPageNumber, $maxNumItemsPerPage, $filters, $this->createOptions($filters)),
            $currentPageNumber, $maxNumItemsPerPage);
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?object
    {
        $this->denyOperationAccessUnlessGranted(self::GET_ITEM_OPERATION);

        $filters = $context[self::FILTERS_KEY] ?? [];

        return $this->getItemById($id, $filters, $this->createOptions($filters));
    }

    public function isUserAuthenticated(): bool
    {
        return $this->security->isGranted('IS_AUTHENTICATED_FULLY');
    }

    abstract protected function getResourceClass(): string;

    abstract protected function isUserGrantedOperationAccess(int $operation): bool;

    abstract protected function getItemById($id, array $filters = [], array $options = []): object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;

    /**
     * @throws ApiError
     */
    private function createOptions(array $filters): array
    {
        $options = [];
        $options[Locale::LANGUAGE_OPTION] = $this->locale->getCurrentPrimaryLanguage();

        LocalData::addOptions($options, $filters);
        $this->denyLocalDataAccessUnlessGranted($options);

        return $options;
    }

    /**
     * @throws AccessDeniedException
     */
    private function denyOperationAccessUnlessGranted(int $operation)
    {
        if (!$this->isUserGrantedOperationAccess($operation)) {
            throw new AccessDeniedException();
        }
    }
}
