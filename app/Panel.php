<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Panel extends Model
{
    //
    /**
     * Los atributos que son asignables
     *
     * @var array
     */
    protected $fillable = [
        'idGlobal', 'name', 'identification', 'phone', 'email', 'origin', 'registryDate', 'address', 'city',
        'country', 'plan', 'dateEnd', 'regime', 'version', 'coupon', 'sector'
    ];    
}
