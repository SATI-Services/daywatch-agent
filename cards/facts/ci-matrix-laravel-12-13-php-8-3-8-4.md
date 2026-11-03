---
title: CI matrix runs Pest on Laravel 12/13 × PHP 8.3/8.4
tags: [ci, testing]
status: verified 2026-11-03 auto
source: [.github/workflows/tests.yml]
as_of: 5ce2311ca 2026-11-02
---
CI matrix tests PHP 8.3 and 8.4 against Laravel 12 and 13.

Each matrix cell runs `composer require "orchestra/testbench:${{ matrix.testbench }}" --no-update --dev`
to pin testbench to the Laravel line (`^10.0` for Laravel 12, `^11.0` for Laravel 13) before
`composer update` and `vendor/bin/pest`.

**Why:** Testbench constraint varies by Laravel line; the matrix pre-pins it per cell so `composer update`
resolves the correct dependencies.
