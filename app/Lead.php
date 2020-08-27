<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Lead extends Model
{
    /**
     * Los atributos que son asignables
     *
     * @var array
     */
    protected $fillable = [
        'name', 'phonePrimary','phoneSecondary','mobile', 'email', 'company', 'source', 
        'industry', 'country', 'comment'
    ];

    /**
     * Método que retorna la informacion del lead en un arreglo, para evitar campos vacios
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'phonePrimary' => $this->phonePrimary,
            'phoneSecondary' => $this->phoneSecondary,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'company' => $this->company,
            'source' => $this->source,
            'industry' => $this->industry,
            'country' => $this->country,
            'comment' => $this->comment
        );
    }            
}
