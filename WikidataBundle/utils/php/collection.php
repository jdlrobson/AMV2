<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'updateDB.php');

class Collection {

  public static function renderCollection($param = array()) {    
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );

    $artworks = [];
    $ids_found = [];
    $ids_artists = [];
    $artists_am = [];

    // 1. Récupération de ce qui existe sur atlasmuseum

    $articles = explode(';', $param['notices']);
    $articlesList = join('","', $articles);
    $query = 'SELECT DISTINCT article,title,artist,latitude,longitude,`date`,wikidata FROM tmp_library_2 WHERE article IN ("' . $articlesList . '") OR wikidata IN ("' . $articlesList . '") ORDER BY article,artist';
    $result = query($query);

    while ($row = $result->fetch_row()) {
      $artwork = [
        'article' => $row[0],
        'title' => $row[1],
        'artists' => [],
        'latitude' => (double)$row[3],
        'longitude' => (double)$row[4],
        'date' => $row[5],
        'wikidata' => $row[6],
        'origin' => 'atlasmuseum'
      ];

      $artists = explode(';', $row[2]);
      foreach ($artists as $artist) {
        if (preg_match('/^[qQ][0-9]+$/', $artist)) {
          array_push($ids_artists, $artist);
          array_push($artwork['artists'], [
            'title' => $artist,
            'wikidata' => $artist,
            'origin' => 'wikidata'
          ]);
        } else {
          array_push($artwork['artists'], [
            'title' => $artist,
            'wikidata' => '',
            'origin' => 'atlasmuseum'
          ]);
        }
      }

      if ($row[6] != '')
        array_push($ids_found, $row[6]);

      array_push($artworks, $artwork);
    }

    // 2. Récupération de ce qui existe sur Wikidata
    $ids_wikidata = [];
    foreach ($articles as $article) {
      if (preg_match('/^[qQ][0-9]+$/', $article) && !in_array($article, $ids_found))
        array_push($ids_wikidata, $article);
    }

    if (sizeof($ids_wikidata) > 0) {
      $query = "SELECT DISTINCT ?q ?qLabel ?coords ?image ?creator ?creatorLabel (YEAR(?date) AS ?year) WHERE " .
        "{" .
        "  ?q wdt:P625 ?coords ." .
        "  OPTIONAL { ?q wdt:P18 ?image }" .
        "  OPTIONAL { ?q wdt:P170 ?creator }" .
        "  OPTIONAL { ?q wdt:P571 ?date }" .
        "  VALUES ?q { wd:" . implode(" wd:", $ids_wikidata) . " }" .
        "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr,en\". }" .
        "} ORDER BY ?q ?creator";
      
      $result = Api::Sparql($query);
      $data = [];

      foreach ($result->results->bindings as $artwork) {
        // var_dump($artwork);
        $q = str_replace(WIKIDATA_ENTITY, '', $artwork->q->value);

        $data = [
          'article' => $q,
          'title' => $q,
          'artists' => [],
          'latitude' => null,
          'longitude' => null,
          'date' => '',
          'wikidata' => $q,
          'origin' => 'wikidata'
        ];
        if (array_key_exists('qLabel', $artwork)) {
          $data['title'] = $artwork->qLabel->value;
        }
        if (array_key_exists('year', $artwork)) {
          $data['date'] = $artwork->year->value;
        }
        $creator = [
          'title' => '',
          'wikidata' => '',
          'origin' => 'wikidata'
        ];
        if (array_key_exists('creator', $artwork)) {
          $creator['title'] = $artwork->creator->value;
          $creator['wikidata'] = $artwork->creator->value;
        }
        if (array_key_exists('creatorLabel', $artwork)) {
          $creator['title'] = $artwork->creatorLabel->value;
        }
        if ($creator['title'] != '') {
          array_push($data['artists'], $creator);
        }

        array_push($artworks, $data);
      }
    }

    // 3. Récupération des artistes Wikidata existants sur atlasmuseum
    $query = 'SELECT DISTINCT article,wikidata FROM tmp_artist WHERE wikidata IN ("' . implode('", "', $ids_artists) . '")';
    $result = query($query);

    while ($row = $result->fetch_row()) {
      $artists_am[$row[1]] = $row[0];
    }

    ob_start();

    if ($param['description']) {
      ?>
        <div class="description">
          <?php print $param['description']; ?>
        </div>
      <?php
    }

    if ($param['institution']) {
      ?>
        <div class="institution">
          <?php print $param['institution']; ?>
        </div>
      <?php
    }

    ?>
    <div class="mapCtnr dalm">
    </div>

    <div class="homeCtnr dalm">
      <h3><span class="mw-headline" id="Les_.C5.93uvres_de_la_collection">Les œuvres de la collection</span></h3>
      <table class="sortable wikitable smwtable jquery-tablesorter" width="100%">
        <thead>
          <tr>
            <th class="Titre-de-l'œuvre headerSort" tabindex="0" role="columnheader button" title="Tri croissant"><a href="/tmp/w/index.php?title=Attribut:Titre" title="Attribut:Titre">Titre de l'œuvre</a></th>
            <th class="Artiste headerSort" tabindex="0" role="columnheader button" title="Tri croissant"><a href="/tmp/w/index.php?title=Attribut:Auteur" title="Attribut:Auteur">Artiste</a></th>
            <th class="Date headerSort" tabindex="0" role="columnheader button" title="Tri croissant"><a href="/tmp/w/index.php?title=Attribut:Date_d%27inauguration" title="Attribut:Date d'inauguration">Date</a></th>
          </tr>
        </thead>
        <tbody>
          <?php
            $odd = true;
            foreach ($artworks as $artwork) {
              $article = $artwork['article'];
              $title = $artwork['title'];
              $date = $artwork['date'];
              $artists = [];
              foreach ($artwork['artists'] as $artist) {
                if ($artist['origin'] == 'wikidata') {
                  if (array_key_exists($artist['title'], $artists_am)) {
                    $new_title = $artists_am[$artist['title']];
                    array_push($artists, '<a href="' . ATLASMUSEUM_PATH . $new_title . '" title="' . $new_title . '">' . $new_title . '</a>');
                  }
                  else
                    array_push($artists, '<a href="' . ATLASMUSEUM_PATH . 'Spécial:WikidataArtist/' . $artist['title'] . '" title="' . $artist['title'] . '">' . $artist['title'] . '</a>');
                } else {
                  array_push($artists, '<a href="' . ATLASMUSEUM_PATH . $artist['title'] . '" title="' . $artist['title'] . '">' . $artist['title'] . '</a>');
                }
              }
              ?>
                <tr class="row-<?php print $odd ? 'odd' : 'even'; ?>">
                  <td class="Titre-de-l'œuvre smwtype_txt">
                    <a href="<?php print ATLASMUSEUM_PATH . $article; ?>" title="<?php print $article; ?>">
                      <?php print $title; ?>
                    </a>
                  </td>
                  <td class="Artiste smwtype_wpg">
                    <a href="/tmp/w/index.php?title=Philippe_Cazal" title="Philippe Cazal">
                      <?php print implode(', ', $artists); ?>
                    </a>
                  </td>
                  <td data-sort-value="2453371.5" class="Date smwtype_dat">
                    <?php print $date; ?>
                  </td>
                </tr>
              <?php
              $odd = !$odd;
            }
          ?>
        </tbody>
      </table>
    </div>
    <?php
/*
    if ($wikidata) {
      if (array_key_exists($q, $labels)) {
        $artworkTitle = $labels[$q];
        ?>
          <script>document.getElementById('firstHeading').getElementsByTagName('span')[0].textContent = "<?php print $artworkTitle; ?>"</script>
        <?php
      }
      ?>
      <div class="import">
        <a href="<?php print ATLASMUSEUM_PATH; ?>Spécial:WikidataEdit/<?php print $q; ?>">
          <img src="skins/AtlasMuseum/resources/images/hmodify.png" />
          Importer cette œuvre dans atlasmuseum
        </a>
      </div>
      <?php
    } else {
      ?>
      <div class="import">
        <a href="<?php print ATLASMUSEUM_PATH; ?>Spécial:WikidataExport/<?php print $_GET['title']; ?>">
          <img src="skins/AtlasMuseum/resources/images/hmodify.png" />Exporter cette œuvre sur Wikidata
        </a>
      </div>
      <?php
    }
    ?>
    <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>artwork.js"></script>
    <div class="dalm">
      <div class="topCtnr">
        <?php
          self::render_image($data->entities->{$q}->claims, 'P18', $param, 'image_principale');
          self::render_map($lat, $lng);
        ?>
      </div>
      <?php
        if (isset($param['notice_augmentee'])) {
          ?>
            <div class="noticePlus noticePlusExpanded">
              <h2 onclick="toggleNoticePlus(this)"> <span class="mw-headline" id="Notice.2B"> Notice+ </span></h2>
              <div>
                <?php print str_replace("&quot;", "\"", str_replace("\\n", "<br />", $param['notice_augmentee'])); ?>
              </div>
            </div>
          <?php
        }
      ?>
      <div class="ibCtnr">
        <div class="ibOeuvre">
          <h2 <?php if (isset($param['notice_augmentee'])) print 'onclick="toggleNoticePlusHeader(this)"'; ?>> <span class="mw-headline" id=".C5.92uvre"> Œuvre </span></h2>
          <table class="wikitable" style="table">
            <?php
              self::render_title($data->entities->{$q}->labels, ['fr', 'en'], $param, 'titre', 'Titre', false);
              self::render_claim_am($param, 'sous_titre', 'Sous-titre', true);
              self::render_claim_am($param, 'description', 'Description', true);
              self::render_claim2($data->entities->{$q}->claims, 'P571', $labels, $param, 'inauguration', 'Date', 'Date');
              self::render_claim_am($param, 'restauration', 'Date de restauration', false);
              self::render_claim_am($param, 'fin', 'Date de fin', false);
              self::render_claim_am($param, 'precision_date', 'Précision sur les dates', false);
              self::render_claim_am($param, 'nature', 'Nature', false);
              self::render_claim_am($param, 'programme', 'Procédure', false);
              self::render_claim_am($param, 'numero_inventaire', 'Numéro d\'inventaire', false);
              self::render_claim_am($param, 'contexte_production', 'Contexte de production', true);
              self::render_claim_am($param, 'conservation', 'État de conservation', false);
              self::render_claim_am($param, 'precision_etat_conservation', 'Précision sur l\'état de conservation', false);
              self::render_claim_am($param, 'autre_precision_etat_conservation', 'Autres précisions sur l\'état de conservation', false);
              self::render_claim_am($param, 'periode_art', 'Période', false);
              self::render_claim2($data->entities->{$q}->claims, 'P135', $labels, $param, 'mouvement_artistes', 'Mouvement', 'Mouvements');
              self::render_claim_am($param, 'precision_mouvement_artistes', 'Précision sur le mouvement', false);
              self::render_claim2($data->entities->{$q}->claims, 'P31', $labels, $param, 'type_art', 'Domaine', 'Domaines');
              self::render_claim_am($param, 'precision_type_art', 'Précision sur le domaine', false);
              self::render_claim2($data->entities->{$q}->claims, 'P462', $labels, $param, 'couleur', 'Couleur', 'Couleurs');
              self::render_claim_am($param, 'precision_couleur', 'Précision sur les couleurs', false);
              self::render_claim2($data->entities->{$q}->claims, 'P186', $labels, $param, 'materiaux', 'Matériau', 'Matériaux');
              self::render_claim_am($param, 'precision_materiaux', 'Précision sur les matériaux', false);
              self::render_claim_am($param, 'techniques', 'Techniques', false);
              self::render_claim_am($param, 'hauteur', 'Hauteur (m)', false);
              self::render_claim_am($param, 'longueur', 'Profondeur (m)', false);
              self::render_claim_am($param, 'largeur', 'Largeur (m)', false);
              self::render_claim_am($param, 'diametre', 'Largeur (m)', false);
              self::render_claim_am($param, 'surface', 'Surface (m²)', false);
              self::render_claim_am($param, 'precision_dimensions', 'Précision sur les dimensions', false);
              self::render_claim_am($param, 'symbole', 'Références', false);
              self::render_claim2($data->entities->{$q}->claims, 'P921', $labels, $param, 'forme', 'Sujet représenté', 'Sujets représentés');
              self::render_claim_am($param, 'mot_cle', 'Mots clés', false);
              self::render_claim_am($param, 'influences', 'Influences', false);
              self::render_claim_am($param, 'a_influence', 'A influencé', false);
              self::render_claim2($data->entities->{$q}->claims, 'P88', $labels, $param, 'commanditaires', 'Commanditaire', 'Commanditaires');
              self::render_claim2($data->entities->{$q}->claims, 'P1640', $labels, $param, 'commissaires', 'Commissaire', 'Commissaires');
              self::render_claim_am($param, 'partenaires_publics', 'Partenaires publics', false);
              self::render_claim_am($param, 'partenaires_prives', 'Partenaires privés', false);
              self::render_claim_am($param, 'collaborateurs', 'Collaborateurs', false);
              self::render_claim_am($param, 'maitrise_oeuvre', 'Maîtrise d\'œuvre', false);
              self::render_claim_am($param, 'maitrise_oeuvre_deleguee', 'Maîtrise d\'œuvre déléguée', false);
              self::render_claim_am($param, 'maitrise_ouvrage', 'Maîtrise d\'ouvrage', false);
              self::render_claim_am($param, 'maitrise_ouvrage_deleguee', 'Maîtrise d\'ouvrage déléguée', false);
              self::render_claim_am($param, 'proprietaire', 'Propriétaire', false);
            ?>
          </table>
        </div>
        <div class="ibSite">
          <h2 <?php if (isset($param['notice_augmentee'])) print 'onclick="toggleNoticePlusHeader(this)"'; ?>> <span class="mw-headline" id="Site"> Site </span></h2>
          <table class="wikitable" style="table">
            <?php
              self::render_claim2($data->entities->{$q}->claims, 'P276', $labels, $param, 'site_nom', 'Lieu', 'Lieux');
              self::render_claim_am($param, 'site_lieu_dit', 'Lieu-dit', false);
              self::render_claim_am($param, 'site_adresse', 'Adresse', false);
              self::render_claim_am($param, 'site_code_postal', 'Code postal', false);
              self::render_claim2($data->entities->{$q}->claims, 'P131', $labels, $param, 'site_ville', 'Ville', 'Villes');
              self::render_claim_am($param, 'site_departement', 'Département', false);
              self::render_claim_am($param, 'site_region', 'Région', false);
              self::render_claim2($data->entities->{$q}->claims, 'P17', $labels, $param, 'site_pays', 'Pays', 'Pays');
              self::render_claim_am($param, 'site_details', 'Détails sur le site', true);
              self::render_claim_am($param, 'site_acces', 'Accès', false);
              self::render_claim_am($param, 'site_visibilite', 'Visibilité', false);
              self::render_claim2($data->entities->{$q}->claims, 'P2846', $labels, $param, 'site_pmr', 'PMR', 'PMR');
              self::render_claim_am($param, 'site_urls', 'URLs', false);
              self::render_claim_am($param, 'site_pois', 'Points d\'intérêt', false);
              self::render_claim_coords($lat, $lng, 'Latitude/Longitude');
            ?>
          </table>
        </div>
        <div class="ibArtiste">
          <h2 <?php if (isset($param['notice_augmentee'])) print 'onclick="toggleNoticePlusHeader(this)"'; ?>> <span class="mw-headline" id="Artiste"> Artiste<?php (sizeof($data->entities->{$q}->claims->P170)>1 ? 's' : '') ?> </span></h2>
          <?php
            self::render_artists($data->entities->{$q}->claims->P170, $param['artiste']);
          ?>
        </div>
        <div class="clearfix"></div>
      </div>
      <?php
        if (isset($param['source'])) {
        ?>
          <div class="mapCtnr">
            <b>Sources :</b><br />
            <?php print $param['source']; ?>
          </div>
        <?php
        }
        if ($q != '') {
          ?>
            <div class="wikidataLink">
              <a href="https://www.wikidata.org/wiki/<?php print $q; ?>" target="_blank">
                <img src="skins/AtlasMuseum/resources/hwikidata.png" />
                <span>Voir cette œuvre sur Wikidata</span>
              </a>
            </div>
          <?php
        }
      ?>
      <div class="atlasCtnr">
        <h2> <span class="mw-headline" id="ATLAS"> ATLAS </span></h2>
        <?php
          self::render_galerie($param, 'image_galerie_construction', 'Construction / installation / Montage');
          self::render_galerie($param, 'image_galerie_autre', 'Autres prises de vues');
          self::render_other_works($q, $data->entities->{$q}->claims->P170, $param);
          self::render_near_sites($q, $lat, $lng);
          self::render_near_artworks($q, $lat, $lng);
        ?>
      </div>
    </div>
    <script src="<?php print OPEN_LAYER_JS; ?>"></script>
    <link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
    <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>artwork.css">
    <?php
    */

    $contents = ob_get_contents();
    ob_end_clean();

    return preg_replace("/\r|\n/", "", $contents);

  }

}
