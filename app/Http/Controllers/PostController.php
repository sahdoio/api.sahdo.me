<?php

namespace App\Http\Controllers;

use App\Core\Post;
use Illuminate\Http\Request;
use Auth;

class PostController extends Controller
{
    private $butler;
    private $provider;

    /**
     * ProviderController constructor.
     * @param Request $request
     */
    function __construct()
    {
        $this->post = new Post;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function one($id)
    {
        $response = $this->post->one($id);

        if (!$response) {
            $response = [
                'status' => 'ok',
                'message' => "there were no results found"
            ];
        }

        return response()->json($response)->withCallback($this->butler->getCallback());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $response = $this->post->all();

        if (!$response) {
            $response = [
                'status' => 'ok',
                'message' => "there were no results found"
            ];
        }

        return response()->json($response)->withCallback($this->butler->getCallback());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function new(Request $request)
    {
        $response = $this->post->new($request->all());

        if (!$response) {
            $response = [
                'status' => 'error',
                'message' => "error to insert post"
            ];
        }

        return response()->json($response);
    }
}
