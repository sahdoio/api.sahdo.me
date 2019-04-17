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
    public function one($post_id)
    {
        $post_id = intval($post_id);
        try {
            $document = $this->database->getDocumentById($post_id, 'posts');
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
            $document = $this->database->getDocuments('posts', 1000, ['timestamp'=>-1]);
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
    public function newPost(Request $request)
    {
        $params         = $request->all();
        $user           = $request->get('admin_auth');
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
     * @param array $params
     * @return array|bool
     * @throws \Exception
     */
    public function updatePost($post_id, Request $request)
    {
        $params         = $request->all();
        $user           = $request->get('admin_auth');
        $post_id        = intval($post_id);
        $admin_user_id  = isset($user->id) ? $user->id : false;
        $title          = isset($params['title']) ? $params['title'] : false;
        $body           = isset($params['body']) ? $params['body'] : false; 

        try {
            $status = $this->database->updateDocumentById(
                $post_id,
                [
                    'id' => $post_id,
                    'admin_user_id' => $admin_user_id,
                    'title' => $title,
                    'body' => $body                 
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
     * @param array $params
     * @return array|bool
     * @throws \Exception
     */
    public function deletePost($post_id)
    {
        $post_id = intval($post_id);

        try {
            $status = $this->database->deleteDocumentById($post_id, 'posts');
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
        $post_id = intval($post_id);
        try {
            $documents = $this->database->getDocumentsByQuery(
                [
                    'post_id' => $post_id
                ],
                'post_comments',
                1000,
                ['timestamp' => -1]
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

    /**
     * @param array $params
     * @return array|bool
     * @throws \Exception
     */
    public function newComment($post_id, Request $request)
    {
        $user           = $request->get('auth');
        $comment_id     = $this->database->getNextValue('comments.id');   
        $post_id        = intval($post_id);
        $name           = $request->name ? $request->name : 'Anônimo';
        $email          = $request->email;
        $body           = $request->body;
        $timestamp      = time();
        $created_at     = new UTCDateTime(new \DateTime());

        try {
            $status = $this->database->insertDocument([
                'id' => $comment_id,
                'post_id' => $post_id,
                'user' => [                    
                    'name' => $name,
                    'email' => $email
                ],                
                'body' => $body,
                'timestamp' => $timestamp,
                'created_at' => $created_at
            ],
                'post_comments'
            );

            return $status;
        }
        catch (Execption $e) {
            return false;
        }
    }
}