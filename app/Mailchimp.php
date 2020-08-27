<?php

namespace App;
use Aws\Lambda\LambdaClient;

class Mailchimp {
    
    public $apiKey = "ddc6fab63d9ba8156a3e2afd457a69b4-us3";
    public $url = "https://us3.api.mailchimp.com/3.0";
    public $idList = "bfe0f9fd16"; 
    
    /*
     * Funcion para obtener los datos de las listas presentes en mailchimp
     * @return $response Son los datos de las listas presentes
     */
    public function getList($param) {                                
	$ch = curl_init($this->url.'/lists');

        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization:apikey $this->apiKey"));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        
	$response = curl_exec($ch);
	curl_close($ch);
        $response = json_decode($response, true);
        
        return $response;
    }
    
    /*
     * Verifica si un email existe en una lista predefinida de mailchimp
     * @return $response Devuelve el email si existe y false en caso de que no exista
     */
    public function verifyExist($list, $email){
        $hash = md5(strtolower($email));        
        $ch = curl_init("$this->url/lists/$list/members/$hash");            
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization:apikey $this->apiKey"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);        
        
        if ($response['status']!="subscribed")
            return false;
        
        return $response;
    }
    
    /*
     * Elimina el usuario que es pasado en el parametro email en la lsta list
     * @param $list Lista en la que se va a buscar
     * @param $email Email q se va a buscar y si es encintrado se va a eliminar
     * @param $response Es vacio si elimino  un usuario, un json de error si no lo encontro
     */
    public function delete($list, $email){
        $hash = md5(strtolower($email));        
        $ch = curl_init("$this->url/lists/$list/members/$hash");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization:apikey $this->apiKey"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);        
        
        return $response;
    }
    
    /*
     * Funcion para la actualizacion masiva de los contactos presentes en mailchimp
     * verificando si ya eisten en alegra. Los caontactos de la lista van a estar contenidos en
     * un archivo js/mailchimp.json 
     * @param $lambdaKey, $lambdaSecret Paraetros para la verificacion de la funcion lambda
     * @param $response El listado de los contactos que ya existen en alegra
     */
    public function massiveDelete($start, $limit, $lambdaKey, $lambdaSecret){
        $listExist = "";
        $contacts = json_decode(file_get_contents('js/mailchimp.json'), true);
        $total =0;  

        for ($i=$start;$i<($start+$limit);$i++){
            if ($i>(count($contacts)-1))
                break;
            
            $email = $contacts[$i]["Email Address"];
            $contact = '{"email":"'.$email.'"}';    
            $response = $this->existLambdaContact($contact, $lambdaKey, $lambdaSecret);
            if ($response){
                $response = $this->delete("bfe0f9fd16", $email);                
                if (empty($response)){
                    $listExist .= $email."\r\n";                
                    $total++;
                }
            }    

        }
        $response = file_put_contents('log/emailsDeleted', $listExist, FILE_APPEND);        
        $response = $listExist.$total;
        
        
        
        return $response;
    }
    
    /*
     * Verifica si un contacto existe en alegra
     * @param $contact El contacto a verificr
     * @param $return Si existe devuelve true, si no existe 
     * devuelve false
     */
    public function existLambdaContact($contact, $lambdaKey, $lambdaSecret) {
        $client = LambdaClient::factory([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'credentials' => [
                'key'    => $lambdaKey,
                'secret' => $lambdaSecret,
            ],            
        ]);

        $result = $client->invoke([
            'FunctionName' => 'checkCompanyExistenceInBi',
            'Payload' => $contact,
        ]); 
        
        $results = json_decode($result->get('Payload'), true);
        
        if (empty($results))
            return false;
        
        return $results;
    }
}