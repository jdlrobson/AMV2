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
  curl_setopt($c, CURLOPT_URL, 'http://publicartmuseum.net/tmp/w/api.php?'.$postdata);
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
    "summary" => "",
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

function convert($page) {
  ob_start();
  get_page($page);
  $content = ob_get_contents();
  ob_end_clean();

  // var_dump($content);

  if ($content != "Erreur") {
    $result = edit($page, $content);
    file_put_contents('./convert.log', $page . "\t" . $result->edit->result . PHP_EOL, FILE_APPEND);
  } else {
    file_put_contents('./convert.log', $page . "\t" . "Erreur" . PHP_EOL, FILE_APPEND);
  }

}

function convert2($title) {
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

  ob_start();

  foreach ($output->query->pages as $page) {
    $content = $page->revisions[0]->{'*'};
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
      if ($line != '[[Page has default form::Artiste| ]]')
        print $line . "\n";
    }
  }

  $content = ob_get_contents();
  ob_end_clean();

  var_dump($title);

  if ($content != "Erreur") {
    $result = edit($title, $content);
    var_dump($result);
    file_put_contents('./convert.log', $title . "\t" . $result->edit->result . PHP_EOL, FILE_APPEND);
  } else {
    file_put_contents('./convert.log', $title . "\t" . "Erreur" . PHP_EOL, FILE_APPEND);
  }

}

function convert_image($title) {
  global
    $semantic,
    $param_convert;

  $output = api_v1_get([
    "action"	=> "query",
    "prop"		=> "revisions",
    "rvlimit" => 1,
    "rvprop"  => "content",
    "titles"  => $title,
    "continue" => ''
  ]);

  $error = false;

  foreach ($output->query->pages as $page) {
    $content = $page->revisions[0]->{'*'};
  }

  if ($content != "Erreur") {
    $result = edit($title, $content);
    var_dump($result);
    file_put_contents('./convert.log', $title . "\t" . $result->edit->result . PHP_EOL, FILE_APPEND);
  } else {
    file_put_contents('./convert.log', $title . "\t" . "Erreur" . PHP_EOL, FILE_APPEND);
  }
}

login();

convert_image("Fichier:Pa00086707.jpg");
convert_image("Fichier:Pablo picasso 1.jpg");
convert_image("Fichier:Pages place.jpg");
convert_image("Fichier:Pages vence imgA.jpg");
convert_image("Fichier:Pages vence imgB1.jpg");
convert_image("Fichier:Pages vence imgB2.jpg");
convert_image("Fichier:PalaisT.jpg");
convert_image("Fichier:Pan duna.jpg");
convert_image("Fichier:Panmarta.jpg");
convert_image("Fichier:Panoramique-nord-Indret.jpg");
convert_image("Fichier:Paola-pivi-how-i-roll-rotating-piper-seneca-21.jpg");
convert_image("Fichier:Partie gauche.jpg");
convert_image("Fichier:Pascalbrateau resurgence.jpg");
convert_image("Fichier:Patie droite.jpg");
convert_image("Fichier:Patkai-7c88f.jpg");
convert_image("Fichier:Paul-mccarthy-paris-fiac-sculpture-2-e1413545004583.jpg");
convert_image("Fichier:Pauline-daniel-euromediterranee-marseille-les-deux-visages-de-la-belle-de-mai-01.jpg");
convert_image("Fichier:Pauline-daniel-euromediterranee-marseille-les-deux-visages-de-la-belle-de-mai-02.jpg");
convert_image("Fichier:Pauline-daniel-euromediterranee-marseille-les-deux-visages-de-la-belle-de-mai-03.jpg");
convert_image("Fichier:Pauline-daniel-euromediterranee-marseille-les-deux-visages-de-la-belle-de-mai-04.jpg");
convert_image("Fichier:Pauline-daniel-euromediterranee-marseille-les-deux-visages-de-la-belle-de-mai-12.jpg");
convert_image("Fichier:Pauline-daniel-euromediterranee-marseille-les-deux-visages-de-la-belle-de-mai-15.jpg");
convert_image("Fichier:Pauline-daniel-euromediterranee-marseille-les-deux-visages-de-la-belle-de-mai-23.jpg");
convert_image("Fichier:PaysageAvecPassante marseille01.jpg");
convert_image("Fichier:Peignot.jpg");
convert_image("Fichier:Penafiel.jpg");
convert_image("Fichier:Performance60.jpg");
convert_image("Fichier:Perilleusement vôtre.jpg");
convert_image("Fichier:Perilously your.jpg");
convert_image("Fichier:Perreaut bis.jpg");
convert_image("Fichier:Petitgand.jpg");
convert_image("Fichier:Petitgand3.jpg");
convert_image("Fichier:Petitgand4.jpg");
convert_image("Fichier:Peut etre I.jpg");
convert_image("Fichier:Philippe Cazal 1995.jpg");
convert_image("Fichier:Philippe Cognee.jpg");
convert_image("Fichier:Philippe portrait2-.jpg");
convert_image("Fichier:Photo-collioure1.JPG");
convert_image("Fichier:Photo-collioure2.JPG");
convert_image("Fichier:Photo-collioure3.JPG");
convert_image("Fichier:Photo-site.jpg");
convert_image("Fichier:Photo-souvenir Les Deux Plateaux, travail in situ permanent pour le Palais-Royal.jpeg");
convert_image("Fichier:Photo1 g.jpg");
convert_image("Fichier:Photo3 ©petit patrimoine.jpg");
convert_image("Fichier:Photo4 ©petit patrimoine.jpg");
convert_image("Fichier:Photo5 ©petit patrimoine.jpg");
convert_image("Fichier:Photo 0--1000192616.jpg");
convert_image("Fichier:Photo 0--1003925566.jpg");
convert_image("Fichier:Photo 0--1011267397.jpg");
convert_image("Fichier:Photo 0--1050750766.jpg");
convert_image("Fichier:Photo 0--1051833742.jpg");
convert_image("Fichier:Photo 0--1053662130.jpg");
convert_image("Fichier:Photo 0--1055137241.jpg");
convert_image("Fichier:Photo 0--1058295544.jpg");
convert_image("Fichier:Photo 0--1079870138.jpg");
convert_image("Fichier:Photo 0--1086701002.jpg");
convert_image("Fichier:Photo 0--1162414415.jpg");
convert_image("Fichier:Photo 0--117291305.jpg");
convert_image("Fichier:Photo 0--1190102914.jpg");
convert_image("Fichier:Photo 0--1203326460.jpg");
convert_image("Fichier:Photo 0--1212456443.jpg");
convert_image("Fichier:Photo 0--1228295384.jpg");
convert_image("Fichier:Photo 0--1230659117.jpg");
convert_image("Fichier:Photo 0--1237406081.jpg");
convert_image("Fichier:Photo 0--1248399296.jpg");
convert_image("Fichier:Photo 0--1255465861.jpg");
convert_image("Fichier:Photo 0--1276135488.jpg");
convert_image("Fichier:Photo 0--1278075300.jpg");
convert_image("Fichier:Photo 0--1278839468.jpg");
convert_image("Fichier:Photo 0--1286033017.jpg");
convert_image("Fichier:Photo 0--1287366440.jpg");
convert_image("Fichier:Photo 0--1288841816.jpg");
convert_image("Fichier:Photo 0--1300154007.jpg");
convert_image("Fichier:Photo 0--1305248083.jpg");
convert_image("Fichier:Photo 0--1314196011.jpg");
convert_image("Fichier:Photo 0--1327036840.jpg");
convert_image("Fichier:Photo 0--1335068559.jpg");
convert_image("Fichier:Photo 0--1352261259.jpg");
convert_image("Fichier:Photo 0--1355503752.jpg");
convert_image("Fichier:Photo 0--1370425462.jpg");
convert_image("Fichier:Photo 0--1372175025.jpg");
convert_image("Fichier:Photo 0--1385631671.jpg");
convert_image("Fichier:Photo 0--1396734800.jpg");
convert_image("Fichier:Photo 0--1414648083.jpg");
convert_image("Fichier:Photo 0--1421986602.jpg");
convert_image("Fichier:Photo 0--1425919648.jpg");
convert_image("Fichier:Photo 0--1427251093.jpg");
convert_image("Fichier:Photo 0--1431962102.jpg");
convert_image("Fichier:Photo 0--1437458726.jpg");
convert_image("Fichier:Photo 0--1437983623.jpg");
convert_image("Fichier:Photo 0--1450308869.jpg");
convert_image("Fichier:Photo 0--1484665383.jpg");
convert_image("Fichier:Photo 0--1495908638.jpg");
convert_image("Fichier:Photo 0--1509872984.jpg");
convert_image("Fichier:Photo 0--151631226.jpg");
convert_image("Fichier:Photo 0--151761492.jpg");
convert_image("Fichier:Photo 0--1528365349.jpg");
convert_image("Fichier:Photo 0--1566835328.jpg");
convert_image("Fichier:Photo 0--157981638.jpg");
convert_image("Fichier:Photo 0--1588906721.jpg");
convert_image("Fichier:Photo 0--1594342547.jpg");
convert_image("Fichier:Photo 0--1598177040.jpg");
convert_image("Fichier:Photo 0--1615617032.jpg");
convert_image("Fichier:Photo 0--1664199025.jpg");
convert_image("Fichier:Photo 0--1685395380.jpg");
convert_image("Fichier:Photo 0--1688925547.jpg");
convert_image("Fichier:Photo 0--1690020376.jpg");
convert_image("Fichier:Photo 0--1695540213.jpg");
convert_image("Fichier:Photo 0--1744690611.jpg");
convert_image("Fichier:Photo 0--1745385972.jpg");
convert_image("Fichier:Photo 0--1745728597.jpg");
convert_image("Fichier:Photo 0--1764734163.jpg");
convert_image("Fichier:Photo 0--1766664861.jpg");
convert_image("Fichier:Photo 0--1784729215.jpg");
convert_image("Fichier:Photo 0--1796583815.jpg");
convert_image("Fichier:Photo 0--1797359541.jpg");
convert_image("Fichier:Photo 0--181771504.jpg");
convert_image("Fichier:Photo 0--1828685203.jpg");
convert_image("Fichier:Photo 0--1835644019.jpg");
convert_image("Fichier:Photo 0--1857299128.jpg");
convert_image("Fichier:Photo 0--186176890.jpg");
convert_image("Fichier:Photo 0--1867002659.jpg");
convert_image("Fichier:Photo 0--1867353730.jpg");
convert_image("Fichier:Photo 0--1868116584.jpg");
convert_image("Fichier:Photo 0--1868824427.jpg");
convert_image("Fichier:Photo 0--187573439.jpg");
convert_image("Fichier:Photo 0--1876852638.jpg");
convert_image("Fichier:Photo 0--1931658387.jpg");
convert_image("Fichier:Photo 0--193328251.jpg");
convert_image("Fichier:Photo 0--1934767434.jpg");
convert_image("Fichier:Photo 0--1956159191.jpg");
convert_image("Fichier:Photo 0--1957571600.jpg");
convert_image("Fichier:Photo 0--1991372977.jpg");
convert_image("Fichier:Photo 0--2007757300.jpg");
convert_image("Fichier:Photo 0--2013233642.jpg");
convert_image("Fichier:Photo 0--2027399820.jpg");
convert_image("Fichier:Photo 0--2030555229.jpg");
convert_image("Fichier:Photo 0--2037215426.jpg");
convert_image("Fichier:Photo 0--2037410732.jpg");
convert_image("Fichier:Photo 0--2051346754.jpg");
convert_image("Fichier:Photo 0--2054080782.jpg");
convert_image("Fichier:Photo 0--2061486239.jpg");
convert_image("Fichier:Photo 0--2070762259.jpg");
convert_image("Fichier:Photo 0--2072042888.jpg");
convert_image("Fichier:Photo 0--208681858.jpg");
convert_image("Fichier:Photo 0--2097895312.jpg");
convert_image("Fichier:Photo 0--2103502445.jpg");
convert_image("Fichier:Photo 0--2117051847.jpg");
convert_image("Fichier:Photo 0--2118844010.jpg");
convert_image("Fichier:Photo 0--2120448944.jpg");
convert_image("Fichier:Photo 0--2134509451.jpg");
convert_image("Fichier:Photo 0--213904331.jpg");
convert_image("Fichier:Photo 0--232979154.jpg");
convert_image("Fichier:Photo 0--239502460.jpg");
convert_image("Fichier:Photo 0--267089347.jpg");
convert_image("Fichier:Photo 0--27937662.jpg");
convert_image("Fichier:Photo 0--281051097.jpg");
convert_image("Fichier:Photo 0--292075289.jpg");
convert_image("Fichier:Photo 0--297529040.jpg");
convert_image("Fichier:Photo 0--299307074.jpg");
convert_image("Fichier:Photo 0--307544611.jpg");
convert_image("Fichier:Photo 0--309368598.jpg");
convert_image("Fichier:Photo 0--340380604.jpg");
convert_image("Fichier:Photo 0--356096816.jpg");
convert_image("Fichier:Photo 0--364104155.jpg");
convert_image("Fichier:Photo 0--366961212.jpg");
convert_image("Fichier:Photo 0--368592835.jpg");
convert_image("Fichier:Photo 0--386761406.jpg");
convert_image("Fichier:Photo 0--39583088.jpg");
convert_image("Fichier:Photo 0--40825411.jpg");
convert_image("Fichier:Photo 0--445738653.jpg");
convert_image("Fichier:Photo 0--453640755.jpg");
convert_image("Fichier:Photo 0--460450509.jpg");
convert_image("Fichier:Photo 0--473192607.jpg");
convert_image("Fichier:Photo 0--498504617.jpg");
convert_image("Fichier:Photo 0--509012112.jpg");
convert_image("Fichier:Photo 0--515345219.jpg");
convert_image("Fichier:Photo 0--523372625.jpg");
convert_image("Fichier:Photo 0--527357697.jpg");
convert_image("Fichier:Photo 0--532795777.jpg");
convert_image("Fichier:Photo 0--554394826.jpg");
convert_image("Fichier:Photo 0--567621857.jpg");
convert_image("Fichier:Photo 0--585703878.jpg");
convert_image("Fichier:Photo 0--592491172.jpg");
convert_image("Fichier:Photo 0--599711569.jpg");
convert_image("Fichier:Photo 0--607059447.jpg");
convert_image("Fichier:Photo 0--607706107.jpg");
convert_image("Fichier:Photo 0--614138978.jpg");
convert_image("Fichier:Photo 0--650099884.jpg");
convert_image("Fichier:Photo 0--659282671.jpg");
convert_image("Fichier:Photo 0--675400843.jpg");
convert_image("Fichier:Photo 0--70095027.jpg");
convert_image("Fichier:Photo 0--70660062.jpg");
convert_image("Fichier:Photo 0--72015422.jpg");
convert_image("Fichier:Photo 0--74814427.jpg");
convert_image("Fichier:Photo 0--757447685.jpg");
convert_image("Fichier:Photo 0--76264487.jpg");
convert_image("Fichier:Photo 0--764592155.jpg");
convert_image("Fichier:Photo 0--773995353.jpg");
convert_image("Fichier:Photo 0--779609655.jpg");
convert_image("Fichier:Photo 0--830767633.jpg");
convert_image("Fichier:Photo 0--832418724.jpg");
convert_image("Fichier:Photo 0--841524780.jpg");
convert_image("Fichier:Photo 0--842112380.jpg");
convert_image("Fichier:Photo 0--853884996.jpg");
convert_image("Fichier:Photo 0--860429853.jpg");
convert_image("Fichier:Photo 0--865116470.jpg");
convert_image("Fichier:Photo 0--897052408.jpg");
convert_image("Fichier:Photo 0--897623211.jpg");
convert_image("Fichier:Photo 0--921077759.jpg");
convert_image("Fichier:Photo 0--926411465.jpg");
convert_image("Fichier:Photo 0--937838325.jpg");
convert_image("Fichier:Photo 0--939005802.jpg");
convert_image("Fichier:Photo 0--95651013.jpg");
convert_image("Fichier:Photo 0--962325775.jpg");
convert_image("Fichier:Photo 0--966612479.jpg");
convert_image("Fichier:Photo 0--968583679.jpg");
convert_image("Fichier:Photo 0--97125802.jpg");
convert_image("Fichier:Photo 0--993220775.jpg");
convert_image("Fichier:Photo 0-1005085576.jpg");
convert_image("Fichier:Photo 0-1010077496.jpg");
convert_image("Fichier:Photo 0-1019493371.jpg");
convert_image("Fichier:Photo 0-1027849113.jpg");
convert_image("Fichier:Photo 0-1035574736.jpg");
convert_image("Fichier:Photo 0-1044623261.jpg");
convert_image("Fichier:Photo 0-1048946147.jpg");
convert_image("Fichier:Photo 0-1056160368.jpg");
convert_image("Fichier:Photo 0-1057473694.jpg");
convert_image("Fichier:Photo 0-1060520280.jpg");
convert_image("Fichier:Photo 0-1067678190.jpg");
convert_image("Fichier:Photo 0-1084653306.jpg");
convert_image("Fichier:Photo 0-1109494096.jpg");
convert_image("Fichier:Photo 0-1113146995.jpg");
convert_image("Fichier:Photo 0-1125153140.jpg");
convert_image("Fichier:Photo 0-1127841022.jpg");
convert_image("Fichier:Photo 0-1143649854.jpg");
convert_image("Fichier:Photo 0-117613455.jpg");
convert_image("Fichier:Photo 0-1178962344.jpg");
convert_image("Fichier:Photo 0-1183671193.jpg");
convert_image("Fichier:Photo 0-1195265456.jpg");
convert_image("Fichier:Photo 0-120118233.jpg");
convert_image("Fichier:Photo 0-1202286728.jpg");
convert_image("Fichier:Photo 0-1208657380.jpg");
convert_image("Fichier:Photo 0-1238483889.jpg");
convert_image("Fichier:Photo 0-1243393004.jpg");
convert_image("Fichier:Photo 0-1262368043.jpg");
convert_image("Fichier:Photo 0-1269721988.jpg");
convert_image("Fichier:Photo 0-1276277044.jpg");
convert_image("Fichier:Photo 0-1288159242.jpg");
convert_image("Fichier:Photo 0-1296620441.jpg");
convert_image("Fichier:Photo 0-1303377805.jpg");
convert_image("Fichier:Photo 0-1318410336.jpg");
convert_image("Fichier:Photo 0-133862616.jpg");
convert_image("Fichier:Photo 0-1367961767.jpg");
convert_image("Fichier:Photo 0-1372428970.jpg");
convert_image("Fichier:Photo 0-1373007105.jpg");
convert_image("Fichier:Photo 0-1388474014.jpg");
convert_image("Fichier:Photo 0-1432259138.jpg");
convert_image("Fichier:Photo 0-1443500546.jpg");
convert_image("Fichier:Photo 0-1454750748.jpg");
convert_image("Fichier:Photo 0-1471075620.jpg");
convert_image("Fichier:Photo 0-1492492658.jpg");
convert_image("Fichier:Photo 0-1492879654.jpg");
convert_image("Fichier:Photo 0-149923051.jpg");
convert_image("Fichier:Photo 0-1513875632.jpg");
convert_image("Fichier:Photo 0-1515383440.jpg");
convert_image("Fichier:Photo 0-1524988909.jpg");
convert_image("Fichier:Photo 0-1549923480.jpg");
convert_image("Fichier:Photo 0-1554409098.jpg");
convert_image("Fichier:Photo 0-156456204.jpg");
convert_image("Fichier:Photo 0-1579623021.jpg");
convert_image("Fichier:Photo 0-1582940425.jpg");
convert_image("Fichier:Photo 0-1593749644.jpg");
convert_image("Fichier:Photo 0-1596410502.jpg");
convert_image("Fichier:Photo 0-1597971470.jpg");
convert_image("Fichier:Photo 0-160148227.jpg");
convert_image("Fichier:Photo 0-1607869303.jpg");
convert_image("Fichier:Photo 0-1608283159.jpg");
convert_image("Fichier:Photo 0-1609026218.jpg");
convert_image("Fichier:Photo 0-1615157647.jpg");
convert_image("Fichier:Photo 0-1636141828.jpg");
convert_image("Fichier:Photo 0-1644725237.jpg");
convert_image("Fichier:Photo 0-1650895077.jpg");
convert_image("Fichier:Photo 0-1651118472.jpg");
convert_image("Fichier:Photo 0-1662300389.jpg");
convert_image("Fichier:Photo 0-1663594811.jpg");
convert_image("Fichier:Photo 0-1701616966.jpg");
convert_image("Fichier:Photo 0-1736792115.jpg");
convert_image("Fichier:Photo 0-1746589734.jpg");
convert_image("Fichier:Photo 0-1749021349.jpg");
convert_image("Fichier:Photo 0-1782913699.jpg");
convert_image("Fichier:Photo 0-178743172.jpg");
convert_image("Fichier:Photo 0-1787434948.jpg");
convert_image("Fichier:Photo 0-1787485399.jpg");
convert_image("Fichier:Photo 0-1790294453.jpg");
convert_image("Fichier:Photo 0-1805266977.jpg");
convert_image("Fichier:Photo 0-1836398944.jpg");
convert_image("Fichier:Photo 0-1853922267.jpg");
convert_image("Fichier:Photo 0-1860113358.jpg");
convert_image("Fichier:Photo 0-1861516974.jpg");
convert_image("Fichier:Photo 0-1888376131.jpg");
convert_image("Fichier:Photo 0-1891170348.jpg");
convert_image("Fichier:Photo 0-1896212120.jpg");
convert_image("Fichier:Photo 0-1905429633.jpg");
convert_image("Fichier:Photo 0-1914222589.jpg");
convert_image("Fichier:Photo 0-1925966732.jpg");
convert_image("Fichier:Photo 0-193204513.jpg");
convert_image("Fichier:Photo 0-1952527845.jpg");
convert_image("Fichier:Photo 0-1986908358.jpg");
convert_image("Fichier:Photo 0-1990212762.jpg");
convert_image("Fichier:Photo 0-1996220924.jpg");
convert_image("Fichier:Photo 0-2005814905.jpg");
convert_image("Fichier:Photo 0-2007695264.jpg");
convert_image("Fichier:Photo 0-2011349811.jpg");
convert_image("Fichier:Photo 0-2011916199.jpg");
convert_image("Fichier:Photo 0-2022310881.jpg");
convert_image("Fichier:Photo 0-202592129.jpg");
convert_image("Fichier:Photo 0-2026789824.jpg");
convert_image("Fichier:Photo 0-2042862804.jpg");
convert_image("Fichier:Photo 0-2057939358.jpg");
convert_image("Fichier:Photo 0-2059233762.jpg");
convert_image("Fichier:Photo 0-2067741586.jpg");
convert_image("Fichier:Photo 0-209135206.jpg");
convert_image("Fichier:Photo 0-2100224030.jpg");
convert_image("Fichier:Photo 0-2108861066.jpg");
convert_image("Fichier:Photo 0-2124883921.jpg");
convert_image("Fichier:Photo 0-2134003894.jpg");
convert_image("Fichier:Photo 0-2143598389.jpg");
convert_image("Fichier:Photo 0-22086011.jpg");
convert_image("Fichier:Photo 0-221953235.jpg");
convert_image("Fichier:Photo 0-234919457.jpg");
convert_image("Fichier:Photo 0-236573573.jpg");
convert_image("Fichier:Photo 0-242418060.jpg");
convert_image("Fichier:Photo 0-251914844.jpg");
convert_image("Fichier:Photo 0-287756600.jpg");
convert_image("Fichier:Photo 0-289779837.jpg");
convert_image("Fichier:Photo 0-303387258.jpg");
convert_image("Fichier:Photo 0-313939306.jpg");
convert_image("Fichier:Photo 0-323775222.jpg");
convert_image("Fichier:Photo 0-339161223.jpg");
convert_image("Fichier:Photo 0-33951786.jpg");
convert_image("Fichier:Photo 0-34357742.jpg");
convert_image("Fichier:Photo 0-348888762.jpg");
convert_image("Fichier:Photo 0-358792858.jpg");
convert_image("Fichier:Photo 0-393161778.jpg");
convert_image("Fichier:Photo 0-411727993.jpg");
convert_image("Fichier:Photo 0-411879486.jpg");
convert_image("Fichier:Photo 0-416310617.jpg");
convert_image("Fichier:Photo 0-420470484.jpg");
convert_image("Fichier:Photo 0-471730479.jpg");
convert_image("Fichier:Photo 0-473980977.jpg");
convert_image("Fichier:Photo 0-478475983.jpg");
convert_image("Fichier:Photo 0-485643303.jpg");
convert_image("Fichier:Photo 0-487087497.jpg");
convert_image("Fichier:Photo 0-492325986.jpg");
convert_image("Fichier:Photo 0-498406093.jpg");
convert_image("Fichier:Photo 0-500420019.jpg");
convert_image("Fichier:Photo 0-516386122.jpg");
convert_image("Fichier:Photo 0-52240285.jpg");
convert_image("Fichier:Photo 0-524601793.jpg");
convert_image("Fichier:Photo 0-533670392.jpg");
convert_image("Fichier:Photo 0-5386226.jpg");
convert_image("Fichier:Photo 0-546255649.jpg");
convert_image("Fichier:Photo 0-558335022.jpg");
convert_image("Fichier:Photo 0-567240285.jpg");
convert_image("Fichier:Photo 0-598373238.jpg");
convert_image("Fichier:Photo 0-600256626.jpg");
convert_image("Fichier:Photo 0-601413292.jpg");
convert_image("Fichier:Photo 0-613306475.jpg");
convert_image("Fichier:Photo 0-613417242.jpg");
convert_image("Fichier:Photo 0-626479782.jpg");
convert_image("Fichier:Photo 0-632028116.jpg");
convert_image("Fichier:Photo 0-632697338.jpg");
convert_image("Fichier:Photo 0-636022930.jpg");
convert_image("Fichier:Photo 0-63744904.jpg");
convert_image("Fichier:Photo 0-644866248.jpg");
convert_image("Fichier:Photo 0-644984156.jpg");
convert_image("Fichier:Photo 0-660748513.jpg");
convert_image("Fichier:Photo 0-664399780.jpg");
convert_image("Fichier:Photo 0-702619632.jpg");
convert_image("Fichier:Photo 0-750395949.jpg");
convert_image("Fichier:Photo 0-76248175.jpg");
convert_image("Fichier:Photo 0-773582416.jpg");
convert_image("Fichier:Photo 0-77677119.jpg");
convert_image("Fichier:Photo 0-777815986.jpg");
convert_image("Fichier:Photo 0-77792482.jpg");
convert_image("Fichier:Photo 0-799014390.jpg");
convert_image("Fichier:Photo 0-804157097.jpg");
convert_image("Fichier:Photo 0-814867777.jpg");
convert_image("Fichier:Photo 0-818783058.jpg");
convert_image("Fichier:Photo 0-841849028.jpg");
convert_image("Fichier:Photo 0-852104351.jpg");
convert_image("Fichier:Photo 0-880221963.jpg");
convert_image("Fichier:Photo 0-881749811.jpg");
convert_image("Fichier:Photo 0-901001246.jpg");
convert_image("Fichier:Photo 0-930866242.jpg");
convert_image("Fichier:Photo 0-944048512.jpg");
convert_image("Fichier:Photo 0-954908406.jpg");
convert_image("Fichier:Photo 0-976517725.jpg");
convert_image("Fichier:Photo 0-988248867.jpg");
convert_image("Fichier:Photo 1.JPG");
convert_image("Fichier:Photo 1448--101681043.jpg");
convert_image("Fichier:Photo 1454--2102903972.jpg");
convert_image("Fichier:Photo 1494-450611751.jpg");
convert_image("Fichier:Photo 1524--897052408.jpg");
convert_image("Fichier:Photo 1 bdccg80.jpg");
convert_image("Fichier:Photo 2.JPG");
convert_image("Fichier:Photo 2973-1972108111.jpg");
convert_image("Fichier:Photo 3.JPG");
convert_image("Fichier:Photo 3593--580378819.jpg");
convert_image("Fichier:Photo 3593-1995941689.jpg");
convert_image("Fichier:Photo 4.JPG");
convert_image("Fichier:Photo 4068--404763434.jpg");
convert_image("Fichier:Photo 4633-29625677.jpg");
convert_image("Fichier:Photo 5.JPG");
convert_image("Fichier:Photo 5535--1424715737.jpg");
convert_image("Fichier:Photo 5673-71113553.jpg");
convert_image("Fichier:Photo 6.jpg");
convert_image("Fichier:Photo 6762-71113553.jpg");
convert_image("Fichier:Photo 7448--324765629.jpg");
convert_image("Fichier:Photo 7693--937838325.jpg");
convert_image("Fichier:Photo 7759--1430927147.jpg");
convert_image("Fichier:Photo 8260-705610603.jpg");
convert_image("Fichier:Photo 8311--1505145809.jpg");
convert_image("Fichier:Photo 8311-998096218.jpg");
convert_image("Fichier:Photobob.jpg");
convert_image("Fichier:Picto-Wikidata.png");
convert_image("Fichier:Picto-blanc.png");
convert_image("Fichier:Picto-bleu.png");
convert_image("Fichier:Picto-gris.png");
convert_image("Fichier:Picto-jaune.png");
convert_image("Fichier:Picto-rouge.png");
convert_image("Fichier:Pierre Szekely 1994.jpg");
convert_image("Fichier:Pierre di Sciullo Enseigne T copyright P.di Sciullo 4.jpg");
convert_image("Fichier:Piotr Kowalski.jpg");
convert_image("Fichier:Place de la République - Liberté.jpg");
convert_image("Fichier:Plaqueminier.JPG");
convert_image("Fichier:Plateau Californie Kern.JPG");
convert_image("Fichier:Poetemuse.jpg");
convert_image("Fichier:Poings d'eau.jpeg");
convert_image("Fichier:Poirier.jpg");
convert_image("Fichier:Poitiers mjc local 051.jpg");
convert_image("Fichier:Pol7.JPG");
convert_image("Fichier:Polska-1.jpg");
convert_image("Fichier:Polypores Fountain. Jean Yves Lechevallier.jpg");
convert_image("Fichier:Pommereulle.png");
convert_image("Fichier:Pont+Michel+01.jpg");
convert_image("Fichier:Pont.png");
convert_image("Fichier:Porte+Fausse.jpg");
convert_image("Fichier:Portrait of Alexander Calder 1947 July 10.jpg");
convert_image("Fichier:Portrait of Henri Matisse 1933 May 20.jpg");
convert_image("Fichier:Portraits-de-la-comedie-francaise.jpg");
convert_image("Fichier:Poster.jpg");
convert_image("Fichier:Poubelles des frigos.jpeg");
convert_image("Fichier:Poubelles des frigos rangées.jpeg");
convert_image("Fichier:Poussières marseille05.jpg");
convert_image("Fichier:Promenade nutzerfreundlich.JPG");
convert_image("Fichier:PtdetIMGP6146b.jpg");
convert_image("Fichier:R2 1994 lyon 1025 001 zi 06.jpg");
convert_image("Fichier:Rabinowiotchinsitu.jpg");
convert_image("Fichier:Raboniwitch.jpg");
convert_image("Fichier:Radi 3 vases fantomes©G Gardette.jpg");
convert_image("Fichier:Radi dessous de plat©G Gardette.jpg");
convert_image("Fichier:Radi plateau a fromage©G Gardette.jpg");
convert_image("Fichier:Radi poignee de porte©G Gardette.jpg");
convert_image("Fichier:Radi porte fruits et legumes©G Gardette.jpg");
convert_image("Fichier:Radi robot porte fruits et legumes 72.jpg");
convert_image("Fichier:RaetzMarkusPhotoKerguehennec0771 01.JPG");
convert_image("Fichier:Raffray.jpg");
convert_image("Fichier:Rasheed Araeen.jpg");
convert_image("Fichier:Rat.jpg");
convert_image("Fichier:Raynaudriviere.jpg");
convert_image("Fichier:Raysse.jpg");
convert_image("Fichier:Reist.JPG");
convert_image("Fichier:Render.png");
convert_image("Fichier:Render2.png");
convert_image("Fichier:Render3.png");
convert_image("Fichier:Render5.png");
convert_image("Fichier:Resize.jpeg");
convert_image("Fichier:Ressources-galerie.jpeg");
convert_image("Fichier:RiceRoom8.jpg");
convert_image("Fichier:Riga-freedom-monument2.jpg");
convert_image("Fichier:Roberto Paci Dalò - SUD Salon Urbain de Douala 2010 (273).jpg");
convert_image("Fichier:Rod5 photo 003i.jpg");
convert_image("Fichier:Roden.jpg");
convert_image("Fichier:Rodin-cropped.jpg");
convert_image("Fichier:Roger Gaudreau La migration du rhinoceros VdF1999 credit Annick Chretien 2000(2).jpg");
convert_image("Fichier:Roger pfund EPFL.jpg");
convert_image("Fichier:Roman Opalka (1995).png");
convert_image("Fichier:Ross.jpg");
convert_image("Fichier:Ross oiron.jpg");
convert_image("Fichier:Roucou.jpg");
convert_image("Fichier:Rpcq bien 109838 30698.JPG");
convert_image("Fichier:Rutaultoiron.jpg");
convert_image("Fichier:Rutaultoiron2.jpg");
convert_image("Fichier:Ruyant calix1©G Gardette.jpg");
convert_image("Fichier:Ruyant calix 2©G Gardette.jpg");
convert_image("Fichier:Ruyant coquetier©G Gardette.jpg");
convert_image("Fichier:Ruyant ring salade©G Gardette.jpg");
convert_image("Fichier:Ruyant rocky cheese©G Gardette.jpg");
convert_image("Fichier:Ruyant trinity©G Gardette.jpg");
convert_image("Fichier:Ruyant utility©G Gardette.jpg");
convert_image("Fichier:Ruyatn aquafix©G Gardette.jpg");
convert_image("Fichier:Ryszard Litwiniuk Renaissance VdF1998 credit CDT Meuse-2004-07(2).jpg");
convert_image("Fichier:Réenchantement, Jean-Luc Verna, VdF 2010 Photo Sébastien Agnetti (2).jpg");
convert_image("Fichier:SCHIESS.jpg");
convert_image("Fichier:SCREENSHOT IOS Ipad 0 2048x1536.png");
convert_image("Fichier:SUD Salon Urbain de Douala 2010 - 07.JPG");
convert_image("Fichier:SURFACE 1.jpg");
convert_image("Fichier:SURFACE 13.jpg");
convert_image("Fichier:SURFACE 16.jpg");
convert_image("Fichier:SURFACE 21.jpg");
convert_image("Fichier:Saint-Nazaire Port view from Espadon submarine base.JPG");
convert_image("Fichier:Saint Phalle (1964) by Erling Mandelmann.jpg");
convert_image("Fichier:Saksik.jpeg");
convert_image("Fichier:Salvador Dalí 1939.jpg");
convert_image("Fichier:Sanccj001.jpg");
convert_image("Fichier:Sans titre 2013 a.jpg");
convert_image("Fichier:Saphira, Claudia Comte, VdF 2010 Photo Sébastien Agnetti (1).jpg");
convert_image("Fichier:Saussier-projet brancusi 4.jpg");
convert_image("Fichier:Schlegel.jpg");
convert_image("Fichier:Sculpture-acier-IUT-REIMS-1969-Marino-di-Teana.jpg");
convert_image("Fichier:Sculpture-acier-inox-fac-medecine-Paris-Villemin.jpg");
convert_image("Fichier:Sculpture de Visée VI .jpg");
convert_image("Fichier:Sculpture les pianos collège Ravel Toulon (1).jpg");
convert_image("Fichier:Sculpture rives aar.jpg");
convert_image("Fichier:Secondenature.jpg");
convert_image("Fichier:Seite 10.jpg");
convert_image("Fichier:Seite 16.jpg");
convert_image("Fichier:Seite 22.jpg");
convert_image("Fichier:Seite 24.jpg");
convert_image("Fichier:Seite 9.jpg");
convert_image("Fichier:Serpent.JPG");
convert_image("Fichier:Serpentinerouge.jpg");
convert_image("Fichier:Serraphilibert.jpg");
convert_image("Fichier:Shannon.jpg");
convert_image("Fichier:Shapiro,Joel 250211.jpg");
convert_image("Fichier:Shirley Jaffe 1998.jpg");
convert_image("Fichier:Silophone montreal.jpg");
convert_image("Fichier:Sinea ateliers de petrosani 2012 tirage lambdachrome 108 x 138 cm.jpg");
convert_image("Fichier:Site-specific installation by Dan Flavin, 1996, Menil Collection, Houston Texas.JPG");
convert_image("Fichier:Site AM 1505 96.jpg");
convert_image("Fichier:Sixfoursweba.jpg");
convert_image("Fichier:Slide355.jpg");
convert_image("Fichier:Soleil - Forêt - Fête.JPG");
convert_image("Fichier:Soperricorpsdetail.jpg");
convert_image("Fichier:Soperrioironcorps.jpg");
convert_image("Fichier:Sorbonne11.jpg");
convert_image("Fichier:Speedy graphito atlasmuseum.jpg");
convert_image("Fichier:Speedy graphito atlasmuseum1.jpg");
convert_image("Fichier:Spiral-jetty-from-rozel-point.png");
convert_image("Fichier:Spoerri oiron.jpg");
convert_image("Fichier:Stahly.jpg");
convert_image("Fichier:Stampfli.png");
convert_image("Fichier:Stampflidole.jpg");
convert_image("Fichier:Statue of BALZAC, made by RODIN, Paris.jpg");
convert_image("Fichier:Stile creativo ufolism.jpg");
convert_image("Fichier:Stp85529.jpg");
convert_image("Fichier:Stratmann.jpg");
convert_image("Fichier:Street-painting-LangBaumann.jpg");
convert_image("Fichier:Streuli.jpeg");
convert_image("Fichier:Sun Tunnels.jpg");
convert_image("Fichier:Sven Domann Ombre de lune VdF1997 credit Camille Gresset 2013 07 24 (1).JPG");
convert_image("Fichier:Szekely briques-a vin©G Gardette.jpg");
convert_image("Fichier:Szekely briques a fleurs 2©G Gardette.jpg");
convert_image("Fichier:TABLE ET CRUCHE©G Gardette.jpg");
convert_image("Fichier:Tadashi Kawamata, Walkway and Tower 04.jpg");
convert_image("Fichier:Taller Buddha of Bamiyan before and after destruction.jpg");
convert_image("Fichier:Tallon picnic 1©G Gardette.jpg");
convert_image("Fichier:Tallon picnic 2©G Gardette.jpg");
convert_image("Fichier:Tallon picnic 3©G Gardette.jpg");
convert_image("Fichier:Tallon picnic 4©G Gardette.jpg");
convert_image("Fichier:Tallon terrines ovales©G Gardette.jpg");
convert_image("Fichier:Tallon terrines rondes©G Gardette.jpg");
convert_image("Fichier:Tanya Preminger Made by God VdF1999 credit VdF-2007 (1).JPG");
convert_image("Fichier:Tatsuo Inagaki Forest within Forest VdF1999 credit Maryse Gentilhomme-1999 (2).jpg");
convert_image("Fichier:Terje Ojaver Responsabilite VdF1998 credit VdF-2005-05.JPG");
convert_image("Fichier:Test upload2.jpg");
convert_image("Fichier:The-Way-Earthly-Things-Are-Going-Emeka-Ogboh.JPG");
convert_image("Fichier:The Division of Woman and Man.jpg");
convert_image("Fichier:Thierry Devaux Barques VdF2002-credit CDT meuse-2004-07 (3).jpg");
convert_image("Fichier:Thu Van Tran.jpg");
convert_image("Fichier:Tjerrie Verhellen Acrobates Vd1997 credit VdF-2008-03 (4).JPG");
convert_image("Fichier:Toitraouche 600-1424-17.jpg");
convert_image("Fichier:Toroni oiron.jpg");
convert_image("Fichier:Totem de Pierre Theunissen collège de L'estérel à Saint-Raphael.jpg");
convert_image("Fichier:Totems magnolias-Dieleman.jpeg");
convert_image("Fichier:Totems magnolias-Foyé.jpeg");
convert_image("Fichier:Tour7 big.jpg");
convert_image("Fichier:Tram Le Mans - Avenue du Général Leclerc.JPG");
convert_image("Fichier:TransferPrudhomme.JPG");
convert_image("Fichier:Trenet.jpg");
convert_image("Fichier:Triangle Silvio Mattioli 2006.png");
convert_image("Fichier:Trias Silvio Mattioli 1991.png");
convert_image("Fichier:Twisted Cube, Karina Bisch, VdF 2010 Photo Sébastien Agnetti (3).jpg");
convert_image("Fichier:Twopy2.png");
convert_image("Fichier:Twopy22.png");
convert_image("Fichier:Twopy3.png");
convert_image("Fichier:UGNEFT.jpg");
convert_image("Fichier:Un-adulte-bâtit-les-fondations-de-son-bon-sens-sur-la-chute-certaine-dun-objet-qui-na-pas-de-support-©-Émilie-Perotto.jpg");
convert_image("Fichier:UneHirondellenefaitpasleprintemps.JPG");
convert_image("Fichier:Unnamed.jpg");
convert_image("Fichier:VAR428A01a.jpeg");
convert_image("Fichier:VAR431A01.jpeg");
convert_image("Fichier:VARINI VENCE 1.jpg");
convert_image("Fichier:VARINI VENCE 2.jpg");
convert_image("Fichier:VARINI VENCE 3.jpg");
convert_image("Fichier:VARINI VENCE 4.jpg");
convert_image("Fichier:VARINI VENCE 5.jpg");
convert_image("Fichier:Valerio Adami 1995.jpg");
convert_image("Fichier:Varini.jpg");
convert_image("Fichier:Varini oiron détails.jpg");
convert_image("Fichier:Varini oiron vue d'ensemble.jpg");
convert_image("Fichier:Varini vence©Andre.jpg");
convert_image("Fichier:Varinioironensemble.jpg");
convert_image("Fichier:Vasarely-paris-gareM1.JPG");
convert_image("Fichier:Vasarely-paris-gareM2.JPG");
convert_image("Fichier:Veilhan.jpg");
convert_image("Fichier:Verjux.jpg");
convert_image("Fichier:Vignette-collection-cent1.jpg");
convert_image("Fichier:Vignette-collection-cnap.jpg");
convert_image("Fichier:Vincent Bioules 1995.jpg");
convert_image("Fichier:Vincent Lamouroux Aire23 VdF2012 credit Guillaume Onimus (1).jpg");
convert_image("Fichier:Vincent Mauger Le theoreme des dictateurs VdF2009 credit Guillaume Ramon 2013 07 19 (1).JPG");
convert_image("Fichier:Vincent kohler galet01.jpg");
convert_image("Fichier:Visser.jpg");
convert_image("Fichier:Visuel de La Forêt d'Art Contemporain.jpeg");
convert_image("Fichier:Visuel de la collection 1 % artistique.jpeg");
convert_image("Fichier:Vladimir Skoda 2011-07-23 02.jpg");
convert_image("Fichier:Vonier 4.jpg");
convert_image("Fichier:Voss jan.jpg");
convert_image("Fichier:Vue-verticale01-800.jpg");
convert_image("Fichier:Vuk-pac-sweden.jpg");
convert_image("Fichier:Vuk Ćosić 2012.jpg");
convert_image("Fichier:W1kO0ieikT84M3B5-v4y5vDxkJO8VC8gib1-Ka5lhor8LFEZca3EmWM-wuQiCfSFLvAhczVtSwFulQyPT9i6l2 l85rj5MefxQ.jpg");
convert_image("Fichier:W437.35IMG 2232.jpg");
convert_image("Fichier:W 100713 rdl 0860.jpg");
convert_image("Fichier:Walther Piesch Lieu de reve VdF2002 credit Patricia Lion-2006.JPG");
convert_image("Fichier:Web Source0.jpg");
convert_image("Fichier:Webp.net-resizeimage (1).jpg");
convert_image("Fichier:Weiner.jpg");
convert_image("Fichier:Windfahne.jpg");
convert_image("Fichier:Wpid-1248381486image web full.jpg");
convert_image("Fichier:Xavier Veilhan.jpg");
convert_image("Fichier:YANE1.jpg");
convert_image("Fichier:YANE2012.jpg");
convert_image("Fichier:Yaacov Agam.JPG");
convert_image("Fichier:Yayoi Kusama Obliteration Room 2012 600 kids of dada article grande.jpg");
convert_image("Fichier:Yushin chang intrusion vdf2014@Hadrien FRANCESCHINI.JPG");
convert_image("Fichier:Yves-Klein-portrait-.jpg");
convert_image("Fichier:Zao Wou-Ki.jpg");
convert_image("Fichier:Zao Wou-Ki1.jpg");
convert_image("Fichier:ZoneImmaterielKlein2.jpg");
convert_image("Fichier:©Martine Locatelli-CNAP.jpg");
convert_image("Fichier:©Martine Locatelli CNAP.jpg");
convert_image("Fichier:© parisart.jpg");
convert_image("Fichier:© serrano.jpg");
convert_image("Fichier:©arte.tv.jpg");
convert_image("Fichier:©elger esser.jpg");
