<?php

namespace Thorsten\IndexNowListener\Tests\Service;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Thorsten\IndexNowListener\Service\IndexNowNotifier;

class IndexNowNotifierTest extends TestCase
{
    private string $key = 'test_key';
    private string $keyLocation = 'https://example.com/test_key.txt';

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifySendsCorrectRequest(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $notifier = new IndexNowNotifier(
            $httpClient,
            $this->key,
            $this->keyLocation
        );

        $url = 'https://example.com/page1';
        $expectedPayload = [
            'json' => [
                'host' => 'example.com',
                'key' => $this->key,
                'keyLocation' => $this->keyLocation,
                'urlList' => [$url],
            ],
        ];

        $response = $this->createStub(ResponseInterface::class);

        $httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.bing.com/indexnow', $expectedPayload)
            ->willReturn($response);

        $notifier->notify([$url]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifyWithCustomSearchEngine(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $customSearchEngine = 'https://api.indexnow.org/indexnow';

        $notifier = new IndexNowNotifier(
            $httpClient,
            $this->key,
            $this->keyLocation,
            $customSearchEngine
        );

        $url = 'https://example.com/page1';
        $response = $this->createStub(ResponseInterface::class);

        $httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $customSearchEngine, $this->anything())
            ->willReturn($response);

        $notifier->notify([$url]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifyThrowsExceptionOnInvalidUrl(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $notifier = new IndexNowNotifier(
            $httpClient,
            $this->key,
            $this->keyLocation
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL "invalid-url" does not contain a valid host.');

        $notifier->notify('invalid-url');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testLoadFromEnvVars(): void
    {
        $_ENV['INDEXNOW_KEY'] = 'env_key';
        $_ENV['INDEXNOW_KEY_LOCATION'] = 'https://example.com/env_key.txt';

        try {
            $httpClient = $this->createMock(HttpClientInterface::class);
            $notifier = new IndexNowNotifier($httpClient);

            $url = 'https://example.com/page1';
            $expectedPayload = [
                'json' => [
                    'host' => 'example.com',
                    'key' => 'env_key',
                    'keyLocation' => 'https://example.com/env_key.txt',
                    'urlList' => [$url],
                ],
            ];

            $response = $this->createStub(ResponseInterface::class);

            $httpClient->expects($this->once())
                ->method('request')
                ->with('POST', 'https://www.bing.com/indexnow', $expectedPayload)
                ->willReturn($response);

            $notifier->notify([$url]);
        } finally {
            unset($_ENV['INDEXNOW_KEY'], $_ENV['INDEXNOW_KEY_LOCATION']);
        }
    }

    public function testThrowsExceptionIfKeyMissing(): void
    {
        unset(
            $_ENV['INDEXNOW_KEY'],
            $_ENV['INDEXNOW_KEY_LOCATION'],
            $_SERVER['INDEXNOW_KEY'],
            $_SERVER['INDEXNOW_KEY_LOCATION']
        );

        $httpClient = $this->createStub(HttpClientInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IndexNow key is required.');

        new IndexNowNotifier($httpClient);
    }
}
