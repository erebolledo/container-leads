<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OtherContact extends Model
{
    //
    /**
     * Los atributos que son asignables
     *
     * @var array
     */
    
    protected $fillable = [
        'idGlobal', 'nameCompany', 'nameContact', 'phone','mobile', 'otherPhone'
    ];    
}
