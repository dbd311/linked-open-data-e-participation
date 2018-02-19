<?php

namespace App\Utils;

/**
 * DateProcessing
 *
 * @author Duy Dinh <dinhbaduy@gmail.com>
 * @date 06/06/2016
 */
class DateProcessing {

    public static function checkDateStart($date) {
        if (is_numeric($date)) {
            // then it is supposed that it is a year
            // add the first day of the month to this year
//            error_log('yes it is an integer');
            return $date . '-01-01'; // yyyy-MM-dd
        } else {
//             error_log('no ' . $date . ' is not an integer');
            return $date;
        }
    }

    public static function checkDateEnd($date) {
        if (is_numeric($date)) {
            // then it is supposed that it is a year
            // add the last day of the last month for this year
            return $date . '-12-31'; // yyyy-MM-dd
        } else {
            return $date;
        }
    }
}
