<?php
/**
 * Created by PhpStorm.
 * User: lucas
 * Date: 30/11/18
 * Time: 23:22
 */

namespace App\Libs;


class FilterQL
{
// contructor method
    function __construct() {
        // ..
    }

    /*
     * Starts filter sequence
     */
    public function generateFilter($exp) {
        $filter = $filter_or = array();
        $arr_or = explode(" or ", $exp);

        // CASE HAS OR/AND EXPERESSIONS
        if (count($arr_or) > 1) {
            foreach ($arr_or as $or) {
                $or = trim($or);
                $arr_and = explode(" and ", $or);

                // AND EXPRESSIONS
                if (count($arr_and) > 1) {
                    $filter_and = array();
                    foreach ($arr_and as $and) {
                        $and = trim($and);
                        $filter_and[] = $this->baseLogic($and);
                    }

                    $filter_and = array(
                        '$and' => $filter_and
                    );

                    $filter_or[] = $filter_and;
                }
                else {
                    $filter_or[] = $this->baseLogic($or);
                }
            }

            $filter = array(
                '$match' => array(
                    '$or' => $filter_or
                )
            );
        }
        // CASE HAS ONLY AND EXPRESSIONS
        else {
            $arr_and = explode(" and ", $exp);

            if (count($arr_and) > 1) {
                $filter_and = array();
                foreach ($arr_and as $and) {
                    $and = trim($and);
                    $filter_and[] = $this->baseLogic($and);
                }

                $filter = array(
                    '$match' => array(
                        '$and' => $filter_and
                    )
                );
            }
            else {
                $logic = $this->baseLogic($exp);

                if (isset($logic) && count($logic) > 0) {
                    $filter = array(
                        '$match' =>  $logic
                    );
                }
            }
        }

        return $filter;
    }

    private function baseLogic($exp) {
        $filter = array();

        switch (true) {
            case $this->in_string($exp, '='):
                $filter = $this->f_equals($exp);
                break;

            case $this->in_string($exp, ' not contains '):
                $filter = $this->f_notcontains($exp);
                break;

            case $this->in_string($exp, ' contains '):
                $filter = $this->f_contains($exp);
                break;

            case $this->in_string($exp, ' not in '):
                $filter = $this->f_notin($exp);
                break;

            case $this->in_string($exp, ' in '):
                $filter = $this->f_in($exp);
                break;

            case $this->in_string($exp, '>'):
                $filter = $this->f_gt($exp);
                break;

            case $this->in_string($exp, '>='):
                $filter = $this->f_gte($exp);
                break;

            case $this->in_string($exp, '<'):
                $filter = $this->f_lt($exp);
                break;

            case $this->in_string($exp, '<='):
                $filter = $this->f_lte($exp);
                break;

            case $this->in_string($exp, ' like '):
                $filter = $this->f_like($exp);
                break;

            default:
                # code...
                break;
        }

        return $filter;
    }

    /*
     * Filter Logic Methods
     */

    private function f_equals($query) {
        $query = explode("=", $query);

        $field = trim($query[0]);
        $value = $this->prepareType(trim($query[1]));

        if (
            (strtolower($field) == 'categories') ||
            (strtolower($field) == 'id') ||
            (strtolower($field) == 'sku')
        ) {
            $value = (string) $value;
        }

        return array(
            $field => $value
        );
    }

    private function f_gt($query) {
        $query = explode(">", $query);

        $field = trim($query[0]);
        $value = $this->prepareType(trim($query[1]));

        return array(
            $field => array(
                '$gt' => $value
            )
        );
    }

    private function f_gte($query) {
        $query = explode(">=", $query);

        $field = trim($query[0]);
        $value = $this->prepareType(trim($query[1]));

        return array(
            $field => array(
                '$gte' => $value
            )
        );
    }

    private function f_lt($query) {
        $query = explode("<", $query);

        $field = trim($query[0]);
        $value = $this->prepareType(trim($query[1]));

        return array(
            $field => array(
                '$lt' => $value
            )
        );
    }

    private function f_lte($query) {
        $query = explode("<=", $query);

        $field = trim($query[0]);
        $value = $this->prepareType(trim($query[1]));

        return array(
            $field => array(
                '$lte' => $value
            )
        );
    }

    private function f_contains($query) {
        $query = explode(" contains ", $query);

        $field = trim($query[0]);
        $value = $this->prepareType(trim($query[1]));

        if (
            (strtolower($field) == 'categories') ||
            (strtolower($field) == 'id') ||
            (strtolower($field) == 'sku')
        ) {
            $value = (string) $value;
        }

        return array(
            $field => array(
                '$in' => array($value)
            )
        );
    }

    private function f_notcontains($query) {
        $query = explode(" not contains ", $query);

        $field = trim($query[0]);
        $value = $this->prepareType(trim($query[1]));

        if (
            (strtolower($field) == 'categories') ||
            (strtolower($field) == 'id') ||
            (strtolower($field) == 'sku')
        ) {
            $value = (string) $value;
        }

        return array(
            $field => array(
                '$nin' => array($value)
            )
        );
    }

    private function f_in($query) {
        $query = explode(" in ", $query);

        $field = trim($query[0]);
        $value = trim($query[1]);

        $filter = array();
        if (strpos($value, '[') === 0) {
            $value = str_replace("[", "", $value);
            $value = str_replace("]", "", $value);
            $values = explode(",", $value);

            $list = array();
            foreach ($values as $v) {
                $v = $this->prepareType(trim($v));

                if (isset($v) && !empty($v)) {
                    if (
                        (strtolower($field) == 'categories') ||
                        (strtolower($field) == 'id') ||
                        (strtolower($field) == 'sku')
                    ) {
                        $list[] = (string) $v;
                    }
                    else {
                        $list[] = $v;
                    }
                }
            }

            $filter = array(
                $field => array(
                    '$in' => $list
                )
            );
        }

        return $filter;
    }

    private function f_notin($query) {
        $query = explode(" not in ", $query);

        $field = trim($query[0]);
        $value = trim($query[1]);

        $filter = array();
        if (strpos($value, '[') === 0) {
            $value = str_replace("[", "", $value);
            $value = str_replace("]", "", $value);
            $values = explode(",", $value);

            $list = array();
            foreach ($values as $v) {
                $v = $this->prepareType(trim($v));

                if (isset($v) && !empty($v)) {
                    if (
                        (strtolower($field) == 'categories') ||
                        (strtolower($field) == 'id') ||
                        (strtolower($field) == 'sku')
                    ) {
                        $list[] = (string) $v;
                    }
                    else {
                        $list[] = $v;
                    }
                }
            }

            $filter = array(
                $field => array(
                    '$nin' => $list
                )
            );
        }

        return $filter;
    }

    private function f_like($query) {
        $query = explode(" like ", $query);

        $field = trim($query[0]);
        $value = $this->prepareType(trim($query[1]));

        if (
            (strtolower($field) == 'categories') ||
            (strtolower($field) == 'id') ||
            (strtolower($field) == 'sku')
        ) {
            $value = (string) $value;
        }

        return array(
            $field => [
                '$regex' => $value,
                '$options' => 'i'
            ]
        );
    }

    /*
     * Special Methods
     */

    private function prepareType($value) {
        // variable type area
        switch (true) {
            case $this->checkBool($value):
                if ($value === "true") {
                    $value = true;
                } else {
                    $value = false;
                }
                break;

            case $this->checkInteger($value):
                $value = intval($value);
                break;

            case $this->checkDouble($value):
                $value = floatval($value);
                break;

            default:
                $value = (string) $value;

                if (strpos($value, '"') === 0 || strpos($value, "'") === 0) {
                    $value = substr($value, 1, strlen($value) - 2);
                }

                break;
        }

        return $value;
    }

    private function in_string($string, $substring) {
        if (strpos($string, $substring) !== false) {
            return true;
        } else {
            return false;
        }
    }

    private function checkInteger($value) {
        if (preg_match('/^[0-9]+$/', $value)) {
            return true;
        } else {
            return false;
        }
    }

    private function checkDouble($value) {
        if (is_numeric($value) && strpos($value, ".") !== false) {
            return true;
        } else {
            return false;
        }
    }

    private function checkBool($value) {
        $value = strtolower($value);
        return (in_array($value, array("true", "false"), true));
    }
}