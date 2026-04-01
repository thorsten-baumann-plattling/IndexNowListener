<?php

namespace Thorsten\IndexNowListener\Event;

use InvalidArgumentException;
use Symfony\Contracts\EventDispatcher\Event;

class IndexNowEvent extends Event
{
    private readonly array $urls;

    /**
     * @param string[]|string $urls
     */
    public function __construct(
        array|string $urls
    ) {
        $urls = (array) $urls;
        $host = null;

        foreach ($urls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException(sprintf('The URL "%s" is not valid.', $url));
            }

            $currentHost = parse_url($url, PHP_URL_HOST);
            if ($host === null) {
                $host = $currentHost;
            } elseif ($host !== $currentHost) {
                throw new InvalidArgumentException(sprintf('All URLs must belong to the same host. Expecting "%s", but got "%s" from URL "%s".', $host, $currentHost, $url));
            }
        }

        $this->urls = array_values(array_unique($urls));
    }

    /**
     * @return string[]
     */
    public function getUrls(): array
    {
        return $this->urls;
    }
}
