# Kodhe Framework – Modular Packages (PSR-4 + PSR-12)

Setiap package berdiri sendiri. Install hanya yang dibutuhkan.

## Standar yang Diterapkan

- **PSR-4** — Autoload & struktur namespace
- **PSR-12** — Coding style (header, declare strict_types, namespace, use, brace)

### Contoh header file (PSR-12)

```php
<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Middleware;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Throwable;

class CallableMiddleware implements MiddlewareInterface
{
    // ...
}
```

## Namespace (PSR-4)

### Core

| Package | Composer | Namespace root |
|---------|----------|----------------|
| framework | `kodhe/framework` | `Kodhe\Framework\` |
| http | `kodhe/http` | `Kodhe\Framework\Http\` |
| routing | `kodhe/routing` | `Kodhe\Framework\Routing\` |
| database | `kodhe/database` | `Kodhe\Framework\Database\` |
| session | `kodhe/session` | `Kodhe\Framework\Session\` |
| cache | `kodhe/cache` | `Kodhe\Framework\Cache\` |
| view | `kodhe/view` | `Kodhe\Framework\View\` |
| validation | `kodhe/validation` | `Kodhe\Framework\Validation\` |

### Libraries

| Package | Composer | Namespace |
|---------|----------|-----------|
| agent | `kodhe/user-agent` | `Kodhe\UserAgent\` |
| driver | `kodhe/driver` | `Kodhe\Driver\` |
| email | `kodhe/email` | `Kodhe\Email\` |
| upload | `kodhe/upload` | `Kodhe\Upload\` |
| image | `kodhe/image` | `Kodhe\Image\` |
| encryption | `kodhe/encryption` | `Kodhe\Encryption\` |
| encrypt | `kodhe/encrypt` | `Kodhe\Encrypt\` |
| ftp | `kodhe/ftp` | `Kodhe\Ftp\` |
| zip | `kodhe/zip` | `Kodhe\Zip\` |
| cart | `kodhe/cart` | `Kodhe\Cart\` |
| calendar | `kodhe/calendar` | `Kodhe\Calendar\` |
| pagination | `kodhe/pagination` | `Kodhe\Pagination\` |
| parser | `kodhe/parser` | `Kodhe\Parser\` |
| profiler | `kodhe/profiler` | `Kodhe\Profiler\` |
| table | `kodhe/table` | `Kodhe\Table\` |
| typography | `kodhe/typography` | `Kodhe\Typography\` |
| javascript | `kodhe/javascript` | `Kodhe\Javascript\` |
| migration | `kodhe/migration` | `Kodhe\Migration\` |
| test | `kodhe/test` | `Kodhe\Test\` |
| trackback | `kodhe/trackback` | `Kodhe\Trackback\` |
| xmlrpc | `kodhe/xmlrpc` | `Kodhe\Xmlrpc\` |
| xmlrpcs | `kodhe/xmlrpcs` | `Kodhe\Xmlrpcs\` |

## Install Selektif

```bash
# Minimal API
composer require kodhe/framework kodhe/http kodhe/routing kodhe/database

# Full web
composer require kodhe/framework kodhe/http kodhe/routing kodhe/database \
  kodhe/session kodhe/cache kodhe/view kodhe/validation

# Library saja
composer require kodhe/email kodhe/upload kodhe/user-agent
```

### Path repository (lokal)

```json
{
  "repositories": [
    { "type": "path", "url": "packages/*", "options": { "symlink": true } }
  ],
  "require": {
    "kodhe/framework": "*",
    "kodhe/http": "*",
    "kodhe/user-agent": "*"
  }
}
```

## Catatan

- `kodhe/cache` membutuhkan `kodhe/driver`
- `kodhe/framework` tidak memaksa dependency ke http/session/cache (hanya suggest)
- File helper & Legacy tidak dipaksa `strict_types` (kompatibilitas)
- Semua class file memakai `declare(strict_types=1);`

---

## Calendar Library (New!)

Refactored modular PSR-4 compatible Calendar library with:

- **Multiple Renderers**: HTML table, JSON for JavaScript
- **Localization**: English, Indonesian, French, German, Spanish
- **Strategy Pattern**: Easy to add custom renderers
- **Value Objects**: `CalendarDate`, `CalendarEvent`
- **Caching**: Built-in structure caching
- **100% CI3 Backward Compatible**

### Structure

```
src/
├── Calendar.php                    # Main class
├── Contracts/
│   ├── CalendarInterface.php
│   └── CalendarRendererInterface.php
├── Generators/
│   └── MonthGenerator.php
├── Renderers/
│   ├── HtmlTableRenderer.php
│   └── JsonRenderer.php
├── Localization/
│   ├── LocalLexicon.php
│   └── LexiconRepository.php
├── ValueObjects/
│   ├── CalendarDate.php
│   └── CalendarEvent.php
├── Traits/
│   ├── ConfigurableTrait.php
│   └── SingletonTrait.php
└── tests/
    ├── CalendarTest.php
    └── Generators/
        └── MonthGeneratorTest.php
```

### Quick Start

```php
use Kodhe\Calendar\Calendar;

$calendar = new Calendar(['locale' => 'en']);
echo $calendar->generate(2026, 8);

// JSON output for FullCalendar.js
$json = $calendar->asJson(2026, 8, [
    15 => ['title' => 'Meeting', 'url' => '/meeting'],
]);
```

See full documentation in the package README.
