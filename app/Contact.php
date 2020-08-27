<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    //
    /**
     * Los atributos que son asignables
     *
     * @var array
     */
    
    protected $fillable = [
        'idGlobal', 'name', 'lastName', 'account','owner', 'email', 'synchronized'
    ];    
}
