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
<!-- Start of alegrahelp Zendesk Widget script -->
<script>
    /*<![CDATA[*/
        window.zEmbed||function(e,t){var n,o,d,i,s,a=[],r=document.createElement("iframe");
        window.zEmbed=function(){a.push(arguments)},
            window.zE=window.zE||window.zEmbed,r.src="javascript:false",r.title="",
            r.role="presentation",(r.frameElement||r).style.cssText="display: none",
            d=document.getElementsByTagName("script"),d=d[d.length-1],d.parentNode.insertBefore(r,d),
            i=r.contentWindow,s=i.document;try{o=s}catch(e){n=document.domain,
            r.src='javascript:var d=document.open();d.domain="'+n+'";void(0);',o=s}
            o.open()._l=function(){var e=this.createElement("script");n&&(this.domain=n),
            e.id="js-iframe-async",e.src="https://assets.zendesk.com/embeddable_framework/main.js",
            this.t=+new Date,this.zendeskHost="alegrahelp.zendesk.com",this.zEQueue=a,this.body.appendChild(e)},
            o.write('<body onload="document._l();">'),o.close()}();
/*]]>*/
</script>
<!-- End of alegrahelp Zendesk Widget script -->        
    </head>
    <body class="body">
        <div class="container">        
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Contacto</th>
                        <th>Empresa</th>
                        <th>Telefóno</th>
                        <th>Celular</th>
                        <th>Otro Telefóno</th>
                    </tr>
                </thead>
<?php foreach ($data as $row){
    $oContact = json_decode($row, true);?>
                <tbody>            
                <tr>
                    <td><?=$oContact['nameContact']?></td>
                    <td><?=$oContact['nameCompany']?></td>
                    <td><?=$oContact['phone']?></td>
                    <td><?=$oContact['mobile']?></td>
                    <td><?=$oContact['otherPhone']?></td>
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
