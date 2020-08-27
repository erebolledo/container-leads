<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <!--<meta http-equiv="refresh" content="10">-->
        
        <style>
            .inputFile {
                border: 1px solid black;
                padding: 10px;
                margin: 15px;
                cursor: pointer;
            }
            
            .body {
                padding: 20px;
            }
        </style>
    </head>
    <body class="body">
        <div class="container">        
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th></th>
                        <th>Número</th>
                        <th>Estado</th>
                        <th>Fecha de Recibo</th>
                        <th>Hora de Recibo</th>
                    </tr>
                </thead>
<?php $i=1?>
<?php foreach ($data as $receipt){?>
<?php 
    switch ($receipt['status']){
        case "?": $status = "Desconocido"; break;
        case "D": $status = "Entregado"; break;
        case "U": $status = "Perdido"; break;
        case "I": $status = "Número Inválido"; break;
    }
 
    $timestamp = strtotime($receipt['datetime']);
    $date = date('d/m/Y', $timestamp);
    $time = date('H:i:s', $timestamp);
?>                
                <tbody>            
                <tr>
                    <td><?=$i?></td>
                    <td><?=$receipt['number']?></td>
                    <td><?=$status?></td>
                    <td><?=$date?></td>
                    <td><?=$time?></td>
                </tr>    
                </tbody>
<?php $i++;?>                
<?php }?>                
            </table>
        </div>    
   </body>
</html>
