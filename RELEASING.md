# Releasing

## One-time setup

1. **WordPress.org account.** Create one at https://login.wordpress.org/register. Put the username in `readme.txt` (`Contributors:`) in place of `WPORG_USERNAME`.
2. **GitHub repository.** Create an empty public repository named `kontoros-company-lookup`. Replace `GITHUB_USER` in `readme.txt` with its owner, then push:

   ```bash
   git remote add origin git@github.com:GITHUB_USER/kontoros-company-lookup.git
   git push -u origin main
   ```

3. **Plugin Check.** Run it on a test site with the plugin installed and fix any ERROR lines:

   ```bash
   wp plugin install plugin-check --activate
   wp plugin check kontoros-company-lookup
   ```

4. **First submission (manual).** Build the zip with `php bin/build-zip.php` and upload `build/kontoros-company-lookup-<version>.zip` at https://wordpress.org/plugins/developers/add/. Review takes about 1–10 days, by email.
5. **After approval: GitHub secrets.** In the repository, open Settings → Secrets and variables → Actions and add:
   - `SVN_USERNAME`: your WordPress.org username
   - `SVN_PASSWORD`: the SVN password from your WordPress.org profile (Account & Security). It is not your login password.

   Then push the first tag (`git tag v1.1.0 && git push origin v1.1.0`). The deploy workflow fills SVN trunk, creates `tags/1.1.0`, uploads `.wordpress-org/` as the page graphics, and attaches the zip to a GitHub release.

Until the plugin is approved, the deploy and assets workflows fail because the SVN repository doesn't exist yet. That's expected; only CI needs to pass.

## Every release

1. Update the version in three places, which CI checks match:
   - `kontoros-company-lookup.php`: the `Version:` header and `KONTOROS_CL_VERSION`
   - `readme.txt`: `Stable tag:`, plus a changelog entry
2. If you changed strings: `wp i18n make-pot . languages/kontoros-company-lookup.pot`, then `wp i18n update-po languages/kontoros-company-lookup.pot languages/`, translate, and run `wp i18n make-mo languages`.
3. Commit, then `git tag vX.Y.Z && git push origin main vX.Y.Z`.
4. On kontoros.lt: copy the zip from the GitHub release to `public/downloads/` (versioned name and `kontoros-company-lookup.zip`), set `woo_plugin_version` in `config/site.php`, add a line to the changelog in `resources/views/api/docs.blade.php`, then run `artisan optimize`.

Changes to only `readme.txt` or `.wordpress-org/` on `main` are pushed to WordPress.org without a new release (assets workflow).
