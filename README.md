<p align="center">
  <img src="https://seviaflow.io/assets/img/seviaflow-logo.png" alt="SeviaFlow" width="240">
</p>

<h1 align="center">SeviaFlow DevTools</h1>

<p align="center"><strong>Define your data once. Operate it everywhere.</strong></p>

<p align="center">Discover Eloquent models, generate SeviaFlow Resources, and inspect your application setup with focused Artisan development tools.</p>

<p align="center">
  <a href="https://seviaflow.io/">Website</a> ·
  <a href="https://seviaflow.io/docs/0.1/why-seviaflow">What is SeviaFlow?</a> ·
  <a href="https://seviaflow.io/docs/0.1/devtools/">Documentation</a> ·
  <a href="https://github.com/seviaflow/devtools">Source</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/status-beta-orange?style=flat-square" alt="Beta release">
  <img src="https://img.shields.io/badge/license-MIT-2563eb?style=flat-square" alt="MIT License">
</p>

<p align="center"><strong>Composer package:</strong> <code>seviaflow/devtools</code></p>

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

## Installation

This package is in beta and is licensed under the [MIT License](LICENSE). You may use, modify and redistribute it with the license notice. Beta APIs and compatibility may change before a stable release.

Install DevTools as a `require-dev` dependency alongside Core or UI. It is part of the free SeviaFlow edition.

Install the beta with Composer:

```bash
composer require --dev seviaflow/devtools:^0.1@beta
```

## Commands

```bash
php artisan seviaflow:doctor
php artisan seviaflow:status
php artisan seviaflow:discover
php artisan seviaflow:make-resource UserResource --model=User
```

## Local Checks

For local development against a Core checkout, register a temporary path repository in your Composer root; do not commit it:

```bash
composer validate --strict
composer config repositories.seviaflow-core '{"type":"path","url":"../core","options":{"symlink":true,"versions":{"seviaflow/core":"0.1.x-dev"}}}'
composer update
composer check
```

## Free edition scope

Core, UI, API, DevTools and Framework are part of the free edition. Copilot is an optional paid module and is excluded. These five packages use MIT; Copilot has a separate license.
