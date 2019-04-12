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

$router->get('/phpinfo', function () use ($router) {
    phpinfo();
    return '';
});

/*
######################################
# Tester Routes
######################################
*/

$router->get('/tester/mongo/find', ['as' => 'tester.mongo.find', 'uses' => 'TesterController@find']);
$router->get('/tester/mongo/aggregate', ['as' => 'tester.mongo.aggregate', 'uses' => 'TesterController@aggregate']);

/*
######################################
# Posts
######################################
*/


$router->get('/posts/{id}', ['as' => 'posts.one', 'uses' => 'PostController@one']);
$router->get('/posts', ['as' => 'tracker.all', 'uses' => 'PostController@all']);
$router->post('/posts', ['as' => 'posts.new', 'uses' => 'PostController@new']);



/*
######################################
# Provider
######################################
*/

$router->group(['middleware' => 'butler'], function () use ($router) {
    $router->get('/provider/start', ['as' => 'provider.start', 'uses' => 'ProviderController@start']);
});

/*
######################################
# Tracker
######################################
*/

$router->group(['middleware' => 'butler'], function () use ($router) {
    $router->get('/tracker/click', ['as' => 'tracker.click', 'uses' => 'TrackerController@click']);
    $router->get('/tracker/search', ['as' => 'tracker.search', 'uses' => 'TrackerController@search']);
    $router->get('/tracker/purchase', ['as' => 'tracker.purchase', 'uses' => 'TrackerController@purchase']);
});

/*
######################################
# Recommendation
######################################
*/

$router->group(['middleware' => 'butler'], function () use ($router) {
    // intelligence through panel
    $router->get('/recommendation/load', ['as' => 'recommendation.load', 'uses' => 'RecommendationController@load']);

    // open intelligences
    $router->get('/recommendation/bestsellers', ['as' => 'recommendation.bestsellers', 'uses' => 'RecommendationController@bestSellers']);
    $router->get('/recommendation/hotproducts', ['as' => 'recommendation.hotproducts', 'uses' => 'RecommendationController@hotProducts']);
    $router->get('/recommendation/personal', ['as' => 'recommendation.user', 'uses' => 'RecommendationController@personalRecommendation']);
    $router->get('/recommendation/mostclicked', ['as' => 'recommendation.mostclicked', 'uses' => 'RecommendationController@mostClicked']);
    $router->get('/recommendation/visitor_history', ['as' => 'recommendation.visitor_history', 'uses' => 'RecommendationController@visitorHistory']);
    $router->get('/recommendation/complementary', ['as' => 'recommendation.complementary', 'uses' => 'RecommendationController@complementaryProducts']);
    $router->get('/recommendation/alternative', ['as' => 'recommendation.hotproducts', 'uses' => 'RecommendationController@alternativeProducts']);


    // panel dashboard
    $router->group(['prefix' => 'dashboard'], function () use ($router) {
        $router->get('/data', ['as' => 'dashboard.data', 'uses' => 'DashboardController@data']);
    });

});