<?php

if (!class_exists('Api')) {

class Api {

  public static function call_api($data, $target='Wikidata') {
    $data['format'] = 'json';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $url = WIKIDATA_API;
    switch ($target) {
      case 'atlasmuseum':
        $url = ATLASMUSEUM_API;
        break;
      case 'Commons':
        $url = COMMONS_API;
        break;
      case 'Wikidata':
        $url = WIKIDATA_API;
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
    curl_setopt($ch, CURLOPT_URL, ATLASMUSEUM_API);
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
    $result = self::post_api([
      "action"	=> "query",
      "meta"		=> "tokens"
    ]);
    
    return $result["query"]["tokens"]["csrftoken"];
  }

  public static function sparql($query) {

    $parameters = array();
		$parameters['query'] = $query;
		$parameters['format']="json";

    //$content = file_get_contents("https://query.wikidata.org/bigdata/namespace/wdq/sparql"."?".http_build_query($parameters));
    
    $url = WIKIDATA_SPARQL . http_build_query($parameters);

    $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

    // line 2
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //curl_multi_add_handle($multiCurlHandler, $ch);

    $data = curl_exec($ch);
    curl_close($ch);

    return json_decode($data, false);


/*
    $parameters = array();
		$parameters['query'] = $query;
		$parameters['format']="json";

    //$content = file_get_contents("https://query.wikidata.org/bigdata/namespace/wdq/sparql"."?".http_build_query($parameters));
    
    $url = "https://query.wikidata.org/bigdata/namespace/wdq/sparql"."?".http_build_query($parameters);

    $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

    $ch = curl_init($query);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

    // line 2
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_multi_add_handle($multiCurlHandler, $ch);

    $data = curl_exec($ch);
    curl_close($ch);

    //var_dump($data);
    //exit;
    return json_decode($data, false);
    */
  }

}

}
