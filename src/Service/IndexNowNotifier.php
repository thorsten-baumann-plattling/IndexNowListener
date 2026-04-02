<?php

namespace Thorsten\IndexNowListener\Service;

use InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class IndexNowNotifier
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $key,
        private string $keyLocation,
        private string $searchEngine = 'https://www.bing.com/indexnow'
    ) {
        if ($this->key === '') {
            throw new InvalidArgumentException('IndexNow key is required.');
        }

        if ($this->keyLocation === '') {
            throw new InvalidArgumentException('IndexNow key location is required.');
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

        $host = parse_url($urls[0], PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw new InvalidArgumentException(sprintf(
                'The URL "%s" does not contain a valid host.',
                $urls[0]
            ));
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
