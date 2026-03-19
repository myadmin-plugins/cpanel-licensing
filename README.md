# MyAdmin cPanel Licensing Plugin

[![Tests](https://github.com/detain/myadmin-cpanel-licensing/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-cpanel-licensing/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-cpanel-licensing/version)](https://packagist.org/packages/detain/myadmin-cpanel-licensing)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-cpanel-licensing/downloads)](https://packagist.org/packages/detain/myadmin-cpanel-licensing)
[![License](https://poser.pugx.org/detain/myadmin-cpanel-licensing/license)](https://packagist.org/packages/detain/myadmin-cpanel-licensing)

A MyAdmin plugin that integrates cPanel license management into the MyAdmin billing and administration platform. It provides automated provisioning, activation, deactivation, IP changes, and billing reconciliation for cPanel licenses through the cPanel Manage2 API.

## Features

- Activate and deactivate cPanel licenses via the cPanel Manage2 API
- Change the IP address associated with a cPanel license
- Verify license status for a given IP
- Retrieve license data and account lists by IP
- KernelCare and Ksplice addon license management
- Admin view of all active cPanel licenses
- Unbilled license detection and reporting
- Symfony EventDispatcher integration for the MyAdmin plugin system

## Requirements

- PHP >= 5.0
- ext-soap
- symfony/event-dispatcher ^5.0
- detain/cpanel-licensing (cPanel Manage2 API wrapper)

## Installation

Install with Composer:

```sh
composer require detain/myadmin-cpanel-licensing
```

## Usage

The plugin registers itself with the MyAdmin framework through event hooks. It is loaded automatically by the plugin system when installed.

### Plugin Hooks

The plugin listens on the following events:

| Event | Handler | Description |
|---|---|---|
| `licenses.settings` | `getSettings` | Registers cPanel configuration fields |
| `licenses.activate` | `getActivate` | Provisions a new cPanel license |
| `licenses.reactivate` | `getActivate` | Re-provisions an expired license |
| `licenses.deactivate` | `getDeactivate` | Expires a cPanel license |
| `licenses.deactivate_ip` | `getDeactivateIp` | Expires a license by IP |
| `licenses.change_ip` | `getChangeIp` | Moves a license to a new IP |
| `function.requirements` | `getRequirements` | Registers function autoloading |
| `ui.menu` | `getMenu` | Adds admin menu entries |

### Standalone Functions

The package also provides procedural helper functions:

- `activate_cpanel($ip, $package)` - Activate a license for an IP with a given package ID
- `deactivate_cpanel($ip)` - Deactivate a license by IP
- `verify_cpanel($ip)` - Check if a license is active
- `get_cpanel_license_data_by_ip($ip)` - Get full license details
- `get_cpanel_licenses()` - List all licenses on the account
- `get_cpanel_accounts_for_license_ip($ip)` - Get accounts for a licensed IP

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

To generate a coverage report:

```sh
vendor/bin/phpunit --coverage-html coverage/
```

## License

This package is licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) license.
