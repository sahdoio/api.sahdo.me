<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['middleware' => 'cors'], function() use ($router) {
    $router->get('/', function () use ($router) {
        return view('root');
    });

    /*
    ######################################
    # Login
    ######################################
    */

    $router->post('auth/login', ['as' => 'auth.login', 'uses' => 'AuthController@authenticate']);

    /*
    ######################################
    # Posts
    ######################################
    */

    $router->get('/posts/{post_id}', ['as' => 'posts.one', 'uses' => 'PostController@singlePost']);
    $router->get('/posts', ['as' => 'posts.all', 'uses' => 'PostController@allPosts']);
    $router->get('/posts/{post_id}/comments', ['as' => 'posts.comments', 'uses' => 'PostController@postComments']);
    $router->get('/posts/comments/{comment_id}', ['as' => 'posts.comments.one', 'uses' => 'PostController@singleComment']);
    $router->post('/posts/{post_id}/comments', ['as' => 'posts.comments.new', 'uses' => 'PostController@newComment']);

    /*
    ######################################
    # JWT protected routes for logged users
    ######################################
    */
    $router->group(['middleware' => 'jwt.auth'], function () use ($router) {
        /*
        ######################################
        # Admin auth verify
        ######################################
        */
        $router->post('auth/verify', ['as' => 'auth.verify', 'uses' => 'AuthController@verify']);

        /*
        ######################################
        # Posts
        ######################################
        */
        $router->post('/posts', ['as' => 'posts.new', 'uses' => 'PostController@newPost']);
        $router->post('/posts/{post_id}', ['as' => 'posts.update', 'uses' => 'PostController@updatePost']);
        $router->get('/posts/{post_id}/delete', ['as' => 'posts.delete', 'uses' => 'PostController@deletePost']);
    });
});