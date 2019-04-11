<?php
/**
 * Created by PhpStorm.
 * User: lucas
 * Date: 01/03/19
 * Time: 06:39
 */

namespace App\Core;

use App\Libs\Butler;

class Setup
{
    // store db
    private $db_store;
    // hintify db
    private $db_hintify;
    // butler var
    private $butler;
    // params
    private $params;
    // config
    private $config;

    /**
     * contructor method
     * Provider constructor.
     * @param Butler $butler
     */
    function __construct(Butler $butler)
    {
        $this->butler = $butler;
        $this->params = $this->butler->getParams();
        $this->config = $this->butler->getConfig();

        // store database
        $this->db_store = $this->butler->getDatabase();

        // hintify database
        $this->db_hintify = $this->butler->getMainDatabase();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function click()
    {
        $response = [];

        try {
            $product_id = (string) $product_id;

            if (!is_array($labels)) {
                $arr_labels = [];
                $arr_labels[] = $labels;
                $labels = $arr_labels;
            }

            $this->db_store->insertDocument([
                'timestamp' => time(),
                'id' => $product_id,
                'labels' => $labels,
                'visitor_id' => $this->params['visitor']['id'],
                'recommended' => $recommended,
                "query" => $query
            ],
                'clicks'
            );
        }
        catch (Execption $e) {
            echo "[ERROR][Tracking] Can`t send click tracking...\n";
        }


        return $response;
    }
}