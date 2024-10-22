<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi;

use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;

/**
 * @extends AbstractDataProvider<TestResource>
 */
class TestResourceProvider extends AbstractDataProvider
{
    protected function getItemById(string $id, array $filters = [], array $options = []): ?object
    {
        if ($testType = $filters['test_output_groups'] ?? null) {
            $this->setUpOutputGroups($testType);
        }
        $instance = new TestResource();
        $instance->setIdentifier($id);
        $instance->setContent(null);
        $instance->setSecret('secret');

        return $instance;
    }

    protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        return [];
    }

    private function setUpOutputGroups(string $testType): void
    {
        $this->setUpAccessControlPolicies([
            'ROLE_ADMIN' => 'user.get("IS_ADMIN")',
        ], [
            'READ_TEST_RESOURCE' => 'resource.getIdentifier() === user.getIdentifier()',
        ]);

        switch ($testType) {
            case 'class_by_condition':
                $this->showOutputGroupsForEntityClassIf(TestResource::class, ['TestResource:output:admin'],
                    function () {
                        return $this->getUserIdentifier() === 'king_arthur';
                    });
                break;
            case 'class_by_role':
                $this->showOutputGroupsForEntityClassIfGrantedRoles(TestResource::class, ['TestResource:output:admin'], ['ROLE_ADMIN']);
                break;
            case 'class_by_role_and_condition':
                $this->showOutputGroupsForEntityClassIf(TestResource::class, ['TestResource:output:admin'],
                    function () {
                        return $this->getUserIdentifier() === 'king_arthur';
                    });
                $this->showOutputGroupsForEntityClassIfGrantedRoles(TestResource::class, ['TestResource:output:admin'], ['ROLE_ADMIN']);
                break;
            case 'entity_by_resource_permission':
                $this->showOutputGroupsForEntityInstanceIfGrantedResourcePermissions(TestResource::class,
                    ['TestResource:output:admin'], ['READ_TEST_RESOURCE']);
                break;
            case 'entity_by_condition':
                $this->showOutputGroupsForEntityInstanceIf(TestResource::class,
                    ['TestResource:output:admin'], function (object $testResource) {
                        return $testResource->getIdentifier() === 'public';
                    });
                break;
            case 'entity_by_resource_permission_and_condition':
                $this->showOutputGroupsForEntityInstanceIfGrantedResourcePermissions(TestResource::class,
                    ['TestResource:output:admin'], ['READ_TEST_RESOURCE']);
                $this->showOutputGroupsForEntityInstanceIf(TestResource::class,
                    ['TestResource:output:admin'], function (object $testResource) {
                        return $testResource->getIdentifier() === 'public';
                    });
                break;
        }
    }
}
