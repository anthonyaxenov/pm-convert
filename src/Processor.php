<?php

declare(strict_types=1);

namespace PmConverter;

use Exception;
use InvalidArgumentException;
use JsonException;
use PmConverter\Exceptions\CannotCreateDirectoryException;
use PmConverter\Exceptions\DirectoryIsNotReadableException;
use PmConverter\Exceptions\DirectoryIsNotWriteableException;
use PmConverter\Exceptions\DirectoryNotExistsException;
use PmConverter\Exporters\ConverterContract;
use PmConverter\Exporters\ConvertFormat;

/**
 *
 */
class Processor
{
    /**
     * Converter version
     */
    public const VERSION = '1.1.0';

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
     * @var ConvertFormat[] Formats to convert a collections into
     */
    protected array $formats;

    /**
     * @var ConverterContract[] Converters will be used for conversion according to choosen formats
     */
    protected array $converters = [];

    /**
     * @var object[] Collections that will be converted into choosen formats
     */
    protected array $collections = [];

    /**
     * @var int Initial timestamp
     */
    protected int $init_time;

    /**
     * @var int Initial RAM usage
     */
    protected int $init_ram;

    /**
     * Constructor
     *
     * @param array $argv Arguments came from cli
     */
    public function __construct(protected array $argv)
    {
        $this->init_time = hrtime(true);
        $this->init_ram = memory_get_usage(true);
    }

    /**
     * Parses an array of arguments came from cli
     *
     * @return void
     * @throws DirectoryIsNotWriteableException
     * @throws DirectoryNotExistsException
     * @throws DirectoryIsNotReadableException
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
                    $path = FileSystem::normalizePath($this->argv[$idx + 1]);
                    if (empty($path) || !str_ends_with($path, '.json') || !file_exists($path) || !is_readable($path)) {
                        throw new InvalidArgumentException('a valid json-file path is expected for -f (--file)');
                    }
                    $this->collectionPaths[] = $this->argv[$idx + 1];
                    break;
                case '-o':
                case '--output':
                    if (empty($this->argv[$idx + 1])) {
                        throw new InvalidArgumentException('-o expected');
                    }
                    $this->outputPath = $this->argv[$idx + 1];
                    break;
                case '-d':
                case '--dir':
                    if (empty($this->argv[$idx + 1])) {
                        throw new InvalidArgumentException('a directory path is expected for -d (--dir)');
                    }
                    $path = $this->argv[$idx + 1];
                    $files = array_filter(
                        FileSystem::dirContents($path),
                        static fn($filename) => str_ends_with($filename, '.json')
                    );
                    $this->collectionPaths = array_unique(array_merge($this?->collectionPaths ?? [], $files));
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
        if (empty($this->formats)) {
            $this->formats = [ConvertFormat::Http->name => ConvertFormat::Http];
        }
    }

    /**
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
    }

    /**
     * @throws JsonException
     */
    protected function initCollections(): void
    {
        foreach ($this->collectionPaths as $collectionPath) {
            $content = file_get_contents(FileSystem::normalizePath($collectionPath));
            $content = json_decode($content, flags: JSON_THROW_ON_ERROR);
            if (!property_exists($content, 'collection') || empty($content?->collection)) {
                throw new JsonException("not a valid collection: $collectionPath");
            }
            $this->collections[$content->collection->info->name] = $content->collection;
        }
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
        $count = count($this->collections);
        $current = 1;
        $success = 0;
        print(implode(PHP_EOL, array_merge($this->version(), $this->copyright())) . PHP_EOL . PHP_EOL);
        foreach ($this->collections as $collectionName => $collection) {
            printf("Converting '%s' (%d/%d):%s", $collectionName, $current, $count, PHP_EOL);
            foreach ($this->converters as $type => $exporter) {
                printf(' > %s', strtolower($type));
                $outputPath = sprintf('%s%s%s', $this->outputPath, DIRECTORY_SEPARATOR, $collectionName);
                $exporter->convert($collection, $outputPath);
                printf(' - OK: %s%s', $exporter->getOutputPath(), PHP_EOL);
            }
            print(PHP_EOL);
            ++$current;
            ++$success;
        }
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
        $time = (hrtime(true) - $this->init_time) / 1_000_000;
        $ram = (memory_get_peak_usage(true) - $this->init_ram) / 1024 / 1024;
        printf('Converted %d of %d in %.3f ms using %.3f MiB RAM', $success, $count, $time, $ram);
    }

    /**
     * @return string[]
     */
    protected function version(): array
    {
        return ["Postman collection converter v" . self::VERSION];
    }

    /**
     * @return string[]
     */
    protected function copyright(): array
    {
        return [
            'Anthony Axenov (c) ' . date('Y') . ", MIT license",
            'https://git.axenov.dev/anthony/pm-convert'
        ];
    }

    /**
     * @return array
     */
    protected function usage(): array
    {
        return array_merge($this->version(), [
            'Usage:',
            "\t./pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]",
            "\tphp pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]",
            "\tcomposer pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]",
            "\t./vendor/bin/pm-convert -f|-d PATH -o OUTPUT_PATH [ARGUMENTS] [FORMATS]",
            '',
            'Possible ARGUMENTS:',
            "\t-f, --file       - a PATH to single collection located in PATH to convert from",
            "\t-d, --dir        - a directory with collections located in COLLECTION_FILEPATH to convert from",
            "\t-o, --output     - a directory OUTPUT_PATH to put results in",
            "\t-p, --preserve   - do not delete OUTPUT_PATH (if exists)",
            "\t-h, --help       - show this help message and exit",
            "\t-v, --version    - show version info and exit",
            '',
            'If no ARGUMENTS passed then --help implied.',
            'If both -c and -d are specified then only unique set of files will be converted.',
            '-f or -d are required to be specified at least once, but each may be specified multiple times.',
            'PATH must be a valid path to readable json-file or directory.',
            'OUTPUT_PATH must be a valid path to writeable directory.',
            'If -o is specified several times then only last one will be used.',
            '',
            'Possible FORMATS:',
            "\t--http   - generate raw *.http files (default)",
            "\t--curl   - generate shell scripts with curl command",
            "\t--wget   - generate shell scripts with wget command",
            'If no FORMATS specified then --http implied.',
            'Any of FORMATS can be specified at the same time.',
            '',
            'Example:',
            "    ./pm-convert \ ",
            "        -f ~/dir1/first.postman_collection.json \ ",
            "        --directory ~/team \ ",
            "        --file ~/dir2/second.postman_collection.json \ ",
            "        -d ~/personal \ ",
            "        -o ~/postman_export ",
            "",
        ], $this->copyright());
    }
}
