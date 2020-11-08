<?php
/*****************************************************************************
 * api.php
 *
 * API
 *****************************************************************************/

define('BASE_URL', 'http://atlasmuseum.net/');
define('MISSING_IMAGE_THUMBNAIL', BASE_URL . 'w/images/5/5f/Image-manquante.jpg');
define('MISSING_IMAGE_FILE', 'Fichier:Image-manquante.jpg');
define('MISSING_IMAGE_URL', BASE_URL . 'wiki/' . MISSING_IMAGE_FILE);

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
    $url = sprintf("%s?%s", $url, http_build_query($data, null, '&', PHP_QUERY_RFC3986));

    $url = str_replace('%5Cn', '%0A', $url);
    $url = str_replace('%253D', '%3D', $url);

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
    $result = str_replace('class="external free"', 'class="external free" target="_blank"', $result);
    $result = str_replace('class="external text"', 'class="external text" target="_blank"', $result);

    return $result;
  }

  /**
   * Récupère les labels d'un ensemble d'ids sur Wikidata
   */
  public static function getLabels($ids) {
    $labels = [];
    // Subdivise le tableau des items à traiter en sous-tableaux de max 50 entrées
    // afin de ne pas dépasser la limite de l'api Wikidata
    $split_ids = array_chunk($ids, 50);

    for ($i=0; $i<sizeof($split_ids); $i++) {
      
      $iterate = false;
      do {
        $labels_data = Api::callApi(array(
          'action' => 'wbgetentities',
          'props' => 'labels',
          'ids' => join($split_ids[$i], '|')
        ));

        // Si l'un des éléments n'existe pas sur WD, l'api retourne une erreur :
        // il faut l'enlever et recommencer la requête
        if (!is_null($labels_data->error) && $labels_data->error->code === 'no-such-entity') {
          $errorId = $labels_data->error->id;
          array_splice($split_ids[$i], array_search($errorId, $split_ids[$i]), 1);
          $iterate = true;
        } else
          $iterate = false;
      } while($iterate);

      if (isset($labels_data->entities)) {
        foreach($labels_data->entities as $id=>$value) {
          if (isset($value->labels->fr)) {
            $labels[$id] = $value->labels->fr->value;
          } else
          if (isset($value->labels->en)) {
            $labels[$id] = $value->labels->en->value;
          }
        }
      }
    }

    return $labels;
  }

  public static function getImageWD($image, $width=320) {
    return Api::callApi(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => 'File:'.$image
    ), 'Commons');
  }

  public static function getImageAM($image, $width=320) {
    return Api::callApi(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => 'File:'.$image
    ), 'atlasmuseum');
  }

  public static function ask($queryString, $queryParameters) {
    $offset = 0;
    $limit = 5000;
    $results = [];

    $queryParameters['limit'] = $limit;

    foreach ($queryParameters as $key => $value) {
      $queryString .= '|' . $key . '=' . $value;
    }

    $continue = false;
    do {
      // Requête à l'API
      $parameters = [
        'action' => 'ask',
        'query' => $queryString . '|offset=' . $offset
      ];
      $tmpData = self::callApi($parameters, 'atlasmuseum');

      if (!is_null($tmpData)) {
        // Doit-on continuer la query avec un offset ?
        if (property_exists($tmpData, 'query-continue-offset')) {
          $continue = true;
          $offset += $limit;
        } else
          $continue = false;

        // Notices
        if (property_exists($tmpData, 'query') && property_exists($tmpData->query, 'results'))
          $results = array_merge($results, $tmpData->query->results);
      } else {
        $continue = false;
      }
    } while ($continue && $offset < 4000);

    return $results;
  }

}

}

?>
