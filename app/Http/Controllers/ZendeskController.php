<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use App\Company;
use App\Contact;

class ZendeskController extends Controller{

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
    
    /*
     * Funcion que devuelve el valor de un resultado en el arreglo respuesta de zoho
     * @param $value Es el valor que se quiere buscar
     * @param $array Es el arreglo en el cual vamos a hacer la busqueda
     * @return $content Es el contenido del valor en el arreglo
     */
    public function _content($array, $value) {
        $content = "";

        foreach ($array as $key){
            if ($value == $key['val']){
                $content = $key['content'];
                break;
            }                
        }
        return $content;
    }    
    
    /*
     * Funcion para extraer la data de un contacto de zoho a la base de datos
     * @param $data Data del contactos en zoho
     * @param $array Es el arreglo en el cual vamos a hacer la busqueda
     * @return $content Es el contenido del valor en el arreglo
     */
    public function _zohoContactToDB($data, $email) {

        
    }    

    /*
     * Funcion para extraer la data de zoho a la base de datos
     * @param $data Data en zoho de la cuenta
     * @param $idGlobal idGlobal de la cuenta
     * @return void
     */
    public function _zohoAccountToDB($data, $idGlobal) {
        $dataCompany = [];
        $dataCompany['idGlobal'] = $this->_content($data, 'Account Number');
        $dataCompany['name'] = $this->_content($data, 'Account Name');
        $dataCompany['identification'] = $this->_content($data, 'Identificación');
        $dataCompany['phone'] = $this->_content($data, 'Phone');
        $dataCompany['email'] = $this->_content($data, 'Correo electrónico');
        $dataCompany['origin'] = $this->_content($data, 'Origen');
        $dataCompany['owner'] = $this->_content($data, 'Account Owner');
        $dataCompany['registryDate'] = $this->_content($data, 'Fecha de registro');
        $dataCompany['address'] = $this->_content($data, 'Dirección');
        $dataCompany['city'] = $this->_content($data, 'Ciudad');
        $dataCompany['country'] = $this->_content($data, 'País');
        $dataCompany['status'] = $this->_content($data, 'Estado');
        $dataCompany['plan'] = $this->_content($data, 'Plan actual');
        $dataCompany['regime'] = $this->_content($data, 'Régimen');
        $dataCompany['version'] = $this->_content($data, 'Versión');
        $dataCompany['sector'] = $this->_content($data, 'Actividad o sector');
        $dataCompany['coupon'] = $this->_content($data, 'Código promocional');
        $dataCompany['dateEnd'] = $this->_content($data, 'Fecha fin demo');
        $dataCompany['dateUltimateInvoice'] = $this->_content($data, 'Ultima Facturación');            
        $dataCompany['urlFirstContact'] = $this->_content($data, 'URL primera visita');
        $dataCompany['firstPage'] = $this->_content($data, 'Primera página');
        $dataCompany['invoicesLastMonth'] = $this->_content($data, 'Facturas últimos 30 días');
        $dataCompany['clientsLastMonth'] = $this->_content($data, 'Clientes últimos 30 días');            
        $dataCompany['tickets'] = $this->_content($data, 'Zendesk tickets');
        $responseCompany = Company::updateOrCreate(['idGlobal' => $idGlobal], $dataCompany);          
    }    
    
    /*
     * Funcion para encontrar la info de las empresas por su email, o por el email de uno de sus contactos
     * @param $email Es el email a buscar
     * @return $data Es la data de la cuenta/empresa
     */
    public function _searchByEmail($email) {        
        
        $url = "https://crm.zoho.com/crm/private/json/Accounts/searchRecords";        
        $params = 'authtoken='.$this->authToken.'&scope=crmapi&newFormat=2&criteria=(Correo electrónico:"'.$email.'")';             
        $response = json_decode($this->_curl($url, $params), true);        
        if (isset($response['response']['result']['Accounts']['row']['FL'])){
            $data = $response['response']['result']['Accounts']['row']['FL'];
            $idGlobal = $this->_content($data, 'Account Number');
            $this->_zohoAccountToDB($data, $idGlobal);                    
            return $idGlobal;
        }else{
            $url = "https://crm.zoho.com/crm/private/json/Contacts/searchRecords";        
            $params = 'authtoken='.$this->authToken.'&scope=crmapi&newFormat=2&criteria=(Email:"'.$email.'")';             
            $response = json_decode($this->_curl($url, $params), true);        

            if (isset($response['response']['result']['Contacts']['row']['FL'])){
                $data = $response['response']['result']['Contacts']['row']['FL'];
                $accountId = $this->_content($data, 'ACCOUNTID');

                $url = "https://crm.zoho.com/crm/private/json/Accounts/getRecordById";        
                $params = 'authtoken='.$this->authToken.'&scope=crmapi&newFormat=2&id='.$accountId;             
                $response = json_decode($this->_curl($url, $params), true);        

                if (isset($response['response']['result']['Accounts']['row']['FL'])){
                    $data = $response['response']['result']['Accounts']['row']['FL'];                    
                    $idGlobal = $this->_content($data, 'Account Number');
                    $this->_zohoAccountToDB($data, $idGlobal);                    
                    return $idGlobal;                    
                }                
            }           
        }
        return "";
    }
    
    /*
     * Funcion que nos dice si un contacto se encuantra en la tabla contacto, o en empresa, si es asi devuelve los parametros de la empresa.
     * En caso contrario devuelve false
     * @param $email El correo del usuario que se buscara en la tabla company y en contact
     * @param $data Si existe el email inserta los datos del ticket en el campo ticket en la tabla, sino false
     */
    public function _dataInTable($email, $ticket){
        $idGlobal = "";
        
        //El email esta en la tabla company
        $data = Company::where('email', $email)->get();        
        if (isset($data[0]))                    
            $idGlobal = $data[0]['idGlobal'];

        //El email esta en la tabla contact
        if (empty($idGlobal)){            
            $data = Contact::where('email', $email)->get();        

            if (isset($data[0])){
                $nameAccount = $data[0]['account'];                
                $data = Company::where('name', $nameAccount)->get();                     
                if (isset($data[0]))                    
                    $idGlobal = $data[0]['idGlobal'];            
            }    
        }
        
        if (empty($idGlobal)){
            $idGlobal = $this->_searchByEmail($email);            
        }        

        if (!empty($idGlobal)){
            $tickets = Company::where('idGlobal', $idGlobal)->get();
            $tickets = $tickets[0]['tickets'];
            $tickets.=".\n".$ticket;
            $company = Company::where('idGlobal', $idGlobal)->update(['tickets'=>$tickets, 'synchronized'=>0]);
            return true;
        }
                
        return false;
    }


    /*
     * Funcion que recibee los datos de los tickets y aqui son tratados, para ser ingresados en zoho
     * @param $request Es el json que contiene la data del ticket entrante
     * @return void El resultado es el ingreso del ticket a la bd para ser agregado e zoho
     */
    public function updateTickets(Request $request) {
        $body = preg_replace('/}"/', '}', 
                preg_replace('/"{/', '{', 
                preg_replace('/\\\\\\\\/', '\\', 
                preg_replace('/\\\\"/', '"', 
                preg_replace('/\\\\ud83cdfe0/', '', $request->getContent())))));        

        file_put_contents('log/zendesk', $body."\n", FILE_APPEND);
        $body = json_decode($body, true);
        
        $user = $body['requester_email'];
        $description = $body['description'];
        
        $ticket = "=> Ticket N°: ".$body['id']." * Tipo: ".$body['ticket_type']." * Asunto: ".$body['subject']." * Prioridad: ".$body['priority']." * Estatus: ".$body['status'];
        $res = $this->_dataInTable($user, $ticket);
        
        return json_encode($res);                
    }
    
    /*
     * Funcion para hacer las pruebas
     */
    public function test(Request $request) {
        return $body;        
    }    
    
    public function createTickets($email){
        
        return view('text.reportSMS', ['data'=>$response]);        
    }
}


