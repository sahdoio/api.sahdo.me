<?php


namespace App\Core;


use App\Libs\MongoManager;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class Post
{
    private $database;

    /**
     * Post constructor.
     */
    function __construct($params=null)
    {
        $this->database = new MongoManager(env('DB_HOST'), env('DB_DATABASE'));
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
            $document = $this->database->getDocuments('posts');
            return $document;
        }
        catch (Execption $e) {
            return false;
        }
    }

    /**
     * @param array $params
     * @return array|bool
     * @throws \Exception
     */
    public function new(Request $request)
    {
        $params         = $request->all();
        $user           = $request->get('auth');

        $post_id        = $this->database->getNextValue('posts.id');
        $admin_user_id  = isset($user->id) ? $user->id : false;
        $title          = isset($params['title']) ? $params['title'] : false;
        $body           = isset($params['body']) ? $params['body'] : false;
        $timestamp      = time();
        $created_at     = new UTCDateTime(new \DateTime());

        try {
            $status = $this->database->insertDocument([
                'id' => $post_id,
                'admin_user_id' => $admin_user_id,
                'title' => $title,
                'body' => $body,
                'timestamp' => $timestamp,
                'created_at' => $created_at
            ],
                'posts'
            );

            return $status;
        }
        catch (Execption $e) {
            return false;
        }
    }

    /**
     * @return array|bool
     * @throws \Exception
     */
    public function comments($post_id)
    {
        try {
            $documents = $this->database->getDocumentsByQuery(
                [
                    'post_id' => $post_id
                ],
                'post_comments'
            );

            return $documents;
        }
        catch (Execption $e) {
            return false;
        }
    }

    /**
     * @return array|bool
     * @throws \Exception
     */
    public function singleComment($comment_id)
    {
        try {
            $document = $this->database->getDocumentById($comment_id, 'post_comments');
            return $document;
        }
        catch (Execption $e) {
            return false;
        }
    }
}