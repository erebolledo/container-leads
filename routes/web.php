<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Psr\Http\Message\ServerRequestInterface;
use Spatie\Analytics\Period;

Route::get('/psr-7-example', function (ServerRequestInterface $request) {
	
});
 	
Route::get('/', function() {return view('welcome');});
Route::get('/leads/get-columns', 'LeadsController@getColumns');
Route::get('/leads/import', function() {return view('leads.import');});
Route::get('/leads/list', function() {return view('leads.list');});
Route::get('/zoho/others-contacts/{id}/{key}', 'ContactsController@listOtherContacts');
Route::get('/zoho/report-companies/{id}/{key}', 'ContactsController@reportCompanies');
Route::post('/leads/{id}', 'LeadsController@update');
Route::post('/leads/import-preview', 'LeadsController@importPreview');
Route::post('/leads/add-from-excel-import', 'LeadsController@addFromExcelImport');
Route::get('/ga', 'GAController@test');
Route::get('/text-locales/daily-report-sms/{id}/{key}', 'TextLocalesController@dailyReportSMS');
Route::get('/text-locales/available-credits/{id}/{key}', 'TextLocalesController@getCredits');
Route::get('/zendesk/create-tickets/{email}', 'ZendeskController@createTickets');
