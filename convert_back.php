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
convert_back('Sagesse (Yasuo Mizui)');
convert_back('Salle de départs (Ettore Spalletti,Guido Santi)');
convert_back('Salle des départs (Ettore Spalletti)');
convert_back('Salle des plates peintures (Claude Rutault)');
convert_back('Salt (Saburo Muraoka)');
convert_back('Salut pour tous, encore des agapes à moratoire orphique (Théodore Fivel)');
convert_back('Sanctuaire de la nature (Herman de Vries)');
convert_back('Sandwich Sound System (Julien Celdran)');
convert_back('Sans titre (2) (Toni Grand)');
convert_back('Sans titre (Adrian Schiess)');
convert_back('Sans titre (Alain Séchas)');
convert_back('Sans titre (Albert Ayme, Villeneuve-d\'Ascq)');
convert_back('Sans titre (Alberto Guzmán, Bordeaux)');
convert_back('Sans titre (Alex Majoli)');
convert_back('Sans titre (Alfred Manessier, Céret)');
convert_back('Sans titre (Alfred Manessier, Saint-Dié-des-Vosges)');
convert_back('Sans titre (André Bloc, Toulouse)');
convert_back('Sans Titre (André Borderie)');
convert_back('Sans titre (André Lanskoy, Rennes, 1973)');
convert_back('Sans titre (Ange Leccia, Lyon)');
convert_back('Sans titre (Ange Leccia, Nantes, 2007)');
convert_back('Sans titre (Ange Leccia, Toulouse)');
convert_back('Sans titre (Antoni Miralda)');
convert_back('Sans titre (Antoni Tàpies)');
convert_back('Sans titre (Antonio Semeraro)');
convert_back('Sans titre (Antoniucci Volti, Rennes, 1968)');
convert_back('Sans titre (Arthur Fages, Toulouse, 1961)');
convert_back('Sans titre (Aurélie Nemours, Mane)');
convert_back('Sans titre (Aurélie Nemours, Paris)');
convert_back('Sans titre (Beate Honsell-Weiss)');
convert_back('Sans titre (Bernar Venet, Paris)');
convert_back('Sans titre (Bernar Venet, Toulouse)');
convert_back('Sans titre (Bernard Barto, Nantes, 1981)');
convert_back('Sans titre (Bernard Gerboud)');
convert_back('Sans titre (Bernard Lorjou)');
convert_back('Sans titre (Bernard Pagès)');
convert_back('Sans titre (Bernard Piffaretti)');
convert_back('Sans titre (Bernard Plossu)');
convert_back('Sans titre (Bertrand Vivin)');
convert_back('Sans titre (Bruno Dumont, Villeneuve-d\'Ascq)');
convert_back('Sans titre (Bruno Yvonnet)');
convert_back('Sans titre (Bård Breivik)');
convert_back('Sans titre (Carmelo Zagari)');
convert_back('Sans titre (Carole Benzaken)');
convert_back('Sans titre (Charles Gianferrari)');
convert_back('Sans Titre (Charles Gianferrari, Grenoble, 1973)');
convert_back('Sans titre (Christophe Cuzin, Caen)');
convert_back('Sans titre (Christophe Cuzin, Lognes)');
convert_back('Sans titre (Christophe Cuzin, Villeneuve-d\'Ascq)');
convert_back('Sans titre (Christophe Gonnet)');
convert_back('Sans Titre (Christophe Gonnet, Valence)');
convert_back('Sans titre (Christophe Pillet, Toulouse)');
convert_back('Sans titre (Claire de Rougemont)');
convert_back('Sans titre (Claude Bouscau, Toulouse)');
convert_back('Sans titre (Claude Caillol, Judith Bartolani)');
convert_back('Sans titre (Claude Courtecuisse)');
convert_back('Sans titre (Claude Viallat, Aigues-Mortes)');
convert_back('Sans titre (Claude Viallat, Nevers)');
convert_back('Sans titre (Claude Viallat, Paris)');
convert_back('Sans titre (Claude Viseux, Lyon)');
convert_back('Sans titre (Claude Viseux, Saint-Max-Dommartemont)');
convert_back('Sans titre (Corinne Sentou)');
convert_back('Sans titre (Costa Coulentianos, Toulouse)');
convert_back('Sans titre (Cyrille Weiner)');
convert_back('Sans titre (Cécile Dupaquier)');
convert_back('Sans titre (D.Harmel)');
convert_back('Sans titre (Damien Aspe, Olivier Mosset)');
convert_back('Sans titre (Damien Cabanes)');
convert_back('Sans titre (Dan Graham)');
convert_back('Sans titre (Daniel Buren)');
convert_back('Sans Titre (Daniel Challe)');
convert_back('Sans titre (Daniel Coulet)');
convert_back('Sans titre (Daniel Pommereulle, Annecy)');
convert_back('Sans titre (Daniel Pommereulle, Toulouse)');
convert_back('Sans titre (Daniel Pontoreau)');
convert_back('Sans titre (Daniel Resal)');
convert_back('Sans titre (David Boeno)');
convert_back('Sans titre (David Rabinowitch)');
convert_back('Sans titre (David Rabinowitch, Digne-les-Bains)');
convert_back('Sans titre (Denis Morog)');
convert_back('Sans titre (Denis Pondruel)');
convert_back('Sans titre (Denis Santachiara)');
convert_back('Sans titre (Didier Mencoboni)');
convert_back('Sans titre (Dominique Gutherz)');
convert_back('Sans titre (Dominique Labauvie)');
convert_back('Sans titre (Edgard Pillet, Saint-Martin-d\'Hères, 1969)');
convert_back('Sans titre (Edwin Zwakman)');
convert_back('Sans titre (Elvire Jan)');
convert_back('Sans titre (Emmanuel Pinard)');
convert_back('Sans titre (Emmanuelle Villard)');
convert_back('Sans titre (Enlil Albanna)');
convert_back('Sans titre (Ervin Patkaï)');
convert_back('Sans titre (Esther Stocker)');
convert_back('Sans titre (Eugène-Henri Duler, Toulouse)');
convert_back('Sans titre (Faust Cardinali)');
convert_back('Sans titre (Felice Varini, Dijon)');
convert_back('Sans titre (Felice Varini, Toulouse)');
convert_back('Sans titre (Fernand Léger, Paris)');
convert_back('Sans titre (Florence Carbonne, Pessac)');
convert_back('Sans titre (Florence Lazar, Paris, 2015)');
convert_back('Sans titre (Francesco Iodice)');
convert_back('Sans titre (Francis Pellerin, Rennes, 1973)');
convert_back('Sans titre (François Bauchet, Azay-le-Rideau)');
convert_back('Sans titre (François Bauchet, Paris)');
convert_back('Sans titre (François Bouillon)');
convert_back('Sans titre (François Clarens, Tours, 1967)');
convert_back('Sans titre (François Fabrizi)');
convert_back('Sans titre (François Rouan, Castelnau-le-Lez)');
convert_back('Sans titre (François Rouan, Nevers)');
convert_back('Sans titre (François Rouan, Paris)');
convert_back('Sans titre (François Rouillot, Saint-Saulge)');
convert_back('Sans titre (Gabriel Gouttard)');
convert_back('Sans titre (Geneviève Asse et Olivier Debré)');
convert_back('Sans titre (Geneviève Asse)');
convert_back('Sans titre (Geneviève Micha, Villeneuve-d\'Ascq)');
convert_back('Sans titre (Georg Ettl)');
convert_back('Sans titre (Georges Adilon)');
convert_back('Sans titre (Georges Jeanclos)');
convert_back('Sans titre (Gilles Clément)');
convert_back('Sans titre (Giulio Paolini)');
convert_back('Sans titre (Gottfried Honegger, La Rochelle, installation)');
convert_back('Sans titre (Gottfried Honegger, La Rochelle, vitrail)');
convert_back('Sans titre (Gottfried Honegger, Nevers)');
convert_back('Sans titre (grand personnage) (Jean-Jules Chassepot)');
convert_back('Sans titre (Guy de Rougemont)');
convert_back('Sans titre (Guy Lartigue)');
convert_back('Sans titre (Guy-Rachel Grataloup)');
convert_back('Sans titre (Gérald Collot)');
convert_back('Sans titre (Gérald Martinand)');
convert_back('Sans titre (Gérard Fromanger)');
convert_back('Sans titre (Gérard Garouste)');
convert_back('Sans titre (Gérard Gasquet)');
convert_back('Sans titre (Gérard Ifert, Saint-Martin-d\'Hères, 1974)');
convert_back('Sans titre (Henri Larrière)');
convert_back('Sans titre (Hervé di rosa)');
convert_back('Sans titre (Hervé Di Rosa, Richard Di Rosa)');
convert_back('Sans titre (Horia Damian)');
convert_back('Sans titre (Hugues Maurin, Pessac)');
convert_back('Sans titre (Ingo Maurer)');
convert_back('Sans titre (Intensive care service) (Robert Barry)');
convert_back('Sans titre (Intégral Ruedi Baur et associés)');
convert_back('Sans titre (Invader, Paris 19e, avenue Jean Jaurès)');
convert_back('Sans titre (IRWIN)');
convert_back('Sans titre (Isaac Mizrahi, Toulouse)');
convert_back('Sans titre (Ivan Theimer)');
convert_back('Sans titre (Jackie Dumonteil, Gières, 2001)');
convert_back('Sans titre (Jacqueline Nicolle)');
convert_back('Sans titre (Jacques Bony)');
convert_back('Sans titre (Jacques Hondelatte)');
convert_back('Sans titre (Jacques Lagrange, Paris)');
convert_back('Sans titre (Jacques Perreaut)');
convert_back('Sans titre (Jacques Vieille, Nice)');
convert_back('Sans titre (Jacques Vieille, Villeurbanne)');
convert_back('Sans titre (Jacques Zwobada, Rennes, 1967)');
convert_back('Sans titre (Jacques-Victor André)');
convert_back('Sans titre (Jan Dibbets)');
convert_back('Sans titre (Jan Voss)');
convert_back('Sans titre (Jasper Morrison)');
convert_back('Sans titre (Jaume Plensa)');
convert_back('Sans titre (Jean Amado, Grenoble, 1965)');
convert_back('Sans titre (Jean Bazaine)');
convert_back('Sans titre (Jean Edelmann, Tours, 1980)');
convert_back('Sans titre (Jean Le Gac)');
convert_back('Sans titre (Jean Le Merdy, Rennes, 1966)');
convert_back('Sans titre (Jean Le Moal)');
convert_back('Sans titre (Jean Lurçat, Rennes, 1966)');
convert_back('Sans titre (Jean Messagier)');
convert_back('Sans titre (Jean Picart Le Doux, Toulouse, 1965)');
convert_back('Sans titre (Jean Pierre Raynaud, Oullins)');
convert_back('Sans titre (Jean Ricardon)');
convert_back('Sans titre (Jean Stern, Lyon, 1990)');
convert_back('Sans Titre (Jean-André Cante, Grenoble, 1967)');
convert_back('Sans titre (Jean-Claude Bédard, Paris)');
convert_back('Sans titre (Jean-Gabriel Coignet)');
convert_back('Sans titre (Jean-Louis Garnell)');
convert_back('Sans titre (Jean-Michel Alberola)');
convert_back('Sans titre (Jean-Paul Chambas)');
convert_back('Sans titre (Jean-Philippe Aubanel)');
convert_back('Sans titre (Jean-Pierre Bertrand, Bourg-Saint-Andéol)');
convert_back('Sans titre (Jean-Pierre Bertrand, Toulouse)');
convert_back('Sans titre (Jean-Pierre Filippi)');
convert_back('Sans titre (Jean-Pierre Pincemin)');
convert_back('Sans titre (Jean-Pierre Raynaud, Paris)');
convert_back('Sans Titre (Jenny Holzer)');
convert_back('Sans titre (Jesús-Rafael Soto)');
convert_back('Sans titre (Joan Miró)');
convert_back('Sans titre (Jose Pini)');
convert_back('Sans titre (Joseph Andrau, Toulouse)');
convert_back('Sans titre (Julije Knifer)');
convert_back('Sans titre (Keith Haring)');
convert_back('Sans titre (Kim Creighton, Jean-Marie Guigues et David Vincent, Lille, 2003)');
convert_back('Sans titre (Kristian Gavoille)');
convert_back('Sans titre (L\'étoile du cirque) (Ernst Caramelle)');
convert_back('Sans titre (L\'île) (Xavier Veilhan)');
convert_back('Sans titre (Le Jardin aux sentiers qui bifurquent) (Bruno Peinado)');
convert_back('Sans titre (Louis-Emmanuel Chavignier)');
convert_back('Sans titre (Luc Peire, Villeneuve-d\'Ascq)');
convert_back('Sans titre (Luc-Simon)');
convert_back('Sans titre (Lucien Fontanarosa)');
convert_back('Sans titre (Lucien Fontanarosa, Orsay, 1962)');
convert_back('Sans titre (Lucien Lautrec)');
convert_back('Sans titre (Ludger Gerdes)');
convert_back('Sans titre (Léon Gischia, Paris)');
convert_back('Sans titre (Léon Gischia, Tours, 1971)');
convert_back('Sans titre (Marc Chagall)');
convert_back('Sans titre (Marc Couturier)');
convert_back('Sans titre (Marc-Camille Chaimowicz, Cluny)');
convert_back('Sans titre (Marc-Camille Chaimowicz, Pacé)');
convert_back('Sans titre (Michelangelo Pistoletto)');
convert_back('Sans titre (Niki de Saint Phalle et Jean Tinguely)');
convert_back('Sans titre (Noël Dolla)');
convert_back('Sans titre (Olivier Debré, La Croix-Helléan)');
convert_back('Sans titre (Olivier Debré, Paris)');
convert_back('Sans titre (Olivier Descamps, Gières, 1990)');
convert_back('Sans titre (Pascal Mourgue)');
convert_back('Sans titre (Patrice Carré)');
convert_back('Sans titre (Philippe Favier, Paris)');
convert_back('Sans titre (Philippe Lang)');
convert_back('Sans titre (Pierre Buraglio)');
convert_back('Sans titre (Pierre Della Giustina)');
convert_back('Sans Titre (Pierre Lebe, Meylan, 1973)');
convert_back('Sans titre (Pierre Mabille)');
convert_back('Sans titre (Pierre Sabatier)');
convert_back('Sans titre (Pierre Soulages)');
convert_back('Sans titre (Pierre Théron)');
convert_back('Sans titre (Pierre Tual, Élancourt)');
convert_back('Sans titre (Pierre Tual, Évry)');
convert_back('Sans titre (Pierre-François Gorse, Nevers)');
convert_back('Sans titre (Pierrick Sorin)');
convert_back('Sans Titre (Pillet Edgar)');
convert_back('Sans titre (Pièce pour Hellerau) (Veit Stratmann)');
convert_back('Sans titre (Pol Bury, Conflans-Sainte-Honorine)');
convert_back('Sans titre (Pol Bury, Paris)');
convert_back('Sans titre (Raoul Ubac)');
convert_back('Sans titre (Raoul Ubac, Orsay, 1969)');
convert_back('Sans titre (Raoul Ubac, Villeneuve-d\'Ascq)');
convert_back('Sans titre (Raymond Subes, Paris, 1961)');
convert_back('Sans titre (Raymond Subes, Rennes, 1968)');
convert_back('Sans Titre (René Collamarini, Grenoble, 1969)');
convert_back('Sans titre (René Perrot, Toulouse)');
convert_back('Sans titre (Richard Fauguet et Daniel Schlier, Toulouse)');
convert_back('Sans titre (Robert Ascain)');
convert_back('Sans titre (Robert Fachard, Toulouse)');
convert_back('Sans titre (Robert Julius Jacobsen)');
convert_back('Sans titre (Robert Morris)');
convert_back('Sans titre (Robert Pagès, Toulouse)');
convert_back('Sans titre (Roger Chapelain-Midy, Paris)');
convert_back('Sans titre (Roger Lersy, Toulouse)');
convert_back('Sans Titre (Roger Pfund)');
convert_back('Sans titre (Romain Récubert et Pierre Gérard, Mandelieu-la-Napoule)');
convert_back('Sans titre (Roman Opałka)');
convert_back('Sans titre (Ruedi Baur et Eric Jourdan)');
convert_back('Sans titre (Ruedi Baur)');
convert_back('Sans titre (Ruedi Baur, Nantes)');
convert_back('Sans titre (Rugerro Pazzi)');
convert_back('Sans titre (Rémi Guerrin)');
convert_back('Sans titre (Salvador Dalí)');
convert_back('Sans titre (Samuel Paugam, Nantes)');
convert_back('Sans titre (Sarkis)');
convert_back('Sans titre (Setsuko Nagasawa)');
convert_back('Sans titre (Shirley Jaffe)');
convert_back('Sans titre (Sophie Calle)');
convert_back('Sans titre (Stanley Brouwn)');
convert_back('Sans titre (Stephan Balkenhol)');
convert_back('Sans titre (Takis, Beauvais)');
convert_back('Sans titre (Takis, Toulouse)');
convert_back('Sans titre (Thomas Gleb, Toulouse)');
convert_back('Sans titre (Thomas Huot-Marchand)');
convert_back('Sans titre (Thu Van Tran)');
convert_back('Sans titre (Thème de l\'affiche) (Jacques Villeglé)');
convert_back('Sans titre (Thème de l\'alphabet) (Jacques Villeglé)');
convert_back('Sans titre (Tim)');
convert_back('Sans titre (Toni Grand, Bignan)');
convert_back('Sans titre (Toni Grand, Salses-le-Château)');
convert_back('Sans titre (Tony Cragg)');
convert_back('Sans titre (Ulrich Rückriem, Albertville)');
convert_back('Sans titre (Ulrich Rückriem, Bourg-en-Bresse)');
convert_back('Sans titre (Valérie de Calignon)');
convert_back('Sans titre (Victor Anicet)');
convert_back('Sans titre (Victor Vasarely)');
convert_back('Sans titre (Victor Vasarely, Toulouse)');
convert_back('Sans titre (Vincent Mauger)');
convert_back('Sans titre (Vladimír Škoda)');
convert_back('Sans titre (Westkapelle) (Dirk Zoete)');
convert_back('Sans titre (Wolfgang Nestler)');
convert_back('Sans titre (Yann de Portzamparc)');
convert_back('Sans titre (Yann Lestrat)');
convert_back('Sans titre (Yves Loyer, Villeneuve-d\'Ascq, 1971)');
convert_back('Sans titre (Yves Trévédy, Nantes, 1966)');
convert_back('Sans titre (Éric Dalbis)');
convert_back('Sans titre (Éric Jourdan)');
convert_back('Sans titre (Étienne Hajdu)');
convert_back('Sans titre, Le gris du ciel (Bruno Peinado)');
convert_back('Sans titre, Remington stoned Barrocco bronco Smokey tone (Bruno Peinado)');
convert_back('Sans toi la maison est chauve (Erik Dietman)');
convert_back('Saphira (Claudia Comte)');
convert_back('Sarabande pour Picasso (Miguel Berrocal)');
convert_back('Satoyama Symphony / Earth Is Our Home (Toshiharu Miki)');
convert_back('Saturation (Bernar Venet)');
convert_back('Sciences physiques et chimiques (Yves Millecamps)');
convert_back('Scissure signal (Pierre-Alexandre Rémy)');
convert_back('Scoterus Castle (Navin Rawanchaikul)');
convert_back('Sculpter dans la couleur pure (Bernard Pagès)');
convert_back('Sculpture (Albert Ayme)');
convert_back('Sculpture architecturale (Marino di Teana)');
convert_back('Sculpture aérostatique (Yves Klein)');
convert_back('Sculpture cybernétique de Rennes (Nicolas Schöffer)');
convert_back('Sculpture dans l\'île (Ivan Messac)');
convert_back('Sculpture en béton (Ervin Patkaï)');
convert_back('Sculpture en granit du Huelgoat (Francis Pellerin)');
convert_back('Sculpture non attribuée (Pascal Convert)');
convert_back('Sculpture n°77 (Ervin Patkaï)');
convert_back('Sculpture pour la paix (Christine O\'Loughlin)');
convert_back('Sculptures de visée / Sculptures Bachelard (Jean-Max Albert)');
convert_back('Sculptures flottantes 1 et 2 (Marta Pan)');
convert_back('Scénario Parmelan (Didier Courbot)');
convert_back('Second House (Hreinn Fridfinnsson)');
convert_back('Seconde nature (Miguel Chevalier)');
convert_back('Seize objets dans la ville (Guy de Rougemont)');
convert_back('Semaison (Samuel Buckman)');
convert_back('Sens dessus dessous, sculpture in situ et en mouvement (Daniel Buren)');
convert_back('Sentinelle de la vallée du Bès (Andy Goldsworthy)');
convert_back('Sentinelle de la vallée du l\'Asse (Andy Goldsworthy)');
convert_back('Sentinelle de la vallée du Vançon (Andy Goldsworthy)');
convert_back('Sentinelles de la mémoire (Jorge Soler)');
convert_back('Serpent d\'océan (Huang Yong Ping)');
convert_back('Serpentine rouge (Jimmie Durham)');
convert_back('Serre cyprès Florence (Patrick Saytour)');
convert_back('Set North for Japan (74°33’2”) (Richard Wilson)');
convert_back('Seven Pursuits for a Stretch of Water (Michel Verjux)');
convert_back('Sextan (Laurent Saksik)');
convert_back('Shamiyaana–Food for Thought: Thought for Change (Rasheed Araeen )');
convert_back('Shell (Pascal Bernier)');
convert_back('Shimmy Shimmy Belly (Qubo Gas)');
convert_back('Shining Wings (Yoshikuni Iida)');
convert_back('Shrapnel Galeries (Arnaud Rochard)');
convert_back('Shreds of White Cloth (Attached to Trees) (Lawrence Weiner)');
convert_back('Shrine for Mother Nature (Eko Prawoto)');
convert_back('Sidewalk Chalk (Mick Peter)');
convert_back('Signal (Raymond Subes)');
convert_back('Signal (Takis)');
convert_back('Signal Oblique (Alain Lovato)');
convert_back('Signal Orange (Alain Lovato)');
convert_back('Signalétique (Niklaus Thoenen)');
convert_back('Signalétique (Pierre di Sciullo)');
convert_back('Signalétique (Visuel Design Jean Widmer)');
convert_back('Signalétique pour le parc naturel régional de la Haute vallée de Chevreuse (Éric Jourdan)');
convert_back('Signalétique Sans titre (Anna-Monika Jost)');
convert_back('Signalétique sans titre (Delo Lindo)');
convert_back('Signalétique sans titre (Laurent Fétis)');
convert_back('Signaux lumineux (Takis)');
convert_back('Signe Ecoute (Lovato)');
convert_back('Signes de vie (Olivia Rosenthal, Philippe Bretelle)');
convert_back('Sillery France / Sillery Québec (Mauro Corda)');
convert_back('Silophone (The User)');
convert_back('Silver Butterfly (When the Composition Is Over) (Surasi Kusolwong)');
convert_back('Skate Park (Peter Kogler)');
convert_back('Skin (Mehmet Ali Uysal)');
convert_back('Slat (Richard Serra)');
convert_back('Sluit je ogen, verbeeld je kunst (Jan Christensen)');
convert_back('Soffio interno-esterno et Soffio di foglie (Giuseppe Penone)');
convert_back('Soin du deuil (Bruno Carbonnet)');
convert_back('Sol (Daniel Dezeuze)');
convert_back('Sol et Colombe (Martial Raysse)');
convert_back('Solanum (Stéphane Calais)');
convert_back('SolarWind (Laurent Grasso)');
convert_back('Soleil (Ivan Avoscan)');
convert_back('Soleil - Forêt - Fête (Yasuo Mizui)');
convert_back('Soleil couchant (Camille Hilaire)');
convert_back('Soleil d\'encre (Laurent Saksik)');
convert_back('Soleil levant (Nicole Cormier-Vago)');
convert_back('Soleils portés (Francesca Caruana)');
convert_back('Soltice et Systole (Alain Domagala)');
convert_back('Sonals (Michel Redolfi)');
convert_back('Song of the mountains (Anthony Caro)');
convert_back('Song-Line (Delphine Bretesché, Martin Gracineau)');
convert_back('Sonic Beast (Francisco López)');
convert_back('Soubise (Patrice Carré)');
convert_back('Souffle végétal Geste végétal Arbre (Giuseppe Penone)');
convert_back('Source de vie (Alain Mila)');
convert_back('Source de vitalité (Yasuo Mizui)');
convert_back('Souris Cheminée Street art – 14’Arts (Twopy)');
convert_back('Souris Mur en construction – Street art – 14’Arts (Twopy)');
convert_back('Sous le plafond (sur le sol exactement) (Michel Verjux)');
convert_back('Sous le plus grand chapiteau du monde (partie 1) (Claude Lévêque)');
convert_back('Space (Jean-Pierre Raynaud)');
convert_back('Speranza (Charlotte Pringuey Cessac)');
convert_back('Sphère (Vladimír Škoda)');
convert_back('Sphère coupée 1400-1000 (Marta Pan)');
convert_back('Sphère enterrée (François Morellet)');
convert_back('Sphère-trame (François Morellet, Cholet)');
convert_back('Spiral Jetty (Robert Smithson)');
convert_back('Spirale Aby Warburg, le monument aux vivants (Bert Theis)');
convert_back('Spirit House (Marina Abramović)');
convert_back('Square and Circle (Norman Dilworth)');
convert_back('Square de la Joliette (Corinne Chiche, Eric Dussol, Remi Dutoit, Bernard Boyer)');
convert_back('Squaring the Circle (Attila Csörgő)');
convert_back('Stabile (Alexander Calder, Tours)');
convert_back('Stabile (Pierre Sabatier)');
convert_back('Stabile-Mobile (Alexander Calder)');
convert_back('Staircase (Kevin van Braak)');
convert_back('Standing Figure (Willem de Kooning)');
convert_back('Standing Woman (Gaston Lachaise)');
convert_back('Station - Je me suis levé (Édouard Boyer)');
convert_back('Station - Je suis toujours vivant (Édouard Boyer)');
convert_back('Station Prouvé (Jean Prouvé)');
convert_back('Statue du Sergent Blandan (André Tajana)');
convert_back('Statue équestre de Georgios Karaiskakis (Michael Tompros)');
convert_back('Statue équestre de Jeanne d\'Arc (Mathurin Moreau, Pierre Le Nordez)');
convert_back('Statue équestre de Washington (Daniel Chester French)');
convert_back('Status post historicus (Braco Dimitrijevic)');
convert_back('Steinar Breiflabb (Erik Dietman)');
convert_back('Steinkirka (Bjørn Nørgaard)');
convert_back('Stella Maris (Steinar Christensen)');
convert_back('Stellar Axis: Antarctica (Lita Albuquerque)');
convert_back('Step in Plan (John Körmeling)');
convert_back('Step to Entropy (Richard Artschwager)');
convert_back('Sternwarte Sonneberg (titre temporaire) (Heike Mutter, Ulrich Genth)');
convert_back('Storehouse of Names (Linda Covit)');
convert_back('Street Painting 7 (Sabina Lang et Daniel Baumann)');
convert_back('Structure (Costa Coulentianos)');
convert_back('Structure (Vincent Batbedat)');
convert_back('Structure architecturale (Marino di Teana)');
convert_back('Structure Oblique (Jean-Claude Barrere)');
convert_back('Structure Villemin (Marino di Teana)');
convert_back('Stèle (Liuba Kirova)');
convert_back('Stèle à Goethe (Eduardo Chillida)');
convert_back('Stèles lumineuses (Carlos Valverde)');
convert_back('Stèles Ramibhul (François Stahly)');
convert_back('Suite de triangles (Felice Varini)');
convert_back('Sun Tunnels (Nancy Holt)');
convert_back('Superadode (Cal-Earth)');
convert_back('Supper Memory (Joan Crous)');
convert_back('Sur le seuil (Laurent Pariente)');
convert_back('Surface basculée / Gekippte Fläche (Benoit Tremsal)');
convert_back('Surface de rencontres temporaires + Horizon des astres 360°CMJN (Stéphane Magnin)');
convert_back('Surface vivante (Bertrand Segers)');
convert_back('SVAYAMBH (Anish Kapoor)');
convert_back('Sylvia (Stéphane Vigny)');
convert_back('Synchromie n°1 (René Roche)');
convert_back('Syndicat des vins de Chinon (Dewar & Gicquel)');
convert_back('Synoptique (Michel Verjux)');
convert_back('Syv magiske punkter (Martti Aiha)');
convert_back('Sérigraphie sur verre (Marianne Colombani)');
convert_back('Table d\'Airain (Yves Adrien)');
convert_back('Table-relief (David Renaud)');
convert_back('Tables d\'orientation (Jean-Jacques Rullier)');
convert_back('Tamugi\'s Book (Bili Bidjocka)');
convert_back('Tapis (Bernard Calet)');
convert_back('Tapis urbain « Bienvenue » (Carolyn Wittendal & Benjamin Jacqueme alias Microclimax)');
convert_back('Tchaïkovsky (Claude Lévêque)');
convert_back('Tempo (José Subirà-Puig)');
convert_back('Tente (Yaacov Agam)');
convert_back('Tentvillage (Dré Wapenaar)');
convert_back('Terra Mater (Alfred Janniot)');
convert_back('Terrain d\'occurrences (Jennifer Caubet)');
convert_back('Terrain Saint-François d\'Assise (Andreas Brandolini)');
convert_back('Terrasses de la Terre et de l\'Air (Etienne-Martin)');
convert_back('Terre / Ciel (Chris Booth)');
convert_back('Terre Loire (Kôichi Kurita)');
convert_back('Test 18 mars (Michel Blazy)');
convert_back('The Arch (Henry Moore)');
convert_back('The Basilica of the Forest (Jannecke Lønne Christiansen)');
convert_back('The Beat of the Ground (Yasuyuki Watanabe)');
convert_back('The Forty Part Motet (Janet Cardiff)');
convert_back('The Lightning Field (Walter De Maria)');
convert_back('The Misthrown dice (Gilles Barbier)');
convert_back('The Other Side Of The World (Pascal Brateau)');
convert_back('The Settlement (Hans op de Beeck)');
convert_back('The Settlers (Sarah Sze)');
convert_back('The Vertical Earth Kilometer (Walter De Maria)');
convert_back('The Vertical Works (Anthony McCall)');
convert_back('The Veymany151 (Marc Fornès)');
convert_back('The Wash House/ The Roadsides (Sarah Jones)');
convert_back('The way eartly things are going (Emeka Ogboh)');
convert_back('The Welcoming Hands (Louise Bourgeois)');
convert_back('The Zebra Crossing - Regulations and General Directions (Angela Bulloch)');
convert_back('The○△□Tower and the Red Dragonfly (Shintaro Tanaka)');
convert_back('This is the way you and me mesure the World (Saâdane Afif)');
convert_back('Thématique : l\'écriture (Jean-Paul Van Lith)');
convert_back('Thésée et le fil d\'Ariane (Anne-Marie Pochat)');
convert_back('Théâtres optiques (Pierrick Sorin)');
convert_back('Tindaro (Igor Mitoraj)');
convert_back('Ting-King-Ping in Kyororo—Sound Source (Taiko Shono)');
convert_back('Titre inconnu (Agustín Cárdenas, Saint-Denis, 1969)');
convert_back('Titre inconnu (Alain François, Toulon, 1986)');
convert_back('Titre inconnu (Alain Hiéronimus, Perpignan, 1963)');
convert_back('Titre inconnu (Alain Hiéronimus, Sceaux, 1971)');
convert_back('Titre inconnu (Alain Lantero, Aubière, 1991)');
convert_back('Titre inconnu (Alain Lantero, Saint-Flour, 1979)');
convert_back('Titre inconnu (Albert Bouquillon, Douai, 1962)');
convert_back('Titre inconnu (Albert de Jaeger, Amiens, 1970)');
convert_back('Titre inconnu (Albert de Jaeger, Montpellier, 1976)');
convert_back('Titre inconnu (Albert Dupin, Montpellier, 1963)');
convert_back('Titre inconnu (Albert Dupin, Montpellier, 1964)');
convert_back('Titre inconnu (Albert Dupin, Montpellier, 1969)');
convert_back('Titre inconnu (Albert Féraud, Conflans-Sainte-Honorine, 1971)');
convert_back('Titre inconnu (Albert Féraud, La Tronche, 1963)');
convert_back('Titre inconnu (Albert Féraud, Nice, 1968)');
convert_back('Titre inconnu (Albert Leclerc, Saint-Denis, 1962)');
convert_back('Titre inconnu (Albert Ràfols-Casamada, Lyon, 2000)');
convert_back('Titre inconnu (Alexandra Cot, Villetaneuse, 1972)');
convert_back('Titre inconnu (Alexandre Bonnier, Montpellier, 1973)');
convert_back('Titre inconnu (Alfred Janniot, Clichy, 1959)');
convert_back('Titre inconnu (Alfred Janniot, Strasbourg, 1957)');
convert_back('Titre inconnu (Alfred Manessier, Châtenay-Malabry, 1976)');
convert_back('Titre inconnu (Alma Remondet, Strasbourg, 1968)');
convert_back('Titre inconnu (Andrea Blum, Saint-Denis, 1998)');
convert_back('Titre inconnu (André Arbus, Metz, 1963)');
convert_back('Titre inconnu (André Beaudin, Marseille, 1967)');
convert_back('Titre inconnu (André Bizette-Lindet, Caen, 1972)');
convert_back('Titre inconnu (André Borderie, Limoges, 1974)');
convert_back('Titre inconnu (André Condé, Chambéry)');
convert_back('Titre inconnu (André Dupin, Montpellier, 1970)');
convert_back('Titre inconnu (André Jacob, Clermont-Ferrand, 1971)');
convert_back('Titre inconnu (André Michel, Reims, 1980)');
convert_back('Titre inconnu (André Mériel-Bussy, Vannes, 1962)');
convert_back('Titre inconnu (André Roger, Nancy, 1960)');
convert_back('Titre inconnu (Andrée Honoré, Charleville-Mézières, 1971)');
convert_back('Titre inconnu (Anne Abou, Grenoble, 1988)');
convert_back('Titre inconnu (Anne Bregeault, Noyon)');
convert_back('Titre inconnu (Anne Deguelle, Aubière, 1996)');
convert_back('Titre inconnu (Anne Doyon, Granville)');
convert_back('Titre inconnu (Anne Filali et Jacques Zwobada, Amiens, 1974)');
convert_back('Titre inconnu (Anne Filali et Jacques Zwobada, Le Havre, 1966)');
convert_back('Titre inconnu (Anne-Katrin Feddersen, Montpellier, 1997)');
convert_back('Titre inconnu (Anne-Marie Gerault, Firminy, 1961)');
convert_back('Titre inconnu (Anne-Marie Jugnet, Limoges, 1996)');
convert_back('Titre inconnu (Antoine Poncet, Besançon, 1971)');
convert_back('Titre inconnu (Antoine Poncet, Marseille, 1975)');
convert_back('Titre inconnu (Antoine Rohal, Strasbourg, 1970)');
convert_back('Titre inconnu (Antoine-René Giguet, Nancy, 1964)');
convert_back('Titre inconnu (Antoine-René Giguet, Nancy, 1967)');
convert_back('Titre inconnu (Arlette Granval, Brest)');
convert_back('Titre inconnu (Arlette Granval, Grenoble)');
convert_back('Titre inconnu (Art Brenner, Nancy, 1988)');
convert_back('Titre inconnu (Arthur Fages, Toulouse, 1964)');
convert_back('Titre inconnu (Arthur Van Hecke, Lille, 1975)');
convert_back('Titre inconnu (Artiste inconnu - 2015-04-20 10:41:25)');
convert_back('Titre inconnu (Artiste inconnu - 2015-09-25 17:54:10)');
convert_back('Titre inconnu (Artiste inconnu - 2016-03-23 17:24:29)');
convert_back('Titre inconnu (Artiste inconnu - 2016-05-13 08:58:04)');
convert_back('Titre inconnu (Artiste inconnu - 2016-05-13 09:16:33)');
convert_back('Titre inconnu (Artiste inconnu - 2016-05-13 09:17:20)');
convert_back('Titre inconnu (Artiste inconnu - 2016-05-13 09:17:42)');
convert_back('Titre inconnu (Artiste inconnu - 2016-05-13 09:18:05)');
convert_back('Titre inconnu (Artiste inconnu - 2016-05-13 09:18:23)');
convert_back('Titre inconnu (Artiste inconnu - 2016-05-13 09:18:45)');
convert_back('Titre inconnu (Artiste inconnu - 2016-05-13 09:22:27)');
convert_back('Titre inconnu (Artiste inconnu - 2016/12/01 10:38:56)');
convert_back('Titre inconnu (Artiste inconnu - 2016/12/01 10:57:08)');
convert_back('Titre inconnu (Artiste inconnu - 2016/12/01 10:59:55)');
convert_back('Titre inconnu (Artiste inconnu - 2017/01/05 15:34:44)');
convert_back('Titre inconnu (Artiste inconnu - 2017/01/05 15:39:52)');
convert_back('Titre inconnu (Artiste inconnu - 2017/01/05 15:50:34)');
convert_back('Titre inconnu (Artiste inconnu - 2017/01/05 16:02:30)');
convert_back('Titre inconnu (Artiste inconnu - 2017/04/14 14:00:03)');
convert_back('Titre inconnu (Artiste inconnu - 2017/07/04 12:20:37)');
convert_back('Titre inconnu (Artiste inconnu - 2017/07/06 18:32:04)');
convert_back('Titre inconnu (Artiste inconnu - 2017/07/19 20:40:37)');
convert_back('Titre inconnu (Artiste inconnu - 2017/07/30 21:59:54)');
convert_back('Titre inconnu (Artiste inconnu - 2017/07/30 22:02:17)');
convert_back('Titre inconnu (Artiste inconnu - 2017/10/22 14:00:17)');
convert_back('Titre inconnu (Artiste inconnu - 2018/02/16 09:46:46)');
convert_back('Titre inconnu (Artiste inconnu - 2018/05/28 17:09:10)');
convert_back('Titre inconnu (Artiste inconnu - 2018/06/19 11:28:47)');
convert_back('Titre inconnu (Artiste inconnu - 2018/06/19 11:29:35)');
convert_back('Titre inconnu (Artiste inconnu - 2018/06/20 16:08:59)');
convert_back('Titre inconnu (Artiste inconnu, Clermont-Ferrand, Maison de la culture)');
convert_back('Titre inconnu (artiste inconnu, Le Mans, 2010)');
convert_back('Titre inconnu (Artiste inconnu, Saint-Nazaire)');
convert_back('Titre inconnu (artiste inconnu, Strasbourg)');
convert_back('Titre inconnu (Artiste inconnu, Vernoux-en-Vivarais, fresque)');
convert_back('Titre inconnu (Bachir Hadji, Villeurbanne, 2005)');
convert_back('Titre inconnu (Banksy)');
convert_back('Titre inconnu (Beat Streuli, Nantes)');
convert_back('Titre inconnu (Becker, Périgueux, 1966)');
convert_back('Titre inconnu (Benoît Luyckx, Lyon, 1989)');
convert_back('Titre inconnu (Bernard Barto, Nantes, 1977)');
convert_back('Titre inconnu (Bernard Calet, Brive-la-Gaillarde, 1990)');
convert_back('Titre inconnu (Bernard Calet, Limoges, 1991)');
convert_back('Titre inconnu (Bernard Dejonghe, Cannes)');
convert_back('Titre inconnu (Bernard Gerboud & Virginie Monicot, Saint-Denis, 1983)');
convert_back('Titre inconnu (Bernard Mandeville, Clermont-Ferrand, 1967)');
convert_back('Titre inconnu (Bernard Mougin, Nancy, 1955)');
convert_back('Titre inconnu (Bernard Mougin, Strasbourg, 1973)');
convert_back('Titre inconnu (Bernard Pagès, Aix-en-Provence, 1996)');
convert_back('Titre inconnu (Bernard Pagès, Limoges, 2005)');
convert_back('Titre inconnu (Bernard Vincent et Yvette Alleaume, Agen, 1976)');
convert_back('Titre inconnu (Bernard Vincent et Yvette Alleaume, Bonneuil-sur-Marne, 1977)');
convert_back('Titre inconnu (Bernard Vincent et Yvette Alleaume, Paris, 1973)');
convert_back('Titre inconnu (Bernard Vincent et Yvette Alleaume, Schœlcher, 1978)');
convert_back('Titre inconnu (Berto Lardera, Toulouse, 1967)');
convert_back('Titre inconnu (Bertrand Vivin, Montpellier, 1997)');
convert_back('Titre inconnu (Betty Bui, Avignon)');
convert_back('Titre inconnu (Biot, Villetaneuse, 1972)');
convert_back('Titre inconnu (Brigitte Baumas, Marseille, 1971)');
convert_back('Titre inconnu (Bruno Breitwieser, Angoulême, 1995)');
convert_back('Titre inconnu (Bruno Lebel, Versailles, 1965)');
convert_back('Titre inconnu (Bruno Saas, Mont-Saint-Aignan, 1992)');
convert_back('Titre inconnu (Béatrice Casadesus, Poitiers, 1975)');
convert_back('Titre inconnu (Camille Hilaire, Nancy, 1955)');
convert_back('Titre inconnu (Camille Hilaire, Nancy, 1975)');
convert_back('Titre inconnu (Camille Hilaire, Toulouse, 1966)');
convert_back('Titre inconnu (Charles Belle, Besançon, 1995)');
convert_back('Titre inconnu (Charles Betremieux, Valenciennes, 1970)');
convert_back('Titre inconnu (Charles Gianferrari, Versailles, 1980)');
convert_back('Titre inconnu (Charles Le Bars, Chambéry)');
convert_back('Titre inconnu (Charles-Émile Pinson, Audrieu, 1954)');
convert_back('Titre inconnu (Charles-Émile Pinson, Caen, 1955)');
convert_back('Titre inconnu (Charles-Émile Pinson, Caen, 1962)');
convert_back('Titre inconnu (Christian Bizeul, Montigny-lès-Metz, 1985)');
convert_back('Titre inconnu (Christian Christel, Limoges, 1981)');
convert_back('Titre inconnu (Christine de Beauchêne, Perpignan, 1994)');
convert_back('Titre inconnu (Christophe Berdaguer et Marie Péjus, Grenoble, 2005)');
convert_back('Titre inconnu (Christophe Curien, Gif-sur-Yvette, 1976)');
convert_back('Titre inconnu (Christophe Doucet, Bayonne, 2008)');
convert_back('Titre inconnu (Christophe Pillet et Éric Tabuchi, Besançon)');
convert_back('Titre inconnu (Christophe Pillet, Montpellier, 2010)');
convert_back('Titre inconnu (Chrystèle Lerisse, Reims, 1993)');
convert_back('Titre inconnu (Claire Dehove, Angers, 1997)');
convert_back('Titre inconnu (Claire Lucas, Rennes, 2000)');
convert_back('Titre inconnu (Clarisse Denoué-Schlegel, Châtellerault, 1995)');
convert_back('Titre inconnu (Claude Abeille, Antony, 1978)');
convert_back('Titre inconnu (Claude Bessou, Laval, 1954)');
convert_back('Titre inconnu (Claude Bleynie, Limoges, 1977)');
convert_back('Titre inconnu (Claude Bouscau, Aurillac, 1957)');
convert_back('Titre inconnu (Claude Roucard, Mont-Saint-Aignan, 1979)');
convert_back('Titre inconnu (Claude Rutault, Nantes, 2014)');
convert_back('Titre inconnu (Claude Schürr, Nice, 1964)');
convert_back('Titre inconnu (Claude Viseux, Grenoble, 1977)');
convert_back('Titre inconnu (Compard, Quimper)');
convert_back('Titre inconnu (Constant Le Breton, Angers, 1961)');
convert_back('Titre inconnu (Costa Coulentianos, Aix-en-Provence, 1972)');
convert_back('Titre inconnu (Costa Coulentianos, Annecy, 1975)');
convert_back('Titre inconnu (Costa Coulentianos, Marseille, 1970)');
convert_back('Titre inconnu (Cécile Bart, Cachan, 1997)');
convert_back('Titre inconnu (Cécile Le Prado, Niort, 1996)');
convert_back('Titre inconnu (Damien Roland, Orléans, 1995)');
convert_back('Titre inconnu (Daniel Buren, Strasbourg)');
convert_back('Titre inconnu (Daniel Challe, Chambéry, 1997)');
convert_back('Titre inconnu (Daniel Dartois, Bordeaux, 1992)');
convert_back('Titre inconnu (Daniel Dupire, Valenciennes, 1992)');
convert_back('Titre inconnu (Daniel Octobre, Dijon, 1952)');
convert_back('Titre inconnu (Daniel Picard, Metz, 1958)');
convert_back('Titre inconnu (David Boeno, Chambéry, 1993)');
convert_back('Titre inconnu (David Boeno, Poitiers, 1996)');
convert_back('Titre inconnu (David Tremlett et L\'Oulipo, Saint-Denis, 1996)');
convert_back('Titre inconnu (Delphine Coindet, Chartres, 1998)');
convert_back('Titre inconnu (Delphine Reist, La Tronche, 2001)');
convert_back('Titre inconnu (Denis Morog, Lyon, 1962)');
convert_back('Titre inconnu (Denis Pondruel, Bornel, 2007)');
convert_back('Titre inconnu (Denis Pondruel, Brest)');
convert_back('Titre inconnu (Didier Courbot, Rouen)');
convert_back('Titre inconnu (Didier Trenet, Saint-Philbert-de-Grand-Lieu, 2015)');
convert_back('Titre inconnu (Dome, Bordeaux, 1994)');
convert_back('Titre inconnu (Dominick Le Tarnec, Lannion, 1993)');
convert_back('Titre inconnu (Dominique Masse, Argenteuil, 1992)');
convert_back('Titre inconnu (Eddie Ladoire, Saint-Symphorien)');
convert_back('Titre inconnu (Edmond Boissonnet, Talence, 1966)');
convert_back('Titre inconnu (Edmée Larnaudie, Angoulême, 1960)');
convert_back('Titre inconnu (Emma Reyes, Paris, 1965)');
convert_back('Titre inconnu (Emmanuel Auricoste, Orléans, 1963)');
convert_back('Titre inconnu (Erik Samakh, Mantes-la-Jolie, 2000)');
convert_back('Titre inconnu (Erik Samakh, Nice, 1996)');
convert_back('Titre inconnu (Erik Samakh, Poitiers, 1996)');
convert_back('Titre inconnu (Ernest Pignon-Ernest, Livry-Gargan, 1974)');
convert_back('Titre inconnu (Ervin Patkaï, Aubière, 1972)');
convert_back('Titre inconnu (Ervin Patkaï, Clermont-Ferrand, 1956)');
convert_back('Titre inconnu (Ervin Patkaï, Clermont-Ferrand, 1972)');
convert_back('Titre inconnu (Ervin Patkaï, Clermont-Ferrand, 1978)');
convert_back('Titre inconnu (Ervin Patkaï, Clermont-Ferrand, 2008)');
convert_back('Titre inconnu (Eugène Dodeigne, Béthune, 1970)');
convert_back('Titre inconnu (Eugène Dodeigne, Lille, 1972)');
convert_back('Titre inconnu (Eugène-Henri Duler, Toulouse, 1960)');
convert_back('Titre inconnu (Evelyne Koeppel et Agnès Pietri, Grenoble, 1998)');
convert_back('Titre inconnu (Fabrice Berrux, Béthune, 1998)');
convert_back('Titre inconnu (Fernand Léger)');
convert_back('Titre inconnu (Fernand Michel, Montpellier, 1966)');
convert_back('Titre inconnu (Fernand Michel, Montpellier, 1977)');
convert_back('Titre inconnu (Florence Groussin-Bouvier)');
convert_back('Titre inconnu (Francine Zubeil, Marseille, 2000)');
convert_back('Titre inconnu (Francis Burette, Cergy, 1978)');
convert_back('Titre inconnu (Francis Pellerin, Aubière, 1975)');
convert_back('Titre inconnu (Francis Pellerin, Brest, 1965)');
convert_back('Titre inconnu (Francis Pellerin, Fougères)');
convert_back('Titre inconnu (Francis Pellerin, Rennes, 1974)');
convert_back('Titre inconnu (François Baron-Renouard, Cagnes-sur-Mer, 1974)');
convert_back('Titre inconnu (François Baron-Renouard, Le Mans, 1971)');
convert_back('Titre inconnu (François Bauchet, Saint-Étienne)');
convert_back('Titre inconnu (François Bertrand, Orsay, 1974)');
convert_back('Titre inconnu (François Bouché, Aix-en-Provence, 1971)');
convert_back('Titre inconnu (François Bouché, Marseille)');
convert_back('Titre inconnu (François Brochet, Bar-le-Duc, 1962)');
convert_back('Titre inconnu (François Desnoyer, Montpellier, 1972)');
convert_back('Titre inconnu (François Ganeau, Marseille, 1955)');
convert_back('Titre inconnu (François Guéneau, Noyer)');
convert_back('Titre inconnu (François Guéneau, Saint-Georges-sur-Baulche)');
convert_back('Titre inconnu (François Hornn, Saint-Denis, 1980)');
convert_back('Titre inconnu (François Martin, Châlons-en-Champagne, 1996)');
convert_back('Titre inconnu (François Rouan, Montpellier, 1964)');
convert_back('Titre inconnu (François Rouan, Montpellier, 1969)');
convert_back('Titre inconnu (François Stahly, Pau, 1972)');
convert_back('Titre inconnu (François Stahly, Strasbourg, 1969)');
convert_back('Titre inconnu (François-Xavier Lalanne, Toulouse, 1960)');
convert_back('Titre inconnu (Françoise Bizette-Lindet, Chaumont, 1965)');
convert_back('Titre inconnu (Françoise Bizette-Lindet, Châtenay-Malabry, 1972)');
convert_back('Titre inconnu (Françoise Bizette-Lindet, Paris, 1972)');
convert_back('Titre inconnu (Françoise Couvez, Cergy, 1990)');
convert_back('Titre inconnu (Frédéric Acquaviva)');
convert_back('Titre inconnu (Frédéric Bleuet, Paris, 1990)');
convert_back('Titre inconnu (Gabriel Loire, Le Mans, 1961)');
convert_back('Titre inconnu (Gaston Cadenat, Paris, 1958)');
convert_back('Titre inconnu (Gaston Watkin, Troyes, 1974)');
convert_back('Titre inconnu (Geneviève Dumont, Grenoble, 1981)');
convert_back('Titre inconnu (Georges Ball, Marseille, 1973)');
convert_back('Titre inconnu (Georges Cheyssial, Orsay, 1962)');
convert_back('Titre inconnu (Georges Guinot, Le Kremlin-Bicêtre, 1979)');
convert_back('Titre inconnu (Georges Gunsett, Grenoble, 1969)');
convert_back('Titre inconnu (Georges Jouve, Marseille, 1962)');
convert_back('Titre inconnu (Georges Jouve, Marseille, 1964)');
convert_back('Titre inconnu (Georges Mathieu, Limoges, 1981)');
convert_back('Titre inconnu (Georges Nadal et Albert Féraud, Bastia)');
convert_back('Titre inconnu (Georges Nadal, Draguignan, 1956)');
convert_back('Titre inconnu (Georges Rohner, Châtenay-Malabry, 1973)');
convert_back('Titre inconnu (Georges Rohner, Nice, 1970)');
convert_back('Titre inconnu (Georges Tautel, Saint-Étienne)');
convert_back('Titre inconnu (Georges Violet, Antony, 1976)');
convert_back('Titre inconnu (Gigi Guadagnucci, Créteil, 1973)');
convert_back('Titre inconnu (Gilles Aillaud, Lyon, 1987)');
convert_back('Titre inconnu (Gottfried Honegger, Nancy, 1986)');
convert_back('Titre inconnu (Groupe Munimenti, Corte)');
convert_back('Titre inconnu (Grégori Anatchkov, Lens, 1993)');
convert_back('Titre inconnu (Guerin, Pau, 1974)');
convert_back('Titre inconnu (Guidette Carbonell, Rouen)');
convert_back('Titre inconnu (Guidette Carbonell, Rouen, 1965)');
convert_back('Titre inconnu (Gustave Louis Jaulmes, Paris, 1953)');
convert_back('Titre inconnu (Gustave Singier, Aubusson, 1968)');
convert_back('Titre inconnu (Gustave Singier, Châtenay-Malabry, 1973)');
convert_back('Titre inconnu (Gustave Singier, Clermont-Ferrand)');
convert_back('Titre inconnu (Gustave Tiffoche, Saint-Nazaire, 1980)');
convert_back('Titre inconnu (Guy de Rogemont, Calais, 1986)');
convert_back('Titre inconnu (Guy de Rougemont, Amiens, 1990)');
convert_back('Titre inconnu (Guy Vacheret, Le Havre, 1992)');
convert_back('Titre inconnu (Guy-Rachel Grataloup, Créteil, 1984)');
convert_back('Titre inconnu (Gérard Ducouret, Calais, 1993)');
convert_back('Titre inconnu (Gérard Fromanger, Versailles, 1997)');
convert_back('Titre inconnu (Gérard Schlosser, Chaumont, 1965)');
convert_back('Titre inconnu (Gérard Singer, Saint-Denis, 1970)');
convert_back('Titre inconnu (Gérard-Henri Langeois, Troyes, 1961)');
convert_back('Titre inconnu (Hans Hedberg, Nice, 1964)');
convert_back('Titre inconnu (Hedberg, Marseille, 1967)');
convert_back('Titre inconnu (Henri Cueco, Livry-Gargan, 1974)');
convert_back('Titre inconnu (Henri Guérin, Toulouse, 1973)');
convert_back('Titre inconnu (Henri Lagriffoul)');
convert_back('Titre inconnu (Henri Marquet, Bordeaux, 1992)');
convert_back('Titre inconnu (Henri Marquet, Pau, 1993)');
convert_back('Titre inconnu (Henri Navarre, Toulouse, 1960)');
convert_back('Titre inconnu (Henri-Georges Adam, La Trinité)');
convert_back('Titre inconnu (Henri-Georges Adam, Nancy, 1967)');
convert_back('Titre inconnu (Henri-Georges Adam, Paris, 1966)');
convert_back('Titre inconnu (Henri-Georges Adam, Paris, 1971)');
convert_back('Titre inconnu (Henriette Lemercier, Charleville-Mézières)');
convert_back('Titre inconnu (Hervé Di Rosa, Montpellier, 1993)');
convert_back('Titre inconnu (Hervé Le Nost, Vandœuvre-lès-Nancy, 2000)');
convert_back('Titre inconnu (Hubert Duprat, Toulouse, 2005)');
convert_back('Titre inconnu (Hubert Yencesse, Dijon, 1957)');
convert_back('Titre inconnu (Hubert Yencesse, Dijon, 1962)');
convert_back('Titre inconnu (Hubert Yencesse, Dijon, 1965)');
convert_back('Titre inconnu (Hubert Yencesse, Dijon, 1969)');
convert_back('Titre inconnu (Hélène Rémy, Reims)');
convert_back('Titre inconnu (Ilan Wolff, Mulhouse, 2007)');
convert_back('Titre inconnu (Invader - 2017/04/14 11:28:46)');
convert_back('Titre inconnu (Irène Laksine, Saint-Denis, 1991)');
convert_back('Titre inconnu (Isabelle Braud, Limoges, 1992)');
convert_back('Titre inconnu (Jack Beng-Thi, Saint-Pierre)');
convert_back('Titre inconnu (Jacq Sinapi, Nancy, 1960)');
convert_back('Titre inconnu (Jacq Sinapi, Nancy, 1974)');
convert_back('Titre inconnu (Jacqueline Dauriac, Grenoble, 2001)');
convert_back('Titre inconnu (Jacques Bertoux, Créteil, 1972)');
convert_back('Titre inconnu (Jacques Bertoux, Paris, 1966)');
convert_back('Titre inconnu (Jacques Bertoux, Strasbourg, 1970)');
convert_back('Titre inconnu (Jacques Bosser, Aubière, 1998)');
convert_back('Titre inconnu (Jacques Chevallier, Beaumont-sur-Oise, 1958)');
convert_back('Titre inconnu (Jacques Choquin, Marignane)');
convert_back('Titre inconnu (Jacques Choquin, Nîmes)');
convert_back('Titre inconnu (Jacques Despierre, Châtenay-Malabry, 1973)');
convert_back('Titre inconnu (Jacques Despierre, Strasbourg, 1960)');
convert_back('Titre inconnu (Jacques Durand-Henriot, Rennes, 1969)');
convert_back('Titre inconnu (Jacques Fillacier, Dijon, 1952)');
convert_back('Titre inconnu (Jacques Fillacier, Saint-Étienne)');
convert_back('Titre inconnu (Jacques Lagrange, Besançon, 1964)');
convert_back('Titre inconnu (Jacques Lagrange, Lille, 1973)');
convert_back('Titre inconnu (Jacques Leuzy, Toulouse, 1959)');
convert_back('Titre inconnu (Jacques Monory, Rouen, 1982)');
convert_back('Titre inconnu (Jacques Pasquier, Caen, 1971)');
convert_back('Titre inconnu (Jacques Teulières, Tarbes, 1964)');
convert_back('Titre inconnu (Jacques Tissinier, Pau, 1992)');
convert_back('Titre inconnu (Jacques-Victor André, Arras, 1992)');
convert_back('Titre inconnu (Jacques-Victor André, Paris, 1989)');
convert_back('Titre inconnu (Jacques-Victor André, Valenciennes, 1991)');
convert_back('Titre inconnu (James Guittet, Paris, 1965)');
convert_back('Titre inconnu (Janick Rozo, Bobigny, 1972)');
convert_back('Titre inconnu (Jaroslav Serpan, Besançon, 1965)');
convert_back('Titre inconnu (Jean Allemand, Gif-sur-Yvette, 1979)');
convert_back('Titre inconnu (Jean Amado, Lyon, 1964)');
convert_back('Titre inconnu (Jean Amado, Lyon, 1967)');
convert_back('Titre inconnu (Jean Amado, Marseille, 1970)');
convert_back('Titre inconnu (Jean Amado, Marseille, 1971)');
convert_back('Titre inconnu (Jean Amado, Toulon, 1972)');
convert_back('Titre inconnu (Jean Amado, Vannes, 1973)');
convert_back('Titre inconnu (Jean Bazaine, Metz, 1977)');
convert_back('Titre inconnu (Jean Buffile, Bordeaux, 1967)');
convert_back('Titre inconnu (Jean Cardot, Saint-Étienne)');
convert_back('Titre inconnu (Jean Chauffrey, Annecy)');
convert_back('Titre inconnu (Jean Chauffrey, Brest, 1965)');
convert_back('Titre inconnu (Jean Claro, Poitiers)');
convert_back('Titre inconnu (Jean de Gaspary)');
convert_back('Titre inconnu (Jean Druille, Toulouse, 1967)');
convert_back('Titre inconnu (Jean Dubuffet, Nanterre, 1965)');
convert_back('Titre inconnu (Jean Gilles, Besançon, 1961)');
convert_back('Titre inconnu (Jean Gorin, Nancy, 1968)');
convert_back('Titre inconnu (Jean Jegoudez, Nantes, 1966)');
convert_back('Titre inconnu (Jean Kerbrat, Mont-Saint-Aignan, 1977)');
convert_back('Titre inconnu (Jean Kirastinnicos, Orléans, 1970)');
convert_back('Titre inconnu (Jean Laugier dit Beppo)');
convert_back('Titre inconnu (Jean Lurçat, Grenoble, 1967)');
convert_back('Titre inconnu (Jean Martin-Roch, Marseille, 1955)');
convert_back('Titre inconnu (Jean Moiras, Clermont-Ferrand, 1992)');
convert_back('Titre inconnu (Jean Picart Le Doux, Nancy)');
convert_back('Titre inconnu (Jean Picart Le Doux, Reims)');
convert_back('Titre inconnu (Jean Picart Le Doux, Toulouse, 1960)');
convert_back('Titre inconnu (Jean Sire, Auxerre, 1964)');
convert_back('Titre inconnu (Jean Teulieres, Toulouse, 1957)');
convert_back('Titre inconnu (Jean Torregrossa, Figari)');
convert_back('Titre inconnu (Jean Villemin, Épinal, 1993)');
convert_back('Titre inconnu (Jean Zetlaoui, Saint-Denis, 1971)');
convert_back('Titre inconnu (Jean, Grenoble, 1995)');
convert_back('Titre inconnu (Jean-André Cante, Nancy, 1977)');
convert_back('Titre inconnu (Jean-Ange Msika, Paris, 1977)');
convert_back('Titre inconnu (Jean-Baptiste Bonnardel, Pulversheim)');
convert_back('Titre inconnu (Jean-Bernard Métais, Toucy)');
convert_back('Titre inconnu (Jean-Christophe Nourisson, Nice, 2008)');
convert_back('Titre inconnu (Jean-claude Dutertre, Orléans, 1970)');
convert_back('Titre inconnu (Jean-Claude Jolet, Le Tampon)');
convert_back('Titre inconnu (Jean-Claude Lamborot, Caen, 1970)');
convert_back('Titre inconnu (Jean-Claude Rustin, Montigny-lès-Metz, 1957)');
convert_back('Titre inconnu (Jean-Jacques Dumont, Metz, 2003)');
convert_back('Titre inconnu (Jean-Jacques Prolongeau, Toulouse, 1966)');
convert_back('Titre inconnu (Jean-Jacques Staebler, Le Mans, 1970)');
convert_back('Titre inconnu (Jean-Jacques Staebler, Limoges, 1975)');
convert_back('Titre inconnu (Jean-Jacques Staebler, Nancy, 1968)');
convert_back('Titre inconnu (Jean-Jacques Staebler, Reims, 1966)');
convert_back('Titre inconnu (Jean-Jacques Staebler, Saint-Denis, 1971)');
convert_back('Titre inconnu (Jean-Louis Bilweiss, Nancy, 1970)');
convert_back('Titre inconnu (Jean-Louis Boudet, Toulouse, 1979)');
convert_back('Titre inconnu (Jean-Louis Cantin, Nice, 1999)');
convert_back('Titre inconnu (Jean-Louis Coursaget, Orléans, 1987)');
convert_back('Titre inconnu (Jean-Louis Lessard, Rouen, 1993)');
convert_back('Titre inconnu (Jean-Luc Perrot, Paris, 1964)');
convert_back('Titre inconnu (Jean-Luc Tartarin, Metz, 1998)');
convert_back('Titre inconnu (Jean-Luc Wilmouth, Grenoble, 1998)');
convert_back('Titre inconnu (Jean-Marc Lange, Belfort, 1975)');
convert_back('Titre inconnu (Jean-Max Hardy de Visme, Le Mans, 1991)');
convert_back('Titre inconnu (Jean-Philippe Aubanel, Lyon, 1988)');
convert_back('Titre inconnu (Jean-Pierre Bertrand, Amiens, 1997)');
convert_back('Titre inconnu (Jean-Pierre Da-Gioz, La Roche-sur-Yon, 1992)');
convert_back('Titre inconnu (Jean-Pierre Demarchi, Ajaccio)');
convert_back('Titre inconnu (Jean-Pierre Regnault, Gif-sur-Yvette, 1976)');
convert_back('Titre inconnu (Jean-Pierre Regnault, Rennes, 1973)');
convert_back('Titre inconnu (Jean-Pierre Viot, Nogent-sur-Vernisson)');
convert_back('Titre inconnu (Jean-Pierre Yvaral, Montpellier, 1972)');
convert_back('Titre inconnu (Jean-Vincent De Crozals, Nice, 1964)');
convert_back('Titre inconnu (Jeanne Beylot, Bordeaux, 1995)');
convert_back('Titre inconnu (Jeanne-Marie Bertaux, Torigni-sur-Vire)');
convert_back('Titre inconnu (Jesús-Rafael Soto, Rennes, 1968)');
convert_back('Titre inconnu (John-Franklin Koenig, Paris, 1965)');
convert_back('Titre inconnu (Joseph Ciesla, Limoges, 1979)');
convert_back('Titre inconnu (Joseph Ciesla, Lyon, 1994)');
convert_back('Titre inconnu (Joseph Ciesla, Lyon, 2006)');
convert_back('Titre inconnu (Joseph Rivière, Bordeaux, 1959)');
convert_back('Titre inconnu (Joseph Rivière, Marseille, 1960)');
convert_back('Titre inconnu (Joséphine Chevry, Brest, date inconnue)');
convert_back('Titre inconnu (Joséphine Chevry, Châtenay-Malabry, 1970)');
convert_back('Titre inconnu (Joël Ducorroy, Corbeil-Essonnes, 1955)');
convert_back('Titre inconnu (Joël Moulin, Reims, 1980)');
convert_back('Titre inconnu (Joël Paubel, Lyon, 2005)');
convert_back('Titre inconnu (Julien Quentel, Clisson)');
convert_back('Titre inconnu (Kim Haminsky, Sevenans, 1992)');
convert_back('Titre inconnu (Klaus Schultze, Ajaccio)');
convert_back('Titre inconnu (Klaus Schulze, Cergy, 1978)');
convert_back('Titre inconnu (Kling, Toulouse, 1960)');
convert_back('Titre inconnu (Kristian Gavoille, Cergy, 1993)');
convert_back('Titre inconnu (Kumquats, Épinal, 1997)');
convert_back('Titre inconnu (L\'Œuf centre d\'études, Bourg-en-Bresse, vers 1974)');
convert_back('Titre inconnu (Lardière, Nantes, 1977)');
convert_back('Titre inconnu (Lars Fredrikson, Nice, 1978)');
convert_back('Titre inconnu (Lebars, Rennes, 1973)');
convert_back('Titre inconnu (Lebe, Pau, 1974)');
convert_back('Titre inconnu (Liber David, Paris, 1980)');
convert_back('Titre inconnu (Liliane Tribel-Gruning, Lyon, 1974)');
convert_back('Titre inconnu (Lino Melano, Nice, 1967)');
convert_back('Titre inconnu (Livialdino De Poli, Mulhouse, 2005)');
convert_back('Titre inconnu (Louis Chavignier, Cachan, 1973)');
convert_back('Titre inconnu (Louis Leygue, Caen, 1955)');
convert_back('Titre inconnu (Louis Leygue, Granville)');
convert_back('Titre inconnu (Louis Leygue, Marly-le-Roi, 1966)');
convert_back('Titre inconnu (Louis Leygue, Paris, 1986)');
convert_back('Titre inconnu (Louis Leygue, Toulouse, 1960)');
convert_back('Titre inconnu (Louis Nallard, Nantes, 1977)');
convert_back('Titre inconnu (Louis-Marie Julien, Audrieu, 1969)');
convert_back('Titre inconnu (Louis-Marie Jullien, Caen, 1970)');
convert_back('Titre inconnu (Louis-Marie Jullien, Marseille, 1956)');
convert_back('Titre inconnu (Louis-Marie Jullien, Marseille, 1961)');
convert_back('Titre inconnu (Louis-Marie Jullien, Marseille, 1962)');
convert_back('Titre inconnu (Louis-Marie Jullien, Marseille, 1970)');
convert_back('Titre inconnu (Louis-Marie Jullien, Nice, 1972)');
convert_back('Titre inconnu (Louis-Marie Jullien, Nice, 1973)');
convert_back('Titre inconnu (Louis-Marie Jullien, Strasbourg, 1966)');
convert_back('Titre inconnu (Louttre.B, Nantes, 1977)');
convert_back('Titre inconnu (Luc Peire, Angers, 1974)');
convert_back('Titre inconnu (Lucien Fleury, Montrouge, 1978)');
convert_back('Titre inconnu (Lucien Fontanarosa, Nantes, 1966)');
convert_back('Titre inconnu (Luis Feito, Paris, 1967)');
convert_back('Titre inconnu (Luis Tomasello, Marseille, 1971)');
convert_back('Titre inconnu (Lydia Leblanc, Le Mans, 1996)');
convert_back('Titre inconnu (Lydia Leblanc, Le Mans, 1997)');
convert_back('Titre inconnu (Léon Gischia, Cachan, 1963)');
convert_back('Titre inconnu (Léon Marchutz, Nice, 1969)');
convert_back('Titre inconnu (Léon Robert, Besançon, 1953)');
convert_back('Titre inconnu (Léon Toublanc, Orléans, 1963)');
convert_back('Titre inconnu (Lézards)');
convert_back('Titre inconnu (Manuel Forestini, Grenoble, 2003)');
convert_back('Titre inconnu (Marc Couturier, Nice)');
convert_back('Titre inconnu (Marc Halter, Évry, 1979)');
convert_back('Titre inconnu (Marcel Bodart, Charleville-Mézières, 1970)');
convert_back('Titre inconnu (Marcel Gili, Ajaccio)');
convert_back('Titre inconnu (Marcel Gili, Nice, 1970)');
convert_back('Titre inconnu (Marcel Gili, Quimper, 1975)');
convert_back('Titre inconnu (Marcel Homs, Charleville-Mézières, 1969)');
convert_back('Titre inconnu (Marcel Petit, Brest, 1975)');
convert_back('Titre inconnu (Marcel Petit, Paris, 1976)');
convert_back('Titre inconnu (Marcelle Cahn, Dijon)');
convert_back('Titre inconnu (Marcelle Cahn, Is-sur-Tille)');
convert_back('Titre inconnu (Marguerite Huré)');
convert_back('Titre inconnu (Marguerite Lavrillier, Fontenay-aux-Roses, 1967)');
convert_back('Titre inconnu (Marguerite Lavrillier, Paris, 1953)');
convert_back('Titre inconnu (Marguerite Lavrillier, Paris, 1960)');
convert_back('Titre inconnu (Maria Helena Vieira da Silva, Pessac)');
convert_back('Titre inconnu (Marie-Ange Guilleminot, Bornel, 2007)');
convert_back('Titre inconnu (Marino di Teana, Reims, 1980)');
convert_back('Titre inconnu (Marino di Teana, Thann, 1968)');
convert_back('Titre inconnu (Mario Prassinos, Arles, 1985)');
convert_back('Titre inconnu (Mario Prassinos, Aubusson, 1968)');
convert_back('Titre inconnu (Mario Prassinos, Bordeaux, 1976)');
convert_back('Titre inconnu (Mario Prassinos, Sisteron)');
convert_back('Titre inconnu (Mark Handforth, Lyon, 2008)');
convert_back('Titre inconnu (Marta Colvin, Montrouge, 1978)');
convert_back('Titre inconnu (Marta Pan, Paris, 1965)');
convert_back('Titre inconnu (Marthe Schwenck, Paris, 1967)');
convert_back('Titre inconnu (Martine Bedin, Bordeaux, 1996 ?)');
convert_back('Titre inconnu (Marylène Negro, La Rochelle, 2004)');
convert_back('Titre inconnu (Massaud, Nantes, 1997)');
convert_back('Titre inconnu (Maurice Bertrand, Versailles, 1965)');
convert_back('Titre inconnu (Maurice Calka, Orléans, 1975)');
convert_back('Titre inconnu (Maurice Calka, Poitiers, 1970)');
convert_back('Titre inconnu (Maurice Calka, Poitiers, 1975)');
convert_back('Titre inconnu (Maurin, Pessac)');
convert_back('Titre inconnu (Max Ingrand, Strasbourg, 1969)');
convert_back('Titre inconnu (Max Mathieu, Grenoble, 1966)');
convert_back('Titre inconnu (Maxime Adam-Tessier, Saint-Maur-des-Fossés, 1971)');
convert_back('Titre inconnu (Maxime Descombin, Mulhouse, 1969)');
convert_back('Titre inconnu (Michael Prentice, Brive-la-Gaillarde, 1993)');
convert_back('Titre inconnu (Michaël Lin, Tallard)');
convert_back('Titre inconnu (Michel Carlin, Toulon, 1981)');
convert_back('Titre inconnu (Michel Deverne, Brest, 1977)');
convert_back('Titre inconnu (Michel Deverne, Valenciennes, 1984)');
convert_back('Titre inconnu (Michel Gomez, Aix-en-Provence, 1978)');
convert_back('Titre inconnu (Michel Henry, Chaumont, 1965)');
convert_back('Titre inconnu (Michel Mazé, Grenoble)');
convert_back('Titre inconnu (Michel Mazé, Limoges)');
convert_back('Titre inconnu (Michel Platino, Limoges, 1994)');
convert_back('Titre inconnu (Michel Rivière, Villeneuve-d\'Ascq, 1977)');
convert_back('Titre inconnu (Michel Saint-Olive et Éliane Saint-Olive, Paris, 1974)');
convert_back('Titre inconnu (Michel Saint-Olive, Dijon)');
convert_back('Titre inconnu (Michel Saint-Olive, Pessac, 1967)');
convert_back('Titre inconnu (Michel Seuphor, Châtenay-Malabry, 1973)');
convert_back('Titre inconnu (Michel Tourlière, Torigni-sur-Vire, 1980)');
convert_back('Titre inconnu (Michel Zachariou, Bordeaux, 1986)');
convert_back('Titre inconnu (Michèle Ackerer-Ronget, Mulhouse, 2006)');
convert_back('Titre inconnu (Michèle Blondel, Longwy, 1985)');
convert_back('Titre inconnu (Michèle dite Michell Bargoin, Aubière, 1978)');
convert_back('Titre inconnu (Michèle Sylvander, Nice, 1996)');
convert_back('Titre inconnu (Miguel Chevalier, Bayonne, 1995)');
convert_back('Titre inconnu (Monique Chapelet, Angers, 1992)');
convert_back('Titre inconnu (Mr Marcon, Melesse, 1992)');
convert_back('Titre inconnu (Mrzyk & Moriceau, Marcy-l\'Étoile, 2007)');
convert_back('Titre inconnu (Myriam Bros, Lyon, 1967)');
convert_back('Titre inconnu (Nelly Marty, Bordeaux, 1959)');
convert_back('Titre inconnu (Nicolas De Jaeger, Rouen, 1962)');
convert_back('Titre inconnu (Nicolas De Jaeger, Rouen, 1966)');
convert_back('Titre inconnu (Nicolas Untersteller, Metz, 1963)');
convert_back('Titre inconnu (Nicolas Valabrègue, Nice, 1975)');
convert_back('Titre inconnu (Nino Calos, Nantes, 1975)');
convert_back('Titre inconnu (Norbert Morage, Valenciennes, 1990)');
convert_back('Titre inconnu (Noël Bonardi, Ajaccio)');
convert_back('Titre inconnu (Noël Pasquier et André Borderie, Brest)');
convert_back('Titre inconnu (Odette Pauvert, 19e arrondissement, 1933)');
convert_back('Titre inconnu (Olivier Debré, Tours, 1973)');
convert_back('Titre inconnu (Olivier Leroi, Grenoble, 2008)');
convert_back('Titre inconnu (Olivier Liégent, Charleville-Mézières, 1998)');
convert_back('Titre inconnu (Ossip Zadkine, Marseille, 1964)');
convert_back('Titre inconnu (Ossip Zadkine, Marseille, 1969)');
convert_back('Titre inconnu (Ossip Zadkine, Paris, 20e)');
convert_back('Titre inconnu (Ossip Zadkine, Pau, 1968)');
convert_back('Titre inconnu (Paolo Santini, Paris, 1968)');
convert_back('Titre inconnu (Pascal Broccolichi, Tulle, 2001)');
convert_back('Titre inconnu (Pascal Legallois et Carole Le Blay, Laval, 2000)');
convert_back('Titre inconnu (Pascal Pinaud, Marseille, 2005)');
convert_back('Titre inconnu (Pascale Bas, Le Creusot, 1992)');
convert_back('Titre inconnu (Patrice Azzolin, Nantes, 1995)');
convert_back('Titre inconnu (Patrick Corillon, Tours, 2007)');
convert_back('Titre inconnu (Patrick Verchère, Villeurbanne)');
convert_back('Titre inconnu (Paul Aïzpiri, Reims, 1967)');
convert_back('Titre inconnu (Paul Becker, Paris, 1965)');
convert_back('Titre inconnu (Paul Belmondo, Caen, 1962)');
convert_back('Titre inconnu (Paul Belmondo, Ollainville, 1957)');
convert_back('Titre inconnu (Paul Belmondo, Paris, 1957)');
convert_back('Titre inconnu (Paul Cheriau, Le Kremlin-Bicêtre, 1979)');
convert_back('Titre inconnu (Paul Griot, Paimpont, 1972)');
convert_back('Titre inconnu (Paul Griot, Rennes, 1967)');
convert_back('Titre inconnu (Paul Guiramand, Grenoble, 1963)');
convert_back('Titre inconnu (Paul Guéry, Montpellier, 1965)');
convert_back('Titre inconnu (Paul Lemagny, Bar-le-Duc, 1962)');
convert_back('Titre inconnu (Paul Lemagny, Rennes, 1961)');
convert_back('Titre inconnu (Paul Manaut, Agen, 1958)');
convert_back('Titre inconnu (Paul Turmel, Strasbourg, 1993)');
convert_back('Titre inconnu (Perromat, Périgueux, 1966)');
convert_back('Titre inconnu (Peter Downsbrough, Le Creusot, 1998)');
convert_back('Titre inconnu (Philippe Cazal, Montpellier, 2002)');
convert_back('Titre inconnu (Philippe Favier, Lyon, 1988)');
convert_back('Titre inconnu (Philippe Guillemet, Le Havre, 1993)');
convert_back('Titre inconnu (Philippe Hiquily, Bordeaux, 1970)');
convert_back('Titre inconnu (Philippe Kaeppelin, Albi, 1957)');
convert_back('Titre inconnu (Philippe Lelièvre, Paris, 1961)');
convert_back('Titre inconnu (Philippe Morisson, Nancy, 1979)');
convert_back('Titre inconnu (Philippe Niez, Bordeaux, 1997)');
convert_back('Titre inconnu (Philippe Niez, Mérignac, 1997)');
convert_back('Titre inconnu (Philippe Ramette, Mouans-Sartoux)');
convert_back('Titre inconnu (Philolaos, Aubière)');
convert_back('Titre inconnu (Philolaos, Le Mans, 1969)');
convert_back('Titre inconnu (Philolaos, Nantes, 1977)');
convert_back('Titre inconnu (Philolaos, Toulouse, 1973)');
convert_back('Titre inconnu (Pier Paolo Calzolari, Orléans, 1998)');
convert_back('Titre inconnu (Pierre Baey, Mont-Saint-Aignan)');
convert_back('Titre inconnu (Pierre Bares, Bayonne, 1958)');
convert_back('Titre inconnu (Pierre Belvès, Firminy, 1961)');
convert_back('Titre inconnu (Pierre Bonneval, Orléans, 1970)');
convert_back('Titre inconnu (Pierre Brun, Lille, 1971)');
convert_back('Titre inconnu (Pierre Brun, Poitiers, 1968)');
convert_back('Titre inconnu (Pierre Brun, Strasbourg, 1972)');
convert_back('Titre inconnu (Pierre Buraglio, Champs-sur-Marne, 1996)');
convert_back('Titre inconnu (Pierre Buraglio, Lyon, 1993)');
convert_back('Titre inconnu (Pierre Buraglio, Paris, 1996)');
convert_back('Titre inconnu (Pierre Chevalley, Belfort, 1971)');
convert_back('Titre inconnu (Pierre de Berroeta, La Rochelle, 1973)');
convert_back('Titre inconnu (Pierre di Sciullo, Bobigny, 1997)');
convert_back('Titre inconnu (Pierre Fichet, Paris, 1964)');
convert_back('Titre inconnu (Pierre Honoré, Dijon, 1957)');
convert_back('Titre inconnu (Pierre Honoré, Dijon, 1962)');
convert_back('Titre inconnu (Pierre Koppe, Strasbourg, 1966)');
convert_back('Titre inconnu (Pierre Lebe, Toulouse, 1973)');
convert_back('Titre inconnu (Pierre Lohner, Strasbourg, 1972)');
convert_back('Titre inconnu (Pierre Louisin, Bordeaux, 1967)');
convert_back('Titre inconnu (Pierre Olivier, Valenciennes, 1996)');
convert_back('Titre inconnu (Pierre Parsus, Montpellier, 1969)');
convert_back('Titre inconnu (Pierre Plattier, Lyon, 1987)');
convert_back('Titre inconnu (Pierre Sabatier, Cachan, 1962)');
convert_back('Titre inconnu (Pierre Sabatier, Calais, 1986)');
convert_back('Titre inconnu (Pierre Sabatier, Dijon, 1973)');
convert_back('Titre inconnu (Pierre Sabatier, Dijon, 1976)');
convert_back('Titre inconnu (Pierre Sabatier, Gif-sur-Yvette, 1976)');
convert_back('Titre inconnu (Pierre Sabatier, Mont-Saint-Aignan, 1977)');
convert_back('Titre inconnu (Pierre Sabatier, Neuvy, 1968)');
convert_back('Titre inconnu (Pierre Sabatier, Paris, 1969)');
convert_back('Titre inconnu (Pierre Sabatier, Rouen)');
convert_back('Titre inconnu (Pierre Sabatier, Saint-Denis, 1970)');
convert_back('Titre inconnu (Pierre Soulages, Villeneuve-d\'Ascq, 1972)');
convert_back('Titre inconnu (Pierre Székely, Grenoble, 1971)');
convert_back('Titre inconnu (Pierre Theneusen, Saint-Raphaël, 1979)');
convert_back('Titre inconnu (Pierre Theze, Saint-Barthélemy-d\'Anjou, 1973)');
convert_back('Titre inconnu (Pierre Théron, Libourne, 1959)');
convert_back('Titre inconnu (Pierre-Alain Hubert, Marseille, 1975)');
convert_back('Titre inconnu (Pierre-Aymon Bernard, Créteil, 1985)');
convert_back('Titre inconnu (Pierre-Noël Drain, Grenoble, 1967)');
convert_back('Titre inconnu (Pierre-Paul Desrumeaux, Lille, 1965)');
convert_back('Titre inconnu (Pierre-Paul Desrumeaux, Lille, 1970)');
convert_back('Titre inconnu (Pierrick Tual, Mont-Saint-Aignan, 1977)');
convert_back('Titre inconnu (Pignon, Marseille, 1967)');
convert_back('Titre inconnu (Pol Abraham, 19e arrondissement)');
convert_back('Titre inconnu (Pol Bury, Montpellier, 1974)');
convert_back('Titre inconnu (Port-Vendres) (Invader)');
convert_back('Titre inconnu (R.G)');
convert_back('Titre inconnu (Raoul Ubac, Avignon)');
convert_back('Titre inconnu (Raoul Ubac, Châtenay-Malabry, 1973)');
convert_back('Titre inconnu (Raymond Gid, Nancy, 1981)');
convert_back('Titre inconnu (Raymond Gosselin, Rouen, 1982)');
convert_back('Titre inconnu (Raymond Subes, Angers, 1953)');
convert_back('Titre inconnu (Raymond Subes, Angers, 1960)');
convert_back('Titre inconnu (Raymond Subes, Caen, 1970)');
convert_back('Titre inconnu (Raymond Subes, Grenoble)');
convert_back('Titre inconnu (Raymond Subes, Lille, 1958)');
convert_back('Titre inconnu (Raymond Subes, Paris, 1965)');
convert_back('Titre inconnu (Raymond Subes, Rennes, 1967)');
convert_back('Titre inconnu (Raymond Subes, Toulouse, 1960)');
convert_back('Titre inconnu (Raymond Veysset, Paris, 1961)');
convert_back('Titre inconnu (Raymond Veysset, Tulle, 1957)');
convert_back('Titre inconnu (Raymond-Jean Jupille, Moulins, 1959)');
convert_back('Titre inconnu (Rayond Veysset, Paris, 1961)');
convert_back('Titre inconnu (Renato Montanaro, Mulhouse, 2006)');
convert_back('Titre inconnu (René Blanc, Strasbourg, 1957)');
convert_back('Titre inconnu (René Collamarini, La Verrière, 1968)');
convert_back('Titre inconnu (René Collamarini, Pointe-à-Pitre, 1959)');
convert_back('Titre inconnu (René Fumeron, Poitiers, 1973)');
convert_back('Titre inconnu (René Iché)');
convert_back('Titre inconnu (René Letourneur, Lille, 1958)');
convert_back('Titre inconnu (René Letourneur, Lille, 1961)');
convert_back('Titre inconnu (René-Paul Savignan, Le Tampon, 2007)');
convert_back('Titre inconnu (Reynold Arnould, Le Havre, 1966)');
convert_back('Titre inconnu (Richard Fauguet, Limoges, 2003)');
convert_back('Titre inconnu (Robert Couturier, Châtenay-Malabry, 1972)');
convert_back('Titre inconnu (Robert Couturier, Soissons)');
convert_back('Titre inconnu (Robert Husset, Versailles, 1964)');
convert_back('Titre inconnu (Robert Juvin, La Rochelle, 1973)');
convert_back('Titre inconnu (Robert Pagès, Campistrous, 1969)');
convert_back('Titre inconnu (Robert Perot, Grenoble, 1972)');
convert_back('Titre inconnu (Robert Pillods, Montpellier, 1966)');
convert_back('Titre inconnu (Robert Rigot, Troyes, 1965)');
convert_back('Titre inconnu (Robert Savary, Grenoble, 1967)');
convert_back('Titre inconnu (Robert Vernet, Marseille, 1970)');
convert_back('Titre inconnu (Robert Wogensky, Limoges, 1972)');
convert_back('Titre inconnu (Robert Wogensky, Mulhouse, 1969)');
convert_back('Titre inconnu (Robert Wogensky, Paris, 1965)');
convert_back('Titre inconnu (Robert Wogensky, Paris, 1967)');
convert_back('Titre inconnu (Robert Wogensky, Strasbourg, 1968)');
convert_back('Titre inconnu (Robert Wogensky, Strasbourg, 1974)');
convert_back('Titre inconnu (Roger Bezombes, Lannion, 1973)');
convert_back('Titre inconnu (Roger Chapelain-Midy, Rennes, 1970)');
convert_back('Titre inconnu (Roger Lersy, Brest)');
convert_back('Titre inconnu (Roger Lersy, Montluçon, 1970)');
convert_back('Titre inconnu (Roger Lersy, Strasbourg, 1977)');
convert_back('Titre inconnu (Roger Pfund, Villeurbanne)');
convert_back('Titre inconnu (Roger Vieillard, Troyes, 1966)');
convert_back('Titre inconnu (Rémi Ucheda, Montpellier, 1994)');
convert_back('Titre inconnu (Saint-Maur, Le Mans, 1961)');
convert_back('Titre inconnu (Salomé Venard, Paris, 1969)');
convert_back('Titre inconnu (Samuel Rousseau, Clermont-Ferrand, 2007)');
convert_back('Titre inconnu (Santini, Rennes, 1968)');
convert_back('Titre inconnu (Schneider, Metz, 1997)');
convert_back('Titre inconnu (Shamaï Haber, Amiens, 1970)');
convert_back('Titre inconnu (Shamaï Haber, Le Creusot, 1975)');
convert_back('Titre inconnu (Shamaï Haber, Lyon, 1971)');
convert_back('Titre inconnu (Shamaï Haber, Mâcon, 1972)');
convert_back('Titre inconnu (Shamaï Haber, Paris, 1973)');
convert_back('Titre inconnu (Silvia Beju, Nevers, 1998)');
convert_back('Titre inconnu (Simon Hantai, Trappes, 1973)');
convert_back('Titre inconnu (stelios faitakis)');
convert_back('Titre inconnu (Stéphane Calais, Besançon)');
convert_back('Titre inconnu (Sybille Paquet, Grenoble, 1974)');
convert_back('Titre inconnu (Sylvain Dubuisson, Sceaux, 1995)');
convert_back('Titre inconnu (Sylvie Blocher, Aix-en-Provence)');
convert_back('Titre inconnu (Séraphin Gilly, Aix-en-Provence, 1954)');
convert_back('Titre inconnu (Séraphin Gilly, Marseille, 1955)');
convert_back('Titre inconnu (Tamanoir Studio, Nancy, 1996)');
convert_back('Titre inconnu (Tania Mouraud, Lieusaint, 1997)');
convert_back('Titre inconnu (Thibaud, Limoges, 1977)');
convert_back('Titre inconnu (Thibaut Weisz, Limoges, 1977)');
convert_back('Titre inconnu (Thierry Dufourmantelle, Valenciennes, 1997)');
convert_back('Titre inconnu (Thierry Fontaine, Saint-Pierre)');
convert_back('Titre inconnu (Thierry Géhin, Chenôve)');
convert_back('Titre inconnu (Thierry Vide, Metz, 1982)');
convert_back('Titre inconnu (Thérèse Laflèche, Montpellier)');
convert_back('Titre inconnu (Tourlière, Le Creusot, 1975)');
convert_back('Titre inconnu (Ulysse Gemignani, Conflans-Sainte-Honorine, 1971)');
convert_back('Titre inconnu (Vadime Androusov, Agen, 1958)');
convert_back('Titre inconnu (Vassiliki Tsekoura, Montreuil, 1997)');
convert_back('Titre inconnu (Veit Stratmann, Troyes, 2009)');
convert_back('Titre inconnu (Victor Vasarely, Aix-en-Provence, 1973)');
convert_back('Titre inconnu (Victor Vasarely, Aix-en-Provence, 1989)');
convert_back('Titre inconnu (Victor Vasarely, Aubière, 1972)');
convert_back('Titre inconnu (Victor Vasarely, Marseille, 1970)');
convert_back('Titre inconnu (Victor Vasarely, Montpellier, 1991)');
convert_back('Titre inconnu (Vincent Bioulès, Montpellier, 1965)');
convert_back('Titre inconnu (Vincent Dufaud, Lyon, 1993)');
convert_back('Titre inconnu (Vincent Prudhomme, Grenoble, 2004)');
convert_back('Titre inconnu (Vladimir Škoda, Aubière, 2003)');
convert_back('Titre inconnu (Vladimir Škoda, Strasbourg)');
convert_back('Titre inconnu (Yaacov Agam, Montpellier, 1969)');
convert_back('Titre inconnu (Yvan Erpeldinger, Toulouse, 1968)');
convert_back('Titre inconnu (Yves Belorgey, Annecy, 1995)');
convert_back('Titre inconnu (Yves Brayer, Toulouse, 1964)');
convert_back('Titre inconnu (Yves Guérin, Aubière, 1988)');
convert_back('Titre inconnu (Yves Millecamps, Pouzauges, 1973)');
convert_back('Titre inconnu (Yves Trémorin, Dijon, 1999)');
convert_back('Titre inconnu (Yves Trévédy, Brest, 1963)');
convert_back('Titre inconnu (Yves Varaguin, Vermenton)');
convert_back('Titre inconnu (Yves Videau, Égletons, 1972)');
convert_back('Titre inconnu (Yvette Vincent-Alleaume, Saint-Barthélemy-d\'Anjou, 1977)');
convert_back('Titre inconnu (Yvonne Gouts-Guegan, Caen, 1962)');
convert_back('Titre inconnu (Yzo)');
convert_back('Titre inconnu (Ádám Sjöholm, Champs-sur-Marne, 1992)');
convert_back('Titre inconnu (Émile Dorée, Équeurdreville-Hainneville)');
convert_back('Titre inconnu (Émile Gilioli, Metz, 1975)');
convert_back('Titre inconnu (Émile Gilioli, Nanterre, 1972)');
convert_back('Titre inconnu (Émile Gilioli, Saint-Étienne)');
convert_back('Titre inconnu (Émile Morlaix, Douai, 1957)');
convert_back('Titre inconnu (Émile Morlaix, Lille, 1965)');
convert_back('Titre inconnu (Éric Gouret, Savenay, 2012)');
convert_back('Titre inconnu (Éric Pongerard, Saint-Denis)');
convert_back('Titre inconnu (Étienne Bossut, Nevers, 1997)');
convert_back('Titre inconnu (Étienne Hajdu, Dijon, 1968)');
convert_back('Titre inconnu (Étienne Hajdu, Dijon, 1975)');
convert_back('Titre inconnu (Étienne Hajdu, Nice, 1969)');
convert_back('Titre inconnu (Étienne Hajdu, Strasbourg, 1976)');
convert_back('Titre inconnu (Étienne Vago, Paris, 1978)');
convert_back('Titre inconnu - (Valérie Izzo)');
convert_back('Todos juntos podemos parar el sida (Keith Haring)');
convert_back('Together (Cyrille André)');
convert_back('Toi et moi (Louise Bourgeois)');
convert_back('Toi(t) en perspective (Rainer Gross)');
convert_back('Toi(t) à terre (Rainer Gross)');
convert_back('Tony ! (Bruno Yvonnet)');
convert_back('Totem (Guy de Rougemont)');
convert_back('Totem (Pierre Theunissen)');
convert_back('Totem en feux (Katerine Louineau)');
convert_back('Totem Giga la vie (Jean-Charles de Castelbajac)');
convert_back('Totems (Laurent Le Deunff)');
convert_back('Totems (Pierre di Sciullo)');
convert_back('Totems les Magnolias (Elizabeth Foyé)');
convert_back('Totipotent architecture (Lucy Orta)');
convert_back('Tour de biodiversité (Angelo Vermeulen)');
convert_back('Tour des rêves (Berto Lardera)');
convert_back('Tour et Aléa (Jean-Gabriel Coignet)');
convert_back('Tour-lanterne d\'Alençon (Salomé Venard)');
convert_back('Tourelle d\'y Voir (Erik Nussbicker)');
convert_back('Tourne-sol (Elisabeth Ballet)');
convert_back('Tous les soleils (Claude Lévêque)');
convert_back('Traces de ferveur bleue (Olivier Debré)');
convert_back('Trait d\'union (Éric Duyckaerts)');
convert_back('Trait pour trait (Élisabeth Ballet)');
convert_back('Traits d\'union (Hervé Mathieu-Bachelot et André Ropion)');
convert_back('Tram / Trame (Daniel Buren)');
convert_back('Trames 3°-87°-93°-183° (François Morellet)');
convert_back('Tranchée (Alexandra Engelfriet)');
convert_back('Transatlantic Flowerbed (Claudia Losi)');
convert_back('Transfert (Laura Lamiel)');
convert_back('Transfert (Philippe Lepeut)');
convert_back('Transfert (Vincent Prud\'homme)');
convert_back('Translucide (Jean-François Cantin)');
convert_back('Trapèze désaxé autour du rectangle (Felice Varini)');
convert_back('Travelling (Élisabeth Ballet)');
convert_back('Tre paesaggi (Giuseppe Penone)');
convert_back('Tre Éldar (Hulda Hákon)');
convert_back('Tree (Paul McCarthy)');
convert_back('Treecycle (Jean-Charles Blanc)');
convert_back('Treetent (Dré Wapenaar)');
convert_back('Triangle (Silvio Mattioli)');
convert_back('Trias (Silvio Mattioli)');
convert_back('Trichoptères (Hubert Duprat)');
convert_back('Tricotin et Conversation (Olga Boldyreff, Saint-Nazaire, 2001)');
convert_back('Triple suite en hommage à Van Gogh (Albert Ayme)');
convert_back('Triplechaton (Alain Séchas)');
convert_back('Triptychos post historicus ou la dernière bataille de Paolo Uccello (Braco Dimitrijevic)');
convert_back('Trois croix d\'acier (Bernar Venet)');
convert_back('Trois sans nom (Sébastien Vonier)');
convert_back('Tu me fais tourner la tête (Pierre Ardouvin)');
convert_back('Tuandomino (http://tuandomino99.tk/)');
convert_back('Tumulte (Claude Mercier)');
convert_back('Turbin (Matthieu Pilaud)');
convert_back('Turbo Tango (Julia Cottin)');
convert_back('Tureluur (Koen De Decker)');
convert_back('Tutto (Alighiero Boetti)');
convert_back('Twisted Cube (Karina Bisch)');
convert_back('Twisted Lamppost Star (Mark Handforth)');
convert_back('Two Thumbs up Monument (Guillaume Pilet)');
convert_back('Tête de berger (Jean Roulland)');
convert_back('Ubiquité (Jean-Gabriel Coignet)');
convert_back('Ultimo Cielo (Battista Lena)');
convert_back('Un adulte bâtit les fondations de son bon sens sur la chute certaine d’un objet qui n’a pas de support (Émilie Perotto)');
convert_back('Un angle, deux vues pour trois arcs (François Morellet)');
convert_back('Un cercle et mille fragments (Felice Varini)');
convert_back('Un quillier en torchis (Stéphane Magnin)');
convert_back('Un seuil pour le ciel (Natacha Guillaumont)');
convert_back('Un seul ticket pour un même manège (Nicolas Hérubel)');
convert_back('Une fenêtre en forêt (Hiroshi Teshima)');
convert_back('Une haie utopique et fondée (Marie Lansac, Gerco de Ruijter)');
convert_back('Une hirondelle ne fait pas le Printemps (Cyrille André)');
convert_back('Une maison de village (Marc Barani)');
convert_back('Une multiplication (Ernesto Sartori)');
convert_back('Une naissance (Yasuo Mizui)');
convert_back('Une rencontre, la métis, le même et l\'autre (Bruno Peinado)');
convert_back('Une ronde (Yasuo Mizui)');
convert_back('Une seconde lune pour Vlimmeren (Sarah van Sonsbeeck)');
convert_back('Unes Hybrides (Kelley Walker)');
convert_back('Unicorn horn (James Lee Byars)');
convert_back('Unis vers elle (Isabelle Rouquette)');
convert_back('Unite / Move Around / Workings (Tatsuji Ushijima)');
convert_back('Unitehope Angel (Lehna Edwall)');
convert_back('Universelle (Xavier de Fraissinette)');
convert_back('Université (Marino di Teana)');
convert_back('Untitled (De Kaai) (Peter de Graaf)');
convert_back('Untitled (De Markten) (Niele Toroni)');
convert_back('Untitled (Flowers Blinde Muren) (Michael Lin)');
convert_back('Untitled (New York Marble Sculpture) (Cyprien Gaillard)');
convert_back('Urbanité (Fernando Costa)');
convert_back('Urgence 69 (Pierre Brun)');
convert_back('Utopia : 8215 km dans le 269° (Klaus Heid)');
convert_back('Utsurohi (Aiko Miyawaki)');
convert_back('Vaguement (François Morellet)');
convert_back('Vagues de briques (Yvette Alleaume et Bernard Vincent)');
convert_back('Varde (Per Kirkeby)');
convert_back('Veemarkt Kortrijk (Stefan Balkenhol)');
convert_back('Vendanges (Jean Lurçat)');
convert_back('Viaduc de Terrenoire (John M Armleder)');
convert_back('Vierge des phoquiers (Félix Férioli)');
convert_back('Villa cheminée (Tatzu Nishi)');
convert_back('Villeneuve les Maguelones gaol (Jean-Luc Le Gac, Hervé di rosa)');
convert_back('Vindenes hus (Sissel Tolaas)');
convert_back('Vingt-cinq carrés bleus en damier (Felice Varini)');
convert_back('Vis Mineralis (Stéphanie Cherpin)');
convert_back('Vis-à-Vis (Véronique Joumard)');
convert_back('Vision Verticale (Marvin Gaye Chetwynd)');
convert_back('Vitrail virtuel (David Boeno)');
convert_back('Vitraux (Daniel Coulet)');
convert_back('Vitraux (Stéphane Belzère)');
convert_back('Vitraux - Église Oisilly (Marc Couturier)');
convert_back('Vitteaux (Sylvie Fleury)');
convert_back('Vivaldi (Mark di Suvero)');
convert_back('Vive la mariée... (Yvette et Bernard Alleaume)');
convert_back('Vive le vent (Michel Deverne)');
convert_back('Void to Void (Matias Faldbakken et Leander Djønne)');
convert_back('Voile bleue (Jean-Paul Van Lith)');
convert_back('Volis et Chandelles (Dominique Blais)');
convert_back('Volume 19 (Gottfried Honegger)');
convert_back('Volutes en gerbes (Hervé Mathieu-Bachelot)');
convert_back('Vous me direz (Élisabeth Ballet)');
convert_back('Vous n’êtes pas ici (Sandrine Raquin)');
convert_back('Vox populi (Rodolphe Burger)');
convert_back('VSML (Hervé Audibert, Bernard Moninot)');
convert_back('Vues de Strasbourg (Alain Séchas)');
convert_back('Wagner sur le toit (Jean-Pierre Bourquin)');
convert_back('Wall Drawing n° 711 (Sol LeWitt)');
convert_back('Wall Drawing n°649 (Sol LeWitt)');
convert_back('Wall-Drawing n°752 (Sol LeWitt)');
convert_back('WAM (Tjeerd Alkema et Lydie Chauvac)');
convert_back('Wampicôn (Patrick Bernier et Olive Martin)');
convert_back('Water in the Lilies (Roy Lichtenstein)');
convert_back('Way through (PASCAL BRATEAU)');
convert_back('What a Wonderful World! (Roland Cognet)');
convert_back('While you weight 1976 – 2009 (Cildo Meireles)');
convert_back('Wijkgezondheidscentrum (Lionel Estève)');
convert_back('Wikiki (Vincent Kohler)');
convert_back('Wild Protest (Junko Hiroshige, Thomas Tilly)');
convert_back('Wind Fort (Yasuyoshi Sugiura)');
convert_back('Woman Walking to the Sky (Jonathan Borofsky)');
convert_back('Work nº054-2 : Keep warm burnout the rich (stabile) (Nøne Futbol Club)');
convert_back('Y d\'If (François Bouillon)');
convert_back('Y du pronom au prénom (Yvan Le Bozec)');
convert_back('Yané (Teruhisa Suzuki)');
convert_back('Yellow Submarine (Emilio Lopez-Menchero)');
convert_back('Zero Milestone (Horace W. Peaslee)');
convert_back('Zone de Sensibilité Picturale Immatérielle (Yves Klein)');
convert_back('ZONING (ZONAGE) (Hippolyte Hentgen)');
convert_back('Zoo de sculptures (Laurent Le Deunff)');
convert_back('Zupversions - zupstitut - zupçons (Vincent Labaume)');
convert_back('Zwijnaarde as Center of the World (Christophe Fink)');
convert_back('\ ʁu.ba.to \ (Amandine Arcelli)');
convert_back('³ (Lydie Arickx)');
convert_back('À Distances (Samuel Bianchini)');
convert_back('À l\'abolition de l\'esclavage (Jean-Claude Mayo)');
convert_back('À l\'école d\'architecture (Carmen Perrin)');
convert_back('À la gloire de Bordeaux (André Lhote)');
convert_back('À mon seul désir (Cécile Pitois)');
convert_back('À plat (Caroline Molusson)');
convert_back('À suivre (Thierry Lahontâa)');
convert_back('À travers un verger (Christophe Gonnet)');
convert_back('Échelles ADN Code 1, 2, 3,4, 5, 6 (Daniel Dezeuze)');
convert_back('Écho (Jean-Yves Brélivet)');
convert_back('Écho (Philippe Cognée)');
convert_back('Écho au chaos (Pierre-Alexandre Rémy)');
convert_back('Éclatement II (Charles Daudelin)');
convert_back('Éclosion (Christine Maigne)');
convert_back('École Le Blé en herbe (Matali Crasset)');
convert_back('Écoute (Henri de Miller)');
convert_back('Écrire les frontières (Andreas Brandolini)');
convert_back('Écrit dans le cœur des objets (Lawrence Weiner)');
convert_back('Écritures d\'arbres (Alexandre Hollan)');
convert_back('Égarement (François Morellet)');
convert_back('Éloge de la nature (Sigurður Árni Sigurðsson)');
convert_back('Émulsion (Matali Crasset)');
convert_back('Épidémental (Philippe Richard)');
convert_back('Équations figures (Bernar Venet)');
convert_back('Étude pour l\'hôpital de l\'Archet (Lily van der Stokker)');
convert_back('Étude pour la place de la Mairie (Benjamin Avignon, Saweta Clouet)');
convert_back('Évasion (Charlie Skubich)');
convert_back('Être utile (quotidiennement) (Alain Bublex)');
convert_back('Øye i stein (Anish Kapoor)');
convert_back('Øymuseet (Raffael Rheinsberg)');
convert_back('Œuvre inconnue (artiste inconnu)');
print '</ul>';
