<?php

namespace MirKml\EO;

abstract class EoisConfig
{
    const PRODUCTION_ENVIRONMENT = "production";
    const STAGING_ENVIRONMENT = "staging";
    const DEVELOPMENT_ENVIRONMENT = "development";

    protected $baseUrl;
    protected $username;
    protected $password;

    public $dbServer;
    public $dbName;
    public $dbUsername;
    public $dbPassword;

    public function __construct($environment)
    {
        $this->setByEnvironment($environment);
    }

    public abstract function setByEnvironment($environment);

    public abstract function setDdByEnvironment($environment, $isThroughVpn = false);

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getHelp() {
        return "";
    }
}
