<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <meta http-equiv="refresh" content="10">
        
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
                        <th>ID Global</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Origen</th>
                        <th>País</th>
                        <th>Plan actual</th>
                        <th>Propietario</th>
                    </tr>
                </thead>
<?php foreach ($data as $row){
    $Company = json_decode($row, true);?>
                <tbody>            
                <tr>
                    <td><?=$Company['idGlobal']?></td>
                    <td><?=$Company['name']?></td>
                    <td><?=$Company['status']?></td>
                    <td><?=$Company['origin']?></td>
                    <td><?=$Company['country']?></td>
                    <td><?=$Company['plan']?></td>
                    <td><?=$Company['owner']?></td>                    
                </tr>    
                </tbody>
<?php }?>                
            </table>
        </div>    
      <!--<form method="post" action="import-preview" accept-charset="UTF-8" enctype="multipart/form-data">          
            Archivo de candidatos *:&nbsp;
            <span class="inputFile">
                <input name="excel" type="file" accept=".xls,.xlsx,.csv">
            </span>
            <input type="submit" value="Importar">
      </form>-->
   </body>
</html>
