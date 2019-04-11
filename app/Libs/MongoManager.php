<?php

namespace App\Libs;
use LightnCandy\LightnCandy;
use LightnCandy\Runtime;
use MongoDB\Client as MongoClient;

class MongoManager
{
    // mongo connection
    private $connection;

    // mongo database
    private $database;

    /**
     * Constructor Method
     */

    function __construct($mongo_server, $database)
    {
        $this->connection = new MongoClient($mongo_server);

        // Some cases the database will not passed, for example the setup stores,
        // in those cases the database was not created yet.
        if (strlen(trim($database)) > 0)
            $this->database = $this->connection->$database;
    }

    /*
     #########################################
     * Create Methods
     *########################################
     */

    /**
     * @param $content
     * @param $collection
     * @return array
     */
    public function insertDocument($content, $collection) {
        $res = array();
        try {
            $status = $this->database->$collection->insertOne($content);
            $res["status"] = 'ok';
            $res["message"] = 'success';
        }
        catch (Exeception $e) {
            $res["status"] = 'error';
            $res["message"] = $e->getMessage();
        }
        return $res;
    }

    /*
     #########################################
     * Update Methods
     *########################################
     */

    /**
     * @param $filter
     * @param $content
     * @param $collection
     * @return array
     */
    public function updateDocumentByQuery($filter, $content, $collection) {
        $res = array();

        try {
            $collection = $this->database->$collection->findAndModify($filter, $content);
            $res["status"] = true;
        }
        catch (Exeception $e) {
            $res["status"] = false;
            $res["error"] = $e;
        }
        return $res;
    }

    /**
     * @param $id
     * @param $content
     * @param $collection
     * @return array
     */
    public function updateDocumentById($id, $content, $collection) {
        $res = array();

        try {
            $collection = $this->database->$collection->update(array("id"=>$id),$content);
            $res["status"] = true;
        }
        catch (Exeception $e) {
            $res["status"] = false;
            $res["error"] = $e;
        }
        return $res;
    }

    /*
     #########################################
     * Search Methods
     *########################################
     */

    /**
     * @param $filter
     * @param $collection
     * @return bool
     */
    public function getSingleDocumentByQuery($filter, $collection)
    {
        $document = $this->database->$collection->findOne($filter);

        if (!isset($document) || count($document) <= 0)
            return false;

        return $document;
    }

    /**
     * @param $filter
     * @param $collection
     * @param int $limit
     * @param null $sort
     * @return mixed
     */
    public function getDocumentsByQuery($filter, $collection, $limit=10, $sort=null)
    {
        if (isset($sort)) {
            $documents = $this->database->$collection->find(
                $filter,
                [
                    'limit' => $limit,
                    'sort' => $sort
                ]
            );
        }
        else {
            $documents = $this->database->$collection->find(
                $filter,
                [
                    "limit" => $limit
                ]
            );
        }


        return $documents;
    }

    /**
     * @param $field
     * @param $value
     * @param $collection
     * @param int $limit
     * @param null $sort
     * @return bool|mixed
     */
    public function getDocumentByField($field, $value, $collection, $limit=10, $sort=null)
    {
        if (isset($sort))
            $documents = $this->database->$collection
                ->find([
                    $field => $value
                ])
                ->limit($limit)
                ->sort($sort);
        else
            $documents = $this->database->$collection
                ->find([
                    $field => $value
                ])
                ->limit($limit);

        // return iterator_to_array($documents);

        $res = array();
        foreach ($documents as $key => $value) {
            $res[] = $value;
        }

        if (!isset($res[0]))
            return false;

        return $res[0];
    }

    /**
     * @param $id
     * @param $collection
     * @param int $limit
     * @param null $sort
     * @return bool|mixed
     */
    public function getDocumentById($id, $collection, $limit=10, $sort=null)
    {
        if (isset($sort)) {
            $documents = $this->database->$collection->find(
                [
                    'id' => $id
                ],
                [
                    'limit' => $limit,
                    'sort' => $sort
                ]
            );
        }
        else {
            $documents = $this->database->$collection->find(
                [
                    "id" => $id
                ],
                [
                    "limit" => $limit
                ]
            );
        }

        // return iterator_to_array($documents);

        $res = array();
        foreach ($documents as $key => $value) {
            $res[] = $value;
        }

        if (!isset($res[0]))
            return false;

        return $res[0];
    }

    /**
     * @param Array $filter
     * @param $collection
     * @param int $limit
     * @return bool
     */
    public function collectionAggregate($filter, $collection, $limit=999999)
    {
        $cursor = $this->database->$collection->aggregate(
            $filter
        );

        $result = [];
        foreach ($cursor as $item) {
            $result[] = iterator_to_array($item);
        }

        return $result;
    }

    /**
     * @param $collection
     * @param int $limit
     * @param null $sort
     * @return array
     */
    public function getDocuments($collection, $limit=10, $sort=null)
    {
        if (isset($sort)) $documents = $this->database->$collection->find()->limit($limit)->sort($sort);
        else $documents = $this->database->$collection->find()->limit($limit);

        //return iterator_to_array($documents);

        $res = array();

        foreach ($documents as $key => $value) {
            $res[$key] = $value;
        }

        return $res;
    }

    /**
     *
     */
    public function listCollections() {
        //code
    }

    /**
     * Use $collection to verify if database exists
     * @param null $collection
     * @return bool
     */
    public function listDatabases($collection=null)
    {
        $dbs = $this->connection->listDatabases();

        $ret = false;
        if (isset($collection)) {
            foreach ($dbs as $details) {
                if (trim(strtolower($details['name'])) == trim(strtolower($collection))) {
                    $ret = true;
                    break;
                }
            }
        }
        else {
            $ret = $dbs;
        }

        return $ret;
    }

    /**
     * @return mixed
     */
    public function getMongo()
    {
        return $this->database;
    }

    /**
     * Return a json with list of products with details
     * @param array ids with id's to create a return
     * @param array $order = how to return data to user: array(['field'=>'list_price','sort':'asc/desc'])
     * @param array $add_ret = Adictional data to return with the main result with products: array('param'=>'value','param2'=>'valeu');
     */
    public function getProducts($params, $products_enabled)
    {
        $ids = $params['ids'];
        $add_ret = isset($params['extra']) ? $params['extra'] : [];
        $url_params = isset($params['url_params']) ? $params['url_params'] : [];
        $block_id = isset($url_params['block_id']) ? $url_params['block_id'] : '';
        $debug = isset($url_params['debug']) ? $url_params['debug'] : false;
        $sort = (isset($url_params['sort']) && !empty($url_params['sort'])) ? $url_params['sort'] : false;
        $sort_aux = (isset($params['sort']) && !empty($params['sort'])) ? $params['sort'] : false;
        $limit  = isset($url_params['limit']) ? intval($url_params['limit']) : 16;
        $data_extra = isset($params['data_extra']) ? $params['data_extra'] : [];


        // add products_enabled
        $add_ret['products_enabled'] = $products_enabled;
        $add_ret['processed_quantity'] = count($ids);

        $arr_res = array();
        $continue = true;

        // If $id's not a array or $id's count is 0
        if (!is_array($ids)) {
            $arr_res['status'] = 'error';
            $arr_res['message'] = 'Wrong type list of ids';
            $continue = false;
        }
        elseif (count($ids) <= 0) {
            $arr_res['status'] = 'error';
            $arr_res['message'] = 'Empty list of ids';
            $continue = false;
        }

        $new_product_data   = array();
        $ids_not_found      = array();
        $arr_result         = array();
        $arr_product_data   = array();
        $duplicated         = array();
        $count              = 0;

        if ($continue) {
            $filter = array();

            // default filter
            $filter[] = ['$match' => ['id' => ['$in' => $ids]]];

            // eliminates intelligence field
            // $filter[] = ['$project' => ['intelligence' => 0]];

            // filterQL area
            if (isset($url_params['filter']) && !empty($url_params['filter'])) {
                $query = $url_params['filter'];
                $filterql = new FilterQL;

                $match = $filterql->generateFilter($query);

                $filter[] = $match;

                $add_ret['filter'] = $query;
            }

            // sort area
            if ($sort) {
                $sort_field = isset($sort['field']) ? $sort['field'] : null;
                $sort_order = isset($sort['field']) ? $sort['order'] : null;

                if (isset($sort_field) && isset($sort_order))
                    $filter[] = ['$sort' => ["$sort_field" => $sort_order]];
            }

            if ($sort_aux) {
                $filter[] = ['$sort' => $sort_aux];
            }

            // get data from database
            $res = $this->collectionAggregate(
                $filter,
                $products_enabled,
                999999
            );

            // format result and save products on new array
            foreach ($res as $product) {
                // Remove de mongo id from result
                try {
                    unset($product['_id']);
                }
                catch(Exception $e) {
                    // exception
                }

                // Used to decode htmlentities for correct acents
                foreach ($product as $_id => $_value) {
                    if (
                        is_string($_value) &&
                        $_value != 'id'
                    ) {
                        try {
                            $product[(string) $_id] = html_entity_decode($_value);
                        }
                        catch (Exception $e) {
                            // exception
                        }
                    }
                }

                if (is_array($data_extra)) {
                    foreach ($data_extra as $key => $value) {
                        $product[(string)$key] = $value;
                    }
                }

                if (isset($arr_product_data[(string) $product['id']])) {
                    $duplicated[] = (string) $product['id'];
                }
                else {
                    $arr_product_data[(string) $product['id']] = $product;
                }
            }

            // limit area
            $count_limit = (isset($url_params['limit'])) ? (int) $url_params['limit'] : 16;

            // Orgnize arr_products like ids passed by ids parameter
            // this was need because mongo send the result sorted by _ids
            // and not sorted by send ids sequence
            // also limit quantity in this area too with <limit> parameter
            if ($sort || $sort_aux) {
                foreach ($arr_product_data as $_id => $_product) {
                    $count_limit--;
                    if ($count_limit < 0) break;

                    $new_product_data[] = $_product;
                    $arr_result[] = $_id;

                    $count++;
                }
            }
            else {
                foreach ($ids as $_id) {
                    if (isset($arr_product_data[(string) $_id])) {
                        $count_limit--;
                        if ($count_limit < 0) break;

                        $new_product_data[] = $arr_product_data[(string) $_id];
                        $arr_result[] = $arr_product_data[(string) $_id]['id'];

                        $count++;
                    }
                    else {
                        $ids_not_found[] = (string) $_id;
                    }
                }
            }
        }

        $arr_product_data = $new_product_data;

        $count = count($arr_product_data);

        // template render area
        if (strlen(trim($block_id)) > 0) {
            $template_error     = false;

            // If found 1 item or more
            if ($count > 0) {
                // Handlebars template
                $filter = [];

                // find content
                $filter[] = [
                    '$match' => [
                        'id' => $block_id
                    ]
                ];

                // make join with design table
                $filter[] = [
                    '$lookup' => [
                        'from'=>'designs',
                        'localField' => 'design_id',
                        'foreignField' => 'id',
                        'as' => 'design'
                    ]
                ];

                $content = $this->collectionAggregate($filter,'contents');

                $template_item = $content[0]['design'][0]['design_item'];
                $template_container = $content[0]['design'][0]['design_container'];
                $template_css = $content[0]['design'][0]['design_css'];
                $template_js = $content[0]['design'][0]['design_js'];

                // Content data
                $random = (isset($content[0]['random'])) ? $content[0]['random'] : false;
                $call_back = (isset($content[0]['call_back'])) ? $content[0]['call_back'] : '';
                $reverse = (isset($content[0]['reverse'])) ? $content[0]['reverse'] : false;
                $vars = (isset($content[0]['vars'])) ? $content[0]['vars'] : false;
                $intelligence = (isset($content[0]['intelligence'])) ? $content[0]['intelligence'] : false;

                // Replace variables within the model by their respective values
                if (count($vars) > 0) {
                    foreach($vars as $key => $value) {
                        $template_item = preg_replace("/@@(.*?)".$key."(.*?)@@/i", $value, $template_item);
                        $template_container = preg_replace("/@@(.*?)".$key."(.*?)@@/i", $value, $template_container);
                        $template_css = preg_replace("/@@(.*?)".$key."(.*?)@@/i", $value, $template_css);
                        $template_js = preg_replace("/@@(.*?)".$key."(.*?)@@/i", $value, $template_js);
                    }

                    // Remove any others variables that not receive a value
                    $template_item = preg_replace("/@@(.*?)@@/i",'', $template_item);
                    $template_container = preg_replace("/@@(.*?)@@/i",'', $template_container);
                    $template_css = preg_replace("/@@(.*?)@@/i",'', $template_css);
                    $template_js = preg_replace("/@@(.*?)@@/i",'', $template_js);
                }

                // Compile template item to prepare to render
                $template_item = LightnCandy::compile($template_item, [
                    LightnCandy::FLAG_HANDLEBARSJS |
                    LightnCandy::FLAG_BESTPERFORMANCE |
                    LightnCandy::FLAG_IGNORESTANDALONE
                ]);

                // default
                $renderer = function() {
                    return false;
                };

                if ($template_item)
                    // get the render function from LightCandy
                    $renderer = LightnCandy::prepare($template_item);

                // Randomize itens
                if ($random) {
                    // get keys of array
                    $keys = array_keys($arr_product_data);

                    // randomize keys of array
                    shuffle($keys);

                    $random = [];
                    $arr_result = [];

                    foreach ($keys as $key) {
                        $random[$key] = $arr_product_data[$key];
                        // Recreate a array arr_result
                        $arr_result[] = (String)$key;
                    }

                    $arr_product_data = $random;
                }

                // Reverse itens
                if (($random != true) && ($reverse == true)) {
                    $arr_product_data = array_reverse($arr_product_data);
                    $arr_result = [];
                    foreach ($arr_product_data as $key) {
                        $arr_result[] = (String)$key;
                    }
                }

                // default
                $output_items = '';

                // If any problem with compilation item, the render_item will be false
                if ($renderer != false) {
                    foreach ($arr_product_data as $data) {
                        $item = $renderer($data, array('debug' => Runtime::DEBUG_ERROR_LOG));
                        $output_items .= $item;
                    }

                    // Get the string inside html element that determines where all items must be rendered
                    $regex = "/id(.*?)=(.*?)(\"|')(.*?)hintify_block(.*?)(\"|')(.*?)>/i";
                    preg_match($regex, $template_container, $output_array);

                    $found = isset($output_array[0]) ? $output_array[0] : false;
                    $success = false;

                    if ($found)
                        $template = preg_replace($regex, $found . $output_items, $template_container, 1, $success);
                    else
                        $template = $template_container;

                    if ($success > 0) {
                        $template_final = '<style>'. $template_css . '</style>';
                        $template_final .= $template;
                        $template_final .= $template_js;
                    }
                    else {
                        $template_final = '';
                        $template_error = 'hintify_block not found...';
                    }
                }
                else {
                    $template_final = '';
                    $template_error = 'Template item with sintaxe problems...';
                }

                // Verify intelligence if set, this data will be populate the variable reference, this variable
                // will used in front script to load the specific script for the specific product, this script
                // get the required information (productid, categoryid, etc...)
                if ($intelligence != false) {
                    $reference = $intelligence;
                }
                else {
                    $template_final = '';
                    $count = 0;
                    $reference = false;
                    $template_error = 'No intelligence selected to renderer...';
                }
            }
            else {
                $template_final = '';
                $count = 0;
                $reference = false;
                $template_error = '[INFO] No items to render...';
            }

            $arr_res = array(
                "template" => $template_final,
                "hits" => $count,
                "blockId" => $block_id,
                "reference" => $reference
            );

            if ($template_error && (strlen(trim($template_error)) > 0)) {
                $arr_res['status'] = 'error';
                $arr_res['message'] = $template_error;
            }
        }
        else {
            $arr_res = [
                "data" => $arr_product_data,
                "status" => 'error',
                "message" => 'Block ID not found...'
            ];
        }

        if ($debug == true) {
            $arr_res['data'] = $arr_product_data;
            $arr_res['data_ids'] = $arr_result;

            // put sort on extras if exists
            if (isset($url_params['sort']) && !empty($url_params['sort'])) $add_ret['sort'] = $url_params['sort'];

            // Add extra params into return array
            if (is_array($add_ret)) $arr_res['extra'] = $add_ret;
        }

        return $arr_res;
    }

    /**
     * Return a json with list of products with details
     * @param array ids with id's to create a return
     * @param array $order = how to return data to user: array(['field'=>'list_price','sort':'asc/desc'])
     * @param array $add_ret = Adictional data to return with the main result with products: array('param'=>'value','param2'=>'valeu');
     */
    public function formatProducts($params, $products_enabled)
    {
        $products = $params['products'];
        $add_ret = isset($params['extra']) ? $params['extra'] : [];
        $url_params = isset($params['url_params']) ? $params['url_params'] : [];
        $block_id = isset($url_params['block_id']) ? $url_params['block_id'] : '';
        $debug = isset($url_params['debug']) ? $url_params['debug'] : false;

        // add products_enabled
        $add_ret['products_enabled'] = $products_enabled;
        $add_ret['processed_quantity'] = count($products);

        $arr_res = array();
        $continue = true;

        // If $products's not a array or $products's count is 0
        if (!is_array($products)) {
            $arr_res['status'] = 'error';
            $arr_res['message'] = 'wrong type list of ids';
            $continue = false;
        }
        elseif (count($products) <= 0) {
            $arr_res['status'] = 'error';
            $arr_res['message'] = 'empty list of ids';
            $continue = false;
        }

        $ids_not_found      = array();
        $arr_result         = array();
        $arr_product_data   = array();
        $duplicated         = array();
        $count              = 0;

        if ($continue) {
            foreach ($products as $product) {
                // Remove de mongo id from result
                try {
                    unset($product['_id']);
                } catch(Exception $e) {
                    // exception
                }

                // Used to decode htmlentities for correct acents
                foreach ($product as $_id => $_value) {
                    if (
                        is_string($_value) &&
                        $_value != 'id'
                    ) {
                        try {
                            $product[(string) $_id] = html_entity_decode($_value);
                        }
                        catch (Exception $e) {
                            // exception
                        }
                    }
                }

                if (isset($arr_product_data[$product['id']])) {
                    $duplicated[] = (string) $product['id'];
                }
                else {
                    $arr_product_data[] = $product;
                    $arr_result[] = (string) $product['id'];
                }
            }
        }

        $count = count($arr_product_data);

        // template render area
        if (strlen(trim($block_id)) > 0) {
            $template_error     = false;

            // If found 1 item or more
            if ($count > 0) {
                // Handlebars template
                $filter = [];

                // find content
                $filter[] = [
                    '$match' => [
                        'id' => $block_id
                    ]
                ];

                // make join with design table
                $filter[] = [
                    '$lookup' => [
                        'from'=>'designs',
                        'localField' => 'design_id',
                        'foreignField' => 'id',
                        'as' => 'design'
                    ]
                ];

                $content = $this->collectionAggregate($filter,'contents');

                $template_item = $content[0]['design'][0]['design_item'];
                $template_container = $content[0]['design'][0]['design_container'];
                $template_css = $content[0]['design'][0]['design_css'];
                $template_js = $content[0]['design'][0]['design_js'];

                // Content data
                $random = (isset($content[0]['random'])) ? $content[0]['random'] : false;
                $call_back = (isset($content[0]['call_back'])) ? $content[0]['call_back'] : '';
                $reverse = (isset($content[0]['reverse'])) ? $content[0]['reverse'] : false;
                $vars = (isset($content[0]['vars'])) ? $content[0]['vars'] : false;
                $intelligence = (isset($content[0]['intelligence'])) ? $content[0]['intelligence'] : false;

                // Replace variables within the model by their respective values
                if (count($vars) > 0) {
                    foreach($vars as $key => $value) {
                        $template_item = preg_replace("/@@(.*?)".$key."(.*?)@@/i", $value, $template_item);
                        $template_container = preg_replace("/@@(.*?)".$key."(.*?)@@/i", $value, $template_container);
                        $template_css = preg_replace("/@@(.*?)".$key."(.*?)@@/i", $value, $template_css);
                        $template_js = preg_replace("/@@(.*?)".$key."(.*?)@@/i", $value, $template_js);
                    }

                    // Remove any others variables that not receive a value
                    $template_item = preg_replace("/@@(.*?)@@/i",'', $template_item);
                    $template_container = preg_replace("/@@(.*?)@@/i",'', $template_container);
                    $template_css = preg_replace("/@@(.*?)@@/i",'', $template_css);
                    $template_js = preg_replace("/@@(.*?)@@/i",'', $template_js);
                }

                // Compile template item to prepare to render
                $template_item = LightnCandy::compile($template_item, [
                    LightnCandy::FLAG_HANDLEBARSJS |
                    LightnCandy::FLAG_BESTPERFORMANCE |
                    LightnCandy::FLAG_IGNORESTANDALONE
                ]);

                // default
                $renderer = function() {
                    return false;
                };

                if ($template_item)
                    // get the render function from LightCandy
                    $renderer = LightnCandy::prepare($template_item);

                // Randomize itens
                if ($random) {
                    // get keys of array
                    $keys = array_keys($arr_product_data);

                    // randomize keys of array
                    shuffle($keys);

                    $random = [];
                    $arr_result = [];

                    foreach ($keys as $key) {
                        $random[$key] = $arr_product_data[$key];
                        // Recreate a array arr_result
                        $arr_result[] = (String)$key;
                    }

                    $arr_product_data = $random;
                }

                // Reverse itens
                if (($random != true) && ($reverse == true)) {
                    $arr_product_data = array_reverse($arr_product_data);
                    $arr_result = [];
                    foreach ($arr_product_data as $key) {
                        $arr_result[] = (String)$key;
                    }
                }

                // default
                $output_items = '';

                // If any problem with compilation item, the render_item will be false
                if ($renderer != false) {
                    foreach ($arr_product_data as $data) {
                        $item = $renderer($data, array('debug' => Runtime::DEBUG_ERROR_LOG));
                        $output_items .= $item;
                    }

                    // Get the string inside html element that determines where all items must be rendered
                    $regex = "/id(.*?)=(.*?)(\"|')(.*?)hintify_block(.*?)(\"|')(.*?)>/i";
                    preg_match($regex, $template_container, $output_array);

                    $found = isset($output_array[0]) ? $output_array[0] : false;
                    $success = false;

                    if ($found) {
                        $template = preg_replace($regex, $found . $output_items, $template_container, 1, $success);
                    }
                    else {
                        $template = $template_container;
                    }

                    if ($success) {
                        $template_final = '<style>'.$template_css.'</style>';
                        $template_final .= $template;
                    }
                    else {
                        $template_final = '';
                        $template_error = 'hintify_block not found...';
                    }
                }
                else {
                    $template_final = '';
                    $template_error = 'Template item with sintaxe problems...';
                }

                // Verify intelligence if set, this data will be populate the variable reference, this variable
                // will used in front script to load the specific script for the specific product, this script
                // get the required information (productid, categoryid, etc...)
                if ($intelligence != false) {
                    $reference = $intelligence;
                }
                else {
                    $template_final = '';
                    $count = 0;
                    $reference = false;
                    $template_error = 'No intelligence selected to renderer...';
                }
            }
            else {
                $template_final = '';
                $template_js = '';
                $template_error = '[INFO] No items to render...';
            }

            $arr_res = array(
                "template" => $template_final,
                "template_js" => $template_js,
                "hits" => $count,
                "blockId" => $block_id,
                "reference" => $reference
            );

            if ($template_error && (strlen(trim($template_error)) > 0)) {
                $arr_res['status'] = 'error';
                $arr_res['message'] = $template_error;
            }
        }
        else {
            $arr_res = [
                "data" => $arr_product_data,
                "status" => 'error',
                "message" => 'Block ID not found...'
            ];
        }

        if ($debug == true) {
            $arr_res['data'] = $arr_product_data;
            $arr_res['data_ids'] = $arr_result;

            // put sort on extras if exists
            if (isset($url_params['sort']) && !empty($url_params['sort'])) $add_ret['sort'] = $url_params['sort'];

            // Add extra params into return array
            if (is_array($add_ret)) $arr_res['extra'] = $add_ret;
        }

        return $arr_res;
    }
}