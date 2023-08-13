# Postman collection converter

Convert your Postman collections into different formats.

Very fast.  
Offline.  
Without 3rd-party dependencies.

These formats are supported for now: `http`, `curl`, `wget`.

> This project was quickly written in my spare time to solve one exact problem in one NDA-project, so it may
> contain stupid errors and (for sure) doesn't cover all possible cases according to collection schema.
> So feel free to propose your improvements.

## Supported features

* [collection schema **v2.1**](https://schema.postman.com/json/collection/v2.1.0/collection.json);
* `Bearer` auth;
* replace vars in requests by stored in collection and environment file;
* export one or several collections (or even whole directories) into one or all of formats supported at the same time;
* all headers (including disabled for `http`-format);
* `json` body (forces header `Content-Type` to `application/json`);
* `formdata` body (including disabled fields for `http`-format; forces header `Content-Type` to `multipart/form-data`)

## Planned features

- support as many as possible/necessary of authentication kinds (_currently only `Bearer` supported_);
- support as many as possible/necessary of body formats (_currently only `json` and `formdata`_);
- documentation generation support (markdown) with responce examples (if present);
- maybe some another convert formats (like httpie or something...);
- better logging;
- tests, phpcs, psalm, etc.;
- web version.

## Install and upgrade

```
composer global r axenov/pm-convert
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
        -f, --file       - a PATH to single collection located in PATH to convert from
        -d, --dir        - a directory with collections located in COLLECTION_FILEPATH to convert from
        -o, --output     - a directory OUTPUT_PATH to put results in
        -e, --env        - use environment file with variable values to replace in request
        -p, --preserve   - do not delete OUTPUT_PATH (if exists)
        -h, --help       - show this help message and exit
        -v, --version    - show version info and exit

If no ARGUMENTS passed then --help implied.
If both -f and -d are specified then only unique set of files will be converted.
-f or -d are required to be specified at least once, but each may be specified multiple times.
PATH must be a valid path to readable json-file or directory.
OUTPUT_PATH must be a valid path to writeable directory.
If -o is specified several times then only last one will be used.
If -e is specified several times then only last one will be used.
If -e is not specified then only collection vars will be replaced (if any).

Possible FORMATS:
        --http   - generate raw *.http files (default)
        --curl   - generate shell scripts with curl command
        --wget   - generate shell scripts with wget command
If no FORMATS specified then --http implied.
Any of FORMATS can be specified at the same time.

Example:
    ./pm-convert \ 
        -f ~/dir1/first.postman_collection.json \ 
        --directory ~/team \ 
        --file ~/dir2/second.postman_collection.json \ 
        --env ~/localhost.postman_environment.json \ 
        -d ~/personal \ 
        -o ~/postman_export 

```
### Notice

Make sure every (I mean _every_) collection (not collection file), its folders and/or requests has unique names.
If not, you can rename them in Postman or convert collections with similar names into different directories.
Otherwise converted files may be overwritten by each other.

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
