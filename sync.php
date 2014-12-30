<?php

/*
 * @Author : Marco Longo
 * @Email : marchrist85@gmail.com
 * @Country : Italy
 * @Date : 19 Dic 2014
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

$conn = new mysqli($config['dbhost'], $config['dbuser'], $config['dbpassword'], $config['dbname'] );
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "\nConnected successfully\n\n";

$rpc = new OpenERP();
$x = $rpc->login("admin", "m1e2s3h4-", "meshcom", $config['odoourl'] . "/xmlrpc/2/");




sync_user($conn,$rpc);
sync_operator($conn,$rpc);
	


die();

function sync_operator(&$conn,&$rpc){
$operator=include('config.operator.php');
foreach($operator as $key => $value)
	{
	echo $key;
	$sql="SELECT * FROM `operator_users` WHERE `operator_id` =". $key;
	$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
	while($row = mysqli_fetch_object($ids)){
		if ($row->user_id=="") continue;
		$sql="\nSELECT * FROM `user_openwisp_odoo` WHERE `uid_openwisp_id` =". $row->user_id;
		echo $sql;
		$result= mysqli_query($conn, $sql) or die("\nError 01: " . $conn->error . "\n");
		if($result->num_rows>0){
			$resuser=mysqli_fetch_object($result);
			$userid = $rpc->write(array($resuser->uid_odoo_id),array('user_id'=>$value),"res.partner" );
		}		
		}
	}

}

function sync_user(&$conn,&$rpc){
	$sql="SELECT products.name as `product_name`, usrprod.product_id, u.id, `email`, `given_name`, `surname`, `birth_date`, `state`, `city`, `address`, `zip`, `username`, `mobile_prefix`, `mobile_suffix`, `verified`, `notes`, `tax_code`, `vat_number`, `iban`, `cpe_template_id`, `pg_ragione_sociale`, `pg_partita_iva`, u.created_at, `pg_indirizzo`, `pg_cap`, `pf_cf`, `pf_luogo_di_nascita`, `inst_indirizzo`, `inst_cap`, `inst_cpe_modello`, `inst_cpe_username`, `inst_cpe_password`, `inst_cpe_mac`, `is_company`, `pg_comune`, `has_credits` FROM `users` as u INNER JOIN (`user_products` as usrprod INNER JOIN `products` as products on usrprod.product_id = products.id)on u.id = usrprod.user_id WHERE u.id NOT IN (SELECT uid_openwisp_id FROM user_openwisp_odoo)";
$ids = mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");
while($row = mysqli_fetch_object($ids))
	{
		//var_dump($row);
		//echo $row->surname;
	 	//echo $rpc->create( array('name'=>$row->surname), "res.partner");
	 	
	 	$user = array(
	  	'name'=>$row->given_name. " ".$row->surname
	 	, 'city' => $row->city
	 	, 'contact_address' => $row->address . " " . $row->city . " " . $row->zip . " " .$row->state
	 	, 'customer' => true
	 	, 'display_name' => $row->given_name. " ".$row->surname
	 	, 'email' => $row->email
	 	, 'employee' => false
		, 'vat' => $row->pg_partita_iva
		, 'date' => $row->created_at
	 	, 'is_company' => $row->is_company?true:false
	 	, 'lang' => 'it_IT'
	 	, 'mobile' => $row->mobile_prefix .  $row->mobile_suffix
	 	, 'notify_email' => 'always'
	 	, 'street' => $row->address
	 	, 'type' => 'contact'
	 	, 'tz' => 'Europe/Rome'
	 	, 'tz_offset' => '+0100'
	 	, 'zip' => $row->zip
	 	, 'country_id' => 110
	 	, 'credit_limit' => "0"
	    , 'state_id' => "53"
		, 'property_account_position' => 1
	 //	, 'property_stock_supplier' => array("8","Partner Locations/Suppliers") 
	 //	, 'section_id' => array("1","Direct Sales") 
	 	);
	 	$userid = $rpc->create( $user, "res.partner");
		if($userid!= -1){
		create_bank_account($conn,$rpc,$row->id,$userid,$row->iban );
		}
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
