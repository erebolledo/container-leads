<html>
    <head>
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
      <form method="post" action="import-preview" accept-charset="UTF-8" enctype="multipart/form-data">          
            Archivo de candidatos *:&nbsp;
            <span class="inputFile">
                <input name="excel" type="file" accept=".xls,.xlsx,.csv">
            </span>
            <input type="submit" value="Importar">
      </form>      
   </body>
</html>
