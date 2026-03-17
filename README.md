# DynamicalWeb

DynamicalWeb is a PHP framework intended to be used with [ncc](https://github.com/nosial/ncc) to create web applications.
This framework is designed with simplicity in mind as a way to add-on to PHP's core functionality and to make it easier
to create web applications with PHP and deploy them using ncc.

## Table of Contents

<!-- TOC -->
* [DynamicalWeb](#dynamicalweb)
  * [Table of Contents](#table-of-contents)
  * [Installation](#installation)
  * [Usage](#usage)
  * [Configuration](#configuration)
    * [Using `project.yml`](#using-projectyml)
    * [Using a separate Yaml file](#using-a-separate-yaml-file)
    * [Application Section](#application-section)
    * [Locales Section](#locales-section)
    * [Sections Section](#sections-section)
    * [Router Section](#router-section)
  * [DynamicalWeb Execution Flow](#dynamicalweb-execution-flow)
  * [Request Object](#request-object)
    * [Accessing Request Data](#accessing-request-data)
    * [File Uploads](#file-uploads)
    * [User Agent Detection](#user-agent-detection)
  * [Response Object](#response-object)
    * [Basic Responses](#basic-responses)
    * [JSON Responses](#json-responses)
    * [YAML Responses](#yaml-responses)
    * [File Download Responses](#file-download-responses)
    * [Redirect Responses](#redirect-responses)
    * [Streaming Responses](#streaming-responses)
  * [Template Functions](#template-functions)
    * [Printing Output](#printing-output)
    * [Printing Locale Strings](#printing-locale-strings)
    * [Printing Route URLs](#printing-route-urls)
    * [Inserting Sections](#inserting-sections)
  * [WebSession](#websession)
    * [Session Variables](#session-variables)
  * [Routing](#routing)
    * [Route Parameters](#route-parameters)
    * [Route Parameter Constraints](#route-parameter-constraints)
    * [Allowed Methods](#allowed-methods)
    * [Response Handlers](#response-handlers)
  * [Localization](#localization)
    * [Locale Files](#locale-files)
    * [Locale Detection](#locale-detection)
    * [Using Locales in Templates](#using-locales-in-templates)
  * [Static Resources](#static-resources)
  * [Pre and Post Request Scripts](#pre-and-post-request-scripts)
  * [XSS Protection](#xss-protection)
  * [Debug Panel](#debug-panel)
  * [Built-in Pages](#built-in-pages)
  * [APCu Caching](#apcu-caching)
  * [Cookies](#cookies)
  * [Deployment](#deployment)
    * [Docker](#docker)
    * [Nginx Configuration](#nginx-configuration)
* [License](#license)
<!-- TOC -->


## Installation

To use DynamicalWeb in your ncc project you can simply run the command:

```sh
ncc project --generate=dynamicalweb
```

What this will do is generate files and modify your existing project files to include DynamicalWeb as a dependency and
to set up the necessary files for DynamicalWeb to work properly, such as the main web entry point, sample phtml files
and a updated project.yml to include the configuration for DynamicalWeb.

To ensure that DynamicalWeb and all other dependencies are met, run the command:

```sh
ncc project install
```


## Usage

DynamicalWeb works by having a configured web server (such as Apache or Nginx) point to a PHP script that executes
the main web entry point of your Application, for instance a `index.php` located under `/var/www/html` may look like this:

```php
<?php
        require 'ncc';
        import('com.example.bootstrap');

        (new \DynamicalWeb\DynamicalWeb('com.example.bootstrap'))->handleRequest();
```

 > Note: It's important that the web server is configured to allow index.php to handle all requests, this is usually
   done by setting up URL rewriting rules in the web server configuration. Without this, DynamicalWeb will not be able
   to handle requests properly.

When using `ncc project --generate=dynamicalweb` these files are already generated for you, the generated `Dockerfile`
is also configured to set up the web server properly to allow DynamicalWeb to handle requests.


## Configuration

There are two ways to configure DynamicalWeb, both includes pointing DynamicalWeb to a Yaml configuration.

### Using `project.yml`

You can add the DynamicalWeb configuration directly to your `project.yml` file, this is done by adding a `web_configuration`
property under your build configuration's `options` property, for example this is how a `project.yml` file may 
look like this:

```yaml
source: src
default_build: release
web_entry_point: web_entry
assembly:
  name: ExampleBootstrap
  package: com.example.bootstrap
  version: 1.0.0
dependencies:
  net.nosial.dynamicalweb: nosial/dynamicalweb@n64
execution_units:
  -
    name: web_entry
    type: php
    mode: auto
    entry: web_entry
build_configurations:
  -
    name: debug
    output: target/debug/com.example.bootstrap.ncc
    type: ncc
    definitions:
      NCC_DEBUG: true
  -
    name: release
    output: target/release/com.example.bootstrap.ncc
    type: ncc
  -
    name: web_release
    output: 'target/web_release/${ASSEMBLY.PACKAGE}.ncc'
    type: ncc
    definitions:
      NCC_DISABLE_LOGGING: '1'
    options:
      web_configuration:
        application:
          name: "Example Bootstrap Application"
          root: "ExampleBootstrap/WebApplication"
          resources: "ExampleBootstrap/WebResources"
          default_locale: "en"
          report_errors: true
          xss_level: 0
          debug_panel: true
        locales:
          en: "ExampleBootstrap/WebLocale/en.yml"
          cn: "ExampleBootstrap/WebLocale/cn.yml"
          jp: "ExampleBootstrap/WebLocale/jp.yml"
        sections:
          navbar:
            module: "sections/navbar.phtml"
            locale_id: "navbar"
        router:
          base_path: "/"
          response_handlers:
            404: "errors/404.phtml"
            500: "errors/500.phtml"
          routes:
            - id: "home"
              path: "/"
              module: "index.phtml"
              locale_id: "home"
              allowed_methods: [ "*" ]
            - id: "about"
              path: "/about"
              module: "about.phtml"
              locale_id: "about"
              allowed_methods: [ GET ]
            - id: "error"
              path: "/error"
              module: "error.phtml"
              locale_id: "error"
            - id: "contact"
              path: "/contact"
              module: "contact.phtml"
              locale_id: "contact"
              allowed_methods: [ GET, POST ]
            - id: "examples"
              path: "/examples"
              module: "examples.phtml"
              locale_id: "examples"
              allowed_methods: [ GET ]
            - id: "redirect_example"
              path: "/redirect-example"
              module: "redirect-example.phtml"
              locale_id: "redirect_example"
              allowed_methods: [ GET ]
            - id: "stream_example"
              path: "/stream-example"
              module: "stream-example.phtml"
              locale_id: "stream_example"
              allowed_methods: [ GET ]
            - id: "test_response_types"
              path: "/test-response-types"
              module: "test-response-types.phtml"
              allowed_methods: [ GET ]
            - id: "api_hello"
              path: "/api/hello"
              module: "api/hello.php"
              allowed_methods: [ GET ]
            - id: "api_user"
              path: "/api/users/{id}"
              module: "api/user.php"
              allowed_methods: [ GET ]
            - id: "api_user_foo"
              path: "/api/users/{id}/{foo}"
              module: "api/user.php"
              allowed_methods: [ GET ]
            - id: "api_download"
              path: "/api/download"
              module: "api/download.php"
              allowed_methods: [ GET ]
            - id: "api_stream"
              path: "/api/stream"
              module: "api/stream.php"
              allowed_methods: [ GET ]
            - id: "api_redirect"
              path: "/api/redirect"
              module: "api/redirect.php"
              allowed_methods: [ GET ]
      static: true
```

### Using a separate Yaml file

Alternatively, you can also point DynamicalWeb to a separate Yaml file that contains the configuration, the configuration
format remains the same as the one used in `project.yml`, the only difference is that the contents of the Yaml file should
be the contents of the `web_configuration` property in the previous example, for instance

```yaml
application:
  name: "Example Bootstrap Application"
  root: "ExampleBootstrap/WebApplication"
  resources: "ExampleBootstrap/WebResources"
  default_locale: "en"
  report_errors: true
  xss_level: 0
  debug_panel: true
locales:
  en: "ExampleBootstrap/WebLocale/en.yml"
  cn: "ExampleBootstrap/WebLocale/cn.yml"
  jp: "ExampleBootstrap/WebLocale/jp.yml"
sections:
  navbar:
    module: "sections/navbar.phtml"
    locale_id: "navbar"
router:
  base_path: "/"
  response_handlers:
    404: "errors/404.phtml"
    500: "errors/500.phtml"
  routes:
    - id: "home"
      path: "/"
      module: "index.phtml"
      locale_id: "home"
      allowed_methods: [ "*" ]
    - id: "about"
      path: "/about"
      module: "about.phtml"
      locale_id: "about"
      allowed_methods: [ GET ]
    - id: "error"
      path: "/error"
      module: "error.phtml"
      locale_id: "error"
    - id: "contact"
      path: "/contact"
      module: "contact.phtml"
      locale_id: "contact"
      allowed_methods: [ GET, POST ]
    - id: "examples"
      path: "/examples"
      module: "examples.phtml"
      locale_id: "examples"
      allowed_methods: [ GET ]
    - id: "redirect_example"
      path: "/redirect-example"
      module: "redirect-example.phtml"
      locale_id: "redirect_example"
      allowed_methods: [ GET ]
    - id: "stream_example"
      path: "/stream-example"
      module: "stream-example.phtml"
      locale_id: "stream_example"
      allowed_methods: [ GET ]
    - id: "test_response_types"
      path: "/test-response-types"
      module: "test-response-types.phtml"
      allowed_methods: [ GET ]
    - id: "api_hello"
      path: "/api/hello"
      module: "api/hello.php"
      allowed_methods: [ GET ]
    - id: "api_user"
      path: "/api/users/{id}"
      module: "api/user.php"
      allowed_methods: [ GET ]
    - id: "api_user_foo"
      path: "/api/users/{id}/{foo}"
      module: "api/user.php"
      allowed_methods: [ GET ]
    - id: "api_download"
      path: "/api/download"
      module: "api/download.php"
      allowed_methods: [ GET ]
    - id: "api_stream"
      path: "/api/stream"
      module: "api/stream.php"
      allowed_methods: [ GET ]
    - id: "api_redirect"
      path: "/api/redirect"
      module: "api/redirect.php"
      allowed_methods: [ GET ]
```

And this file can be pointed to in the `project.yml` by using the `web_configuration` property to point to the file
(relative to the package's path) for instance the above examples would be something like `ExampleBootstrap/configuration.yml`


### Application Section

The Application section of the configuration is where you can set up the core information about your web application, such as the name
and among other configurable properties.

| Name                      | Required | Example                             | Type            | Description                                                                                                                                                                                              |
|---------------------------|----------|-------------------------------------|-----------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `name`                    | Yes      | "Web Application"                   | `string`        | The name of your web application                                                                                                                                                                         |
| `root`                    | Yes      | "ExampleBootstrap/WebApplication"   | `string`        | The root directory where all `.phtml` files resides                                                                                                                                                      |
| `resources`               | No       | "ExampleBootstrap/WebResources"     | `string`        | The root directory where all web resources resides (`.css`, `.js`, etc...) these files are accessible under the root path of your web application                                                        |
| `default_locale`          | No       | "en"                                | `string`        | The default locale ID of the web application                                                                                                                                                             |
| `report_errors`           | No       | True                                | `boolean`       | When True, any unhandled exceptions will result in DynamicalWeb displaying the exception details. Not recommended for production                                                                         |
| `xss_level`               | No       | 1                                   | `integer` (0-3) | XSS Level protection, when enabled DynamicalWeb will inject xss-protection related headers. 0=Disabled, 1=Low, 2=Medium 3=High                                                                           |
| `debug_panel`             | No       | True                                | `boolean`       | When True, a debug iFrame is injected in the resulting HTML responses which contains detailed information about the web environment. Not recommended for production, adds a performance hit when enabled |
| `pre_request`             | No       | `['authentication.php', 'foo.php']` | `array`         | An array of php scripts (Based from `root`) to execute in order before processing the http request                                                                                                       |
| `post_request`            | No       | `['cleanup.php', 'bar.php']`        | `array`         | An array of php scripts (Based from `root`) to execute in order after processing the http request                                                                                                        |
| `disable_apcu`            | No       | True                                | `boolean`       | When True, the use of the APCu cache layer is disabled, otherwise DynamicalWeb will use APCu if it's available to cache properties and small resource files when running the WebApplication              |
| `disable_default_headers` | No       | True                                | `boolean`       | When True, DynamicalWeb omits builtin headers like `X-Powered-By` and `X-Request-ID` from being used in the http response                                                                                |
| `static_cache_max_age`    | No       | 3600                                | `integer`       | The max-age value in seconds used in the `Cache-Control` header when serving static files. Set to `0` to disable cache headers. Defaults to `3600` (1 hour)                                              |
| `apcu_content_max_size`   | No       | 262144                              | `integer`       | The maximum file size in bytes for which static file content will be cached in APCu. Files larger than this are streamed from disk. Defaults to `262144` (256 KB)                                        |
| `apcu_content_ttl`        | No       | 3600                                | `integer`       | The TTL in seconds for static file content cached in APCu. Defaults to `3600` (1 hour)                                                                                                                   |
| `apcu_meta_ttl`           | No       | 10                                  | `integer`       | The TTL in seconds for file metadata (modification time and size) cached in APCu. Defaults to `10` seconds                                                                                               |
| `apcu_config_ttl`         | No       | 60                                  | `integer`       | The TTL in seconds for the parsed web configuration cached in APCu. Defaults to `60` (1 minute)                                                                                                          |


### Locales Section

This section allows you to configure locales for your web application, the key of each locale is the locale ID and the value
is the path to the Yaml file that contains the translations for that locale, for example:

```yaml
home:
  title_banner: "Welcome to the Home Page"
  description: "This is the home page of {app_name}"
footer:
  copyright: "Copyright © 2024-2026 {company_name}. All rights reserved."
```

A locale configuration may look like this:

```yaml
locales:
  en: "ExampleBootstrap/WebLocale/en.yml"
  cn: "ExampleBootstrap/WebLocale/cn.yml"
  jp: "ExampleBootstrap/WebLocale/jp.yml"
```

Locales can be configured and changed simply by visiting the path `/dynaweb/language/<locale_id>` for example, visiting
`/dynaweb/language/cn` will change the current locale to `cn` if it's configured properly, otherwise it will return a
404 response. The locale will be stored in a cookie and will persist across requests until it's changed again or the
cookie is cleared.


### Sections Section

Sections in DynamicalWeb are used to define reusable modules that can be included in multiple pages, for example a
navbar section can be defined, or even headers and footers, these sections can also be localized by setting the
`locale_id` property to a valid locale ID, for example:

```yaml
sections:
  navbar:
    module: "sections/navbar.phtml"
    locale_id: "navbar"
  footer:
    module: "sections/footer.phtml"
    locale_id: "footer"
```

When a section is inserted into a page using `Functions::insertSection('navbar')`, DynamicalWeb will automatically
switch the active locale context to the section's `locale_id` for the duration of the section's rendering, this means
that any `Functions::printl()` calls inside the section will resolve strings from the section's own locale scope
without conflicting with the page's locale scope. Once the section finishes rendering, the previous locale context
is restored.

A section configuration has the following properties:

| Name        | Required | Example                 | Type     | Description                                                                                            |
|-------------|----------|-------------------------|----------|--------------------------------------------------------------------------------------------------------|
| `module`    | Yes      | "sections/navbar.phtml" | `string` | The path to the `.phtml` file for this section, based from the `root` directory                        |
| `locale_id` | No       | "navbar"                | `string` | The locale section ID to use when rendering, this maps to a key in your locale Yaml file               |


### Router Section

The router section allows you to define the routing configuration for your web application, this includes the
base path, response handlers and the actual routes, for example:

```yaml
router:
    base_path: "/"
    response_handlers:
      404: "errors/404.phtml"
      500: "errors/500.phtml"
    routes:
      - id: "home"
        path: "/"
        module: "index.phtml"
        locale_id: "home"
        allowed_methods: [ "*" ]
      - id: "about_user"
        path: "/{id}/about"
        module: "about.phtml"
        locale_id: "about"
        allowed_methods: [ GET ]
```

A Router configuration consists of the following properties:

| Name                | Required | Example                                                | Type     | Description                                                                                                                                                                                                                 |
|---------------------|----------|--------------------------------------------------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `base_path`         | No       | "/"                                                    | `string` | The base path of the web application, this is used when generating URLs in the application, if not set, it will be automatically detected from the incoming http request, but it's recommended to set it explicitly         |
| `response_handlers` | No       | `{ 404: "errors/404.phtml", 500: "errors/500.phtml" }` | `object` | An object where the key is the http status code and the value is the path to the module to handle that response, the module should be a valid `.phtml` file based from the `root` directory                                 |
| `routes`            | Yes      | See example above                                      | `array`  | An array of route objects, each route object should have the following properties: `id`, `path`, `module`, `allowed_methods` and optionally `locale_id`                                                                     |

A route object should have the following properties:

| Name              | Required | Example           | Type     | Description                                                                                                                                                                                                                |
|-------------------|----------|-------------------|----------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `id`              | Yes      | "home"            | `string` | The unique ID of the route, this is used to identify the route and can be used when generating URLs in the application                                                                                                     |
| `path`            | Yes      | "/users/{id}"     | `string` | The path of the route, this can include path parameters enclosed in curly braces, for example `/users/{id}` will match any path that starts with `/users/` followed by a value that will be captured as the `id` parameter |
| `module`          | Yes      | "users.phtml"     | `string` | The path to the module that will handle the route, this should be a valid `.phtml` or `.php` file based from the `root` directory                                                                                          |
| `allowed_methods` | Yes      | [ "GET", "POST" ] | `array`  | An array of allowed http methods for the route, if the incoming request method is not in this array, a 405 Method Not Allowed response will be returned, the special value `*` can be used to allow all methods            |
| `locale_id`       | No       | "home"            | `string` | The locale ID to use when rendering the module for this route, this should be a valid locale ID that is configured in the `locales` section, if not set, the default locale will be used                                   |


## DynamicalWeb Execution Flow

DynamicalWeb follows a specific execution flow when handling an incoming http request, this flow can be summarized:

 1. The main web entry point of the application is executed by the web server, this is usually a `index.php` file that
    includes the code to import the web application and use DynamicalWeb to handle the incoming request, for example:

```php
<?php
    require 'ncc';
    import('com.example.bootstrap');
    
    (new \DynamicalWeb\DynamicalWeb('com.example.bootstrap'))->handleRequest();
?>
```

 2. DynamicalWeb will load your package and parse the configuration file, preparing everything for handling the incoming
    request including initializing the `WebSession`, creating the `Request` and `Response` objects, detecting the locale
    and matching the incoming request to one of the configured routes

 3. If `pre_request` scripts are configured, they are executed in order before the matched module runs, this allows
    you to do things like authentication checks, rate limiting, or any other pre-processing logic

 4. Once a route has been found, the `.phtml` or `.php` module configured for that route will be executed, during this
    time DynamicalWeb's `WebSession` class becomes available to the module allowing the module to access information about
    the incoming request and to set information for the response, see [Request Object](#request-object) and
    [Response Object](#response-object) sections for more details about these objects and how to access them from the module

 5. If `post_request` scripts are configured, they are executed in order after the matched module finishes, this allows
    you to do things like cleanup, logging, or any other post-processing logic

 6. DynamicalWeb sends the response to the client, including all headers, cookies and the response body based on
    the configured response type (HTML, JSON, File Download, Redirect, Stream, etc.)

 7. If the debug panel is enabled, DynamicalWeb will inject a debug iFrame into the resulting HTML response before
    sending it to the client

 8. The `WebSession` is ended and all static state is cleared


## Request Object

The request object can be accessed from anywhere within the module using the `\DynamicalWeb\WebSession::getRequest()`
method which will return a `\DynamicalWeb\Objects\Request` object, this object contains all the information about the
incoming http request including parsed information if available

### Accessing Request Data

| Method                                           | Return Type            | Description                                                                                                                                                          |
|--------------------------------------------------|------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `getId()`                                        | `string`               | Returns the unique ID of the request, this is a randomly generated string that can be used to identify the request in logs and other places                          |
| `getMethod()`                                    | `RequestMethod` (Enum) | Returns the enum value of the http method of the request, this can be used to determine the method of the incoming request                                           |
| `getUrl()`                                       | `string`               | Returns the full URL of the incoming request, this includes the path and query string but not the base URL, for example `/users/123?foo=bar`                         |
| `getPath()`                                      | `string`               | Returns only the path component of the request URL, for example `/users/123`                                                                                         |
| `getHost()`                                      | `string`               | Returns the host of the incoming request, for example `example.com`                                                                                                  |
| `getHttpVersion()`                               | `string`               | Returns the HTTP version of the incoming request, for example `1.1` or `2`                                                                                           |
| `isSecure()`                                     | `bool`                 | Returns True if the request was made over HTTPS                                                                                                                      |
| `getHeaders()`                                   | `array`                | Returns all HTTP headers of the incoming request as an associative array                                                                                             |
| `getHeader(string $name, ?string $default=null)` | `?string`              | Returns a specific header value by name (case-insensitive), or the default value if not found                                                                        |
| `getQueryParameters()`                           | `array`                | Returns the GET query parameters of the incoming request                                                                                                             |
| `getBodyParameters()`                            | `array`                | Returns the parsed body parameters, supports `application/json` and `application/x-yaml` content types                                                               |
| `getFormParameters()`                            | `array`                | Returns the form parameters from a standard POST form submission                                                                                                     |
| `getPathParameters()`                            | `array`                | Returns the extracted path parameters from the matched route, for example if the route is `/users/{id}` and the path is `/users/123`, this returns `['id' => '123']` |
| `getPathParameter(string $name)`                 | `?string`              | Returns a specific path parameter value by name, or null if not found                                                                                                |
| `getParameters()`                                | `array`                | Returns a merged array of all parameters with priority: form > body > query                                                                                          |
| `getParameter(string $name)`                     | `?string`              | Returns a specific parameter value by name from the merged parameters                                                                                                |
| `getCookies()`                                   | `array`                | Returns all cookies from the incoming request                                                                                                                        |
| `getCookie(string $name, $default=null)`         | `mixed`                | Returns a specific cookie value by name, or the default value if not found                                                                                           |
| `getClientIp()`                                  | `?string`              | Returns the client's IP address, checks Cloudflare headers, `X-Forwarded-For` and other proxy headers before falling back to `REMOTE_ADDR`                           |
| `getRawBody()`                                   | `?string`              | Returns the raw request body from `php://input`                                                                                                                      |
| `getDetectedLanguage()`                          | `?string`              | Returns the detected ISO 639-1 language code from the `Accept-Language` header                                                                                       |
| `getUserAgent()`                                 | `?UserAgent`           | Returns the parsed User-Agent object, see [User Agent Detection](#user-agent-detection) for more details                                                             |
| `getUserAgentString()`                           | `?string`              | Returns the raw User-Agent header string                                                                                                                             |

Here's an example of how to use the request object within a module:

```php
<?php
    use DynamicalWeb\WebSession;
    use DynamicalWeb\Enums\RequestMethod;
    
    $request = WebSession::getRequest();
    
    if($request->getMethod() === RequestMethod::POST)
    {
        $name = $request->getParameter('name');
        $email = $request->getParameter('email');
        
        // Process the form submission
    }
    
    $userId = $request->getPathParameter('id');
    $page = $request->getParameter('page') ?? '1';
?>
```


### File Uploads

DynamicalWeb parses file uploads into `UploadedFile` objects which provide a clean interface for working with uploaded
files, the following methods are available on the request object for file uploads:

| Method                 | Return Type                 | Description                                                                                      |
|------------------------|-----------------------------|--------------------------------------------------------------------------------------------------|
| `hasFiles()`           | `bool`                      | Returns True if any files were uploaded with the request                                         |
| `hasFile(string $key)` | `bool`                      | Returns True if a specific file field exists in the upload                                       |
| `getFile(string $key)` | `UploadedFile\|array\|null` | Returns the uploaded file(s) for a specific field, can return an array for multiple file uploads |
| `getFiles()`           | `array`                     | Returns the raw `$_FILES` array                                                                  |
| `getFileCount()`       | `int`                       | Returns the total number of uploaded files                                                       |
| `getValidFiles()`      | `array`                     | Returns only the files that were uploaded without errors                                         |
| `getTotalFileSize()`   | `int`                       | Returns the total size of all uploaded files in bytes                                            |

Each `UploadedFile` object provides the following methods:

| Method                                                              | Return Type       | Description                                                                                |
|---------------------------------------------------------------------|-------------------|--------------------------------------------------------------------------------------------|
| `getClientFilename()`                                               | `string`          | Returns the original filename as provided by the client                                    |
| `getClientExtension()`                                              | `string`          | Returns the file extension from the original filename                                      |
| `getTempPath()`                                                     | `string`          | Returns the temporary file path where the uploaded file is stored                          |
| `getSize()`                                                         | `int`             | Returns the file size in bytes                                                             |
| `getError()`                                                        | `int`             | Returns the PHP upload error code                                                          |
| `getErrorMessage()`                                                 | `?string`         | Returns a human-readable error message for the upload error code                           |
| `isValid()`                                                         | `bool`            | Returns True if the file was uploaded successfully (`UPLOAD_ERR_OK`)                       |
| `getClientMimeType()`                                               | `?string`         | Returns the MIME type as reported by the client (unreliable, should not be trusted)        |
| `getMimeType()`                                                     | `?string`         | Returns the MIME type detected from the file contents (more reliable than client-provided) |
| `isMimeType(string\|array $type)`                                   | `bool`            | Checks if the file matches the given MIME type(s), supports wildcards like `image/*`       |
| `isImage()`                                                         | `bool`            | Returns True if the detected MIME type is `image/*`                                        |
| `isSizeWithinLimit(int $maxSize)`                                   | `bool`            | Returns True if the file size is within the given limit in bytes                           |
| `hasAllowedExtension(array $extensions, bool $caseSensitive=false)` | `bool`            | Returns True if the file extension is in the allowed list                                  |
| `moveTo(string $destination, bool $overwrite=false)`                | `bool`            | Moves the uploaded file to a permanent location, returns True on success                   |
| `isMoved()`                                                         | `bool`            | Returns True if the file has already been moved                                            |
| `getContents(int $maxSize=10485760)`                                | `?string`         | Returns the file contents as a string (max 10MB by default)                                |
| `getStream(string $mode='rb')`                                      | `resource\|false` | Opens and returns a file stream resource                                                   |
| `getHash(string $algo='sha256')`                                    | `?string`         | Returns the hash of the file contents using the specified algorithm                        |

Here's an example of handling file uploads in a module:

```php
<?php
    use DynamicalWeb\WebSession;
    
    $request = WebSession::getRequest();
    
    if($request->hasFile('avatar'))
    {
        $file = $request->getFile('avatar');
        
        if($file->isValid() && $file->isSizeWithinLimit(5 * 1024 * 1024))
        {
            if($file->hasAllowedExtension(['jpg', 'jpeg', 'png', 'webp']))
            {
                $file->moveTo('/var/uploads/avatars/' . $file->getHash() . '.' . $file->getClientExtension());
            }
        }
    }
?>
```


### User Agent Detection

DynamicalWeb includes a built-in User-Agent parser that can detect browsers, operating systems, device types, device
brands, rendering engines and bots from the incoming request's User-Agent header. The parsed result is accessible via
the `getUserAgent()` method on the request object which returns a `UserAgent` object.

| Method                 | Return Type               | Description                                                                            |
|------------------------|---------------------------|----------------------------------------------------------------------------------------|
| `getRawUserAgent()`    | `string`                  | Returns the raw User-Agent string                                                      |
| `getBrowserName()`     | `?Browser` (Enum)         | Returns the detected browser (Chrome, Firefox, Safari, Edge, Opera, Brave, etc.)       |
| `getBrowserVersion()`  | `?string`                 | Returns the detected browser version                                                   |
| `getFullBrowserName()` | `?string`                 | Returns the full browser name with version, for example `Chrome 120.0`                 |
| `getOsName()`          | `?OperatingSystem` (Enum) | Returns the detected OS (Windows, macOS, Linux, Android, iOS, etc.)                    |
| `getOsVersion()`       | `?string`                 | Returns the detected OS version                                                        |
| `getFullOsName()`      | `?string`                 | Returns the full OS name with version, for example `Windows 11`                        |
| `getDeviceType()`      | `DeviceType` (Enum)       | Returns the device type: `MOBILE`, `TABLET`, `DESKTOP`, `BOT`, or `UNKNOWN`            |
| `getDeviceBrand()`     | `?DeviceBrand` (Enum)     | Returns the device brand (Apple, Samsung, Google, Xiaomi, Huawei, etc.)                |
| `getDeviceModel()`     | `?string`                 | Returns the device model if detectable, for example `iPhone` or `Galaxy S21`           |
| `getFullDeviceName()`  | `?string`                 | Returns the full device name with brand and model                                      |
| `isMobile()`           | `bool`                    | Returns True if the device is a mobile phone                                           |
| `isTablet()`           | `bool`                    | Returns True if the device is a tablet                                                 |
| `isDesktop()`          | `bool`                    | Returns True if the device is a desktop computer                                       |
| `isBot()`              | `bool`                    | Returns True if the User-Agent is a known bot or crawler                               |
| `getBotName()`         | `?Bot` (Enum)             | Returns the detected bot type (Googlebot, Bingbot, Facebook, WhatsApp, Telegram, etc.) |
| `getEngine()`          | `?RenderingEngine` (Enum) | Returns the rendering engine (Blink, Gecko, WebKit, Trident, Presto)                   |
| `getEngineVersion()`   | `?string`                 | Returns the rendering engine version                                                   |
| `getFullEngineName()`  | `?string`                 | Returns the full engine name with version                                              |
| `getPlatform()`        | `?string`                 | Returns the platform string                                                            |
| `getPlatformVersion()` | `?string`                 | Returns the platform version                                                           |
| `toArray()`            | `array`                   | Returns all parsed data as a nested associative array                                  |

Here's an example of using the User-Agent detection:

```php
<?php
    use DynamicalWeb\WebSession;
    use DynamicalWeb\Enums\UserAgent\DeviceType;
    
    $request = WebSession::getRequest();
    $ua = $request->getUserAgent();
    
    if($ua !== null)
    {
        if($ua->isBot())
        {
            // Handle bot request differently
        }
        
        if($ua->isMobile())
        {
            // Serve mobile-optimized content
        }
        
        echo $ua->getFullBrowserName();  // "Chrome 120.0"
        echo $ua->getFullOsName();       // "Windows 11"
    }
?>
```


## Response Object

The response object can be accessed from anywhere within the module using the `\DynamicalWeb\WebSession::getResponse()`
method which will return a `\DynamicalWeb\Objects\Response` object, this object allows you to configure the response that
will be sent back to the client. All setter methods on the response object return `self` for method chaining.

| Method                                                                              | Return Type    | Description                                                                                     |
|-------------------------------------------------------------------------------------|----------------|-------------------------------------------------------------------------------------------------|
| `getStatusCode()`                                                                   | `ResponseCode` | Returns the HTTP status code of the response                                                    |
| `setStatusCode(ResponseCode\|int $statusCode)`                                      | `self`         | Sets the HTTP status code of the response                                                       |
| `getHttpVersion()`                                                                  | `string`       | Returns the HTTP version of the response (default: `1.1`)                                       |
| `setHttpVersion(string $httpVersion)`                                               | `self`         | Sets the HTTP version of the response                                                           |
| `getHeaders()`                                                                      | `array`        | Returns all response headers                                                                    |
| `setHeaders(array $headers)`                                                        | `self`         | Sets all response headers at once                                                               |
| `setHeader(string $name, string $value, bool $replace=true)`                        | `self`         | Sets a specific header, if `$replace` is False the value is appended as an array header         |
| `removeHeader(string $name)`                                                        | `self`         | Removes a specific header from the response                                                     |
| `getBody()`                                                                         | `string`       | Returns the response body content                                                               |
| `setBody(string $body)`                                                             | `self`         | Sets the response body content                                                                  |
| `getContentType()`                                                                  | `string`       | Returns the Content-Type of the response                                                        |
| `setContentType(string\|MimeType $contentType)`                                     | `self`         | Sets the Content-Type, accepts a string or `MimeType` enum value                                |
| `getCharset()`                                                                      | `string`       | Returns the character set of the response (default: `UTF-8`)                                    |
| `setCharset(string $charset)`                                                       | `self`         | Sets the character set of the response                                                          |
| `getCookies()`                                                                      | `array`        | Returns all cookies set for the response                                                        |
| `getCookie(string $name)`                                                           | `?Cookie`      | Returns a specific cookie by name                                                               |
| `setCookie(string $name, string $value, int $expires=0, string $path='/', ...)`     | `self`         | Sets a cookie with the given parameters                                                         |
| `addCookie(Cookie $cookie)`                                                         | `self`         | Adds a `Cookie` object to the response                                                          |
| `removeCookie(string $name)`                                                        | `self`         | Removes a cookie from the response                                                              |
| `getResponseType()`                                                                 | `ResponseType` | Returns the response type (BASIC, JSON, YAML, FILE_DOWNLOAD, REDIRECT, STREAM)                  |
| `setResponseType(ResponseType $responseType)`                                       | `self`         | Sets the response type                                                                          |
| `setJson(mixed $data, int $flags=0, int $depth=512)`                                | `self`         | Sets the response as a JSON response, automatically encodes the data                            |
| `setYaml(mixed $data, int $inline=2, int $indent=4, int $flags=0)`                  | `self`         | Sets the response as a YAML response, automatically encodes the data                            |
| `setFileDownload(string $filePath, ?string $filename=null)`                         | `self`         | Sets the response as a file download, optionally with a custom filename                         |
| `setRedirect(string $url, ResponseCode $statusCode=null)`                           | `self`         | Sets the response as a redirect, defaults to 302 Found                                          |
| `setStream(callable $callback)`                                                     | `self`         | Sets the response as a streaming response with a callback function                              |

DynamicalWeb supports six response types, each serving a different purpose. The response type determines how
DynamicalWeb processes and sends the response to the client.


### Basic Responses

The default response type is `BASIC`, this is a standard HTML/text response where the body content is sent directly
to the client. When using `.phtml` files, the output of the file is captured using output buffering and set as the
body content.

```php
<?php
    use DynamicalWeb\WebSession;
    use DynamicalWeb\Enums\ResponseCode;
    
    WebSession::getResponse()
        ->setStatusCode(ResponseCode::OK)
        ->setContentType('text/plain')
        ->setBody('Hello, World!');
?>
```

For `.phtml` files, you don't need to explicitly set the body since the output buffer captures everything:

```phtml
<html>
<body>
    <h1>Hello, World!</h1>
    <p>The current request method is: <?php \DynamicalWeb\Html\Functions::print(WebSession::getRequest()->getMethod()); ?></p>
</body>
</html>
```


### JSON Responses

JSON responses are useful for building API endpoints, the `setJson()` method will automatically set the content type
to `application/json` and encode the given data as JSON:

```php
<?php
    use DynamicalWeb\WebSession;
    use DynamicalWeb\Enums\ResponseCode;
    
    $data = [
        'message' => 'Hello, World!',
        'timestamp' => time(),
        'user' => [
            'id' => WebSession::getRequest()->getPathParameter('id'),
            'name' => 'John Doe',
        ]
    ];
    
    WebSession::getResponse()
        ->setStatusCode(ResponseCode::OK)
        ->setJson($data);
?>
```


### YAML Responses

Similar to JSON responses, the `setYaml()` method will automatically set the content type to `application/x-yaml`
and encode the given data as YAML using Symfony's Yaml component:

```php
<?php
    use DynamicalWeb\WebSession;
    
    WebSession::getResponse()->setYaml([
        'status' => 'ok',
        'services' => ['web', 'database', 'cache']
    ]);
?>
```


### File Download Responses

File download responses allow you to serve files to the client with the appropriate `Content-Disposition` header
set to trigger a download in the browser:

```php
<?php
    use DynamicalWeb\WebSession;
    
    WebSession::getResponse()->setFileDownload('/path/to/report.pdf', 'monthly-report.pdf');
?>
```

The first argument is the path to the file on the server, and the second optional argument is the filename that the
client will see when downloading the file, if not provided the original filename will be used.


### Redirect Responses

Redirect responses allow you to redirect the client to a different URL, the `setRedirect()` method accepts the
target URL and an optional status code (defaults to 302 Found):

```php
<?php
    use DynamicalWeb\WebSession;
    use DynamicalWeb\Enums\ResponseCode;
    
    // Temporary redirect (302)
    WebSession::getResponse()->setRedirect('/dashboard');
    
    // Permanent redirect (301)
    WebSession::getResponse()->setRedirect('/new-location', ResponseCode::MOVED_PERMANENTLY);
    
    // See Other (303) - useful after POST requests
    WebSession::getResponse()->setRedirect('/success', ResponseCode::SEE_OTHER);
?>
```


### Streaming Responses

Streaming responses allow you to send data to the client in real-time as it becomes available, without buffering
the entire response in memory. This is useful for long-running processes, real-time updates, or large data exports.
The `setStream()` method accepts a callable that will be invoked to produce the stream output:

```php
<?php
    use DynamicalWeb\WebSession;
    
    WebSession::getResponse()->setStream(function() {
        for($i = 0; $i < 100; $i++)
        {
            echo "Update #$i at " . date('H:i:s') . "\n";
            flush();
            sleep(1);
        }
    });
?>
```

On the client side, you can consume a streaming response using the Fetch API:

```javascript
const response = await fetch('/api/stream');
const reader = response.body.getReader();
const decoder = new TextDecoder();

while(true) {
    const { done, value } = await reader.read();
    if(done) break;
    console.log(decoder.decode(value));
}
```


## Template Functions

DynamicalWeb provides a set of helper functions through the `\DynamicalWeb\Html\Functions` class that are designed
to be used within `.phtml` template files to make rendering content easier and safer.


### Printing Output

The `Functions::print()` method prints text to the output with HTML escaping enabled by default, this is important
for preventing XSS attacks when displaying user-provided content:

```phtml
<?php use DynamicalWeb\Html\Functions; ?>

<!-- Escaped output (safe) -->
<p><?php Functions::print($userInput); ?></p>

<!-- Unescaped output (only use for trusted HTML) -->
<div><?php Functions::print($trustedHtml, false); ?></div>
```

This method also supports objects that implement the `StringInterface`, allowing you to print enum values and other
objects that define a `toString()` method directly.


### Printing Locale Strings

The `Functions::printl()` method prints a localized string from the current locale, it uses the active locale section
which is determined by the current route's `locale_id` or the section's `locale_id` if called from within a section:

```phtml
<?php use DynamicalWeb\Html\Functions; ?>

<!-- Simple locale string -->
<h1><?php Functions::printl('page_title'); ?></h1>

<!-- Locale string with placeholder replacements -->
<p><?php Functions::printl('welcome_message', ['name' => $userName]); ?></p>
```

Given a locale file like this:

```yaml
home:
  page_title: "Welcome Home"
  welcome_message: "Hello {name}, welcome back!"
```

The `printl()` method will look up the string using the active locale section (e.g., `home`) and the given key
(e.g., `page_title`), and replace any `{placeholder}` tokens with the values from the provided array.


### Printing Route URLs

The `Functions::printRoute()` method generates and prints a fully-qualified URL for a named route, this is useful
for creating links between pages without hardcoding paths:

```phtml
<?php use DynamicalWeb\Html\Functions; ?>

<!-- Simple route URL -->
<a href="<?php Functions::printRoute('home'); ?>">Home</a>

<!-- Route URL with path variables -->
<a href="<?php Functions::printRoute('api_user', ['id' => '123']); ?>">User Profile</a>

<!-- Route URL with path variables and query parameters -->
<a href="<?php Functions::printRoute('api_user', ['id' => '123'], ['tab' => 'settings']); ?>">User Settings</a>
```

This method resolves the route by its ID, builds the URL from the automatically detected base URL (scheme and host
from the current HTTP request) and `base_path`, substitutes
any `{variable}` placeholders in the route path with the provided values, and appends optional GET query parameters.


### Inserting Sections

The `Functions::insertSection()` method renders a named section (a reusable `.phtml` fragment) and outputs its content
inline. Sections are useful for shared components like navigation bars, headers, footers, and sidebars:

```phtml
<?php use DynamicalWeb\Html\Functions; ?>

<html>
<body>
    <?php Functions::insertSection('navbar'); ?>
    
    <main>
        <h1><?php Functions::printl('page_title'); ?></h1>
        <p>Page content here...</p>
    </main>
    
    <?php Functions::insertSection('footer'); ?>
</body>
</html>
```

When a section is inserted, DynamicalWeb automatically switches the active locale context to the section's
configured `locale_id`, this means `printl()` calls inside the section's `.phtml` file resolve strings from
the section's own locale scope. Once the section finishes rendering, the previous locale context is restored,
so the page's own `printl()` calls continue to work as expected.


## WebSession

The `WebSession` class is a static class that acts as the central access point during a request's lifecycle,
it holds references to the current DynamicalWeb instance, the request, the response, the current route, the
loaded locale and any custom variables you want to store.

| Method                                      | Return Type        | Description                                                                        |
|---------------------------------------------|--------------------|------------------------------------------------------------------------------------|
| `getInstance()`                             | `?DynamicalWeb`    | Returns the current DynamicalWeb instance                                          |
| `getRequest()`                              | `?Request`         | Returns the current request object                                                 |
| `getResponse()`                             | `?Response`        | Returns the current response object                                                |
| `getModule()`                               | `?string`          | Returns the path to the currently executing module                                 |
| `getCurrentRoute()`                         | `?Route`           | Returns the matched route object for the current request                           |
| `getLocale()`                               | `?Locale`          | Returns the loaded locale object for the current request                           |
| `getException()`                            | `?Throwable`       | Returns the current exception if one has been set                                  |
| `setException(?Throwable $exception)`       | `void`             | Sets an exception on the session                                                   |


### Session Variables

The `WebSession` class also provides a simple key-value store for storing custom variables during the request's lifecycle,
this can be useful for passing data between pre-request scripts, modules, sections and post-request scripts:

| Method                                      | Return Type        | Description                                                                        |
|---------------------------------------------|--------------------|------------------------------------------------------------------------------------|
| `set(string $key, mixed $value)`            | `void`             | Stores a custom variable in the session                                            |
| `get(string $key)`                          | `mixed`            | Retrieves a custom variable from the session                                       |
| `exists(string $key)`                       | `bool`             | Checks if a custom variable exists in the session                                  |
| `unset(string $key)`                        | `void`             | Removes a custom variable from the session                                         |

 > Note: These variables are only stored in memory during the request and are not persisted across requests, they are
         ideal for sharing data between different parts of the request handling process without using global variables
         or other less clean methods.

Here's an example of using session variables to pass data from a pre-request script to a module:

```php
// pre_request/auth.php
<?php
    use DynamicalWeb\WebSession;
    
    $token = WebSession::getRequest()->getHeader('Authorization');
    if($token !== null)
    {
        $user = validateToken($token);
        WebSession::set('authenticated_user', $user);
    }
?>
```

```php
// dashboard.phtml
<?php
    use DynamicalWeb\WebSession;
    
    $user = WebSession::get('authenticated_user');
    if($user === null)
    {
        WebSession::getResponse()->setRedirect('/login');
        return;
    }
?>

<h1>Welcome, <?php \DynamicalWeb\Html\Functions::print($user->getName()); ?></h1>
```


## Routing

DynamicalWeb uses a configuration-based routing system where routes are defined in the Yaml configuration file, the
router matches incoming requests to configured routes based on the request path and method.


### Route Parameters

Routes can include dynamic path parameters enclosed in curly braces, these parameters are extracted from the actual
request path and made available through the request object:

```yaml
routes:
  - id: "user_profile"
    path: "/users/{id}"
    module: "user-profile.phtml"
    allowed_methods: [ GET ]
  - id: "user_post"
    path: "/users/{userId}/posts/{postId}"
    module: "user-post.phtml"
    allowed_methods: [ GET ]
```

Within the module you can access these parameters using the request object:

```php
<?php
    use DynamicalWeb\WebSession;
    
    $request = WebSession::getRequest();
    
    // For /users/123
    $userId = $request->getPathParameter('id');  // "123"
    
    // For /users/456/posts/789
    $userId = $request->getPathParameter('userId');  // "456"
    $postId = $request->getPathParameter('postId');  // "789"
    
    // Or get all path parameters at once
    $params = $request->getPathParameters();  // ['userId' => '456', 'postId' => '789']
?>
```


### Route Parameter Constraints

DynamicalWeb supports parameter constraints that restrict what values a path parameter can match, constraints are
specified after the parameter name separated by a colon:

```yaml
routes:
  - id: "user_by_id"
    path: "/users/{id:int}"
    module: "user.php"
    allowed_methods: [ GET ]
  - id: "article_by_slug"
    path: "/articles/{slug:slug}"
    module: "article.phtml"
    allowed_methods: [ GET ]
  - id: "resource_by_uuid"
    path: "/resources/{uuid:uuid}"
    module: "resource.phtml"
    allowed_methods: [ GET ]
```

The following built-in constraint shortcuts are available:

| Constraint                        | Pattern                                                                       | Description                                 |
|-----------------------------------|-------------------------------------------------------------------------------|---------------------------------------------|
| `int`, `integer`, `num`, `number` | `\d+`                                                                         | Matches one or more digits                  |
| `alpha`                           | `[a-zA-Z]+`                                                                   | Matches one or more alphabetic characters   |
| `alnum`, `alphanumeric`           | `[a-zA-Z0-9]+`                                                                | Matches one or more alphanumeric characters |
| `uuid`                            | `[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}` | Matches a standard UUID format              |
| `slug`                            | `[a-z0-9]+(?:-[a-z0-9]+)*`                                                    | Matches a URL-friendly slug                 |

You can also use a custom regex pattern as the constraint:

```yaml
routes:
  - id: "year_archive"
    path: "/archive/{year:[0-9]{4}}"
    module: "archive.phtml"
    allowed_methods: [ GET ]
```

If no constraint is specified, the parameter will match any value that doesn't contain a forward slash (`[^/]+`).


### Allowed Methods

Each route must specify which HTTP methods it accepts, if the incoming request method is not in the route's
`allowed_methods` array, DynamicalWeb will return a 405 Method Not Allowed response. The special value `*` can
be used to allow all HTTP methods:

```yaml
routes:
  # Allow all methods
  - id: "home"
    path: "/"
    module: "index.phtml"
    allowed_methods: [ "*" ]
    
  # Allow only GET
  - id: "about"
    path: "/about"
    module: "about.phtml"
    allowed_methods: [ GET ]
    
  # Allow GET and POST
  - id: "contact"
    path: "/contact"
    module: "contact.phtml"
    allowed_methods: [ GET, POST ]
```

The supported HTTP methods are: `GET`, `HEAD`, `POST`, `PUT`, `DELETE`, `CONNECT`, `OPTIONS`, `TRACE`


### Response Handlers

Response handlers allow you to define custom error pages for specific HTTP status codes, these are `.phtml` files
that will be rendered when DynamicalWeb encounters the corresponding error:

```yaml
router:
  response_handlers:
    404: "errors/404.phtml"
    500: "errors/500.phtml"
```

When a 404 Not Found error occurs (no route matches the request), DynamicalWeb will render the configured 404
handler module. When an unhandled exception occurs during module execution, DynamicalWeb will render the configured
500 handler module. Within these error handler modules, the `WebSession` is still available so you can access
the request information and the exception details:

```phtml
<!-- errors/404.phtml -->
<?php use DynamicalWeb\WebSession; ?>

<h1>Page Not Found</h1>
<p>The requested path <code><?php echo htmlspecialchars(WebSession::getRequest()->getPath()); ?></code> was not found.</p>
<a href="/">Go to Homepage</a>
```

```phtml
<!-- errors/500.phtml -->
<?php use DynamicalWeb\WebSession; ?>

<h1>Internal Server Error</h1>
<p>Something went wrong while processing your request.</p>
<a href="/">Go to Homepage</a>
```


## Localization

DynamicalWeb has built-in support for multi-language web applications through its localization system, this system
allows you to define translations in Yaml files and reference them in your templates using the `Functions::printl()`
method.


### Locale Files

Locale files are Yaml files that contain key-value pairs organized by section, the section names correspond to
the `locale_id` values configured on routes and sections:

```yaml
# en.yml
home:
  page_title: "Welcome Home"
  jumbotron_text: "Build web applications with ease"
  learn_more_button: "Learn More"

about:
  page_title: "About Us"
  heading: "About Our Application"
  feature_routing_title: "Routing"
  feature_routing_desc: "Define routes with parameters and constraints"

navbar:
  brand: "My Application"
  home_link: "Home"
  about_link: "About"
  contact_link: "Contact"

footer:
  copyright: "Copyright © 2024-2026 {company}. All rights reserved."
```

```yaml
# jp.yml
home:
  page_title: "ホームへようこそ"
  jumbotron_text: "簡単にウェブアプリケーションを構築"
  learn_more_button: "もっと詳しく"

about:
  page_title: "私たちについて"
  heading: "アプリケーションについて"
  feature_routing_title: "ルーティング"
  feature_routing_desc: "パラメータと制約付きのルートを定義"

navbar:
  brand: "マイアプリケーション"
  home_link: "ホーム"
  about_link: "アバウト"
  contact_link: "コンタクト"

footer:
  copyright: "著作権 © 2024-2026 {company}. 全著作権所有。"
```


### Locale Detection

When a request comes in, DynamicalWeb determines which locale to use based on the following priority:

 1. **Locale Cookie** — If the user has previously selected a locale by visiting `/dynaweb/language/{locale_id}`, the 
    selected locale is stored in a cookie and will be used for subsequent requests
 2. **Accept-Language Header** — If no cookie is set, DynamicalWeb will try to detect the user's preferred language
    from the `Accept-Language` header and match it against the configured locales
 3. **Default Locale** — If no match is found from the header, the configured `default_locale` from the application
    configuration is used
 4. **First Available** — If no default locale is configured, the first locale defined in the configuration is used


### Using Locales in Templates

Locale strings are accessed in templates using the `Functions::printl()` method, this method uses the active
locale section (determined by the route's or section's `locale_id`) to look up the translation key:

```phtml
<?php use DynamicalWeb\Html\Functions; ?>

<h1><?php Functions::printl('page_title'); ?></h1>
<p><?php Functions::printl('welcome_message', ['name' => 'John']); ?></p>
```

The placeholder replacement uses curly braces `{key}` syntax in the locale strings, for example a locale string
`"Hello {name}, you have {count} messages"` with replacements `['name' => 'John', 'count' => 5]` will produce
`"Hello John, you have 5 messages"`.

You can also access the locale object directly from the `WebSession` for more advanced use cases:

```php
<?php
    use DynamicalWeb\WebSession;
    
    $locale = WebSession::getLocale();
    
    // Check if a locale section exists
    if($locale->hasLocaleId('dashboard'))
    {
        // Check if a specific key exists
        if($locale->hasKey('dashboard', 'welcome'))
        {
            $string = $locale->getString('dashboard', 'welcome', ['name' => 'John']);
        }
    }
    
    // Get all section IDs
    $sections = $locale->getLocaleIds();
    
    // Get the current locale code
    $code = $locale->getLocaleCode();  // "en"
?>
```

To allow users to switch locales, you can create links to the built-in language endpoint:

```phtml
<a href="/dynaweb/language/en">English</a>
<a href="/dynaweb/language/cn">中文</a>
<a href="/dynaweb/language/jp">日本語</a>
```


## Static Resources

DynamicalWeb can serve static resource files such as CSS, JavaScript, images and fonts directly without needing to 
configure individual routes for them. Static resources are stored in the directory configured by the `resources`
property in the application configuration.

For example, if your resources directory is set to `ExampleBootstrap/WebResources` and it contains the following
files:

```
WebResources/
├── css/
│   ├── bootstrap.min.css
│   └── style.css
└── js/
    ├── bootstrap.bundle.min.js
    └── jquery.min.js
```

These files will be automatically accessible under the root path of your web application:

```html
<link rel="stylesheet" href="/css/bootstrap.min.css">
<link rel="stylesheet" href="/css/style.css">
<script src="/js/bootstrap.bundle.min.js"></script>
<script src="/js/jquery.min.js"></script>
```

DynamicalWeb will serve these files with appropriate `Content-Type` headers based on the file extension and will
also set caching headers like `Last-Modified`, `ETag` and `Cache-Control` to enable browser caching. If the APCu
extension is available, small resource files (up to 256KB) will be cached in APCu to further improve performance.

DynamicalWeb also protects against directory traversal attacks by sanitizing the requested path, any attempts to
access files outside the configured resources directory using `../` or similar patterns will be blocked.


## Pre and Post Request Scripts

Pre-request and post-request scripts allow you to execute PHP code before and after the matched module runs, this
is useful for implementing cross-cutting concerns like authentication, rate limiting, logging, and cleanup.

```yaml
application:
  name: "My Application"
  root: "MyApp/WebApplication"
  pre_request:
    - "middleware/auth.php"
    - "middleware/rate-limit.php"
  post_request:
    - "middleware/logging.php"
    - "middleware/cleanup.php"
```

The paths are relative to the `root` directory, and the scripts are executed in the order they are defined. During
execution, the `WebSession` is fully initialized so you have access to the request, response, and all other
session data.

Here's an example of a pre-request authentication script:

```php
// middleware/auth.php
<?php
    use DynamicalWeb\WebSession;
    use DynamicalWeb\Enums\ResponseCode;
    
    $publicRoutes = ['home', 'login', 'register'];
    $currentRoute = WebSession::getCurrentRoute();
    
    if($currentRoute !== null && !in_array($currentRoute->getId(), $publicRoutes))
    {
        $token = WebSession::getRequest()->getHeader('Authorization');
        if($token === null)
        {
            WebSession::getResponse()
                ->setStatusCode(ResponseCode::UNAUTHORIZED)
                ->setJson(['error' => 'Authentication required']);
            return;
        }
        
        WebSession::set('user', validateToken($token));
    }
?>
```


## XSS Protection

DynamicalWeb includes built-in XSS (Cross-Site Scripting) protection that can be configured through the `xss_level`
property in the application configuration. There are four levels of protection available:

| Level | Name       | Value | Headers Applied                                                                                          |
|-------|------------|-------|----------------------------------------------------------------------------------------------------------|
| 0     | `DISABLED` | 0     | No XSS protection headers are applied                                                                    |
| 1     | `LOW`      | 1     | `X-XSS-Protection: 1; mode=block`                                                                        |
| 2     | `MEDIUM`   | 2     | `Content-Security-Policy: default-src 'self'; report-uri /csp-report`                                    |
| 3     | `HIGH`     | 3     | `Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{nonce}'; report-uri /csp-report` |

At level 3 (HIGH), DynamicalWeb generates a unique nonce for each request that can be used in your script tags
to allow only explicitly authorized scripts to execute.

Additionally, the `Functions::print()` method HTML-escapes output by default using `htmlspecialchars()` with
`ENT_QUOTES | ENT_SUBSTITUTE` flags and UTF-8 encoding, which provides protection against XSS attacks at the
template level.


## Debug Panel

The debug panel is a development tool that, when enabled, injects an iFrame into the bottom of every HTML response
containing detailed information about the current request and the web application's state. To enable it, set
`debug_panel: true` in the application configuration.

 > Note: The debug panel adds a performance overhead and exposes internal application details, it should never
   be enabled in production environments.

The debug panel includes the following tabs of information:

| Tab        | Description                                                                                          |
|------------|------------------------------------------------------------------------------------------------------|
| App        | Application name, version, package, configuration details                                            |
| Request    | Request method, URL, path, host, HTTP version, headers, query/body/form/path parameters              |
| Response   | Response status code, content type, headers, cookies                                                 |
| Cookies    | All cookies from the request with their values                                                       |
| PHP        | PHP version, SAPI, memory usage, loaded configuration                                                |
| Extensions | List of all loaded PHP extensions                                                                    |
| Server     | Server variables (`$_SERVER` superglobal)                                                            |
| Constants  | PHP constants and their values                                                                       |
| Session    | PHP session data                                                                                     |
| Routes     | All configured routes with their paths, modules and allowed methods                                  |
| Sections   | All configured sections with their modules and locale IDs                                            |
| INI        | PHP INI directives and their values                                                                  |
| APCu       | APCu cache information including memory usage and cache entries                                      |
| Locale     | Current locale code, available locales, and a locale switcher                                        |
| Profiler   | File execution tracking showing which modules/sections were executed and their execution times       |
| OPcache    | OPcache status and configuration information                                                         |

DynamicalWeb also exposes a debug stats API endpoint at `/dynaweb/debug/stats` when the debug panel is enabled, this
returns a JSON object containing profiling data about the current application state.


## Built-in Pages

DynamicalWeb comes with several built-in pages that are served under the `/dynaweb/` path prefix, these pages
provide framework-level functionality and don't need to be configured in your application's routes:

| Path                     | Description                                                                                                   |
|--------------------------|---------------------------------------------------------------------------------------------------------------|
| `/dynaweb`               | An about page that displays information about the DynamicalWeb framework                                      |
| `/dynaweb/health`        | A health check endpoint that returns the application status, useful for load balancers and monitoring systems |
| `/dynaweb/language/{id}` | The locale switcher endpoint, sets a cookie with the selected locale and redirects back                       |
| `/dynaweb/debug/stats`   | Debug statistics API endpoint (only available when `debug_panel` is enabled)                                  |

DynamicalWeb also comes with default 404 and 500 error pages that will be used if you don't configure custom
response handlers in your router configuration.


## APCu Caching

DynamicalWeb uses the APCu (APC User Cache) extension when available to cache various data to improve performance
across requests. If APCu is not installed or is disabled, DynamicalWeb will work without it using in-process caches
that only persist for the duration of a single request. You can also explicitly disable APCu by setting
`disable_apcu: true` in the application configuration.

The following data is cached in APCu when available:

| Cached Data                 | Cache Key Pattern                         | TTL     | Description                                                    |
|-----------------------------|-------------------------------------------|---------|----------------------------------------------------------------|
| Web Configuration           | `dw_webcfg_{md5}`                         | 60s     | Parsed Yaml configuration to avoid re-parsing on every request |
| Locale Files                | `dw_locale_{md5}`                         | 300s    | Parsed locale Yaml files                                       |
| Language Detection          | `dw_lang_{accept_lang_md5}_{locales_md5}` | 3600s   | Accept-Language header detection results                       |
| Route Resolution            | `dw_rtres_{app_md5}_{method}_{path_md5}`  | default | Matched route results for specific request paths               |
| Route Regex Patterns        | `dw_route_regex_{route_md5}`              | default | Compiled regex patterns for route matching                     |
| Static Resource Existence   | `dw_static_res_{path_md5}`                | 60s     | Whether a static resource file exists on disk                  |
| Built-in Resource Existence | `dw_builtin_res_{path_md5}`               | 60s     | Whether a built-in resource file exists on disk                |
| File Metadata               | `dw_filemeta_{file_md5}`                  | 10s     | File modification time and size                                |
| File Content                | `dw_filecontent_{file_md5}`               | 3600s   | Content of small static files (≤ 256KB)                        |

In addition to APCu, DynamicalWeb also maintains in-process static caches for route regex patterns and resource
existence checks that persist for the duration of a single request.


## Cookies

DynamicalWeb provides a `Cookie` object for managing cookies in responses, you can set cookies on the response object
using either the convenience `setCookie()` method or by creating a `Cookie` object and adding it:

```php
<?php
    use DynamicalWeb\WebSession;
    use DynamicalWeb\Objects\Cookie;
    
    // Using the convenience method
    WebSession::getResponse()->setCookie(
        name: 'session_id',
        value: 'abc123',
        expires: time() + 3600,
        path: '/',
        domain: '',
        secure: true,
        httpOnly: true
    );
    
    // Using a Cookie object
    $cookie = new Cookie('preferences', json_encode(['theme' => 'dark']));
    $cookie->setExpires(time() + 86400 * 30);
    $cookie->setSecure(true);
    $cookie->setHttpOnly(true);
    
    WebSession::getResponse()->addCookie($cookie);
?>
```

The `Cookie` object has the following properties:

| Property   | Type     | Default | Description                                                                                 |
|------------|----------|---------|---------------------------------------------------------------------------------------------|
| `name`     | `string` | -       | The name of the cookie                                                                      |
| `value`    | `string` | -       | The value of the cookie                                                                     |
| `expires`  | `int`    | `0`     | Expiration time as a Unix timestamp, `0` means session cookie (expires when browser closes) |
| `path`     | `string` | `"/"`   | The path on the server where the cookie will be available                                   |
| `domain`   | `string` | `""`    | The domain that the cookie is available to, empty string means current domain               |
| `secure`   | `bool`   | `false` | When True, the cookie will only be transmitted over HTTPS connections                       |
| `httpOnly` | `bool`   | `false` | When True, the cookie is not accessible via JavaScript (`document.cookie`)                  |


## Deployment


### Docker

DynamicalWeb applications can be deployed using Docker, when you generate a project using `ncc project --generate=dynamicalweb`
a Dockerfile, docker-compose.yml, nginx.conf and supervisord.conf are generated for you. The Docker setup uses a
multi-stage build where the first stage compiles the ncc package and the second stage sets up a production environment
with PHP-FPM and Nginx managed by Supervisor.

```yaml
# docker-compose.yml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: com.example.bootstrap:1.0.0
    ports:
      - "8080:8080"
    environment:
      - PHP_MEMORY_LIMIT=256M
      - PHP_MAX_EXECUTION_TIME=60
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/dynaweb/health"]
      interval: 30s
      timeout: 10s
      retries: 3
```

The health check uses the built-in `/dynaweb/health` endpoint to verify the application is running and responsive.


### Nginx Configuration

DynamicalWeb requires all requests to be routed through a single entry point (`index.php`), this is achieved using
URL rewriting in the web server configuration. Here's an example Nginx configuration:

```nginx
server {
    listen 8080;
    server_name _;
    root /var/www/html;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

The key part is the `try_files` directive which routes all requests that don't match an existing file to
`index.php`, this allows DynamicalWeb's router to handle all incoming requests.


# License

DynamicalWeb is licensed under the MIT License, see [LICENSE](LICENSE) for more information.
Multiple licenses for the open-source components used in this project can be found at [LICENSE](LICENSE)