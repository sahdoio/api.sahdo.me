<?php

namespace App\Http\Middleware;

use App\Libs\MongoManager;
use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class JwtMiddleware
{
    private $database;

    /**
     * JwtMiddleware constructor.
     */
    public function __construct()
    {
        $this->database = new MongoManager(env('DB_HOST'), env('DB_DATABASE'));
    }

    /**
     * @param $request
     * @param Closure $next
     * @param null $guard
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function handle($request, Closure $next, $guard=null)
    {
        $token = $request->get('token');

        if (!$token) {
            // Unauthorized response if token not there
            return response()->json([
                'error' => 'Token not provided.'
            ], 401);
        }

        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        }
        catch (ExpiredException $e) {
            return response()->json([
                'status' => 'expired',
                'message' => 'Provided token is expired'
            ]);
        }
        catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error while decoding token'                
            ]);
        }

        // Mysql Mode
        // $user = User::find($credentials->sub);

        // Mongo Mode
        $user = $this->database->getDocumentById($credentials->sub,'admin_users');

        // Now let's put the user in the request class so that you can grab it from there
        $request->attributes->add(['admin_auth' => $user]);
        $request->auth = $user;

        return $next($request);
    }
}