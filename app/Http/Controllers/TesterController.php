<?php

namespace App\Http\Controllers;

use App\Libs\MongoManager;
use Illuminate\Http\Request;
use Auth;

class TesterController extends Controller
{
    private $mongoManager;

    /**
     * TesterController constructor.
     * @param Request $request
     */
    function __construct(Request $request)
    {
        $this->mongoManager = new MongoManager(env('DB_HOST'), 'cadence_com_br');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function find()
    {
        $result = $this->mongoManager->getDocuments('products_a');
        return response()->json($result);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function aggregate()
    {
        $result = $this->mongoManager->collectionAggregate(
            [
                [
                    '$project' => [
                        'id' => 1,
                        'index' => '$intelligence.bestsellers'
                    ]
                ],
                [
                    '$sort' => ['index' => 1]
                ]
            ],
            'products_a'
        );

        return response()->json($result);
    }
}
