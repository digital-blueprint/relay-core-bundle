<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\Tests\AbstractApiTestCase;

class AbstractDataProviderApiTestCase extends AbstractApiTestCase
{
    public function testQueryFilter(): void
    {
        $testResourcePublic = $this->addTestResource(isPublic: true);
        $this->addTestResource(isPublic: false);

        $response = $this->getTestClient()->get('/test/test-resources?filter[isPublic]=true');
        $testResourceArray = $this->getTestResourceCollectionData($response);
        $this->assertCount(1, $testResourceArray);
        $testResource = $testResourceArray[0];
        $this->assertSame($testResourcePublic->getIdentifier(), $testResource['identifier']);
        $this->assertSame($testResourcePublic->getIsPublic(), $testResource['isPublic']);
    }

    public function testForceUsePreparedFilter(): void
    {
        $testResourcePublic = $this->addTestResource(isPublic: true);
        $this->addTestResource(isPublic: false);
        $response = $this->getTestClient()->get('/test/test-resources');
        $testResourceArray = $this->getTestResourceCollectionData($response);
        $this->assertCount(2, $testResourceArray);

        $response = $this->getTestClient()->get('/test/test-resources?preparedFilter=public-only');
        $testResourceArray = $this->getTestResourceCollectionData($response);
        $this->assertCount(1, $testResourceArray);
        $testResource = $testResourceArray[0];
        $this->assertSame($testResourcePublic->getIdentifier(), $testResource['identifier']);
        $this->assertSame($testResourcePublic->getIsPublic(), $testResource['isPublic']);
    }

    public function testPreparedFilter(): void
    {
        $testResourcePublic = $this->addTestResource(isPublic: true);
        $this->addTestResource(isPublic: false);
        $userAttributes = self::USER_ATTRIBUTE_DEFAULT_VALUES;
        $userAttributes['FORCE_USE_PREPARED_FILTER'] = false;
        $response = $this->getTestClient(userAttributes: $userAttributes)->get('/test/test-resources');
        $testResourceArray = $this->getTestResourceCollectionData($response);
        $this->assertCount(2, $testResourceArray);

        $userAttributes['FORCE_USE_PREPARED_FILTER'] = true;
        $response = $this->getTestClient(userAttributes: $userAttributes)->get('/test/test-resources');
        $testResourceArray = $this->getTestResourceCollectionData($response);
        $this->assertCount(1, $testResourceArray);
        $testResource = $testResourceArray[0];
        $this->assertSame($testResourcePublic->getIdentifier(), $testResource['identifier']);
        $this->assertSame($testResourcePublic->getIsPublic(), $testResource['isPublic']);
    }

    public function testPreparedFilterAndForcedFilter(): void
    {
        $testResourcePublic = $this->addTestResource(isPublic: true);
        $this->addTestResource(isPublic: false);
        $userAttributes = self::USER_ATTRIBUTE_DEFAULT_VALUES;
        $userAttributes['FORCE_USE_PREPARED_FILTER'] = true;
        $response = $this->getTestClient(userAttributes: $userAttributes)->get('/test/test-resources?preparedFilter=public-only');
        $testResourceArray = $this->getTestResourceCollectionData($response);
        $this->assertCount(1, $testResourceArray);
        $testResource = $testResourceArray[0];
        $this->assertSame($testResourcePublic->getIdentifier(), $testResource['identifier']);
        $this->assertSame($testResourcePublic->getIsPublic(), $testResource['isPublic']);
    }

    public function testQueryFilterAndPreparedFilter(): void
    {
        $this->addTestResource(isPublic: true);
        $this->addTestResource(isPublic: false);
        $response = $this->getTestClient()->get('/test/test-resources?filter[isPublic]=false&preparedFilter=public-only');
        $testResourceArray = $this->getTestResourceCollectionData($response);
        $this->assertCount(0, $testResourceArray);
    }
}
