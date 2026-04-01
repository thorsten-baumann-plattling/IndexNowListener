<?php

namespace Thorsten\IndexNowListener\Tests\Event;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Thorsten\IndexNowListener\Event\IndexNowEvent;

class IndexNowEventTest extends TestCase
{
    public function testGetUrls(): void
    {
        $url = 'https://example.com/page1';
        $event = new IndexNowEvent($url);

        $this->assertSame([$url], $event->getUrls());
    }

    public function testMultipleUrls(): void
    {
        $urls = ['https://example.com/page1', 'https://example.com/page2'];
        $event = new IndexNowEvent($urls);

        $this->assertSame($urls, $event->getUrls());
    }

    public function testDuplicateUrlsAreRemoved(): void
    {
        $urls = ['https://example.com/page1', 'https://example.com/page1'];
        $event = new IndexNowEvent($urls);

        $this->assertSame(['https://example.com/page1'], $event->getUrls());
    }

    public function testInvalidUrlThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL "invalid-url" is not valid.');

        new IndexNowEvent('invalid-url');
    }

    public function testDifferentHostsThrowException(): void
    {
        $urls = ['https://example.com/page1', 'https://other.com/page1'];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All URLs must belong to the same host.');

        new IndexNowEvent($urls);
    }
}
