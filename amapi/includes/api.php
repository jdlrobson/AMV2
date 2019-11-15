<?php
/*****************************************************************************
 * api.php
 *
 * API
 *****************************************************************************/

 if (!class_exists('Api')) {

class Api {
  /**
   * Fonction générique appelant l'api
   *
   * @param {Object} $data - Données transmises à l'api
   * @param {string} [$target='Wikidata'] - API cible ('atlasmuseum', 'commons', 'wikidata)
   */
  public static function callApi($data, $target='Wikidata') {
    $data['format'] = 'json';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    switch (strtolower($target)) {
      case 'atlasmuseum':
        $url = ATLASMUSEUM_API;
        break;
      case 'commons':
        $url = COMMONS_API;
        break;
      case 'wikidata':
      default:
        $url = WIKIDATA_API;
        break;
    }
    $url = sprintf("%s?%s", $url, http_build_query($data));

    $url = str_replace('%5Cn', '%0A', $url);
    curl_setopt($curl, CURLOPT_URL, $url);
    return json_decode(curl_exec($curl));
  }

  /**
   * Appel via POST à l'api atlasmuseum
   *
   * @param {Object} $parameters - Données transmises à l'api
   */
  public static function postApi($parameters) {
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

  /**
   * Récupère un token sur l'api atlasmuseum
   *
   * @return {string} Le token
   */
  public static function getToken() {
    $result = self::postApi([
      "action"	=> "query",
      "meta"		=> "tokens"
    ]);
    
    return $result["query"]["tokens"]["csrftoken"];
  }

  /**
   * Requête SPARQL Wikidata
   *
   * @param {string} $query - Requête
   * @return {Object} Résultat de la requête
   */
  public static function sparql($query) {

    $parameters = array();
		$parameters['query'] = $query;
		$parameters['format']="json";

    $url = WIKIDATA_SPARQL . '?' . http_build_query($parameters);

    $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

    // line 2
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //curl_multi_add_handle($multiCurlHandler, $ch);

    $data = curl_exec($ch);
    curl_close($ch);

    return json_decode($data, false);
  }

  /**
   * Convertit un texte Wiki en texte HTML
   *
   * @param {string} $text - Texte à convertir
   * @return {string} Texte converti
   */
  public static function convertToWikiText($text) {
    $text = str_replace('\\n', '\n', $text);

    $data = self::callApi(array(
      'action' => 'parse',
      'text' => $text,
      'contentmodel' => 'wikitext',
      'prop' => 'text',
      'disablelimitreport' => '1'
    ));

    $result = $data->parse->text->{'*'};
    $result = str_replace('<div class="mw-parser-output">', '', $result);
    $result = preg_replace('/<\/div>$/', '', $result);

    return $result;
  }

}

}

?>
