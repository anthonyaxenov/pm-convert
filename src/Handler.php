<?php

declare(strict_types = 1);

namespace PmConverter;

use JsonException;
use PmConverter\Enums\ArgumentNames as AN;

class Handler
{
    /**
     * @var array Ready to use arguments
     */
    protected array $arguments;

    /**
     * @var Settings Settings read from file and merged with provided in cli
     */
    protected Settings $settings;

    /**
     * @var Environment Environment laoded from file with custom vars provided
     */
    protected Environment $env;

    /**
     * @var Processor Object that do convertions according to settings
     */
    protected Processor $processor;

    /**
     * Initializes main flow
     *
     * @param array $argv Raw arguments passed from cli
     * @return void
     * @throws JsonException
     * @throws \Exception
     */
    public function init(array $argv): void
    {
        $this->arguments = (new ArgumentParser($argv))->parsed();

        if (!empty($this->arguments[AN::Help])) {
            self::printHelp();
            exit;
        }

        if (!empty($this->arguments[AN::Version])) {
            self::printVersion();
            exit;
        }

        $this->settings = new Settings();
        $this->settings->loadFromFile($this->arguments[AN::Config] ?? null);
        $this->settings->override($this->arguments);

        if (empty($this->settings->collectionPaths())) {
            throw new \Exception('at least 1 collection file must be defined');
        }

        if (!empty($arguments[AN::Dump])) {
            $this->handleSettingsDump();
        }

        $this->env = Environment::instance()
            ->readFromFile($this->settings->envFilepath())
            ->setCustomVars($this->settings->vars());
    }

    /**
     * Starts convertions
     *
     * @return void
     * @throws Exceptions\CannotCreateDirectoryException
     * @throws Exceptions\DirectoryIsNotReadableException
     * @throws Exceptions\DirectoryIsNotWriteableException
     * @throws Exceptions\DirectoryNotExistsException
     * @throws Exceptions\IncorrectSettingsFileException
     * @throws JsonException
     */
    public function start(): void
    {
        $this->processor = new Processor($this->settings, $this->env);
        $this->processor->start();
    }

    /**
     * Handles settings file saving when requested by --dump
     *
     * @return never
     */
    protected function handleSettingsDump(): never
    {
        $answer = 'o';

        if ($this->settings->fileExists()) {
            echo 'Settings file already exists: ' . $this->settings->filePath() . EOL;
            echo 'Do you want to (o)verwrite it, (b)ackup it and create new one or (c)ancel (default)?' . EOL;
            $answer = strtolower(trim(readline('> ')));
        }

        if (!in_array($answer, ['o', 'b'])) {
            die('Current settings file has not been changed' . EOL);
        }

        if ($answer === 'b') {
            $filepath = $this->settings->backup();
            printf("Settings file has been backed up to file:%s\t%s%s", EOL, $filepath, EOL);
        }

        $this->settings->dump();
        printf("Arguments has been converted into settings file:%s\t%s%s", EOL, $this->settings->filePath(), EOL);
        die('Review and edit it if needed.' . EOL);
    }

    /**
     * Returns usage help strings
     *
     * @return array
     */
    protected static function help(): array
    {
        return array_merge(self::version(), [
            'Usage:',
            "\t./pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]",
            "\tphp pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]",
            "\tcomposer pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]",
            "\t./vendor/bin/pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]",
            '',
            'Possible ARGUMENTS:',
            "\t-f, --file          - a PATH to a single collection file to convert from",
            "\t-d, --dir           - a PATH to a directory with collections to convert from",
            "\t-o, --output        - a directory OUTPUT_PATH to put results in",
            "\t-e, --env           - use environment file with variables to replace in requests",
            "\t--var \"NAME=VALUE\"  - force replace specified env variable called NAME with custom VALUE",
            "\t-p, --preserve      - do not delete OUTPUT_PATH (if exists)",
            "\t    --dump          - convert provided arguments into settings file in `pwd",
            "\t-h, --help          - show this help message and exit",
            "\t-v, --version       - show version info and exit",
            '',
            'If no ARGUMENTS passed then --help implied.',
            'If both -f and -d are specified then only unique set of files from both arguments will be converted.',
            '-f or -d are required to be specified at least once, but each may be specified multiple times.',
            'PATH must be a valid path to readable json-file or directory.',
            'OUTPUT_PATH must be a valid path to writeable directory.',
            'If -o or -e was specified several times then only last one will be used.',
            '',
            'Possible FORMATS:',
            "\t--http     - generate raw *.http files (default)",
            "\t--curl     - generate shell scripts with curl command",
            "\t--wget     - generate shell scripts with wget command",
            "\t--v2.0     - convert from Postman Collection Schema v2.1 into v2.0",
            "\t--v2.1     - convert from Postman Collection Schema v2.0 into v2.1",
            "\t-a, --all  - convert to all of formats listed above",
            '',
            'If no FORMATS specified then --http implied.',
            'Any of FORMATS can be specified at the same time or replaced by --all.',
            '',
            'Example:',
            "    ./pm-convert \ ",
            "        -f ~/dir1/first.postman_collection.json \ ",
            "        --directory ~/team \ ",
            "        --file ~/dir2/second.postman_collection.json \ ",
            "        --env ~/localhost.postman_environment.json \ ",
            "        -d ~/personal \ ",
            "        --var \"myvar=some value\" \ ",
            "        -o ~/postman_export \ ",
            "       --all",
            "",
        ], self::copyright());
    }

    /**
     * Prints usage help message in stdout
     *
     * @return void
     */
    public static function printHelp(): void
    {
        self::printArray(self::help());
    }

    /**
     * Returns version strings
     *
     * @return string[]
     */
    protected static function version(): array
    {
        return ['Postman collection converter v' . PM_VERSION, ''];
    }

    /**
     * Prints version message in stdout
     *
     * @return void
     */
    public static function printVersion(): void
    {
        self::printArray(self::version());
    }

    /**
     * Returns copyright strings
     *
     * @return string[]
     */
    protected static function copyright(): array
    {
        return [
            'Anthony Axenov (c) 2023 - ' . (int)date('Y') . ', MIT license',
            'https://git.axenov.dev/anthony/pm-convert',
            '',
        ];
    }

    /**
     * Prints copyright message in stdout
     *
     * @return void
     */
    public static function printCopyright(): void
    {
        self::printArray(self::copyright());
    }

    /**
     * Prints an arrays of string to stdout
     *
     * @param ...$strings
     * @return void
     */
    protected static function printArray(...$strings): void
    {
        fwrite(STDOUT, implode("\n", array_merge(...$strings)));
    }
}
