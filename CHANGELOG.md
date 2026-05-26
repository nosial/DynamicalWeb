# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.4] - Ongoing

This update introduces quality of life improvements

### Added
 - Added localizationId parameter to insertSection method, allowing for easier localization of sections
 - Added loadLocalization method in Functions class to allow sections to load localization sections during execution

### Changed
 - insertSection now accepts a path instead of a section name

### Removed
- Removed section configuration from the main configuration file, as it is no longer necessary


## [1.0.3] - 2026-05-25

### Added
 - Added logging event handler for unhandled


## [1.0.2] - 2026-05-25

### Fixed
 - Fixed issue where the URL resolution was not correctly handling query parameters


## [1.0.1] - 2026-05-25

### Fixed
 - Fixed issue where port number was not being included in the URL resolution


## [1.0.0] - 2026-04-14

### Added
 - Initial release of DynamicalWeb