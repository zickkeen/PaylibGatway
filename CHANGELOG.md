# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog (https://keepachangelog.com/) and this project follows Semantic Versioning (SemVer) — https://semver.org/.

## [Unreleased]
- Preparing repository for initial release
- Add CHANGELOG and versioning information

## [1.0.0] - 2025-09-04
### Added
- Initial public release with core features: Config, PaymentGateway, OVO and GoPay providers, exceptions, and tests.
- README and basic examples.

---

Release workflow (singkat):
1. Bump version in composer.json (field `version`) to the new SemVer value (e.g. 1.1.0).
2. Update CHANGELOG.md: move relevant changes from Unreleased to a new heading for the new version and add the release date.
3. Commit changes and tag the release:

   git add composer.json CHANGELOG.md
   git commit -m "chore(release): v1.1.0"
   git tag -a v1.1.0 -m "Release v1.1.0"
   git push origin --tags

4. Optionally create a GitHub Release using the tag.

Notes:
- Use Semantic Versioning: MAJOR.MINOR.PATCH
  - MAJOR: incompatible API changes
  - MINOR: added functionality in a backward-compatible manner
  - PATCH: backward-compatible bug fixes
- Keep a Changelog conventions: group changes under Added, Changed, Deprecated, Removed, Fixed, Security.
