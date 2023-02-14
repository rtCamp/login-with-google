Composer Downloads Plugin
===========================

The "Downloads" plugin allows you to download extra files (`*.zip` or `*.tar.gz`) and extract them within your package.

This is an updated version of [lastcall/composer-extra-files](https://github.com/LastCallMedia/ComposerExtraFiles/).
It adds integration tests, fixes some bugs, and makes a few other improvements. Some of the
configuration options have changed, so it has been renamed to prevent it from conflicting in real-world usage.

## Example

Suppose you publish a PHP package `foo/bar` which relies on an external artifact `examplelib-0.1.zip`. Place this configuration in the `composer.json` for `foo/bar`:

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-downloads-plugin": "~2.1"
  },
  "extra": {
    "downloads": {
      "examplelib": {
        "url": "https://example.com/examplelib-0.1.zip",
        "path": "extern/examplelib",
        "ignore": ["test", "doc", ".*"]
      }
    }
  }
}
```

When a downstream user of `foo/bar` runs `composer install`, it will fetch and extract the zip file, creating `vendor/foo/bar/extern/examplelib`. 

## Evaluation

The primary strengths of `composer-downloads-plugin` are:

* __Simple__: It downloads a URL (ZIP/TAR file) and extracts it. It only needs to know two things: *what to download* (`url`) and *where to put it* (`path`). It runs as pure-PHP without any external dependencies.
* __Fast__: The logic does not require scanning, indexing, or mapping any large registries. The download system uses `composer`'s built-in cache.
* __Isolated__: As the author of a package `foo/bar`, you define the content under the `vendor/foo/bar` folder. When others use `foo/bar`, there is no need for special instructions, no root-level configuration, no interaction with other packages.

The "Downloads" plugin is only a download mechanism. Use it to *assimilate* an external resource as part of a `composer` package.

The "Downloads" plugin is __not__ a *dependency management system*. There is no logic to scan registries, resolve transitive dependencies, identify version-conflicts, etc among diverse external resources.  If you need that functionality, then you may want a *bridge* to integrate `composer` with an external dependency management tool. A few good bridges to consider:

* [Asset Packagist](https://asset-packagist.org/)
* [Composer Asset Plugin](https://github.com/fxpio/composer-asset-plugin)
* [Composer Bower Plugin](https://github.com/php-kit/composer-bower-plugin)
* [Foxy](https://github.com/fxpio/foxy)

## Configuration: Properties

The `extra.downloads` section contains a list of files to download. Each extra-file has a symbolic ID (e.g. `examplelib` above) and some mix of properties:

* `url`: The URL to fetch the content from.

* `path`: The releative path where content will be extracted.

* `type`: (*Optional*) Determines how the download is handled
    * `archive`: The `url` references a zip or tarball which should be extracted at the given `path`. (Default for URLs involving `*.zip`, `*.tar.gz`, or `*.tgz`.)
    * `file`: The `url` should be downloaded to the given `path`. (Default for all other URLs.)
    * `phar`: The `url` references a PHP executable which should be installed at the given `path`.

* `ignore`: (*Optional*) A list of a files that should be omited from the extracted folder. (This supports a subset of `.gitignore` notation.)

* `version`: (*Optional*) A version number for the downloaded artifact. This has no functional impact on the lifecycle of the artifact, but
   it can affect the console output, and it can be optionally used as a variable when setting `url` or `path`.

Values in `url` and `path` support the following variables:

* `{$id}`: The symbolic identifier of the download. (In the introductory example, it would be `examplelib`.)
* `{$version}`: The displayed/simulated/pretty version number of the package.

## Configuration: Defaults

You may set default properties for all downloads. Place them under `*`, as in:

```json
{
  "extra": {
    "downloads": {
      "*": {
        "path": "bower_components/{$id}",
        "ignore": ["test", "tests", "doc", "docs"]
      },
      "jquery": {
        "url": "https://github.com/jquery/jquery-dist/archive/1.12.4.zip"
      },
      "jquery-ui": {
        "url": "https://github.com/components/jqueryui/archive/1.12.1.zip"
      }
    }
  }
}
```

This example will:

* Create `bower_components/jquery` (based on jQuery 1.12.4), minus any test/doc folders.
* Create `bower_components/jquery-ui` (based on jQueryUI 1.12.1), minus any test/doc folders.

## Tips

* In each downloaded folder, this plugin will create a small metadata folder (`.composer-downloads`) to track the origin of the current code. If you modify the `composer.json` to use a different URL, then it will re-download the file.

* Download each extra file to a distinct `path`. Don't try to download into overlapping paths. (*This has not been tested, but I expect downloads are not well-ordered, and you may find that updates require re-downloading.*)

* What should you do if you *normally* download the extra-file as `*.tar.gz` but sometimes (for local dev) need to grab bleeding edge content from somewhere else?  Simply delete the autodownloaded folder and replace it with your own.  `composer-downloads-plugin` will detect that conflict (by virtue of the absent `.composer-downloads`) and leave your code in place (until you choose to get rid of it). To switch back, you can simply delete the code and run `composer install` again.

## Known Limitations

If you use `downloads` in a root-project (or in symlinked dev repo), it will create+update downloads, but it will not remove orphaned items automatically.  This could be addressed by doing a file-scan for `.composer-downloads` (and deleting any orphan folders).  Since the edge-case is not particularly common right now, and since a file-scan could be time-consuming, it might make sense as a separate subcommand.

I believe the limitation does *not* affect downstream consumers of a dependency. In that case, the regular `composer` install/update/removal mechanics should take care of any nested downloads.

## Automated Tests

The `tests/` folder includes unit-tests and integration-tests written with
PHPUnit.  Each integration-test generates a new folder/project with a
plausible, representative `composer.json` file and executes `composer
install`.  It checks the output has the expected files.

To run the tests, you will need `composer` and `phpunit` in the `PATH`.

```
[~/src/composer-downloads-plugin] which composer
/Users/myuser/bin/composer

[~/src/composer-downloads-plugin] which phpunit
/Users/myuser/bin/phpunit

[~/src/composer-downloads-plugin] phpunit
PHPUnit 5.7.27 by Sebastian Bergmann and contributors.

.....                                                               5 / 5 (100%)

Time: 40.35 seconds, Memory: 10.00MB

OK (5 tests, 7 assertions)
```

The integration tests can be a bit large/slow. To monitor the tests more
closesly, set the `DEBUG` variable, as in:

```
[~/src/composer-downloads-plugin] env DEBUG=2 phpunit
```

## Local Dev Harness

What if you want to produce an environment which uses the current plugin
code - one where you can quickly re-run `composer` commands while
iterating on code?

You may use any of the integration-tests to initialize a baseline
environment:

```
env USE_TEST_PROJECT=$HOME/src/myprj DEBUG=2 phpunit tests/SniffTest.php
```
