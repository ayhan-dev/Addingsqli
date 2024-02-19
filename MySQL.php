<?php

class CpanelAPI {
    public $version = '2.0';
    public $ssl = 1;
    public $port = 2083;
    public $server;
    public $maxredirect = 0;
    public $user;
    public $json = '';

    protected $scope;
    protected $api;
    protected $auth;
    protected $pass;
    protected $secret;
    protected $type;
    protected $session;
    protected $method;
    protected $requestUrl;
    protected $eno;
    protected $emes;
    protected $token = false;
    protected $httpMethod = 'GET';
    protected $postData = '';

    public function __construct($user, $pass, $server, $secret = false) {
        $this->user = $user;
        $this->pass = $pass;
        $this->server = $server;
        if ($secret) {
            $this->secret = $secret;
            $this->set2Fa();
        }
    }

    protected function set2Fa() {
        require 'otphp/lib/otphp.php';
        $totp = new \OTPHP\TOTP($this->secret);
        $this->token = $totp->now();
    }

    public function __get($name) {
        switch (strtolower($name)) {
            case 'get':
                $this->httpMethod = 'GET';
                break;
            case 'post':
                $this->httpMethod = 'POST';
                break;
            case 'api2':
                $this->setApi('api2');
                break;
            case 'uapi':
                $this->setApi('uapi');
                break;
            default:
                $this->scope = $name;
        }
        return $this;
    }

    protected function setApi($api) {
        $this->api = $api;
        $this->setMethod();
        return $this;
    }

    protected function setMethod() {
        switch ($this->api) {
            case 'uapi':
                $this->method = '/execute/';
                break;
            case 'api2':
                $this->method = '/json-api/cpanel/';
                break;
            default:
                throw new Exception('$this->api is not set or is incorrectly set. The only available options are \'uapi\' or \'api2\'');
        }
        return $this;
    }

    public function __toString() {
        return $this->json;
    }

    public function __call($name, $arguments) {
        if (count($arguments) < 1 || !is_array($arguments[0])) {
            $arguments[0] = [];
        }
        $this->json = $this->APIcall($name, $arguments[0]);
        return json_decode($this->json);
    }

    protected function APIcall($name, $arguments) {
        $this->auth = base64_encode($this->user . ":" . $this->pass);
        $this->type = $this->ssl == 1 ? "https://" : "http://";
        $this->requestUrl = $this->type . $this->server . ':' . $this->port . $this->method;
        switch ($this->api) {
            case 'uapi':
                $this->requestUrl .= ($this->scope != '' ? $this->scope . "/" : '') . $name . '?';
                break;
            case 'api2':
                if ($this->scope == '') {
                    throw new Exception('Scope must be set.');
                }
                $this->requestUrl .= '?cpanel_jsonapi_user=' . $this->user . '&cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=' . $this->scope . '&cpanel_jsonapi_func=' . $name . '&';
                break;
            default:
                throw new Exception('$this->api is not set or is incorrectly set. The only available options are \'uapi\' or \'api2\'');
        }
        if ($this->httpMethod == 'GET') {
            $this->requestUrl .= http_build_query($arguments);
        }
        if ($this->httpMethod == 'POST') {
            $this->postData = $arguments;
        }

        return $this->curlRequest($this->requestUrl);
    }

    protected function curlRequest($url) {
        $httpHeaders = array("Authorization: Basic " . $this->auth);
        if ($this->token) {
            $httpHeaders[] = "X-CPANEL-OTP: " . $this->token;
        }
        $ch = curl_init();
        if ($this->httpMethod == 'POST') {
            $httpHeaders[] = "Content-type: multipart/form-data";
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postData);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100020);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $content = $this->curlExecFollow($ch, $this->maxredirect);
        $this->eno = curl_errno($ch);
        $this->emes = curl_error($ch);

        curl_close($ch);

        return $content;
    }

    protected function curlExecFollow($ch, &$maxredirect = null) {
        $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5)" . "Gecko/20041107 Firefox/1.0";
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        $mr = $maxredirect === null ? 5 : intval($maxredirect);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        return curl_exec($ch);
    }

    public function getLastRequest() {
        return $this->requestUrl;
    }

    public function getError() {
        if (!empty($this->eno)) {
            return ['no' => $this->eno, 'message' => $this->emes];
        }
        return false;
    }
}
