<?php

$semantic = [
  'Portrait' => '',
	'nom' => 'Nom de l\'artiste',
	'abstract' => 'abstract',
	'dateofbirth' => 'Date de naissance',
	'birthplace' => 'Lieu de naissance',
  'deathplace' => 'Lieu de décès',
  'deathDate' => 'Date de décès',
  'nationality' => 'Nationalité',
  'movement' => 'Mouvement', 
  'isprimarytopicof' => 'isPrimaryTopicOf',
  'societe_gestion_droit_auteur' => 'societe_gestion_droit_auteur',
  'nom_societe_gestion_droit_auteur' => 'nom_societe_gestion_droit_auteur',
];

$param_convert = [
  'prenom_de_l\'artiste' => 'prenom',
  'nom_de_l\'artiste'=> 'nom',
  'birthplace' => 'birthplace',
  'dateofbirth' => 'dateofbirth',
  'nationality'=> 'nationality',
  'isprimarytopicof' => 'isprimarytopicof',
  'societe_gestion_droit_auteur' => 'societe_gestion_droit_auteur',
];

$multiple = [];

function api_get($parameters) {
  $parameters['format'] = "json";
  $postdata = http_build_query($parameters);

  $c = curl_init();
  curl_setopt($c, CURLOPT_URL, 'http://publicartmuseum.net/w/api.php?'.$postdata);
  curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($c, CURLOPT_HEADER, false);
  $output = curl_exec($c);

  return json_decode($output);
}

function api_v1_get($parameters) {
  $parameters['format'] = "json";
  $postdata = http_build_query($parameters);

  $c = curl_init();
  curl_setopt($c, CURLOPT_URL, 'http://publicartmuseum.net/w/api.php?'.$postdata);
  curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($c, CURLOPT_HEADER, false);
  $output = curl_exec($c);

  return json_decode($output);
}

function api_post($parameters) {

  //-- spécifie le format de réponse attendu
  $parameters['format'] = "json";
  
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
  curl_setopt($ch, CURLOPT_URL, 'http://publicartmuseum.net/w/api.php');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
  
  //-- envoie les cookies actuels avec curl
  $cookies = array();
  foreach ($_COOKIE as $key => $value)
    if ($key != 'Array')
      $cookies[] = $key . '=' . $value;
  curl_setopt( $ch, CURLOPT_COOKIE, implode(';', $cookies) );
  
  //-- arrête la session en cours
  session_write_close();
  
  $result = json_decode(curl_exec($ch));
  curl_close($ch);
  
  //-- redémarre la session
  session_start();
  
  return $result;

}

function get_token() {
  $result = api_post([
    "action"	=> "query",
    "meta"		=> "tokens"
  ]);

  return $result->query->tokens->csrftoken;
}

function login() {
  $user = 'François';
  $pass = 'Juillet14';
  
  //-- envoie une première requête de login
  $result = api_post([
    "action" => "login",
    "lgname" => $user,
    "lgpassword" => $pass
  ]);

  //-- si tout se passe bien, l'api renvoie un token de connexion à lui retransmettre
  if ($result->login->result == "NeedToken") {
    if (!empty($result->login->token)) {
      $token = $result->login->token;
      $_SESSION["logintoken"] = $token;

      //-- renvoie ce token
      $result = api_post([
        "action" => "login",
        "lgname" => $user,
        "lgpassword" => $pass,
        "lgtoken" => $token
      ]);
    }
  }

  return $result;
}

function edit($page, $text) {
  $result = api_post([
    "action" => "edit",
    "title" => $page,
    "text" => $text,
    "summary" => "{{Notice d'œuvre}}",
    "token" => get_token()
  ]);

  return $result;
}

function get_page($title) {
  global
    $semantic,
    $param_convert;

  $output = api_get([
    "action"	=> "query",
    "prop"		=> "revisions",
    "rvlimit" => 1,
    "rvprop"  => "content",
    "titles"  => $title,
    "continue" => ''
  ]);

  $error = false;

  $values = [];

  foreach ($output->query->pages as $page) {
    $content = $page->revisions[0]->{'*'};
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
      if ($line != '{{Artiste' && $line != '}}') {
        if (preg_match('/#^[\s]*\|[\s]*[^=]*[\s]*=[\s]*/', $line)) {
          $error  =true;
          break;
        } else {
          $parameter = preg_replace('/^[\s]*\|[\s]*([^=]*)[\s]*=[\s]*.*$/', '$1', $line);
          $parameter = strtolower(str_replace(' ', '_', $parameter));
          $parameter = str_replace('é', 'e', $parameter);
          $parameter = str_replace('œ', 'oe', $parameter);
          $parameter = str_replace('î', 'i', $parameter);
          if (array_key_exists($parameter, $param_convert))
            $parameter=$param_convert[$parameter];
          $value = preg_replace('/^[\s]*\|[\s]*[^=]*[\s]*=[\s]*(.*)$/', '$1', $line);
          $value = str_replace('"', '&quot;', $value);

          if (array_key_exists($multiple[$parameter])) {
            $value = str_replace(';', '\;', $value);
            $value = str_replace(', ', ';', $value);
          }

          if ($value != "")
            $values[$parameter] = $value;
        }
      }
    }
  }

  if ($error)
    print "Erreur";
  else {
    print "<ArtistPage\nq=\"\"\n";
    foreach ($values as $key=>$value)
      print $key . '="' . $value . "\"\n";
    print "/>\n";

    print '<div style="visibility:hidden; height:0px">'."\n";
    foreach ($values as $key=>$value)
      if (isset($semantic[$key]))
      print "[[" . $semantic[$key] . "::" . $value . "]]\n";
    print '</div>'."\n";
    
    print "[[Category:Artistes]]\n";
    print "[[Page has default form::Artiste| ]]\n";
  }
}

function convert_back($title) {
  $output = api_get([
    "action"	=> "query",
    "prop"		=> "revisions",
    "rvlimit" => 1,
    "rvprop"  => "content",
    "titles"  => $title,
    "continue" => ''
  ]);

  $error = false;

  ob_start();

  foreach ($output->query->pages as $page) {
    $content = $page->revisions[0]->{'*'};
    $lines = explode("\n", $content);
    print "{{Notice d'œuvre\n";
    foreach ($lines as $line) {
      if ($line === "{{Notice d'œuvre") {
        $content = ob_get_contents();
        ob_end_clean();
        print "<li>" . $title . " : déjà convertie</li>\n";
        return;
      }
      if (preg_match('/^[^=]+=".*"$/', $line)) {
        $key = preg_replace('/^([^=]+)=".*$/', '$1', $line);
        if ($key == 'q')
          $key = 'wikidata';
        $value = preg_replace('/^[^=]+="(.*)"$/', '$1', $line);
        $value = preg_replace('/,[\s]*$/', '', $value);
        if ($value !== '')
          print "|" . $key . "=" . $value . "\n";
      }
    }
    print "}}";
  }

  $content = ob_get_contents();
  ob_end_clean();


  if ($content != "Erreur") {
    $result = edit($title, $content);
    print "<li>" . $title . " : " . $result->edit->result . "</li>\n";
    file_put_contents('./convert.log', $title . "\t" . $result->edit->result . PHP_EOL, FILE_APPEND);
  } else {
    print "<li>" . $title . " : erreur</li>\n";
    file_put_contents('./convert.log', $title . "\t" . "Erreur" . PHP_EOL, FILE_APPEND);
  }

}

login();

print '<ul>';
convert_back('Le Pot doré (Jean Pierre Raynaud)');
convert_back('Stèle (Jean Pierre Raynaud)');
convert_back('Zuhaitz (Eduardo Chillida)');
print '</ul>';
