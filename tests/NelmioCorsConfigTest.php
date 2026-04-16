<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\DependencyInjection\DbpRelayCoreExtension;
use PHPUnit\Framework\TestCase;

class NelmioCorsConfigTest extends TestCase
{
    // Shorthand to call the method under test.
    private function build(array $exposeEntries = [], array $allowEntries = []): array
    {
        return DbpRelayCoreExtension::buildNelmioCorsPathsConfig($exposeEntries, $allowEntries);
    }

    public function testDefaultsOnly(): void
    {
        $paths = $this->build();

        // Only the global catch-all path should be present.
        $this->assertCount(1, $paths);
        $this->assertArrayHasKey('^/', $paths);

        $global = $paths['^/'];
        $this->assertContains('Link', $global['expose_headers']);
        $this->assertContains('Content-Type', $global['allow_headers']);
        $this->assertContains('Authorization', $global['allow_headers']);
    }

    public function testGlobalExposeHeader(): void
    {
        $paths = $this->build([['X-My-Header', '/']]);

        $this->assertCount(1, $paths);
        $this->assertContains('X-My-Header', $paths['^/']['expose_headers']);
        // Default headers are still present.
        $this->assertContains('Link', $paths['^/']['expose_headers']);
    }

    public function testGlobalAllowHeader(): void
    {
        $paths = $this->build([], [['X-My-Header', '/']]);

        $this->assertCount(1, $paths);
        $this->assertContains('X-My-Header', $paths['^/']['allow_headers']);
        $this->assertContains('Content-Type', $paths['^/']['allow_headers']);
        $this->assertContains('Authorization', $paths['^/']['allow_headers']);
    }

    public function testPrefixedPathIsEmittedBeforeCatchAll(): void
    {
        $paths = $this->build([['X-Scoped', '/my-bundle']]);

        $this->assertCount(2, $paths);
        $keys = array_keys($paths);
        // Prefix path must come before the catch-all.
        $this->assertSame('^/my\-bundle', $keys[0]);
        $this->assertSame('^/', $keys[1]);
    }

    public function testPrefixEscaping(): void
    {
        // Characters that are special in regex must be escaped.
        // Note: '/' is not a regex metacharacter and is not escaped by preg_quote.
        $paths = $this->build([['X-H', '/api/my.bundle']]);

        $this->assertArrayHasKey('^/api/my\.bundle', $paths);
    }

    public function testPrefixPathInheritsGlobalHeaders(): void
    {
        // A sub-prefix path inherits headers from '/' because '/' is a prefix of every path
        // and its nelmio rule would also match requests under /my-bundle.
        $paths = $this->build(
            [['X-Scoped-Expose', '/my-bundle'], ['X-Global-Expose', '/']],
            [['X-Scoped-Allow', '/my-bundle'], ['X-Global-Allow', '/']]
        );

        $prefixPath = $paths['^/my\-bundle'];

        // Prefix-specific headers are present.
        $this->assertContains('X-Scoped-Expose', $prefixPath['expose_headers']);
        $this->assertContains('X-Scoped-Allow', $prefixPath['allow_headers']);

        // Headers from '/' are inherited since '/' matches all routes.
        $this->assertContains('X-Global-Expose', $prefixPath['expose_headers']);
        $this->assertContains('Link', $prefixPath['expose_headers']);
        $this->assertContains('X-Global-Allow', $prefixPath['allow_headers']);
        $this->assertContains('Content-Type', $prefixPath['allow_headers']);
        $this->assertContains('Authorization', $prefixPath['allow_headers']);
    }

    public function testCatchAllDoesNotInheritSubPrefixHeaders(): void
    {
        // '/' has no parent prefixes, so it only contains headers registered on '/' directly.
        $paths = $this->build([['X-Scoped', '/my-bundle']]);

        $this->assertNotContains('X-Scoped', $paths['^/']['expose_headers']);
    }

    public function testMultipleCallsSamePrefix(): void
    {
        $paths = $this->build(
            [['X-First', '/my-bundle'], ['X-Second', '/my-bundle']],
            [['X-Auth', '/my-bundle']]
        );

        // Still only one prefix path + catch-all.
        $this->assertCount(2, $paths);

        $prefixPath = $paths['^/my\-bundle'];
        $this->assertContains('X-First', $prefixPath['expose_headers']);
        $this->assertContains('X-Second', $prefixPath['expose_headers']);
        $this->assertContains('X-Auth', $prefixPath['allow_headers']);
    }

    public function testNoDuplicateHeaders(): void
    {
        // Same header registered twice globally should appear only once.
        $paths = $this->build([['Link', '/']]);

        $this->assertSame(
            array_unique($paths['^/']['expose_headers']),
            $paths['^/']['expose_headers']
        );
    }

    public function testMultiplePrefixesOrderedLongestFirst(): void
    {
        // Equal-length prefixes can appear in any order relative to each other,
        // but both must come before the catch-all.
        $paths = $this->build(
            [['X-A', '/alpha'], ['X-B', '/beta']]
        );

        $keys = array_keys($paths);
        $this->assertNotSame('^/', $keys[0]);
        $this->assertNotSame('^/', $keys[1]);
        $this->assertSame('^/', $keys[2]);
    }

    public function testSubPrefixEmittedBeforeParentPrefix(): void
    {
        // /foo/bar is more specific than /foo — it must appear first in the nelmio config.
        $paths = $this->build(
            [['X-Foo', '/foo'], ['X-FooBar', '/foo/bar']]
        );

        $keys = array_keys($paths);
        $this->assertSame('^/foo/bar', $keys[0]);
        $this->assertSame('^/foo', $keys[1]);
        $this->assertSame('^/', $keys[2]);
    }

    public function testSubPrefixInheritsParentPrefixHeaders(): void
    {
        // /foo/bar inherits from /foo (and /), because those nelmio rules also match
        // requests to /foo/bar — so /foo/bar must expose at least as many headers.
        $paths = $this->build(
            [['X-Foo', '/foo'], ['X-FooBar', '/foo/bar']],
            [['X-FooAllow', '/foo'], ['X-FooBarAllow', '/foo/bar']]
        );

        $fooBarPath = $paths['^/foo/bar'];

        // Own headers.
        $this->assertContains('X-FooBar', $fooBarPath['expose_headers']);
        $this->assertContains('X-FooBarAllow', $fooBarPath['allow_headers']);

        // Inherited from /foo and /.
        $this->assertContains('X-Foo', $fooBarPath['expose_headers']);
        $this->assertContains('X-FooAllow', $fooBarPath['allow_headers']);
        $this->assertContains('Link', $fooBarPath['expose_headers']);
        $this->assertContains('Content-Type', $fooBarPath['allow_headers']);
    }

    public function testParentPrefixDoesNotInheritSubPrefixHeaders(): void
    {
        // /foo is only matched by its own nelmio rule and '/', not by /foo/bar's rule.
        // So /foo should not inherit headers registered exclusively on /foo/bar.
        $paths = $this->build(
            [['X-Foo', '/foo'], ['X-FooBar', '/foo/bar']]
        );

        $fooPath = $paths['^/foo'];
        $this->assertContains('X-Foo', $fooPath['expose_headers']);
        $this->assertNotContains('X-FooBar', $fooPath['expose_headers']);
    }

    public function testInvalidExposePrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->build([['X-Foo', 'no-leading-slash']]);
    }

    public function testInvalidAllowPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->build([], [['X-Foo', 'no-leading-slash']]);
    }

    public function testUnrelatedPrefixesDoNotShareHeaders(): void
    {
        $paths = $this->build(
            [['X-A', '/bundle-a'], ['X-B', '/bundle-b']]
        );

        $this->assertNotContains('X-B', $paths['^/bundle\-a']['expose_headers']);
        $this->assertNotContains('X-A', $paths['^/bundle\-b']['expose_headers']);
    }
}
