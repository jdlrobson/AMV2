<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (isset($_GET["latitude"]) && isset($_GET["longitude"])) {
	$latitude  = $_GET["latitude"];
	$longitude = $_GET["longitude"];
	
	$place = array();

	$response = json_decode(file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?language=fr&key=AIzaSyDdtlAVCL9s3s5SLxPIa58ueeXQIsKv-UU&latlng='.$latitude.','.$longitude));
	
	if (isset($response->results[0])) {

		foreach($response->results[0]->address_components as $address)
			if (isset($address->types[1]) && $address->types[1] == 'political')
				$place[$address->types[0]] = $address->long_name;
	
	}
	
	print json_encode($place);
}

?>
