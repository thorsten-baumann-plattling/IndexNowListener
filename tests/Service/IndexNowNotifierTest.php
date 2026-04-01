<?php

namespace Thorsten\IndexNowListener\Tests\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Thorsten\IndexNowListener\Service\IndexNowNotifier;

class IndexNowNotifierTest extends TestCase
{
    private MockObject&HttpClientInterface $httpClient;
    private IndexNowNotifier $notifier;
    private string $key = 'test_key';
    private string $keyLocation = 'https://example.com/test_key.txt';

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->notifier = new IndexNowNotifier(
            $this->httpClient,
            $this->key,
            $this->keyLocation
        );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifySendsCorrectRequest(): void
    {
        $url = 'https://example.com/page1';
        $expectedPayload = [
            'json' => [
                'host' => 'example.com',
                'key' => $this->key,
                'keyLocation' => $this->keyLocation,
                'urlList' => [$url],
            ],
        ];

        $response = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.bing.com/indexnow', $expectedPayload)
            ->willReturn($response);

        $this->notifier->notify([$url]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifyWithCustomSearchEngine(): void
    {
        $customSearchEngine = 'https://api.indexnow.org/indexnow';
        $notifier = new IndexNowNotifier(
            $this->httpClient,
            $this->key,
            $this->keyLocation,
            $customSearchEngine
        );

        $url = 'https://example.com/page1';

        $response = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $this->httpClient->expects($this->once())
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL "invalid-url" does not contain a valid host.');

        $this->notifier->notify('invalid-url');
    }

    public function testLoadFromEnvVars(): void
    {
        $_ENV['INDEXNOW_KEY'] = 'env_key';
        $_ENV['INDEXNOW_KEY_LOCATION'] = 'https://example.com/env_key.txt';

        $notifier = new IndexNowNotifier($this->httpClient);

        $url = 'https://example.com/page1';
        $expectedPayload = [
            'json' => [
                'host' => 'example.com',
                'key' => 'env_key',
                'keyLocation' => 'https://example.com/env_key.txt',
                'urlList' => [$url],
            ],
        ];

        $response = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.bing.com/indexnow', $expectedPayload)
            ->willReturn($response);

        $notifier->notify([$url]);

        unset($_ENV['INDEXNOW_KEY'], $_ENV['INDEXNOW_KEY_LOCATION']);
    }

    public function testThrowsExceptionIfKeyMissing(): void
    {
        unset($_ENV['INDEXNOW_KEY'], $_ENV['INDEXNOW_KEY_LOCATION'], $_SERVER['INDEXNOW_KEY'], $_SERVER['INDEXNOW_KEY_LOCATION']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IndexNow key is required.');

        new IndexNowNotifier($this->httpClient);
    }
}
