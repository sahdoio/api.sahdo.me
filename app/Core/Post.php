<?php


namespace App\Core;


use App\Libs\MongoManager;
use MongoDB\BSON\UTCDateTime;

class Post
{
    private $database;

    /**
     * Post constructor.
     */
    function __construct()
    {
        $dbas = env('DB_DATABASE');
        $host = env('DB_HOST');
        $this->database = new MongoManager($host, $dbas);
    }

    /**
     * @return array|bool
     * @throws \Exception
     */
    public function one($id)
    {
        try {
            $document = $this->database->getDocumentById($id, 'posts');
            return $document;
        }
        catch (Execption $e) {
            return false;
        }
    }

    /**
     * @return array|bool
     * @throws \Exception
     */
    public function all()
    {
        try {
            $document = $this->database->getDocuments('posts', 99999);
            return $document;
        }
        catch (Execption $e) {
            return false;
        }
    }

    /**
     * @return array|bool
     * @throws \Exception
     */
    public function new($params)
    {
        $product_id  = isset($this->params['product_id']) ? $this->params['product_id'] : false;
        $content_id  = isset($this->params['content_id']) ? $this->params['content_id'] : false;
        $visitor_id  = isset($this->params['visitor_id']) ? $this->params['visitor_id'] : false;
        $from_us     = isset($this->params['from_us']) ? $this->params['from_us'] : false;
        $query       = isset($this->params['query']) ? $this->params['query'] : false;
        $timestamp   = time();
        $created_at  = new UTCDateTime(new \DateTime());

        try {
            $status = $this->database->insertDocument([
                'id' => 1,
                'admin_user_id' => 1,
                'title' => 'Teste',
                'body' => 'Este é um teste',
                'created_at' => new UTCDateTime(new \DateTime()),
                'timestamp' => time(),
            ],
                'posts'
            );

            return $status;
        }
        catch (Execption $e) {
            return false;
        }
    }
}