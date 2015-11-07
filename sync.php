<?php

/*
 * @Author : Marco Longo
 * @Email : marchrist85@gmail.com
 * @Country : Italy
 * @Date : 19 05 2015
 * @License : GPL V2
 * @Contact : https://www.linkedin.com/profile/view?id=112839004
 *
 *
 */


/*
https://www.odoo.com/documentation/8.0/api_integration.html#connection
*/

include_once('openerp.class.php');

$config = include('config.php');
$listver; 

class product_list_item {
	var price_round = 0;
	var create_uid = 1;
	var price_min_margin = 0;
	var price_disount = 0;
	var name = '';
	var sequence = '5';
	var price_max_margin = 0;
	var product_id;
	var base = 2;
	var base_pricelist_id;
	var price_version_id;
	var company_id;
	var price_surchange = 0;
	var min_quantity = 0;
	var price_discount = 1.0;
	
}



$conn = new mysqli($config['dbhost'], $config['dbuser'], $config['dbpassword'], $config['dbname'] );
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "\nConnected successfully\n\n";
file_put_contents('query.txt', '');


$rpc = new OpenERP();
$x = $rpc->login("admin", "m1a1u1c1-", "mauceri", $config['odoourl'] . "xmlrpc/2/");



#sync_bank($conn,$rpc);
////sync_agent($conn,$rpc);
#sync_clienti_vat($conn,$rpc);
#sync_clienti_codfis($conn,$rpc);
#sync_clienti_destcons($conn,$rpc);
#sync_fornitori($conn,$rpc);
#sync_gruppi($conn,$rpc);
//crea_listino($conn,$rpc);
#sync_articoli($conn,$rpc);
sync_fatture($conn,$rpc);
#sync_insoluti($conn,$rpc);
	


die();

function sync_agent(&$conn,&$rpc){

	{
	$sql="SELECT * FROM odoo.agenti";
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	while($row = mysqli_fetch_object($ids)){
		if ($row->NOMINATIVO=="") continue;
		$errstring = array("/", "-", " ");
		$row->telefono=str_replace($errstring,'',$row->telefono);
	 	$row->telefono= ctype_digit($row->telefono)? $row->telefono:"";
	 	$fisso=strcmp(substr($row->telefono, 0),"0")?$row->telefono:"";
	 	$cellulare=strcmp(substr($row->telefono, 0),"3")?$row->telefono:"";	 	
		
		$user = array(
	  	'name'=> preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nominativo)
	  	, 'phone' => $fisso
	 	, 'mobile' => $cellulare
		, 'comment' => preg_replace('/[^A-Za-z0-9\-\s]/', '', $row->indirizzo . "\n" . $row->località ."\n" . $row->provincia ."\n" . $row->provincia ."\n" . $row->provincia ."\n" . $row->cod_part_iv )
	 	);
	 	$userid = $rpc->create( $user, "res.partner");
		$agent= array (
			'active' => true
			,'company_id' => true
			, 'partner_id' => $userid
			
			
		);
			$agentid = $rpc->write(array($resuser->uid_odoo_id),array('user_id'=>$value),"res.user" );
		}		
		}
	}



function sync_bank(&$conn,&$rpc){

$sql="select 	 BANCAAPPO as banca, 
	 DIPENDENZA as bancadip, 
	 CABI as cabi,
	 CAB as cab
from clienti where BANCAAPPO != \"\"";
$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
echo "Carico Banche clienti\n";
while($row = mysqli_fetch_object($ids))
	{
	$bank=array(
	  	'name'=> preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->banca)
	 	, 'cab' =>  substr(preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->cab),0,5)
	 	, 'abi' =>  substr(preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->cabi),0,5)
	 	, 'street2' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->bancadip)
	 	);
	$bankid = $rpc->create( $bank, "res.bank");
	 	if ($bankid== -1){
	 		var_dump($bank);
	 		die();
	
	}
}
}

function sync_clienti_vat(&$conn,&$rpc){
	//TODO importare codice fiscale 
echo "Carico clienti con vat\n";

	$sql="SELECT RAG_SOC as 'name',
	 clienti.PROVINCIA as 'provincia',
	 clienti.INDIRIZZO as 'indirizzo',
	 clienti.LOCALITA as 'localita',  
	 COD_PARTIV as 'partita_iva',
	 BANCAAPPO as banca, 
	 DIPENDENZA as bancadip, 
	 clienti.COD_PAG as mod_pag,
	 CABI as abi,
	 CAB as cab,
	 NUM_CC as conto,
	 clienti.TELEFONO as 'telefono',
	 NOTE1 as 'note1',
	 NOTE2 as 'note2',
	 clinote.NOTE as 'detnote',
    agenti.NOMINATIVO as agente
     FROM clienti left JOIN clinote ON clienti.SSSS = clinote.SSSS LEFT JOIN agenti ON clienti.COD_AGE = agenti.id WHERE COD_PARTIV regexp '^[0-9]+' GROUP BY partita_iva" ;
$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "$sql\n");

$errn=0;
while($row = mysqli_fetch_object($ids))
	{
		$conto=33;
		//var_dump($row);
		//echo preg_replace('/[^(\x20-\x7F)]*/','', $row->address);
		
	 	//echo $rpc->create( array('name'=>$row->surname), "res.partner");

	 	$sql="SELECT odoo_id, type FROM odoo.cp_id_odoo WHERE id = ". $row->mod_pag .";";
	 	$result= mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	 	if ($result->num_rows==0) { echo 'questa modalità di pagamento non esiste :'. $row->mod_pag."\n"; };
	 	$termpag = mysqli_fetch_object($result);
		/* if($termpag->type === 'R')
		 		$conto=237;
		 elseif ($termpag->type === 'D')
			$conto=238;
		*/
		$termpag = $termpag->odoo_id;
	 	//Manipolazione delle stringhe
	 	$commerciale= array();
	 	$localita=explode(" ", $row->localita);
	 	$cap= $localita[0];
	 	$city= isset($localita[1]) ? $localita[1] : "";
	 	$errstring = array("/", "-", " ");
	 	$row->telefono=str_replace($errstring,'',$row->telefono);
	 	$row->telefono= ctype_digit($row->telefono)? $row->telefono:"";
	 	$fisso=strcmp(substr($row->telefono, 0),"0")?$row->telefono:"";
	 	$cellulare=strcmp(substr($row->telefono, 0),"3")?$row->telefono:"";	 	
	 	if(!empty($row->agente)){
	 		$commerciale = $rpc->search(array(array('display_name', 'ilike','%'. preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->agente) . '%')),"res.partner");
	 		if(!empty($commerciale[0])){
	 			$commerciale = $rpc->search(array(array('partner_id', '=',$commerciale[0])),"res.users");
	 			if ($commerciale== -1){
	 				echo "errore inserimento $errn commerciale ". $row->agente."\n";
	 				die();
	 			}
	 		}else{
	 			echo "errore agente $row->agente\n";
	 		}
	 	}
	 	
	 	$user = array(
	  	'name'=> preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name)
	 	, 'city' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$city)
	 	, 'contact_address' =>preg_replace("/[^a-zA-Z0-9\-\s]/", "",  $row->indirizzo) . " " . $city . " " . $cap . " " .$row->provincia
	 	, 'customer' => true
	 	, 'display_name' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name)
	 	, 'employee' => false  
		, 'vat' => 'IT' .  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->partita_iva)
	 	, 'is_company' => true
	 	, 'lang' => 'it_IT'
	 	, 'phone' => $fisso
	 	, 'mobile' => $cellulare
	 	, 'street' =>   preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->indirizzo)
	 	,  'user_id' => empty($commerciale[0])?"":$commerciale[0]
	 	, 'type' => 'contact'
	 	, 'tz' => 'Europe/Rome'
	 	, 'tz_offset' => '+0100'
	 	, 'zip' => $cap
	 	, 'credit_limit' => 100000
	 	, 'country_id' => 110
	   , 'state_id' => "110"
		, 'property_account_receivable' => $conto
		, 'property_account_position' => 1
		, 'property_payment_term' => 'account.payment.term,'.$termpag
		, 'comment' => preg_replace('/[^A-Za-z0-9\-\s]/', '', $row->note1 . "\n" . $row->note2 ."\n" . $row->detnote)
	 	);
	 	$userid = $rpc->create( $user, "res.partner");
	 	if ($userid== -1){
	 		$user['vat'] = "";
	 		$userid = $rpc->create( $user, "res.partner");
	 		if ($userid== -1){
	 		echo "errore inserimento $errn ". $user['name']."\n";
	 		var_dump($user);
	 		die();
	 		}
	 	}
	 	if(empty($row->banca)){
	   	continue;
		}
		$idbanca = $rpc->searchread(
							array(
								array('cab', 'like',substr(preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->cab),0,5)), array('abi', 'like', substr(preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->abi),0,5))),
								"res.bank",
								array('name','id','cab','abi')
								);
							
		if(empty($idbanca)){
	   	continue;
		}			
		
	 	$banca = array (
	 		'owner_name' => preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name)
	 		, 'street' => preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->indirizzo)
	 		, 'name' => '/'
	 		, 'city' => preg_replace('/[^A-Za-z0-9\-\s]/', '',$city)
	 		, 'partner_id' => $userid
	 		, 'bank_name' =>  $idbanca[0]['name']
	 		, 'bank' => $idbanca[0]['id']
	 		, 'acc_number' => $row->conto!=""?$row->conto:"00000"
	 		, 'bank_cab' => $idbanca[0]['cab']
	 		, 'bank_abi' => $idbanca[0]['abi']
	 		, 'state' => 'bank'
	 	);
	 	$bancaid = $rpc->create( $banca, "res.partner.bank");
	 	if ($bancaid== -1){
	 		var_dump($idbanca);
	 		var_dump($banca);
	 		die();
	 	}
	 	
	 }	

}




function sync_clienti_codfis(&$conn,&$rpc){
	//TODO importare codice fiscale 


	$sql="SELECT RAG_SOC as 'name',
	 clienti.PROVINCIA as 'provincia',
	 clienti.INDIRIZZO as 'indirizzo',
	 clienti.LOCALITA as 'localita',
	 COD_PARTIV as 'partita_iva',
	 clienti.COD_FISCAL as'codice_f',
	 clienti.COD_PAG as mod_pag,
	 BANCAAPPO as banca, 
	 DIPENDENZA as bancadip, 
	 CABI as cabi,
	 CAB as cab,
	 NUM_CC as conto,
	 clienti.TELEFONO as 'telefono', 
	 NOTE1 as 'note1', 
	 NOTE2 as 'note2',  
	 clinote.NOTE as 'detnote',
	 agenti.NOMINATIVO as agente 
	 FROM odoo.clienti left JOIN clinote ON clienti.SSSS = clinote.SSSS LEFT JOIN agenti ON clienti.COD_AGE = agenti.id WHERE COD_PARTIV regexp '^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$' OR (clienti.COD_FISCAL regexp '^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$' AND COD_PARTIV LIKE '') OR (clienti.COD_FISCAL LIKE '' AND COD_PARTIV LIKE '') group by name";
	 
	 
	 
	 
$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
echo "Carico clienti con c	odice fiscale\n";
while($row = mysqli_fetch_object($ids))
	{
		if(row->name='.')continue;
		$conto=33;
		//var_dump($row);
		//echo preg_replace('/[^(\x20-\x7F)]*/','', $row->address);
		//echo $row->surname;
	 	//echo $rpc->create( array('name'=>$row->surname), "res.partner");
	 	
	 	
	 	//Cerco la corrispondenza tra le modalità di pagamento
	 	$sql="SELECT odoo_id, type FROM odoo.cp_id_odoo WHERE id = ". $row->mod_pag .";";
	 	$result= mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	 	if ($result->num_rows==0) { echo 'questa modalità di pagamento non esiste :'. $row->mod_pag."\n"; };
	 	$termpag = mysqli_fetch_object($result);
	 	if($termpag->type === 'R')
	 		$conto=237;
	 	elseif ($termpag->type === 'D')
	 		$conto=237;

		 $termpag = $termpag->odoo_id;
	 	//Manipolazione delle stringhe
	 	$localita=explode(" ", $row->localita);
	 	$cap= $localita[0];
	 	$city= isset($localita[1]) ? $localita[1] : "";
	 	$errstring = array("/", "-", " ");
	 	$row->telefono=str_replace($errstring,'',$row->telefono);
	 	$row->telefono= ctype_digit($row->telefono)? $row->telefono:"";
	 	$fisso=strcmp(substr($row->telefono, 0),"0")?$row->telefono:"";
	 	$cellulare=strcmp(substr($row->telefono, 0),"3")?$row->telefono:"";	 	
	 	
	 	if(!empty($row->agente)){
	 		$commerciale = $rpc->search(array(array('display_name', 'ilike', $row->agente)),"res.partner");
	 		if(!empty($commerciale[0])){
	 			$commerciale = $rpc->search(array(array('partner_id', '=', $commerciale[0])),"res.users");
	 		}else{
	 			echo "errore agente $row->agente\n";
	 		}
	 	}
	 	
	 	$user = array(
	  	'name'=> preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name)
	 	, 'city' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$city)
	 	, 'contact_address' =>preg_replace("/[^a-zA-Z0-9\-\s]/", "",  $row->indirizzo) . " " . $city . " " . $cap . " " .$row->provincia
	 	, 'customer' => true
	 	, 'display_name' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name)
	// 	, 'email' => $row->email
	 	, 'employee' => false  
		, 'fiscalcode' =>  !empty($row->partita_iva)?$row->partita_iva:$row->codice_f
	//	, 'date' => $row->created_at
	 	, 'is_company' => true
	 	, 'lang' => 'it_IT'
	 	, 'phone' => $fisso
	 	, 'mobile' => $cellulare
	// 	, 'notify_email' => 'always'
	 	, 'street' =>   preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->indirizzo)
	 	, 'type' => 'contact'
	 	, 'user_id' => empty($commerciale[0])?"":$commerciale[0]
	 	, 'tz' => 'Europe/Rome'
	 	, 'tz_offset' => '+0100'
	 	, 'zip' => $cap
	 	, 'country_id' => 110
        , 'credit_limit' => 100000
		, 'state_id' => "110"
		, 'property_account_receivable' => $conto
		, 'property_account_position' => 1
		, 'property_payment_term' => 'account.payment.term,'.$termpag
		, 'comment' => preg_replace('/[^A-Za-z0-9\-\s]/', '', $row->note1 . "\n" . $row->note2 ."\n" . (isset($row->detnote) ? $row->detnote : ""))
	 //	, 'property_stock_supplier' => array("8","Partner Locations/Suppliers") 
	 //	, 'section_id' => array("1","Direct Sales") 
	 	);
	 	$userid = $rpc->create( $user, "res.partner");
	 	if ($userid== -1){
	 		var_dump($user);
	 	}
	 	
	 		 	if($row->banca==""){
	   	continue;
		}
		$idbanca = $rpc->searchread(array(array('cab', 'like', preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->cab)),array('abi', 'like', preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->cabi))),"res.bank",array('name','id','cab','abi'));
	 	$banca = array (
	 		'owner_name' => preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name)
	 		, 'street' => preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->indirizzo)
	 		, 'name' => $idbanca[0]['name']
	 		, 'city' => $city
	 		, 'partner_id' => $userid
	 		, 'bank' => $idbanca[0]['id']
	 		, 'acc_number' => $row->conto
	 		, 'bank_cab' => $idbanca[0]['cab']
	 		, 'bank_abi' => $idbanca[0]['abi']
	 		, 'state' => 'bank'
	 	);
	 	$bancaid = $rpc->create( $banca, "res.partner.bank");
	 	if ($bancaid== -1){
	 		var_dump($banca);
	 		//die();
	 	}
	 }	

}

function sync_clienti_destcons(&$conn,&$rpc){
	$sql="SELECT RAG_SOC, DESTI_CONS, INDIR_CONS, CAPLO_CONS  FROM odoo.clienti WHERE DESTI_CONS != ''";
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	echo "Carico destinazioni di consegna\n";
	while($row = mysqli_fetch_object($ids))
	{

		$idparent = $rpc->search(array(array('name', 'ilike', preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->RAG_SOC))),"res.partner");
		if (empty($idparent)){
			echo preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->RAG_SOC) . "\n";
			continue;
		}
		$user = array(
		'name'=> preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->DESTI_CONS)
		, 'street' =>   preg_replace('/[^A-Za-z0-9\-\s]/', '', $row->DESTI_CONS . ' ' . $row->INDIR_CONS . ' ' . $row->CAPLO_CONS)
		, 'parent_id' => $idparent[0]
		, 'employee' => false  
		, 'customer' => true
		, 'lang' => 'it_IT'
		, 'type'=> 'delivery'
		);
		$userid = $rpc->create( $user, "res.partner");
	 	if ($userid== -1){
	 		var_dump($user);
	 	}
	}


}

function crea_listino(&$conn,&$rpc){
global $listver;
 $listino= array(
 'name'=> "Listino Scontato"
 ,'type'=> "sale"
 );
 $listino = $rpc->create( $listino, "product.pricelist");
 $listver= array(
 'name'=> "Default listino scontato"
 ,'pricelist_id'=> $listino
 );
 $listver = $rpc->create( $listver, "product.pricelist.version");
}


function sync_fornitori(&$conn,&$rpc){
	$sql="SELECT RAG_SOC as 'name', PROVINCIA as 'provincia', INDIRIZZO as 'indirizzo', LOCALITA as 'localita',  COD_PARTIV as 'partita_iva', TELEFONO as 'telefono', TELEFAX as 'fax', NOTE1 as 'note1', NOTE2 as 'note2' FROM fornitori";
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	echo "Carico Fornitori\n";
	while($row = mysqli_fetch_object($ids))
	{
	 	
	 	//Manipolazione delle stringhe
	 	$localita=explode(" ", $row->localita);
	 	$cap= $localita[0];
	 	$city= isset($localita[1]) ? $localita[1] : "";
	 	$errstring = array("/", "-", " ");
	 	$row->telefono=str_replace($errstring,'',$row->telefono);
	 	$row->fax=str_replace($errstring,'',$row->fax);
	 	$row->telefono= ctype_digit($row->telefono)? $row->telefono:"";
	 	$fisso=strcmp(substr($row->telefono, 0),"0")?$row->telefono:"";
	 	$cellulare=strcmp(substr($row->telefono, 0),"3")?$row->telefono:"";
	 	
	 	
	 	$user = array(
	  	'name'=>preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name)
	 	, 'city' => $city
	 	, 'contact_address' =>preg_replace("/[^a-zA-Z0-9\s]/", "",  $row->indirizzo) . " " . $city . " " . $cap . " " .$row->provincia
	 	, 'customer' => false
	 	, 'supplier' => true
	 	, 'display_name' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name)
	// 	, 'email' => $row->email
	 	, 'employee' => false  
		, 'vat' =>  (empty($row->partita_iva) or preg_match('/^VEDI/i',$row->partita_iva ))?'':'IT'.$row->partita_iva
	//	, 'date' => $row->created_at
	 	, 'is_company' => true
	 	, 'lang' => 'it_IT'
	 	, 'fax' => $row->fax
	 	, 'phone' => $fisso
	 	, 'mobile' => $cellulare
	// 	, 'notify_email' => 'always'
	 	, 'street' =>   preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->indirizzo)
	 	, 'type' => 'contact'
	 	, 'tz' => 'Europe/Rome'
	 	, 'tz_offset' => '+0100'
	 	, 'zip' => $cap
	 	, 'country_id' => 110
	 	, 'credit_limit' => "0"
	 	, 'state_id' => "110"
		, 'property_account_position' => 1
		, 'comment' =>   preg_replace('/[^A-Za-z0-9\-\s]/', ' ',$row->note1 . "\n" . $row->note2)
	 //	, 'property_stock_supplier' => array("8","Partner Locations/Suppliers") 
	 //	, 'section_id' => array("1","Direct Sales") 
	 	);
	//	var_dump($user);
	 	$userid = $rpc->create( $user, "res.partner");
		if ($userid== -1){
	 		$user['vat'] = "";
	 		$userid = $rpc->create( $user, "res.partner");
	 		if ($userid== -1){
	 			echo "errore inserimento $errn ". $user['name']."\n";
	 			var_dump($user);
	 			die();
	 		}
	 	}	
	}
}


function sync_fatture(&$conn,&$rpc){
	echo "Carico Fatture " . date('Y-m-d H:i:s') ."\n";
		
		$sql="SELECT fatture.NUMERO as numero
		, N_REGISTRA 
		, TIPO_CAUSA 
		, fatture.DATA
		, clienti.RAG_SOC as name
		, condpag.PAGAMENTO
		, condpag.id
		, CONSEGNA
		, ORARITIRO
		, DATARITIRO
		, TOTMERCI
		, VETTORE
		, VET_INDIR
		, VET_LOCAL
		, VET_PROVI
        , agenti.NOMINATIVO as agente 
		FROM odoo.fatture 
		INNER JOIN odoo.condpag on fatture.PAGAMENTO = condpag.id
		INNER JOIN odoo.clienti on fatture.MMCC = clienti.mmcc and fatture.SSSS = clienti.SSSS
        LEFT JOIN odoo.agenti ON odoo.clienti.COD_AGE = agenti.id
        GROUP by NUMERO
		ORDER BY numero ASC ;";
		
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	while($row = mysqli_fetch_object($ids))
	{
		$counter=$row->numero;
		$state="open";
		$totnet= 0;
		$totiva = 0;
		$scadenza=$row->DATA;
		$isinsoluto=false;
		$invoiceid=0;
		
		$partner = $rpc->search(array(array('display_name', 'ilike', preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name))),"res.partner");
		if(empty($partner)){
				echo "errore non trovo il CLIENTE  $row->name)\n";
				continue;
			}
		if(!empty($row->agente)){
	 		$commerciale = $rpc->search(array(array('display_name', 'ilike','%'. preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->agente) . '%')),"res.partner");
	 		if(!empty($commerciale[0])){
	 			$commerciale = $rpc->search(array(array('partner_id', '=',$commerciale[0])),"res.users");
	 			if ($commerciale== -1){
	 				echo "errore inserimento $errn commerciale ". $row->agente."\n";
	 				die();
	 			}
	 		}else{
	 			echo "errore agente $row->agente\n";
	 		}
	 	}
			
		//Contronllo le modalita di pagamento
		if($row->id == 1 or $row->id == 2 or $row->id == 19 or $row->id == 29 or $row->id == 45 or $row->id == 50 or $row->id == 58 or $row->id == 114 or $row->id == 80){
			$state="paid";
		}else{
		//Controllo se ci sono ri.ba. insolute
			$sql="SELECT * FROM odoo.scadenze  WHERE NUM_DOC =". $row->numero."  AND DATA_DOC = \"".$row->DATA ."\" ORDER by DATA_DOC desc;";
			$result= mysqli_query($conn, $sql) or die("\nError 02: " . mysql_error() . "$sql\n");
			while($ribarow = mysqli_fetch_object($result)){
				if(time() - strtotime($ribarow->DATA_SCAD) > 60*60*24) {
					// data pagamento scaduta
					if(($ribarow->CAUSALE==='INSOLUTO' or $ribarow->CAUSALE==='Rimessa Diretta') and $ribarow->PAGATO != 'C'){$isinsoluto=true;}
				}
				$scadenza=$ribarow->DATA_SCAD!="0000-00-00"?$ribarow->DATA_SCAD:$row->DATA;
			}
			if($isinsoluto == false and $result->num_rows > 1){$state="paid";}
		}
		
		$sql="SELECT T_FATTURA, T_SPEINCAS, T_IMP1, T_IVA FROM odoo.totfat where NUMERO = ". $row->numero;
		$items= mysqli_query($conn, $sql) or die("\nError 05: " . mysql_error() . "\n");
		while($item = mysqli_fetch_object($items)){
				$totnet= floatval($item->T_IMP1);
				$totiva= floatval($item->T_IVA);
		}
		
		$sql="SELECT odoo_id FROM odoo.cp_id_odoo WHERE id = ". $row->id .";";
	 	$result= mysqli_query($conn, $sql) or die("\nError 03: " . mysql_error() . "\n");
	 	if ($result->num_rows==0) { echo 'questa modalità di pagamento non esiste :'. $row->mod_pag."\n"; };
		$termpag = mysqli_fetch_object($result);
		$termpag =  empty($termpag->odoo_id)?"":$termpag->odoo_id;
		
		$acmoveid =create_account_move($rpc,$partner[0],"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) ,$row->DATA,'posted',"15/".str_pad($counter, 5, '0', STR_PAD_LEFT),4,1);
	
		 //SE NON E' UNA NOTA DI CREDITO
		if($row->TIPO_CAUSA!='N'){
			$invoiceid =create_invoice($rpc,33,"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) , $row->DATA,$scadenza, '/',$acmoveid,$termpag,$partner[0],$state,'out_invoice','false', $row->numero, $isinsoluto, 1, empty($commerciale[0])?1:$commerciale[0]);
			if($invoiceid==-1) continue;
			$sql="SELECT * FROM odoo.fatmov where numero = ". $row->numero;
			$items= mysqli_query($conn, $sql) or die("\nError 04: " . mysql_error() . "\n");
			while($item = mysqli_fetch_object($items)){
				if($item->PREZZO == 0) continue;
				$product = $rpc->searchread(array(array('default_code', 'ilike', $item->ARTICOLO)),"product.product",array('name','id'));
				$invoicelineid = create_invoice_line($rpc,
					empty($product)? preg_replace('/[^A-Za-z0-9\-\s]/', '',$item->descrizion):$product[0]['name'],
					$invoiceid,
					132,
					empty($product)?"":$product[0]['id'],
					$partner[0],
					$item->PREZZO,
					$item->QTA);
			 	if($item->IVA == 20){
			 		$ivacode=53;	
			 		$nameiva='IVA a debito 20%';
			 	}elseif($item->IVA == 21){
			 		$ivacode=55;
			 		$nameiva='IVA a debito 21%';	
			 	}elseif($item->IVA == 4){
			 		$ivacode=57;
			 		$nameiva='IVA a debito 4%';	
			 	}elseif($item->IVA == 10){
			 		$ivacode=51;
			 		$nameiva='IVA a debito 10%';	
			 	}elseif($item->IVA == 22){
			 		$ivacode=79;	
			 		$nameiva='IVA a debito 22%';
			 	}elseif($item->IVA == '' or $item->IVA =='NS26' or $item->IVA =='N41V') { 
			 		$item->IVA == '';
			 	}else{
			 		echo "errore sul'iva";
			 		var_dump($item->IVA);
			 		echo "\n\n\n\n\n";
			 	}
			 	
			 	if($item->IVA !== ''){	
			 		$invoicelinetaxid =create_invoice_tax($rpc,$nameiva,$invoiceid,94,$item->PREZZO,$ivacode,($ivacode - 1),round((($item->PREZZO *  $item->QTA) * $item->IVA /100 ),2));
				}
			}
		
			//Aggiungo spese d'incasso se ci sono
			$sql="SELECT T_FATTURA, T_SPEINCAS, T_IMP1, T_IVA FROM odoo.totfat where NUMERO = ". $row->numero;
			$items= mysqli_query($conn, $sql) or die("\nError 05: " . mysql_error() . "\n");
			while($item = mysqli_fetch_object($items)){
				$fattura = $rpc->searchread(array(array('id', '=', $invoiceid)),"account.invoice",array('amount_untaxed','amount_tax','id'));
				$storno= $totnet - $fattura[0]['amount_untaxed'];
				if($item->T_SPEINCAS > 0){
					$invoicelineid = create_invoice_line($rpc,'storno e s. i.'
					,$invoiceid,132
					,52586
					,$partner[0]
					,$storno
					,1);
				
					$storno= $totiva - $fattura[0]['amount_tax'];
					$invoicelinetaxid= create_invoice_tax($rpc,'IVA a debito 22%',$invoiceid,94,$storno,79,78,round($storno,2));
			 	}
			}
		}
		
		
		
		if($row->TIPO_CAUSA==='N'){ //NOTA DI CREDITO
			//Creo MOVE LINE netto
			$acmovelineid = create_account_move_line($rpc,$partner[0],0,$totnet,80,'valid',"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) ,132,$row->DATA,$acmoveid,"15/$counter totale",-1*$totnet,1,1);
			//Creo MOVE LINE IVA
			$acmovelineid = create_account_move_line($rpc,$partner[0],0,$totiva,79,'valid',"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) ,94,$row->DATA,$acmoveid,"15/$counter IVA",-1*	$totiva,1,1);
			sync_paid_nota($conn,$rpc,$row,$counter,$partner[0],$totnet + $totiva,$scadenza,$acmoveid,$row->DATA,$totnet,$totiva,$termpag,$state);
			continue;
		}
		
		//Creo MOVE LINE netto
		$acmovelineid = create_account_move_line($rpc,$partner[0],$totnet,0,80,'valid',"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) ,132,$row->DATA,$acmoveid,"15/$counter totale",$totnet,1,1,$invoiceid);
		//Creo MOVE LINE IVA
		$acmovelineid = create_account_move_line($rpc,$partner[0],$totiva,0,79,'valid',"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) ,94,$row->DATA,$acmoveid,"15/$counter IVA",$totiva,1,1,$invoiceid);
		if($row->id == 1 or $row->id == 2 or $row->id == 19 or $row->id == 42 or $row->id == 29 or $row->id == 45 or $row->id == 50 or $row->id == 58 or $row->id == 114 or $row->id == 80){
			sync_paid_immediato($conn,$rpc,$row,$counter,$partner[0],$totnet + $totiva,$scadenza,$acmoveid,$row->DATA);
		}else{
			sync_paid_scadenze($conn,$rpc,$row,$counter,$partner[0],$totnet + $totiva,$isinsoluto,$acmoveid,$state,$invoiceid);
		}

	}
}

function sync_paid_nota(&$conn,&$rpc,$row,$counter,$partner,$totale,$scadenza,$acmoveidb,$data,$totnet,$totiva,$termpag,$state){
	//Creo Totale
	$reconcilied = 'none';
	
	$acmoveline = array(
		'partner_id' => $partner
		,'company_id' => 1
		,'date_maturity' => $scadenza
		,'blocked' => false
		,'create_uid' => 1
		,'credit' => $totale
		,'journal_id' => 3
		,'debit' => 0
		,'state' => 'valid'
		,'ref' => "15/".str_pad($counter, 5, '0', STR_PAD_LEFT) 
		,'account_id' => 33
		,'period_id' => 4
		,'date' => $data
		,'move_id' => $acmoveidb
		,'name' => "/"
		,'tax_amount' => 0
		,'quantity' => 1
		);
		
	$acmovelineid= $rpc->create( $acmoveline, "account.move.line");
	if ($acmovelineid== -1){
		var_dump($acmoveline);
		echo "\n\naccount.move.line IVA\n\n\n";
	}
	
	
	
	//$acmoveid = create_account_move($rpc,$partner,"15/" . str_pad($counter, 4, '0', STR_PAD_LEFT),$row->DATA,'posted',"2015/" . str_pad($counter, 4, '0', STR_PAD_LEFT) . "NOTA DI CREDITO",4,3);
	
	$notaid =create_invoice($rpc,33,"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) , $row->DATA,$scadenza, 'nota di credito',$acmoveidb,4,$partner,$state,'out_refund','false', $row->numero,false);
	if($notaid==-1)
		return ;
	
	//AGGIUNGO righe nota di credito
	
	
	
	$sql="SELECT * FROM odoo.fatmov where numero = ". $row->numero;
	$items= mysqli_query($conn, $sql) or die("\nError 04: " . mysql_error() . "\n");
	while($item = mysqli_fetch_object($items)){
		$product = $rpc->searchread(array(array('default_code', 'ilike', $item->ARTICOLO)),"product.product",array('name','id'));
		if($product==-1)continue;
		$invoicelineid = create_invoice_line($rpc
			, empty($product)? preg_replace('/[^A-Za-z0-9\-\s]/', '',$item->descrizion):$product[0]['name']
			,$notaid
			,132
			,empty($product)?"":$product[0]['id']
			,$partner
			,$item->PREZZO
			,$item->QTA);
			
		if($item->IVA == 20){
	 		$ivacode=53;	
	 		$nameiva='IVA a debito 20%';
	 	}elseif($item->IVA == 21){
	 		$ivacode=55;
	 		$nameiva='IVA a debito 21%';	
	 	}elseif($item->IVA == 4){
	 		$ivacode=57;
	 		$nameiva='IVA a debito 4%';	
	 	}elseif($item->IVA == 10){
	 		$ivacode=51;
	 		$nameiva='IVA a debito 10%';	
	 	}elseif($item->IVA == 22){
	 		$ivacode=79;	
	 		$nameiva='IVA a debito 22%';
	 	}elseif($item->IVA == '' or $item->IVA =='NS26' or $item->IVA =='N41V') { 
	 		$item->IVA == '';
	 	}else{
	 		echo "errore sul'iva";
	 		var_dump($item->IVA);
	 		echo "\n\n\n\n\n";
	 	}
	 	
	 	if($item->IVA !== ''){	
	 		$invoicelinetaxid =create_invoice_tax($rpc,$nameiva,$notaid,94,$item->PREZZO,$ivacode,($ivacode - 1),round((($item->PREZZO *  $item->QTA) * $item->IVA /100 ),2));
		}
	}
	
	
	if($state==='paid'){
		$acmovereconcile= array(
			'opening_reconciliation' => false
			,'type' => 'auto'
		);
		$acmovereconcileid= $rpc->create( $acmovereconcile, "account.move.reconcile");
		if ($acmovereconcileid== -1){
			var_dump($acmovereconcile);
			echo "\n\naccount.move.reconcile\n\n\n";
			die();
		}
	
		$invoiceid=$rpc->write(array($acmovelineid),array('reconcile_id' => $acmovereconcileid),'account.move.line');
		$reconcilied=$acmovereconcileid;
		
		$acmoveid = create_account_move($rpc,$partner,"BNK1/2015/" . str_pad($counter, 5, '0', STR_PAD_LEFT),$row->DATA,'draft',"BNK1/2015/" . str_pad($counter, 5, '0', STR_PAD_LEFT),4,7);
		//Creo netto
		$acmovelineid = create_account_move_line($rpc,$partner,$totnet,0,80,'valid'
			,"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) . "NOTA DI CREDITO"
			,132
			,$row->DATA
			,$acmoveid
			,"15/$counter totale"
			,0
			,1
			,7
			);
		
		//Creo IVA
		$acmovelineid = create_account_move_line($rpc,$partner,$totiva,0,79,'valid'
			,"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) . "NOTA DI CREDITO"
			,94
			,$row->DATA
			,$acmoveid
			,"15/$counter IVA"
			,0
			,1
			,7);
	
		//Creo storno
		$acmovelineid =create_account_move_line($rpc,$partner,0,$totale,'','valid'
		,"15/".str_pad($counter, 5, '0', STR_PAD_LEFT) . " NOTA DI CREDITO"
		,33
		,$data
		,$acmoveid,'/',0,1,1,$notaid,$acmovereconcileid,$scadenza);
	}
}

function sync_paid_immediato(&$conn,&$rpc,$row,$counter,$partner,$totale,$scadenza,$acmoveid,$data){
	//Creo Totale
	$acmoveline = array(
		'partner_id' => $partner
		,'company_id' => 1
		,'date_maturity' => $scadenza
		,'blocked' => false
		,'create_uid' => 1
		,'credit' => 0
		,'journal_id' => 1
		,'debit' => $totale
		,'state' => 'valid'
		,'ref' => "15/".str_pad($counter, 5, '0', STR_PAD_LEFT) 
		,'account_id' => 33
		,'period_id' => 4
		,'date' => $data
		,'move_id' => $acmoveid
		,'name' => "/"
		,'tax_amount' => 0
		,'quantity' => 1
		);
		
	$acmovelineid= $rpc->create( $acmoveline, "account.move.line");
	if ($acmovelineid== -1){
		var_dump($acmoveline);
		echo "\n\naccount.move.line IVA\n\n\n";
	}
	$acmovereconcile= array(
		'opening_reconciliation' => false
		,'type' => 'auto'
	);
	$acmovereconcileid= $rpc->create( $acmovereconcile, "account.move.reconcile");
	if ($acmovereconcileid== -1){
		var_dump($acmovereconcile);
		echo "\n\naccount.move.reconcile\n\n\n";
		die();
	}
	$invoiceid=$rpc->write(array($acmovelineid),array('reconcile_ref' => "A$counter", 'reconcile_id' => $acmovereconcileid),'account.move.line');
	
	$acmoveid = create_account_move($rpc,$partner,"BNK1/2015/" . str_pad($counter, 5, '0', STR_PAD_LEFT),$row->DATA,'draft',"BNK1/2015/" . str_pad($counter, 5 '0', STR_PAD_LEFT),4,7);
	
	$acmoveline = array(
		'partner_id' => $partner
		,'company_id' => 1
		, 'blocked' => false
		, 'create_uid' => 1
		, 'credit' => 0
		, 'journal_id' => 7
		, 'debit' => $totale
		, 'state' => 'valid'
		, 'ref' => "BNK12015" . str_pad($counter, 5, '0', STR_PAD_LEFT)
		, 'account_id' => 235
		, 'period_id' => 4
		, 'date' => $row->DATA
		, 'move_id' => $acmoveid
		, 'name' => "/"
		, 'tax_amount' => 0
	);
	$acmovelineid= $rpc->create( $acmoveline, "account.move.line");
		if ($acmovelineid== -1){
			var_dump($acmoveline);
			echo "\n\npaid_immediato account.move.line scadenza\n\n\n";
			die();
		}
		$acmoveline = array(
		'partner_id' => $partner
		,'company_id' => 1
		, 'blocked' => false
		, 'create_uid' => 1
		, 'credit' => $totale
		, 'journal_id' => 7
		, 'debit' => 0
		//, 'reconcile_ref' => "A$counter"
		, 'state' => 'valid'
		, 'ref' => "BNK12015" . str_pad($counter, 5, '0', STR_PAD_LEFT)
		, 'account_id' => 33
		, 'period_id' => 4
		, 'date' => $row->DATA
		, 'move_id' => $acmoveid
		, 'reconcile_id' => $acmovereconcileid
		, 'name' => "/"
		, 'tax_amount' => 0
		, 'quantity' => 1
	);
	$acmovelineid= $rpc->create( $acmoveline, "account.move.line");
		if ($acmovelineid== -1){
			var_dump($acmoveline);
			echo "\n\n paid_immediato account.move.line scadenza\n\n\n";
			die();
		}
	return;

}

function sync_paid_scadenze(&$conn,&$rpc,$row,$counter,$partner,$totale,$isinsoluto,$acmoveid,$state,$invoiceid){
	//$sql="SELECT * FROM odoo.scadenze  WHERE NUM_DOC =". $row->numero."  AND DATA_DOC = \"".$row->DATA ."\"AND DATA_SCAD < NOW() ORDER by DATA_DOC desc;";
	$sql="SELECT * FROM odoo.scadenze  WHERE NUM_DOC =". $row->numero."  AND DATA_DOC = \"".$row->DATA ."\"";
	$saldo=$totale;
	$result= mysqli_query($conn, $sql) or die("\nError 02: " . mysql_error() . "$sql\n");
	while($ribarow = mysqli_fetch_object($result)){
		if($ribarow->CAUSALE === 'Ric. Bancaria' or $ribarow->CAUSALE ==='Rimessa Diretta' or $ribarow->CAUSALE==='Bonifico Banc.' or $ribarow->CAUSALE==='Fattura contrass.' or $ribarow->CAUSALE==='INSOLUTO'){
		    
		    if($ribarow->CAUSALE!='INSOLUTO' ){
			    $acmoveline = array(
				    'partner_id' => $partner
				    ,'company_id' => 1
				    , 'blocked' => false
				    , 'create_uid' => 1
				    , 'credit' => 0
				    , 'journal_id' => 1
				    , 'debit' => $ribarow->IMPORTO
				    , 'state' => 'valid'
				    , 'ref' => "15/".str_pad($counter, 5, '0', STR_PAD_LEFT) 
				    , 'account_id' => 33
				    , 'period_id' => 4
				    , 'date_maturity' => $ribarow->DATA_SCAD
				    , 'date' => $row->DATA
				    , 'move_id' => $acmoveid
				    , 'name' => "/"
				    , 'tax_amount' => 0
				    , 'quantity' => 1
			    );
			    $acmovelineid= $rpc->create( $acmoveline, "account.move.line");
				    if ($acmovelineid== -1){
		     			var_dump($acmoveline);
					    echo "\n\npaid_scadenze account.move.line scadenza\n\n\n";
	     			}
	     	}
	 		if(($ribarow->PAGATO === 'P' and $ribarow->CAUSALE != 'INSOLUTO') or ($ribarow->PAGATO === 'C' and $ribarow->CAUSALE === 'INSOLUTO')){
	 			
	 			$acmovereconcileid='none';
	 			if(time() - strtotime($ribarow->DATA_SCAD) > 60*60*24) {
					$acmovereconcile= array(
						'opening_reconciliation' => false
						,'type' => 'auto'
					);
					$acmovereconcileid= $rpc->create( $acmovereconcile, "account.move.reconcile");
					if ($acmovereconcileid== -1){
						var_dump($acmovereconcile);
						echo "\n\naccount.move.reconcile\n\n\n";
						die();
					}
					$rpc->write(array($acmovelineid),array('reconcile_ref' => "A$counter", 'reconcile_id' => $acmovereconcileid),'account.move.line');
				
				
				
					$acmove_id = create_account_move($rpc,$partner,"Ri.Ba. 15/" . str_pad($counter, 5, '0', STR_PAD_LEFT),$row->DATA,'posted',"BNK1/2015/" . str_pad($counter, 4, '0', STR_PAD_LEFT),4,8);
				
					$acmoveline = array(
						'partner_id' => $partner
						,'company_id' => 1
						, 'blocked' => false
						, 'create_uid' => 1
						, 'credit' => $ribarow->IMPORTO
						, 'journal_id' => 8
						, 'debit' => 0
						, 'state' => 'valid'
						, 'ref' => "Ri.Ba 15/" . str_pad($counter, 5, '0', STR_PAD_LEFT)
						, 'account_id' => 237
						, 'period_id' => 4
						, 'date' => $row->DATA
					
						, 'reconcile_ref' => "A$acmovereconcileid"
						, 'reconcile_id' => $acmovereconcileid
						, 'move_id' => $acmove_id
						, 'name' => "15/" . str_pad($counter, 5, '0', STR_PAD_LEFT)
						, 'tax_amount' => 0
					);
					$acmovelineid= $rpc->create( $acmoveline, "account.move.line");
						if ($acmovelineid== -1){
							var_dump($acmoveline);
							echo "\n\npaid_Scadenze account.move.line scadenza\n\n\n";
						}
					if($acmovereconcileid!='none'){
						$acmoveline = array(
							'partner_id' => $partner
							,'company_id' => 1
							, 'blocked' => false
							, 'create_uid' => 1
							, 'credit' => 0
							, 'journal_id' => 8
							, 'debit' => $ribarow->IMPORTO
							, 'state' => 'valid'
							, 'ref' => "Ri.Ba 15/" . str_pad($counter, 5, '0', STR_PAD_LEFT)
							, 'account_id' => 237
							, 'period_id' => 4
							, 'date' => $row->DATA
							, 'date_maturity' => $ribarow->DATA_SCAD
							, 'move_id' => $acmove_id
							, 'name' => "Ri. Ba 15/" . str_pad($counter, 5, '0', STR_PAD_LEFT)
							, 'tax_amount' => 0
							, 'day' => $ribarow->DATA_SCAD
						);
						$acmovelineid= $rpc->create( $acmoveline, "account.move.line");
							if ($acmovelineid== -1){
								var_dump($acmoveline);
								echo "\n\npaid_Scadenze account.move.line scadenza\n\n\n";
								die();
							}
					}
					else
					{
						echo "reconcilied uguale a none \n";
						create_account_move_line($rpc,$partner,0,$ribarow->IMPORTO,'','valid',"BNK12015" . str_pad($counter, 5, '0', STR_PAD_LEFT),'237',$row->DATA,$acmove_id,'/',0,1,8,$invoiceid, 'none',$ribarow->DATA_SCAD);
					}
				}		
	 		}else{
	 		    echo "Riba non pagata\n";
	 		    $tmp= $rpc->search(array(array('move_id', '=', $acmoveid),array('reconcile_id', '!=', '')),"account.move.line");
	 			$rpc->write(array($invoiceid),array('state'=>'open', 'is_unsolved' => true, 'reconciled' => false),'account.invoice');
	 			$query= "INSERT INTO invoice_unsolved_line_rel VALUES (" . $tmp[0] .",". $invoiceid." );\n";
	 			file_put_contents('query.txt', $query, FILE_APPEND);
	 			#$rpc->write(array($invoiceid),array('unsolved_move_line_ids' => array($tmp[0])),'account.invoice');
	 		}
		 	$saldo=$saldo-$ribarow->IMPORTO;
		}
	}
}

function sync_insoluti(&$conn,&$rpc){
    $sql="SELECT scadenze.* , clienti.RAG_SOC FROM odoo.scadenze INNER JOIN odoo.clienti on scadenze.MMCC = clienti.mmcc and scadenze.SSSS = clienti.SSSS WHERE DATA_DOC < '2015-01-01' AND CAUSALE like \"INSOLUTO\" AND PAGATO != 'C' AND PAGATO != 'P' AND NUM_DOC != 0 group by NUM_DOC, DATA_DOC;";
    $ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
    while($line = mysqli_fetch_object($ids)){
        $partner = $rpc->search(array(array('display_name', 'ilike', preg_replace('/[^A-Za-z0-9\-\s]/', '',$line->RAG_SOC))),"res.partner");
		if(empty($partner[0])){
		    echo "errore non trovo il CLIENTE  $line->name)\n";
		    continue;
	    }
	    $scadenza=$line->DATA_SCAD!="0000-00-00"?$line->DATA_SCAD:$line->DATA_DOC;
        $time = strtotime($line->DATA_DOC);
        $acmoveid =create_account_move($rpc,$partner[0],date('y', $time) .'/'.str_pad($line->NUM_DOC, 5, '0', STR_PAD_LEFT),$line->DATA_DOC,'posted',date('Y', $time).'/'. $line->NUM_DOC,4,1);
        $invoice=array(
			 'account_id' => 33
			, 'company_id' => 1
			, 'number' => date('y', $time).'/'.str_pad($line->NUM_DOC, 5, '0', STR_PAD_LEFT)
			, 'currency_id' => 1
			, 'date_invoice' =>  $line->DATA_DOC
			, 'date_due' => $scadenza
			, 'fiscal_position' => 1
			, 'internal_number' => date('y', $time).'/'.str_pad($line->NUM_DOC, 5, '0', STR_PAD_LEFT)
			, 'period_id' => 4
			, 'payment_term' => 10
			, 'move_id' => $acmoveid
			, 'name' => '/'
			, 'partner_id' => $partner[0]
			, 'journal_id' => 1
			, 'state' => 'open'
			, 'type' => 'out_invoice'
			, 'reconciled' => false
			, 'user_id' => 1
			, 'comment' => ''
			, 'is_unsolved' => true
		    );
		    $invoiceid = $rpc->create( $invoice, "account.invoice");
		    if ($invoiceid== -1){
				var_dump($invoice);
				echo "\n\nsync Insoluti account.invoice\n\n\n";
				die();
			}
        $sql="SELECT scadenze.* , clienti.RAG_SOC FROM odoo.scadenze  INNER JOIN odoo.clienti on scadenze.MMCC = clienti.mmcc and scadenze.SSSS = clienti.SSSS WHERE NUM_DOC =". $line->NUM_DOC."  AND DATA_DOC = \"".$line->DATA_DOC ."\" AND CAUSALE like \"INSOLUTO\" AND PAGATO != 'C'";
        $ods = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
        while($row = mysqli_fetch_object($ods)){
		        $invoicelineid = create_invoice_line($rpc,
					'tot da recuperare',
					$invoiceid,
					132,
					"",
					$partner[0],
					$row->IMPORTO, //prezzo
					1);  //quantità
					
				$acmovelineid = create_account_move_line($rpc,$partner[0],$row->IMPORTO,0,80,'valid',date('y', $time).'/'.str_pad($row->NUM_DOC, 5, '0', STR_PAD_LEFT) ,132,$row->DATA_DOC,$acmoveid,date('y', $time).'/'.str_pad($row->NUM_DOC, 5, '0', STR_PAD_LEFT),$row->IMPORTO,1,1,$invoiceid);
                if ($acmovelineid== -1){
					var_dump($invoice);
					echo "\n\nsync Insoluti account.move.line\n\n\n";
					die();
					}
				$acmoveline = array(
				    'partner_id' => $partner[0]
				    ,'company_id' => 1
				    , 'blocked' => false
				    , 'create_uid' => 1
				    , 'credit' => 0
				    , 'journal_id' => 1
				    , 'debit' => $row->IMPORTO
				    , 'state' => 'valid'
				    , 'ref' => date('y', $time).'/'.str_pad($row->NUM_DOC, 5, '0', STR_PAD_LEFT)
				    , 'account_id' => 33
				    , 'period_id' => 4
				    , 'date_maturity' => $scadenza
				    , 'date' => $row->DATA_DOC
				    , 'move_id' => $acmoveid
				    , 'stored_invoice_id' => $invoiceid
				    , 'name' => "/"
				    , 'tax_amount' => 0
				    , 'quantity' => 1
			    );
			    $acmovelineid= $rpc->create( $acmoveline, "account.move.line");
				    if ($acmovelineid== -1){
		     			var_dump($acmoveline);
					    echo "\n\nsync insoluti account.move.line scadenza\n\n\n";
					    die();
	     			}
            // DA TESTARE
            $tmp= $rpc->search(array(array('move_id', '=', $acmoveid),array('reconcile_id', '!=', '')),"account.move.line");
            $query= "INSERT INTO invoice_unsolved_line_rel VALUES (" . $tmp[0] .",". $invoiceid." );\n";
            file_put_contents('query.txt', $query, FILE_APPEND);
        }
    }
}




function sync_articoli(&$conn,&$rpc){
	global $listver;
	echo "Carico prodotti" . date('Y-m-d H:i:s') ."\n";
	$listver= 2;
	$sql="SELECT articoli.CODICE as codice,
			 GRUPPO as gruppo,
			 descrizion as nome,
			 PREZZOLIST as prezzolistino,
			 PREZZONETT as prezzonetto,
			 COD_ART_FO as codforn,
			 PREZZO_1 as prezzofinale,
             PREZZO_2 as prezzofinale2
			 COSTO_ULT as costo,
			 PERCRICAR1 as scontocliente ,
			 COSTODACON as condizione, 
			 fornitori.RAG_SOC as fornitore,
			 ALIQUOTA as aliquota,
			 GRUPPO as gruppo,
			 artpagc.NOTE1 as note1,
			 artpagc.NOTE2 as note2,
			 artpagc.NOTE3 as note3,
			 artpagc.NOTE4 as note4,
			 artpagc.NOTE5 as note5,
			 artpagc.COSTOCOMAG as costocomag,
			 artpagc.TIPOSCONTO as tiposconto,
			 artpagc.SCONTO1 as sconto1,
			 artpagc.SCONTO2 as sconto2,
			 artpagc.SCONTO3 as sconto3,
			 artpagc.PZOMAGGIO as pezziomaggio,
			 artpagc.OMAGGIOGNI as omaggiogni,
			 BARCODE1 as barcode
			 BARCODE2 as barcode2
			  FROM odoo.articoli left JOIN odoo.fornitori on fornitori.MMCC = MMCC_FORAB AND fornitori.SSSS = SSSS_FORAB left JOIN odoo.artpagc ON articoli.codice = artpagc.CODICE group by CODICE";
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	while($row = mysqli_fetch_object($ids))
	{
	$notefornitore = "";
	if($row->tiposconto == 1){
	    //SCONTO
	    $notefornitore= "Costo finale dato da " . $row->prezzolistino . " - " . $row->sconto1 . "% - " . $row->sconto2 . "% - " . $row->sconto3 . "% \n pezzi omaggio: " . $row->pezziomaggio . " ogni "  . $row->omaggiogni;
	}
	$note=$row->note1 . "\n" . $row->note2 . "\n" . $row->note3 . "\n" . $row->note4 . "\n" . $row->note5 . "\n";
	
	//Cerco l'id del fornitore
	$idfornitore = $rpc->search(array(array('name', '=', $row->fornitore),array('supplier', '=', true)),"res.partner");
		//Cerco l'id del gruppo
	$sql="SELECT descrizion as descrizione  FROM odoo.gruppi WHERE CODICE LIKE \"%$row->gruppo\";";
	$grupquery=  mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	$gruppo=mysqli_fetch_object($grupquery);
	if(isset($gruppo->descrizione)){
		$idcategory = $rpc->search(array(array('name', '=', $gruppo->descrizione)),"product.category");
		}
		//--------
	if( $row->prezzolistino!= '0')
		{
		 	$articolo = array(
			'active'=>true
			, 'default_code' => $row->codice
			,'categ_id' => isset($idcategory[0])?$idcategory[0]:""
			, 'type' => 'product'
			, 'name' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
			, 'ean13'=> $row->barcode
			, 'description' =>  $note
		 	, 'default_code' => $row->codice
		 	, 'list_price' => str_replace(',', '.', $row->prezzolistino)
		 	, 'seller_id' =>  isset($idfornitore[0])?$idfornitore[0]:""
		 	, 'qty_available' => 10000
		 	, 'virtual_available' => 10000
		 	, 'seller_qty' => 1
		 	, 'seller_delay' => 1
		 	, 'description_purchase' => $notefornitore
		 	, 'standard_price' =>str_replace(',', '.', $row->costo)

	 	);
	 	}
	 	elseif($row->prezzolistino == '0' and $row->prezzonetto != '0'){
		 	$articolo = array(
			'active'=>true
			, 'default_code' => $row->codice
		   , 'categ_id' => isset($idcategory[0])?$idcategory[0]:""
			, 'type' => 'product'
			, 'name' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
			,'ean13'=>$row->barcode
			, 'description' =>  $note
		 	, 'default_code' => $row->codice
		 	, 'list_price' => str_replace(',', '.', $row->prezzonetto)
		 	, 'seller_id' =>  isset($idfornitore[0])?$idfornitore[0]:""
		 	, 'qty_available' => 10000
		 	, 'virtual_available' => 10000
		 	, 'seller_qty' => 1
		 	, 'seller_delay' => 1
		 	, 'description_purchase' => $notefornitore
		 	, 'standard_price' =>str_replace(',', '.', $row->costo)

		 	);
		 }
		elseif($row->prezzolistino == '0' and $row->prezzonetto == '0' and $row->prezzofinale != 0){
			$articolo = array(
			'active'=>true
			, 'default_code' => $row->codice
		   , 'categ_id' => isset($idcategory[0])?$idcategory[0]:""
			, 'type' => 'product'
			, 'name' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
			,'ean13'=>$row->barcode
			, 'description' =>  $note
		 	, 'default_code' => $row->codice
		 	, 'list_price' => str_replace(',', '.', $row->prezzofinale)
		 	, 'seller_id' =>  isset($idfornitore[0])?$idfornitore[0]:""
		 	, 'seller_qty' => 1
		 	, 'qty_available' => 10000
		 	, 'virtual_available' => 10000
		 	, 'seller_delay' => 1
		 	, 'description_purchase' => $notefornitore
		 	, 'standard_price' =>str_replace(',', '.', $row->costo)
		 	);
	 	}else{
	 		print("C'è qualcosa che non  va codice: $row->codice \n");
	 		continue;
	 	}
	 	
	 	$articoli = $rpc->create( $articolo, "product.template");
	 	if ($articoli== -1){
	 		$articolo['ean13'] ="";
	 		$articoli = $rpc->create( $articolo, "product.template");
	 			if ($articoli== -1){
	 				echo "articolo\n";
	 				var_dump($articolo);
	 				die();
		 	}
		}	 	
	 	
	 	listino1 = new product_list_item;
	 	listino1->
	 	
	 	
	 	if(!empty($idfornitore)){
	     	$fornitore= array(
	     	  'product_tmpl_id' => $articoli
	     	, 'name' => $idfornitore[0]
	     	, 'product_code' => $row->codforn );
	     	$fornitori= $rpc->create( $fornitore, "product.supplierinfo");
	     	if ($fornitori == -1){
	     		echo "fornitore";
	     		var_dump($fornitore);
	     		die();
	     	}
	 	}
	 	$articolo = $rpc->search(array(array('product_tmpl_id', '=',$articoli)),"product.product");
	 	$quantity=array(
	 	 'location_id' =>12
	 	, 'lot_id'=> False
	 	, 'new_quantity'=> 10000
	 	, 'product_id'=> $articolo[0]
	 	);
	 	$qty= $rpc->create( $quantity, "stock.change.product.qty");
	 	if ($qty == -1){
	 		echo "quantità";
	 		var_dump($quantity);
	 		die();
	 	}
	 	$inventory=array(
	 	 'company_id' => 1
	 	,'filter' =>'product'
	 	, 'location_id' =>12
	 	, 'name' =>'INV: '.preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
	 	, 'state' => 'done'
	 	, 'product_id'=> $articolo[0]
	 	);
	 	$inventoryid= $rpc->create( $inventory, "stock.inventory");
	 	if ($inventoryid == -1){
	 		echo "inventory";
	 		var_dump($inventory);
	 		die();
	 	}
	 	$inventoryline=array(
	 	 'company_id' => 1
	 	,'location_name' =>'Physical Locations / WH / Stock'
	 	, 'location_id' =>12
	 	, 'product_name' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
	 	, 'inventory_id' => $inventoryid
	 	, 'product_qty' => 10000
	 	, 'product_uom_id' => 1
	 	, 'product_id'=> $articolo[0]
	 	, 'product_code' => $row->codice
	 	);
	 	$inventorylineid= $rpc->create( $inventoryline, "stock.inventory.line");
	 	if ($inventorylineid == -1){
	 		echo "inventory";
	 		var_dump($inventorylineid);
	 		die();
	 	}
	 	$stockmove=array(
	 	  'company_id' => 1
	 	//, 'date_expected' => time()
	 	//, 'date' => time()
	 	, 'invoice_state' => 'none'
	 	, 'location_dest_id' => 12
	 	, 'location_id' =>5
	 	, 'product_uom' => 1
	 	, 'inventory_id' =>$inventoryid
	 	, 'state' => 'done'
	 	, 'product_uom_qty' => 10000
	 	, 'name' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
	 	, 'procure_method' => 'make_to_stock'
	 	, 'product_id'=> $articolo[0]
	 	);
	 	$smoveid= $rpc->create( $stockmove, "stock.move");
	 	if ($smoveid == -1){
	 		echo "smoveid";
	 		var_dump($stockmove);
	 		die();
	 	}
	 	$stokquant=array(
	 	 'company_id' => 1
	 	,'qty' => 10000
	 	, 'cost' => str_replace(',', '.', $row->prezzofinale)
	 	, 'location_id' => 12
	 	, 'product_id' => $articolo[0]
	 	);
	 	$stokquantid= $rpc->create( $stokquant, "stock.quant");
	 	if ($stokquantid == -1){
	 		echo "inventory";
	 		var_dump($stokquant);
	 		die();
	 	}
	 	
	 }
}	



function sync_gruppi(&$conn,&$rpc){
	$sql="SELECT CODICE as codice, descrizion as descrizione  FROM odoo.gruppi;";
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	echo "Carico Categorie di prodotti\n";
	while($row = mysqli_fetch_object($ids))
	{
		$gruppi= array(
		'complete_name' => $row->descrizione
		,'name' => $row->descrizione
		,'parent_id' => 2
		);
		//var_dump($gruppi);
		$gruppi = $rpc->create( $gruppi, "product.category");
	}
}





function create_bank_account(&$conn,&$rpc,$id,$userid,$iban ){
		$sql= "INSERT INTO `owums_db_new`.`user_openwisp_odoo` (`uid_openwisp_id`, `uid_odoo_id`, `created_at`, `updated_at`) VALUES ('". $id ."', '".$userid ."', NOW(), NOW());";
	mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");

	
	$bank = array(
 	'acc_number' => $iban
 	, 'partner_id' => $userid
 	, 'state' => 'iban'
 	
 	);
	
	$bankid = $rpc->create( $bank, "res.partner.bank");
}

function create_invoice_tax(&$rpc,$nameiva,$invoiceid,$account_id,$base_amount,$tax_code_id,$base_code_id,$amount){
	$company_id = 1;
	$lineiva = array(
		'invoice_id' => $invoiceid
		, 'name' => $nameiva
		, 'account_id' => $account_id
		, 'company_id' => $company_id
		, 'base_amount' =>  $base_amount
		, 'tax_code_id' => $tax_code_id
		, 'base_code_id' => $base_code_id
		, 'amount' => $amount
	);
	$invoicelinetaxid = $rpc->create( $lineiva, "account.invoice.tax");
	if ($invoicelinetaxid== -1){
		echo "errore sul inserimento iva\n";
		echo "\n\n\n\n\n";
 	}else{
 	return $invoicelinetaxid;
 	}
}

function create_account_move(&$rpc,$partner,$name,$date,$state='posted',$ref='',$period_id=4,$journal_id=1){
	$acmove = array(
			'partner_id' => $partner
			, 'name' => $name
			, 'state' => $state
			, 'period_id' => $period_id
			, 'journal_id' => $journal_id
			, 'date' => $date
			, 'ref' => $ref
		);
		$acmoveid= $rpc->create( $acmove, "account.move");
		if ($acmoveid== -1){
 			var_dump($acmove);
			echo "\n\naccount.move\n\n\n";
		}else{
		return $acmoveid;
		}
}


function create_account_move_line(&$rpc,$partner,$credit,$debit,$tax_code_id,$state,$ref,$account_id,$date,$acmoveid,$name,$tax_amount,$quantity,$journal_id,$invoice_id='',$reconcilied = "none",$date_maturity=''){
	$company_id = 1;
	$period_id = 4;
	
	if($reconcilied==='none'){
		$acmoveline = array(
			'partner_id' => $partner
			,'company_id' => $company_id
			, 'blocked' => false
			, 'create_uid' => 1
			, 'credit' => $credit
			, 'debit' => $debit
			, 'journal_id' => $journal_id
			, 'tax_code_id' => $tax_code_id
			, 'state' => $state
			, 'ref' => $ref 
			, 'account_id' => $account_id
			, 'period_id' => $period_id
			, 'date' => $date
			, 'move_id' => $acmoveid
			, 'name' => $name
			, 'tax_amount' => $tax_amount
			, 'quantity' => 1
			, 'stored_invoice_id' => $invoice_id
			, 'day' => $date_maturity
		);
	}else{
		$acmoveline = array(
			'partner_id' => $partner
			,'company_id' => $company_id
			, 'blocked' => false
			, 'create_uid' => 1
			,  'date_maturity' => $date_maturity
			, 'credit' => $credit
			, 'debit' => $debit
			, 'journal_id' => 1
			, 'tax_code_id' => $tax_code_id
			, 'reconcile_ref' => 'A'.$reconcilied
			, 'reconcile_id' => $reconcilied
			, 'state' => $state
			, 'ref' => $ref 
			, 'account_id' => $account_id
			, 'period_id' => $period_id
			, 'date' => $date
			, 'move_id' => $acmoveid
			, 'name' => $name
			, 'tax_amount' => $tax_amount
			, 'quantity' => 1
			, 'stored_invoice_id' => $invoice_id
			, 'day' => $date_maturity
		);
	
	
	}
	$acmovelineid= $rpc->create( $acmoveline, "account.move.line");
	if ($acmovelineid== -1){
		var_dump($acmoveline);
		echo "\n\naccount.move.line TOT\n\n\n";
	}else{
		return $acmovelineid;
	}
}


function create_invoice_line(&$rpc,$name,$invoiceid,$account_id,$product_id,$partner,$price,$quantity){
	$uos_id = 1;
	$company_id = 1;
	
	
	$line = array(
		'uos_id' => $uos_id 
		, 'name' => $name
		,'invoice_id' => $invoiceid
		, 'account_id' => $account_id
		, 'product_id' => $product_id
		, 'company_id' => $company_id
		, 'partner_id' => $partner
		, 'price_unit' =>  $price
		, 'quantity' => $quantity
		);
		$invoicelineid = $rpc->create( $line, "account.invoice.line");
		if ($invoicelineid== -1){
			echo "errore sul inseriment linea fattura\n";
	 		var_dump($line);
	 		echo "\n\n\n\n\n";
	 	}else{
	 		return $invoicelineid;
	 	
	 	}
}



function create_invoice(&$rpc,$account_id,$number, $date,$scadenza, $name ='/',$acmoveid,$termpag,$partner,$state='posted',$type='out_invoice',$reconcilied = 'false', $comment='', $is_unsolved, $journal_id=1, $user_id = 1){
	$company_id = 1;
	$currency_id = 1;
	$fiscal_position =1;
	$period_id = 4;
	
	$invoice= array(
			'account_id' => $account_id
			, 'company_id' => $company_id
			, 'number' => $number
			, 'currency_id' => $currency_id
			, 'date_invoice' => $date
			, 'date_due' => $scadenza
			, 'fiscal_position' => $fiscal_position
			, 'internal_number' => $number
			, 'period_id' => $period_id
			, 'name' => $name
			, 'move_id' => $acmoveid
			, 'payment_term' => $termpag
			, 'partner_id' => $partner
			, 'journal_id' => $journal_id
			, 'state' => $state
			, 'type' => $type
			, 'reconciled' => $reconcilied
			, 'user_id' => $user_id
			, 'comment' => $comment
			, 'is_unsolved' => $is_unsolved
		);
	$invoiceid = $rpc->create( $invoice, "account.invoice");
		if ($invoiceid== -1){
			var_dump($invoice);
			echo "Function creo fattura";
			echo "\n\n\n\n\n";
			return -1;
		}
		return $invoiceid;
}

?>
