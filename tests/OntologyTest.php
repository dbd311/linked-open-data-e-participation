<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/* * *
 * For testing ontology services
 * @author Duy Dinh
 * @date 06/07/2016
 */

class OntologyTest extends TestCase {

    /**
     * Load eurovoc hierarchy in the facet
     *
     */
    public function testEuroVoc() {
        $this->get('get-domains/en')->see('04 POLITICS');
        $this->get('get-thesaurus-names/en/04 POLITICS')->see('political framework');
        $this->get('get-concept-names/en/0411 political party')->see('party organisation');
        $this->get('get-related-term-of-narrower-names/en/nationalism')->see('national identity');
        $this->get('get-related-term-and-narrower-names/en/political ideology')->see('political discrimination'); // RT
        $this->get('get-related-term-and-narrower-names/en/political ideology')->see('National Socialism'); // NT        
    }
    
    /***
     * Test the function for loading the lang list
     */
    public function testGetLanguages(){
        $this->get('load-languages')->see('"code3":"fra","code":"fr","name":"French (fr)"');        
    }
    
    

}
