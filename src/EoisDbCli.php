<?php

namespace MirKml\EO;

class EoisDbCli
{
    const QUERY_PRODUCTS = "products";
    const QUERY_PARTNERPRODUCTS = "partnerProducts";

    private $shortOptions = "he:d:";

    private $environment;
    private $isThroughVpn = false;

    /**
     * shortcut for defined query
     * @var string
     */
    private $definedQuery;

    /**
     * @var string[]
     */
    private static $definedQueries = [
        self::QUERY_PRODUCTS,
        self::QUERY_PARTNERPRODUCTS
    ];

    /**
     * @var EoisConfig
     */
    private $config;

    private $errors = [];

    public function __construct(EoisConfig $config, $additionalShortOptions = "")
    {
        $additionalShortOptions = trim($additionalShortOptions);
        if ($additionalShortOptions && strpos($this->shortOptions, $additionalShortOptions) !== false) {
            throw new \InvalidArgumentException("cannot use additional short options '$additionalShortOptions'
                . ', there is conflict with current short options '$this->shortOptions");
        }
        $this->shortOptions .= $additionalShortOptions;
        $this->config = $config;
    }

    protected function validateOptions(array $options)
    {
        if (isset($options["d"])) {
            $this->definedQuery = $options["d"];
        }

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
                case "staging-vpn":
                    $this->environment = EoisConfig::STAGING_ENVIRONMENT;
                    $this->isThroughVpn = true;
                    break;
                case "production":
                    $this->environment = EoisConfig::PRODUCTION_ENVIRONMENT;
                    break;
                case "production-vpn":
                    $this->environment = EoisConfig::PRODUCTION_ENVIRONMENT;
                    $this->isThroughVpn = true;
                    break;
                default:
                    $this->errors[] .= "unknown environment '{$options["e"]}, must be 'devel', 'staging', 'staging-vpn'"
                        . ", 'production' or 'production-vpn'";
                    return;
            }
        }

    }

    public function execute()
    {
        $options = getopt($this->shortOptions);
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

        $this->config->setDdByEnvironment($this->environment, $this->isThroughVpn);

        $db = new EoisDb($this->config);
        if ($this->definedQuery) {
            switch ($this->definedQuery) {
                case self::QUERY_PRODUCTS:
                    $result = $db->getProducts();
                    break;
                case self::QUERY_PARTNERPRODUCTS:
                    $result = $db->getPartnerProducts();
                    break;
                default:
                    break;
            }
        }

        if (isset($result)) {
            EoisDb::printResutlAsCSV($result);
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
        echo "{$argv[0]} [-h] [-e <environment>] -d <definedQuery>\n\n";
        echo "-h: print this help\n";
        echo "-e: environment for EOIS database instance: one of these: 'devel', 'staging'"
            . ", 'staging-vpn', 'production', 'production-vpn'. Default is 'devel'.\n";
        echo "-d: predefined query, one of these: " . implode(", ", self::$definedQueries) . "\n";
    }
}

