<?php

namespace MirKml\EO;

class EoisDbCli
{
    const QUERY_PRODUCTS = "products";
    const QUERY_PARTNERPRODUCTS = "partnerProducts";

    private $shortOptions = "he:d:i";

    private $environment;
    private $isThroughVpn = false;
    private $queryFromStdIn = false;

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
            $environmentOptions = self::getEnvironmentOption($options["e"]);
            if (!isset($environmentOptions["environment"])) {
                $this->errors[] .= "unknown environment '{$options["e"]}, must be 'devel', 'staging', 'staging-vpn'"
                    . ", 'production' or 'production-vpn'";
            }
            $this->environment = $environmentOptions["environment"];
            $this->isThroughVpn = $environmentOptions["isThroughVpn"];
        }

        if (isset($options["i"])) {
            if ($this->definedQuery) {
                $this->errors[] = "isn't possible use defined query and read query from STDIN";
                return;
            }
            $this->queryFromStdIn = true;
        }

        if (!$this->queryFromStdIn && !$this->definedQuery) {
            $this->errors[] = "No query options defined. Use pre defined query"
                . " or query from STDIN";
        }
    }

    final public static function getEnvironmentOption($environment)
    {
        $options = ["isThroughVpn" => false];
        switch ($environment) {
            case "devel":
                $options["environment"] = EoisConfig::DEVELOPMENT_ENVIRONMENT;
                break;
            case "staging":
                $options["environment"] = EoisConfig::STAGING_ENVIRONMENT;
                break;
            case "staging-vpn":
                $options["environment"] = EoisConfig::STAGING_ENVIRONMENT;
                $options["isThroughVpn"] = true;
                break;
            case "production":
                $options["environment"] = EoisConfig::PRODUCTION_ENVIRONMENT;
                break;
            case "production-vpn":
                $options["environment"] = EoisConfig::PRODUCTION_ENVIRONMENT;
                $options["isThroughVpn"] = true;
                break;
        }
        return $options;
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
                    $this->errors[] = "unknown defined query for identifier '{$this->definedQuery}'";
                    return;
                    break;
            }
        }
        if ($this->queryFromStdIn) {
            $query = "";
            while(!feof(STDIN)) {
                $query .= fgets(STDIN);
            }
            $result = $db->getResultByQueryString($query);
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

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    protected function printHelp()
    {
        global $argv;
        echo "{$argv[0]} [-h] [-e <environment>] -d <definedQuery>\n\n";
        echo "-h: print this help\n";
        echo "-e: environment for EOIS database instance: one of these: 'devel', 'staging'"
            . ", 'staging-vpn', 'production', 'production-vpn'. Default is 'devel'.\n";
        echo "-d: predefined query, one of these: " . implode(", ", self::$definedQueries) . "\n";
        echo "-i: read query from STDIN\n";
    }
}

