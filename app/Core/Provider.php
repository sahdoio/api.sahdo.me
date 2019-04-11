<?php
/**
 * Created by PhpStorm.
 * User: lucas
 * Date: 01/03/19
 * Time: 06:39
 */

namespace App\Core;

use App\Libs\Butler;

class Provider
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
    public function start()
    {
        $response = [];
        if ($this->config['general']['active'] !== true) {
            $response['status'] = 'error';
            $response['message'] = 'Hintify info: Store disable in our base. Please contact hintify for more informations.';
            $response['details'] = false;
        }
        else {
            $res_details = [];
            $res_details['optimizers'] = $this->getOptimizers();
            $res_details['general'] = $this->config['general'];
            $res_details['variables'] = $this->config['variables'];

            $response['status'] = 'success';
            $response['message'] = 'Hintify info: Store online.';
            $response['details'] = $res_details;
        }

        return $response;
    }

    /**
     * @description Method used to load and verify each product which is active in customer store.
     * For every new product created, it is necessary to create a input whithin this method
     * @return array
     */
    private function getOptimizers()
    {
        $optimizers = $this->config['optimizers'];
        $actives = [];

        if (isset($optimizers['recommendations'])) {
            if ($optimizers['recommendations'] == true) {
                $actives['recommendations']['status'] = true;
                $actives['recommendations']['parameters'] = $this->getRecommendations();
            }
        }

        if (isset($optimizers['livesearches'])) {
            if ($optimizers['livesearches'] == true) {
                $actives['livesearches']['status'] = true;
                $actives['livesearches']['parameters'] = $this->getLiveSearches();
            }
        }

        if (isset($optimizers['exitintent'])) {
            if ($optimizers['exitintent'] == true) {
                $actives['exitintent']['status'] = true;
            }
        }

        return $actives;
    }

    /**
     * @description Methos used in recommendations
     * @return array
     */
    private function getRecommendations()
    {
        $filter = [
            ['$match'=>['status'=>['$ne'=>false]]],
            ['$project'=>[
                '_id'=>0,
                "id"=>1,
                "intelligence"=>1,
                "limit"=>1,
                "design_id"=>1,
                "filter"=>1,
                "vars"=>1,
                "labels"=>1,
                "random"=>1,
                "active"=>1,
                "status"=>1,
                "execute_after_render"=>1,
                "target"=>1,
                "device"=>1,
                "slider"=>1,
                "mask"=>1,
                "target_button"=>1,
                "trigger_elements"=>1,
                "target_input"=>1,
                "fade"=>1,
                "call_back"=>1,
                "empty_message"=>1,
                "show_brand"=>1,
                "show_brand_type"=>1,
                "place_holder"=>1,
                "global_var"=>1
            ]],
            ['$lookup'=>[
                'from' => 'designs',
                'localField' => 'design_id',
                'foreignField' => 'id',
                'as' => 'design'
            ]]
        ];

        $contents = $this->db_store->collectionAggregate($filter, 'contents', 500);

        $new_contents = [];

        foreach ($contents as $index => $value) {
            if (!isset($value['call_back'])) $value['call_back'] = '';

            $new_contents[$value['id']] = [];
            $new_contents[$value['id']]['id'] = $value['id'];
            $new_contents[$value['id']]['execute_external_function'] = $value['execute_after_render'];
            $new_contents[$value['id']]['target'] = $value['target'];
            $new_contents[$value['id']]['vars'] = $value['vars'];
            $new_contents[$value['id']]['slider'] = $value['slider'];
            $new_contents[$value['id']]['device'] = $value['device'];
            $new_contents[$value['id']]['status'] = $value['status'];
            $new_contents[$value['id']]['active'] = $value['active'];
            $new_contents[$value['id']]['javascript_block_id'] = $value['global_var'];
        }

        return $new_contents;
    }

    /**
     * @description Methos used in recommendations
     * @return array
     */
    private function getLiveSearches()
    {
        $filter = [
            ['$match'=>['status'=>['$ne'=>false]]],
            ['$project'=>[
                '_id'=>0,
                "id"=>1,
                "intelligence"=>1,
                "limit"=>1,
                "design_id"=>1,
                "filter"=>1,
                "vars"=>1,
                "labels"=>1,
                "random"=>1,
                "active"=>1,
                "status"=>1,
                "execute_after_render"=>1,
                "target"=>1,
                "device"=>1,
                "slider"=>1,
                "mask"=>1,
                "target_button"=>1,
                "trigger_elements"=>1,
                "target_input"=>1,
                "fade"=>1,
                "call_back"=>1,
                "empty_message"=>1,
                "show_brand"=>1,
                "show_brand_type"=>1,
                "place_holder"=>1,
                "global_var"=>1
            ]],
            ['$lookup'=>[
                'from' => 'designs',
                'localField' => 'design_id',
                'foreignField' => 'id',
                'as' => 'design'
            ]]
        ];

        $contents = $this->db_store->collectionAggregate($filter, 'livesearches', 500);

        $new_contents = [];

        foreach($contents as $index => $value) {
            if (!isset($value['call_back'])) $value['call_back'] = '';

            $new_contents[$value['id']] = [];
            $new_contents[$value['id']]['id'] = $value['id'];
            $new_contents[$value['id']]['execute_external_function'] = $value['execute_after_render'];
            $new_contents[$value['id']]['target'] = $value['target'];
            $new_contents[$value['id']]['vars'] = $value['vars'];
            $new_contents[$value['id']]['show_brand'] = $value['show_brand'];
            $new_contents[$value['id']]['show_brand_type'] = $value['show_brand_type'];
            $new_contents[$value['id']]['target_button'] = $value['target_button'];
            $new_contents[$value['id']]['target_input'] = $value['target_input'];
            $new_contents[$value['id']]['trigger_elements'] = $value['trigger_elements'];
            $new_contents[$value['id']]['mask'] = $value['mask'];
            $new_contents[$value['id']]['place_holder'] = $value['place_holder'];
            $new_contents[$value['id']]['fade'] = $value['fade'];
            $new_contents[$value['id']]['slider'] = $value['slider'];
            $new_contents[$value['id']]['device'] = $value['device'];
            $new_contents[$value['id']]['status'] = $value['status'];
            $new_contents[$value['id']]['active'] = $value['active'];
            $new_contents[$value['id']]['javascript_block_id'] = $value['global_var'];
        }
        return $new_contents;
    }
}