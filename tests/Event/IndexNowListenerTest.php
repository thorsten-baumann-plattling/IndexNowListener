<?php

namespace Thorsten\IndexNowListener\Tests\Event;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Thorsten\IndexNowListener\Event\IndexNowEvent;
use Thorsten\IndexNowListener\Event\IndexNowListener;
use Thorsten\IndexNowListener\Service\IndexNowNotifier;

class IndexNowListenerTest extends TestCase
{
    private MockObject&IndexNowNotifier $notifier;
    private IndexNowListener $listener;

    protected function setUp(): void
    {
        $this->notifier = $this->createMock(IndexNowNotifier::class);
        $this->listener = new IndexNowListener($this->notifier);
    }

    public function testInvokeNotifiesNotifier(): void
    {
        $url = 'https://example.com/page1';
        $event = new IndexNowEvent($url);

        $this->notifier->expects($this->once())
            ->method('notify')
            ->with([$url]);

        ($this->listener)($event);
    }
}
