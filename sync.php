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
QUERY
SELECT products.name, usrprod.product_id,`email`, `given_name`, `surname`, `birth_date`, `state`, `city`, `address`, `zip`, `username`, `mobile_prefix`, `mobile_suffix`, `verified`, `notes`, `tax_code`, `vat_number`, `iban`, `cpe_template_id`, `pg_ragione_sociale`, `pg_partita_iva`, `pg_indirizzo`, `pg_cap`, `pf_cf`, `pf_luogo_di_nascita`, `inst_indirizzo`, `inst_cap`, `inst_cpe_modello`, `inst_cpe_username`, `inst_cpe_password`, `inst_cpe_mac`, `is_company`, `pg_comune`, `has_credits` FROM `users` as u INNER JOIN (`user_products` as usrprod INNER JOIN `products` as products on usrprod.product_id = products.id)on u.id = usrprod.user_id  


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

//print_r($rpc->get_fields('res.partner'));
//$data = $rpc->read(array(21), array(), 'res.partner');
//$data = $rpc->searchread(  array(array('name','=','%Annalisa%')),  "res.partner");


//echo json_encode($data);



$sql = "SELECT products.name as `product_name`, usrprod.product_id, u.id, `email`, `given_name`, `surname`, `birth_date`, `state`, `city`, `address`, `zip`, `username`, `mobile_prefix`, `mobile_suffix`, `verified`, `notes`, `tax_code`, `vat_number`, `iban`, `cpe_template_id`, `pg_ragione_sociale`, `pg_partita_iva`, `pg_indirizzo`, `pg_cap`, `pf_cf`, `pf_luogo_di_nascita`, `inst_indirizzo`, `inst_cap`, `inst_cpe_modello`, `inst_cpe_username`, `inst_cpe_password`, `inst_cpe_mac`, `is_company`, `pg_comune`, `has_credits` FROM `users` as u INNER JOIN (`user_products` as usrprod INNER JOIN `products` as products on usrprod.product_id = products.id)on u.id = usrprod.user_id";
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
 	, 'is_company' => $row->is_company?true:false
 	, 'lang' => 'it_IT'
 	, 'mobile' => $row->mobile_prefix .  $row->mobile_suffix
 	, 'notify_email' => 'always'
 	, 'street' => $row->address
 	, 'type' => 'contact'
 	, 'tz' => 'Europe/Rome'
 	, 'tz_offset' => '+0100'
 	, 'zip' => $row->zip
 	, 'country_id' => "110"
 	, 'credit_limit' => "0"
   , 'state_id' => "53"
 //	, 'property_stock_supplier' => array("8","Partner Locations/Suppliers") 
 //	, 'section_id' => array("1","Direct Sales") 
 	);
 	 	
 	
 	$userid = $rpc->create( $user, "res.partner");
 	
 	$sql= "INSERT INTO `owums_db_new`.`user_openwisp_odoo` (`uid_openwisp_id`, `uid_odoo_id`, `created_at`, `updated_at`) VALUES ('". $row->id ."', '".$userid ."', NOW(), NOW());";
	mysqli_query($conn, $sql) or die("\nError 01: " . mysql_error() . "\n");

	$bank = array(
 	'acc_number' => $row->iban
 	, 'partner_id' => $userid
 	, 'state' => 'iban'
 	
 	);
	
	$bankid = $rpc->create( $bank, "res.partner.bank");
 	
	
}

die();



?>
