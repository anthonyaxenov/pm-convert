<?php

declare(strict_types=1);

namespace PmConverter;

use Exception;
use InvalidArgumentException;
use JsonException;
use PmConverter\Converters\ConvertFormat;
use PmConverter\Enums\ArgumentNames as AN;

/**
 * Class responsible for settings storage and dumping
 */
class Settings
{
    /**
     * @var string|null Full path to settings file
     */
    protected ?string $filePath = null;

    /**
     * @var bool Flag to output some debug-specific messages
     */
    protected bool $devMode = false;

    /**
     * @var string[] Paths to collection directories
     */
    protected array $dirPaths = [];

    /**
     * @var string[] Paths to collection files
     */
    protected array $collectionPaths = [];

    /**
     * @var string|null Output path where to put results in
     */
    protected ?string $outputPath;

    /**
     * @var bool Flag to remove output directories or not before conversion started
     */
    protected bool $preserveOutput;

    /**
     * @var string[] Additional variables
     */
    protected array $vars = [];

    /**
     * @var ConvertFormat[] Formats to convert a collections into
     */
    protected array $formats = [];

    /**
     * @var string|null Path to environment file
     */
    protected ?string $envFilePath = null;

    /**
     * @throws JsonException
     */
    public function __construct()
    {
        $this->loadFromDefaults();
    }

    /**
     * Loads settings from file
     *
     * @param string|null $filePath
     * @return void
     * @throws Exception
     */
    public function loadFromFile(?string $filePath = null): void
    {
        if (is_null($filePath)) {
            $filePath = sprintf('%s%spm-convert-settings.json', $_SERVER['PWD'], DS);
        }

        $filePath = trim($filePath);

        if (!file_exists($filePath)) {
            throw new Exception("file does not exist: $filePath");
        }

        if (!is_file($filePath)) {
            throw new Exception("not a file: $filePath");
        }

        if (!is_readable($filePath)) {
            throw new Exception("file is not readable: $filePath");
        }

        $content = file_get_contents($filePath);
        $settings = json_decode($content ?: '{}', true, JSON_THROW_ON_ERROR);

        $this->setFromArray($settings);
        $this->filePath = $filePath;
    }

    /**
     * Rewrites some defined settings with new values
     *
     * @param array $settings
     * @return void
     * @throws JsonException
     */
    public function override(array $settings): void
    {
        $settings = array_replace_recursive($this->__serialize(), $settings);
        $this->setFromArray($settings);
    }

    /**
     * Loads settings with default values
     *
     * @return void
     * @throws JsonException
     */
    public function loadFromDefaults(): void
    {
        $this->setFromArray(self::defaults());
    }

    /**
     * Returns default settings values
     *
     * @return array
     */
    public static function defaults(?string $key = null): mixed
    {
        $values = [
            AN::Config => null,
            AN::Directories => [],
            AN::Files => [],
            AN::Environment => null,
            AN::Output => null,
            AN::PreserveOutput => false,
            AN::Formats => ['http'],
            AN::Vars => [],
            AN::DevMode => false,
            AN::Verbose => false,
        ];

        return $key ? $values[$key] : $values;
    }

    /**
     * Set settings from array
     *
     * @param array $settings
     * @return void
     * @throws JsonException
     */
    protected function setFromArray(array $settings): void
    {
        foreach ($settings[AN::Directories] ?? self::defaults(AN::Directories) as $path) {
            $this->addDirPath($path);
        }

        foreach ($settings[AN::Files] ?? self::defaults(AN::Files) ?? [] as $path) {
            $this->addFilePath($path);
        }

        $this->setEnvFilePath($settings[AN::Environment] ?? self::defaults(AN::Environment));
        $this->setOutputPath($settings[AN::Output] ?? self::defaults(AN::Output));
        $this->setPreserveOutput($settings[AN::PreserveOutput] ?? self::defaults(AN::PreserveOutput));

        foreach ($settings[AN::Formats] ?? self::defaults(AN::Formats) as $format) {
            $this->addFormat(ConvertFormat::fromArg($format));
        }

        $this->vars = $settings[AN::Vars] ?? self::defaults(AN::Vars);
        $this->setDevMode($settings[AN::DevMode] ?? self::defaults(AN::DevMode));
    }

    /**
     * Checks wether settings file exists or not
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        return is_file($this->filePath)
            && is_readable($this->filePath)
            && is_writable($this->filePath);
    }

    /**
     * Returns full path to settings file
     *
     * @return string
     */
    public function filePath(): string
    {
        return $this->filePath;
    }

    /**
     * Adds directory path into current settings array and fills files array with its content
     *
     * @param string $path
     * @return void
     * @throws JsonException
     */
    public function addDirPath(string $path): void
    {
        $this->dirPaths = array_unique(array_merge(
            $this->dirPaths ?? [],
            [FileSystem::normalizePath($path)]
        ));

        $files = array_filter(
            FileSystem::dirContents($path),
            static fn ($filename) => FileSystem::isCollectionFile($filename)
        );

        $this->collectionPaths = array_unique(array_merge($this->collectionPaths ?? [], $files));
    }

    /**
     * Adds collection file into current settings array
     *
     * @param string $path
     * @return void
     * @throws JsonException
     */
    public function addFilePath(string $path): void
    {
        if (!FileSystem::isCollectionFile($path)) {
            throw new InvalidArgumentException("not a valid collection: $path");
        }

        if (!in_array($path, $this->collectionPaths)) {
            $this->collectionPaths[] = FileSystem::normalizePath($path);
        }
    }

    /**
     * Sets output directory path
     *
     * @param string|null $outputPath
     * @return void
     */
    public function setOutputPath(?string $outputPath): void
    {
        $this->outputPath = $outputPath;
    }

    /**
     * Sets developer mode setting
     *
     * @param bool $devMode
     * @return void
     */
    public function setDevMode(bool $devMode): void
    {
        $this->devMode = $devMode;
    }

    /**
     * Adds a format to convert to into current settings array
     *
     * @param ConvertFormat $format
     * @return void
     */
    public function addFormat(ConvertFormat $format): void
    {
        $this->formats[$format->name] = $format;
    }

    /**
     * Returns array of variables
     *
     * @return string[]
     */
    public function vars(): array
    {
        return $this->vars;
    }

    /**
     * Sets a setting responsible for saving old convertion results
     *
     * @param bool $preserveOutput
     * @return void
     */
    public function setPreserveOutput(bool $preserveOutput): void
    {
        $this->preserveOutput = $preserveOutput;
    }

    /**
     * Sets environment filepath setting
     *
     * @param string|null $filepath
     * @return void
     */
    public function setEnvFilePath(?string $filepath): void
    {
        $this->envFilePath = is_string($filepath)
            ? FileSystem::normalizePath($filepath)
            : $filepath;
    }

    /**
     * Returns current value of developer mode setting
     *
     * @return bool
     */
    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    /**
     * Returns current value of collection files setting
     *
     * @return string[]
     */
    public function collectionPaths(): array
    {
        return $this->collectionPaths;
    }

    /**
     * Returns current value of output directory path setting
     *
     * @return string|null
     */
    public function outputPath(): ?string
    {
        return $this->outputPath;
    }

    /**
     * Returns current value of preserve output setting
     *
     * @return bool
     */
    public function isPreserveOutput(): bool
    {
        return $this->preserveOutput;
    }

    /**
     * Returns current convert formats
     *
     * @return ConvertFormat[]
     */
    public function formats(): array
    {
        return $this->formats;
    }

    /**
     * Returns current value of environment filepath  setting
     *
     * @return string|null
     */
    public function envFilepath(): ?string
    {
        return $this->envFilePath;
    }

    /**
     * Determines fieldset of settings JSON
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            AN::DevMode => $this->isDevMode(),
            AN::Directories => $this->dirPaths,
            AN::Files => $this->collectionPaths(),
            AN::Environment => $this->envFilepath(),
            AN::Output => $this->outputPath(),
            AN::PreserveOutput => $this->isPreserveOutput(),
            AN::Formats => array_values(array_map(
                static fn (ConvertFormat $format) => $format->toArg(),
                $this->formats(),
            )),
            AN::Vars => $this->vars,
        ];
    }

    /**
     * Converts settings into JSON format
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->__serialize(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Writes settings in JSON format into settings file
     *
     * @param array $vars
     * @return bool
     */
    public function dump(array $vars = []): bool
    {
        count($vars) > 0 && $this->vars = $vars;
        return file_put_contents(self::$filepath, (string)$this) > 0;
    }

    /**
     * Makes a backup file of current settings file
     *
     * @return string
     */
    public function backup(): string
    {
        $newFilePath = $this->filePath() . '.bak.' . time();
        copy($this->filePath(), $newFilePath);
        return $newFilePath;
    }
}
