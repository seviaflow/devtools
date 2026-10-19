# SeviaFlow DevTools

Composer package: `seviaflow/devtools`

Repository: `https://github.com/seviaflow/devtools`

## Beta compatibility

The free beta supports Laravel 12 on PHP 8.2+ and Laravel 13 on PHP 8.3+. Laravel 11 is outside this beta's support scope; its security support ended on March 12, 2026. CI covers these framework targets. Package unit tests use SQLite; the Northstar integration and browser suites use MariaDB 10.11. PostgreSQL is not yet a verified beta target.

DevTools owns development-time commands, model/resource discovery, generators, diagnostics and cache/status tooling for SeviaFlow projects.

DevTools depends on Core only. It is intended for `require-dev` usage and does not require UI or API.

## Discovery Safety

Model discovery reads Eloquent hidden attributes and applies a fail-safe sensitive
column policy. Passwords, remember tokens, 2FA secrets/recovery codes and token
columns are excluded from suggested fields, filters, generated tables, forms and
exports. The generator applies the policy again to caller-supplied suggestions;
discovery therefore cannot make a standard `User` resource expose authentication
secrets by accident.

## Installation Status

SeviaFlow is pre-release. This package is licensed under the [MIT License](LICENSE), which permits commercial use, modification and redistribution with its copyright and license notice. Release tags, distribution endpoints and support scope are documented separately.

The intended use is `require-dev` alongside the Core or UI profile. The owner approved the free Core/UI/API/DevTools/Framework edition on 2026-10-10. MIT usage rights apply to this package; publication, support scope and the first stable tag remain release decisions.

```bash
composer require --dev seviaflow/devtools:^0.1@dev
```

## Commands

```bash
php artisan seviaflow:doctor
php artisan seviaflow:status
php artisan seviaflow:discover
php artisan seviaflow:make-resource UserResource --model=User
```

## Local Checks

When testing this repository before Packagist registration, register a temporary local Core repository in your Composer root instead of committing a path repository:

```bash
composer validate --strict
composer config repositories.seviaflow-core '{"type":"path","url":"../core","options":{"symlink":true,"versions":{"seviaflow/core":"0.1.x-dev"}}}'
composer update
composer check
```

## Free edition scope

Core, UI, API, DevTools and Framework are part of the free edition. Copilot is an optional paid module and is excluded. These five packages use MIT; Copilot has a separate license.
