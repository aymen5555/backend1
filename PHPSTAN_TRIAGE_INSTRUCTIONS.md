PHPStan triage — next steps

Context:
- CI runs are currently failing during "Set up job" preventing artifact upload.
- Local `vendor/bin/phpstan` runs produced no JSON output in this Windows environment.

Goals:
1. Obtain `phpstan_report.json` from CI run and triage top errors.
2. Fix logic/type issues prioritized by severity, run PHPUnit after each change.

Immediate reproduction steps (CI):
- Ensure the workflow writes the phpstan JSON report (already added in `.github/workflows/ci.yml`).
- If GitHub Actions runners report "Set up job" failures, retry the workflow or rerun from GitHub UI until it completes.
- Once completed, download the artifact named `phpstan-report` from the run and extract `phpstan_report.json`.

Immediate reproduction steps (local):
1. From `backend/` run:

```
# run phpstan and write JSON locally
vendor/bin/phpstan analyse -c phpstan.neon app tests --level=7 --memory-limit=1G --error-format=json > phpstan_report.json || true
```

2. If the command exits without producing `phpstan_report.json`, run with debug to see internal errors:

```
php -d display_errors=1 vendor/bin/phpstan.phar analyse -c phpstan.neon app tests --level=7 --no-progress --debug
```

3. If PHPStan fails silently on Windows but works in CI, run the CI job on `ubuntu-latest` and download the artifact.

Triage steps once `phpstan_report.json` is available:
- Parse the JSON and list errors grouped by `severity` and `file`.
- Prioritize fixes:
  1. Errors pointing to undefined variables or wrong method calls in payment/refund flows.
  2. Type mismatch issues that affect runtime behavior (e.g., passing null where object expected).
  3. Missing imports or wrong class names.
  4. Lower-priority style/type hints.

- Implement fixes in small commits with PHPUnit runs between them.
- Push commits and wait for CI; once phpstan_report.json is regenerated, re-check remaining issues.

If you want, I can continue polling CI for the artifact and attempt to download it repeatedly. Otherwise, tell me to proceed with local fixes I can do without the phpstan report.
