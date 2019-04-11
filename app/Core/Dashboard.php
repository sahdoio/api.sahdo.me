<?php

namespace App\Core;

use App\Libs\Butler;
use App\Libs\FilterQL;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Dashboard
{
    // store db
    private $db_store;
    // butler var
    private $butler;
    // api params
    private $params;
    // config
    private $config;
    // debug
    private $debug;
    // products base enabled
    private $products_enabled;
    // filterql
    private $filterql;

    // contructor method
    function __construct(Butler $butler)
    {
        // general
        $this->butler = $butler;
        $this->params = $this->butler->getParams();
        $this->config = $this->butler->getConfig();
        $this->debug = (isset($this->params["debug"])) ? $this->params["debug"] : false;
        $this->filterql = new FilterQL();

        // Bases
        $this->products_enabled = $this->config->database['products_base']['enabled'];
        $this->products_disabled = $this->config->database['products_base']['disabled'];

        // store database
        $this->db_store = $this->butler->getDatabase();
    }

    /*
    #####################################
    # Intelligence Area
    #####################################
    */

    /**
     * Get count total revenue
     * @return mixed
     */
    public function totalRevenue()
    {
        $total_value = DB::collection('purchases')->raw(function ($collection) {
            return $collection->aggregate([
                [
                    '$unwind' => '$products'
                ],
                [
                    '$group' => [
                        '_id' => null,
                        'total_value' => ['$sum' => '$products.value'],
                    ]
                ]
            ]);
        })
            ->toArray();

        if (isset($total_value[0]['total_value'])) {
            return $total_value[0]['total_value'];
        } else {
            return 0;
        }
    }

    // produtos é a quantidade de itens vendidos
    // pedidos é a quantidade de vendas

    /**
     * Get count total orders
     * @return mixed
     */
    public function totalOrders()
    {
        return DB::collection('purchases')->count();
    }

    /**
     * Get count total customers
     * @return mixed
     */
    public function totalCustomers()
    {
        return DB::collection('customers')->count();
    }

    /**
     * Get count total products
     * @return mixed
     */
    public function totalProducts()
    {
        return DB::collection($this->products_enabled)->count();
    }

    /**
     * Get count total revenue per month
     * @param $payload
     * @param $recommended
     * @return array
     */
    public function revenueMonth($payload, $recommended)
    {
        $payload = json_decode($payload, true);
        $revenue_month = [];


        $collection_purchases = DB::collection('purchases')->raw(function ($collection) {
            return $collection->aggregate([
                [
                    '$match' => [
                        'products.recommended' => false
                    ]
                ],
                [
                    '$unwind' => '$products'
                ],
                [
                    '$group' => [
                        '_id' => [
                            'year' => ['$year' => '$products.created_at'],
                            'month' => ['$month' => '$products.created_at']
                        ],
                        'total_value' => ['$sum' => '$products.value'],
                    ]
                ],
                [
                    '$sort' => [
                        '_id' => -1
                    ]
                ]
            ]);
        })
            ->toArray();

        if ($recommended) {
            $collection_purchases = DB::collection('purchases')->raw(function ($collection) {
                return $collection->aggregate([
                    [
                        '$unwind' => '$products'
                    ],
                    [
                        '$group' => [
                            '_id' => [
                                'year' => ['$year' => '$products.created_at'],
                                'month' => ['$month' => '$products.created_at']
                            ],
                            'total_value' => ['$sum' => '$products.value'],
                        ]
                    ],
                    [
                        '$sort' => [
                            '_id' => -1
                        ]
                    ]
                ]);
            })
                ->toArray();
        }


        for ($i = 0; $i < $payload['lastMonths']; $i++) {
            if (isset($collection_purchases[$i])) {
                array_unshift($revenue_month, $collection_purchases[$i]['total_value']);
            }
        }
        return $revenue_month;
    }
}