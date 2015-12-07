<?php

	$domain = $_POST['domain'];
	$tlds = $_POST['tlds'];
	
	if($domain!="" && count($tlds)==0) die("no values");

	include("pointer_api.php");
	
	$api = new pointer_api();
	$api->login('YOUR_API_USERNAME' , 'YOUR_API_PASSWORD');
	$results = $api->domainCheck($domain, $tlds);
	$api->logout();
	
	foreach($results as $domain=>$available) {
		
		echo $domain .' '. ($available==1 ? "<b>is available</b>" : "is not available") . "<br />";
		
	}

?>
