<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Company;
use App\Contact;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class CompaniesController extends Controller
{
    public $authToken = "109e870ae7f8f5f23ad6f67e2eca3d82";
        
    /*
     * Funcion para realizar la llamada a curl
     * @parameter $url La direccion a donde se va a realizar el llamado
     * @return $response La respuesta del curl 
     */
    public function _curl($url, $params=false) {
        $ch = curl_init($url);          
        curl_setopt($ch, CURLOPT_URL, $url);        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($params)
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);    
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    
        $response = curl_exec ($ch);            
        
        curl_close($ch);                        
        return $response;
    }
    
    public function _curlPost($url, $params) {
        $ch = curl_init();          
        curl_setopt($ch, CURLOPT_URL, $url);        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);    
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $response = curl_exec ($ch);                    
        curl_close($ch);     

        return $response;
    }
            
    /*
     * Funcion para actualizar la fecha de la ultima factura hecha por la empresa
     * @return $void Las fechas actualizadas de las facturas realizadas por la empresa
     */
    public function updateLastInvoice(){
        $ga = new GAController;
        $users = $ga->userDidInvoice();

        $xmlData  = '<Accounts><row no="1"><FL val="Account Number">10</FL><FL val="Identificación">"10"</FL></row><FL val="Account Name">"Prueba Erka - 10"</FL></Accounts>';    
        $url = "https://crm.zoho.com/crm/private/json/Accounts/insertRecords";
        $params = "authtoken=".$this->authToken."&scope=crmapi&duplicateCheck=2&version=4&xmlData=".$xmlData;
        $response = json_decode($this->_curlPost($url, $params), true);        
return $response;        
                
        $url = "https://crm.zoho.com/crm/private/json/Accounts/searchRecords";        
        $params = "authtoken=$this->authToken&scope=crmapi&newFormat=2&criteria=(Account Number:$users[0]])";             
        $response = json_decode($this->_curl($url, $params), true); 
        
        
        return $response;
    }
    
    /*
     * Funcion para realizar/implementar pruebas del api y/o funciones para correrlas una sola vez
     * 
     */
    public function test() { 
        
    }
}
