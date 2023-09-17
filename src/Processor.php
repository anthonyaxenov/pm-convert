<?php

declare(strict_types=1);

namespace PmConverter;

use Exception;
use InvalidArgumentException;
use JsonException;
use PmConverter\Converters\{
    ConverterContract,
    ConvertFormat};
use PmConverter\Exceptions\{
    CannotCreateDirectoryException,
    DirectoryIsNotReadableException,
    DirectoryIsNotWriteableException,
    DirectoryNotExistsException};

class Processor
{
    /**
     * Converter version
     */
    public const VERSION = '1.5.0';

    /**
     * @var string[] Paths to collection files
     */
    protected array $collectionPaths = [];

    /**
     * @var string Output path where to put results in
     */
    protected string $outputPath;

    /**
     * @var bool Flag to remove output directories or not before conversion started
     */
    protected bool $preserveOutput = false;

    /**
     * @var string[] Additional variables
     */
    protected array $vars;

    /**
     * @var ConvertFormat[] Formats to convert a collections into
     */
    protected array $formats;

    /**
     * @var ConverterContract[] Converters will be used for conversion according to choosen formats
     */
    protected array $converters = [];

    /**
     * @var Collection[] Collections that will be converted into choosen formats
     */
    protected array $collections = [];

    /**
     * @var int Initial timestamp
     */
    protected int $initTime;

    /**
     * @var int Initial RAM usage
     */
    protected int $initRam;

    /**
     * @var string Path to environment file
     */
    protected string $envFile;

    /**
     * @var Environment
     */
    protected Environment $env;

    /**
     * Constructor
     *
     * @param array $argv Arguments came from cli
     */
    public function __construct(protected array $argv)
    {
        $this->initTime = hrtime(true);
        $this->initRam = memory_get_usage(true);
    }

    /**
     * Parses an array of arguments came from cli
     *
     * @return void
     * @throws JsonException
     */
    protected function parseArgs(): void
    {
        if (count($this->argv) < 2) {
            die(implode(PHP_EOL, $this->usage()) . PHP_EOL);
        }
        foreach ($this->argv as $idx => $arg) {
            switch ($arg) {
                case '-f':
                case '--file':
                    $rawpath = $this->argv[$idx + 1];
                    $normpath = FileSystem::normalizePath($rawpath);
                    if (!FileSystem::isCollectionFile($normpath)) {
                        throw new InvalidArgumentException(
                            sprintf("not a valid collection:%s\t%s %s", PHP_EOL, $arg, $rawpath)
                        );
                    }
                    $this->collectionPaths[] = $this->argv[$idx + 1];
                    break;

                case '-o':
                case '--output':
                    if (empty($this->argv[$idx + 1])) {
                        throw new InvalidArgumentException('-o is required');
                    }
                    $this->outputPath = $this->argv[$idx + 1];
                    break;

                case '-d':
                case '--dir':
                    if (empty($this->argv[$idx + 1])) {
                        throw new InvalidArgumentException('a directory path is expected for -d (--dir)');
                    }
                    $rawpath = $this->argv[$idx + 1];
                    $files = array_filter(
                        FileSystem::dirContents($rawpath),
                        static fn($filename) => FileSystem::isCollectionFile($filename)
                    );
                    $this->collectionPaths = array_unique(array_merge($this?->collectionPaths ?? [], $files));
                    break;

                case '-e':
                case '--env':
                    $this->envFile = FileSystem::normalizePath($this->argv[$idx + 1]);
                    break;

                case '-p':
                case '--preserve':
                    $this->preserveOutput = true;
                    break;

                case '--http':
                    $this->formats[ConvertFormat::Http->name] = ConvertFormat::Http;
                    break;

                case '--curl':
                    $this->formats[ConvertFormat::Curl->name] = ConvertFormat::Curl;
                    break;

                case '--wget':
                    $this->formats[ConvertFormat::Wget->name] = ConvertFormat::Wget;
                    break;

                case '--v2.0':
                    $this->formats[ConvertFormat::Postman20->name] = ConvertFormat::Postman20;
                    break;

                case '--v2.1':
                    $this->formats[ConvertFormat::Postman21->name] = ConvertFormat::Postman21;
                    break;

                case '-a':
                case '--all':
                    foreach (ConvertFormat::cases() as $format) {
                        $this->formats[$format->name] = $format;
                    }
                    break;

                case '--var':
                    [$var, $value] = explode('=', trim($this->argv[$idx + 1]));
                    $this->vars[$var] = $value;
                    break;

                case '-v':
                case '--version':
                    die(implode(PHP_EOL, $this->version()) . PHP_EOL);

                case '-h':
                case '--help':
                    die(implode(PHP_EOL, $this->usage()) . PHP_EOL);
            }
        }
        if (empty($this->collectionPaths)) {
            throw new InvalidArgumentException('there are no collections to convert');
        }
        if (empty($this->outputPath)) {
            throw new InvalidArgumentException('-o is required');
        }
        if (empty($this->formats)) {
            $this->formats = [ConvertFormat::Http->name => ConvertFormat::Http];
        }
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
    protected function initOutputDirectory(): void
    {
        if (isset($this?->outputPath) && !$this->preserveOutput) {
            FileSystem::removeDir($this->outputPath);
        }
        FileSystem::makeDir($this->outputPath);
    }

    /**
     * Initializes converters according to choosen formats
     *
     * @return void
     */
    protected function initConverters(): void
    {
        foreach ($this->formats as $type) {
            $this->converters[$type->name] = new $type->value($this->preserveOutput);
        }
        unset($this->formats);
    }

    /**
     * Initializes collection objects
     *
     * @throws JsonException
     */
    protected function initCollections(): void
    {
        foreach ($this->collectionPaths as $collectionPath) {
            $collection = Collection::fromFile($collectionPath);
            $this->collections[$collection->name()] = $collection;
        }
        unset($this->collectionPaths, $content);
    }

    /**
     * Initializes environment object
     *
     * @return void
     * @throws JsonException
     */
    protected function initEnv(): void
    {
        if (!isset($this->envFile)) {
            return;
        }
        $content = file_get_contents(FileSystem::normalizePath($this->envFile));
        $content = json_decode($content, flags: JSON_THROW_ON_ERROR);
        if (!property_exists($content, 'environment') || empty($content?->environment)) {
            throw new JsonException("not a valid environment: $this->envFile");
        }
        $this->env = new Environment($content->environment);
        foreach ($this->vars as $var => $value) {
            $this->env[$var] = $value;
        }
        unset($this->vars, $this->envFile, $content, $var, $value);
    }

    /**
     * Begins a conversion
     *
     * @throws Exception
     */
    public function convert(): void
    {
        $this->parseArgs();
        $this->initOutputDirectory();
        $this->initConverters();
        $this->initCollections();
        $this->initEnv();
        $count = count($this->collections);
        $current = 0;
        $success = 0;
        print(implode(PHP_EOL, array_merge($this->version(), $this->copyright())) . PHP_EOL . PHP_EOL);
        foreach ($this->collections as $collectionName => $collection) {
            ++$current;
            printf("Converting '%s' (%d/%d):%s", $collectionName, $current, $count, PHP_EOL);
            foreach ($this->converters as $type => $exporter) {
                printf('> %s%s', strtolower($type), PHP_EOL);
                $outputPath = sprintf('%s%s%s', $this->outputPath, DIRECTORY_SEPARATOR, $collectionName);
                if (!empty($this->env)) {
                    $exporter->withEnv($this->env);
                }
                $exporter->convert($collection, $outputPath);
                printf('  OK: %s%s', $exporter->getOutputPath(), PHP_EOL);
            }
            print(PHP_EOL);
            ++$success;
        }
        unset($this->converters, $type, $exporter, $outputPath, $this->collections, $collectionName, $collection);
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
        printf("Converted %d/%d in %.2f $timeFmt using up to %.2f MiB RAM%s", $success, $count, $time, $ram, PHP_EOL);
    }

    /**
     * @return string[]
     */
    public function version(): array
    {
        return ["Postman collection converter v" . self::VERSION];
    }

    /**
     * @return string[]
     */
    public function copyright(): array
    {
        return [
            'Anthony Axenov (c) ' . date('Y') . ", MIT license",
            'https://git.axenov.dev/anthony/pm-convert'
        ];
    }

    /**
     * @return array
     */
    public function usage(): array
    {
        return array_merge($this->version(), [
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
        ], $this->copyright());
    }
}
