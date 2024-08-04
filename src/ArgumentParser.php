<?php

declare(strict_types=1);

namespace PmConverter;

use InvalidArgumentException;
use PmConverter\Converters\ConvertFormat;
use PmConverter\Enums\ArgumentNames as AN;

class ArgumentParser
{
    /**
     * @var array Raw arguments passed from cli ($argv)
     */
    protected readonly array $raw;

    /**
     * @var array Parsed and ready to use
     */
    protected array $parsed;

    /**
     * Constructor
     *
     * @param array $argv Raw arguments passed from cli ($argv)
     */
    public function __construct(array $argv)
    {
        $this->raw = array_slice($argv, 1);
    }

    /**
     * Parses raw arguments
     *
     * @return array Settings according to settings file format
     */
    public function parse(): array
    {
        foreach ($this->raw as $idx => $arg) {
            switch ($arg) {
                case '-c':
                case '--config':
                    if (empty($this->raw[$idx + 1])) {
                        throw new InvalidArgumentException('a configuration file path is expected for -c (--config)');
                    }
                    if (isset($this->parsed[AN::Config])) {
                        printf(
                            "INFO: Config file is already set to '%s' and will be overwritten to '%s'",
                            $this->parsed[AN::Config],
                            $this->raw[$idx + 1],
                        );
                    }
                    $this->parsed[AN::Config] = $this->raw[$idx + 1];
                    break;

                case '-d':
                case '--dir':
                    if (empty($this->raw[$idx + 1])) {
                        throw new InvalidArgumentException('a directory path is expected for -d (--dir)');
                    }
                    $this->parsed[AN::Directories][] = $this->raw[$idx + 1];
                    break;

                case '-f':
                case '--file':
                    if (empty($this->raw[$idx + 1])) {
                        throw new InvalidArgumentException('a directory path is expected for -f (--file)');
                    }
                    $this->parsed[AN::Files][] = $this->raw[$idx + 1];
                    break;

                case '-e':
                case '--env':
                    if (empty($this->raw[$idx + 1])) {
                        throw new InvalidArgumentException('an environment file path is expected for -e (--env)');
                    }
                    $this->parsed[AN::Environment][] = $this->raw[$idx + 1];
                    break;

                case '-o':
                case '--output':
                    if (empty($this->raw[$idx + 1])) {
                        throw new InvalidArgumentException('an output path is expected for -o (--output)');
                    }
                    $this->parsed[AN::Output][] = $this->raw[$idx + 1];
                    break;

                case '-p':
                case '--preserve':
                    $this->parsed[AN::PreserveOutput] = true;
                    break;

                case '--http':
                    $this->parsed[AN::Formats][] = ConvertFormat::Http;
                    break;

                case '--curl':
                    $this->parsed[AN::Formats][] = ConvertFormat::Curl;
                    break;

                case '--wget':
                    $this->parsed[AN::Formats][] = ConvertFormat::Wget;
                    break;

                case '--v2.0':
                    $this->parsed[AN::Formats][] = ConvertFormat::Postman20;
                    break;

                case '--v2.1':
                    $this->parsed[AN::Formats][] = ConvertFormat::Postman21;
                    break;

                case '-a':
                case '--all':
                    foreach (ConvertFormat::cases() as $format) {
                        $this->parsed[AN::Formats][] = $format;
                    }
                    break;

                case '--var':
                    $definition = trim($this->raw[$idx + 1]);
                    $name = strtok($definition, '='); // take first part before equal sign as var name
                    $value = strtok(''); // take the rest of argument as var value
                    if (isset($this->parsed[AN::Vars][$name])) {
                        printf(
                            "INFO: Variable '%s' is already set to '%s' and will be overwritten to '%s'",
                            $name,
                            $this->parsed[AN::Vars][$name],
                            $value,
                        );
                    }
                    $this->parsed[AN::Vars][$name] = $value;
                    break;

                case '--dev':
                    $this->parsed[AN::DevMode] = true;
                    break;

                case '-v':
                case '--verbose':
                    $this->parsed[AN::Verbose] = true;
                    break;

                case '--dump':
                    $this->parsed[AN::Dump] = true;
                    break;

                case '--version':
                    $this->parsed[AN::Version] = true;
                    break;

                case '-h':
                case '--help':
                    $this->parsed[AN::Help] = true;
                    break;
            }
        }

        foreach ([AN::Directories, AN::Files, AN::Formats] as $field) {
            if (!empty($this->parsed[$field])) {
                $this->parsed[$field] = array_unique($this->parsed[$field] ?? []);
            }
        }

        return $this->parsed;
    }

    /**
     * Returns parsed arguments (if set) or parses raw ones
     *
     * @return array
     */
    public function parsed(): array
    {
        return $this->parsed ??= $this->parse();
    }
}
