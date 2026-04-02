<?php

namespace Thorsten\IndexNowListener\Service;

use InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Thorsten\IndexNowListener\Exception\IndexNowNotificationException;

readonly class IndexNowNotifier
{
    public const BING_ENDPOINT = 'https://www.bing.com/indexnow';
    public const YANDEX_ENDPOINT = 'https://yandex.com/indexnow';
    public const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $key,
        private string $keyLocation,
        private string $searchEngine = self::BING_ENDPOINT,
        private int $maxRetries = 2,
        private int $retryDelayMilliseconds = 250
    ) {
        if ($this->key === '') {
            throw new InvalidArgumentException('IndexNow key is required.');
        }

        if ($this->keyLocation === '') {
            throw new InvalidArgumentException('IndexNow key location is required.');
        }

        if (!filter_var($this->keyLocation, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf('The key location "%s" must be a valid absolute URL.', $this->keyLocation));
        }

        if (!filter_var($this->searchEngine, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf('The search engine endpoint "%s" must be a valid absolute URL.', $this->searchEngine));
        }

        if ($this->maxRetries < 0) {
            throw new InvalidArgumentException('The retry count must be greater than or equal to 0.');
        }

        if ($this->retryDelayMilliseconds < 0) {
            throw new InvalidArgumentException('The retry delay must be greater than or equal to 0 milliseconds.');
        }
    }

    /**
     * @param string[]|string $urls
     *
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function notify(array|string $urls): void
    {
        $urls = (array) $urls;
        if ($urls === []) {
            return;
        }

        $host = $this->validateAndExtractHost($urls);
        $payload = [
            'host' => $host,
            'key' => $this->key,
            'keyLocation' => $this->keyLocation,
            'urlList' => $urls,
        ];

        $attempt = 0;

        while (true) {
            try {
                if ($this->sendRequest($payload)) {
                    return;
                }
            } catch (IndexNowNotificationException $exception) {
                if ($attempt >= $this->maxRetries || !$this->shouldRetryStatusCode($exception->getCode())) {
                    throw $exception;
                }
            } catch (TransportExceptionInterface $exception) {
                if ($attempt >= $this->maxRetries) {
                    throw $exception;
                }
            }

            ++$attempt;
            $this->pauseBeforeRetry($attempt);
        }
    }

    /**
     * @param string[] $urls
     */
    private function validateAndExtractHost(array $urls): string
    {
        $host = $this->extractHost($urls[0]);

        foreach ($urls as $url) {
            $currentHost = $this->extractHost($url);
            if ($currentHost !== $host) {
                throw new InvalidArgumentException(sprintf(
                    'All URLs must belong to the same host. Expecting "%s", but got "%s" from URL "%s".',
                    $host,
                    $currentHost,
                    $url
                ));
            }
        }

        return $host;
    }

    /**
     * @param array{host: string, key: string, keyLocation: string, urlList: string[]} $payload
     */
    private function sendRequest(array $payload): bool
    {
        $response = $this->httpClient->request('POST', $this->searchEngine, [
            'json' => $payload,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300) {
            return true;
        }

        throw new IndexNowNotificationException(
            sprintf(
                'IndexNow request failed with HTTP %d. Response: %s',
                $statusCode,
                $this->truncateResponseBody($response->getContent(false))
            ),
            $statusCode
        );
    }

    private function extractHost(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf('The URL "%s" is not valid.', $url));
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw new InvalidArgumentException(sprintf('The URL "%s" does not contain a valid host.', $url));
        }

        return $host;
    }

    private function shouldRetryStatusCode(int $statusCode): bool
    {
        return in_array($statusCode, [429, 500, 502, 503, 504], true);
    }

    private function truncateResponseBody(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '[empty response body]';
        }

        if (strlen($body) <= 200) {
            return $body;
        }

        return substr($body, 0, 197) . '...';
    }

    private function pauseBeforeRetry(int $attempt): void
    {
        if ($this->retryDelayMilliseconds === 0) {
            return;
        }

        usleep($this->retryDelayMilliseconds * $attempt * 1000);
    }
}
