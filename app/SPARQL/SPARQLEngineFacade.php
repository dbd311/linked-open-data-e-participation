<?php

namespace App\SPARQL;

/**
 * SPARQLEngineFacade
 *
 * @author Duy Dinh
 * @date 21 March 2016
 */
use Illuminate\Support\Facades\Facade;

class SPARQLEngineFacade extends Facade {

    protected static function getFacadeAccessor() {
        return 'SPARQLEngine';
    }

}
