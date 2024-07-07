# Postman collection converter

Convert your Postman collections into different formats.

Very fast.  
Offline.  
Without 3rd-party dependencies.

These formats are supported for now: `http`, `curl`, `wget`.

> This project has been started and quickly written in my spare time to solve one exact problem in one NDA-project,
> so it may contain stupid errors and (for sure) doesn't cover all possible cases according to collection schema.
> Feel free to propose your improvements.

Versions older than the latest are not supported, only current one is.
If you found an error in old version please ensure if an error you found has been fixed in latest version.
So please always use the latest version of `pm-convert`.

## Supported features

* collection schemas [**v2.1**](https://schema.postman.com/json/collection/v2.1.0/collection.json) and [**v2.0**](https://schema.postman.com/json/collection/v2.0.0/collection.json);
* replace vars in requests by stored in collection and environment file;
* export one or several collections (or even whole directories) into one or all of formats supported at the same time;
* all headers (including disabled for `http`-format);
* `json` body (forces header `Content-Type` to `application/json`);
* `formdata` body (including disabled fields for `http`-format; forces header `Content-Type` to `multipart/form-data`)

## Planned features

- support as many as possible/necessary of authentication kinds (_currently only `Bearer` supported_);
- support as many as possible/necessary of body formats (_currently only `json` and `formdata` supported_);
- documentation generation support (markdown) with response examples (if present) (#6);
- maybe some another convert formats (like httpie or something...);
- better logging;
- 90%+ test coverage, phpcs, psalm, etc.;
- web version.

## Install and upgrade

```
composer global r axenov/pm-convert   # install
composer global u axenov/pm-convert   # upgrade
```

Make sure your `~/.config/composer/vendor/bin` is in `$PATH` env:

```
echo $PATH | grep --color=auto 'composer'
# if not then execute this command and add it into ~/.profile:
export PATH="$PATH:~/.config/composer/vendor/bin"
```

## Usage

```
$ pm-convert --help
Postman collection converter
Usage:
        ./pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]
        php pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]
        composer pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]
        ./vendor/bin/pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]

Possible ARGUMENTS:
        -f, --file          - a PATH to a single collection file to convert from
        -d, --dir           - a PATH to a directory with collections to convert from
        -o, --output        - a directory OUTPUT_PATH to put results in
        -e, --env           - use environment file with variables to replace in requests
        --var "NAME=VALUE"  - force replace specified env variable called NAME with custom VALUE
        -p, --preserve      - do not delete OUTPUT_PATH (if exists)
            --dump          - convert provided arguments into settings file in `pwd`
        -h, --help          - show this help message and exit
        -v, --version       - show version info and exit

If no ARGUMENTS passed then --help implied.
If both -f and -d are specified then only unique set of files from both arguments will be converted.
-f or -d are required to be specified at least once, but each may be specified multiple times.
PATH must be a valid path to readable json-file or directory.
OUTPUT_PATH must be a valid path to writeable directory.
If -o or -e was specified several times then only last one will be used.

Possible FORMATS:
        --http     - generate raw *.http files (default)
        --curl     - generate shell scripts with curl command
        --wget     - generate shell scripts with wget command
        --v2.0     - convert from Postman Collection Schema v2.1 into v2.0
        --v2.1     - convert from Postman Collection Schema v2.0 into v2.1
        -a, --all  - convert to all of formats listed above

If no FORMATS specified then --http implied.
Any of FORMATS can be specified at the same time or replaced by --all.

Example:
    ./pm-convert \
        -f ~/dir1/first.postman_collection.json \
        --directory ~/team \
        --file ~/dir2/second.postman_collection.json \
        --env ~/localhost.postman_environment.json \
        -d ~/personal \
        --var "myvar=some value" \
        -o ~/postman_export \
        --all
```

### Notices

1. Result of `pm-convert` execution is bunch of generated files.
   Most likely they will contain errors such as not interpolated `{{variables}}` values (due to missed ones in collection),
   wrong command format or `GET`s with bodies.
   You must review any generated file before using.
2. Make sure every (I mean _every_) collection (not collection file), its folders and/or requests has unique names.
   If not, you can rename them in Postman or convert collections with similar names into different directories.
   Otherwise any generated file may be accidently overwritten by another one.

## Notes about variable interpolation

1. You can use -e to tell where to find variables to replace in requests.
2. You can use one or several --var to replace specific env variables to your own value.
3. Correct syntax is `--var "NAME=VALUE"`. `NAME` may be in curly braces like `{{NAME}}`.
4. Since -e is optional, a bunch of `--var` will emulate an environment. Also it does not matter if there is `--var` in environment file you provided or not.
5. Even if you (not) provided -e and/or `--var`, any of variable may still be overridden from collection (if any), so last ones has top priority.

### Notes about conversion between Postman Schemas

You can use `--v2.1` to convert v2.1 into v2.1 (and this is not a typo).
Same applies to `--v2.0`.

There is a case when a collection has been exported via Postman API.
In such case collection itself places in single root object called `collection` like this:

```
{
   "collection": {
      // your actual collection here
   }
}
```

So, pm-convert will just raise actual data up on top level and write into disk.

## Settings file

You may want to specify parameters once and just use them everytime without explicit defining arguments to `pm-convert`.

This might be done in several ways.

1. Save this file as `pm-convert-settings.json` in your project directory:

   ```json
   {
       "directories": [],
       "files": [],
       "environment": "",
       "output": "",
       "preserveOutput": false,
       "formats": [],
       "vars": {}
   }
   ```
   
   Fill it with values you need.

2. Add `--dump` at the end of your command and all arguments you provided will be converted and saved as
   `pm-convert-settings.json` in your curent working directory. For example in `--help` file will contain this:

   ```json
   {
       "directories": [
           "~/team",
           "~/personal"
       ],
       "files": [
           "~/dir1/first.postman_collection.json",
           "~/dir2/second.postman_collection.json"
       ],
       "environment": "~/localhost.postman_environment.json",
       "output": "~/postman_export",
       "preserveOutput": false,
       "formats": [
           "http",
           "curl",
           "wget",
           "v2.0",
           "v2.1"
       ],
       "vars": {
           "myvar": "some value"
       }
   }
   ```

   If settings file already exists then you will be asked what to do: overwrite it, back it up or exit.

Once settings file saved in current you can just run `pm-convert`.
Settings will be applied like if you pass them explicitly via arguments.

## How to implement a new format

1. Create new namespace in `./src/Converters` and name it according to format of your choice.
2. Create two classes for converter and request object which extends `Converters\Abstract\Abstract{Converter, Request}` respectively.
3. Change constants values in your new request class according to format you want to implement.
4. Add your converter class name in `Converters\ConvertFormat`.
5. Write your own logic in converter, write new methods and override abstract ones.

## License

You can use, share and develop this project according to [MIT License](LICENSE).

Postman is [protected legal trademark](https://www.postman.com/legal/trademark-policy/) of Postman, Inc.

-----

## Disclaimer

I'm **not** affiliated with Postman, Inc. in any way.

I'm just a backend developer who is forced to use this javascripted gigachad-shitmonster.

So the goal of this project is to:
* take the data and its synchronization under own transparent control;
* easily migrate to something more RAM tolerant and productive, easier and free to use;
* get off the needle of the vendor lock, strict restrictions for teams and not to pay incredible $$$ for heavy useless WYSIWYGs;
* give YOU these opportunities.
