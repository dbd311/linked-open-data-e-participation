<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It's a breeze. Simply tell Laravel the URIs it should respond to
  | and give it the controller to call when that URI is requested.
  |
 */

/** DD[05-08-2016]: For security reasons, URLs should be grouped into different middlewares * */
// Here are two examples of using middlewares for grouping URLs protected by authentication mechanism with or without admin rights

/** Middleware for general public access (any Web user) */
Route::group(['middleware' => ['web']], function() {
    /* ElasticSearch */
    Route::get('search', 'SearchController@getResults');
    Route::get('runQuery', 'SearchController@runQuery');

    /* Activation link */
    Route::get('activate-account', 'Auth\AuthController@activateAccount');
});

Route::group(['middleware' => ['auth-admin']], function() {
    // redirect admin to the cpanel
    Route::get('cpanel', 'AdminController@admin');
	Route::get('cpanel/management', 'AdminController@showGlobalConfig');
	Route::get('services/get-env-parameters', 'AdminController@loadEnvironment');
    
    /** ElasticSearch middleware for indexing : protected with user authentication and admin rights */
    Route::get('/elastic/document-indexing', 'IndexController@index');
    Route::get('/elastic/clean-document-index', 'IndexController@cleanIndex');
    
    /** End ElasticSearch  */
    /** Documents Virtuoso * */
    Route::get('cpanel/virtuoso/delete-documents', 'DocumentController@deleteDocuments');
	Route::get('virtuoso/exist-graph', 'MetadataController@existGraph');
    /** End Documents Virtuoso * */
});


// Home page
Route::get('', 'WebPageController@home');
Route::get('load-languages', 'WebPageController@loadLanguages');
Route::get('load-nationalities', 'MetadataController@getNationalities');
Route::get('load-groups', 'MetadataController@getGroups');

// Menus
Route::get('about', 'WebPageController@about');
Route::get('faqs', 'WebPageController@faqs');
Route::get('contact', 'WebPageController@contact');
Route::get('legal-notice', 'WebPageController@legalNotice');
Route::get('sitemap', 'WebPageController@sitemap');
Route::get('what-is-epart', 'WebPageController@whatIsEpart');
Route::get('what-is-lod', 'WebPageController@whatIsLod');
Route::get('dashboard/espace-admin', 'WebPageController@admin');
Route::get('dashboard/espace-user', 'WebPageController@user');

// Authentication routes...
Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');

// Registration routes...
Route::get('auth/register', 'Auth\AuthController@getRegister');
Route::post('auth/register', 'Auth\AuthController@postRegister');

// Password routes...
Route::get('password/email', 'Auth\PasswordController@getEmail');
Route::post('password/email', 'Auth\PasswordController@postEmail');
Route::get('password/reset', 'Auth\PasswordController@getReset');
Route::post('reset/password', 'Auth\PasswordController@resetUserPassword');

/* * ***************** BACK OFFICE ******************************** */



/** Virtuoso ** */
Route::get('/virtuoso/populate-json-documents', 'DocumentController@populateFormexJson');
Route::get('/virtuoso/clean-virtuoso-graph', 'MetadataController@cleanVirtuosoGraph');
/** User comments*** */
Route::post('add-user-comment', 'CommentController@storeComment');
Route::post('reply-to-previous-comment', 'CommentController@replyUserComment');
Route::post('comment-edit', 'CommentController@ammendPost');
Route::post('comment-delete', 'CommentController@deletePost');
Route::post('comment-like-dislike', 'CommentController@likeDislikePost');
Route::post('is-like-dislike', 'CommentController@getNoteForPost');
Route::post('reply-edit', 'CommentController@ammendPostForPost');
Route::post('reply-delete', 'CommentController@deletePostForPost');

/* * * Documents * */
Route::get('/lod/documents/displayDoc', 'DocumentController@displayDoc');

Route::post('/services/document-indexing', 'IndexController@indexDocuments');
Route::get('/services/get-indices', 'IndexController@getIndices');
Route::get('/services/clean-document-indices', 'IndexController@cleanIndices');

Route::get('/load-document', 'DocumentController@loadDocument');
Route::get('/load-metadata/{docId}/{lang}', 'DocumentController@loadMetadata');
Route::get('/get-themes/{id}/{lang}', 'DocumentController@retrieveThemes');

Route::get('/get-documents/{criteria}', 'DocumentController@getDocuments');
Route::get('/get-documents-filtering', 'DocumentController@getFilteredDocuments');
Route::get('/get-documents-finder', 'DocumentController@getFinderDocuments');

Route::post('show-more-statistics', 'DocumentController@_post_displayContainerStatistics');
Route::get('show-more-statistics', 'DocumentController@displayContainerStatistics');

Route::get('exist-file', 'DocumentController@existFile');

Route::get('lod/dashboard/load-languages', 'MetadataController@addEuroVoclanguages');
Route::get('lod/dashboard/load-eurovoc', 'MetadataController@loadEurovoc');
Route::get('load-users', 'UserController@loadUsers');
Route::post('update-user', 'UserController@updateUser');
Route::post('update-avatar', 'UserController@updateAvatar');
Route::post('change-password', 'UserController@changePassword');
Route::get('get-domains/{eli_lang_code}', 'MetadataController@getDomainNames');
Route::get('get-thesaurus-names/{eli_lang_code}/{domain}', 'MetadataController@getThesaurusNames');
Route::get('get-concept-names/{eli_lang_code}/{thesaurus}', 'MetadataController@getConceptNames');
Route::get('get-narrower-names/{eli_lang_code}/{concept}', 'MetadataController@getNarrowerNames');
Route::get('get-narrower-names-of-narrower-names/{eli_lang_code}/{narrower}', 'MetadataController@getNarrowerNamesOfNarrowerNames');

Route::get('get-related-term/{eli_lang_code}/{concept}', 'MetadataController@getRelatedTerm');

Route::get('has-child-narrower-term/{eli_lang_code}/{narrower}', 'MetadataController@hasChildNarrowerTerm');

Route::get('get-hashtags', 'MetadataController@getHashtags');

Route::get('get-authors', 'MetadataController@getAuthors');

Route::get('get-procedure-years', 'MetadataController@getProcedureYear');

Route::get('/load-annexes/{folder}', 'DocumentController@loadAnnexes');

Route::get('/get_lang_code/{eli_lang_code}', 'MetadataController@get_lang_code');
Route::get('/get_eli_lang_code/{lang_code}', 'MetadataController@get_eli_lang_code');

Route::get('dashboard/exist-graph', 'MetadataController@existGraph');

Route::get('nb-ammendments', 'AmmendementController@getNumberAmmendment');
Route::get('nb-ammendments-post', 'AmmendementController@getNumberAmmendmentOfPost');


//-----------------------Translation--------------------
Route::get('updatetranslation', 'TranslationController@update_translation');
Route::get('test_sending', 'PodcastController@test');


Route::get('sendtranslation', 'TranslationController@sendTranslation1');

Route::post('lod/documents/detect-language', 'TranslationController@detectLanguage');
Route::post('lod/documents/translated-text', 'TranslationController@getTranslatedText');

Route::post('public/callback', 'TranslationController@callback');
Route::post('public/errorcallback', 'TranslationController@errorcallback');

Route::get('show-link-update-translation', 'TranslationController@show_link_update_translation');
Route::get('update-translation', 'TranslationController@update_translation');
Route::post('back-translation', 'TranslationController@backTranslation');
//-------------------------------------------------------
 
 