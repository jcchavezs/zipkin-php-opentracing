<?php

namespace ZipkinOpenTracing\Tests\Unit;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class Request implements RequestInterface
{
    /**
     * @var array $headers
     */
    private $headers;

    /**
     * @var array $lowerCaseHeaders
     */
    private $lowerCaseHeaders;

    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
        $keys = \array_keys($headers);
        $this->lowerCaseHeaders = [];
        foreach ($keys as $key) {
            $this->lowerCaseHeaders[strtolower($key)] = $key;
        }
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        $lowerName = strtolower($name);

        return \array_key_exists($lowerName, $this->lowerCaseHeaders);
    }

    public function getHeader($name)
    {
        $lowerName = strtolower($name);

        if (\array_key_exists($lowerName, $this->lowerCaseHeaders)) {
            $index = $this->lowerCaseHeaders[$lowerName];

            return [$this->headers[$index]];
        } else {
            return [];
        }
    }

    public function withHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function withAddedHeader($name, $value)
    {
        $lowerName = strtolower($name);
        if (\array_key_exists($lowerName, $this->lowerCaseHeaders)) {
            $index = $this->lowerCaseHeaders[$lowerName];
            $this->headers[$index] = [$this->headers[$index], $value];
        } else {
            $this->headers[$index] = $value;
        }

        return $this;
    }

    // These functions are required by the interface but no used during
    // parsing of headers
    //
    // phpcs:disable
    public function getProtocolVersion()
    {
    }
    public function withProtocolVersion($version)
    {
    }
    public function getHeaderLine($name)
    {
    }
    public function withoutHeader($name)
    {
    }
    public function getBody()
    {
    }
    public function withBody(StreamInterface $body)
    {
    }
    public function getRequestTarget()
    {
    }
    public function withRequestTarget($requestTarget)
    {
    }
    public function getMethod()
    {
    }
    public function withMethod($method)
    {
    }
    public function getUri()
    {
    }
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
    }
    // phpcs:enable
}
