<?php

namespace ZipkinOpenTracing\Tests\Unit;

use LogicException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class Request implements RequestInterface
{
    /**
     * @var array
     */
    private $headers;

    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $value) {
            $this->headers[strtolower($key)] = [$value];
        }
    }

    /**
     *  @return string[][]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return bool
     */
    public function hasHeader($name): bool
    {
        foreach ($this->headers as $key => $value) {
            if ($key === strtolower($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getHeader($name): array
    {
        foreach ($this->headers as $key => $value) {
            if ($key === strtolower($name)) {
                return $value;
            }
        }

        return [];
    }

    public function withHeader($name, $value): \Psr\Http\Message\MessageInterface
    {
        $this->headers[strtolower($name)] = [$value];
        return $this;
    }

    // These functions are required by the interface but no used during
    // parsing of headers
    //
    // phpcs:disable
    public function withAddedHeader($name, $value): \Psr\Http\Message\MessageInterface
    {
        throw new LogicException('not implemented');
    }

    public function getProtocolVersion(): string
    {
        throw new LogicException('not implemented');
    }

    public function withProtocolVersion($version): \Psr\Http\Message\MessageInterface
    {
        throw new LogicException('not implemented');
    }

    public function getHeaderLine($name): string
    {
        throw new LogicException('not implemented');
    }

    public function withoutHeader($name): \Psr\Http\Message\MessageInterface
    {
        throw new LogicException('not implemented');
    }

    public function getBody(): StreamInterface
    {
        throw new LogicException('not implemented');
    }

    public function withBody(StreamInterface $body): \Psr\Http\Message\MessageInterface
    {
        throw new LogicException('not implemented');
    }

    public function getRequestTarget(): string
    {
        throw new LogicException('not implemented');
    }

    public function withRequestTarget($requestTarget): RequestInterface
    {
        throw new LogicException('not implemented');
    }

    public function getMethod(): string
    {
        throw new LogicException('not implemented');
    }

    public function withMethod($method): RequestInterface
    {
        throw new LogicException('not implemented');
    }

    public function getUri(): UriInterface
    {
        throw new LogicException('not implemented');
    }

    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        throw new LogicException('not implemented');
    }
    // phpcs:enable
}
