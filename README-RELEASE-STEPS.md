# Release Steps for PCon
1. Increment version in [README.md](./README.md) and [src/pcon.php](./src/pcon.php)
1. Update versions in [phpcs.xml](./phpcs.xml) and [test/full.sh](./test/full.sh) if needed
    - reference [supported PHP versions](https://www.php.net/supported-versions.php) and decide what to support.
1. Run `./test/full.sh` and review output for issues
    - Be sure to update [test/dist/test-readme.md](./test/dist/test-readme.md) with the new hash as instructed
1. Run `./lint.sh` and fix any issues
1. Run `./doc.sh`
1. Merge & push to master
1. Run `./test/usage_update.sh` and review output for issues with update for version packaged and deployed during previous steps.
    - Optionally re-run `./test/full.sh` entirely for a full safety test
