<div id="leftMenu-container" class="col-md-4" ng-init="loadDomainNames()">
    <div class="finder">
        <div id="leftMenu-header">
            {{trans('lodepart.document-finder')}}
        </div>

        <div id="filtering-themes" class="filtering">
            <form ng-submit="filteredDocuments()">                                
                <div class="input-group keywords-search">
                    <span class="input-group-addon">
                        {{trans('lodepart.topics')}}
                        <i class="fa fa-question-circle" title="{{trans('lodepart.topics.help')}}"></i>
                    </span>
                    <textarea class="form-control theme-input" value="{{ Request::get('themes') }}" rows="3"
                              maxlength="200" ng-model="themes" ng-keyup="getSuggestions()" style="border-top-right-radius:4px;border-bottom-right-radius:4px;"></textarea>
                    <ul id="suggestions" class="typeahead theme-menu" role="menu" ng-show="suggestions.length > 0">
                        <li ng-repeat="suggestion in suggestions" auto-suggest-menu-item class="">
                            <a href="" ng-bind-html="suggestion.concept_name.value | htmlFilter" class="theme-option"
                               ng-click="addTheme(suggestion.concept_name.value)"></a>
                        </li>
                    </ul>                                                
                </div>
                <br />
                <div class="row">
                    <div class="col-xs-6 date-from">
                        <div class="input-group">
                            <span class="input-group-addon">{{trans('lodepart.date-from')}}</span>
                            <input type="text" class="form-control" value="{{ Request::get('date-from') }}"  maxlength="10" 
                                   ng-model="dateFrom" id="datepickerFrom" ng-click="dateFrom = ''">
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="input-group">
                            <span class="input-group-addon">{{trans('lodepart.date-to')}}</span>
                            <input type="text" class="form-control" value="{{ Request::get('date-to') }}" maxlength="10" 
                                   ng-model="dateTo" id="datepickerTo" ng-click="dateTo = ''">
                        </div>
                    </div>
                </div>
                <br />	
                <div id="leftMenu-header" ng-click="eurovoc = !eurovoc">
                    <i ng-hide="eurovoc" class="fa fa-plus-square" aria-hidden="true"></i>
                    <i ng-show="eurovoc" class="fa fa-minus-square" aria-hidden="true"></i>
                    {{trans('lodepart.finder-topics')}}
                </div>
                <div class="list-group">
                    <div ng-show="eurovoc">
                        <div class="checkbox">
                            <ul>
                                <li ng-repeat="domain in domains">
                                    <span ng-bind="domain.domain_name.value"></span>
                                    <i ng-show="!domain.thesaurusNames || domain.thesaurusNames.length === 0" class="fa fa-plus hand" ng-click="loadThesaurusNames(domain)"></i>
                                    <i ng-show="domain.thesaurusNames && domain.thesaurusNames.length > 0" class="fa fa-minus hand" ng-click="collapseThesaurusNames(domain)"></i>
                                    <ul>
                                        <li ng-repeat="thesaurus in domain.thesaurusNames">
                                            <span ng-bind="thesaurus.thesaurus_name.value"></span>
                                            <i ng-show="!thesaurus.conceptNames || thesaurus.conceptNames.length === 0" class="fa fa-plus hand" ng-click="loadConceptNames(thesaurus)"></i>
                                            <i ng-show="thesaurus.conceptNames && thesaurus.conceptNames.length > 0" class="fa fa-minus hand" ng-click="collapseConceptNames(thesaurus)"></i>
                                            <ul>
                                                <li ng-repeat="concept in thesaurus.conceptNames">
                                                    <input type="checkbox" name="[[concept.concept_name.value]]" ng-model="concept.checked">
                                                    <span ng-bind="concept.concept_name.value"></span>
                                                    <i ng-show="!concept.relatedTermAndNarrowerNames || concept.relatedTermAndNarrowerNames.length === 0" class="fa fa-plus hand" ng-click="loadRelatedTermAndNarrowerNames(concept)"></i>
                                                    <i ng-show="concept.relatedTermAndNarrowerNames && concept.relatedTermAndNarrowerNames.length > 0" class="fa fa-minus hand" ng-click="collapseRelatedTermAndNarrowerNames(concept)"></i>
                                                    <ul>
                                                        <li ng-repeat="narrower in concept.relatedTermAndNarrowerNames" ng-init="hasChildNarrowerTerm(narrower)">
                                                            <input type="checkbox" name="[[narrower.related_term_name.value]]" ng-model="narrower.checked">
                                                            <span ng-if="narrower.related_term_name">RT </span>
                                                            <span ng-if="narrower.related_term_name" ng-bind="narrower.related_term_name.value"></span>
                                                            <span ng-if="narrower.narrower_name">NT1 </span>
                                                            <span ng-if="narrower.narrower_name" ng-bind="narrower.narrower_name.value"></span>
                                                            <i ng-if="narrower.hasChild" ng-show="(!narrower.relatedTermOfNarrowerNames || narrower.relatedTermOfNarrowerNames.length === 0)" class="fa fa-plus hand" ng-click="loadRelatedTermOfNarrowerNames(narrower)"></i>
                                                            <i ng-if="narrower.hasChild" ng-show="narrower.relatedTermOfNarrowerNames && narrower.relatedTermOfNarrowerNames.length > 0" class="fa fa-minus hand" ng-click="collapseRelatedTermOfNarrowerNames(narrower)"></i>
                                                            <ul>
                                                                <li ng-repeat="term in narrower.relatedTermOfNarrowerNames">
                                                                    <input type="checkbox" name="[[term.related_term_narrower_name.value]]" ng-model="term.checked">
                                                                    <span>NT2 </span>
                                                                    <span ng-bind="term.narrower_of_narrower_name.value"></span>
                                                                </li>
                                                            </ul>
                                                        </li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <input type="hidden" value="{{Config::get('app.eli_lang_code')}}" name="lang_code">
                <input type="submit" class="btn btn-primary" value="{{trans('lodepart.search')}}">
            </form>
        </div>

    <!--    <div class="list-group">
            <a role="button" data-toggle="collapse" data-target="#collapse_year_proc">
                <span class="caret"></span>
                {{trans('lodepart.finder-years')}}
            </a>
            <div class="collapse in list-group-checkbox" id="collapse_year_proc" ng-init="loadProceduresYears()">
                <div class="checkbox" ng-repeat="procedureYear in procedureYears">
                    <label>
                        <input type="checkbox" ng-model="procedureYear.checked">
                        <span ng-bind="procedureYear.value"></span> (<span ng-bind="procedureYear.nb"></span>)
                    </label>
                </div>    
            </div>
        </div>        -->

        <!--<button ng-click="search()" class="btn btn-primary btn-search pull-right">{{trans('lodepart.finder-filter')}}</button>-->
    </div>
</div>

<div class="col-md-8 search-list-container" ng-init="initDocuments()">
    <div class="title-acts">
        {{trans('lodepart.title-acts')}}
    </div>
    <div class="row" ng-hide="loadingSearch">
        <div class="col-xs-7 docs-found">
            <br />
            <span ng-bind="documents.length"></span> 
            <span ng-if="documents.length <= 1 && documents.length >= 0">{{trans('lodepart.find-doc')}}</span>
            <span ng-if="documents.length > 1">{{trans('lodepart.find-docs')}}</span>
        </div>
        <div class="col-xs-5">
            <div class="input-group">
                <span class="input-group-addon">{{trans('lodepart.sort-by')}}</span>
                <select class="form-control" style="margin:0;" ng-model="selectedCriteria" ng-change="filteredDocuments()">
                    <option value="1">{{trans('lodepart.date')}}</option>
                    <option value="2">{{trans('lodepart.number-of-comments')}}</option>
                    <option value="3">{{trans('lodepart.positive-comments')}}</option>
                    <option value="4">{{trans('lodepart.neutral-comments')}}</option>
                    <option value="5">{{trans('lodepart.negative-comments')}}</option>
                </select>
            </div>
        </div>
    </div>
    <div id="search-list-items">
        
        <div ng-show="loadingSearch" class="act">
            <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.loading-search')}}
        </div>

        <div ng-hide="loadingSearch" class="row search-list-item"  ng-repeat="document in documentsDisplay">

            <div class="col-lg-10 col-md-9 col-xs-9">
                <a href="/lod/documents/displayDoc?path=[[document.path]]&hl=[[document.lang]]&lang={{Config::get('app.locale')}}">
                    <span ng-bind-html="document.title | htmlFilter"></span>
                </a>
                <p>
                    <span ng-bind="document.date"></span> 
                    <span ng-if="document.date && document.procedure"> - </span>
                    <span ng-bind="document.procedure"></span>
                </p>
                <p>
                    <span ng-bind="document.themes"></span>
                </p>
            </div>

            <div class="col-lg-2 col-md-3 col-xs-3 statist">
                <div class="panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-comments-o"></i> 
                        <span id="number-of-comments" ng-bind="document.nbOfComments"></span>
                    </div>
                    <div class="panel-body no-padding">
                        <nvd3-pie-chart
                            ng-init="calculateStatistics([[document.nbOfCommentsY]], [[document.nbOfCommentsM]], [[document.nbOfCommentsN]], [[document.nbOfComments]], [[document]])"
                            data="document.dataPieChart"
                            x="xFunction()"
                            y="yFunction()"
                            color="colorFunction()"                                    
                            width="200"
                            height="200"                                    
                            margin="{left:0,top:-50,bottom:-50,right:0}"
                            showLabels="true"
                            pieLabelsOutside="false"
                            tooltips="true"
                            labelType="percent"
                            tooltipcontent="toolTipContentFunction()">
                            <svg></svg>
                        </nvd3-pie-chart>
                    </div>                                                
                </div>                                            
            </div>  
        </div>
    </div>
    <div ng-hide="loadingSearch" class="row">
        <div class="col-xs-4">
            <div class="input-group">
                <span class="input-group-addon">{{trans('lodepart.per')}}</span>
                <select class="form-control" style="margin:0;" ng-model="nbElementsPerPage" ng-change="filteredDocuments()">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </div>
        </div>
        <div class="col-xs-8">
            <div ng-if="range().length > 1" class="align-right">
                <span ng-if="currentPage > 1" class="item-page" ng-click="previousPage()">&laquo;</span>&nbsp;
                <span ng-repeat="item in range() track by $index" class="item-page" ng-click="changePage($index + 1)">
                    <span ng-if="$index + 1 != currentPage" ng-bind="$index + 1"></span>
                    <span ng-if="$index + 1 == currentPage" ng-bind="$index + 1" class="select-current-page"></span>
                </span>&nbsp;
                <span ng-if="currentPage < range().length" class="item-page" ng-click="nextPage()">&raquo;</span>
            </div>
        </div>
    </div>
</div>

