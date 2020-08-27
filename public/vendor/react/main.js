import React from 'react';
import ReactDOM from 'react-dom';
import {Table, Column, Cell} from 'fixed-data-table';

console.log(prueba);
class LeadCell extends React.Component { 
    render(){    
        const{rowIndex, col, data} = this.props;    
        return (<Cell>{data[rowIndex][col]}</Cell>);
    }
}

class HeaderCell extends React.Component { 
    render(){    
        const{name, filter, width} = this.props;    
        let inputWidth = width -100;
        return (<Cell>
            {name} 
            {
                <div className=".filter">    
                    <input size="15" id="filterName" placeholder="Filtrar"/>
                </div>
            }
        </Cell>);
    }
}

class LeadsTable extends React.Component {
 
    constructor(props) {
                
        super(props);
    
        this.state = {
            rows: [],
            filteredDataList: [],            
            limit: 10,
            page: 0,
            prevDisabled: true,
            nextDisabled: true,
            listFilters: {"name":"", "phonePrimary":""}, 
            search:'', 
            filters:''
        }
    }
    
    componentWillMount() {
        let limit = this.state.limit  +1;
        console.log(limit);
        fetch('http://54.234.236.252/api/leads/?start=0&limit='+limit, {
            method: 'get',
            headers: new Headers({
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Headers': 'Content-Type, Accept, Authorization, X-Requested-With',                
                'Access-Control-Request-Method': 'get',
                'Content-Type': 'application/json'
            })            
        }).then(function(response) {
            return response.json()
        }).then((leads) => {        
            if (leads.length===0){
                alert('No existen candidatos registrados');
            }else{    
                this.toEnablePager(0, leads.length);
                if (leads.length>this.state.limit) leads.pop();
                this.setState({filteredDataList:leads})                        
            }
        }).catch(function(err) { 
                // Error :(
        });                
    }      
  
    /*
     * Esta funcion se encarga de filtrar los datos, filtrando y actualizando los 
     * datos, establece el valor de filteredDataList
     */
    onFilterChange(column) {
        let value = document.getElementById(column).value;
        let stringQuery = '';
        this.state.listFilters[column] = value;
        console.log(this.state.listFilters);
        for (var key in this.state.listFilters){
            if (this.state.listFilters[key]!='')
                stringQuery += '&'+key+'='+this.state.listFilters[key];                
        }
        this.state.filters = stringQuery;
        this.state.page = 0;
        
        this.loadData('');
    }
    
    /*
     * Esta funcion se encarga de llamar al conjunto anterior de resultados paginados
     */
    previousPage(){
        var prev = parseInt(this.state.page)-1;                               
        this.loadData(prev, 'prev', ''); 
    }

    /*
     * Esta funcion se encarga de llamar a la pagina indicada en el id
     */
    callPage(id){
        alert('pnche el numero '+ id);
    }
        
    /*
     * Esta funcion se encarga de llamar al conjunto proximo de resultados paginados
     */
    nextPage(){
        console.log('estamos en la pagina: ');
        console.log(this.state.page);
        console.log('pagina siguiente: ');
        console.log(parseInt(this.state.page)+1); 
        var next = parseInt(this.state.page)+1;                                        
        this.loadData(next, 'next', '');
    }
    
    /*
     * Funcion para cargar los datos en la tabla
     */
    loadData(direction){
        let page = 0;   
    
        if (direction === 'prev')
            page = this.state.page-1;
        if (direction === 'next')
            page = this.state.page+1;
        
        let start = page*this.state.limit;
        start = 'start='+start+'&';                
        let limit = this.state.limit  +1;
        limit = 'limit='+limit;
        let search = this.state.search;
        let filters = this.state.filters;
        console.log('url');
        console.log(start+limit+search+filters);
        
        fetch('http://54.234.236.252/api/leads?'+start+limit+search+filters, {
            method: 'get',
            headers: new Headers({
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Headers': 'Cache-Control, Pragma, Origin, Authorization, Content-Type, X-Requested-With',                
                'Access-Control-Request-Method': 'get',
                'Content-Type': 'application/json'
            })            
        }).then(function(response) {
            return response.json();
        }).then((leads) => {
            if (leads.length===0){
                alert('No existen resultados para la busqueda');
            }else{    
                this.toEnablePager(page, leads.length);                
                if (leads.length>this.state.limit) leads.pop();                
                this.setState({filteredDataList:leads, page:page})                                        
            }
        }).catch(function(err) { 
                // Error :(
        });
    }        
    
    /*
     * Funcion para habililar los botones del paginador
     */
    toEnablePager(page, numberLeads){
        console.log('pager');
    
        this.state.prevDisabled=true;
        this.state.nextDisabled=true;
    
        if (page>0)
            this.state.prevDisabled=false;
        if (numberLeads>this.state.limit)
            this.state.nextDisabled=false;
    }
    
    /*generatePager2(leadsNumber){
        var limit = this.state.limit;
        var pagesNumber = leadsNumber/limit;
        pagesNumber = ((pagesNumber%limit)===0)?pagesNumber:pagesNumber+1;
        alert(leadsNumber+" "+pagesNumber);
        
        var buttons = [];
        for (var idPage=1;idPage<6;idPage++){
            buttons.push(
            <li key={idPage} id={idPage}>
            <button id={idPage} onClick={event => this.callPage(event.target.id)}>
                {idPage}
            </button>    
            </li>);
        }
        return (
            <div className="pager">{buttons}</div>
        );
    } */
        
    /*
     * Funcion para realizar la busqueda avanzada en la tabla
     */
    search() {        
        let searchInput = document.getElementById('searchInput').value;
        this.state.page=0;
        if (searchInput != '') 
            this.state.search='&search='+searchInput;
        else
            this.state.search='';
        this.loadData('');        
    }
    
    /*
     * Funcion para realizar la busqueda avanzada en la tabla
     */
    clear() {                
        this.state.page=0;
        this.state.filters='';
        this.state.search='';
        document.getElementById('searchInput').value = '';
        for (var key in this.state.listFilters){
            document.getElementById(key).value='';
            this.state.listFilters[key]='';
            this.state.filters='';
        }        
        this.loadData('');        
    }    
    
    /*
     * Cabecera de las columnas
     */
    headerCell(label, id){            
        return <div>
           <span>{label}</span>
             <div>
               <input style={{width:80+'%'}} id={id} 
                   onChange={() => this.onFilterChange(id)}/>
             </div>
         </div>;        
    }
    
    render() {
        console.log('aca');
        if (this.state.filteredDataList.length>0){
            return (
                <div style={{width:1050}}>  
                    <div className="search">
                        <input width="20" id="searchInput" placeholder="Buscar texto"/>
                        <button id="search" title="Busqueda avanzada" onClick={()=>this.search()}>Buscar</button>
                        <button id="clear" title="Limpiar la busqueda y los filtros"
                            onClick={()=>this.clear()}>Limpiar</button>
                    </div>
                
                    <Table
                        height={(this.state.limit*30)+52}        
                        width={1050}
                        rowsCount={this.state.filteredDataList.length}
                        rowHeight={30}
                        headerHeight={50}                        
                        {...this.props}>

                        <Column
                            header={ ()=>this.headerCell('Nombre', 'name')}
                            cell={ <LeadCell col='name' data={this.state.filteredDataList} /> }
                            width={200}
                        />                                                
                        <Column
                            header={ ()=>this.headerCell('Telefono 1', 'phonePrimary')}
                            cell={ <LeadCell col='phonePrimary' data={this.state.filteredDataList} /> }
                            width={100}
                        />
                        <Column
                            header={ ()=>this.headerCell('Telefono 2', 'phoneSecondary')}
                            cell={ <LeadCell col='phoneSecondary' data={this.state.filteredDataList} /> }
                            width={100}
                        />
                        <Column
                            header={ ()=>this.headerCell('Correo', 'email')}
                            cell={ <LeadCell col='email' data={this.state.filteredDataList} /> }
                            width={150}
                        />
                        <Column
                            header={ ()=>this.headerCell('Celular', 'mobile')}
                            cell={ <LeadCell col='mobile' data={this.state.filteredDataList} /> }
                            width={100}
                        />
                        <Column
                            header={ ()=>this.headerCell('Industria', 'industry')}
                            cell={ <LeadCell col='industry' data={this.state.filteredDataList} /> }
                            width={100}
                        />
                        <Column
                            header={ ()=>this.headerCell('Empresa', 'company')}
                            cell={ <LeadCell col='company' data={this.state.filteredDataList} /> }
                            width={100}
                        />
                        <Column
                            header={ ()=>this.headerCell('Pais', 'country')}
                            cell={ <LeadCell col='country' data={this.state.filteredDataList} /> }
                            width={100}
                        />
                        <Column
                            header={ ()=>this.headerCell('Origen', 'source')}
                            cell={ <LeadCell col='source' data={this.state.filteredDataList} /> }
                            width={100}
                        />
                    </Table>

                    <div className="pager">                    
                        <span>
                            <button disabled={this.state.prevDisabled} className="previousButton"
                                onClick={()=>this.loadData('prev')}>
                                &lt; Anterior
                            </button>    
                        </span>
                        <span>
                            <button disabled={this.state.nextDisabled} className="nextButton"
                                onClick={()=>this.loadData('next')}>
                                Siguiente &gt; 
                            </button>                        
                        </span>
                    </div>
                </div>
            );
        }else{
            return <img src="cargando.gif"/>
        }
    }
}

ReactDOM.render(<LeadsTable/>,document.getElementById('leadsTable'));