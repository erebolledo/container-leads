@extends('layouts.app')

@section('title', 'Page Title')

@section('content')
<div style="width:80%; margin: auto">
<table class="table table-bordered table-striped"> 
    <thead class="thead-inverse"> 
        <tr> 
            <th>id</th> 
            <th>Nombre</th> 
            <th>Teléfono primario</th> 
            <th>Teléfono secundario</th> 
            <th>Celular</th> 
            <th>Correo electrónico</th> 
            <th>Compañía</th> 
            <th>Origen</th> 
            <th>Industria</th> 
            <th>Pais</th> 
        </tr> 
    </thead> 
    <tbody> 
@foreach($leads as $lead)
        <tr> 
            <th scope="row">{{ $lead->id }}</th> 
            <td>{{ $lead->name }}</td> 
            <td>{{ $lead->phonePrimary }}</td> 
            <td>{{ $lead->phoneSecondary }}</td> 
            <td>{{ $lead->mobile }}</td> 
            <td>{{ $lead->email }}</td> 
            <td>{{ $lead->company }}</td>             
            <td>{{ $lead->source }}</td> 
            <td>{{ $lead->industry }}</td>                         
            <td>{{ $lead->country }}</td>                         
        </tr>     
@endforeach
    </tbody>
</table>
{{ $leads->links() }}
</div>>

@endsection