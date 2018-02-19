<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Concepts;

/**
 * Filter maker for SPARQL queries
 *
 * @author Duy Dinh
 * @date 06/06/2016
 */
class Filter {

    /**
     * create a filter for containers
     * @param type $containers a list of containers
     * @return string
     */
    public static function create_filter_containers($containers) {
        $filter = 'FILTER(';
        for ($i = 0; $i < sizeof($containers) - 1; $i++) {

            $filter .= '?container=<' . $containers[$i] . '> || ';
        }
        if (sizeof($containers) > 0) {
            $filter .= '?container=<' . $containers[$i] . '>)';
        }
        return $filter;
    }

    /**
     * create a filter for retrieving ascendant containers
     * @param type $containers a list of containers
     * @return string
     */
    public static function create_filter_ascendant_containers($containers) {
        $filter = 'FILTER((';
        for ($i = 0; $i < sizeof($containers) - 2; $i++) {
            $filter .= '?container=<' . $containers[$i] . '> || ';
        }

        if (sizeof($containers) > 0) {
            $filter .= '?container=<' . $containers[$i] . '>)';
        }

        $filter .= ')';

        return $filter;
    }

    public static function create_filter_years($yearList) {
        $years = preg_split('/[,]+/', $yearList, -1, PREG_SPLIT_NO_EMPTY);
        $filter = '';
        $length = sizeof($years);
        if ($length > 0) {
            $filter .= 'FILTER(';
            $i = 0;
            for (; $i < $length - 1; $i++) {
                $filter .= '((?year >= "' . $years[$i] . '"^^xsd:gYear) AND (?year <= "' . $years[$i] . '"^^xsd:gYear)) || ';
            }
            $filter .= '((?year >= "' . $years[$i] . '"^^xsd:gYear) AND (?year <= "' . $years[$i] . '"^^xsd:gYear)))';
        }

        return $filter;
    }

}
