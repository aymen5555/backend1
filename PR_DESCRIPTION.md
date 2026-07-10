Summary

This PR adds audit logging for refund/payment/cancel flows across reservations and direct sales, fixes undefined variables, and strengthens tests for refund flows and gerant cross-complexe isolation.

What I changed

- Added `app/Services/AuditService.php` and `app/Models/AuditLog.php` with migration.
- Added audit calls in `ReservationController`, `VenteDirecteController`, and other refund/payment paths.
- Added/updated tests: `ReservationPaymentFlowTest`, `VenteDirecteFlowTest`, `GerantCrossComplexeIsolationTest`.
- Added PHPStan (`phpstan.neon`) and PHPCS (`phpcs.xml`) configs.
- Ran `phpcbf` to auto-fix many PSR-12 issues and committed resulting changes.
- Untracked `.env` from git history (please rotate credentials if present on remote).

Tests

- PHPUnit: 55 tests passing, 0 failures (local run).
- PHPCBF: auto-fixed 248 style issues across 56 files.
- PHPStan: attempted run with Larastan; produced no JSON output in this environment — recommend running in CI or locally for complete diagnostics.

Next steps

1. Rotate any secrets that were committed and ensure `.env` not in repo.
2. Run PHPStan in CI with `composer install --no-dev` and `vendor/bin/phpstan analyse -c phpstan.neon` to collect full errors; triage the top errors.
3. Add CI workflow to run `phpstan`, `phpcs --standard=phpcs.xml`, and `phpunit` on PRs.
4. Triage PHPStan errors and address critical issues in follow-up PRs.

Notes

- I couldn't find `gh` CLI in this environment to open the PR automatically. The branch is pushed: `audit/fix-refunds-and-ventes`.
- If you want, I can prepare a follow-up PR that fixes the highest-priority PHPStan items.
