<?php

use Illuminate\Database\Seeder;
use App\Libs\MongoManager;
use MongoDB\BSON\UTCDateTime;

class FeedDatabaseSeeder extends Seeder
{
	private $url_posts = 'https://jsonplaceholder.typicode.com/posts';
	private $url_comments = 'https://jsonplaceholder.typicode.com/comments';
	private $url_users = 'https://jsonplaceholder.typicode.com/users';

    private $database;

    /**
     * Post constructor.
     */
    function __construct($params=null)
    {
        $this->database = new MongoManager(env('DB_HOST'), env('DB_DATABASE'));
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->fetchPosts();
        $this->fetchComments();
        $this->fetchUsers();
        $this->createCounters();
        $this->createDefaultUser();
    }

    private function fetchPosts()
    {   	
		$json_str = file_get_contents($this->url_posts);		
		$json_obj = json_decode($json_str);

	 	$timestamp = time();
        $created_at = new UTCDateTime(new \DateTime());

		foreach ($json_obj as $key => $value) {
			$status = $this->database->getMongo()->{'posts'}->updateOne(
				['id' => $value->id],
				['$set' => [
	                'id' => $value->id,
	                'admin_user_id' => $value->userId,
	                'title' => $value->title,
	                'body' => $value->body,
	                'timestamp' => $timestamp,
	                'created_at' => $created_at
            	]],
                ['upsert' => true]
            );
		}
    }

    private function fetchComments()
    {   	
		$json_str = file_get_contents($this->url_comments);		
		$json_obj = json_decode($json_str);

	 	$timestamp = time();
        $created_at = new UTCDateTime(new \DateTime());



		foreach ($json_obj as $key => $value) {
			$status = $this->database->getMongo()->{'post_comments'}->updateOne(
				['id' => $value->id],
				['$set' => [
	                'id' => $value->id,
	                'post_id' => $value->postId,
	                'user' => [
	                	'name' => $value->name,
                		'email' => $value->email
	                ],
	                'body' => $value->body,
	                'timestamp' => $timestamp,
	                'created_at' => $created_at
            	]],
                ['upsert' => true]
            );
		}
    }

    private function fetchUsers()
    {   	
		$json_str = file_get_contents($this->url_users);		
		$json_obj = json_decode($json_str);

	 	$timestamp = time();
        $created_at = new UTCDateTime(new \DateTime());

		foreach ($json_obj as $key => $value) {
			$status = $this->database->getMongo()->{'admin_users'}->updateOne(
				['id' => $value->id],
				['$set' => [
	                'id' => $value->id,
	                'name' => $value->name,
	                'username' => $value->username,
	                'email' => $value->email,
	                'password' => bcrypt('123456'),
	                'address' => [
	                	'street' => $value->address->street,
	                	'suite' => $value->address->suite,
	                	'city' => $value->address->city,
	                	'zipcode' => $value->address->zipcode,
	                	'geo' => [
	                		'lat' => $value->address->geo->lat,
	                		'lng' => $value->address->geo->lng
	                	]
	                ],
	                'phone' => $value->phone,
	                'company' => [
	                	'name' => $value->company->name,
	                	'catchPhrase' => $value->company->catchPhrase,
	                	'bs' => $value->company->catchPhrase
	                ],
	                'timestamp' => $timestamp,
	                'created_at' => $created_at
            	]],
                ['upsert' => true]
            );
		}
    }

    private function createCounters() 
    {
    	$count_posts = $this->database->getMongo()->{'posts'}->count();
    	$count_comments = $this->database->getMongo()->{'post_comments'}->count();
    	$count_admin_users = $this->database->getMongo()->{'admin_users'}->count();

    	$status = $this->database->getMongo()->{'counters'}->updateOne(
			['id' => 'posts.id'],
			['$set' => [
                'id' => 'posts.id',
                'seq' => $count_posts + 1               
        	]],
            ['upsert' => true]
        );

        $status = $this->database->getMongo()->{'counters'}->updateOne(
			['id' => 'post_comments.id'],
			['$set' => [
                'id' => 'post_comments.id',
                'seq' => $count_comments + 1               
        	]],
            ['upsert' => true]
        );

        $status = $this->database->getMongo()->{'counters'}->updateOne(
			['id' => 'admin_users.id'],
			['$set' => [
                'id' => 'admin_users.id',
                'seq' => $count_admin_users + 1               
        	]],
            ['upsert' => true]
        );
    }

    private function createDefaultUser()
    {
    	$timestamp = time();
        $created_at = new UTCDateTime(new \DateTime());

    	$status = $this->database->getMongo()->{'admin_users'}->updateOne(
			['email' => 'lucassahdo@gmail.com'],
			['$set' => [
                'id' => $this->database->getNextValue('admin_users.id'),
                'name' => 'Lucas Sahdo',
                'username' => 'lucassahdo',
                'email' => 'lucassahdo@gmail.com',
                'password' => bcrypt('123456'),
                'timestamp' => $timestamp,
                'created_at' => $created_at
        	]],
            ['upsert' => true]
        );
    }
}
