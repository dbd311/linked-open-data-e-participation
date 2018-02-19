<?php

namespace App\Retriever;

use App\Concepts\Themes;

/**
 * Description of DocumentFilter
 *
 * @author duy
 */
class DocumentFilter extends Retriever {
    /*     * *
     * Multitle criteria for document filtering
     */

    protected $dateFrom;
    protected $themes;
    protected $sortBy;
    protected $lang_code;
    protected $eli_lang_code;

    /**
     * Filter documents w.r.t multiple criteria
     */
    /*public function retrieveAndFilter() {

        $filter_themes = Themes::create_filter_themes($this->themes, $this->eli_lang_code);

        // create a query for filtering documents
        $query = 'PREFIX sioc: <http://rdfs.org/sioc/ns#> 
  	SELECT DISTINCT ?act ?id ?title ?theme ?date ?procedure ?yes ?mixed ?no ?total
	WHERE {
        ?act sioc:has_parent ?parent.
	?act sioc:id ?id. ?act lodep:topic ?topic. ?topic rdfs:label ?theme.' .
               //add theme filter 
                $filter_themes .
                '?act lodep:title ?title. FILTER (LANG(?title)="' . $this->eli_lang_code . '") ?act lodep:created_at ?date. ';

        if (!empty($this->dateFrom) && (trim($this->dateFrom) !== '')) {
            $query .= ' FILTER((?date >= "' . $this->dateFrom . '"^^xsd:dateTime)';
            if (!empty($this->dateTo) && (trim($this->dateTo) !== '')) {
                $query .= ' AND (?date <= "' . $this->dateTo . '"^^xsd:dateTime)';
            }
            $query .= ')';
        }


        $query .= ' ?act lodep:procedure_type ?pr. ?pr rdfs:label ?procedure. FILTER(LANG(?procedure)="' . $this->eli_lang_code . '")
        ?act lodep:num_items_yes ?yes; lodep:num_items_mixed ?mixed; lodep:num_items_no ?no; lodep:num_items_total ?total.}';

        if (!empty($this->sortBy) && (trim($this->sortBy) !== '')) {
            switch ($this->sortBy) {
                case 1:
                    $criteria = '?total';
                    break;
                case 2:
                    $criteria = '?yes';
                    break;
                case 3:
                    $criteria = '?mixed';
                    break;
                case 4:
                    $criteria = '?no';
                    break;
                default :
                    $criteria = '?date';
                    break;
            }

            $query .= ' ORDER BY DESC(' . $criteria . ')';
        }

//        echo urlencode($query) . '<br><br>';
        $this->processQuery($query);

        $this->resetCriteria();
    }*/

   /* public function setLanguages($lang_code, $eli_lang_code) {
        $this->lang_code = $lang_code;
        $this->eli_lang_code = $eli_lang_code;
    }*/

  /*  public function setCriteria($themes, $dateFrom, $dateTo, $sortBy) {
        $this->themes = $themes;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->sortBy = $sortBy;
    }*/

   /* public function resetCriteria() {
        $this->themes = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->sortBy = null;
    }*/
}
