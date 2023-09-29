1. Increment version following SemVer in README.md and src/pcon.php
2. Update versions in phpcs.xml and test/full.sh if needed
    - reference [supported PHP versions](https://www.php.net/supported-versions.php) and decide what to support.
3. Run ./test/full.sh and review output for issues
    - Be sure to update test/dist/test-readme.md with the new hash as instructed
4. Run ./lint.sh and fix any issues
5. Run ./doc.sh
6. Merge & push to master
7. Run ./test/usage_update.sh and review output for issues with update for version packaged and deployed during previous steps.
    - Optionally re-run ./test/full.sh entirely for a full safety test
