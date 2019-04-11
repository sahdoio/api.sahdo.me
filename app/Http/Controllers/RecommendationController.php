<?php

namespace App\Http\Controllers;

use App\Core\Recommender;
use Illuminate\Http\Request;
use Auth;

class RecommendationController extends Controller
{
    private $butler;
    private $recommender;

    function __construct(Request $request)
    {
        $this->butler = $request->get('butler');
        $this->recommender = new Recommender($this->butler);
    }


    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function load()
    {
        $response = $this->recommender->load();

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
    public function bestSellers()
    {
        $response = $this->recommender->bestSellers();

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
    public function mostClicked()
    {
        $response = $this->recommender->mostClicked();

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
    public function hotProducts()
    {
        $response = $this->recommender->hotProducts();

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
    public function visitorHistory()
    {
        $response = $this->recommender->visitorHistory();

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
    public function personalRecommendation()
    {
        $response = $this->recommender->personalRecommendation();

        if (!$response) {
            $response = [
                'status' => 'ok',
                'message' => "there were no results found"
            ];
        }

        return response()->json($response);
    }
}
