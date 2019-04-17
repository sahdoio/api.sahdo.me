<?php

namespace App\Libs;
use LightnCandy\LightnCandy;
use LightnCandy\Runtime;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client as MongoClient;

class MongoManager
{
    // mongo connection
    private $connection;

    // mongo database
    private $database;

    /**
     * Constructor Method
     */

    function __construct($mongo_server, $database)
    {
        $this->connection = new MongoClient($mongo_server);

        // Some cases the database will not passed, for example the setup stores,
        // in those cases the database was not created yet.
        if (strlen(trim($database)) > 0)
            $this->database = $this->connection->$database;
    }

    /*
     #########################################
     * Create Methods
     *########################################
     */

    /**
     * @param $content
     * @param $collection
     * @return array
     */
    public function insertDocument($content, $collection) {
        $res = array();
        try {
            $status = $this->database->$collection->insertOne($content);
            $res["status"] = 'ok';
            $res["message"] = 'success';
        }
        catch (Exeception $e) {
            $res["status"] = 'error';
            $res["message"] = $e->getMessage();
        }
        return $res;
    }

    /*
     #########################################
     * Update Methods
     *########################################
     */

    /**
     * @param $filter
     * @param $content
     * @param $collection
     * @return array
     */
    public function updateDocumentByQuery($filter, $content, $collection) {
        $res = array();

        try {
            $collection = $this->database->$collection->findAndModify($filter, $content);
            $res["status"] = true;
        }
        catch (Exeception $e) {
            $res["status"] = false;
            $res["error"] = $e;
        }
        return $res;
    }

    /**
     * @param $id
     * @param $content
     * @param $collection
     * @return array
     */
    public function updateDocumentById($id, $content, $collection) {
        $res = [];

        try {
            $collection = $this->database->$collection->updateOne(
                ["id" => $id],
                ['$set' => $content]
            );

            $res["status"] = true;
        }
        catch (Exeception $e) {
            $res["status"] = false;
            $res["error"] = $e;
        }

        return $res;
    }

    /*
     #########################################
     * Delete Methods
     *########################################
     */

    /**
     * @param $id
     * @param $content
     * @param $collection
     * @return array
     */
    public function deleteDocumentById($id, $collection) {
        $res = [];

        try {
            $collection = $this->database->$collection->deleteOne(
                ["id" => $id]
            );

            $res["status"] = true;
        }
        catch (Exeception $e) {
            $res["status"] = false;
            $res["error"] = $e;
        }

        return $res;
    }

    /*
     #########################################
     * Search Methods
     *########################################
     */

    /**
     * @param $filter
     * @param $collection
     * @return bool
     */
    public function getSingleDocumentByQuery($filter, $collection)
    {
        $document = $this->database->$collection->findOne($filter);

        if (!isset($document) || count($document) <= 0)
            return false;

        return $document;
    }

    /**
     * @param $filter
     * @param $collection
     * @param int $limit
     * @param null $sort
     * @return mixed
     */
    public function getDocumentsByQuery($filter, $collection, $limit=1000, $sort=null)
    {  
        if (isset($sort)) {
            $documents = $this->database->$collection->find(
                $filter,
                [
                    'limit' => $limit,
                    'sort' => $sort
                ]
            );
        }
        else {
            $documents = $this->database->$collection->find(
                $filter,
                [
                    "limit" => $limit
                ]
            );
        }

        $res = array();

        foreach ($documents as $key => $value) {
            $res[$key] = $value;
        }

        return $res;
    }

    /**
     * @param $field
     * @param $value
     * @param $collection
     * @param int $limit
     * @param null $sort
     * @return bool|mixed
     */
    public function getDocumentByField($field, $value, $collection, $limit=1000, $sort=null)
    {
        if (isset($sort)) {
            $documents = $this->database->$collection->find(
                [
                    $field => $value
                ],
                [
                    'limit' => $limit,
                    'sort' => $sort
                ]
            );
        }
        else {
            $documents = $this->database->$collection->find(
                [
                    $field => $value
                ],
                [
                    "limit" => $limit
                ]
            );
        }

        $res = array();
        foreach ($documents as $key => $value) {
            $res[] = $value;
        }

        if (!isset($res[0]))
            return false;

        return $res[0];
    }

    /**
     * @param $id
     * @param $collection
     * @param int $limit
     * @param null $sort
     * @return bool|mixed
     */
    public function getDocumentById($id, $collection)
    {
        $documents = $this->database->$collection->find(['id' => $id]);

        $res = array();
        foreach ($documents as $key => $value) {
            $res[] = $value;
        }

        if (!isset($res[0]))
            return false;

        return $res[0];
    }

    /**
     * @param Array $filter
     * @param $collection
     * @param int $limit
     * @return bool
     */
    public function collectionAggregate($filter, $collection, $limit=10009)
    {
        $cursor = $this->database->$collection->aggregate(
            $filter
        );

        $result = [];
        foreach ($cursor as $item) {
            $result[] = iterator_to_array($item);
        }

        return $result;
    }

    /**
     * @param $collection
     * @param int $limit
     * @param null $sort
     * @return array
     */
    public function getDocuments($collection, $limit=1000, $sort=null)
    {
        if (isset($sort)) {
            $documents = $this->database->$collection->find(
                [],
                [
                    'limit' => $limit,
                    'sort' => $sort
                ]
            );
        }
        else {
            $documents = $this->database->$collection->find(
                [],
                [
                    "limit" => $limit
                ]
            );
        }

        $res = array();

        foreach ($documents as $key => $value) {
            $res[$key] = $value;
        }

        return $res;
    }

    /**
     *
     */
    public function listCollections() {
        //code
    }

    /**
     * Use $collection to verify if database exists
     * @param null $collection
     * @return bool
     */
    public function listDatabases($collection=null)
    {
        $dbs = $this->connection->listDatabases();

        $ret = false;
        if (isset($collection)) {
            foreach ($dbs as $details) {
                if (trim(strtolower($details['name'])) == trim(strtolower($collection))) {
                    $ret = true;
                    break;
                }
            }
        }
        else {
            $ret = $dbs;
        }

        return $ret;
    }

    /**
     * @return mixed
     */
    public function getMongo()
    {
        return $this->database;
    }

    /**
     *
     */
    public function getNextValue($field) {
        $document = $this->database->counters->findOneAndUpdate(
            ['id' => $field],
            ['$inc' => ['seq' => 1]]
        );

        if (!$document) {
            $this->database->counters->insertOne([
                'id' => $field,
                'seq' => 1,
                'created_at' => new UTCDateTime(new \DateTime()),
                'timestamp' => time(),
            ]);

            $document = $this->database->counters->findOne(['id' => $field]);
        }

        if (!isset($document['seq'])) {
            return false;
        }

        return $document['seq'];
    }
}