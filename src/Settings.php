<?php

declare(strict_types=1);

namespace PmConverter;

use InvalidArgumentException;
use JsonException;
use PmConverter\Converters\ConvertFormat;
use PmConverter\Exceptions\IncorrectSettingsFileException;

/**
 * Class responsible for settings storage and dumping
 */
class Settings
{
    /**
     * @var string Full path to settings file
     */
    protected static string $filepath;

    /**
     * @var bool Flag to output some debug-specific messages
     */
    protected bool $devMode = false;

    /**
     * @var string[] Paths to collection directories
     */
    protected array $directories = [];

    /**
     * @var string[] Paths to collection files
     */
    protected array $collectionPaths = [];

    /**
     * @var string Output path where to put results in
     */
    protected string $outputPath = '';

    /**
     * @var bool Flag to remove output directories or not before conversion started
     */
    protected bool $preserveOutput = false;

    /**
     * @var string[] Additional variables
     */
    protected array $vars = [];

    /**
     * @var ConvertFormat[] Formats to convert a collections into
     */
    protected array $formats = [];

    /**
     * @var string Path to environment file
     */
    protected string $envFilepath = '';

    /**
     * @return bool
     */
    public static function fileExists(): bool
    {
        return file_exists(self::$filepath);
    }

    /**
     * @return self
     * @throws IncorrectSettingsFileException
     * @throws JsonException
     */
    public static function init(): self
    {
        $content = '{}';
        self::$filepath = sprintf('%s%spm-convert-settings.json', $_SERVER['PWD'], DS);
        if (self::fileExists()) {
            $content = trim(file_get_contents(self::$filepath));
        }
        try {
            $settings = json_decode($content ?: '{}', flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new IncorrectSettingsFileException('Incorrect settings file: ' . $e->getMessage(), $e->getCode());
        }
        return new self($settings);
    }

    /**
     * Returns full path to settings file
     *
     * @return string
     */
    public static function filepath(): string
    {
        return self::$filepath;
    }

    /**
     * @param object $settings
     * @throws JsonException
     */
    protected function __construct(object $settings)
    {
        foreach ($settings->directories ?? [] as $path) {
            $this->addDirPath($path);
        }
        foreach ($settings->files ?? [] as $path) {
            $this->addFilePath($path);
        }
        $this->setDevMode(!empty($settings->devMode));
        $this->setPreserveOutput(!empty($settings->preserveOutput));
        isset($settings->environment) && $this->setEnvFilepath($settings->environment);
        isset($settings->output) && $this->setOutputPath($settings->output);
        foreach ($settings->formats ?? [] as $format) {
            $this->addFormat(ConvertFormat::fromArg($format));
        }
        foreach ($settings->vars ?? [] as $name => $value) {
            $this->vars[$name] = $value;
        }
    }

    /**
     * @param string $path
     * @return void
     * @throws JsonException
     */
    public function addDirPath(string $path): void
    {
        $this->directories = array_unique(array_merge(
            $this->directories ?? [],
            [FileSystem::normalizePath($path)]
        ));
        $files = array_filter(
            FileSystem::dirContents($path),
            static fn ($filename) => FileSystem::isCollectionFile($filename)
        );
        $this->collectionPaths = array_unique(array_merge($this->collectionPaths ?? [], $files));
    }

    /**
     * @param string $path
     * @return void
     * @throws JsonException
     */
    public function addFilePath(string $path): void
    {
        $normpath = FileSystem::normalizePath($path);
        if (!FileSystem::isCollectionFile($normpath)) {
            throw new InvalidArgumentException("not a valid collection: $path");
        }
        in_array($path, $this->collectionPaths) || $this->collectionPaths[] = $path;
    }

    /**
     * @param string $outputPath
     * @return void
     */
    public function setOutputPath(string $outputPath): void
    {
        $this->outputPath = $outputPath;
    }

    /**
     * @param bool $devMode
     * @return void
     */
    public function setDevMode(bool $devMode): void
    {
        $this->devMode = $devMode;
    }

    /**
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
     * @param bool $preserveOutput
     * @return void
     */
    public function setPreserveOutput(bool $preserveOutput): void
    {
        $this->preserveOutput = $preserveOutput;
    }

    /**
     * @param string $filepath
     * @return void
     */
    public function setEnvFilepath(string $filepath): void
    {
        $this->envFilepath = FileSystem::normalizePath($filepath);
    }

    /**
     * @return bool
     */
    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    /**
     * @return string[]
     */
    public function collectionPaths(): array
    {
        return $this->collectionPaths;
    }

    /**
     * @return string
     */
    public function outputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * @return bool
     */
    public function isPreserveOutput(): bool
    {
        return $this->preserveOutput;
    }

    /**
     * @return ConvertFormat[]
     */
    public function formats(): array
    {
        return $this->formats;
    }

    /**
     * @return string
     */
    public function envFilepath(): string
    {
        return $this->envFilepath;
    }

    /**
     * Determines fieldset of settings JSON
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'dev' => $this->isDevMode(),
            'directories' => $this->directories,
            'files' => $this->collectionPaths(),
            'environment' => $this->envFilepath(),
            'output' => $this->outputPath(),
            'preserve-output' => $this->isPreserveOutput(),
            'formats' => array_values(array_map(
                static fn (ConvertFormat $format) => $format->toArg(),
                $this->formats(),
            )),
            'vars' => $this->vars,
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
        copy(self::$filepath, $newfilepath = self::$filepath . '.bak.' . time());
        return $newfilepath;
    }
}
