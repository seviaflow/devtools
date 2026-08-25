# SeviaFlow DevTools

Composer package: `seviaflow/devtools`

Repository: `https://github.com/seviaflow/devtools`

DevTools owns development-time commands, model/resource discovery, generators, diagnostics and cache/status tooling for SeviaFlow projects.

DevTools depends on Core only. It is intended for `require-dev` usage and does not require UI or API.

## Installation Status

SeviaFlow is pre-release. The `main` branch is aliased as `0.1.x-dev`; the first stable tag is expected to start at `0.1.0`.

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
