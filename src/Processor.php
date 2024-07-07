<?php

declare(strict_types=1);

namespace PmConverter;

use Exception;
use Generator;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use JsonException;
use PmConverter\Converters\Abstract\AbstractConverter;
use PmConverter\Converters\ConverterContract;
use PmConverter\Converters\ConvertFormat;
use PmConverter\Exceptions\CannotCreateDirectoryException;
use PmConverter\Exceptions\DirectoryIsNotReadableException;
use PmConverter\Exceptions\DirectoryIsNotWriteableException;
use PmConverter\Exceptions\DirectoryNotExistsException;
use PmConverter\Exceptions\IncorrectSettingsFileException;

/**
 * Main class
 */
class Processor
{
    /**
     * Converter version
     */
    public const VERSION = '1.6.1';

    /**
     * @var int Initial timestamp
     */
    protected readonly int $initTime;

    /**
     * @var int Initial RAM usage
     */
    protected readonly int $initRam;

    /**
     * @var Settings Settings                               (lol)
     */
    protected Settings $settings;

    /**
     * @var ConverterContract[] Converters will be used for conversion according to chosen formats
     */
    protected array $converters = [];

    /**
     * @var bool Do we need to save settings file and exit or not?
     */
    protected bool $needDumpSettings = false;

    /**
     * @var Environment
     */
    public Environment $env;

    /**
     * Constructor
     *
     * @param array $argv Arguments came from cli
     * @throws IncorrectSettingsFileException
     * @throws JsonException
     */
    public function __construct(protected readonly array $argv)
    {
        $this->initTime = hrtime(true);
        $this->initRam = memory_get_usage(true);
        $this->settings = Settings::init();
        $this->env = Environment::instance()
            ->readFromFile($this->settings->envFilepath())
            ->setCustomVars($this->settings->vars());
        $this->parseArgs();
        $this->needDumpSettings && $this->dumpSettingsFile();
    }

    /**
     * Parses an array of arguments came from cli
     *
     * @return void
     * @throws JsonException
     */
    protected function parseArgs(): void
    {
        $arguments = array_slice($this->argv, 1);
        $needHelp = count($arguments) === 0 && !$this->settings::fileExists();
        foreach ($arguments as $idx => $arg) {
            switch ($arg) {
                case '-f':
                case '--file':
                    $this->settings->addFilePath($this->argv[$idx + 1]);
                    break;

                case '-o':
                case '--output':
                    if (empty($this->argv[$idx + 1])) {
                        throw new InvalidArgumentException('-o is required');
                    }
                    $this->settings->setOutputPath($this->argv[$idx + 1]);
                    break;

                case '-d':
                case '--dir':
                    if (empty($this->argv[$idx + 1])) {
                        throw new InvalidArgumentException('a directory path is expected for -d (--dir)');
                    }
                    $this->settings->addDirPath($this->argv[$idx + 1]);
                    break;

                case '-e':
                case '--env':
                    $this->settings->setEnvFilepath($this->argv[$idx + 1]);
                    break;

                case '-p':
                case '--preserve':
                    $this->settings->setPreserveOutput(true);
                    break;

                case '--http':
                    $this->settings->addFormat(ConvertFormat::Http);
                    break;

                case '--curl':
                    $this->settings->addFormat(ConvertFormat::Curl);
                    break;

                case '--wget':
                    $this->settings->addFormat(ConvertFormat::Wget);
                    break;

                case '--v2.0':
                    $this->settings->addFormat(ConvertFormat::Postman20);
                    break;

                case '--v2.1':
                    $this->settings->addFormat(ConvertFormat::Postman21);
                    break;

                case '-a':
                case '--all':
                    foreach (ConvertFormat::cases() as $format) {
                        $this->settings->addFormat($format);
                    }
                    break;

                case '--var':
                    //TODO split by first equal sign
                    $this->env->setCustomVar(...explode('=', trim($this->argv[$idx + 1])));
                    break;

                case '--dev':
                    $this->settings->setDevMode(true);
                    break;

                case '--dump':
                    $this->needDumpSettings = true;
                    break;

                case '-v':
                case '--version':
                    die(implode(EOL, $this->version()) . EOL);

                case '-h':
                case '--help':
                    $needHelp = true;
                    break;
            }
        }
        if ($needHelp) {
            die(implode(EOL, $this->usage()) . EOL);
        }
        if (empty($this->settings->collectionPaths())) {
            throw new InvalidArgumentException('there are no collections to convert');
        }
        if (empty($this->settings->outputPath())) {
            throw new InvalidArgumentException('-o is required');
        }
        if (empty($this->settings->formats())) {
            $this->settings->addFormat(ConvertFormat::Http);
        }
    }

    /**
     * Handles input command
     *
     * @return void
     * @throws CannotCreateDirectoryException
     * @throws DirectoryIsNotReadableException
     * @throws DirectoryIsNotWriteableException
     * @throws DirectoryNotExistsException
     * @throws JsonException
     * @throws IncorrectSettingsFileException
     */
    public function handle(): void
    {
        $this->prepareOutputDirectory();
        $this->initConverters();
        $this->convert();
    }

    /**
     * Writes all settings into file if --dump provided
     *
     * @return never
     */
    #[NoReturn]
    protected function dumpSettingsFile(): never
    {
        $answer = 'o';
        if ($this->settings::fileExists()) {
            echo 'Settings file already exists: ' . $this->settings::filepath() . EOL;
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
        $this->settings->dump($this->env->customVars());
        printf("Arguments has been converted into settings file:%s\t%s%s", EOL, $this->settings::filepath(), EOL);
        die('Review and edit it if needed.' . EOL);
    }

    /**
     * Initializes output directory
     *
     * @return void
     * @throws CannotCreateDirectoryException
     * @throws DirectoryIsNotWriteableException
     * @throws DirectoryNotExistsException
     * @throws DirectoryIsNotReadableException
     */
    protected function prepareOutputDirectory(): void
    {
        if (!$this->settings->isPreserveOutput()) {
            FileSystem::removeDir($this->settings->outputPath());
        }
        FileSystem::makeDir($this->settings->outputPath());
    }

    /**
     * Initializes converters according to chosen formats
     *
     * @return void
     */
    protected function initConverters(): void
    {
        foreach ($this->settings->formats() as $type) {
            $this->converters[$type->name] = new $type->value($this->settings->isPreserveOutput());
        }
        unset($this->formats);
    }

    /**
     * Generates collections from settings
     *
     * @return Generator<Collection>
     * @throws JsonException
     */
    protected function newCollection(): Generator
    {
        foreach ($this->settings->collectionPaths() as $collectionPath) {
            yield Collection::fromFile($collectionPath);
        }
    }

    /**
     * Begins a conversion
     *
     * @throws JsonException
     */
    public function convert(): void
    {
        $count = count($this->settings->collectionPaths());
        $current = $success = 0;
        $collection = null;
        print(implode(EOL, array_merge($this->version(), $this->copyright())) . EOL . EOL);
        foreach ($this->newCollection() as $collection) {
            ++$current;
            printf("Converting '%s' (%d/%d):%s", $collection->name(), $current, $count, EOL);
            foreach ($this->converters as $type => $converter) {
                /** @var AbstractConverter $converter */
                printf('> %s%s', strtolower($type), EOL);
                $outputPath = sprintf('%s%s%s', $this->settings->outputPath(), DS, $collection->name());
                try {
                    $converter = $converter->to($outputPath);
                    $converter = $converter->convert($collection);
                    $converter->flush();
                    printf('  OK: %s%s', $converter->getOutputPath(), EOL);
                } catch (Exception $e) {
                    printf('  ERROR %s: %s%s', $e->getCode(), $e->getMessage(), EOL);
                    if ($this->settings->isDevMode()) {
                        array_map(static fn ($line) => printf('  %s%s', $line, EOL), $e->getTrace());
                    }
                }
            }
            print(EOL);
            ++$success;
        }
        unset($this->converters, $type, $converter, $outputPath, $this->collections, $collectionName, $collection);
        $this->printStats($success, $current);
    }

    /**
     * Outputs some statistics
     *
     * @param int $success
     * @param int $count
     * @return void
     */
    protected function printStats(int $success, int $count): void
    {
        $time = (hrtime(true) - $this->initTime) / 1_000_000;
        $timeFmt = 'ms';
        if ($time > 1000) {
            $time /= 1000;
            $timeFmt = 'sec';
        }
        $ram = (memory_get_peak_usage(true) - $this->initRam) / 1024 / 1024;
        printf("Converted %d/%d in %.2f $timeFmt using up to %.2f MiB RAM%s", $success, $count, $time, $ram, EOL);
    }

    /**
     * @return string[]
     */
    public static function version(): array
    {
        return ['Postman collection converter v' . self::VERSION];
    }

    /**
     * @return string[]
     */
    public static function copyright(): array
    {
        $years = ($year = (int)date('Y')) > 2023 ? "2023 - $year" : $year;
        return [
            "Anthony Axenov (c) $years, MIT license",
            'https://git.axenov.dev/anthony/pm-convert'
        ];
    }

    /**
     * @return array
     */
    public static function usage(): array
    {
        return array_merge(static::version(), [
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
        ], static::copyright());
    }
}
