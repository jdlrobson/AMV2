<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'updateDB.php');

class Collection {
  public static function get_image($image, $width=320) {
    return Api::call_api(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => COMMONS_FILE_PREFIX . $image
    ), 'Commons');
  }

  public static function get_image_am($image, $width=320) {
    return Api::call_api(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => ATLASMUSEUM_FILE_PREFIX . $image
    ), 'atlasmuseum');
  }

  public static function renderCollection($param = array()) {    
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );

    $artworks = [];
    $ids_found = [];
    $ids_artists = [];
    $artists_am = [];

    // 1. Récupération de ce qui existe sur atlasmuseum

    $articles = preg_split('/[\s]*;[\s]*/', $param['notices']);
    $articlesList = join('","', $articles);
    $query = 'SELECT DISTINCT article,title,artist,latitude,longitude,`date`,wikidata,nature FROM tmp_library_2 WHERE article IN ("' . $articlesList . '") OR wikidata IN ("' . $articlesList . '") ORDER BY article,artist';
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
        'origin' => 'atlasmuseum',
        'nature' => $row[7]
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
        $q = str_replace(WIKIDATA_ENTITY, '', $artwork->q->value);

        $data = [
          'article' => $q,
          'title' => $q,
          'artists' => [],
          'latitude' => null,
          'longitude' => null,
          'date' => '',
          'wikidata' => $q,
          'origin' => 'wikidata',
          'nature' => 'wikidata'
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

    // 4. Modifications récentes
    $articleName = [];
    foreach ($artworks as $artwork) {
      array_push($articleName, '[[' . $artwork['article'] . '|+depth=0]]');
    }
    //$query = '[[Category:Notices d\'œuvre]]|?Image principale|sort=Modification date|limit=4|order=desc';
    //$query = join($articleName, ' OR ') . '|?Image principale|sort=Modification date|limit=4|order=desc';
    $currentArticle = preg_replace('/^.*\/wiki\//', '', $_SERVER['REQUEST_URI']);
    $currentArticle = urldecode(str_replace('_', ' ', $currentArticle));
    $query = '[[-Contient la notice::' . $currentArticle . ']]|?Image principale|sort=Modification date|limit=4|order=desc';
    $data = Api::post_api(array(
      'action' => 'ask',
      'query' => $query
    ), 'atlasmuseum');

    $recentChanges = [];

    foreach ($data['query']['results'] as $result) {
      $artwork = [];
      $artwork['title'] = $result['fulltext'];
      $artwork['url'] = $result['fullurl'];
      $image = MISSING_IMAGE_FILE;
      $imageUrl = MISSING_IMAGE_THUMB;
      if (isset($result['printouts'][0][0])) {
        $image = $result['printouts'][0][0];
        if (preg_match('/^Commons:/i', $image)) {
          $imageName = substr($image, 8);
          $tmp = self::get_image($imageName, 420);
          foreach($tmp->query->pages as $img)
            $imageUrl = $img->imageinfo[0]->thumburl;
        } else {
          $tmp = self::get_image_am($image, 420);
          foreach($tmp->query->pages as $img)
            $imageUrl = $img->imageinfo[0]->thumburl;
        }
      }
      $artwork['image'] = $imageUrl;

      array_push($recentChanges, $artwork);
    }

    ob_start();

    ?>
    <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>collection.js"></script>
    <?php

    if ($param['description']) {
      ?>
        <div class="description">
          <?php print API::convert_to_wiki_text($param['description']); ?>
        </div>
      <?php
    }

    if ($param['institution']) {
      ?>
        <div class="institution">
          <?php print API::convert_to_wiki_text($param['institution']); ?>
        </div>
      <?php
    }

    ?>
    <div class="mapCtnr dalm">
      <div id="map" style="height:400px">
        <div id="mapData" data-artworks="<?php print htmlspecialchars(json_encode($artworks), ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div id="map-popup" class="ol-popup" class="popupOeuvre">
        <a href="#" id="map-popup-closer" class="ol-popup-closer"></a>
        <p id="map-popup-content"></p>
      </div>
    </div>

    <div class="homeCtnr dalm">
      <div class="atmslideshowCtnr">
        <div class="atmslideshowHead"><h3> <span class="mw-headline" id="Contributions_les_plus_r.C3.A9centes">Contributions les plus récentes</span></h3></div>
        <ul>
        <?php
        foreach($recentChanges as $artwork) {
          ?><li>
              <div class="thumb tright">
                <div class="thumbinner">
                  <a href="<?php print $artwork['url']; ?>" title="<?php print $artwork['title']; ?>">
                    <img alt="<?php print $artwork['title']; ?>" src="<?php print $artwork['image']; ?>" style="width:auto;max-width:205px;max-height:134px;">
                  </a>
                  <div class="thumbcaption"><?php print $artwork['title']; ?></div>
                </div>
              </div>
            </li><?php
        }
        ?>
        </ul>
      </div>
    </div>

    <div class="homeCtnr dalm">
      <h3><span class="mw-headline" id="Les_.C5.93uvres_de_la_collection">Les œuvres de la collection</span></h3>
      <table class="sortable wikitable smwtable jquery-tablesorter" width="100%">
        <thead>
          <tr>
            <th class="Titre-de-l'œuvre headerSort" tabindex="0" role="columnheader button" title="Tri croissant"><a href="Attribut:Titre" title="Attribut:Titre">Titre de l'œuvre</a></th>
            <th class="Artiste headerSort" tabindex="0" role="columnheader button" title="Tri croissant"><a href="Attribut:Auteur" title="Attribut:Auteur">Artiste</a></th>
            <th class="Date headerSort" tabindex="0" role="columnheader button" title="Tri croissant"><a href="Attribut:Date_d%27inauguration" title="Attribut:Date d'inauguration">Date</a></th>
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
                    <a href="Philippe_Cazal" title="Philippe Cazal">
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
    if ($param['texte']) {
      ?>
        <div class="texte">
          <?php print API::convert_to_wiki_text($param['texte']); ?>
        </div>
      <?php
    }
    ?>

    <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery.min.js"></script>
    <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
    <script src="<?php print OPEN_LAYER_JS; ?>"></script>
    <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>mapCollection.js"></script>
    <link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
    <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>map.css" type="text/css">
    <?php

    $contents = ob_get_contents();
    ob_end_clean();

    return preg_replace("/\r|\n/", "", $contents);

  }

}
