<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\DB;
use App\Lead;
use App\Company;
use App\Contact;
use App\OtherContact;
use App\ReportCompany;
use Maatwebsite\Excel\Facades\Excel;
use App\LeadValueBinder;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Aws\Lambda\LambdaClient;

class ContactsController extends Controller
{
    public $user = "alegr@";
    public $pass = "alegra2020";
    
    public function listOtherContacts($id, $key) {        
        if ($key!=md5($this->user).".".md5($this->pass))
            return "No autorizado"; 
        
        $otherContacts = OtherContact::all();
        
        return view('zoho.otherContacts', ['data'=>$otherContacts]);
    }
    
    public function reportCompanies($id, $key){
        if ($key!=md5($this->user).".".md5($this->pass))
            return "No autorizado"; 
        
        $reportCompanies = ReportCompany::all();
        
        return view('zoho.reportCompanies', ['data'=>$reportCompanies]);
    }
}
