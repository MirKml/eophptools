<?php

namespace MirKml\EO;

class EoisWsCli
{
    const POST_METHOD = "POST";
    const GET_METHOD = "GET";

    const ENVIRONMENT_DEVELOPMENT = "devel";
    const ENVIRONMENT_STAGING = "staging";
    const ENVIRONMENT_PRODUCTION = "production";

    private $shortOptions = "hm:a:q:e:";
    private $longOptions = ["verbose", "json-file:"];

    private $method;
    private $actionUrl;
    private $environment;
    private $isVerbose;

    private $errors = [];

    /**
     * @var EoisConfig
     */
    private $config;

    /**
     * @var array
     */
    private $queryOptions;

    public function __construct(EoisConfig $config, $additionalShortOptions = "", array $additionalLongOptions = [])
    {
        $additionalShortOptions = trim($additionalShortOptions);
        if ($additionalShortOptions && strpos($this->shortOptions, $additionalShortOptions) !== false) {
            throw new \InvalidArgumentException("cannot use additional short options '$additionalShortOptions'
                . ', there is conflict with current short options '$this->shortOptions");
        }
        $this->shortOptions .= $additionalShortOptions;
        $this->longOptions += $additionalLongOptions;
        $this->config = $config;
    }

    protected function validateOptions(array $options)
    {
        if (!isset($options["m"])) {
            $this->errors[] = "-m option for method name option is mandatory";
            return;
        }
        $this->method = $options["m"];
        if ($this->method != self::POST_METHOD && $this->method != self::GET_METHOD) {
            $this->errors[] = "method must be " . self::POST_METHOD . " or " . self::GET_METHOD;
            return;
        }
        if ($this->method == self::POST_METHOD && !$options["json-file"]) {
            $this->errors[] = "for POST method --json-file option is mandatory";
            return;
        }

        if (isset($options["q"])) {
            parse_str($options["q"], $this->queryOptions);
        }

        if (!isset($options["a"])) {
            $this->errors[] = "-a option for action url is mandatory";
            return;
        }
        $this->actionUrl = $options["a"];

        if (!isset($options["e"])) {
            $this->environment = EoisConfig::DEVELOPMENT_ENVIRONMENT;
        } else {
            switch ($options["e"]) {
                case "devel":
                    $this->environment = EoisConfig::DEVELOPMENT_ENVIRONMENT;
                    break;
                case "staging":
                    $this->environment = EoisConfig::STAGING_ENVIRONMENT;
                    break;
                case "production":
                    $this->environment = EoisConfig::PRODUCTION_ENVIRONMENT;
                    break;
                default:
                    $this->errors[] .= "unknown environment '{$options["e"]}, must be 'devel', 'staging' or 'production'";
                    return;
            }
        }

        if (isset($options["verbose"])) {
            $this->isVerbose = true;
        }
    }

    public function execute()
    {
        $options = getopt($this->shortOptions, $this->longOptions);
        if (!$options) {
            $this->errors[] = "no options specified, use -h for help";
        } else {
            $this->validateOptions($options);
        }
        if (isset($options["h"])) {
            $this->printHelp();
            return;
        }

        if ($this->errors) {
           echo implode("\n", $this->errors);
           return;
        }

        $this->config->setByEnvironment($this->environment);

        $api = new EoisApi($this->config);
        if ($this->isVerbose) {
            $api->setVerbose();
        }

        if ($this->method == self::GET_METHOD) {
            $api->printGetJson($this->actionUrl, $this->queryOptions ?: []);
        } elseif ($this->method == self::POST_METHOD) {
            $api->printPostJson($this->actionUrl, $options["json-file"],$this->queryOptions ?: []);
        }
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return (bool)($this->errors);
    }

    protected function printHelp()
    {
        global $argv;
        echo "{$argv[0]} [-h] [-e <environment>] [--verbose] [--json-file <jsonFilePath>] -m <method> -a <actionUrl>\n\n";
        echo "-h: print this help\n";
        echo "-e: environment for EOIS instance: one of these: 'devel', 'staging', 'production', default is 'devel'\n";
        echo "--verbose: use verbose error output for Curl\n";
        echo "--json-file <jsonFilePath>: use <jsonFilePath> as request body for POST requests\n";
        echo "-m: request method, can be GET or POST\n";
        echo "-a: action URL\n";

        echo "\n" . $this->config->getHelp();
    }
}

