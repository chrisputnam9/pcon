# Release Steps

1. Increment version following SemVer in README.md and src/pcon.php
2. Run ./tests/cleanup.sh && ./tests/usage_package.sh and review output for issues
   - Be sure to update tests/dist/test-readme.md with the new hash as instructed
3. Run ./lint.sh and fix any issues
4. Run ./doc.sh
5. Merge & push to master
6. Run ./tests/usage_update.sh and review output for issues
