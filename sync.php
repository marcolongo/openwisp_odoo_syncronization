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





$conn = new mysqli($config['dbhost'], $config['dbuser'], $config['dbpassword'], $config['dbname'] );
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "\nConnected successfully\n\n";

$rpc = new OpenERP();
$x = $rpc->login("admin", "m1a1u1c1-", "mauceri", $config['odoourl'] . "xmlrpc/2/");



//sync_bank($conn,$rpc);
/////sync_agent($conn,$rpc);
//sync_clienti_vat($conn,$rpc);
//sync_clienti_codfis($conn,$rpc);
//sync_clienti_destcons($conn,$rpc);
//sync_fornitori($conn,$rpc);
//sync_gruppi($conn,$rpc);
//crea_listino($conn,$rpc);
//sync_articoli($conn,$rpc);
sync_fatture($conn,$rpc);
	


die();

function sync_agent(&$conn,&$rpc){

	{
	$sql="SELECT * FROM odoo.agenti";
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	while($row = mysqli_fetch_object($ids)){
		if ($row->nominativo=="") continue;
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
     FROM clienti left JOIN clinote ON clienti.SSSS = clinote.SSSS LEFT JOIN agenti ON clienti.COD_AGE = agenti.id WHERE COD_PARTIV regexp '^[0-9]+'" ;
$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
echo "Carico clienti con vat\n";
$errn=0;
while($row = mysqli_fetch_object($ids))
	{
		//var_dump($row);
		//echo preg_replace('/[^(\x20-\x7F)]*/','', $row->address);
		
	 	//echo $rpc->create( array('name'=>$row->surname), "res.partner");

	 	$sql="SELECT odoo_id FROM odoo.cp_id_odoo WHERE id = ". $row->mod_pag .";";
	 	$result= mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	 	if ($result->num_rows==0) { echo 'questa modalità di pagamento non esiste :'. $row->mod_pag."\n"; };
	 	$termpag = mysqli_fetch_object($result);
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
	 		$commerciale = $rpc->search(array(array('display_name', 'ilike','%'. $row->agente. '%')),"res.partner");
	 		if(!empty($commerciale[0])){
	 			$commerciale = $rpc->search(array(array('partner_id', 'like','%'. $commerciale[0]. '%')),"res.users");
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
	 	, 'country_id' => 110
	 	, 'credit_limit' => "0"
	   , 'state_id' => "110"
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
	 FROM odoo.clienti left JOIN clinote ON clienti.SSSS = clinote.SSSS LEFT JOIN agenti ON clienti.COD_AGE = agenti.id WHERE COD_PARTIV regexp '^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$' OR (clienti.COD_FISCAL regexp '^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$' AND COD_PARTIV LIKE '') OR (clienti.COD_FISCAL LIKE '' AND COD_PARTIV LIKE '')";
	 
	 
	 
	 
$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
echo "Carico clienti con c	odice fiscale\n";
while($row = mysqli_fetch_object($ids))
	{
		//var_dump($row);
		//echo preg_replace('/[^(\x20-\x7F)]*/','', $row->address);
		//echo $row->surname;
	 	//echo $rpc->create( array('name'=>$row->surname), "res.partner");
	 	
	 	
	 	//Cerco la corrispondenza tra le modalità di pagamento
	 	$sql="SELECT odoo_id FROM odoo.cp_id_odoo WHERE id = ". $row->mod_pag .";";
	 	$result= mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	 	if ($result->num_rows==0) { echo 'questa modalità di pagamento non esiste :'. $row->mod_pag."\n"; };
	 	$termpag = mysqli_fetch_object($result);
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
	 			$commerciale = $rpc->search(array(array('partner_id', 'like', $commerciale[0])),"res.users");
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
	 	, 'credit_limit' => "0"
	   , 'state_id' => "110"
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
	 		die();
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
	$sql="SELECT fatture15.NUMERO as numero
		, N_REGISTRA 
		, fatture15.DATA
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
		FROM odoo.fatture15 
		INNER JOIN odoo.condpag on fatture15.PAGAMENTO = condpag.id
		INNER JOIN odoo.clienti on fatture15.MMCC = clienti.mmcc and fatture15.SSSS = clienti.SSSS
		WHERE TIPO_CAUSA NOT LIKE 'N';";
	
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
		
	while($row = mysqli_fetch_object($ids))
	{
		$state="open";
		$partner = $rpc->search(array(array('display_name', 'ilike', preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->name))),"res.partner");
		if(empty($partner)){
	 			echo "errore non trovo il prodotto  $row->name)\n";
	 		}
		//Contronllo le modalita di pagamento
		if($row->id == 1 or $row->id == 2 or $row->id == 19 or $row->id == 29 or $row->id == 45 or $row->id == 50 or $row->id == 58){
			$state="paid";
		}
		$sql="SELECT odoo_id FROM odoo.cp_id_odoo WHERE id = ". $row->id .";";
	 	$result= mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	 	if ($result->num_rows==0) { echo 'questa modalità di pagamento non esiste :'. $row->mod_pag."\n"; };
	 	$termpag = mysqli_fetch_object($result);
	 	$termpag = $termpag->odoo_id;
	 	
		$invoice= array(
			'account_id' => 33
			, 'company_id' => 1
			, 'currency_id' => 1
			, 'date_invoice' => $row->DATA
			, 'fiscal_position' => 1
			, 'number' => $row->numero
			, 'internal_number' => $row->numero
			, 'payment_term' => $termpag
			, 'partner_id' => $partner[0]
			, 'journal_id' => 1
			, 'state' => $state
			, 'type' => 'out_invoice'
			, 'reconciled' => false	
			, 'user_id' => 1
	
		);
	
		$invoiceid = $rpc->create( $invoice, "account.invoice");
			if ($invoiceid== -1){
		 		var_dump($invoice);
				echo "\n\n\n\n\n";
				continue;
		 	}
		 	
		 
		
	//	$sql="SELECT * FROM odoo.fatmov where numero = ". $row->numero;
	$sql="SELECT * FROM odoo.fatmov15 where numero = ". $row->numero;
		$items= mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
		while($item = mysqli_fetch_object($items)){
			$product = $rpc->searchread(array(array('default_code', 'ilike', $item->ARTICOLO)),"product.product",array('name','id'));
		
			$line = array(
				'uos_id' => 1
				, 'name' => empty($product)? preg_replace('/[^A-Za-z0-9\-\s]/', '',$item->descrizion):$product[0]['name']
				,'invoice_id' => $invoiceid
				, 'account_id' => 132
				, 'product_id' => empty($product)?"":$product[0]['id']
				, 'company_id' => 1
				, 'partner_id' => $partner[0]
				, 'price_unit' =>  $item->PREZZO
				, 'quantity' => $item->QTA
			);
			$invoicelineid = $rpc->create( $line, "account.invoice.line");
			if ($invoicelineid== -1){
				echo "errore sul inseriment linea fattura\n";
		 		var_dump($invoice);
		 		var_dump($line);
		 			echo "\n\n\n\n\n";
		 		continue;
		 	}
		 	
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
		 	}elseif($item->IVA == '' or $item->IVA =='NS26') { 
		 		$item->IVA == '';
		 	}else{
		 		echo "errore sul'iva";
		 		var_dump($item->IVA);
		 		echo "\n\n\n\n\n";
		 	}
		 	
		 if($item->IVA !== ''){	
			 	$lineiva = array(
					'invoice_id' => $invoiceid
					, 'name' => $nameiva
					, 'account_id' => 94
					, 'company_id' => 1
					, 'base_amount' =>  $item->PREZZO
					, 'tax_code_id' => $ivacode
					, 'base_code_id' => ($ivacode - 1)
					, 'amount' => (($item->PREZZO *  $item->QTA) * $item->IVA /100 )
				);
			$invoicelinetaxid = $rpc->create( $lineiva, "account.invoice.tax");
				if ($invoicelinetaxid== -1){
					echo "errore sul inserimento iva\n";
			 		var_dump($invoice);
		 			var_dump($line);
		 			var_dump($lineiva);
		 			echo "\n\n\n\n\n";
		 			continue;
			 	}
		
			}
		}

	
	}
}

function sync_modpagamento(&$conn,&$rpc){


}


function sync_articoli(&$conn,&$rpc){
	global $listver;
	echo "Carico prodotti" . date('Y-m-d H:i:s') ."\n";
	$listver= 2;
	$sql="SELECT articoli.CODICE as codice, GRUPPO as gruppo, descrizion as nome,PREZZOLIST as prezzolistino,PREZZONETT as prezzonetto,PREZZO_1 as prezzofinale, COSTO_ULT as costo, PERCRICAR1 as scontocliente ,COSTODACON as condizione,  COD_ART_FO as codice_fornitore, fornitori.RAG_SOC as fornitore, ALIQUOTA as aliquota, GRUPPO as gruppo, BARCODE1 as barcode FROM odoo.articoli left JOIN odoo.fornitori on fornitori.MMCC = MMCC_FORAB AND fornitori.SSSS = SSSS_FORAB left JOIN odoo.artpagc ON articoli.codice = artpagc.CODICE;";
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	echo "Carico prodotti: fine query" . date('Y-m-d H:i:s') ."\n";
	while($row = mysqli_fetch_object($ids))
	{
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
		   , 'categ_id' => isset($idcategory[0])?$idcategory[0]:""
			, 'type' => 'product'
			, 'name' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
			,'ean13'=>$row->barcode
			, 'description' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
		 	, 'default_code' => $row->codice
		 	, 'list_price' => str_replace(',', '.', $row->prezzolistino)
		 	, 'seller_id' =>  isset($idfornitore[0])?$idfornitore[0]:""
		 	, 'seller_qty' => 1
		 	, 'seller_delay' => 1
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
			, 'description' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
		 	, 'default_code' => $row->codice
		 	, 'list_price' => str_replace(',', '.', $row->prezzonetto)
		 	, 'seller_id' =>  isset($idfornitore[0])?$idfornitore[0]:""
		 	, 'seller_qty' => 1
		 	, 'seller_delay' => 1
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
			, 'description' =>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
		 	, 'default_code' => $row->codice
		 	, 'list_price' => str_replace(',', '.', $row->prezzofinale)
		 	, 'seller_id' =>  isset($idfornitore[0])?$idfornitore[0]:""
		 	, 'seller_qty' => 1
		 	, 'seller_delay' => 1
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
	 	
	 	if(!empty($idfornitore)){
	 	$fornitore= array(
	 	  'product_tmpl_id' => $articoli
	 	, 'name' => $idfornitore[0]);
	 	$fornitori= $rpc->create( $fornitore, "product.supplierinfo");
	 	if ($fornitori == -1){
	 		echo "fornitore";
	 		var_dump($fornitore);
	 		die();
	 	}
	 	
	 	
	 	
	 	}
	 	if($row->condizione==4){
	 		//somma percentuale
	 		$sconto=$row->scontocliente/100;
	 	}else{
	 		//sottrai percentuale
	 		$sconto=-1*($row->scontocliente/100);
	 	
	 	}
	 	
	 	$pricelists=array(
	 		'name'=>  preg_replace('/[^A-Za-z0-9\-\s]/', '',$row->nome)
	 		, 'price_version_id' => $listver
	 		, 'product_tmpl_id' => $articoli
	 		, 'base' => 1
	 		, 'price_discount' => $sconto
	 		);
	 	$pricelist= $rpc->create( $pricelists, "product.pricelist.item");
	 	if ($pricelist== -1){
	 		echo "price list";
	 		var_dump($pricelists);
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



?>
