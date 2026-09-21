<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * A minimal Symfony HttpClient ResponseInterface implementation that adapts the
 * in-process results of a KernelBrowser request (an HttpFoundation response and
 * a BrowserKit response) to the HttpClient contract.
 *
 * This exists for the same reason API Platform ships its own test Response: there
 * is no real HTTP transport involved in functional tests (the KernelBrowser calls
 * the kernel directly), so we need an adapter to expose those in-process results
 * through the HttpClient ResponseInterface that tests expect.
 *
 * The underlying HttpFoundation and BrowserKit responses remain accessible via
 * getKernelResponse() and getBrowserKitResponse().
 */
final class ApiTestResponse implements ResponseInterface
{
    /**
     * @var array<string, string[]>
     */
    private readonly array $headers;

    /**
     * @var array<string, mixed>
     */
    private array $info;

    private readonly string $content;

    /**
     * @var array<mixed>|null
     */
    private ?array $jsonData = null;

    /**
     * @param array<string, mixed> $info
     */
    public function __construct(
        private readonly HttpFoundationResponse $kernelResponse,
        private readonly BrowserKitResponse $browserKitResponse,
        array $info = [],
    ) {
        $this->headers = $kernelResponse->headers->all();

        $responseHeaders = [];
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $responseHeaders[] = \sprintf('%s: %s', $name, $value);
            }
        }

        $this->content = $kernelResponse instanceof StreamedResponse
            ? $browserKitResponse->getContent()
            : (string) $kernelResponse->getContent();

        $this->info = [
            'http_code' => $kernelResponse->getStatusCode(),
            'error' => null,
            'response_headers' => $responseHeaders,
        ] + $info;
    }

    public function getStatusCode(): int
    {
        return $this->info['http_code'];
    }

    public function getContent(bool $throw = true): string
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->content;
    }

    public function getHeaders(bool $throw = true): array
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->headers;
    }

    public function toArray(bool $throw = true): array
    {
        if ('' === $content = $this->getContent($throw)) {
            throw new TransportException('Response body is empty.');
        }

        if (null !== $this->jsonData) {
            return $this->jsonData;
        }

        try {
            $data = json_decode($content, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new JsonException($e->getMessage(), $e->getCode());
        }

        if (!\is_array($data)) {
            throw new JsonException(\sprintf('JSON content was expected to decode to an array, %s returned.', get_debug_type($data)));
        }

        return $this->jsonData = $data;
    }

    public function getInfo(?string $type = null): mixed
    {
        return null !== $type ? ($this->info[$type] ?? null) : $this->info;
    }

    public function cancel(): void
    {
        $this->info['error'] = 'Response has been canceled.';
    }

    /**
     * Returns the underlying HttpFoundation (kernel) response.
     */
    public function getKernelResponse(): HttpFoundationResponse
    {
        return $this->kernelResponse;
    }

    /**
     * Returns the underlying BrowserKit response.
     */
    public function getBrowserKitResponse(): BrowserKitResponse
    {
        return $this->browserKitResponse;
    }

    private function checkStatusCode(): void
    {
        $statusCode = $this->info['http_code'];
        if ($statusCode >= 500) {
            throw new ServerException($this);
        }
        if ($statusCode >= 400) {
            throw new ClientException($this);
        }
        if ($statusCode >= 300) {
            throw new RedirectionException($this);
        }
    }
}
