<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use \Spatie\Analytics\Period;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use \Spatie\Analytics\AnalyticsFacade as Analytics;
use App\Company;

class GAController extends Controller{

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
     * Funcion para encontrar el id de aquellas cuentas a las cuales se les va a cambiar el nombre
     * @param $accountNumber Es el idGlobal de la empresa
     * @return $idAccount Es el id de la cuenta en zoho
     */
    public function _searchByIdGlobal($accountNumber) {
        $url = "https://crm.zoho.com/crm/private/json/Accounts/searchRecords";        
        $params = 'authtoken='.$this->authToken.'&scope=crmapi&newFormat=2&criteria=(Account Number:"'.$accountNumber.'")';             
        $response = json_decode($this->_curl($url, $params), true); 
        if (isset($response['response']['result']['Accounts']['row']['FL']))
            return $response['response']['result']['Accounts']['row']['FL'];
        else
            return false;
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
     * Funcion para actualizar la fecha de la ultima facturacion en aquellas compañias que hayan facturado este dia
     * @return $void
     */
    public function companiesUpdateLastInvoice() {
        $ids = $this->userDidInvoice();

        if (!$ids)
            return "not done";

        $date = date('Y-m-d');//date('Y-m-d',strtotime("-1 days"));

        foreach($ids as $idGlobal){
            $idGlobal = (int)$idGlobal;
            
            $company = Company::where('idGlobal', $idGlobal)->
                                //where('dateUltimateInvoice','<>',$date)->;
                                update(['dateUltimateInvoice'=>$date, 'synchronized'=>0]);

            $existInTable = Company::where('idGlobal', $idGlobal)->get();
            
            if (!isset($existInTable[0])){

                $data = $this->_searchByIdGlobal($idGlobal);   

                if ($data){
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
                    $dataCompany['urlFirstContact'] = $this->_content($data, 'URL primera visita');
                    $dataCompany['firstPage'] = $this->_content($data, 'Primera página');
                    $dataCompany['dateUltimateInvoice'] = $date;
                    $dataCompany['synchronized'] = 0;                                                                                            
                    $responseCompany = Company::updateOrCreate(['idGlobal' => $idGlobal], $dataCompany);  
                }
            }
        }   
        return "done";
    }
    
    /*
     * Funcion para actualizar la fecha de la ultima facturacion en aquellas compañias que hayan facturado este dia
     * @return $void
     */
    public function companiesUpdateInvoicesLastMonth() {
        $arr = $this->objectsLastMonth('invoices');
        $date = date('Y-m', strtotime(date('Y-m')." -1 month"));
        $date.= date('-d');

        if (!$arr)
            return "not done";   

        $companies = Company::where('registryDate','>' ,$date)->get();                

        foreach($companies as $company){
            $idGlobal = $company['idGlobal'];
            $invoices = $company['invoicesLastMonth'];
            if (isset($arr[$idGlobal])){                
                if ($invoices != $arr[$idGlobal]){
                    Company::where('idGlobal', $idGlobal)->
                        update(['invoicesLastMonth'=>$arr[$idGlobal], 'synchronized'=>0]);                                         
                }                    
            }
        }
  
        return "done";
    }

    /*
     * Funcion para actualizar la fecha de la ultima facturacion en aquellas compañias que hayan creado clientes los ultimos 30 dias
     * @return $void
     */
    public function companiesUpdateClientsLastMonth() {
        date_default_timezone_set('America/Bogota');
        $arr = $this->objectsLastMonth('clients');
        $date = date('Y-m', strtotime(date('Y-m')." -1 month"));
        $date.= date('-d');

        if (!$arr)
            return "not done";   

        $companies = Company::where('registryDate','>' ,$date)->get();

        foreach($companies as $company){
            $idGlobal = $company['idGlobal'];
            $clients = $company['clientsLastMonth'];
            if (isset($arr[$idGlobal])){                
                if ($clients != $arr[$idGlobal]){
                    Company::where('idGlobal', $idGlobal)->
                        update(['clientsLastMonth'=>$arr[$idGlobal], 'synchronized'=>0]);                                         
                }                    
            }
        }
  
        return "done";
    }
    
    /*
     * Funcion que devuelve un arreglo con los ids de los usuarios que hicieron facturas el ultimo dia
     * @return $users 
     */
    public function userDidInvoice(){
        date_default_timezone_set('America/Bogota');        
        $users = [];
        $maxRes = 10000;        
        $startDate = Carbon::today();
        $endDate = Carbon::today();

        $query = Analytics::performQuery(Period::create($startDate, $endDate), "ga:totalEvents", 
                ['dimensions' => 'ga:eventLabel, ga:date, ga:eventCategory, ga:eventAction', 
                 'filters' => 'ga:eventAction==invoice / add / *',
                 'start-index' => 1,
                 'max-results' => $maxRes]);

        $total = $query['totalResults'];                
        $rows = $query['rows'];

        if (empty($rows))
            return false;

        foreach ($rows as $row){
            if ($row[1]==date('Ymd'))
                $users[] = $row[0];
        }
        
        return $users;
    }

    /*
     * Funcion que devuelve un arreglo con los ids de los usuarios que cumplen con cierta condicion para lso utios 30 dias
     * @return $users 
     */
    public function objectsLastMonth($param){
        date_default_timezone_set('America/Bogota');        
        $users = [];
        $maxRes = 10000;        
        $date = date('Y-m', strtotime(date('Y-m')." -1 month"));
        $date.= date('-d');
        $date = explode('-', $date);
        
        $startDate = Carbon::createFromDate($date[0], $date[1], $date[2]);
        $endDate = Carbon::today();

        switch ($param){
            case 'invoices': $filters = 'ga:eventAction==invoice / add / *';
                break;
            case 'clients': $filters = 'ga:eventAction==client / add / *';
                break;            
        }
        
        $query = Analytics::performQuery(Period::create($startDate, $endDate), "ga:totalEvents", 
                ['dimensions' => 'ga:eventLabel, ga:eventCategory, ga:eventAction', 
                 'filters' => $filters,
                 'start-index' => 1,
                 'max-results' => $maxRes]);
        $total = $query['totalResults'];                        
        $rows = $query['rows'];

        foreach ($rows as $row) {
            $users[$row[0]] = $row[3];
        }

        if ($total>$maxRes){
            $step = ((int) ($total/$maxRes))+1;
            for ($i=1;$i<$step;$i++){
                $query = Analytics::performQuery(Period::create($startDate, $endDate), "ga:totalEvents", 
                        ['dimensions' => 'ga:eventLabel, ga:eventCategory, ga:eventAction', 
                         'filters' => $filters,
                         'start-index' => $i*$maxRes,
                         'max-results' => $maxRes]);
                $rows =  $query['rows'];
                foreach ($rows as $row) {
                    $users[$row[0]] = $row[3];
                }
            }            
        }

        if (empty($users))
            return false;

        return $users;
    }
    
    /*
     * Funcion para hacer las pruebas
     */
    public function test() {
        while(1){
            
        }
        $this->invoicesLastMonth();
        
        return $this->companiesUpdateLastInvoice();
        die();
        $analytics = Analytics::getAnalyticsService();

        $query = Analytics::performQuery(Period::days(1), "ga:sessionsWithEvent", 
                ['dimensions' => 'ga:eventLabel, ga:pagePath', 
                 'filters' => 'ga:pagePath==app.alegra.com/invoice/add',
                 'start-index' => 1000,
                 'max-results' => 10]);
        
        $totalResults = $query['totalResults'];
        $rows = $query['rows'];

        
        dd($rows    );
        
        //die('ok');
        
        $analyticsData = Analytics::fetchVisitorsAndPageViews(Period::days(7));
        dd($analyticsData);
        return $analyticsData;        
    }    
}
