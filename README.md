# SeviaFlow DevTools

Composer package: `seviaflow/devtools`

Repository: `https://github.com/seviaflow/devtools`

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

SeviaFlow is pre-release and this repository currently declares `license: proprietary`. DevTools does not require a Hub token, API or UI at runtime, but that technical profile is not an open-source grant or proof that public distribution is already authorized. Use an authorized Composer source until the owner publishes the applicable terms.

The intended use is `require-dev` alongside the Core or UI profile. The owner approved the free Core/UI/API/DevTools/Framework edition on 2026-10-10. Usage-license rights, publication, support scope and the first stable tag remain release decisions.

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

Core, UI, API, DevTools and Framework are free. API requires no paid license or runtime Hub activation. Copilot remains an optional paid module and is excluded from the free edition. Price zero does not change the current proprietary usage terms or publish private source repositories.
