<?php

declare(strict_types=1);

namespace PmConverter;

use Exception;
use Generator;
use JsonException;
use PmConverter\Converters\Abstract\AbstractConverter;
use PmConverter\Converters\ConverterContract;
use PmConverter\Exceptions\CannotCreateDirectoryException;
use PmConverter\Exceptions\DirectoryIsNotReadableException;
use PmConverter\Exceptions\DirectoryIsNotWriteableException;
use PmConverter\Exceptions\DirectoryNotExistsException;
use PmConverter\Exceptions\IncorrectSettingsFileException;

/**
 * Processor class
 */
class Processor
{
    /**
     * @var int Initial timestamp
     */
    protected readonly int $initTime;

    /**
     * @var int Initial RAM usage
     */
    protected readonly int $initRam;

    /**
     * @var ConverterContract[] Converters will be used for conversion according to chosen formats
     */
    protected array $converters = [];

    /**
     * Constructor
     *
     * @param Settings $settings Settings                               (lol)
     * @param Environment $env Environment
     */
    public function __construct(
        protected Settings $settings,
        protected Environment $env,
    ) {
        $this->initTime = hrtime(true);
        $this->initRam = memory_get_usage(true);
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
     */
    public function start(): void
    {
        $this->prepareOutputDirectory();
        $this->initConverters();
        $this->convert();
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
    protected function nextCollection(): Generator
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
        foreach ($this->nextCollection() as $collection) {
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
                        array_map(static fn (string $line) => printf('  %s%s', $line, EOL), $e->getTrace());
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
}
