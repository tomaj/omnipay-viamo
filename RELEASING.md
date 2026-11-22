# Releasing

Releases are published by `.github/workflows/release.yml` when a version tag is pushed. Tags have no `v` prefix: `5.0.0`, or `5.1.0-rc1` for a pre-release.

## Steps

1. In a pull request, move the `## [Unreleased][unreleased]` entries in `CHANGELOG.md` into a new `## [X.Y.Z] - YYYY-MM-DD` section below it, leave the empty `## [Unreleased][unreleased]` heading in place, and for a major version also update `UPGRADE-X.0.md`, the supported versions table in `SECURITY.md` (the new line supported, the previous one security fixes only for six months after the release) and the docs version menu (archive the previous major's pages under `docs/<major>.x/`). At the bottom, change the `[unreleased]` link to `compare/X.Y.Z...HEAD` and add `[X.Y.Z]: https://github.com/tomaj/omnipay-viamo/compare/<previous>...X.Y.Z`; each version links to the compare with the version below it. Check the section locally; it is the text of the release notes, except that relative links such as `[UPGRADE-5.0.md](UPGRADE-5.0.md)` become absolute links to the file at the tag:

   ```bash
   .github/scripts/release-notes.sh X.Y.Z
   ```

2. Merge the pull request and wait for CI on `master`.
3. Tag the merge commit and push the tag:

   ```bash
   git switch master && git pull
   git tag -a X.Y.Z -m X.Y.Z
   git push origin X.Y.Z
   ```

4. Close the `X.Y.Z` milestone and open the next one.

The workflow then:

- fails before publishing anything when `CHANGELOG.md` has no `## [X.Y.Z] - <date>` section (the older `## X.Y.Z - <date>` form is still accepted);
- builds `omnipay-viamo-X.Y.Z.tar.gz` and `omnipay-viamo-X.Y.Z.zip` with `git archive`, which leaves out the `export-ignore` paths of `.gitattributes` exactly like the Composer dist archive, plus `SHA256SUMS`;
- creates build provenance attestations for both archives (`actions/attest-build-provenance`);
- creates the GitHub release with the CHANGELOG section as notes, marked as a pre-release when the tag has a `-` suffix.

Packagist picks the tag up through its GitHub hook; nothing is uploaded there.

## Verifying a release

```bash
gh release download X.Y.Z --repo tomaj/omnipay-viamo
gh attestation verify omnipay-viamo-X.Y.Z.zip --repo tomaj/omnipay-viamo
sha256sum -c SHA256SUMS
```

## When something goes wrong

A failed run publishes nothing. Fix `CHANGELOG.md` on `master`, then move the tag and push it again:

```bash
git push origin :refs/tags/X.Y.Z
git tag -f -a X.Y.Z -m X.Y.Z
git push origin X.Y.Z
```

Do not move a tag after the release was published: Composer and Packagist may already have cached it. Release a new patch version instead.
