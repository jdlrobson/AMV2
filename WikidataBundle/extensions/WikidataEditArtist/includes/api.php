<?php

class Api {

  public static function call_api($data, $target='Wikidata') {
    $data['format'] = 'json';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $url = 'https://www.wikidata.org/w/api.php';
    switch ($target) {
      case 'atlasmuseum':
        $url = 'http://publicartmuseum.net/tmp/w/api.php';
        break;
      case 'Commons':
        $url = 'https://commons.wikimedia.org/w/api.php';
        break;
      case 'Wikidata':
        $url = 'https://www.wikidata.org/w/api.php';
        break;
    }
    $url = sprintf("%s?%s", $url, http_build_query($data));
    curl_setopt($curl, CURLOPT_URL, $url);
    return json_decode(curl_exec($curl));
  }

  public static function post_api($parameters) {

    //-- spécifie le format de réponse attendu
    $parameters['format'] = "php";
    
    //-- construit les paramètres à envoyer
    $postdata = http_build_query($parameters);
    
    //-- envoie la requête avec cURL
    $ch = curl_init();
    $cookiefile = tempnam("/tmp", "CURLCOOKIE");
    curl_setopt_array($ch, array(
      CURLOPT_COOKIEFILE => $cookiefile,
      CURLOPT_COOKIEJAR => $cookiefile,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_POST => TRUE
    ));
    curl_setopt($ch, CURLOPT_URL, 'http://publicartmuseum.net/tmp/w/api.php');
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

  public static function get_token() {
    $result = api_post([
      "action"	=> "query",
      "meta"		=> "tokens"
    ]);
    
    return $result["query"]["tokens"]["csrftoken"];
  }

  public static function sparql($query) {
    $parameters = array();
		$parameters['query'] = $query;
		$parameters['format']="json";

		$content = file_get_contents("https://query.wikidata.org/bigdata/namespace/wdq/sparql"."?".http_build_query($parameters));

		return json_decode($content, false);
  }

}