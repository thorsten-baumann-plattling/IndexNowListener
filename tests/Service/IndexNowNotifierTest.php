<?php

namespace Thorsten\IndexNowListener\Tests\Service;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Thorsten\IndexNowListener\Exception\IndexNowNotificationException;
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

        $response = $this->createMock(ResponseInterface::class);

        $httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.bing.com/indexnow', $expectedPayload)
            ->willReturn($response);

        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(202);

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
        $response = $this->createMock(ResponseInterface::class);

        $httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $customSearchEngine, [
                'json' => [
                    'host' => 'example.com',
                    'key' => $this->key,
                    'keyLocation' => $this->keyLocation,
                    'urlList' => [$url],
                ],
            ])
            ->willReturn($response);

        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

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
        $this->expectExceptionMessage('The URL "invalid-url" is not valid.');

        $notifier->notify('invalid-url');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifyThrowsExceptionOnMixedHosts(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $notifier = new IndexNowNotifier(
            $httpClient,
            $this->key,
            $this->keyLocation
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All URLs must belong to the same host.');

        $notifier->notify([
            'https://example.com/page1',
            'https://other.example/page2',
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifyThrowsExceptionWhenAnyUrlIsInvalid(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $notifier = new IndexNowNotifier(
            $httpClient,
            $this->key,
            $this->keyLocation
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL "not-a-url" is not valid.');

        $notifier->notify([
            'https://example.com/page1',
            'not-a-url',
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifyThrowsDomainExceptionOnNonSuccessResponse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $notifier = new IndexNowNotifier(
            $httpClient,
            $this->key,
            $this->keyLocation,
            IndexNowNotifier::BING_ENDPOINT,
            0,
            0
        );

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(400);
        $response->expects($this->once())
            ->method('getContent')
            ->with(false)
            ->willReturn('Bad request');

        $httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(IndexNowNotificationException::class);
        $this->expectExceptionMessage('IndexNow request failed with HTTP 400. Response: Bad request');

        $notifier->notify('https://example.com/page1');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifyRetriesTransientHttpFailures(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $notifier = new IndexNowNotifier(
            $httpClient,
            $this->key,
            $this->keyLocation,
            IndexNowNotifier::BING_ENDPOINT,
            1,
            0
        );

        $firstResponse = $this->createMock(ResponseInterface::class);
        $firstResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(503);
        $firstResponse->expects($this->once())
            ->method('getContent')
            ->with(false)
            ->willReturn('Try again later');

        $secondResponse = $this->createMock(ResponseInterface::class);
        $secondResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($firstResponse, $secondResponse);

        $notifier->notify('https://example.com/page1');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testNotifyReturnsEarlyOnEmptyUrlList(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $notifier = new IndexNowNotifier(
            $httpClient,
            $this->key,
            $this->keyLocation
        );

        $httpClient->expects($this->never())->method('request');

        $notifier->notify([]);
    }

    public function testThrowsExceptionIfKeyMissing(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IndexNow key is required.');

        new IndexNowNotifier($httpClient, '', $this->keyLocation);
    }

    public function testThrowsExceptionIfKeyLocationMissing(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IndexNow key location is required.');

        new IndexNowNotifier($httpClient, $this->key, '');
    }

    public function testThrowsExceptionIfKeyLocationIsNotAbsoluteUrl(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The key location "/relative/path.txt" must be a valid absolute URL.');

        new IndexNowNotifier($httpClient, $this->key, '/relative/path.txt');
    }
}
