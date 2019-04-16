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

$router->get('/', function () use ($router) {
    return view('root');
});

/*
######################################
# Login
######################################
*/

$router->post('auth/login', ['uses' => 'AuthController@authenticate']);

/*
######################################
# Posts
######################################
*/

$router->get('/posts/{post_id}', ['as' => 'posts.one', 'uses' => 'PostController@singlePost']);
$router->get('/posts', ['as' => 'posts.all', 'uses' => 'PostController@allPosts']);
$router->get('/posts/{post_id}/comments', ['as' => 'posts.comments', 'uses' => 'PostController@postComments']);
$router->get('/posts/comments/{comment_id}', ['as' => 'posts.comments.one', 'uses' => 'PostController@singleComment']);

/*
######################################
# JWT protected routes for users
######################################
*/

$router->post('/posts/{post_id}/comments', ['as' => 'posts.comments.new', 'uses' => 'PostController@newComment']);


/*
######################################
# JWT protected routes for admin users
######################################
*/
$router->group(['middleware' => 'jwt.auth'], function() use ($router) {
    /*
    ######################################
    # Posts
    ######################################
    */
    $router->post('/posts', ['as' => 'posts.new', 'uses' => 'PostController@newPost']);
});