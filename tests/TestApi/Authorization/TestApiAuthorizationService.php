<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Authorization;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestSubResource;

class TestApiAuthorizationService extends AbstractAuthorizationService
{
    protected function setUpInputAndOutputGroups(): void
    {
        $this->showOutputGroupsForEntityClassIf(TestResource::class, ['TestResource:output:admin'],
            function () {
                return $this->getUserIdentifier() === 'king_arthur';
            });
        $this->showOutputGroupsForEntityClassIfGrantedRoles(TestResource::class, ['TestResource:output:admin'], ['ROLE_ADMIN']);
        $this->showOutputGroupsForEntityClassIf(TestResource::class, ['TestResource:output:admin'],
            function () {
                return $this->getUserIdentifier() === 'king_arthur';
            });
        $this->showOutputGroupsForEntityClassIfGrantedRoles(TestResource::class, ['TestResource:output:admin'], ['ROLE_ADMIN']);
        $this->showOutputGroupsForEntityInstanceIfGrantedResourcePermissions(TestResource::class,
            ['TestResource:output:admin'], ['READ_TEST_RESOURCE']);
        $this->showOutputGroupsForEntityInstanceIf(TestResource::class,
            ['TestResource:output:admin'], function (TestResource $testResource) {
                return $testResource->getIsPublic();
            });
        $this->showOutputGroupsForEntityInstanceIfGrantedResourcePermissions(TestResource::class,
            ['TestResource:output:admin'], ['READ_TEST_RESOURCE']);
        $this->showOutputGroupsForEntityInstanceIf(TestResource::class,
            ['TestResource:output:admin'], function (TestResource $testResource) {
                return $testResource->getIsPublic();
            });

        $this->showOutputGroupsForEntityInstanceIf(TestSubResource::class,
            ['TestSubResource:output:admin'], function (TestSubResource $testSubResource) {
                return $testSubResource->getIsPublic();
            });
    }

    public static function getTestConfig(): array
    {
        return [
            'authorization' => [
                'roles' => [
                    'ROLE_ADMIN' => 'user.get("IS_ADMIN")',
                ],
                'resource_permissions' => [
                    'READ_TEST_RESOURCE' => 'relay.str_starts_with(resource.getContent(), "public")',
                ],
            ]];
    }
}
