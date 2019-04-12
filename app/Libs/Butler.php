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

        $this->database = new MongoManager($host, $dbas);

        $arr_res['status'] = 'ok';

        return $arr_res;
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
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }
}