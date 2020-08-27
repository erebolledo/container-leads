<!doctype html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width"> 
    <link href="favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">    
    <title>Alegra - Leads</title>
    <link href="vendor/react/node_modules/fixed-data-table/dist/fixed-data-table.css" rel="stylesheet">
    <link href="css/leads-table.css" rel="stylesheet" type="text/css"/>
  </head>
  <body style="font-family:arial;font-size:12px; background:#e1e1e1">
      <style>
            #react-paginate ul {
                display: inline-block;
                padding-left: 15px;
                padding-right: 15px;
            }

            #react-paginate li {
                display: inline-block;
            }

            #react-paginate .break a {
                cursor: default;
            }
      </style>
    <script>
        var prueba = [
            {"name":"Erka Rebolledo","phonePrimary":"04166424326","email":"erkarebolledo@gmail.com"}, 
            {"name":"Jessica Perez","phonePrimary":"21454545454","email":""},
            {"name":"Samantha Valentina","phonePrimary":"02126823741","email":"sami@hotmail.com"}, 
            {"name":"karen suarez","phonePrimary":"","email":"ksuarez@gmail.com"}            
        ]                
    </script>

        <div align="center" id="leadsTable"></div>
        <script src="vendor/react/bundle.js"></script>
        
  </body>
</html>