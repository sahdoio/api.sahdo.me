<?php

namespace App\Core;

use App\Libs\Butler;
use App\Libs\FilterQL;
use App\Models\Intelligence;

class Recommender
{
    // store db
    private $db_store;
    // hintify db
    private $db_hintify;
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
    // categories base enabled
    private $categories_enabled;
    // products base disabled
    private $products_disabled;
    // categories base disabled
    private $categories_disabled;
    // indexer
    private $indexer;
    // filterql
    private $filterql;

    // contructor method
    function __construct(Butler $butler)
    {
        // general
        $this->butler               = $butler;
        $this->params               = $this->butler->getParams();
        $this->config               = $this->butler->getConfig();
        $this->debug                = (isset($this->params["debug"])) ? $this->params["debug"] : false;
        $this->filterql             = new FilterQL();

        // Bases
        $this->products_enabled     = $this->config->database['products_base']['enabled'];
        $this->products_disabled    = $this->config->database['products_base']['disabled'];
        $this->categories_enabled   = $this->config->database['categories_base']['enabled'];
        $this->categories_disabled  = $this->config->database['categories_base']['disabled'];

        // store database
        $this->db_store = $this->butler->getDatabase();

        // hintify database
        $this->db_hintify = $this->butler->getMainDatabase();

        // indexer
        $this->indexer = new Indexer($this->config);
    }


    /**
     * Method that gets all parameters from my.hintify.io and loads respective intelligence
     * @return \Illuminate\Http\JsonResponse
     */
    public function load()
    {
        $content = $this->db_store->getDocumentById($this->params['block_id'], 'contents');
        $intelligence = $content['intelligence'];
        $limit = $content['limit'];

        $this->params['limit'] = $limit;

        $response = [];
        switch ($intelligence) {
            case Intelligence::BEST_SELLERS:
                $response = $this->bestSellers();
                break;
            case Intelligence::MOST_CLICKED:
                $response = $this->mostClicked();
                break;
            case Intelligence::HOT_PRODUCTS:
                $response = $this->hotProducts();
                break;
            case Intelligence::PERSONAL_RECOMMENDATION:
                $response = $this->personalRecommendation();
                break;
            case Intelligence::VISITOR_HISTORY:
                $response = $this->visitorHistory();
                break;
        }

        return $response;
    }

    /*
    #####################################
    # Intelligence Area
    #####################################
    */

    /**
     *
     */
    public function all_products()
    {
        $match = $this->filterql->generateFilter($this->param['filter']);
        $this->params['filter'] = '';
        $extra = array('intelligence' => 'all/products');

        $filter = [];

        // eliminates intelligence field
        // $filter[] = ['$project' => ['intelligence' => 0]];

        // get all products without any intelligence, for used with filters
        $res = $this->db_store->collectionAggregate(
            $filter,
            $this->products_enabled,
            999999
        );

        // get ids from result
        $ids = [];
        foreach ($res as $item) {
            $ids[] = (string) $item["id"];
        }

        // return produts
        return $this->db_store->getProducts(
            array(
                "ids" => $ids,
                "extra" => $extra
            ),
            $this->products_enabled
        );
    }

    /**
     *
     */
    public function bestSellers()
    {
        $limit  = isset($this->params['limit']) ? intval($this->params['limit']) : 16;
        $sort   = (isset($this->params['sort']) && !empty($this->params['sort'])) ? $this->params['sort'] : false;
        $extra  = array('intelligence' => 'all/bestsellers');

        // starts filter
        $filter = [];

        // custom filter area
        if (isset($this->params["filter"]) && !empty($this->params["filter"])) {
            $this->filterql = new FilterQL;
            $match = $this->filterql->generateFilter($this->params["filter"]);
            $filter[] = $match;
        }

        // main logic
        $filter[] = ['$sort' => ['intelligence.bestsellers' => 1]];

        // sort area
        if ($sort) {
            $sort_field = isset($sort['field']) ? $sort['field'] : null;
            $sort_order = isset($sort['field']) ? $sort['order'] : null;
            if (isset($sort_field) && isset($sort_order))
                $filter[] = ['$sort' => ["$sort_field" => $sort_order]];
        }

        // limit area
        $filter[] = ['$limit' => $limit];

        // get orders from database
        $res = $this->db_store->collectionAggregate($filter, $this->products_enabled);

        // return produts
        return $this->db_store->formatProducts(
            [
                "products" => $res,
                "url_params" => $this->params,
                "extra" => $extra
            ],
            $this->products_enabled
        );
    }

    /**
     *
     */
    public function mostClicked()
    {
        $limit = isset($this->params['limit']) ? intval($this->params['limit']) : 16;
        $sort   = (isset($this->params['sort']) && !empty($this->params['sort'])) ? $this->params['sort'] : false;
        $extra = array('intelligence' => 'all/mostclicked');

        // starts filter
        $filter = [];

        // eliminates intelligence field
        // $filter[] = ['$project' => ['intelligence' => 0]];

        // custom filter area
        if (isset($this->params["filter"]) && !empty($this->params["filter"])) {
            $this->filterql = new FilterQL;
            $match = $this->filterql->generateFilter($this->params["filter"]);
            $filter[] = $match;
        }

        // main logic
        $filter[] = ['$sort' => ['intelligence.mostclicked' => 1]];

        // sort area
        if ($sort) {
            $sort_field = isset($sort['field']) ? $sort['field'] : null;
            $sort_order = isset($sort['field']) ? $sort['order'] : null;
            if (isset($sort_field) && isset($sort_order))
                $filter[] = ['$sort' => ["$sort_field" => $sort_order]];
        }

        // limit area
        $filter[] = ['$limit' => $limit];

        // get orders from database
        $res = $this->db_store->collectionAggregate($filter, $this->products_enabled);

        // return produts
        return $this->db_store->formatProducts(
            [
                "products" => $res,
                "url_params" => $this->params,
                "extra" => $extra
            ],
            $this->products_enabled
        );
    }

    /**
     *
     */
    public function categoryBestSellers($id)
    {
        $id = (string) $id;
        $limit = isset($this->params['limit']) ? intval($this->params['limit']) : 16;
        $extra = ['intelligence' => 'category/bestsellers'];

        // limit only if have no filters
        if (isset($this->params["filter"]))
            $filter = [
                ['$project' => ['id' => 1,'rec' => '$intelligence.categorybestsellers.rec']],
                ['$match' => ['id' => $id]]
            ];
        else
            $filter = [
                ['$project' => ['id' => 1,'rec' => '$intelligence.categorybestsellers.rec']],
                ['$match' => ['id' => $id]],
                ['$limit' => $limit]
            ];

        // get categories best sellers from database
        $res = $this->db_store->collectionAggregate($filter, $this->categories_enabled);

        // case no rec found OR empty rec OR intelligence time overflow
        if (
            !isset($res[0]['rec']) ||
            count($res[0]['rec']) <= 0 ||
            !$this->checkIntelligenceTime($id, 'categorybestsellers')
        ) {
            $extra['calc_method'] = 'new_index';

            if (!$this->indexer->singleCategoryBestSellers($id, $this->categories_enabled)) {
                return $this->db_store->getProducts(
                    array(
                        "ids" => [],
                        "url_params" => $this->params,
                        "extra" => $extra
                    ),
                    $this->products_enabled
                );
            } else {
                // get categories best sellers from database
                $res = $this->db_store->collectionAggregate($filter, $this->categories_enabled);

                if (!isset($res[0]['rec'])) return $this->db_store->getProducts(
                    array(
                        "ids" => [],
                        "url_params" => $this->params,
                        "extra" => $extra
                    ),
                    $this->products_enabled
                );
            }
        } else {
            $extra['calc_method'] = 'cache_index';
        }

        $rec = $res[0]['rec'];

        // get ids from result
        $ids = [];
        foreach ($rec as $product) {
            $ids[] = (string) $product;
        }

        // return produts
        return $this->db_store->getProducts(
            array(
                "ids" => $ids,
                "url_params" => $this->params,
                "extra" => $extra
            ),
            $this->products_enabled
        );
    }

    /**
     *
     */
    public function hotProducts()
    {
        $limit = isset($this->params['limit']) ? intval($this->params['limit']) : 16;
        $sort   = (isset($this->params['sort']) && !empty($this->params['sort'])) ? $this->params['sort'] : false;
        $extra = array('intelligence' => 'all/hotproducts');

        // starts filter
        $filter = [];

        // eliminates intelligence field
        // $filter[] = ['$project' => ['intelligence' => 0]];

        // custom filter area
        if (isset($this->params["filter"]) && !empty($this->params["filter"])) {
            $this->filterql = new FilterQL;
            $match = $this->filterql->generateFilter($this->params["filter"]);
            $filter[] = $match;
        }

        // main logic
        $filter[] = ['$sort' => ['intelligence.mostclicked' => 1]];

        // sort area
        if ($sort) {
            $sort_field = isset($sort['field']) ? $sort['field'] : null;
            $sort_order = isset($sort['field']) ? $sort['order'] : null;
            if (isset($sort_field) && isset($sort_order))
                $filter[] = ['$sort' => ["$sort_field" => $sort_order]];
        }

        // limit area
        $filter[] = ['$limit' => $limit];

        // get orders from database
        $res = $this->db_store->collectionAggregate($filter, $this->products_enabled);

        // return produts
        return $this->db_store->formatProducts(
            array(
                "products" => $res,
                "url_params" => $this->params,
                "extra" => $extra
            ),
            $this->products_enabled
        );
    }

    /**
     *
     */
    public function personalRecommendation()
    {
        $period_mongo = (isset($this->config["periods"]["personalrecommendation"])) ? $this->config["periods"]["personalrecommendation"] : 180;
        $period_limit = intval($period_mongo);
        $time = (time() - ($period_limit * 24 * 60 * 60));
        $extra = array('intelligence' => 'personal/recommendation');
        $visitor_id = isset($this->params['visitor']['id']) ? $this->params['visitor']['id'] : null;

        if (!isset($visitor_id) && isset($this->params['visitor_id']))
            $visitor_id = isset($this->params['visitor_id']) ? $this->params['visitor_id'] : null;

        $ids = [];
        if (isset($visitor_id)) {
            $filter = [
                array(
                    '$match' => array(
                        "visitor_id" => $visitor_id,
                        "timestamp" => array(
                            '$gt' => $time
                        )
                    )
                ),
                array(
                    '$group' => array(
                        '_id' => '$id',
                        'timestamp' => array(
                            '$max' => '$timestamp'
                        )
                    )
                ),
                array(
                    '$sort' => array(
                        'timestamp' => -1
                    )
                ),
                array(
                    '$limit' => 4
                )
            ];

            // get last 4 visitor history
            $res = $this->db_store->collectionAggregate($filter, "clicks");

            // get visitor history ids
            $his_ids = [];
            foreach ($res as $item) {
                $his_ids[] = (string) $item["_id"];
            }

            // get alternatives for each product
            $_ids = [];
            foreach ($his_ids as $id) {
                $req = $this->products_alternative($id, false);
                $prods = isset($req['result']) ? $req['result'] : [];
                $count = count($prods);

                // get ids from result
                $__ids = [];
                for ($i = 0; $i < $count; $i++) {
                    $isOK = true;

                    // check if product already exists
                    foreach ($_ids as $group) {
                        if (in_array($prods[$i], $group)) {
                            $isOK = false;
                            break;
                        }
                    }

                    // if product do not repeat save it
                    if ($isOK)
                        $__ids[] = (string) $prods[$i];
                }

                if (count($__ids) > 0)
                    $_ids[] = $__ids;
            }

            $total = count($_ids);
            $_l = isset($this->params['limit']) ? $this->params['limit'] : 16;
            $k = ($total>0) ? ceil($_l / $total) : 0; // If count $_ids <= 0, then $k = 0, otherwise the php send a warning division by zero

            // distributes the result equally
            foreach ($_ids as $item) {
                for ($i = 0; $i < count($item); $i++) {
                    if ($i < $k)
                        $ids[] = (string) $item[$i];
                    else
                        break;
                }
            }
        }

        // return produts
        return $this->db_store->getProducts(
            array(
                "ids" => $ids,
                "url_params" => $this->params,
                "extra" => $extra
            ),
            $this->products_enabled
        );
    }

    /**
     *
     */
    public function visitorHistory()
    {
        $period_mongo = (isset($this->config["periods"]["visitorhistory"])) ? $this->config["periods"]["visitorhistory"] : 180;
        $period_limit = intval($period_mongo);
        $time = (time() - ($period_limit * 24 * 60 * 60));
        $time = 0;
        $extra = array('intelligence' => 'personal/visitor/history');
        $visitor_id = isset($this->params['visitor']['id']) ? $this->params['visitor']['id'] : null;

        if (!isset($visitor_id) && isset($this->params['visitor_id']))
            $visitor_id = isset($this->params['visitor_id']) ? $this->params['visitor_id'] : null;

        $ids = [];
        if (isset($visitor_id)) {
            $filter = [
                [
                    '$match' => [
                        "visitor_id" => $visitor_id,
                        "timestamp" => [
                            '$gt' => $time
                        ]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$id',
                        'timestamp' => [
                            '$max' => '$timestamp'
                        ]
                    ]
                ],
                [
                    '$sort' => [
                        'timestamp' => -1
                    ]
                ]
            ];

            // get visitor history
            $res = $this->db_store->collectionAggregate($filter, "clicks");

            // get ids from result
            foreach ($res as $item) {
                $ids[] = (string) $item["_id"];
            }
        }

        // return produts
        return $this->db_store->getProducts(
            array(
                "ids" => $ids,
                "url_params" => $this->params,
                "extra" => $extra
            ),
            $this->products_enabled
        );
    }

    /**
     * Produtos mais vendidos da categoria do produto
     * Produtos de categorias irmãs
     */
    public function products_alternative($ids, $history=true)
    {
        // check ids type
        if(!is_array($ids)) {
            $ids = trim((string) $ids);
            $ids = str_replace('[', '', $ids);
            $ids = str_replace(']', '', $ids);
            $ids = explode(",", $ids);
            $id = $ids[0];
        }
        else {
            $id = (string) $ids[0];
        }

        // history
        if ($history) {
            $tracking = new Tracking($this->butler);
            $tracking->sendClick($id, 'organic', false);
        }

        $limit = isset($this->params['limit']) ? intval($this->params['limit']) : 16;
        $extra = array('intelligence' => 'products/alternative');

        // limit only if have no filters
        if (isset($this->params["filter"]))
            $filter = [
                ['$project' => ['id' => 1,'rec' => '$intelligence.alternative.rec']],
                ['$match' => ['id' => $id]]
            ];
        else
            $filter = [
                ['$project' => ['id' => 1,'rec' => '$intelligence.alternative.rec']],
                ['$match' => ['id' => $id]],
                ['$limit' => $limit]
            ];

        // get orders from database
        $res = $this->db_store->collectionAggregate($filter, $this->products_enabled);

        // case no rec found OR empty rec OR intelligence time overflow
        if (
            !isset($res[0]['rec']) ||
            count($res[0]['rec']) <= 0 ||
            !$this->checkIntelligenceTime($id, 'alternative')
        ) {
            $extra['calc_method'] = 'new_index';

            if (!$this->indexer->singleProductAlternative($id, $this->products_enabled)) {
                return $this->db_store->getProducts(
                    array(
                        "ids" => [],
                        "url_params" => $this->params,
                        "extra" => $extra
                    ),
                    $this->products_enabled
                );
            }
            else {
                // get products from database
                $res = $this->db_store->collectionAggregate($filter, $this->products_enabled);

                if (!isset($res[0]['rec'])) return $this->db_store->getProducts(
                    array(
                        "ids" => [],
                        "url_params" => $this->params,
                        "extra" => $extra
                    ),
                    $this->products_enabled
                );
            }
        }
        else {
            $extra['calc_method'] = 'cache_index';
        }

        $rec = $res[0]['rec'];

        // get ids from result
        $ids = [];
        foreach ($rec as $product) {
            $ids[] = (string) $product;
        }

        // return produts
        return $this->db_store->getProducts(
            array(
                "ids" => $ids,
                "url_params" => $this->params,
                "extra" => $extra
            ),
            $this->products_enabled
        );
    }

    /**
     * @description Complemetary Products
     * Produtos que foram comprados juntos
     * Produtos de categorias irmãs
     * @param id Product ID
     * @param history true = send click/view to table / false = do not send (dã)
     */
    public function products_complementary($ids, $history=true)
    {
        // check ids type
        if(!is_array($ids)) {
            $ids = trim((string) $ids);
            $ids = str_replace('[', '', $ids);
            $ids = str_replace(']', '', $ids);
            $ids = explode(",", $ids);
            $id = $ids[0];
        }
        else {
            $id = (string) $ids[0];
        }

        if ($history) {
            $tracking = new Tracking($this->butler);
            $tracking->sendClick($id, 'organic', false);
        }

        $limit = isset($this->params['limit']) ? intval($this->params['limit']) : 16;
        $extra = array('intelligence' => 'products/complementary');

        // limit only if have no filters
        if (isset($this->params["filter"]))
            $filter = [
                ['$project' => ['id' => 1,'rec' => '$intelligence.complementary.rec']],
                ['$match' => ['id' => $id]]
            ];
        else
            $filter = [
                ['$project' => ['id' => 1,'rec' => '$intelligence.complementary.rec']],
                ['$match' => ['id' => $id]],
                ['$limit' => $limit]
            ];

        // get products from database
        $res = $this->db_store->collectionAggregate($filter, $this->products_enabled);

        // case no rec found OR empty rec OR intelligence time overflow
        if (
            !isset($res[0]['rec']) ||
            count($res[0]['rec']) <= 0 ||
            !$this->checkIntelligenceTime($id, 'complementary')
        ) {
            $extra['calc_method'] = 'new_index';

            if (!$this->indexer->singleProductComplementary($id, $this->products_enabled)) {
                return $this->db_store->getProducts(
                    array(
                        "ids" => [],
                        "url_params" => $this->params,
                        "extra" => $extra
                    ),
                    $this->products_enabled
                );
            }
            else {
                // get products from database
                $res = $this->db_store->collectionAggregate($filter, $this->products_enabled);

                if (!isset($res[0]['rec'])) return $this->db_store->getProducts(
                    array(
                        "ids" => [],
                        "url_params" => $this->params,
                        "extra" => $extra
                    ),
                    $this->products_enabled
                );
            }
        }
        else {
            $extra['calc_method'] = 'cache_index';
        }

        $rec = $res[0]['rec'];

        // get ids from result
        $ids = [];
        foreach ($rec as $product) {
            $ids[] = (string) $product;
        }

        // return produts
        return $this->db_store->getProducts(
            array(
                "ids" => $ids,
                "url_params" => $this->params,
                "extra" => $extra
            ),
            $this->products_enabled
        );
    }

    /**
     *
     */
    public function products_complementary2($ids, $history=true)
    {
        // check ids type
        if(!is_array($ids)) {
            $ids = trim((string) $ids);
            $ids = str_replace('[', '', $ids);
            $ids = str_replace(']', '', $ids);
            $ids = explode(",", $ids);
            $id = $ids[0];
        }
        else {
            $id = (string) $ids[0];
        }

        if ($history) {
            $tracking = new Tracking($this->butler);
            $tracking->sendClick($id, 'organic', false);
        }

        $limit = isset($this->params['limit']) ? intval($this->params['limit']) : 16;
        $extra = array('intelligence' => 'products/complementary');

        $filter = [
            array(
                '$match' => array(
                    "visitor_id" => $visitor_id,
                    "timestamp" => array(
                        '$gt' => $time
                    )
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$id',
                    'timestamp' => array(
                        '$max' => '$timestamp'
                    )
                )
            ),
            array(
                '$sort' => array(
                    'timestamp' => -1
                )
            ),
            array(
                '$limit' => 4
            )
        ];

        // get last 4 visitor history
        $res = $this->db_store->collectionAggregate($filter, "clicks");

        // get visitor history ids
        $his_ids = [];
        foreach ($res as $item) {
            $his_ids[] = (string) $item["_id"];
        }

        // get alternatives for each product
        $_ids = [];
        foreach ($his_ids as $id) {
            $req = $this->products_alternative($id, false);
            $prods = isset($req['result']) ? $req['result'] : [];
            $count = count($prods);

            // get ids from result
            $__ids = [];
            for ($i = 0; $i < $count; $i++) {
                $isOK = true;

                // check if product already exists
                foreach ($_ids as $group) {
                    if (in_array($prods[$i], $group)) {
                        $isOK = false;
                        break;
                    }
                }

                // if product do not repeat save it
                if ($isOK)
                    $__ids[] = (string) $prods[$i];
            }

            if (count($__ids) > 0)
                $_ids[] = $__ids;
        }

        $total = count($_ids);
        $_l = isset($this->params['limit']) ? $this->params['limit'] : 16;
        $k = ($total>0) ? ceil($_l / $total) : 0; // If count $_ids <= 0, then $k = 0, otherwise the php send a warning division by zero

        // distributes the result equally
        foreach ($_ids as $item) {
            for ($i = 0; $i < count($item); $i++) {
                if ($i < $k)
                    $ids[] = (string) $item[$i];
                else
                    break;
            }
        }

        // return produts
        return $this->db_store->getProducts(
            array(
                "ids" => $ids,
                "url_params" => $this->params,
                "extra" => $extra
            ),
            $this->products_enabled
        );
    }

    /**
     * @description BuyTogether Products
     * Produtos que foram comprados juntos sem filtros, independente de categorias
     * @param id Product ID
     * @param history true = send click/view to table / false = do not send (dã)
     */
    public function products_buytogether($ids, $history=true)
    {
        // check ids type
        if(!is_array($ids)) {
            $ids = trim((string) $ids);
            $ids = str_replace('[', '', $ids);
            $ids = str_replace(']', '', $ids);
            $ids = explode(",", $ids);
            $id = $ids[0];
        }
        else {
            $id = (string) $ids[0];
        }

        if ($history) {
            $tracking = new Tracking($this->butler);
            $tracking->sendClick($id, 'organic', false);
        }

        $limit = isset($this->params['limit']) ? intval($this->params['limit']) : 16;
        $extra = array('intelligence' => 'products/buytogether');

        // limit only if have no filters
        if (isset($this->params["filter"]))
            $filter = [
                ['$project' => ['id' => 1,'rec' => '$intelligence.buytogether.rec']],
                ['$match' => ['id' => $id]]
            ];
        else
            $filter = [
                ['$project' => ['id' => 1,'rec' => '$intelligence.buytogether.rec']],
                ['$match' => ['id' => $id]],
                ['$limit' => $limit]
            ];

        // get products from database
        $res = $this->db_store->collectionAggregate($filter, $this->products_enabled);

        // case no rec found OR empty rec OR intelligence time overflow
        if (
            !isset($res[0]['rec']) ||
            count($res[0]['rec']) <= 0 ||
            !$this->checkIntelligenceTime($id, 'buytogether')
        ) {
            $extra['calc_method'] = 'new_index';

            if (!$this->indexer->singleBuyTogetherProducts($id, $this->products_enabled)) {
                return $this->db_store->getProducts(
                    array(
                        "ids" => [],
                        "url_params" => $this->params,
                        "extra" => $extra
                    ),
                    $this->products_enabled
                );
            }
            else {
                // get products from database
                $res = $this->db_store->collectionAggregate($filter, $this->products_enabled);

                if (!isset($res[0]['rec'])) return $this->db_store->getProducts(
                    array(
                        "ids" => [],
                        "url_params" => $this->params,
                        "extra" => $extra
                    ),
                    $this->products_enabled
                );
            }
        }
        else {
            $extra['calc_method'] = 'cache_index';
        }

        $rec = $res[0]['rec'];

        // get ids from result
        $ids = [];
        foreach ($rec as $product) {
            $ids[] = (string) $product;
        }

        // return produts
        return $this->db_store->getProducts(
            array(
                "ids" => $ids,
                "url_params" => $this->params,
                "extra" => $extra
            ),
            $this->products_enabled
        );
    }

    /*
    #####################################
    # Special Methods
    #####################################
    */

    /**
     * @param $id
     * @param $intel
     * @return bool
     */
    private function checkIntelligenceTime($id, $intel)
    {
        $time_limit = isset($this->config['intel_time'][$intel]) ? intval($this->config['intel_time'][$intel]) : 4; // hours
        $t_limit = $time_limit * 60 * 60; // hours -> seconds

        if ($intel == "categorybestsellers") {
            $collection = $this->db_store->getSingleDocumentByQuery(['id' => $id], $this->categories_enabled);
        } else {
            $collection = $this->db_store->getSingleDocumentByQuery(['id' => $id], $this->products_enabled);
        }

        if (!$collection) return false;

        $last_update = isset($collection['intelligence'][$intel]['last_update']) ? intval($collection['intelligence'][$intel]['last_update']) : 0;
        $now = time();
        $diff = $now - $last_update;

        if ($diff > $t_limit) {
            return false;
        }

        return true;
    }
}