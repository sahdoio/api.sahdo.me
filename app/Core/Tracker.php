<?php
/**
 * Created by PhpStorm.
 * User: lucas
 * Date: 01/03/19
 * Time: 06:39
 */

namespace App\Core;

use App\Libs\Butler;
use MongoDB\BSON\UTCDateTime;

class Tracker
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
     *
     */
    public function click()
    {
        $product_id  = isset($this->params['product_id']) ? $this->params['product_id'] : false;
        $content_id  = isset($this->params['content_id']) ? $this->params['content_id'] : false;
        $visitor_id  = isset($this->params['visitor_id']) ? $this->params['visitor_id'] : false;
        $from_us     = isset($this->params['from_us']) ? $this->params['from_us'] : false;
        $query       = isset($this->params['query']) ? $this->params['query'] : false;
        $timestamp   = time();
        $created_at  = new UTCDateTime(new \DateTime());

        try {
            $res = $this->db_store->insertDocument([
                    'product_id' => $product_id,
                    'content_id' => $content_id,
                    'visitor_id' => $visitor_id,
                    'from_us' => $from_us,
                    "query" => $query,
                    'created_at' => $created_at,
                    'timestamp' => $timestamp,
                ],
                'clicks'
            );

            return $res;
        }
        catch (Execption $e) {
            echo "[ERROR][Tracking] Error trying to send click tracking.\n";
        }
    }

    /**
     * @param $query
     * @param $corrected_query
     * @param $labels
     */
    public function search($query, $corrected_query, $labels)
    {
        try {
            if (!is_array($labels)) {
                $arr_labels = [];
                $arr_labels[] = $labels;
                $labels = $arr_labels;
            }
            $this->database->insertDocument(array('timestamp'=>time(),'query'=>$query,'corrected_query'=>$corrected_query,'labels'=>$labels,'visitor_id'=>$this->params['visitor']['id']),'searchs');
        }
        catch (Execption $e) {
            echo "[ERROR][Tracking] Can`t send search tracking...\n";
        }
    }

    /**
     * @param $order_id
     * @param $products
     * @param $email
     */
    public function purchase() {

        // paramas - $order_id, $products, $email

        dd('here');

        $new_products = array();
        try {
            foreach($products as $key => $value) {
                $arr_normalize = $this->normalizePurchaseProducts();

                //Need be opened to grant that values in fields are the string
                foreach ($value as $key2 => $value2) {
                    if ($key2 == 'product_id') $value[$key2] = (string)$value2;
                    if ($key2 == 'price') $value[$key2] = (float)$value2;
                    if ($key2 == 'quantity') $value[$key2] = (int)$value2;
                }

                $arr_normalize = array_merge($arr_normalize, $value);

                // Search and insert into product the categories, used in categories best sellers intelligence
                try {
                    $time = (time() - (7 * 24 * 60 * 60)); //7 days of tracking

                    // For query priority
                    // $filter = [
                    //     ['$match':
                    //         ["id":"345","visitor_id":"0ee2600a6a9ae473","recommended":true,"timestamp":["$gt":1523031372]]
                    //     ],
                    //     ['$sort':['query':-1]
                    //     ]);

                    $filter = [
                        'id' => (string) $arr_normalize['product_id'],
                        'visitor_id' => $this->params['visitor']['id'],
                        'from_us' => true,
                        'timestamp' => [
                            '$gt' => $time
                        ]
                    ];

                    // Verify if product was recommended by qwe123, case yes, the field recommended will be set to true
                    // $ret = $this->database->collectionAggregate($filter, "clicks"); //For query priority
                    $ret = $this->database->getDocumentsByQuery($filter, "clicks", 1);

                    $verify_rec = false;
                    $query = '';
                    $labels = [];

                    try {
                        $ret = iterator_to_array($ret);
                        foreach ($ret as $verify => $verify_value) {
                            $verify_rec = $verify_value['recommended'];
                            $query = $verify_value['query'];
                            try {
                                $labels = $verify_value['labels'];
                            }
                            catch(Exception $e) {
                                $labels = [];
                            }
                        }
                        $ret = $verify_rec;
                    }
                    catch(Exception $e) {
                        $ret = false;
                    }

                    if ($ret) {
                        $arr_normalize['recommended'] = true;
                        $arr_normalize['query'] = $query;
                        $arr_normalize['labels'] = $labels;
                    }
                }
                catch(Exception $e) {}

                array_push($new_products,$arr_normalize);

            }
        }
        catch (Exception $e) {
            echo "[ERROR][Purchase] Array products problem... $e\n";
        }

        // Send purchase to qwe123 database
        try {
            $tb = $this->database->insertDocument([
                    'timestamp' => time(),
                    'order_id' => $order_id,
                    'products' => $new_products,
                    'visitor_id' => $this->params['visitor']['id'],
                    'email' => $email
                ],
                'purchases'
            );
        }
        catch (Execption $e) {
            echo "[ERROR][Tracking] Can`t send purchase tracking...\n";
        }
    }

    /**
     * @return array
     */
    private function normalizePurchaseProducts() {
        $arr_normalize = array();
        $arr_normalize['id'] = '';
        $arr_normalize['quantity'] = 0;
        $arr_normalize['price'] = 0;
        $arr_normalize['recommended'] = false;
        $arr_normalize['query'] = '';
        $arr_normalize['labels'] = [];

        return $arr_normalize;
    }
}