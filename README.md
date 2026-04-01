# IndexNow Listener for Symfony

## What this package does

This package provides a simple, event-driven integration for the IndexNow protocol in Symfony applications.

It allows you to notify search engines automatically when URLs change — by dispatching a Symfony event instead of handling API calls manually.

---

## Why this exists

Search engines like Bing and Yandex support IndexNow, which allows pushing URL updates directly instead of waiting for crawlers.

In practice, this is often handled in an inconsistent way:

- manual API calls spread across the codebase
- delayed cronjobs
- missing notifications for certain changes

This package centralizes that logic.

Instead of triggering IndexNow manually, you dispatch an event — the notification is handled in one place.

---

## What this is (and what it is not)

**This package is:**
- a small, focused utility for Symfony applications
- event-driven and easy to integrate into existing workflows
- designed to solve one specific problem: notifying IndexNow endpoints

**This package is not:**
- a full SEO framework
- a crawler or indexing system
- a queue or retry system
- a complete search engine integration layer

It is intentionally minimal and does not try to cover every possible use case.

---

## Use Cases

Useful for applications where content changes frequently:

- Blogs / CMS systems
- E-Commerce platforms (products, categories)
- SEO-driven projects
- Headless backends

## Installation

```bash
composer require thorsten/index-now-listener
```

## Configuration

### Environment Variables

```env
INDEXNOW_KEY=your_indexnow_key
INDEXNOW_KEY_LOCATION=https://yourdomain.com/your_indexnow_key.txt
```

### Symfony Service Configuration (optional)

```yaml
# config/services.yaml
Thorsten\IndexNowListener\Service\IndexNowNotifier:
  arguments:
    $key: '%env(INDEXNOW_KEY)%'
    $keyLocation: '%env(INDEXNOW_KEY_LOCATION)%'
    $searchEngine: 'https://www.bing.com/indexnow'
```

## Usage

### Notify a single URL

```php
use Thorsten\IndexNowListener\Event\IndexNowEvent;

$dispatcher->dispatch(new IndexNowEvent('https://yourdomain.com/page'));
```

### Notify multiple URLs

```php
$dispatcher->dispatch(new IndexNowEvent([
    'https://yourdomain.com/page1',
    'https://yourdomain.com/page2',
]));
```

*Note: All URLs must belong to the same host.*

### Real-world Example

```php
public function save(Post $post, EventDispatcherInterface $dispatcher)
{
    // ... persist logic
    $dispatcher->dispatch(new IndexNowEvent(
        'https://yourdomain.com/blog/' . $post->getSlug()
    ));
}
```

## How it works

- **IndexNowEvent**: Holds and validates URLs.
- **IndexNowListener**: Listens to the event and triggers the notifier.
- **IndexNowNotifier**: Sends the HTTP request to the IndexNow API.

## Customization

### Logging
Inject a `LoggerInterface` to track failed requests or invalid URLs.

### Custom Search Engine
Default: `https://www.bing.com/indexnow`. You can override this if needed.

## When to use (and when not)

**Use it if:**
- You care about SEO freshness.
- Your content changes regularly.
- You want a clean, event-driven solution.

**Don’t use it if:**
- Your content rarely changes.
- You don’t need fast indexing.

## Author

**Thorsten Baumann**  
[https://baumann-it-dienstleistungen.de](https://baumann-it-dienstleistungen.de)  
[info@baumann-it-dienstleistungen.de](mailto:info@baumann-it-dienstleistungen.de)

---
This package is intentionally simple: no framework magic, no hidden behavior, just a clean way to integrate IndexNow into Symfony.
