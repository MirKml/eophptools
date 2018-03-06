<?php

namespace MirKml\EO;

use Dibi;

class EoisDb
{
    /**
     * @var EoisConfig
     */
    private $config;

    private $connection;
    public function __construct(EoisConfig $config)
    {
        $this->config = $config;
    }

    public function getConnection()
    {
        if ($this->connection) {
            return $this->connection;
        }

        $connectionOptions = [
            "driver" => "sqlsrv",
            "host" => $this->config->dbServer,
            "database" => $this->config->dbName,
            "lazy" => true
        ];

        if ($this->config->dbUsername) {
            $connectionOptions["username"] = $this->config->dbUsername;
            $connectionOptions["password"] = $this->config->dbPassword;
        }

        return $this->connection = new Dibi\Connection($connectionOptions);
    }

    /**
     * @return Dibi\Result
     */
    public function getProducts() {
        $connection = $this->getConnection();
        return $connection->query("select id, Name, IsActive from Products");
    }

    /**
     * @return Dibi\Result
     */
    public function getPartnerProducts() {
        $connection = $this->getConnection();
        return $connection->query("select * from PartnerProducts");
    }

    /**
     * @return Dibi\Result
     */
    public function getResultByQueryString($query) {
        $connection = $this->getConnection();
        return $connection->query($query);
    }

    public static function printResutlAsCSV(Dibi\Result $result) {
        $columnsNamesPrinted = false;
        /** @var Dibi\Row $row */
        foreach ($result as $row) {
            $rowAsArray = $row->toArray();
            if (!$columnsNamesPrinted) {
                $columns = array_keys($rowAsArray);
                echo implode(", ", $columns) . "\n";
                $columnsNamesPrinted = true;
            }

            echo implode(", ", $rowAsArray) . "\n";
        }
    }
}
