<?php

namespace App\Libs;

class Butler
{
    private $config;
    private $payload;
    private $referer;
    private $public_key;
    private $database;
    private $database_hintify;
    private $status;
    private $callback;

    /**
     * Butler constructor.
     * @param $payload
     * @param $referer
     */
    function __construct($params)
    {
        $this->payload = isset($params['payload']) ? $params['payload'] : false;
        $this->public_key = isset($params['public_key']) ? $params['public_key'] : false;

        $this->payload = $this->buildParams($this->payload);
        $this->callback = isset($this->payload['callback']) ? $this->payload['callback'] : false;
        $this->referer = isset($_SERVER["HTTP_REFERER"]) ? strtolower($_SERVER["HTTP_REFERER"]) : (defined("STDIN") ? "CRON" : "");

        // setup config for respective store, if there is one
        $this->status = $this->init($this->public_key);
    }

    /**
     * @param $public_key
     * @return array
     */
    private function init($public_key)
    {
        $virtual_public_key = date("dmy");
        $arr_res = [];

        if (!$public_key) {
            $arr_res['status'] = 'error';
            $arr_res['message'] = 'public key not found';
            return $arr_res;
        }

        $dbas = env('DB_DATABASE');
        $host = env('DB_HOST');

        $this->public_key = $public_key;

        // Verify if public_key was a virtual or real
        if ($public_key !== $virtual_public_key) {
            $database = new MongoManager($host, $dbas);
            $this->database_hintify = $database;
            $res = $database->getSingleDocumentByQuery(['general.public_key' => $public_key], 'stores');

            // case public_key matches some store from database
            if ($res) {
                if ($database->listDatabases($res['database']['name'])) {
                    $url_ok = true;

                    /*

                    PENDING!!

                    $referer = strtolower($_SERVER["HTTP_REFERER"]);

                    foreach ($res['general']['url'] as $key => $value) {
                        echo $value."\n";
                        echo $referer."\n";
                        if (strtolower($value) == $referer) $url_ok = true;
                    }

                    */

                    if ($url_ok) {
                        $this->config = $res;

                        // create a new connection with database, this connection uses the store database
                        // and can be used for other classes
                        $host = env('DB_HOST');
                        $dbas = $this->config['database']['name'];

                        $this->database = new MongoManager($host, $dbas);

                        $arr_res['status'] = 'ok';
                        $arr_res['public_key'] = $this->config['general']['public_key'];
                    }
                    else {
                        $arr_res['status'] = 'error';
                        $arr_res['message'] = 'Pubkey and URL is not associated, this is going to be reported';
                    }
                }
                else {
                    $arr_res['status'] = 'error';
                    $arr_res['message'] = 'Database does not exists';
                }
            }
            // case no match found between public_key and some store from database
            else {
                $arr_res['status'] = 'error';
                $arr_res['message'] = 'Database does not exists';
            }
        }
        else {
            $arr_res['status'] = 'ok';
            $arr_res['message'] = 'Using virtual public_key';
        }

        return $arr_res;
    }

    /**
     * Verify if a string is base64 encoded and decode...
     * Also we check if it's a JSON string and turn it on an array
     * Result: base64 decoded string (if encoded) or a array if a JSON string
     */
    private function buildParams($input)
    {
        if ($input === FALSE) //if no payload given from URL payload
            return FALSE;

        if (!preg_match('~[^0-9a-zA-Z+/=]~', $input)) {
            $check = str_split(base64_decode($input));
            $x = 0;

            foreach ($check as $char) {
                if (ord($char) > 126)
                    $x++;
            }

            if ($x / count($check) * 100 < 30)
                $input = base64_decode($input);
        }

        // we check if it's a JSON string and turn it on an array
        $check = json_decode($input,TRUE);

        if (json_last_error() === JSON_ERROR_NONE)
            // If so, we turn all payload as lowercase except it values
            $input = array_change_key_case($check,CASE_LOWER);

        return $input;
    }

    /**
     * @param parameter array with one paramter, ex: ['limit':10];
     * @return true if parameter is array
     * @return false if parameter is not array (dã!)
     */
    public function setParams($parameter)
    {
        if (is_array($parameter)) {
            reset($parameter);
            $this->payload[key($parameter)] = $parameter[key($parameter)];
        }
        else {
            return false;
        }

        return true;
    }


    /**
     * @return array
     */
    public function getStatus() {
        if ($this->status) {
            return $this->status;
        }

        return [
            'status' => 'error',
            'message' => 'no status found'
        ];
    }

    /**
     * @return array|bool|string
     */
    public function getParams() {
        if ($this->payload)
            return $this->payload;
        else
            return false;
    }

    /**
     * @return bool
     */
    public function getConfig()
    {
        if ($this->config)
            return $this->config;
        else
            return false;
    }

    /**
     * @return bool
     */
    public function getCallback()
    {
        if ($this->callback)
            return $this->callback;
        else
            return false;
    }

    /**
     * @return bool
     */
    public function getPubKey()
    {
        if ($this->config)
            return $this->config['general']['public_key'];
        else
            return false;
    }

    /**
     * @return bool
     */
    public function getPrivateKey()
    {
        if ($this->config)
            return $this->config['security']['privatekey'];
        else
            return false;
    }

    /**
     * @return bool
     */
    public function getDomain()
    {
        if ($this->config)
            return $this->config['general']['url'];
        else
            return false;
    }

    /**
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return mixed
     */
    public function getDatabaseHintify()
    {
        return $this->database_hintify;
    }

    /**
     * @return array
     */
    public function getMainDatabase()
    {
        $arr_res = array();
        $arr_res['database'] = 'hintfy';
        $arr_res['host'] = 'mongodb://hintfy:hintfy#2018@127.0.0.1:27017';

        return $arr_res;
    }
}