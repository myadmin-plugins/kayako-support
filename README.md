# MyAdmin Kayako Support Plugin

[![Build Status](https://github.com/detain/myadmin-kayako-support/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-kayako-support/actions)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-kayako-support/version)](https://packagist.org/packages/detain/myadmin-kayako-support)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-kayako-support/downloads)](https://packagist.org/packages/detain/myadmin-kayako-support)
[![License](https://poser.pugx.org/detain/myadmin-kayako-support/license)](https://packagist.org/packages/detain/myadmin-kayako-support)

A MyAdmin plugin that integrates with the Kayako ticket and helpdesk system. It provides event-driven hooks for API registration, requirement loading, and settings management within the MyAdmin platform. The package exposes SOAP-compatible API functions for creating, listing, viewing, and replying to support tickets through Kayako.

## Features

- Registers ticket management API endpoints (create, list, view, reply)
- Hooks into MyAdmin's event dispatcher for seamless plugin integration
- Manages Kayako connection settings (API URL, key, secret)
- Input validation with descriptive error messages on all API calls
- Pagination support for ticket listing

## Installation

Install with Composer:

```sh
composer require detain/myadmin-kayako-support
```

## Configuration

The plugin uses three configuration constants that should be defined in your MyAdmin environment:

- `KAYAKO_API_URL` - The base URL for the Kayako REST API
- `KAYAKO_API_KEY` - Your Kayako API key
- `KAYAKO_API_SECRET` - Your Kayako API secret

## Usage

The plugin registers itself through MyAdmin's event dispatcher. The `Plugin::getHooks()` method returns the event-to-handler mappings:

```php
use Detain\MyAdminKayako\Plugin;

$hooks = Plugin::getHooks();
// Returns: ['api.register' => ..., 'function.requirements' => ..., 'system.settings' => ...]
```

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

Licensed under the LGPL-2.1. See [LICENSE](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) for details.
