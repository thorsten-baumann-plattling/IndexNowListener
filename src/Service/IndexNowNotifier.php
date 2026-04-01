<?php

namespace Thorsten\IndexNowListener\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use InvalidArgumentException;

readonly class IndexNowNotifier
{
    private string $key;
    private string $keyLocation;

    public function __construct(
        private HttpClientInterface $httpClient,
        ?string $key = null,
        ?string $keyLocation = null,
        private string $searchEngine = 'https://www.bing.com/indexnow'
    ) {
        $this->key = $key ?? $_ENV['INDEXNOW_KEY'] ?? $_SERVER['INDEXNOW_KEY'] ?? throw new InvalidArgumentException('IndexNow key is required.');
        $this->keyLocation = $keyLocation ?? $_ENV['INDEXNOW_KEY_LOCATION'] ?? $_SERVER['INDEXNOW_KEY_LOCATION'] ?? throw new InvalidArgumentException('IndexNow key location is required.');
    }

    /**
     * @param string[]|string $urls
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function notify(array|string $urls): void
    {
        $urls = (array) $urls;
        if (empty($urls)) {
            return;
        }

        $host = parse_url($urls[0], PHP_URL_HOST);
        if ($host === null || $host === false) {
            throw new InvalidArgumentException(sprintf('The URL "%s" does not contain a valid host.', $urls[0]));
        }

        $response = $this->httpClient->request('POST', $this->searchEngine, [
            'json' => [
                'host' => $host,
                'key' => $this->key,
                'keyLocation' => $this->keyLocation,
                'urlList' => $urls,
            ],
        ]);

        $response->getStatusCode();
    }
}
