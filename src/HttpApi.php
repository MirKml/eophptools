<?php

namespace MirKml\EO;

class HttpApi
{
    private $baseUrl;

    private $username;

    private $password;

    private $isVerbose = false;

    private $requestUrl;

    private $error;

    private $httpResponseCode;

    private $isResponseJson = false;

    private $requestBody;
    private $responseBody;

    /**
     * time out in seconds
     */
    private $timeOut = 30;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function setCredentials($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function setTimeOut($timeOut)
    {
        $this->timeOut = $timeOut;
    }

    public function setVerbose()
    {
        $this->isVerbose = true;
    }

    private function resetResultVariables()
    {
        $this->error =
        $this->httpResponseCode =
        $this->requestBody =
        $this->responseBody = "";
        $this->isResponseJson = false;
    }

    public function getJson($actionUrl, $parameters = [])
    {
        $this->resetResultVariables();
        $process = $this->createCurlProcess($actionUrl, $parameters);
        curl_setopt($process, CURLOPT_HTTPHEADER, [
            "Accept: application/json"
        ]);
        return $this->executeCurl($process);
    }

    public function postJsonFromFile($actionUrl, $filePath, $parameters = [])
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("json file '$filePath' doesn't exist");
        }

        $this->resetResultVariables();
        $process = $this->createCurlProcess($actionUrl, $parameters);
        curl_setopt($process, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json; charset=utf-8"
        ]);
        $this->requestBody = file_get_contents($filePath);
        curl_setopt($process, CURLOPT_POSTFIELDS, $this->requestBody);
        return $this->executeCurl($process);
    }

    public function postJson($actionUrl, $data, $parameters = [])
    {
        $this->resetResultVariables();
        $process = $this->createCurlProcess($actionUrl, $parameters);
        curl_setopt($process, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json; charset=utf-8"
        ]);
        $this->requestBody = json_encode($data);
        curl_setopt($process, CURLOPT_POSTFIELDS, $this->requestBody);
        return $this->executeCurl($process);
    }

    private function createCurlProcess($actionUrl, array $parameters)
    {
        $requestUrl = $this->baseUrl . "/" . $actionUrl;
        if ($parameters) {
            $requestUrl .= "?" . http_build_query($parameters);
        }
        $this->requestUrl = $requestUrl;

        $process = curl_init($requestUrl);
        if ($this->username) {
            curl_setopt($process, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        }
        curl_setopt($process, CURLOPT_TIMEOUT, $this->timeOut);
        if ($this->isVerbose) {
            curl_setopt($process, CURLOPT_VERBOSE, true);
        }
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        return $process;
    }

    private function executeCurl($curlProcess)
    {
        $result = curl_exec($curlProcess);
        $httpInfo = curl_getinfo($curlProcess);

        if ($result === false) {
            $this->error = "Error " . curl_errno($curlProcess) . ": " . curl_error($curlProcess);
            curl_close($curlProcess);
            return;
        }

        curl_close($curlProcess);

        $this->httpResponseCode = $httpInfo["http_code"];
        $this->responseBody = $result;

        $response = json_decode($result);
        if ($response === null) {
            $this->isResponseJson = false;
            return $result;
        }

        $this->isResponseJson = true;
        return $response;
    }

    public static function getJsonPrettyPrint($json)
    {
        return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function printGetJson($actionUrl, $parameters = [])
    {
        $this->printResponse($this->getJson($actionUrl, $parameters));
    }

    public function printPostJsonFile($actionUrl, $filePath, $parameters = [])
    {
        $this->printResponse($this->postJsonFromFile($actionUrl, $filePath, $parameters));
    }

    public function printPostJson($actionUrl, $data, $parameters = [])
    {
        $this->printResponse($this->postJson($actionUrl, $data, $parameters));
    }

    private function printResponse($response)
    {
        echo "request url: $this->requestUrl\n";
        if ($this->requestBody) {
            echo "request body:\n$this->requestBody\n";
        }
        if ($this->error) {
            echo "Curl error: $this->error\n";
        }
        echo "HTTP response code: $this->httpResponseCode\n";
        echo "response body:\n";
        if ($this->isResponseJson) {
            echo self::getJsonPrettyPrint($response);
        } else {
            echo $response;
        }
    }
}
