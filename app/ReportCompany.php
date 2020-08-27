<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportCompany extends Model
{
    //
    /**
     * Los atributos que son asignables
     *
     * @var array
     */
    protected $fillable = [
        'idGlobal', 'idZoho','name','status','origin', 'owner', 'plan', 'country'
    ];    
}
