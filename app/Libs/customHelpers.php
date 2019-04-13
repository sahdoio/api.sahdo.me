<?php

if (!function_exists('bcrypt')) {

   function bcrypt($value, $options = [])
   {
       return app('hash')->make($value, $options);
   }

}

if (!function_exists('is_money')) {
    /**
     * Validate money in US and other patterns without the prefix or sufix.
     * Only validates numbers with commas and dots.
     * Ex: 100,00  // is valid
     * Ex: 100.00  // is valid
     * Ex: 100a00  // is invalid
     * Ex: 1,000.0 // is valid
     * Ex: 1.000,0 // is valid
     * @param string $number
     *
     * @return bool
     */
    function is_money($number)
    {
        return preg_match("/^[0-9]{1,3}(,?[0-9]{3})*(\.[0-9]{1,6})?$/", $number) ||
            preg_match("/^[0-9]{1,3}(\.?[0-9]{3})*(,[0-9]{1,6})?$/", $number);
    }

}

if (!function_exists('_dd')) {
    function _dd(...$args)
    {
        foreach ($args as $arg) {
            echo "<pre>";
            var_dump($arg);
            echo "<pre>";
        }
        die;
    }
}

if (!function_exists('ddd')) {
    function ddd(...$args)
    {
        foreach ($args as $arg) {
            echo "<pre>";
            print_r($arg);
            echo "<pre>";
        }
        die;
    }
}

if (!function_exists('numberFloat')) {
    function numberFloat($price)
    {
        $price = strip_tags($price);
        $price = str_replace("De:", "", $price);
        $price = str_replace("De", "", $price);
        $price = str_replace("Por", "", $price);
        $price = str_replace("R$", "", $price);
        $price = str_replace("BRL", "", $price);
        $price = str_replace(" ", "", $price);
        $price = str_replace(".", "", $price);
        $price = str_replace(",", ".", $price);

        return floatval(number_format((float) $price, 6, '.', ''));
    }
}

if (!function_exists('array2Object')) {
    function array2Object($array)
    {
        return json_decode(json_encode($array), false);
    }
}