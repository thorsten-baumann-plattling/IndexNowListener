<?php

namespace Thorsten\IndexNowListener\Event;

use Thorsten\IndexNowListener\Event\IndexNowEvent;
use Thorsten\IndexNowListener\Service\IndexNowNotifier;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

#[AsEventListener(event: IndexNowEvent::class)]
readonly class IndexNowListener
{
    private LoggerInterface $logger;

    public function __construct(
        private IndexNowNotifier $notifier,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(IndexNowEvent $event): void
    {
        try {
            $this->notifier->notify($event->getUrls());
        } catch (\Throwable $e) {
            $this->logger->error('Failed to notify IndexNow: ' . $e->getMessage(), [
                'exception' => $e,
                'urls' => $event->getUrls(),
            ]);
        }
    }
}
