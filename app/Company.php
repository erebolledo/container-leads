<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    //
    /**
     * Los atributos que son asignables
     *
     * @var array
     */
    protected $fillable = [
        'idGlobal', 'name','identification','phone', 'email', 'origin', 'owner',
        'registryDate', 'address', 'city', 'country', 'status', 'plan', 'dateEnd',
        'regime', 'version', 'coupon', 'synchronized', 'sector', 'dateUltimateInvoice', 
        'urlFirstContact', 'firstPage', 'invoicesLastMonth', 'clientsLastMonth', 'tickets', 'welcomeSent'
    ];    
}
