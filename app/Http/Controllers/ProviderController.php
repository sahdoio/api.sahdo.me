<?php

namespace App\Http\Controllers;

use App\Core\Provider;
use Illuminate\Http\Request;
use Auth;

class ProviderController extends Controller
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
        $this->provider = new Provider($this->butler);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function start()
    {
        $response = $this->provider->start();

        if (!$response) {
            $response = [
                'status' => 'ok',
                'message' => "there were no results found"
            ];
        }

        return response()->json($response)->withCallback($this->butler->getCallback());
    }
}
