<?php

namespace MirKml\EO;

class EoisApi extends HttpApi
{
    public function __construct(EoisConfig $config)
    {
        parent::__construct($config->getBaseUrl());
        $this->setCredentials($config->getUsername(), $config->getPassword());
    }
}
