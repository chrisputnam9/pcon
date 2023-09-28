# Release Steps

1. Increment version following SemVer in README.md and src/pcon.php
2. Update phpcs.xml and tests/full.sh - reference [supported PHP versions](https://www.php.net/supported-versions.php) and decide what to support.
3. Run ./tests/full.sh and review output for issues
   - Be sure to update tests/dist/test-readme.md with the new hash as instructed
4. Run ./lint.sh and fix any issues
5. Run ./doc.sh
6. Merge & push to master
7. Run ./tests/usage_update.sh and review output for issues with update for version packaged and deployed during previous steps.
   - Optionally re-run ./tests/full.sh entirely for a full safety test
