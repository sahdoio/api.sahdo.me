<?php

namespace App\Http\Controllers;

use App\Core\Tracker;
use Illuminate\Http\Request;
use Auth;

class TrackerController extends Controller
{
    private $butler;
    private $provider;

    /**
     * ProviderController constructor.
     * @param Request $request
     */
    function __construct(Request $request)
    {
        $this->butler = $request->get('butler');
        $this->tracker = new Tracker($this->butler);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function click()
    {
        $response = $this->tracker->click();

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
    public function purchase()
    {
        $response = $this->tracker->purchase();

        if (!$response) {
            $response = [
                'status' => 'ok',
                'message' => "there were no results found"
            ];
        }

        return response()->json($response)->withCallback($this->butler->getCallback());
    }
}
