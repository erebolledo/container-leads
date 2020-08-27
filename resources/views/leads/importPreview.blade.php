<html>
    <head>
        <style>
            .header {
                border: 1px solid black;
                padding: 10px;
                margin: 15px;
                cursor: pointer;
            }
            
            .body {
                padding: 20px;
            }
            
            table {
                border-collapse: collapse;
            }

            table, th, td {
                border: 1px solid black;
            }
        </style>
        <script>
            
            function validateEmail(email) 
            {
                var re = /\S+@\S+\.\S+/;
                return re.test(email);
            }            
            
            function validate(){
                var nColumns = <?=count($leads[0])?>;
                var entryLeads = <?= json_encode($leads)?>;
                var forImportLeads = [];
                var selectColumns = [];
                
                for(var i=0;i<nColumns;i++){
                    columns = document.getElementById("select"+i);                    
                    selectColumns[i]= columns.value;
                }
                
                var existName = false;
                for (var i=0;i<nColumns; i++){                    
                    var index = selectColumns[i];                    
                    var count = 0;
                    if (index==='name')
                        existName=true;
                    for (var j=0;j<nColumns;j++){
                        if ((index===selectColumns[j])&&(index!=0)){
                            count++;
                            if (count>1){
                                alert('No puedes asignar el mismo tipo a 2 columnas');
                                return false;
                            }
                        }
                    }
                }
                
                if (!existName){
                    alert('Debes seleccionar una columna con el valor requerido "Nombre"');
                    return false;
                }
                
                var array = Object.values(entryLeads[0]);
                console.log('array');
                console.log(array);
                
                var index;
                var auxArray; 
                var auxLead = {};                
                for (i=0;i<entryLeads.length;i++){                    
                    auxArray = Object.values(entryLeads[i]);
                    auxLead = {};
                    for (var j=0;j<selectColumns.length;j++){
                        var field = selectColumns[j];
                        if (field!=='0'){                            
                            auxLead[field]=auxArray[j];
                            if ((field==='name')&&(auxLead[field]===null)){
                                alert('En la columna "Nombre" del archivo excel no pueden haber campos vacios');
                                return false;
                            }
                            
                            if ((field==='email')&&(auxLead[field]!==null)){
                                if (!validateEmail(auxLead[field])){
                                    alert('Los datos presentes en el campo "Correo" deben ser direcciones de correos validas');
                                    return false;
                                }
                            }
                        }
                    }
                    forImportLeads.push(auxLead);
                    index++;                    
                }
                console.log(forImportLeads);
                
                var data = new FormData();
                data.append( "leads", JSON.stringify(forImportLeads) );

                fetch("http://54.234.236.252/leads/add-from-excel-import",
                {
                    method: "POST",
                    body: data
                }).then(function(response) {
                    return response.json()
                }).then((response) => {
                    if (response.status!=="ok")
                        alert('Ocurrio un problema importando los datos, favor contacte a su administrador');
                    console.log('Esta es la respuesta');
                    alert(response.data);
                    console.log(response)
                }).catch(function(err) { 
                        alert('Ocurrio un problema importando los datos, favor contacte a su administrador');
                });
                
                return false;
            }
        </script>
    </head>    
   <body>
       <form method="POST" onsubmit="return validate();">   
           <input type="hidden" id="forImportLeads">
        <table>
            <tr>
                <?php for ($i=0;$i<count($leads[0]);$i++):?>
                <th>
                    <select id="select<?=$i?>" style="width:200px">
                    <?php foreach($fields as $key => $value): ?>
                        <option value="<?php echo $key ?>"><?php echo $value ?></option>                        
                    <?php endforeach;?>
                    </select>                               
                </th>
                <?php endfor;?>
            </tr>
            <?php $j=0?>
           <?php foreach ($leads as $lead):?>
                <tr>
                    <?php $j++ ?>
                    <?php if($j>4) break?>    
                     <?php foreach($lead as $key => $column):?>
                         <td><?php echo $column?></td>
                     <?php endforeach; ?>
                </tr>
           <?php endforeach;?>
        </table>
        <input type="submit" value="Importar">
    </form>        
   </body>
</html>
