# Changelog

## 1.1.1 - 2026-08-14

- Fix Composer-installed CLI binaries failing to locate the project autoloader.
- Support both Composer's injected autoloader path and the standard installed
  package layout while preserving standalone checkout and PHAR execution.
- Add regression coverage for execution through `vendor/bin/env-builder`.

## 1.1.0 - 2026-08-11

- Add `--staging` support for `*.env.staging` overlays in the CLI, PHAR, and
  Laravel Artisan command.
- Preserve the existing `--dev` behavior and public PHP call signatures.
- Reject simultaneous development and staging overlays.
- Add PHPUnit, end-to-end, PHP 8.2-8.5, and PHAR verification in CI.

## 1.0.1 - 2026-03-03

- Process `app.env` first and remove superseded duplicate variables.

## 1.0.0 - 2026-03-03

- Initial release.
