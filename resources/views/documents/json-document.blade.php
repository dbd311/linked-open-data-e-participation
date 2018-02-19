@extends('layouts.lodepart-fancy')

@section('css-local')
<link rel="stylesheet" type="text/css" href="/css/formex/act_xml.css"/>
@stop

@section('title')
<title><?php echo $path; ?></title>
@stop

@section('page-name')
<li>{{trans('lodepart.preparatory-act')}}</li>
@stop

<?php

use Illuminate\Support\Facades\Session;

if (Session::get('user') == null) {
    $userId = '0';
} else {
    $userId = Session::get('user')->user_id->value;
}
$path = Request::get('path');
$hl = Request::get('hl');
    $sectionId = Request::get('section');
?>

@section('adaptable-area')
<div id="page-content">

    <div class="container-fluid" ng-controller="documentCtrl" ng-init="loadDocument('{{$path}}', '{{$hl}}', '{{$userId}}', '{{$sectionId}}')">

        <div class="container-fluid doc-table">   

            <div id="loading" class="act center">
                <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.loading')}}
            </div>

            <div hidden id="allelements" class="act">

                <div class="header-document row">  
                    <div class="col-md-8 col-header-metadata">
                        <div class="title-document">
                            <span ng-bind-html="document.titleHtml | htmlFilter"></span>
                            <span ng-bind-html="document.subjectHtml | htmlFilter"></span>
                        </div>
                        <br />
                        <p ng-if="document.idCelex">
                            {{trans('lodepart.title-procedure-code')}} : <span ng-bind="document.procedureCode"></span> 
                        </p>
                        <p ng-if="document.idCelex">
                            {{trans('lodepart.celex')}} : <span ng-bind="document.idCelex"></span> 
                            <i class="fa fa-question-circle" aria-hidden="true" title="{{trans('lodepart.title-id-celex')}}"></i>
                        </p>
                        <p ng-if="document.dateAdopted">
                            {{trans('lodepart.title-date-adopted')}} : <span ng-bind="document.dateAdopted"></span> 
                        </p>
                        <p ng-if="document.procedureTypeLabel">
                            {{trans('lodepart.title-type-label')}} : <strong><span ng-bind="document.procedureTypeLabel"></span></strong> 
                        </p>
                        <p ng-if="document.directoryCode">
                            {{trans('lodepart.title-directory-code')}} : 
                            <span ng-bind="document.directoryCode"></span> 
                        </p>
                        <p>
                            {{trans('lodepart.topics')}} : <i ng-bind="document.topics"></i>
                        </p>
                        <div ng-repeat="annexe in document.annexes">
                            <i class="fa fa-file-pdf-o fa-lg" aria-hidden="true"></i> 
                            <a target="_blank" href="/../collection/formex-documents-json/[[document.folder]]/annexes/[[annexe]]">
                                <span ng-bind="annexe"></span>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-2 col-header-comments">
                        <div ng-if="nbTotalComments != 0">
                            <nvd3-pie-chart
                                ng-if="nbTotalComments != 0"
                                data="dataPieChartDocument"
                                x="xFunction()"
                                y="yFunction()"
                                color="colorFunction()"                        
                                width="300"
                                height="300" 
                                showLabels="true"
                                pieLabelsOutside="false"
                                tooltips="true"
                                tooltipcontent="toolTipContentFunction()"
                                labelType="percent">
                                <svg class="pie-chart-document">
                                </svg>
                            </nvd3-pie-chart>
                        </div>
                    </div>
                    <div class="col-md-2 col-header-comments">
                        <div ng-if="nbTotalComments != 0">
                            <div class="center">
                                <span class="fa fa-comment fa-2x btn-inbody right" ng-bind="' ' + nbTotalComments"></span>
                                <br/><br/>
                                <!--<span class="fa fa-pencil fa-2x btn-inbody right" ng-bind="' ' + nbTotalAmmendements"></span>-->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="languagues-section">
                    <span ng-if="document.num == 'INFOHUB'">{{trans('lodepart.lang-document')}}  : English</span>
                    <div class="input-group col-lg-4 col-md-5 col-xs-7">
                        <span class="input-group-addon">{{trans('lodepart.lang-document')}}</span>
                        <select class="form-control" ng-show="document.num != 'INFOHUB'" ng-model="languageDocument" ng-change="changeDocumentLanguage()" title="{{trans('lodepart.lang')}}">
                            <option ng-repeat="language in listLanguages" value="[[language.code3]]" ng-bind="language.name"></option>
                        </select>
                    </div>
                    <br />
                    <span class="hand" ng-show="collapseAll" ng-click="collapsedAll(true)">
                        <i class="fa fa-lg fa-minus-square" aria-hidden="true"></i> 
                        {{trans('lodepart.collapse-all')}}
                    </span>
                    <span class="hand" ng-hide="collapseAll" ng-click="collapsedAll(false)">
                        <i class="fa fa-lg fa-plus-square" aria-hidden="true"></i> 
                        {{trans('lodepart.expand-all')}}
                    </span>
                </div>

                <div class="[[sizeDocument]]">
                    <div class="col-section" id="col-section">
                        <div class="col-body" ng-hide="hidesection">
                            <span id="ACT">
                                <div class="row content-box [[selectSection('all')]] [[document.score]]" ng-click="loadSection(document, false)" title="{{trans('lodepart.open-document')}}">
                                    <div class="col-xs-11">
                                        <br/>
                                        <span class="title-summary" ng-bind-html="document.titleHtml | htmlFilter"></span>
                                        <span class="title-summary" ng-bind-html="document.subjectHtml | htmlFilter"></span>
                                    </div>
                                    <div class="col-xs-1">
                                        <br/>
                                        <i ng-if="document.nbComments > 0" class="fa fa-comment fa-lg right btn-inbody">
                                            <br/><br/> <span ng-bind="document.nbComments"></span>
                                        </i>
                                        <i ng-if="document.nbComments == 0" class="fa fa-comment fa-lg right">
                                            <br/><br/> 0
                                        </i>
                                    </div>
                                </div>
                                <div class="row content-box" ng-if="document.preamble">
                                    <div class="col-xs-12">
                                        <br />
                                        <span ng-bind-html="document.preamble.titleHtml | htmlFilter"></span>
                                        <i class="fa fa-lg fa-plus-square hand" aria-hidden="true" ng-show="document.preamble.collapsed"
                                           ng-click="document.preamble.collapsed = false"></i>
                                        <i class="fa fa-lg fa-minus-square hand" aria-hidden="true" ng-hide="document.preamble.collapsed"
                                           ng-click="document.preamble.collapsed = true"></i>
                                        <span ng-bind-html="document.preamble.content | htmlFilter" ng-hide="document.preamble.collapsed"></span>
                                    </div>
                                </div>
                            </span>
                            <script type="text/ng-template" id="sections.html">
                                <div ng-if="currentSection.loaded">
                                    <div id="[[currentSection.id]]" class="row content-box [[selectSection(currentSection)]] [[currentSection.score]]" ng-click="loadSection(currentSection,true)" title="{{trans('lodepart.open-section')}}">
                                        <div class="col-xs-10">
                                            <br/>
                                            <span class="title-summary" ng-if="currentSection.title" ng-bind-html="currentSection.titleHtml | htmlFilter"></span>
                                            <span class="title-summary" ng-if="currentSection.subject" ng-bind-html="currentSection.subjectHtml | htmlFilter"></span>

                                            <i class="fa fa-lg fa-minus-square hand" aria-hidden="true"  ng-hide="(commentSelected && currentSection.id == section.id) || currentSection.collapsed || (creationComment && currentSection.id === section.id)" ng-click="collaspedElement($event,currentSection,true)" ng-if="!currentSection.amendmentToEditCK"></i>

                                            <div ng-if="currentSection.content" class="[[currentSection.editableContent]]">
                                                <span ng-if="!currentSection.amendmentToEditCK" ng-hide="(commentSelected && currentSection.id == section.id) || commentSelected.edit || currentSection.collapsed || (creationComment && currentSection.id === section.id)"  ng-bind-html="currentSection.content | htmlFilter"></span>
                                                <textarea ng-if="(currentSection.amendmentToEditCK && currentSection.amendmentToEditCK.sectionId == currentSection.id)" ckeditor id="editeurEdit" ng-model="currentSection.amendmentToEditCK.content"></textarea>

                                                <div hidden>
                                                    <textarea ckeditor id="originalText2" ng-model="section.content"></textarea>
                                                </div>

                                                <div ng-if="!creationComment && commentSelected && currentSection.id == section.id && !commentSelected.edit">
                                                    <span class="other-lang" ng-show="anotherLanguageAmendment">{{trans('lodepart.exist-in-other-lang')}}<br/>{{trans('lodepart.see-in-other-lang')}} <span ng-bind="otherLanguageComment"></span></span>
                                                    <span ng-bind-html="commentSelected.amendment | htmlFilter""></span>    
                                                </div>

                                                <div class="center" ng-if="(currentSection.amendmentToEditCK || creationComment) && currentSection.id === section.id" ng-init="editContent = currentSection.content; originalText = currentSection.content">
                                                    <textarea ckeditor id="editeur" ng-if="!currentSection.amendmentToEditCK" ng-hide="currentSection.collapsed" ng-model="editContent"></textarea>
                                                    <div hidden>
                                                        <textarea ckeditor id="originalText" ng-model="originalText"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>                                 
                        
                                        <div class="col-xs-1" ng-if="currentSection.content">
                                            <br/>
                                            <i ng-if="currentSection.nbAmmendements > 0" class="fa fa-pencil fa-lg right btn-inbody">
                                                <br/><br/> 
                                                <span ng-bind="currentSection.nbAmmendements"></span>
                                            </i>
                                            <i ng-if="currentSection.nbAmmendements == 0" class="fa fa-pencil fa-lg right btn-inbody-no">
                                                <br/><br/> 0
                                            </i>
                                        </div>
                        
                                        <div class="col-xs-1 right">
                                            <br/>
                                            <i ng-if="currentSection.nbComments > 0" class="fa fa-comment fa-lg right btn-inbody">
                                                <br/><br/> 
                                            <span ng-bind="currentSection.nbComments"></span>
                                            </i>
                                            <i ng-if="currentSection.nbComments == 0" class="fa fa-comment fa-lg right btn-inbody-no">
                                                <br/><br/> 0
                                            </i>
                                        </div>
                                
                                    </div>
                                    <div ng-repeat="currentSection in currentSection.sections" ng-include="'sections.html'"></div>
                                </div>

                            </script>                            
                            <div ng-repeat="currentSection in document.sections" ng-include="'sections.html'"></div>
                            <div class="load-sections col-xs-10" ng-if="!sectionsLoaded" ng-click="loadOtherSections(nbFirstBranches)">
                                <i class="fa fa-arrow-down" aria-hidden="true"></i> {{trans('lodepart.load-new-sections')}} <i class="fa fa-arrow-down" aria-hidden="true"></i>
                            </div>
                            <span ng-bind="document.final"></span>
                        </div>
                    </div>
                </div>

                <div class="col-xs-5" ng-show="section">
                    <div class="col-comments" id="col-comments">
                        <div ng-show="loadComments" class="act center">
                            <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.loading-comments')}}
                        </div>
                        <div ng-hide="loadComments">
                            <b>{{trans('lodepart.comments-first')}}</b><br/>
                            <span ng-if="!isSection">{{trans('lodepart.general-comments')}}</span>
                            <span ng-if="isSection && section.subject">
                                <span ng-bind="section.title"></span> : 
                                <span ng-bind="section.subject"></span>
                            </span>
                            <span ng-if="isSection && !section.subject">{{trans('lodepart.paragraph-comments')}}</span>

                            <div class="col-body-comments message-status" ng-if="section.commented == 'false'">
                                {{trans('lodepart.not-commented')}}
                            </div>

                            <div class="col-body-comments" ng-if="section.commented == 'true'">

                                <div ng-if="nbCommentsSection === 0" ng-hide="creationComment" class="row">
                                    <br/>
                                    <div class="col-md-3"></div>
                                    <div class="btn btn-primary [[statusComment]] col-md-6 col-xs-12" ng-click="formCreation(true)">
                                        {{trans('lodepart.new-comment')}}&nbsp;&nbsp;<i class="fa fa-lg fa-comment" aria-hidden="true"></i>
                                    </div>
                                    <a class="[[statusCommentLogin]] col-xs-12 col-md-6 btn btn-default" href="{{ url('/auth/login?lang='.Config::get('app.locale'))}}">
                                        {{trans('lodepart.login-to-participate')}}&nbsp;&nbsp;<i class="fa fa-lg fa-lock" aria-hidden="true"></i>
                                    </a>
                                    <div class="col-md-3"></div>
                                </div>
                                <div ng-if="nbCommentsSection === 0" ng-hide="creationComment" class="center">
                                    <br /><br />
                                    <b>{{trans('lodepart.no-comments')}}</b>
                                </div>

                                <div ng-if="nbCommentsSection > 0" ng-hide="creationComment" class="row">
                                    <div class="col-md-6 size-chart-comment">
                                        <nvd3-pie-chart
                                            data="dataPieChartSection"
                                            x="xFunction()"
                                            y="yFunction()"
                                            color="colorFunction()"                                    
                                            width="250"
                                            height="250"
                                            showLabels="true"
                                            pieLabelsOutside="false"
                                            tooltips="true"
                                            tooltipcontent="toolTipContentFunction()"
                                            labelType="percent">
                                            <svg>
                                            </svg>
                                        </nvd3-pie-chart>
                                    </div>
                                    <div class="col-md-6">
                                        <a class="btn btn-default col-xs-12" href="/show-more-statistics?doc_code=[[document.docCode]]&amp;year=[[document.year]]&amp;num=[[document.num]]&amp;path={{$path}}&amp;eli_lang_code=[[document.eli_lang_code]]&amp;id_fmx_element=[[section.id]]&lang={{Config::get('app.locale')}}">
                                            {{trans('lodepart.show-more-stats')}}&nbsp;&nbsp;<i class="fa fa-lg fa-bar-chart" aria-hidden="true"></i>
                                        </a>
                                        <br /><br /><br />
                                        <div class="btn btn-primary [[statusComment]] col-xs-12" ng-click="formCreation(true)">
                                            {{trans('lodepart.new-comment')}}&nbsp;&nbsp;<i class="fa fa-lg fa-comment" aria-hidden="true"></i>
                                        </div>
                                        <a class="[[statusCommentLogin]] col-xs-12 btn btn-default" href="{{ url('/auth/login?lang='.Config::get('app.locale'))}}">
                                            {{trans('lodepart.login-to-participate')}}&nbsp;&nbsp;<i class="fa fa-lg fa-lock" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>

                                <div id="creationCommentId" ng-show="creationComment">
                                    <form ng-submit="addUserCommentSection(section)">
                                        <textarea placeholder="{{trans('lodepart.write-comment')}}" class="form-control comment-txt" rows="6" maxlength="1000"
                                                  title="{{trans('lodepart.write-comment')}}" name="commentTextBox" 
                                                  ng-model="commentData.commentTextBox" ng-change="changeCommentArea()"
                                                  ng-keyup="checkShowHashtags()">
                                        </textarea>
                                        <ul id="new-hashtags" class="typeahead new-hashtag-menu" role="menu" ng-show="hashtags.length > 0">
                                            <li ng-repeat="hashtag in hashtags" auto-suggest-menu-item class="">
                                                <a href="" ng-bind="hashtag" class="hashtag-option" ng-click="addNewHashtag(hashtag)"></a>
                                            </li>
                                        </ul>
                                        <div class="btn-group btn-group-xs pull-left" data-toggle="buttons">
                                            <label ng-click="changeType('yes')" class="btn btn-commentType">
                                                <input type="radio" ng-model="commentData.commentType" value="yes">
                                                <i class="fa fa-thumbs-o-up fa-lg [[cssYes]]">&nbsp;{{trans('lodepart.positive')}}</i>
                                            </label>
                                            <label ng-click="changeType('no')" class="btn btn-commentType">
                                                <input type="radio" ng-model="commentData.commentType" value="no">
                                                <i class="fa fa-thumbs-o-down fa-lg [[cssNo]]">&nbsp;{{trans('lodepart.negative')}}</i>
                                            </label>
                                        </div>
                                        <div class="pull-right">
                                            <button type="submit" class="btn btn-xs btn-submit" ng-disabled="commentEmpty" ng-click="formCreation(false)">
                                                <i class="fa fa-check" aria-hidden="true"></i>
                                                {{trans('lodepart.submit')}}
                                            </button>
                                            <button type="button" class="btn btn-xs btn-submit" ng-click="cancelAdd(section)">
                                                <i class="fa fa-times" aria-hidden="true"></i>
                                                {{trans('lodepart.cancel')}}
                                            </button>
                                        </div>
                                        <br /><br />
                                    </form>
                                </div>
                                <div id="saveCommentsId" hidden>
                                    <br /><i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.saving')}}
                                </div>
                                <div id="showCommentsId" ng-hide="creationComment">
                                    <div class="filter-comments-title">
                                        <span ng-hide="section.filteringComments || comments.length === 0" ng-click="section.filteringComments = true" class="right">
                                            <i class="fa fa-lg fa-search-plus" aria-hidden="true"></i>
                                            {{trans('lodepart.filter-open')}}
                                        </span>
                                        <span  ng-show="section.filteringComments" ng-click="clearFilter(section)" class="right">
                                            <i class="fa fa-lg fa-search-minus" aria-hidden="true"></i>
                                            {{trans('lodepart.filter-close')}}
                                        </span>
                                        <br />
                                    </div>
                                    <div ng-show="section.filteringComments" class="filter-comments">
                                        <div class="input-group">
                                            <span class="input-group-addon">
                                                <i class="fa fa-search" aria-hidden="true"></i>
                                            </span>
                                            <input ng-model="section.filterSearch" type="text" class="form-control" 
                                                   placeholder="{{trans('lodepart.filter-text')}}" maxlength="200" >
                                        </div>
                                        <br />
                                        <div class="input-group keywords-search">
                                            <span class="input-group-addon">
                                                <i class="fa fa-tags" aria-hidden="true"></i>
                                            </span>
                                            <input ng-model="section.filterHashtags" type="text" class="form-control hashtag-input" 
                                                   maxlength="100" ng-keyup="getHashtags(section, $event)" 
                                                   placeholder="{{trans('lodepart.filter-hashtags')}}">
                                            <ul id="hashtags-filter" class="typeahead hashtag-menu" role="menu" ng-show="hashtags.length > 0">
                                                <li ng-repeat="hashtag in hashtags" auto-suggest-menu-item class="">
                                                    <a href="" ng-bind="hashtag" class="hashtag-option-filter" ng-click="addHashtag(section, hashtag)"></a>
                                                </li>
                                            </ul>
                                        </div>
                                        <br />
                                        <script>
                                            $(function () {
                                            $("#datepickerFilterFrom").datepicker({dateFormat: 'yy-mm-dd'});
                                            $("#datepickerFilterFrom").css("background-color", "white");
                                            $("#datepickerFilterTo").datepicker({dateFormat: 'yy-mm-dd'});
                                            $("#datepickerFilterTo").css("background-color", "white");
                                            });
                                        </script>
                                        <div class="row">
                                            <div class="col-xs-6 date-from">
                                                <div class="input-group">
                                                    <span class="input-group-addon">{{trans('lodepart.date-from')}}</span>
                                                    <input type="text" class="form-control" maxlength="10" ng-model="section.filterDateFrom" id="datepickerFilterFrom">
                                                </div>
                                            </div>
                                            <div class="col-xs-6">
                                                <div class="input-group">
                                                    <span class="input-group-addon">{{trans('lodepart.date-to')}}</span>
                                                    <input type="text" class="form-control" maxlength="10" ng-model="section.filterDateTo" id="datepickerFilterTo">
                                                </div>
                                            </div>
                                        </div>
                                        <br />
                                        <div class="input-group">
                                            <span class="input-group-addon">{{trans('lodepart.filter-author')}}</span>
                                            <input type="text" class="form-control authors-input" maxlength="50" ng-model="section.filterAuthor" ng-keyup="getAuthors(section, $event)" >
                                            <ul id="authors-filter" class="typeahead authors-menu" role="menu" ng-show="authors.length > 0">
                                                <li ng-repeat="author in authors" auto-suggest-menu-item class="">
                                                    <a href="" ng-bind="author.name.value" class="authors-option" ng-click="addAuthor(section, author.name.value)"></a>
                                                </li>
                                            </ul>
                                        </div>
                                        <br />
                                        <div class="input-group">
                                            <span class="input-group-addon">{{trans('lodepart.language')}}</span>
                                            <select class="form-control" ng-model="section.filterLanguage">
                                                <option value="all">{{trans('lodepart.all-languages')}}</option>
                                                <option ng-repeat="language in listLanguages" value="[[language.code3]]" ng-bind="language.name"></option>
                                            </select>
                                        </div>
                                        <br />
                                        <label ng-if="isConnected('{{$userId}}')" class="checkbox-inline">
                                            <input type="checkbox" ng-model="section.onlyMyComments"> 
                                            {{trans('lodepart.only-my-comments')}}
                                            <br /><br />
                                        </label>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" ng-model="section.filterPositive"> 
                                                    {{trans('lodepart.positive')}}&nbsp;<span class="fa fa-square yes"></span>
                                                </label>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" ng-model="section.filterNeutral"> 
                                                    {{trans('lodepart.neutral')}}&nbsp;<span class="fa fa-square mixed"></span>
                                                </label>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" ng-model="section.filterNegative"> 
                                                    {{trans('lodepart.negative')}}&nbsp;<span class="fa fa-square no"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <br />
                                        <label class="checkbox-inline">
                                            <input type="checkbox" ng-model="section.filterAmendments"> 
                                            {{trans('lodepart.filter-with-amendment')}}
                                        </label>
                                        <br /><br />
                                        <div class="btn btn-primary" ng-click="filterComments(section)">
                                            {{trans('lodepart.finder-filter')}}
                                        </div>
                                        <br /><br />
                                        <div class="input-group">
                                            <span class="input-group-addon">{{trans('lodepart.sort-by')}}</span>
                                            <select class="form-control" ng-model="section.filterSort" ng-change="filterComments(section)">
                                                <option value="date">{{trans('lodepart.filter-sort-date')}}</option>
                                                <option value="popular">{{trans('lodepart.filter-sort-popular')}}</option>
                                                <option value="good">{{trans('lodepart.filter-sort-likes')}}</option>
                                                <option value="bad">{{trans('lodepart.filter-sort-dislikes')}}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div ng-show="currentFiltering">
                                        <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.current-filtering')}}
                                    </div>
                                    <div ng-hide="currentFiltering" class="comment-complet" ng-repeat="comment in comments| reverse | startFrom:section.currentPage * section.pageSize | limitTo:section.pageSize">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <a href="{{url('/dashboard/espace-user?id=[[comment.user_id.value]]&lang='.Config::get('app.locale'))}}">
                                                    <img class="img-user-comment" ng-src="[[comment.avatar.value]]" alt="{{trans('lodepart.avatar')}}">
                                                    <span class="user-name-comment">
                                                        <span ng-bind="comment.firstName.value"></span> 
                                                        <span ng-bind="comment.lastName.value"></span>
                                                    </span>
                                                </a>
                                                <br />
                                                <span class="comment-time" ng-bind="comment.created_at.value"></span>
                                                <span ng-if="comment.amended_at" class="fa fa-pencil-square-o" title="{{trans('lodepart.edited')}} [[comment.amended_at.value]]"></span>
                                                <br />
                                                <br />
                                                <div ng-show="!comment.edit">
                                                    <table>
                                                        <tr ng-init="isLikeDislikeComment(comment,'{{$userId}}')">
                                                            <td ng-if="comment.note.value == 'yes'"><span class="fa fa-square yes"><span class="opinion">&nbsp;{{trans('lodepart.positive')}}</span></span></td>
                                                            <td ng-if="comment.note.value == 'no'"><span class="fa fa-square no"><span class="opinion">&nbsp;{{trans('lodepart.negative')}}</span></span></td>
                                                            <td ng-if="comment.note.value == 'mixed'"><span class="fa fa-square mixed"><span class="opinion">&nbsp;{{trans('lodepart.neutral')}}</span></span></td>
                                                            <td ng-click="likeDislikeComment(section, comment, 'yes','{{$userId}}','{{trans('lodepart.connect-for-like')}}','{{trans('lodepart.other-for-like')}}')">
                                                                &nbsp;&nbsp;&nbsp;<span class="fa fa-thumbs-o-up like [[comment.cssLike]]" title="{{trans('lodepart.like')}}">
                                                                    &nbsp;<span ng-bind="comment.num_like.value"></span></span>
                                                            </td>
                                                            <td ng-click="likeDislikeComment(section, comment, 'no','{{$userId}}','{{trans('lodepart.connect-for-like')}}','{{trans('lodepart.other-for-like')}}')">
                                                                &nbsp;&nbsp;&nbsp;<span class="fa fa-thumbs-o-down like [[comment.cssDislike]]" title="{{trans('lodepart.dislike')}}">
                                                                    &nbsp;<span ng-bind="comment.num_dislike.value"></span></span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <table class="btn btn-default" ng-click="loadReplies([[comment]], true)" title="{{trans('lodepart.open-replies')}}">
                                                    <tr>
                                                        <td class="element-user-comment">
                                                            <span class="fa fa-comment"></span>
                                                        </td>
                                                        <td class="element-user-comment">
                                                            {{trans('lodepart.replies')}}:  <span ng-bind="comment.totalReplies.value"></span>
                                                        </td>
                                                    </tr>
                                                    <tr ng-if="section.content">
                                                        <td class="element-user-comment">
                                                            <span class="fa fa-pencil"></span>
                                                        </td>
                                                        <td class="element-user-comment">
                                                            {{trans('lodepart.insertions')}}:  <span ng-bind="comment.num_insertion.value"></span><br />
                                                            {{trans('lodepart.deletions')}}:  <span ng-bind="comment.num_deletion.value"></span><br />
                                                            {{trans('lodepart.substitutions')}}:  <span ng-bind="comment.num_substitution.value"></span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <br />
                                        <div class="comment-content">
                                            <span ng-show="!comment.edit" compile="comment.comment.value"></span>
                                            <div ng-if="comment.translation" class="translation">
                                                <span ng-bind="comment.translation"></span>
                                            </div>
                                            <textarea ng-show="comment.edit" class="edit-comment" rows="4" maxlength="1000" trusted-html 
                                                      ng-model="comment.comment.value" ng-keyup="checkShowEditHashtags(comment)"></textarea>
                                            <ul id="edit-hashtags" class="typeahead new-hashtag-menu" role="menu" ng-show="comment.hashtags.length > 0">
                                                <li ng-repeat="hashtag in comment.hashtags" auto-suggest-menu-item class="">
                                                    <a href="" ng-bind="hashtag" class="hashtag-option-edit" ng-click="addEditHashtag(comment, hashtag)"></a>
                                                </li>
                                            </ul>
                                            <div ng-show="comment.edit" class="btn-group btn-group-xs pull-left width-complete" data-toggle="buttons">
                                                <br/>
                                                <label ng-click="changeNote(comment, 'yes')" class="btn btn-commentType">
                                                    <input type="radio" ng-model="comment.note" value="yes">
                                                    <i class="fa fa-thumbs-o-up fa-lg [[editCssYes]]">&nbsp;{{trans('lodepart.positive')}}</i>
                                                </label>
                                                <label ng-click="changeNote(comment, 'no')" class="btn btn-commentType">
                                                    <input type="radio" ng-model="comment.note" value="no">
                                                    <i class="fa fa-thumbs-o-up fa-lg [[editCssNo]]">&nbsp;{{trans('lodepart.negative')}}</i>
                                                </label>
                                                <span class="link-comment right" ng-hide="loadingSaveEdit" ng-click="saveEdit(section, comment)">
                                                    <span class="fa fa-floppy-o"></span> {{trans('lodepart.save')}}
                                                </span>
                                                <span class="right" ng-show="loadingSaveEdit">
                                                    <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.saving')}}
                                                </span>
                                            </div>
                                        </div>
                                        <br />
                                        <span ng-show="!comment.loadingDeleteComment && !comment.edit && isUserComment(comment,'{{$userId}}') && comment.totalReplies.value == 0 && comment.num_like.value == 0 && comment.num_dislike.value == 0" class="link-comment" ng-click="edit(section, comment)">
                                            <span class="fa fa-pencil"></span> {{trans('lodepart.edit')}}
                                        </span> 
                                        <span ng-show="!comment.loadingDeleteComment && !comment.edit && isUserComment(comment,'{{$userId}}') && comment.totalReplies.value == 0 && comment.num_like.value == 0 && comment.num_dislike.value == 0" class="link-comment" ng-click="deleteComment(section, comment)">
                                            <span class="fa fa-trash"></span> {{trans('lodepart.trash')}}
                                        </span>
                                        <span ng-show="!comment.loadingDeleteComment && !comment.edit && comment.lang.value != languageDocument" class="link-comment" ng-click="translate(comment)">
                                            <span class="fa fa-language"></span> {{trans('lodepart.translate')}}
                                        </span>
                                        <span class="right" ng-show="comment.loadingDeleteComment">
                                            <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.deletion')}}
                                        </span>
                                        <div class="media replies" ng-show="commentSelected == comment">
                                            <div ng-repeat="reply in comment.replies| reverse | startFrom:comment.currentPage * comment.pageSize | limitTo:comment.pageSize">
                                                <a href="{{url('/dashboard/espace-user?id=[[reply.user_id.value]]&lang='.Config::get('app.locale'))}}">
                                                    <img class="img-user-comment" ng-src="[[reply.avatar.value]]" alt="{{trans('lodepart.avatar')}}">
                                                    <span class="user-name-comment">
                                                        <span ng-bind="reply.firstName.value"></span> 
                                                        <span ng-bind="reply.lastName.value"></span>
                                                    </span>
                                                </a>
                                                <br />
                                                <span class="comment-time" ng-bind="reply.created_at.value"></span>
                                                <span ng-if="reply.amended_at" class="fa fa-pencil-square-o" title="{{trans('lodepart.edited')}} [[reply.amended_at]]"></span>
                                                <br/><br/>
                                                <div class="reply-content" ng-init="isLikeDislikeReply(reply,'{{$userId}}')">
                                                    <span ng-hide="reply.edit" ng-bind-html="reply.comment.value | htmlFilter"></span>
                                                    <div ng-if="reply.translation"  class="translation-reply">
                                                        <span ng-bind="reply.translation"></span>
                                                    </div>
                                                    <textarea ng-show="reply.edit" class="edit-comment" rows="4" maxlength="1000" trusted-html ng-model="reply.comment.value"></textarea>
                                                    <br/>
                                                    <div ng-show="reply.edit" class="btn-group btn-group-xs pull-left width-complete" data-toggle="buttons">
                                                        <br/>
                                                        <span class="link-comment right" ng-hide="loadingSaveEdit" ng-click="saveEditReply(comment, reply)">
                                                            <span class="fa fa-floppy-o"></span> {{trans('lodepart.save')}}
                                                        </span>
                                                        <span class="right" ng-show="loadingSaveEdit">
                                                            <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.saving')}}
                                                        </span>
                                                    </div>
                                                    <br/>
                                                    <span ng-show="!reply.loadingDeleteComment && !reply.edit && isUserReply(reply,'{{$userId}}') && reply.num_like.value == 0 && reply.num_dislike.value == 0" class="link-comment" ng-click="editReply(reply)">
                                                        <span class="fa fa-pencil"></span> {{trans('lodepart.edit')}}
                                                    </span> 
                                                    <span ng-show="!reply.loadingDeleteComment && !reply.edit && isUserReply(reply,'{{$userId}}') && reply.num_like.value == 0 && reply.num_dislike.value == 0" class="link-comment" ng-click="deleteReply(comment, reply)">
                                                        <span class="fa fa-trash"></span> {{trans('lodepart.trash')}}
                                                    </span>
                                                    <span ng-show="!reply.loadingDeleteComment && !reply.edit && reply.lang.value != languageDocument" class="link-comment" ng-click="translateReply(reply)">
                                                        <span class="fa fa-language"></span> {{trans('lodepart.translate')}}
                                                    </span>
                                                    <span ng-show="!reply.loadingDeleteComment && !reply.edit" ng-click="likeDislikeReply(section, comment, reply, 'yes','{{$userId}}','{{trans('lodepart.connect-for-like')}}','{{trans('lodepart.other-for-like')}}')">
                                                        &nbsp;&nbsp;&nbsp;<span class="fa fa-thumbs-o-up like [[reply.cssLike]]" title="{{trans('lodepart.like')}}">
                                                            &nbsp;<span ng-bind="reply.num_like.value"></span></span>
                                                    </span>
                                                    <span ng-show="!reply.loadingDeleteComment && !reply.edit" ng-click="likeDislikeReply(section, comment, reply, 'no','{{$userId}}','{{trans('lodepart.connect-for-like')}}','{{trans('lodepart.other-for-like')}}')">
                                                        &nbsp;&nbsp;&nbsp;<span class="fa fa-thumbs-o-down like [[reply.cssDislike]]" title="{{trans('lodepart.dislike')}}">
                                                            &nbsp;<span ng-bind="reply.num_dislike.value"></span></span>
                                                    </span>
                                                    <span class="right" ng-show="reply.loadingDeleteComment">
                                                        <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.deletion')}}
                                                    </span>
                                                    <br/>
                                                </div>
                                                <br />
                                            </div>
                                            <div class="center" ng-if="comment.numberOfPages() > 1">
                                                <button ng-hide="comment.currentPage === 0" ng-click="comment.currentPage = comment.currentPage - 1" 
                                                        class="item-page" title="{{trans('lodepart.previous-reply')}}">
                                                    &laquo;
                                                </button>
                                                <span class="display-pages">[[comment.currentPage+1]]/[[comment.numberOfPages()]]</span>
                                                <button ng-hide="comment.currentPage >= comment.replies.length / comment.pageSize - 1" ng-click="comment.currentPage = comment.currentPage + 1" 
                                                        class="item-page" title="{{trans('lodepart.next-reply')}}">
                                                    &raquo;
                                                </button>
                                            </div>
                                            <form ng-hide="loadingSaveReply" ng-if="!comment.edit" ng-submit="addUserReply([[comment]])" class="[[statusComment]]">
                                                <textarea placeholder="{{trans('lodepart.write-reply')}}" class="form-control comment-txt" rows="3" maxlength="500"
                                                          title="{{trans('lodepart.write-reply')}}" ng-model="reply.replyText"
                                                          ng-change="changeReplyArea()">
                                                </textarea>
                                                <div class="pull-right">
                                                    <button type="submit" class="btn btn-xs btn-submit" ng-disabled="replyEmpty">
                                                        <span class="comment-icon"></span>
                                                        {{trans('lodepart.submit')}}
                                                    </button>
                                                </div>
                                            </form>
                                            <span ng-show="loadingSaveReply">
                                                <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.saving-reply')}}
                                            </span>
                                        </div>
                                        <br />
                                    </div>
                                    <div class="center" ng-if="section.numberOfPages() > 1">
                                        <button ng-hide="section.currentPage === 0" ng-click="section.currentPage = section.currentPage - 1" 
                                                class="item-page" title="{{trans('lodepart.previous-comment')}}">
                                            &laquo;
                                        </button>
                                        <span class="display-pages">[[section.currentPage+1]]/[[section.numberOfPages()]]</span>
                                        <button ng-hide="section.currentPage >= comments.length / section.pageSize - 1" ng-click="section.currentPage = section.currentPage + 1" 
                                                class="item-page" title="{{trans('lodepart.next-comment')}}">
                                            &raquo;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>
@stop