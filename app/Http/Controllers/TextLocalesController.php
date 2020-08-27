<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TextLocalesController extends Controller
{
    public $user = "alegr@";
    public $pass = "alegra2020";
    public $apiKey = "ErTuVXqBQR0-bsYACRfiO1qkK5SAzPVw6TRfGVb0NO";
        
    /*
     * Funcion para obtener todos los estados de los sms mensuales
     */
    public function dailyReportSMS($id, $key, Request $request){ 
        if ($key!=md5($this->user).".".md5($this->pass))
            return "No autorizado"; 
        
        $request = $request->all();
        
        $period = (isset($request['period']))?$request['period']:date("Ym");
        $min_time = strtotime($period."02");
        $max_time = strtotime(date($period."t"));

	// Prepare data for POST request
	$data = array('apikey' => $this->apiKey, 'min_time' => $min_time, 'max_time' => $max_time);
 
	// Send the POST request with cURL
	$ch = curl_init('https://api.txtlocal.com/get_history_api/');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	
	$response = json_decode($response, true);
        $response = $response['messages'];
        
        return view('text.reportSMS', ['data'=>$response]);        
    }
    
    public function getCredits($id, $key){
        if ($key!=md5($this->user).".".md5($this->pass))
            return "No autorizado"; 
        
	// Prepare data for POST request
	$data = array('apikey' => $this->apiKey);
 
	// Send the POST request with cURL
	$ch = curl_init('https://api.txtlocal.com/balance/');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
        
        $response = json_decode($response, true);
die(print_r($response));        
        $response = $response['balance']['sms'];
        
        $table = "<table><tr><td>SMS Créditos Disponibles</td></tr><tr><td>$response</td></tr></table>";
	
	// Process your response here
	return $table;
        
    }
}
