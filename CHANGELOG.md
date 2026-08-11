# Changelog

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
