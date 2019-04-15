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
    public function findPost($id)
    {
        $id = intval($id);
        $response = $this->post->one($id);

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
        $response = $this->post->new($this->request);

        if (!$response) {
            $response = [
                'status' => 'error',
                'message' => "error to insert post"
            ];
        }

        return response()->json($response);
    }
}
