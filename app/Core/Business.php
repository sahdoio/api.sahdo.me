<?php


namespace App\Core;

use App\Libs\Butler;
use App\Libs\FilterQL;

class Business
{
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
}