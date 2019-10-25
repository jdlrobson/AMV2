<?php
/***********************************
 * API
 ***********************************/

$endPoint = "https://www.wikidata.org/w/api.php";
$amLogin = "Atlasmuseum";
$amPass = "abywarburg";
$cookiefile = "/tmp/cookie.txt";

function api_post($parameters) {
  global
    $endPoint,
    $cookiefile;

  //-- spécifie le format de réponse attendu
  $parameters['format'] = "php";
  
  //-- construit les paramètres à envoyer
  $postdata = http_build_query($parameters);
  
  //-- envoie la requête avec cURL
  $ch = curl_init();
  // $cookiefile = tempnam("/tmp", "CURLCOOKIE");
  curl_setopt_array($ch, array(
    CURLOPT_COOKIEFILE => $cookiefile,
    CURLOPT_COOKIEJAR => $cookiefile,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => TRUE
  ));
  curl_setopt($ch, CURLOPT_URL, $endPoint);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

  //-- envoie les cookies actuels avec curl
  $cookies = array();
  foreach ($_COOKIE as $key => $value)
    if ($key != 'Array')
      $cookies[] = $key . '=' . $value;
  curl_setopt( $ch, CURLOPT_COOKIE, implode(';', $cookies) );
  
  //-- arrête la session en cours
  session_write_close();
  
  $result = unserialize(curl_exec($ch));
  curl_close($ch);
  
  //-- redémarre la session
  session_start();
  
  return $result;
}

function getLoginToken() {
  global
    $endPoint,
    $cookiefile;

	$params1 = [
		"action" => "query",
		"meta" => "tokens",
		"type" => "login",
		"format" => "json"
	];

	$url = $endPoint . "?" . http_build_query( $params1 );

	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookiefile );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookiefile );

	$output = curl_exec( $ch );
	curl_close( $ch );

  $result = json_decode( $output, true );

	return $result["query"]["tokens"]["logintoken"];
}

function loginRequest( $logintoken ) {
  global
    $endPoint,
    $amLogin,
    $amPass,
    $cookiefile;

	$params2 = [
		"action" => "login",
		"lgname" => $amLogin,
		"lgpassword" => $amPass,
		"lgtoken" => $logintoken,
		"format" => "json"
	];

  $ch = curl_init();

	curl_setopt( $ch, CURLOPT_URL, $endPoint );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params2 ) );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookiefile );
  curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookiefile );

	$output = curl_exec( $ch );
  curl_close( $ch );

  return $output;
}

function login() {  
  $login_Token = getLoginToken();
  $result = loginRequest( $login_Token );
  return $result;
}

function getEditToken() {
  $result = api_post([
    "action"	=> "query",
    "meta"		=> "tokens"
  ]);

  return $result['query']['tokens']['csrftoken'];
}

function getData($id) {
  $result = api_post([
    "action" => "wbgetentities",
    "ids" => $id,
    "languages" => "fr",
  ]);

  return $result['entities'][$id];
}

function labelExists($data, $language) {
  return (isset($data['labels']) && isset($data['labels'][$language]));
}

function descriptionExists($data, $language) {
  return (isset($data['descriptions']) && isset($data['descriptions'][$language]));
}

function claimCoordinatesExists($data) {
  return (isset($data['claims']) & isset($data['claims']['P625']));
}

function claimItemExists($data, $property, $value) {
  if (!isset($data['claims']) || !isset($data['claims'][$property]))
    return false;

  for ($i=0; $i < sizeof($data['claims'][$property]); $i++) {
    if ($data['claims'][$property][$i]['mainsnak']['datavalue']['value']['id'] == $value)
      return true;
  }
  
  return false;
}

function editItem($id, $data) {
  $token = getEditToken();

  $params = [
    "action" => "wbeditentity",
    "data" => json_encode($data),
    "token" => $token,
  ];

  if ($id != '')
    $params['id'] = $id;
  else
    $params['new'] = 'item';

  $result = api_post($params);

  if ($id != '')
    return $id;
  else
    return $result['entity']['id'];

  return $result;
}
