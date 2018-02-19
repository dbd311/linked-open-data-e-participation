<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use SPARQL;
use Elastic;

/* * *
 * Index documents using Elastic Search
 * @author: Duy Dinh
 * @date: 17 May 2016
 */

class IndexController extends Controller {

    /**
     * do indexing CELLAR documents retrieved from CELLAR SPARQL endpoint
     *
     */
    public function index() {

        return view('admin.index-docs');
    }

    /**
     * Clean indices          
     */
    public function cleanIndex() {
        return view('admin.clean-index');
    }

    public function indexYear(Request $request) {
        $params = array(
            'dateFrom' => $request->get('dateFrom'),
            'dateTo' => $request->get('dateTo')
        );
        return view('index-docs')->with($params);
    }

// index documents from date1 to date 2
    public function indexDocuments(Request $request) {

        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');

        // retrieve documents from date1 to date 2
        $query = 'SELECT DISTINCT ?act ?id ?title ?space ?date WHERE {
                    ?act a sioc:Forum; sioc:has_parent ?parent;
                          sioc:id ?id; lodep:title ?title; sioc:has_space ?space; lodep:created_at ?date.';

        if (!empty($dateFrom) && $dateFrom !== 'undefined') {
            $query .= ' FILTER(?date >= "' . $dateFrom . '"^^xsd:dateTime ';
            if (!empty($dateTo) && $dateTo !== 'undefined') {
                $query .=' AND ?date <= "' . $dateTo . '"^^xsd:dateTime)';
            }
        }

        $query .= '}';

        $results = SPARQL::runSPARQLQuery($query);

        // send the results to ElasticSearch
        $jsnResults = json_decode($results);
        $counter = 0;
        foreach ($jsnResults->results->bindings as $res) {
            $eli_lang_code = substr($res->act->value, -3);
            $indexName = $eli_lang_code . '_docindex';
            $filename = public_path() . '/' . env('FORMEX.DOCUMENTS.JSON.PATH') . '/' . $res->space->value;
            if (file_exists($filename)) {
                $content = strip_tags(file_get_contents($filename));
                $jsonContent = json_decode(strip_tags($content));

                if (!Elastic::exists($indexName)) {
                    $settings = $this->getIndexSettings($indexName, $eli_lang_code);
                    Elastic::createIndex($settings);
                }
                $params = [
                    'index' => $indexName, # index per language
                    'type' => env('INDEX_TYPE', 'doc'), # preparatory_act                
                    'id' => $res->id->value . '_' . $eli_lang_code,
                    'body' => $jsonContent
                ];

                $res = Elastic::indexDocument($params);
                $counter++;
            }
        }
        // return the total number of indexed documents
        return \Response::json(['counter' => $counter]);
    }

    function getMappings($indexType, $eli_lang_code) {
        $content = '{
            "' . $indexType . '" : {    
                "_all" : {
                    "enabled" : false
                },
                "properties" : {                    
                    "num" : {
                        "type" : "string",
                        "analyzer" : "' . $eli_lang_code . '_indexer"
                    },                    
                    "year" : {
                        "type" : "string",
                        "analyzer" : "' . $eli_lang_code . '_indexer"
                    }, 
                    "title" : {
                        "type" : "string",
                        "analyzer" : "' . $eli_lang_code . '_indexer"
                    }, 
                    "subject" : {
                        "type" : "string",
                        "analyzer" : "' . $eli_lang_code . '_indexer"
                    },
                    "preamble" : {
                        "type" : "nested",
                        "properties" : {
                            "titleHtml" : { "type" : "string", "analyzer" : "' . $eli_lang_code . '_indexer" },
                            "content" : { 
                                "type" : "string", 
                                "analyzer" : "' . $eli_lang_code . '_indexer",
                                "fields" : {"std" : {"type" : "string", "analyzer" : "standard"}}
                            }    
                        }
                    },                     
                    "sections" : {
                        "type" : "nested",
                        "properties" : {
                            "title" : { "type" : "string", "analyzer" : "' . $eli_lang_code . '_indexer" },
                            "subject" : { "type" : "string", "analyzer" : "' . $eli_lang_code . '_indexer"},
                            "content" : { "type" : "string", "analyzer" : "' . $eli_lang_code . '_indexer",
                                "fields" : {"std" : {"type" : "string", "analyzer" : "standard"}}}                            
                        }
                    }
                }
            }
        }';

        return json_decode($content);
    }

    public function getIndexSettings($index, $eli_lang_code) {
        $settings = '';
        $indexType = env('INDEX_TYPE', 'doc');
        
        switch ($eli_lang_code) {
            case 'eng' :
                $language = 'english';
                $settings = $this->get_eng_settings($index, $indexType, $eli_lang_code, $language);
                break;
            case 'fra' :
                $language = 'french';
                $settings = $this->get_fra_settings($index, $indexType, $eli_lang_code, $language);
                break;
            case 'deu' :
                $language = 'german';
                $settings = $this->get_deu_settings($index, $indexType, $eli_lang_code, $language);
                break;
            case 'ell' :
                $language = 'greek';
                $settings = $this->get_ell_settings($index, $indexType, $eli_lang_code, $language);
                break;
            case 'ita' :
                $language = 'italian';
                $settings = $this->get_ita_settings($index, $indexType, $eli_lang_code, $language);
                break;
            case 'spa' :
                $language = 'spanish';
                $settings = $this->get_spa_settings($index, $indexType, $eli_lang_code, $language);
                break;
            default:
                $settings = $this->get_default_settings($index);
                break;
        }
        return $settings;
    }

    function get_fra_settings($index, $indexType, $eli_lang_code, $language) {
        return [
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'filter' => [
                            $language . '_elision' => [
                                "type" => "elision",
                                "articles" => ["l", "m", "t", "qu", "n", "s", "j"]
                            ],
                            $language . '_stop' => [
                                'type' => 'stop',
                                'stopwords' => '_' . $language . '_'
                            ],
                            'light_' . $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => 'light_' . $language
                            ],
                            $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => $language
                            ],
                            'stemmer' => [
                                'type' => 'snowball',
                                'language' => $language
                            ]
                        ],
                        'analyzer' => [
                            $eli_lang_code . '_indexer' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_elision', $language . '_stop', 'light_' . $language . '_stemmer', 'asciifolding'//, 'snowball'
                                ]
                            ],
                            $eli_lang_code . '_searcher' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_elision', $language . '_stop', 'light_' . $language . '_stemmer', 'asciifolding' //, 'snowball'
                                ],
                                'query_mode' => true
                            ]
                        ]
                    ]
                ]
                ,
                'mappings' => $this->getMappings($indexType, $eli_lang_code)
            ]
        ];
    }

    function get_eng_settings($index, $indexType, $eli_lang_code, $language) {
        return [
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'filter' => [
                            $language . '_stop' => [
                                'type' => 'stop',
                                'stopwords' => '_' . $language . '_'
                            ],
                            $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => 'light_' . $language
                            ],
                            $language . '_possessive_stemmer' => [
                                'type' => 'stemmer',
                                'language' => 'possessive_' . $language
                            ]
                        ],
                        'analyzer' => [
                            $eli_lang_code . '_indexer' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    $language . '_possessive_stemmer', 'lowercase', $language . '_stop', $language . '_stemmer', 'asciifolding'
                                ]
                            ],
                            $eli_lang_code . '_searcher' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    $language . '_possessive_stemmer', 'lowercase', $language . '_stop', $language . '_stemmer', 'asciifolding'
                                ],
                                'query_mode' => true
                            ]
                        ]
                    ]
                ]
                ,
                'mappings' => $this->getMappings($indexType, $eli_lang_code)
            ]
        ];
    }

    function get_deu_settings($index, $indexType, $eli_lang_code, $language) {
        return [
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'filter' => [
                            $language . '_stop' => [
                                'type' => 'stop',
                                'stopwords' => '_' . $language . '_'
                            ],
                            'light_' . $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => 'light_' . $language
                            ],
                            $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => $language
                            ],
                            'stemmer' => [
                                'type' => 'snowball',
                                'language' => $language
                            ]
                        ],
                        'analyzer' => [
                            $eli_lang_code . '_indexer' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_stop', $language . '_normalization', 'light_' . $language . '_stemmer', 'asciifolding'//, 'snowball'
                                ]
                            ],
                            $eli_lang_code . '_searcher' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_stop', $language . '_normalization', 'light_' . $language . '_stemmer', 'asciifolding' //, 'snowball'
                                ],
                                'query_mode' => true
                            ]
                        ]
                    ]
                ],
                'mappings' => $this->getMappings($indexType, $eli_lang_code)
            ]
        ];
    }
    
    function get_ell_settings($index, $indexType, $eli_lang_code, $language) {
        return [
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'filter' => [
                            $language . '_stop' => [
                                'type' => 'stop',
                                'stopwords' => '_' . $language . '_'
                            ],
                            'light_' . $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => 'light_' . $language
                            ],
                            $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => $language
                            ],
                            'stemmer' => [
                                'type' => 'snowball',
                                'language' => $language
                            ]
                        ],
                        'analyzer' => [
                            $eli_lang_code . '_indexer' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_stop', $language . '_stemmer', 'asciifolding'//, 'snowball'
                                ]
                            ],
                            $eli_lang_code . '_searcher' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_stop', $language . '_stemmer', 'asciifolding' //, 'snowball'
                                ],
                                'query_mode' => true
                            ]
                        ]
                    ]
                ],
                'mappings' => $this->getMappings($indexType, $eli_lang_code)
            ]
        ];
    }

    function get_ita_settings($index, $indexType, $eli_lang_code, $language) {
        return [
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'filter' => [
                            $language . '_elision' => [
                                "type" => "elision",
                                "articles" => ["c", "l", "all", "dall", "dell", "nell", "sull", "coll", "pell", "gl", "agl", "dagl", "degl", "negl", "sugl", "un", "m", "t", "s", "v", "d"]
                            ],
                            $language . '_stop' => [
                                'type' => 'stop',
                                'stopwords' => '_' . $language . '_'
                            ],
                            'light_' . $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => 'light_' . $language
                            ],
                            $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => $language
                            ],
                            'stemmer' => [
                                'type' => 'snowball',
                                'language' => $language
                            ]
                        ],
                        'analyzer' => [
                            $eli_lang_code . '_indexer' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_elision', $language . '_stop', 'light_' . $language . '_stemmer', 'asciifolding'//, 'snowball'
                                ]
                            ],
                            $eli_lang_code . '_searcher' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_elision', $language . '_stop', 'light_' . $language . '_stemmer', 'asciifolding' //, 'snowball'
                                ],
                                'query_mode' => true
                            ]
                        ]
                    ]
                ],
                'mappings' => $this->getMappings($indexType, $eli_lang_code)
            ]
        ];
    }
    
    function get_spa_settings($index, $indexType, $eli_lang_code, $language) {
        return [
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'filter' => [
                            $language . '_stop' => [
                                'type' => 'stop',
                                'stopwords' => '_' . $language . '_'
                            ],
                            'light_' . $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => 'light_' . $language
                            ],
                            $language . '_stemmer' => [
                                'type' => 'stemmer',
                                'language' => $language
                            ],
                            'stemmer' => [
                                'type' => 'snowball',
                                'language' => $language
                            ]
                        ],
                        'analyzer' => [
                            $eli_lang_code . '_indexer' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_stop', $language . '_stemmer', 'asciifolding'//, 'snowball'
                                ]
                            ],
                            $eli_lang_code . '_searcher' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase', $language . '_stop', $language . '_stemmer', 'asciifolding' //, 'snowball'
                                ],
                                'query_mode' => true
                            ]
                        ]
                    ]
                ],
                'mappings' => $this->getMappings($indexType, $eli_lang_code)
            ]
        ];
    }

    function get_default_settings($index) {
        return [
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'analyzer' => [
                            'default' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get indices from ElasticSearch
     */
    public function getIndices() {
        $indices = Elastic::getIndices();
        return \Response::json($indices);
    }

    /*     * *
     * Clean a particular index or all indices
     */

    public function cleanIndices(Request $r) {

        if (empty($r->i)) {
            error_log('Deleting all indices ....');
            $response = Elastic::deleteAllIndices();
        } else {
            error_log('Deleting index ' . $r->i . '....');
            $response = Elastic::cleanIndexByName($r->i);
        }
        return \Response::json($response);
    }

}
