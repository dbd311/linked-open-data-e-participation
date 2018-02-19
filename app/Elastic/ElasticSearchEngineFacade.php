<?php

 
namespace App\Elastic;

/**
 * Description of SPARQLEngineFacade
 *
 * @author Duy Dinh <dinhbaduy@gmail.com>
 * @date 29 April 2016
 */

use Illuminate\Support\Facades\Facade;

class ElasticSearchEngineFacade extends Facade {

    protected static function getFacadeAccessor() {
        return 'ElasticSearchEngine';
    }

}
