<?php

namespace App\Http\Controllers;

use App\Core\Post;
use Illuminate\Http\Request;
use Auth;

class PostController extends Controller
{
    private $butler;
    private $request;
    private $provider;

    /**
     * ProviderController constructor.
     * @param Request $request
     */
    function __construct(Request $request)
    {
        $this->request = $request;
        $this->post = new Post;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function singlePost($post_id)
    {       
        $response = $this->post->one($post_id);

        if (!$response) {
            $response = [
                'status' => 'ok',
                'message' => "there were no results found"
            ];
        }

        return response()->json($response);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function allPosts()
    {       
        $response = $this->post->all();

        if (!$response) {
            $response = [
                'status' => 'ok',
                'message' => "there were no results found"
            ];
        }

        return response()->json($response);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function newPost()
    {
        $response = $this->post->newPost($this->request);

        if (!$response) {
            $response = [
                'status' => 'error',
                'message' => "error to insert post"
            ];
        }

        return response()->json($response);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePost($post_id)
    {
        $response = $this->post->updatePost($post_id, $this->request);

        if (!$response) {
            $response = [
                'status' => 'error',
                'message' => "error to update post"
            ];
        }

        return response()->json($response);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePost($post_id)
    {
        $response = $this->post->deletePost($post_id);

        if (!$response) {
            $response = [
                'status' => 'error',
                'message' => "error to delete post"
            ];
        }

        return response()->json($response);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function postComments($post_id)
    {        
        $response = $this->post->comments($post_id);

        if (!$response) {
            $response = [
                'status' => 'ok',
                'message' => "there were no results found"
            ];
        }

        return response()->json($response);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function singleComment($comment_id)
    {
        $comment_id = intval($comment_id);
        $response = $this->post->singleComment($comment_id);

        if (!$response) {
            $response = [
                'status' => 'ok',
                'message' => "there were no results found"
            ];
        }

        return response()->json($response);
    }


    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function newComment($post_id)
    {
        $response = $this->post->newComment($post_id, $this->request);

        if (!$response) {
            $response = [
                'status' => 'error',
                'message' => "error to insert post"
            ];
        }

        return response()->json($response);
    }
}
