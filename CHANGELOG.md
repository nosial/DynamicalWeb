# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.21] - 2026-10-06

This is an ongoing update

### Added
 - Added `DynamicalWeb\Classes\Memcache` to expose DynamicalWeb's Memcached instance to web applications as a
   general-purpose shared cache, keys are namespaced via `MEMCACHED_KEY_PREFIX` (default `dw_app_`) ([#1](https://github.com/nosial/DynamicalWeb/issues/1))
 - Added `MEMCACHED_SESSION_SLIDING` to renew the session cookie's expiry whenever the session is read, keeping the
   attributes (path, domain, `SameSite`) the session was created with
 - Added `MEMCACHED_SESSION_BIND_IP` to leave the client IP out of session fingerprints; IP-bound sessions are moved to
   the new fingerprint instead of ending
 - Added CSRF protection: `WebSession::getCsrfToken()`, `WebSession::verifyCsrfToken()`, `Functions::csrfField()`,
   `Functions::csrfMeta()`, the `csrf_protection` application option and the `csrf_exempt` route option
 - Added `Functions::getl()`, `Functions::hasl()` and `Functions::getActiveLocaleId()` to use locale strings without printing them
 - Added `WebSession::redirectTo()`, `WebSession::redirectToRoute()`, `WebSession::respondJson()` and `WebSession::abort()`,
   which send the response and end the request, and `DynamicalWeb::renderResponseHandler()`
 - Added flash values in cookie sessions: `WebSession::flash()`, `WebSession::getFlash()` and `WebSession::hasFlash()`
 - Added the `headers` application option for response headers set on every response
 - Added `only`, `except` and `websocket` filters for `pre_request` / `post_request` entries
 - Added `Functions::getAbsoluteRouteUrl()`
 - Added `Request::getIntParameter()`, `Request::getBoolParameter()` and `Request::getStringParameter()`
 - Added `DynamicalWeb\Classes\RequestCache`, an in-process cache that lasts for one request
 - Added an optional `$sameSite` parameter to `Response::setCookie()`

### Changed
 - `WebSession::getCookieSession()` returns the same `CookieSession` instance for every call within a request, so a
   change saved by one caller is not overwritten by another caller saving an older copy

### Fixed
 - The `$sameSite` argument of `WebSession::createCookieSession()` was ignored and every session cookie was sent as `Lax`
 - A WebSocket request whose session fingerprint did not match (it arrives through the local bridge) deleted the
   browser's session and expired its cookie


## [1.0.20] - 2026-08-24

This update introduces a minor bug fix

### Fixed
 - Updated routing Request handling logic to not expose bad URL schemes/paths



## [1.0.19] - 2026-07-22

This update removes `npm` and `nodejs` included in the docker build



## [1.0.18] - 2026-07-21

This update introduces `npm` and `nodejs` included in the docker build



## [1.0.17] - 2026-07-20

This update introduces a new special section in the localization implementation where if a section `global` is defined,
labels from that section will be available no matter what current section of the localization is selected.



## [1.0.16] - 2026-07-20

This update introduces the new version of the ncc compiler



## [1.0.15] - 2026-06-01

Set `MEMCACHED_ENABLED` to `1` in the Dockerfile to enable memcached sessions by default



## [1.0.14] - 2026-06-01

This update introduces some minor improvements and changes to the project

### Added
 - Added PhpDoc to WebSocket

### Changed
 - Pre-Request and Post-Request event handlers will no longer execute for resource files, instead only executing modules.



## [1.0.13] - 2026-06-01

This update introduces a new method in Functions called `getRouteUrl` which works the same as `printRouteUrl` but
instead of printing the URL, it returns it as a string.



## [1.0.12] - 2026-06-01

This update introduces some minor improvements and changes to the project

### Changed
 - Updated `WebSession::endSession` to properly end the request rather than just clearing up the session data. This method
   now sends the response and optionally terminates the script executing if the `$exitCode` parameter is not null and is
   an integer.
 - Updated Dockerfile to include a health check.


## [1.0.11] - 2025-05-31

This update introduces web cookie sessions using memcached.



## [1.0.10] - 2026-05-30

This update introduces some minor improvements and changes to the project

### Changed
 - Added LogLib environment configuration to the Dockerfile

### Removed
 - Removed php-fpm's logging configuration from supervisord.conf



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