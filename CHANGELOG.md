# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.9] - 2026-05-30

This update introduces some changes

### Changed
 - Updated supervisord and Dockerfile to include LogLib2Server
 - Refactored proper Enum usage in codebase for better performance and maintainability



## [1.0.8] - 2026-05-28

This update introduces a bug fix

### Added
 - ExecutionHandler now keeps track of executed files

### Fixed
 - Functions::insertSection can now resolve relative paths correctly



## [1.0.7] - 2026-05-28

Fixed websocket timing issues


### [1.0.6] - 2026-05-27

This update introduces a critical bug fix

### Fixed
 - Fixed issue where insertSection would not resolve paths correctly


## [1.0.5] - 2026-05-27

This update introduces fix for php 8.5

### Fix
 - Fixed unexpected NAN value was coerced to string in PhpTabBuilder



## [1.0.4] - 2026-05-27

This update introduces quality of life improvements

### Added
 - Added localizationId parameter to insertSection method, allowing for easier localization of sections
 - Added loadLocalization method in Functions class to allow sections to load localization sections during execution
 - Added Docker build based off the ncc image builds
 - Added support for Websockets

### Changed
 - insertSection now accepts a path instead of a section name

### Removed
- Removed section configuration from the main configuration file, as it is no longer necessary
- Removed dead setCompleted from Response
- Removed dead isCompleted from Response


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