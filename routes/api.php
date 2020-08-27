<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/leads', 'LeadsController@index');
Route::any('/leads/test', 'LeadsController@test');
Route::post('/leads', 'LeadsController@store');
Route::post('/leads/new-company', 'LeadsController@newCompany');
Route::post('/leads/sinchronize-companies', 'LeadsController@sinchronizeCompanies');
Route::post('/leads/send-sms', 'LeadsController@sendSms');
Route::post('/leads/handle-receipts', 'LeadsController@handleReceipts');
Route::post('/leads/other-contact', 'LeadsController@otherContact');
Route::post('/leads/report-companies', 'LeadsController@reportCompanies');
Route::post('/leads/update-users', 'LeadsController@updateUsers');
Route::get('/leads/batch-synchronize', 'LeadsController@batchSynchronize');
Route::post('/leads/edit-company', 'LeadsController@editCompany');
Route::post('/leads/verify-new-user-zoho', 'LeadsController@verifyNewUserZoho');
Route::get('/leads/{id}', 'LeadsController@show');
Route::get('/leads/{id}/delete', 'LeadsController@destroy');    
Route::put('/leads/{id}', 'LeadsController@update');
Route::post('/leads/{id}/comment', 'LeadsController@addComment');
Route::post('/leads/{id}', 'LeadsController@update');
Route::delete('/leads/{id}', 'LeadsController@destroy');
Route::any('/adwords/test', 'AdwordsController@test');
Route::any('/ga/user-did-invoice', 'GAController@userDidInvoice');
Route::any('/ga/test', 'GAController@test');
Route::any('/ga/update-last-invoice', 'GAController@companiesUpdateLastInvoice');
Route::any('/ga/update-invoices-last-month', 'GAController@companiesUpdateInvoicesLastMonth');
Route::any('/ga/update-clients-last-month', 'GAController@companiesUpdateClientsLastMonth');
Route::post('/zendesk/update-tickets', 'ZendeskController@updateTickets');