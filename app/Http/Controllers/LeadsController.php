<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\DB;
use App\Lead;
use App\Mailchimp;
use App\Company;
use App\Contact;
use App\OtherContact;
use App\ReportCompany;
use App\Panel;
use Maatwebsite\Excel\Facades\Excel;
use App\LeadValueBinder;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Aws\Lambda\LambdaClient;

/**
 * @SWG\Swagger(
 *   basePath="/api",
 *   @SWG\Info(
 *     title="API Rest candidatos Alegra.",
 *     version="1.0.0"
 *   ),
 *
 *   @SWG\Definition(
 *      definition="candidato", 
 *      type="object", 
 *      required={"name"},
 *          @SWG\Property(
 *              property="name",
 *              default="Samantha Perez",
 *              type="string"
 *          ),
 *          @SWG\Property(
 *              property="phonePrimary",
 *              default="02126821370",
 *               type="string"
 *           ),
 *           @SWG\Property(
 *              property="phoneSecondary",
 *              default="02124359211",
 *              type="string"
 *           ),
 *           @SWG\Property(
 *              property="mobile",
 *              default="04166424255",
 *              type="string"
 *           ),
 *           @SWG\Property(
 *              property="email",
 *              default="samaper@gmail.com",
 *              type="string"
 *           ),
 *           @SWG\Property(
 *               property="company",
 *               default="CANTV",
 *               type="string"
 *           ),
 *           @SWG\Property(
 *               property="source",
 *               default="internet",
 *               type="string"
 *           ),
 *           @SWG\Property(
 *               property="industry",
 *               default="Telas",
 *               type="string"
 *           ),
 *           @SWG\Property(
 *               property="country",
 *               default="Venezuela",
 *               type="string"
 *           ), 
 *   ),
 *
 *   @SWG\Definition(
 *      definition="UpdateLead",
 *      type="object",
 *      description="Es el objeto contacto de Alegra",
 *      allOf={
 *          @SWG\Schema(ref="#/definitions/candidato"),
 *          @SWG\Schema(
 *              required={"id"},
 *              @SWG\Property(property="id", format="int", type="integer")
 *          )
 *      }
 *   )
 * )
 */
class LeadsController extends Controller
{
    public $authToken = "109e870ae7f8f5f23ad6f67e2eca3d82";
    public $lambdaKey = "AKIAIXELQ2B5YONEWDYA";
    public $lambdaSecret = "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";
    public $requestSNS = "";
    public $opSync = "create";
    public $verifyLead = false;
    public $accountsBatch;      
    public $apiTextSMS = "ErTuVXqBQR0-bsYACRfiO1qkK5SAzPVw6TRfGVb0NO";  
    //public $apiTextSMS = "oUdjBSXvBEI-tBI66iAcv0zLmRhX8mmDy81J6D70kR";    
    public $welcomeMsg = "Miles de empresas en Latinoamérica usan Alegra, "
            . "descúbrelo tu también, pruebalo por 30 días. Si tienes dudas "
            . "escríbenos en www.alegra.com/123 y te ayudaremos.";    
    public $growingMsg = "Sigue creciendo con Alegra, ganas tiempo y tranquilidad. Si tienes dudas "
            . "escríbenos en www.alegra.com/123 y te ayudaremos.";    

    /**
     * @SWG\Get(
     *     path="/leads",
     *     summary="Lista todos los candidatos",
     *     description="",
     *     operationId="show",
     *     produces={"application/json"},
     *     tags={"Listar candidatos"},
     *     @SWG\Parameter(
     *         description="Desde que id de candidato se va a listar. Por ejemplo para listar desde el candidato 5, se envía start=5.",
     *         in="query",
     *         name="start",
     *         required=false,
     *         type="integer",
     *         format="int"
     *     ),
     *     @SWG\Parameter(
     *         description="Cantidad de candidatos a partir del inicio que se desea retornar. Por defecto retorna 30 candidatos. Si este valor es mayor que 30, la aplicación retorna error.",
     *         in="query",
     *         name="limit",
     *         required=false,
     *         type="integer",
     *         format="int"
     *     ),
     *     @SWG\Parameter(
     *         name="api_key",
     *         in="header",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Busqueda satisfactoria"
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="El parámetro de inicio (start) para retornar los items debe ser mayor o igual a 0. Y El límite de items para retornar debe estar entre 0 y 30."
     *     ),
     *     security={{"petstore_auth":{"write:pets", "read:pets"}}}
     * )
     */    
    public function index(Request $request)
    {
        $start = $request->input('start');
        $limit = $request->input('limit');   
        $search = $request->input('search');           
        $requestArray = $request->all();
        
        if (array_key_exists('start', $requestArray))
            unset($requestArray['start']);
      
        if (array_key_exists('limit', $requestArray))
            unset($requestArray['limit']);
            
        if (array_key_exists('search', $requestArray))
            unset($requestArray['search']);
                
        $start = (!$start)?0:$start;
        $limit = (!$limit)?30:$limit;
	
        if ((!is_numeric($start))||($start<0))
        {
            return response()
                    ->json(['errors'=>array(['code'=>400,
                        'message'=>'El parámetro start debe ser un entero mayor o igual a 0.'])], 400)            
                    ->header('Content-Type', 'application/json');
        }

        if ((!is_numeric($limit))||($limit<0)||($limit>31))
        {
            return response()
                    ->json(['errors'=>array(['code'=>400,
                        'message'=>'El parametro límit debe ser un entero y estar entre 0 y 30.'])], 400)            
                    ->header('Content-Type', 'application/json');
        }
        
        $queryFormer = "select * from leads.leads";        

        if (!empty($search)){
            $queryFormer .= " where Concat_WS('',name,phonePrimary,phoneSecondary,mobile,email, company,source, industry, country) "
                    . "like '%$search%'";        
        }else{
            if (!empty($requestArray)){
                $queryFormer .= " where true";
            }            
        }    
        
        if (!empty($requestArray)){        
            foreach ($requestArray as $key => $req){
                $queryFormer .=  " and $key like '%$req%'";
            }
        }

        $queryFormer .= " order by id desc";
        $queryFormer .= " limit $start,$limit";        

        $leads = DB::select($queryFormer);
        
        return $leads;
    }

    /**
     * @SWG\Post(
     *     path="/leads",
     *     tags={"Crear candidato"},
     *     operationId="store",
     *     summary="Crea un candidato",
     *     description="",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         description="Objeto candidato a crear",
     *         required=true,     
     *         @SWG\Schema(ref="#/definitions/candidato"),
     *     ),
     *     @SWG\Response(
     *         response=201,
     *         description="El recurso fue creado",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="No se encontró información para crear el recurso.",
     *     ),
     *     security={{"petstore_auth":{"write:pets", "read:pets"}}}
     * )
     */    
    public function store(Request $request)
    {        
        $data = $request->all();  
        
        $countries = json_decode(file_get_contents('js/countries.json'), true);
        $validCountries=[];
        for ($i=0;$i<count($countries);$i++)
            $validCountries[$i] = $countries[$i]['alpha3'];         
	
        $validSources=["Aviso", "Recomendacion", "Chat", "Internet"];
        $validIndustry=["Banca", "Mineria", "Servicios", "Telas"];

        if (empty($data['name'])){
            return response()
                    ->json(['errors'=>array(['code'=>400,
                        'message'=>'No se encontró información para crear el '
                        . 'recurso.'])], 400)            
                    ->header('Content-Type', 'application/json');
        }

        if (isset($data['email'])&&(!filter_var($data['email'], FILTER_VALIDATE_EMAIL))){            
           return response()
                    ->json(['errors'=>array(['code'=>2002, 
                        'message'=>'El correo ingresado es inválido.'])], 400)
                    ->header('Content-Type', 'application/json');           
        }
        
	if (isset($data['source'])&&(!in_array($data['source'], $validSources))){
           return response()
                    ->json(['errors'=>array(['code'=>2002, 
                        'message'=>'El campo source debe ser de el tipo Pick list. Ejemplo: Internet.'
                        . ''])], 400)
                    ->header('Content-Type', 'application/json');           
	}        
        
	if (isset($data['industry'])&&(!in_array($data['industry'], $validIndustry))){
           return response()
                    ->json(['errors'=>array(['code'=>2002, 
                        'message'=>'El campo industry debe ser de el tipo Pick list. Ejemplo: Banca.'
                        . ''])], 400)
                    ->header('Content-Type', 'application/json');           
	}                

	if (isset($data['country'])&&(!in_array($data['country'], $validCountries))){
           return response()
                    ->json(['errors'=>array(['code'=>2002, 
                        'message'=>'El codigo del país es inválido. El campo '
                        . 'country debe ser el código ISO del país. Ejemplo: MEX484.'])], 400)
                    ->header('Content-Type', 'application/json');           
	}

        $lead = Lead::create($data);
        $arrayLead = $lead->toArray();

        return response()
                ->json(['status'=>'ok', 'data'=>$arrayLead], 201)
                ->header('Content-Type', 'application/json');     
    }

    /**
     * @SWG\Get(
     *     path="/leads/{id}",
     *     summary="Muestra un candidato",
     *     description="",
     *     operationId="show",
     *     produces={"application/json"},
     *     tags={"Consultar candidato"},
     *     @SWG\Parameter(
     *         description="Id del candidato a mostrar",
     *         in="path",
     *         name="id",
     *         required=true,
     *         type="integer",
     *         format="int"
     *     ),
     *     @SWG\Parameter(
     *         name="api_key",
     *         in="header",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Busqueda satisfactoria"
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="El candidato no se encontró registrado en Alegra"
     *     ),
     *     security={{"petstore_auth":{"write:pets", "read:pets"}}}
     * )
     */    
    public function show($id)
    {        
        if (!is_numeric($id))
        {
            return response()
                    ->json(['errors'=>array(['code'=>400,
                        'message'=>'El parametro id debe ser un entero.'])], 400)            
                    ->header('Content-Type', 'application/json');
        }        

        $lead = Lead::find($id);

        if (empty($lead))
        {
            return response()
                    ->json(['errors'=>array(['code'=>404,
                        'message'=>'El candidato no se encontró registrado en Alegra'])],404);
        }        
        
        $arrayLead = $lead->toArray();
        
        return response()
                ->json(['status'=>'ok','data'=>$arrayLead], 200)
                ->header('Content-Type', 'application/json');
    }
   
    /**
     * @SWG\Put(
     *     path="/leads/{id}",
     *     tags={"Editar candidato"},
     *     operationId="update",
     *     summary="Actualiza un candidato existente",
     *     description="",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del contacto que se desea editar. Se debe enviar en la URL",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         description="Objeto candidato a ser actualizado",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/candidato"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Busqueda satisfactoria",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="No se encontró el candidato con el id",
     *     ),
     *     security={{"petstore_auth":{"write:pets", "read:pets"}}}
     * )
     */    
    public function update(Request $request, $id)
    {  
        $data       = $request->all(); 
        
        $countries = json_decode(file_get_contents('js/countries.json'), true);
        $validCountries=[];
        for ($i=0;$i<count($countries);$i++)
            $validCountries[$i] = $countries[$i]['alpha3'];                 
        
        $validSources=["Aviso", "Recomendacion", "Chat", "Internet"];
        $validIndustry=["Banca", "Mineria", "Servicios", "Telas"];
        
        if (!is_numeric($id))
        {
            return response()
                    ->json(['errors'=>array(['code'=>400,
                        'message'=>'El parametro id debe ser un entero.'])], 400)            
                    ->header('Content-Type', 'application/json');
        }        
        
        $lead       = Lead::find($id);        

        if (!$lead){
            return response()->json(['errors'=>array(['code'=>404,
                'message'=>'No se encontró el candidato con el id: '.$id])],404);
        }

        if ((isset($data['name'])&&(strlen(trim($data['name']))===0))){
            return response()
                    ->json(['errors'=>array(['code'=>400,
                        'message'=>'El parametro "name" no puede estar vacio. Y debe ser del tipo string'])], 400)            
                    ->header('Content-Type', 'application/json');
        }

        if (isset($data['email'])&&(!filter_var($data['email'], FILTER_VALIDATE_EMAIL))){            
           return response()
                    ->json(['errors'=>array(['code'=>2002, 
                        'message'=>'El correo ingresado es inválido.'])], 400)
                    ->header('Content-Type', 'application/json');           
        }
        
	if (isset($data['source'])&&(!in_array($data['source'], $validSources))){
           return response()
                    ->json(['errors'=>array(['code'=>2002, 
                        'message'=>'El campo source debe ser de el tipo Pick list '
                        . 'source.'])], 400)
                    ->header('Content-Type', 'application/json');           
	}        

	if (isset($data['country'])&&(!in_array($data['country'], $validCountries))){
           return response()
                    ->json(['errors'=>array(['code'=>2002, 
                        'message'=>'El codigo del país es inválido. El campo '
                        . 'country debe ser el código ISO del país, ejemplo MEX484.'])], 400)
                    ->header('Content-Type', 'application/json');           
	}
        
        $lead->update($data);
        $arrayLead = $lead->toArray();

        return response()->json(['status'=>'ok','data'=>$arrayLead], 200);                                      
    }

    /**
     * @SWG\Delete(
     *     path="/leads/{id}",
     *     summary="Elimina un candidato del sistema",
     *     description="",
     *     operationId="destroy",
     *     produces={"application/json"},
     *     tags={"Eliminar candidato"},
     *     @SWG\Parameter(
     *         description="Id del candidato a eliminar",
     *         in="path",
     *         name="id",
     *         required=true,
     *         type="integer",
     *         format="int"
     *     ),
     *     @SWG\Parameter(
     *         name="api_key",
     *         in="header",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="El contacto fue eliminado correctamente"
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="El candidato no se encontró registrado en Alegra"
     *     ),
     *     security={{"petstore_auth":{"write:pets", "read:pets"}}}
     * )
     */    
    public function destroy($id)
    {
        if (!is_numeric($id))
        {
            return response()
                    ->json(['errors'=>array(['code'=>400,
                        'message'=>'El parametro id debe ser un entero.'])], 400)            
                    ->header('Content-Type', 'application/json');
        }        
        
        $response = Lead::destroy($id);
        
        if (!$response){
            return response()->json(['errors'=>array(['code'=>404,
                'message'=>'El candidato no se encontró registrado en Alegra'])],404);
        }
        return response()->json(['status'=>'ok',
            'data'=>'El candidato fue eliminado correctamente'], 200);                              
    }
    
    /*
     * Metodo para mostrar los campos del excel que va a ser importados en la base de datos
     * 
     * @parameter Request $request Son los datos que vienen del formulario, en nuestro caso son los datos del archivo
     * @parameter
     * 
     * @return Un arreglo que contiene los leads que seran guardados
     */
    public function importPreview(Request $request){
        $file = $request->file('excel');        
        $destinationPath = 'import';
        $request->file('excel')->move($destinationPath, 'leads');
        $leads = [];
        $reader = Excel::load($destinationPath.'/leads');
        $leads = $reader->toArray();

        if (!empty($leads[0][0]))
            $leads = $leads[0];
        
        $auxLeads = [];
        foreach($leads as $lead){
            foreach ($lead as $key => $value){
                if ($key===0)
                    array_pop($lead);                
            }
            array_push($auxLeads,$lead);
        }
        $leads = $auxLeads;                
        
        $fields = $this->getColumns();
        
        return response()->json(['status'=>'ok',
            'data'=>$leads], 200);                              

        return view('leads.importPreview')
                ->with('leads', $leads)
                ->with('fields', $fields);
    }
    
    /*
     * Obtener estructura de las columnas tabla lead
     * 
     * @return Un arreglo con los campos de la tabla lead
     */
    public function getColumns(){
        $columns = DB::getSchemaBuilder()->getColumnListing('leads');
        if ($index = array_search('created_at', $columns))
            unset($columns[$index]);

        if ($index = array_search('updated_at', $columns))
            unset($columns[$index]);
        
        if ($index = array_search('id', $columns))
            unset($columns[$index]);
        
        $columns = ["0"=>"No importar","name"=>"Nombre","phonePrimary"=>"Telefono 1",
            "phoneSecondary"=>"Telefono 2","mobile"=>"Celular",
            "email"=>"Correo","company"=>"Empresa","source"=>"Fuente",
            "industry"=>"Sector","country"=>"Pais"];
        
        return $columns;
    }
    
    /*
     * Metodo para importar leads desde un archivo excel hacia la base de datos
     * 
     * @parameter request Son  todos los leads en un arreglo que van a ser almacenados en la BD
     * 
     * @return La respuesta de la transaccion en la BD
     */
    public function addFromExcelImport(Request $request){
        $data = $request->all();
        $leads = json_decode($data['leads'], true);
        
        foreach ($leads as $lead){
            $res = Lead::create($lead);
        }
        
        return response()->json(['status'=>'ok',
            'data'=>'Los candidatos fueron creados correctamente'], 200);                              
    }    
    
    /*
     * Metodo para almacenar comentarios a un lead
     * 
     * @parameter request El comentario que va a ser agregado
     * @parameter id Id del candidato al cual vamos agregar el comentario
     * 
     * @return
     */
    public function addComment(Request $request, $id){
        $data = $request->input('comment');
        $lead = Lead::find($id);

        if (!$lead = Lead::find($id)){
            return response()->json(['errors'=>array(['code'=>404,
                'message'=>'No se encontró el candidato con el id: '.$id])],404);                                      
        }

        $res = $lead->update(['comment'=>$data]);
        
        if (!$res) {
            return response()->json(['errors'=>array(['code'=>404,
                'message'=>'Ocurrio unproblema con los datos del comentario, favor verifique'])],404);
        }
            
        return response()->json(['status'=>'ok',
            'data'=>'El comentario fue agregado correctamente'], 200);                              
    }      
    
    /*
     * Metodo que envia la confirmacion del mensaje sns a aws
     * @parameter $body Es el mensaje en modo arreglo enviado por el sns 
     * @return void
     */
    public function _confirmSubscription($body) {
        $url = $body['SubscribeURL'];
        
        $this->_curl($url);
    }
    
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
        
/*        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);        
        $response = curl_exec ($ch);        */
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
    
    public function _verifyFieldsAccount($id, $body) {
        $url = "https://crm.zoho.com/crm/private/json/Accounts/getRecordById";
        $params = "authtoken=".$this->authToken."&scope=crmapi&newFormat=2&id=".$id;        
        $account = json_decode($this->_curl($url, $params), true);        
        $account = $account['response']['result']['Accounts']['row']['FL'];

        $log = (strcasecmp($account[36]['content'], 'null')!==0)?$account[36]['content']."\n":"";
        
        if (strcasecmp($account[34]['content'], $body['email'])!==0)
            $log .= date('d/m/Y h:i')." Cambio correo ".$account[34]['content']." por ".$body['email']."\n";
        
        if (strcasecmp($account[5]['content'], $body['phone'])!==0)
            $log .= date('d/m/Y h:i')." Cambio telefóno ".$account[5]['content']." por ".$body['phone']."\n";
        
        return $log;
    }
    
    /*
     * Funcion para determinar el usuario/propietario de la cuenta
     * @param $idGlobal Es el id que Alegra le otorga a la companie
     * @return $email El correo del usuario al cual se le va a asignar la cuenta
     */
    public function _userOwner($idGlobal) {        
        $users = json_decode(file_get_contents('js/users.json'), true);
        
        foreach ($users as $user) {
            $last = substr($idGlobal, -1);
            
            if (in_array($last, $user['ids']))
                return $user['email'];                    
        }
        
        return false;
    }
    
    /*
     * Funcion para crear tarea en zoho de llamar a las nuevas cuentas
     * @parameter $id Es el objeto cuenta creado
     * @return void  
     */
    public function _createTaskCall($account) {
        $response = $account;
        
        return $response;
    }
    
    /*
     * Funcion para guardar los errores del sistema
     * @parameter $data Es el mensaje de error a guardar, en el caso de problemas en la 
     * insercion con los datos
     * @return $response Es la respuesta de la insercion
     */
    public function _saveLog($data) {
        $response = file_put_contents('log/log', $data.",\n\n", FILE_APPEND);
        return $response;
    }
    
    /*
     * Funcion para guardar el sumario diario del sistema
     * @parameter $operation Es el tipo de operacion que se ejecuto, crear o editar
     * @return void
     */
    public function _saveSummary($operation) {
        $rows = ""; 

        $content = file('log/summary');        
        $parts = preg_split('/\s+/', $content[1]);
        $date = $parts[0];
        
        if (strcmp(date('Y-m-d'), $date)===0){
            $create = ($operation==='create')?$parts[1]+1:$parts[1];
            $edit = ($operation==='edit')?$parts[2]+1:$parts[2];
            $rows = "Fecha\t\t\tCuentas creadas\t\tCuentas editadas\n";
            $rows.= date('Y-m-d')."\t\t\t$create\t\t\t$edit\n";
            
            for($i=2;$i<count($content);$i++){
                $rows.= $content[$i];
            }            
        }else{
            $create = ($operation==='create')?1:0;
            $edit = ($operation==='edit')?1:0;            
            $rows = "Fecha\t\t\tCuentas creadas\t\tCuentas editadas\n";
            $rows.= date('Y-m-d')."\t\t\t$create\t\t\t$edit\n";
            
            for($i=1;$i<count($content);$i++){
                $rows.= $content[$i];
            }            
        }
        
        $response = file_put_contents('log/summary', $rows);

        return $rows;                
    }
    
    /*
     * Funcion para el tratamiento de los caracteres especiales
     * @param $param String de entrada
     * @return String de salida
     * 
     */
    public function _specialChars($param) {
        $param = str_replace('%', '%25', $param);
        $param = str_replace('&', '%26', $param);
        $param = str_replace('<', '%3C', $param);
        $param = str_replace('>', '%3E', $param);

        return '<![CDATA['.$param.']]>';
    }
    
    /*
     * Funcion para actualizar las cuentas
     * @param $body Contiene los datos de la cuenta a ser actualizada
     * @return $response Devuelve el id de la cuenta actualizada  
     */
    public function _updateAccount($body,  $idAccount=null) {        
        $accountNumber = (string)$body['idGlobal'];

        if (empty($idAccount)){
            $url = "https://crm.zoho.com/crm/private/json/Accounts/searchRecords";        
            $params = 'authtoken='.$this->authToken.'&scope=crmapi&newFormat=2&criteria=(Account Number:"'.$accountNumber.'")';             
            $response = json_decode($this->_curl($url, $params), true);

            if (!isset($response['response']['result'])){
                $this->_saveLog ("$this->requestSNS");
                return "Error en _updateAccount";
            }
            $accounts = $response['response']['result']['Accounts']['row'];
            if (isset($accounts[0])){
                foreach ($accounts as $account){                    
                    if ($this->_content($account['FL'], 'Account Number')==$body['idGlobal'])
                        break;
                }        
            }else{
                $account = $accounts;
            }

            $idAccount = $this->_content($account['FL'], 'ACCOUNTID');   
        }
        
        $city = isset($body['address']['city'])?$body['address']['city']:"";
        $address = isset($body['address']['address'])?$body['address']['address']:"";
        $country = isset($body['address']['country'])?$body['address']['country']:"";
        $dateEnd = "";
        if (!empty($body['membership']['dateEnd']))                        
            $dateEnd = $body['membership']['dateEnd'];
        
        $request = json_decode($this->requestSNS, true);        
        
        $xmlData  ='<Accounts>';    
        $xmlData .= '<row no="1">';           
        $xmlData .= '<FL val="Account Name">'.$this->_specialChars($body['name'].' - '.$body['idGlobal']).'</FL>';
        $xmlData .= '<FL val="Account Number">'.$body['idGlobal'].'</FL>';
        $xmlData .= '<FL val="Account Owner">'.$this->_userOwner($body['idGlobal']).'</FL>'; 
        $xmlData .= '<FL val="Phone">'.$this->_specialChars($body['phone']).'</FL>';
        $xmlData .= '<FL val="Correo electrónico">'.$this->_specialChars($body['email']).'</FL>';
        $xmlData .= '<FL val="Origen">'.$body['origin'].'</FL>';
        $xmlData .= '<FL val="Identificación">'.$body['identification'].'</FL>';
        $xmlData .= '<FL val="Fecha de registro">'.$body['registryDate'].'</FL>';
        $xmlData .= '<FL val="Dirección">'.$this->_specialChars($address).'</FL>';
        $xmlData .= '<FL val="Ciudad">'.$this->_specialChars($city).'</FL>';   
        $xmlData .= '<FL val="País">'.$this->_specialChars($country).'</FL>';  
        if (strcmp($request['Subject'], 'trial-reactivated')===0) $xmlData .= '<FL val="Estado">Reactivado</FL>'; 
        $xmlData .= '<FL val="Plan actual">'.$request['Message']['company']['membership']['planName'].'</FL>'; 
        $xmlData .= '<FL val="Régimen">'.$this->_specialChars($body['regime']).'</FL>';        
        $xmlData .= '<FL val="Versión">'.$this->_specialChars($body['applicationVersion']).'</FL>'; 
        $xmlData .= '<FL val="Código promocional">'.$this->_specialChars($body['coupon']['code']." ".$body['coupon']['description']).'</FL>';
        $xmlData .= '<FL val="Fecha fin demo">'.$dateEnd.'</FL>';         
        $xmlData .= '</row>';
        $xmlData .= '</Accounts>';                                       

        $url = "https://crm.zoho.com/crm/private/json/Accounts/updateRecords";
        $params = "authtoken=$this->authToken&scope=crmapi&id=$idAccount&xmlData=".$xmlData;
        $response = json_decode($this->_curlPost($url, $params), true); 

        if (!isset($response['response']['result'])){ 
            $this->_saveLog("$this->requestSNS");
            return "Error guardando las actualizaciones";
        }    
        else
            $this->opSync = 'edit';

        return $response;
    }
    
    /*
     * Funcion para crear la cuenta en Zoho
     * @parameter $body Contiene los datos de la cuenta que va a ser creada
     * @return $response Devuelve el id de la nueva cuenta
     */
    public function _createOrEditAccountZoho($body) {
        $city = isset($body['address']['city'])?$body['address']['city']:"";
        $address = isset($body['address']['address'])?$body['address']['address']:"";
        $country = isset($body['address']['country'])?$body['address']['country']:"";
        $dateEnd = "";
        if (!empty($body['membership']['dateEnd']))                        
            $dateEnd = $body['membership']['dateEnd'];
        
        $request = json_decode($this->requestSNS, true);                
        
        $status = (($request['Message']['company']['membership']['planName']=="Plan Pro")||
            ($request['Message']['company']['membership']['planName']=="Plan Plus")||
            ($request['Message']['company']['membership']['planName']=="Plan Pyme"))?"Pagando":"Por llamar";
                
        $xmlData  ='<Accounts>';    
        $xmlData .= '<row no="1">';           
        $xmlData .= '<FL val="Account Name">'.$this->_specialChars($body['name'].' - '.$body['idGlobal']).'</FL>';
        $xmlData .= '<FL val="Account Number">'.$body['idGlobal'].'</FL>';
        $xmlData .= '<FL val="Account Owner">'.$this->_userOwner($body['idGlobal']).'</FL>'; 
        $xmlData .= '<FL val="Phone">'.$this->_specialChars($body['phone']).'</FL>';
        $xmlData .= '<FL val="Correo electrónico">'.$this->_specialChars($body['email']).'</FL>';
        $xmlData .= '<FL val="Origen">'.$body['origin'].'</FL>';
        $xmlData .= '<FL val="Identificación">'.$body['identification'].'</FL>';
        $xmlData .= '<FL val="Fecha de registro">'.$body['registryDate'].'</FL>';
        $xmlData .= '<FL val="Dirección">'.$this->_specialChars($address).'</FL>';
        $xmlData .= '<FL val="Ciudad">'.$this->_specialChars($city).'</FL>';   
        $xmlData .= '<FL val="País">'.$this->_specialChars($country).'</FL>';
        $xmlData .= '<FL val="Estado">'.$status.'</FL>';        
        if (strcmp($request['Subject'], 'trial-reactivated')===0) $xmlData .= '<FL val="Estado">Reactivado</FL>';  
        $xmlData .= '<FL val="Plan actual">'.$request['Message']['company']['membership']['planName'].'</FL>';
        $xmlData .= '<FL val="Régimen">'.$this->_specialChars($body['regime']).'</FL>';        
        $xmlData .= '<FL val="Versión">'.$this->_specialChars($body['applicationVersion']).'</FL>'; 
        $xmlData .= '<FL val="Código promocional">'.$this->_specialChars($body['coupon']['code']." ".$body['coupon']['description']).'</FL>';                        
        $xmlData .= '<FL val="Fecha fin demo">'.$dateEnd.'</FL>';                                

        $xmlData .= '</row>';
        $xmlData .= '</Accounts>';                                       

        $url = "https://crm.zoho.com/crm/private/json/Accounts/insertRecords";
        $params = "authtoken=".$this->authToken."&scope=crmapi&duplicateCheck=1&xmlData=".$xmlData; 
        $response = json_decode($this->_curlPost($url, $params), true);

        //Envio de mensaje de bienvenida
        $phoneNumbers = $this->_parsePhones($body['phone'], $country);
        if ((!empty($phoneNumbers))&&($body['applicationVersion']!="other")&&($body['applicationVersion']!="republicaDominicana")&&($body['applicationVersion']!="usa"))
            $this->_sendMessage($phoneNumbers, "welcome");
       
        $duplicatedId = (isset($response['response']['error']['code'])&&($response['response']['error']['code']===4819));
        $existAccount = (isset($response['response']['result']['message'])&&
            (strcmp($response['response']['result']['message'], 'Record(s) already exists')===0));
        $successfuly = (isset($response['response']['result']['message'])&&
            (strcmp($response['response']['result']['message'], 'Record(s) added successfully')===0));
        
        if ($successfuly) $this->opSync = 'create';
        
        if ($existAccount){
            $id = $response['response']['result']['recorddetail']['FL'][0]['content'];
            $response = $this->_updateAccount($body, $id);                   
        }        

        if ($duplicatedId){
            $response = $this->_updateAccount($body);       
        }
                
        if (!isset($response['response']['result'])) {
            $this->_saveLog("$this->requestSNS");
            return "Error insertando la cuenta";
        }
            
        $response = $response['response']['result']['recorddetail']['FL'][0]['content'];

        return $response;
    }
    
    /*
     * Funcion que verifica si existe la cuenta, de no ser asi se crea
     * @parameter $body Contiene el mensaje con la data de la Cuenta
     * @return $params Son el id y el nombre de la Cuenta dentro de un arreglo
     */
    public function _verifyAccount($body) {
        $id = $this->_createOrEditAccountZoho($body);
        $url = "https://crm.zoho.com/crm/private/json/Accounts/getRecordById";        
        $params = "authtoken=".$this->authToken."&scope=crmapi&newFormat=2&id=".$id;        
        $response = json_decode($this->_curl($url, $params), true); 
        if (!isset($response['response']['result'])) {
            $this->_saveLog("$this->requestSNS");
            return "Error en  _verifyAccount";
        }
        
        $nameAccount = $this->_content($response['response']['result']['Accounts']['row']['FL'], 'Account Name');
        $params = [];
        $params['idAccount'] = $id;
        $params['nameAccount'] = $nameAccount;
        $params['response']=$response;
        
        return $params;
    }
    
    /*
     * Funcion para crear los contactos en Zoho
     * @parameter $body Es el contenido del sns, aca estan todos los datos de los contactos y de la cuenta
     * @parameter $i Es el indice del arreglo de contactos que se esta verificando/creando
     * @return $params Es un arreglo que contiene el id de la cuenta y del contacto
     */
    function _createOrEditContactZoho($body, $i){
        $account = $this->_verifyAccount($body['Message']['company']); 
        $contact = $body['Message']['company']['users'][$i];
        $lastName = (empty($contact['name']))?$body['Message']['company']['name']:$contact['name']." ".$contact['lastName'];
        
        $xmlData  ='<Contacts>';   
        $xmlData .= '<row no="1">';   
        $xmlData .= '<FL val="Account Name">'.$this->_specialChars($body['Message']['company']['name'].
                ' - '.$body['Message']['company']['idGlobal']).'</FL>';
        $xmlData .= '<FL val="Contact Owner">'.$this->_userOwner($body['Message']['company']['idGlobal']).'</FL>';         
        $xmlData .= '<FL val="Last Name">'.$this->_specialChars($lastName).'</FL>';
        $xmlData .= '<FL val="Creado Automático">TRUE</FL>';        
        $xmlData .= '<FL val="Email">'.$contact['email'].'</FL>';
        $xmlData .= '</row>';
        $xmlData .= '</Contacts>';   
        
        $url = "https://crm.zoho.com/crm/private/json/Contacts/insertRecords";
        $params = "authtoken=".$this->authToken."&scope=crmapi&duplicateCheck=2&xmlData=".$xmlData;
        $response = json_decode($this->_curlPost($url, $params), true);
        
        if (!isset($response['response']['result'])) {
            $this->_saveLog("$this->requestSNS");
            return "Error en _createOrEditContactZoho";
        }
        
        $idContact = $response['response']['result']['recorddetail']['FL'][0]['content'];

        $params = [];
        $params['idAccount']=$account['idAccount'];
        $params['idContact']=$idContact;

        return $params;
    }
    
    /*
     * Metodo que verifica/asocia un lead con una cuenta/contacto
     * @param $param Es una arreglo que contiene el id del lead, el email del lead
     * el id de la cuenta y el id del contacto a asociar al lead
     * @response $response La respuesta de la asociacion
     */
    public function _verifyLead($param) {

        $idLead = $param['idLead'];
        $email = $param['email'];
        $idAccount = $param['idAccount'];
        $idContact = $param['idContact'];

        $xmlData='<Potentials>';
        $xmlData.='<row no="1">';
        $xmlData.='<option val="createPotential">false</option>';
        $xmlData.='<option val="assignTo">'.$email.'</option>';
        $xmlData.='<option val="notifyLeadOwner">true</option>';
        $xmlData.='<option val="notifyNewEntityOwner">true</option>';
        $xmlData.='</row>';
        $xmlData.='<row no="2">';
        $xmlData.='<FL val="ACCOUNTID">'.$idAccount.'</FL>';
        $xmlData.='<FL val="CONTACTID">'.$idContact.'</FL>';
        $xmlData.='</row>';
        $xmlData.='</Potentials>';            

        $url = "https://crm.zoho.com/crm/private/json/Leads/convertLead";
        $params = "authtoken=$this->authToken&scope=crmapi&leadId=$idLead&xmlData=".$xmlData;
        $response = json_decode($this->_curl($url, $params), true);        
        
        return $response;
    }            
    
    /*
     * Funcion que verifica si la nueva empresa esta registrada en Zoho, tanto en contacts, como en leads 
     * @parameter $body Es el mensaje en modo arreglo que envia el sns, alli esta contenida la informacion
     * de la empresa 
     */
    public function _verifyZoho($body) {
        $response = false; 
        for ($i=0;$i<count($body['Message']['company']['users']);$i++) {                        
            $ids = $this->_createOrEditContactZoho($body, $i);
            $this->_saveSummary($this->opSync);
            if (!$this->verifyLead) return $ids;//BORRAR            
            
            $email = $body['Message']['company']['users'][$i]['email'];
            $xmlData = '<Leads>'
                . '<row no="1">'
                . '<FL val="Email">'.$email.'</FL>'
                . '</row>'
                . '</Leads>';

            $url = "https://crm.zoho.com/crm/private/json/Leads/insertRecords";
            $params = "authtoken=".$this->authToken."&scope=crmapi&duplicateCheck=2&xmlData=".$xmlData;
            $response = json_decode($this->_curlPost($url, $params), true);            

            if (isset($response['response']['result'])){
                $idLead = $response['response']['result']['recorddetail']['FL']['0']['content'];            
                $param = ['idLead'=>$idLead,'email'=>$email, 'idAccount'=>$ids['idAccount'], 'idContact'=>$ids['idContact']];
                $response = $this->_verifyLead($param);
            }
        }
        
        $email = $body['Message']['company']['email'];
        $xmlData = '<Leads>'
            . '<row no="1">'
            . '<FL val="Email">'.$email.'</FL>'
            . '</row>'
            . '</Leads>';

        $url = "https://crm.zoho.com/crm/private/json/Leads/insertRecords";
        $params = "authtoken=".$this->authToken."&scope=crmapi&duplicateCheck=2&xmlData=".$xmlData;
        $response = json_decode($this->_curlPost($url, $params), true);            

        if (isset($response['response']['result'])){
            $idLead = $response['response']['result']['recorddetail']['FL']['0']['content'];            
            $param = ['idLead'=>$idLead,'email'=>$email, 'idAccount'=>$ids['idAccount'], 'idContact'=>$ids['idContact']];
            $response = $this->_verifyLead($param);
        }        
        
        return $response;
    }
    
    /*
     * Metodo que notifica la creacion de una nueva empresa en Alegra y revisa en Zoho si el lead existe
     * @parameter $request Es el request pasado por la llamada
     * @return void
     */
    public function newCompany(Request $request) {
        
        $body = preg_replace('/}"/', '}', 
                preg_replace('/"{/', '{', 
                preg_replace('/\\\\\\\\/', '\\', 
                preg_replace('/\\\\"/', '"', 
                preg_replace('/\\\\ud83cdfe0/', 'Carita', $request->getContent())))));
        $this->requestSNS = $body;        
        
        $body = json_decode($body, true);        
        file_put_contents('js/sns.json', json_encode($body).', '."\n", FILE_APPEND);                
        
        if (!isset($body['Type'])) {
            $this->_saveLog("$this->requestSNS");
            return 'Error parseando el body';
        }
        
        if ($body['Type'] != 'Notification') {
            $this->_confirmSubscription($body);
        }else{
            if ($body['Subject']=="new-company")
                $response = $this->_verifyZoho($body);   
            $response = $this->_saveTemp($body);            
        }
        
        return $response;
    }
    
    /*
     * Funcion para guardar las nuevas cuentas/empresas y contactos en una base
     * de datos de manera temporal, para luego sincronizarlas a traves del api en zoho
     * 
     * @parameter $data Es el sns que viene de Alegra, este contiene los datos de la
     * empresa y los contactos que se van a crar/editar en zoho
     * 
     * @return $response Contiene el resultado del procedimiento de guardado 
     */
    public function _saveTemp($data) {
        $mailchimp = new Mailchimp();                
        $company = $data['Message']['company'];
        $city = isset($company['address']['city'])?$company['address']['city']:"";
        $address = isset($company['address']['address'])?$company['address']['address']:"";
        $country = isset($company['address']['country'])?$company['address']['country']:"";
        $dateEnd = "";

        if (!empty($company['membership']['dateEnd']))                
            $dateEnd = $company['membership']['dateEnd'];

        switch ($data['Subject']){
            case 'trial-reactivated': $status = 'Reactivado'; break;
            default: $status = ''; break;                    
        }        
        
        $dataCompany = [];
        $dataCompany['idGlobal'] = $company['idGlobal'];
        $dataCompany['name'] = $company['name'].' - '.$company['idGlobal'];
        $dataCompany['identification'] = $company['identification'];
        $dataCompany['phone'] = $company['phone'];
        $dataCompany['email'] = $company['email'];
        $dataCompany['origin'] = $company['origin'];
        $dataCompany['owner'] = $this->_userOwner($company['idGlobal']);
        $dataCompany['registryDate'] = $company['registryDate'];
        $dataCompany['address'] = $address;
        $dataCompany['city'] = $city;
        $dataCompany['country'] = $country;
        if ($country == "MEX"){            
            $dataCompany['city'] = $company['address']['colony'];
            $street = "";
            $street.= (!empty($company['address']['street']))?$company['address']['street']." ":"";
            $street.= (!empty($company['address']['exteriorNumber']))?$company['address']['exteriorNumber']." ":"";
            $street.= (!empty($company['address']['colony']))?$company['address']['colony']." ":"";
            $street.= (!empty($company['address']['municipality']))?$company['address']['municipality']." ":"";
            $street.= (!empty($company['address']['locality']))?$company['address']['locality']." ":"";
            $street.= (!empty($company['address']['state']))?$company['address']['state']." ":"";
            $street.= (!empty($company['address']['zipCode']))?$company['address']['zipCode']." ":"";
            $addressMex = (!empty($street))?"Calle ".$street:"";
            $dataCompany['address'] = substr($addressMex, 0, 599);
        }
        /*if (($country == "MEX")&&(empty($dataCompany['city']))&&(empty($dataCompany['address']))){
            
            $addressMex = $this->_getMexAddress($email);
            $dataCompany['city']=$addressMex['city'];
            $dataCompany['address']=$addressMex['address'];
        }*/
        $dataCompany['status'] = $status;
        $dataCompany['plan'] = $company['membership']['planName'];
        $dataCompany['regime'] = $company['regime'];
        $dataCompany['version'] = $company['applicationVersion'];
        $dataCompany['sector'] = $company['sector'];
        $dataCompany['coupon'] = $company['coupon']['code']." ".$company['coupon']['description'];        
        $dataCompany['dateEnd'] = $dateEnd;
        $urlFirstContact = "";
        $firstPage = "";
        if (!empty($company['metadata'])){
            foreach ($company['metadata'] as $var){
                if ($var['name']=="Primer landing Referidor"){
                    $urlFirstContact = substr($var['value'], 0, 31999);
                    break;
                }
            }
            foreach ($company['metadata'] as $var){
                if ($var['name']=="Primer landing URL"){
                    $firstPage = substr($var['value'], 0, 31999);
                    break;
                }
            }            
        }    
     
        $dataCompany['urlFirstContact'] = $urlFirstContact;
        $dataCompany['firstPage'] = $firstPage;
        $dataCompany['synchronized'] = 0;
        $idGlobal = $dataCompany['idGlobal'];
        $responseCompany = Company::updateOrCreate(['idGlobal' => $idGlobal], $dataCompany);        

        //Elimino el usuario de mailchimp si existe;
        $mailchimp->delete("bfe0f9fd16", $company['email']);
  
        $contacts = $company['users'];
        foreach ($contacts as $contact){
            $name = ($contact['name']." ".$contact['lastName']===" ")?$company['name']:$contact['name']." ".$contact['lastName'];
            $dataContact = [];
            $dataContact['idGlobal'] = $contact['idGlobal'];
            $dataContact['name'] = $name;
            $dataContact['lastName'] =  $name;
            $dataContact['account'] = $company['name'].' - '.$company['idGlobal'];
            $dataContact['owner'] = $this->_userOwner($company['idGlobal']);
            $dataContact['email'] = $contact['email'];
            $dataContact['synchronized'] = 0;
            $idGlobal = $dataCompany['idGlobal'];
            $response = Contact::updateOrCreate(['idGlobal' => $idGlobal], $dataContact);  
            //Elimino el usuario de mailchimp si existe;
            $mailchimp->delete("bfe0f9fd16", $contact['email']);            
        }

        return $responseCompany;
    }
    
    /*
     * Funcion a traves de la cual se sincroniza lo que esta en las tablas companies y contacts
     * por lotes cada cierto tiempo a zoho a traves del api
     * 
     * @return $response Es la respuesta del envio del api 
     */
    public function batchSynchronize() {        
        $companies = Company::where('synchronized', 0)->get();        
        $contacts = Contact::where('synchronized', 0)->get();
        
        if (count($companies)===0) return 'empty';
        $i = 0;
        $data = [];

        foreach ($companies as $company){
            Company::where('id', $company['id'])->update(['synchronized' => 1]);
            $data[] = $company;
            $i++;                  
            if ((($i%100)==0)||($i==count($companies))){                                
                $response = $this->_createOrEditAccountZohoBatch($data);               
                $data = [];
            }
        }

        if (count($contacts)===0) return 'empty';
        $i = 0; 
        $data = [];                
        
        foreach($contacts as $contact){
            Contact::where('id', $contact['id'])->update(['synchronized' => 1]);
            $data[] = $contact;
            $i++;
            if ((($i%100)==0)||($i==count($contacts))){
                $response = $this->_createOrEditContactZohoBatch($data);                
                $data = [];
            }            
        }
        
        return $response;
    }        
    
    /*
     * Funcion para crear las cuentas por lote en Zoho, si ya existe 
     * se realiza la edicion
     * @parameter $data Contiene los datos de las cuentas que van a ser creadas
     */
    public function _createOrEditAccountZohoBatch($data){
        $this->accountsBatch = $data;
        $i = 0;        
        $xmlData  = '<Accounts>';    
        foreach ($data as $company){
            $xmlData .= '<row no="'.$i.'">';           
            $xmlData .= '<FL val="Account Name">'.$this->_specialChars($company['name']).'</FL>';
            $xmlData .= '<FL val="Account Number">'.$company['idGlobal'].'</FL>';
            $xmlData .= '<FL val="Account Owner">'.$company['owner'].'</FL>'; 
            $xmlData .= '<FL val="Phone">'.$this->_specialChars($company['phone']).'</FL>';
            $xmlData .= '<FL val="Correo electrónico">'.$this->_specialChars($company['email']).'</FL>';
            $xmlData .= '<FL val="Origen">'.$company['origin'].'</FL>';
            $xmlData .= '<FL val="Identificación">'.$this->_specialChars($company['identification']).'</FL>';
            $xmlData .= '<FL val="Fecha de registro">'.$company['registryDate'].'</FL>';
            $xmlData .= '<FL val="Dirección">'.$this->_specialChars($company['address']).'</FL>';
            $xmlData .= '<FL val="Ciudad">'.$this->_specialChars($company['city']).'</FL>';   
            $xmlData .= '<FL val="País">'.$this->_specialChars($company['country']).'</FL>';
            if (!empty($company['status']))
                $xmlData .= '<FL val="Estado">'.$company['status'].'</FL>';        
            $xmlData .= '<FL val="Plan actual">'.$company['plan'].'</FL>';      
            $xmlData .= '<FL val="Régimen">'.$this->_specialChars($company['regime']).'</FL>';        
            $xmlData .= '<FL val="Versión">'.$this->_specialChars($company['version']).'</FL>'; 
            $xmlData .= '<FL val="Código promocional">'.$this->_specialChars($company['coupon']).'</FL>';                                    
            $xmlData .= '<FL val="Fecha fin demo">'.$company['dateEnd'].'</FL>';            
            $xmlData .= '</row>'; 
            $i++;
        }
        $xmlData .= '</Accounts>';

        $url = "https://crm.zoho.com/crm/private/json/Accounts/insertRecords";
        $params = "authtoken=".$this->authToken."&scope=crmapi&duplicateCheck=1&version=4&xmlData=".$xmlData;
        $response = json_decode($this->_curlPost($url, $params), true);        
        
        $responseInsert = $response;
        $responses = [];

        if (isset($response['response']['result']['row'][0]))
            $responses = $response['response']['result']['row'];
        else
            $responses[0] = $response['response']['result']['row'];

        $ids = [];
        foreach ($responses as $response){
            if (isset($response['success'])&&($response['success']['code']==="2000"))
                $ids[] = $this->_content($response['success']['details']['FL'], 'Id');
            if (isset($response['success'])&&($response['success']['code']==="2002"))
                $ids[] = $this->_content($response['success']['details']['FL'], 'Id');
        }

        $idList = "";
        for ($i=0;$i<count($ids);$i++){
            if (empty($idList))
                $idList.=$ids[$i];
            else
                $idList.=";".$ids[$i];
        }
        
        $response = $responseInsert;
        $responses = [];
        if (!empty($idList)){
            $url = "https://crm.zoho.com/crm/private/json/Accounts/getRecordById";        
            $params = "authtoken=$this->authToken&scope=crmapi&newFormat=2&idlist=$idList";        
            $response = json_decode($this->_curl($url, $params), true);
            $responses = [];

            if (isset($response['response']['result']['Accounts']['row'][0]))
                $responses = $response['response']['result']['Accounts']['row'];
            else
                $responses[0] = $response['response']['result']['Accounts']['row'];
        }    
        
        $accounts = $this->accountsBatch;
        foreach($accounts as $account){
            $find = false;
            foreach ($responses as $response){
                if ($account['idGlobal']==$this->_content($response['FL'], 'Account Number')){
                    $account['idAccount'] = $this->_content($response['FL'], 'ACCOUNTID');
                    $account['oldPhone'] = $this->_content($response['FL'], 'Phone');
                    $account['status'] = $this->isPaying($this->_content($response['FL'], 'Plan actual'), $account['plan'], $account['status']);
                    $find = true;
                    break;
                }
            }            

            if (!$find){
                $data = $this->_searchByIdGlobal($account['idGlobal']);                
                $account['idAccount'] = $data['idAccount'];
                $account['oldPhone'] = $data['oldPhone'];
                $account['status'] = $this->isPaying($data['plan'], $account['plan'], $account['status']);
            } 
        }
        
        $response = $this->_updateAccountBatch($accounts);            
        
        return $response;
    }
    
    /*
     * Funcion para cambiar el estado de la cuenta dependiendo de si viene un plan
     * pago y el plan anterior no lo era
     * @param $oldPlan Es el plan anterior
     * @param $newPlan Es el nuevo plan de la cuenta
     * @param $status Es el estado de la cuenta, si no cumple con todas las ccondiciones se 
     * deja tal como esta 
     */
    public function isPaying($oldPlan, $newPlan, $status) {
        $planPro = ($oldPlan=="Plan Pro")?true:false;
        $planPyme = ($oldPlan=="Plan Pyme")?true:false;
        $planPlus = ($oldPlan=="Plan Plus")?true:false;
        $newPlan = (($newPlan=="Plan Pro")||($newPlan=="Plan Pyme")||($newPlan=="Plan Plus"))?true:false;
        
        $status = (!$planPro&&!$planPlus&&!$planPyme&&$newPlan)?"Pagando":$status; 
        
        return $status;
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
     * Funcion para encontrar el id de aquellas cuentas a las cuales se les va a cambiar el nombre
     * @param $accountNumber Es el idGlobal de la empresa
     * @return $idAccount Es el id de la cuenta en zoho
     */
    public function _searchByIdGlobal($accountNumber) {
        $url = "https://crm.zoho.com/crm/private/json/Accounts/searchRecords";        
        $params = 'authtoken='.$this->authToken.'&scope=crmapi&newFormat=2&criteria=(Account Number:"'.$accountNumber.'")';             
        $response = json_decode($this->_curl($url, $params), true); 

        $idAccount = "";
        $oldPhone = "";
        $plan = "";
        $data = [];

        if (isset($response['response']['result']['Accounts']['row'])){
            $accounts = $response['response']['result']['Accounts']['row']; 
            if (isset($accounts[0])){
                foreach ($accounts as $account){
                    if ($this->_content($accounts['FL'], 'Account Number')==$accountNumber){
                        $idAccount = $this->_content($accounts['FL'], 'ACCOUNTID');
                        $oldPhone = $this->_content($accounts['FL'], 'Phone');
                        $plan = $this->_content($accounts['FL'], 'Plan actual');
                        break;                        
                    }                                
                }
            }else{
                if ($this->_content($accounts['FL'], 'Account Number')==$accountNumber){
                    $idAccount = $this->_content($accounts['FL'], 'ACCOUNTID');
                    $oldPhone = $this->_content($accounts['FL'], 'Phone');
                    $plan = $this->_content($accounts['FL'], 'Plan actual');
                }                                
            }                                     
        }
        $data['idAccount'] = $idAccount;
        $data['oldPhone'] = $oldPhone;
        $data['plan'] = $plan;
        
        return $data;
    }
    
    /*
     * Funcion para actualizar las cuentas por lote
     * @param $data Contiene los datos de las cuentas a ser actualizadas
     * @return $response Respuestas del api de zoho 
     */    
    public function _updateAccountBatch($companies) {   
        $i = 0;
        $response = true;
        $xmlData ='<Accounts>'; 
        foreach ($companies as $company){              
            if (!empty($company['idAccount'])){
                $xmlData .= '<row no="'.$i.'">';           
                $xmlData .= '<FL val="Account Name">'.$this->_specialChars($company['name']).'</FL>';
                $xmlData .= '<FL val="Account Number">'.$company['idGlobal'].'</FL>';
                $xmlData .= '<FL val="Account Owner">'.$company['owner'].'</FL>'; 
                $xmlData .= '<FL val="Phone">'.$this->_specialChars($company['phone']).'</FL>';
                $xmlData .= '<FL val="Correo electrónico">'.$this->_specialChars($company['email']).'</FL>';
                $xmlData .= '<FL val="Origen">'.$company['origin'].'</FL>';
                $xmlData .= '<FL val="Identificación">'.$this->_specialChars($company['identification']).'</FL>';
                $xmlData .= '<FL val="Fecha de registro">'.$company['registryDate'].'</FL>';
                $xmlData .= '<FL val="Dirección">'.$this->_specialChars($company['address']).'</FL>';
                $xmlData .= '<FL val="Ciudad">'.$this->_specialChars($company['city']).'</FL>';   
                $xmlData .= '<FL val="País">'.$this->_specialChars($company['country']).'</FL>';
                if (!empty($company['status']))
                    $xmlData .= '<FL val="Estado">'.$company['status'].'</FL>';        
                $xmlData .= '<FL val="Plan actual">'.$company['plan'].'</FL>'; 
                $xmlData .= '<FL val="Régimen">'.$this->_specialChars($company['regime']).'</FL>';        
                $xmlData .= '<FL val="Versión">'.$this->_specialChars($company['version']).'</FL>';                                
                $xmlData .= '<FL val="Fecha fin demo">'.$company['dateEnd'].'</FL>'; 
                $xmlData .= '<FL val="Código promocional">'.$this->_specialChars($company['coupon']).'</FL>';
                $xmlData .= '<FL val="Actividad o sector">'.$this->_specialChars($company['sector']).'</FL>';
                $xmlData .= '<FL val="URL primera visita">'.$this->_specialChars($company['urlFirstContact']).'</FL>';
                $xmlData .= '<FL val="Primera página">'.$this->_specialChars($company['firstPage']).'</FL>';                
                $xmlData .= '<FL val="Ultima Facturación">'.$company['dateUltimateInvoice'].'</FL>';
                $xmlData .= '<FL val="Facturas últimos 30 días">'.$company['invoicesLastMonth'].'</FL>';
                $xmlData .= '<FL val="Contactos últimos 30 días">'.$company['clientsLastMonth'].'</FL>';
                $xmlData .= '<FL val="Zendesk tickets">'.$this->_specialChars($company['tickets']).'</FL>';
                $xmlData .= '<FL val="Id">'.$company['idAccount'].'</FL>';                                    
                $xmlData .= '</row>';
                $i++;

                //Envio de mensaje de bienvenida                
                if ($company['phone']!=$company['oldPhone']){                    
                    $phoneNumbers = $this->_parsePhones($company['phone'], $company['country']);
                    if ((!empty($phoneNumbers))&&($company['applicationVersion']!="other")&&($company['applicationVersion']!="republicaDominicana")&&($company['applicationVersion']!="usa")){
                        if ($company->welcomeSent==0){
                            $this->_sendMessage($phoneNumbers, "welcome");        
                            Company::where('idGlobal', $company->idGlobal)->update(['welcomeSent'=>1]);
                        }else{
                            $this->_sendMessage($phoneNumbers, "growing");               
                        }                        
                    }                        
                }

                //Dispara el trigger en caso de cambio de telefono
                if (($company['phone']!=$company['oldPhone'])&&(!empty($company['phone']))){
                    $urlTemp = 'https://crm.zoho.com/crm/private/json/Accounts/updateRecords';
                    $paramsTemp ='authtoken='.$this->authToken.'&scope=crmapi&version=2&wfTrigger=true&id='.$company['idAccount']
                            . '&xmlData=<Accounts><row no="1"><FL val="Phone">'.$this->_specialChars($company['phone']).'</FL>'
                            . '<FL val="Plan actual">'.$company['plan'].'</FL></row></Accounts>';
                    $response = json_decode($this->_curlPost($urlTemp, $paramsTemp), true);
                }                    
            }    
        }        
        $xmlData .='</Accounts>'; 
        
        if ($xmlData!='<Accounts></Accounts>'){
            $url = "https://crm.zoho.com/crm/private/json/Accounts/updateRecords";
            $params = "authtoken=".$this->authToken."&scope=crmapi&version=4&xmlData=".$xmlData;         
            $response = json_decode($this->_curlPost($url, $params), true);               
        }
    
        return $response;
    }
    
    /*
     * Funcion para crear los contactos en Zoho en lotes
     * @param $data Contiene los datos de los contactos a ser actualizados
     * @return $response Es el resultado de la funcion
     */    
    public function _createOrEditContactZohoBatch($contacts){
        $temp = [];
        $i=0;        
        
        foreach ($contacts as $contact){                    
            $contactExist = false;                   
            for ($j=0;$j<count($temp);$j++){
                if ($temp[$j]['idGlobal']===$contact['idGlobal']){
                    $temp[$j]=$contact;
                    $contactExist = true;
                    break;
                }                    
            }            
            if (!$contactExist){
                $temp[$i] = $contact;
                $i++;
            }
        }
        
        $contacts = $temp;
        $xmlData  ='<Contacts>';   
        $i = 0;
        foreach ($contacts as $contact){
            $xmlData .= '<row no="'.$i.'">';   
            $xmlData .= '<FL val="Account Name">'.$this->_specialChars($contact['account']).'</FL>';
            $xmlData .= '<FL val="Contact Owner">'.$this->_specialChars($contact['owner']).'</FL>';         
            $xmlData .= '<FL val="Last Name">'.$this->_specialChars($contact['lastName']).'</FL>';        
            $xmlData .= '<FL val="Email">'.$this->_specialChars($contact['email']).'</FL>';
            $xmlData .= '<FL val="Creado Automático">TRUE</FL>';                    
            $xmlData .= '</row>'; 
            $i++;
        }
        $xmlData .= '</Contacts>';   
        
        $url = "https://crm.zoho.com/crm/private/json/Contacts/insertRecords";
        $params = "authtoken=".$this->authToken."&scope=crmapi&duplicateCheck=2&version=4&xmlData=".$xmlData;
        $response = json_decode($this->_curlPost($url, $params), true);
        
        return $response;
    }

    /*
     * Metodo que toma como entrada un arreglo de companies y las sincroniza en zoho, en forma de cuentas y contactos,
     * si el contacto existe en forma de lead se asocia a la cuenta y se elimina del listado de leads 
     * @parameter $request Es el request pasado por la llamada, contiene un arreglo de companies
     * @return void
     */
    public function sinchronizeCompanies(Request $request) {
        
        $body = preg_replace('/}"/', '}', 
                preg_replace('/"{/', '{', 
                preg_replace('/\\\\\\\\/', '\\', 
                preg_replace('/\\\\"/', '"', $request->getContent()))));
        
        $companies = json_decode($body, true);

        $nCompanies = 0;
        foreach ($companies as $companie) {

            if ($companie['Type'] != 'Notification') {
                $this->_confirmSubscription($companie);
            }else{
                $response = $this->_verifyZoho($companie);
                $nCompanies++;
            }    
        }        
        
        return $nCompanies;
    }    

    /*
     * Funcion que verifica si existe la direccion de una cuenta de mexico, de ser asi la obtiene, sino esta vacio
     * @parameter $email Es el email con el cual se va a buscar la info
     * @return $address Arreglo que contiene la address y la city en mexico
     */
    public function _getMexAddress($email) {                
        $payload = '{ "email" : "'.$email.'" }';
        $address = ["address"=>"", "city"=>""];
        
        $client = LambdaClient::factory([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'credentials' => [
                'key'    => $this->lambdaKey,
                'secret' => $this->lambdaSecret,
            ],            
        ]);

        $result = $client->invoke([
            'FunctionName' => 'checkCompanyExistenceInBi',
            'Payload' => $payload,
        ]); 

        $results = json_decode($result->get('Payload'), true);
        
        if (isset($results[0]['address']['address'])){
            $address = ["address"=>$results[0]['address']['address'], "city"=>$results[0]['address']['city']];
        }
        
        return $address;
    }    
    
    /*
     * Funcion que verifica si un lead de zoho se encuentra registrado en alegra, de ser asi 
     * crea/actualiza la cuenta en zoho
     * @parameter $request que contiene un json con los parametros de identification, phone y/o email
     * @return void
     */
    public function verifyNewUserZoho(Request $request) {        
        $payload = $request->input('json'); 
        
        $client = LambdaClient::factory([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'credentials' => [
                'key'    => $this->lambdaKey,
                'secret' => $this->lambdaSecret,
            ],            
        ]);

        $result = $client->invoke([
            'FunctionName' => 'checkCompanyExistenceInBi',
            'Payload' => $payload,
        ]); 

        $results = json_decode($result->get('Payload'), true);
      
        //return $results;
        $this->verifyLead = true;
        $body = [];        
        foreach ($results as $result){
            $body['Message']['company'] = $result;             
            $response = $this->_verifyZoho($body);
        }
        return $response;
    }
    
    /*
     * Valida un numero de telefono dependiendo del pais
     * @param $country Es el pais al que se va a enviar el sms
     * @param $number Es el numero de celular que se quiere validar
     * @return $validCell Devuelve true si el numero es alido y false en caso contrario
     */
    public function _validatePhoneCountry($country, $number) {
        $validCell=false;
        
        switch ($country) {
            case "VEN":
                if (preg_match('/^([4]{1})[0-9]{9}$/', $number))
                    $validCell = true;
                break;
            case "COL":
                if (preg_match('/^([3]{1})[0-9]{9}$/', $number))
                    $validCell = true;
                break;
            case "CHL":
                if (preg_match('/^([9]{1})[0-9]{8}$/', $number))
                    $validCell = true;
                break;
            case "CRI":
                if (preg_match('/^([5678]{1})[0-9]{7}$/', $number))
                    $validCell = true;
                break;
            case "PER":
                if (preg_match('/^([9]{1})[0-9]{8}$/', $number))
                    $validCell = true;
                break;
            case "PAN":
                if (preg_match('/^([6]{1})[0-9]{7}$/', $number))
                    $validCell = true;
                break;
            case "DOM":
                if (preg_match('/^[0-9]{10}$/', $number))
                    $validCell = true;
                break;
            case "HND":
                if (preg_match('/^([3789]{1})[0-9]{7}$/', $number))
                    $validCell = true;
                break;
            case "GTM":
                if (preg_match('/^[0-9]{8}$/', $number))
                    $validCell = true;
                break;
            case "ECU":
                if (preg_match('/^([9]{1})[0-9]{8}$/', $number))
                    $validCell = true;
                break;
            case "NIC":
                if (preg_match('/^([8]{1})[0-9]{7}$/', $number))
                    $validCell = true;
                break;
            case "BOL":
                if (preg_match('/^([6,7]{1})[0-9]{7}$/', $number))
                    $validCell = true;
                break;
            case "URY":
                if (preg_match('/^([9]{1})[0-9]{6,7}$/', $number))
                    $validCell = true;
                break;
            case "SLV":
                if (preg_match('/^([7]{1})[0-9]{7}$/', $number))
                    $validCell = true;
                break;
            case "PRI":
                if (preg_match('/^[0-9]{7}$/', $number))
                    $validCell = true;
                break;
            case "PRY":
                if (preg_match('/^[0-9]{8,9}$/', $number))
                    $validCell = true;
                break;

            default:
                if (preg_match('/^[0-9]+$/', $number))
                    $validCell = true;
                break;
        }
        return $validCell;
    }
    
    /*
     * Funcion para parsear lo qu viene en el campo telefono y converirlos en numeros de 
     * telefonos manejables por el sistema
     * @params $numbers Es un string que puede contener uno o mas numeros de telefono
     * @params $country Es un string que contiene las iniciales del pais
     * @return $phoneNumbers Es una arreglo que contiene los numeros de telefono manejables
     * por el sistema 
     */
    public function _parsePhones($numbers, $country) {
        $countries = json_decode(file_get_contents('js/countries.json'), true);
        $country = (empty($country))?"COL":$country;
        $key = array_search($country, array_column($countries, 'alpha3'));        
        $code = (isset($countries[$key]['phoneCode']))?$countries[$key]['phoneCode']:"";
        $valuesForReplace = array("-", ".", "(", ")", " ", "_", "+");
        $numbers = str_replace($valuesForReplace, "", $numbers);
        $delimeters = [",", "/", ";"];
        $numbers = str_replace($delimeters, ",", $numbers);
        $numbers = explode(",", $numbers);
        
        $numbersForSMS = [];
        foreach ($numbers as $number){
            if (substr($number, 0, 2)=="00")
                $number = substr($number, 2);                
            if (substr($number, 0, 1)=="0")
                $number = substr($number, 1);                
            if (substr($number, 0, 1)==$code)
                $number = substr($number, 1);                
            if (substr($number, 0, 2)==$code)
                $number = substr($number, 2);                
            if (substr($number, 0, 3)==$code)
                $number = substr($number, 3);

            $validCell = $this->_validatePhoneCountry($country, $number);
            
            if (!(empty($number))&&($validCell))
                $numbersForSMS[] = $code.$number;
        }
        
        return $numbersForSMS;
    }
    
    /*
     * Funcion para el envio de sms a un arreglo de telefonos
     * @param $numbers Es el arreglo de los numeros de telefonos a los cuales se les va a enviar el mensaje
     * @param $message Es el contenido del mensaje
     * @return $response Es la respuesta de el envio de los mensajes
     */
    public function _sendMessage($numbers, $message) {
        switch ($message) {
            case "welcome":
                $message = $this->welcomeMsg;
                break;
            case "growing":
                $message = $this->growingMsg;
                break;

            default:
                break;
        }
                
	$apiKey = urlencode($this->apiTextSMS);	
	$sender = urlencode('Alegra');
	$message = rawurlencode($message);
 
	$numbers = implode(',', $numbers);
 
	$data = array(  'apikey' => $apiKey, 
                        'numbers' => $numbers, 
                        "sender" => $sender, 
                        "message" => $message,
                        "receipt_url" => "52.204.245.33/api/leads/handle-receipts");
 
	$ch = curl_init('https://api.txtlocal.com/send/');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);	        

        file_put_contents('log/sents', $response."\n", FILE_APPEND);                
        
        return $response;
    }
    
    /*
     * Funcion a ser llamada para el envio de un sms a los numeros de
     * telefonos en el parametro phones
     * @params $numbers Son los numeros de telefono en forma de string 
     * a los cuales se va a enviar el mensaje
     * @params $country Es el pais de los numeros de telefono a los cuales
     * se va a enviar el mensaje
     * @params $message Es el mensaje que va a ser enviado
     * @return $response Es la respuesta de la llamada a la funcion  
     */
    public function sendSms(Request $request) {
        $request = json_decode($request->input('json'), true);
        $response = "dont send";
        $numbers = (isset($request['numbers']))?$request['numbers']:false;
        $country = (isset($request['country']))?$request['country']:"";
        $message = (isset($request['message']))?$request['message']:"";
        
        if (($country!='DOM')&&($country!='USA')){
            $phoneNumbers = $this->_parsePhones($numbers, $country);
            $response = $this->_sendMessage($phoneNumbers, $message);
        }
        return $response;
    }
    
    public function handleReceipts(Request $request) {
        $result = $request->all(); 
                
        file_put_contents('js/zoho.json', json_encode($result), FILE_APPEND);        
        return $result;
    }
    
    /*
     * Funcion que inserta en una tabla los datos de otros contactos creados por los chicos de las llamadas
     */
    public function otherContact(Request $request){
        $dataContact['idGlobal'] = $request->input('id');
        $dataContact['nameCompany'] = $request->input('nameCompany');
        $dataContact['nameContact'] = $request->input('nameContact');
        $dataContact['phone'] = $request->input('phone');
        $dataContact['mobile'] = $request->input('mobile');
        $dataContact['otherPhone'] = $request->input('otherPhone');
        $idGlobal = $dataContact['idGlobal'];        
        $response = OtherContact::updateOrCreate(['idGlobal' => $idGlobal], $dataContact);       
        return $response;
    }
    
    /*
     * Funcion que inserta en una tabla los datos de las empresas para el reporte
     */    
    public function reportCompanies(Request $request) {
        $dataCompany['idGlobal'] = $request->input('id');
        $dataCompany['name'] = $request->input('name');        
        $dataCompany['idZoho'] = $request->input('idZoho');        
        $dataCompany['status'] = $request->input('status');
        $dataCompany['country'] = $request->input('country');        
        $dataCompany['origin'] = $request->input('origin');
        $dataCompany['plan'] = $request->input('plan');        
        $dataCompany['owner'] = $request->input('owner');
        $idGlobal = $dataCompany['idGlobal'];        

        $response = ReportCompany::updateOrCreate(['idGlobal' => $idGlobal], $dataCompany);        
        return $response;
    }
    
    /*
     * Funcion que obtiene los otros usuarios de una cuenta cuando son actualizados
     * @params $request Es un sns que contiene la informacion relevante de la cuenta y el nuevo user
     * @response $void
     */
    public function updateUsers(Request $request) {
        $body = preg_replace('/}"/', '}', 
                preg_replace('/"{/', '{', 
                preg_replace('/\\\\\\\\/', '\\', 
                preg_replace('/\\\\"/', '"', 
                preg_replace('/\\\\ud83cdfe0/', '', $request->getContent())))));        

        if ($body['Type'] != 'Notification') {
            $this->_confirmSubscription($body);
        }else{
            file_put_contents('log/zendesk', $body."\n", FILE_APPEND);                                
        }                
    }
    
    /*
     * Funcion para realizar/implementar pruebas del api y/o funciones para correrlas una sola vez
     * 
     */
    public function test(Request $request) {        
        $start = $request->input('start');
        $limit = $request->input('limit');   
        $search = $request->input('search'); 
        
        /*
         * ELIMINO LOS DUPLICADOS, SOLO DEJO EN LA TABLA EL PRIMERO DE CADA UNO
         */
        $companies = Company::where('plan', $search)
                        ->orderBy('idGlobal', 'asc')
                        ->offset($start)
                        ->limit($limit)
                        ->get();                        
        print(count($companies));
        $actualizar = [];
        foreach ($companies as $company){
            $exist = false;            
            $found = false;

            $panel = Panel::where('idGlobal', $company->idGlobal)->get();
            $planPanel = "";
            
            if ((isset($panel[0]))){
                switch($panel[0]->plan){
                    case 'free': $planPanel = 'Plan Gratis'; break;
                    case 'pyme': $planPanel = 'Plan Pyme'; break;
                    case 'pro': $planPanel = 'Plan Pro'; break;
                    case 'plus': $planPanel = 'Plan Plus'; break;
                    case 'readonly': $planPanel = 'Plan Consulta'; break;
                    case 'fundaciones': $planPanel = 'Plan Fundaciones Cortesía'; break;                    
                    case 'internal': $planPanel = 'Plan Tecnico'; break;                    
                    case 'education': $planPanel = 'Plan Educación'; break;                    
                    case 'acrecer': $planPanel = 'Plan Acrecer'; break;                    
                    case 'demo': $planPanel = 'Plan Demo'; break;                    
                }

                $panel = $panel[0];
                
                //print($planPanel." ".$company->plan."  ");
                if($planPanel!=$company->plan){
                    $actualizar[]=$company->idGlobal;

                    Company::where('id', $company->id)->update(['synchronized'=>0, 
                                                                'plan'=>$planPanel,
                                                                'status'=>""]);
                }                            
            }
        }
        /*
         * FIN ELIMINO LOS DUPLICADOS, SOLO DEJO EN LA TABLA EL PRIMERO DE CADA UNO
         */
        die(print_r($actualizar));
    }
}
