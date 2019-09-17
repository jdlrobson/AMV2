<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artistGetData.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'updateDB.php');

class Artist {

  public static function render_title($labels, $property, $param, $param_name, $th_title) {
    $title = '';
    if (isset($param[$param_name]))
      $title = $param[$param_name];
    else
    if (isset($labels->{$property}->value))
      $title = $labels->{$property}->value;

    print '<tr><th>' . $th_title . '</th><td>' . $title . '</td></tr>';
  }

  public static function render_image($claims, $property, $param, $param_name) {

    $image_thumb = MISSING_IMAGE_FILE;
    $image_url = MISSING_IMAGE_LINK;
    $image_legend = '';

    if (isset($param[$param_name])) {
      if (preg_match('/^Commons:/i', $param[$param_name])) {
        $image_name = substr($param[$param_name], 8);
        $image_url = COMMONS_PATH . COMMONS_FILE_PREFIX . $image_name;
        $tmp = self::get_image($image_name, 420);
        foreach($tmp->query->pages as $image)
          $image_thumb = $image->imageinfo[0]->thumburl;
      } else {
        $image_url = ATLASMUSEUM_PATH . ATLASMUSEUM_FILE_PREFIX . $param[$param_name];
        $tmp = self::get_image_am($param[$param_name], 420);
        foreach($tmp->query->pages as $image)
          $image_thumb = $image->imageinfo[0]->thumburl;
        $tmp = self::parse_page_am(ATLASMUSEUM_FILE_PREFIX . $param[$param_name]);
        $image_legend = $tmp->parse->text->{'*'};
      }
    } else
    if (isset($claims->{$property})) {
      $image_url = COMMONS_PATH . COMMONS_FILE_PREFIX . $claims->{$property}[0]->mainsnak->datavalue->value;
      $tmp = self::get_image($claims->{$property}[0]->mainsnak->datavalue->value, 420);
      foreach($tmp->query->pages as $image)
        $image_thumb = $image->imageinfo[0]->thumburl;
    }

    ?>
          <a href="<?php print $image_url; ?>" class="image">
            <img alt="" src="<?php print $image_thumb; ?>" style="width:auto;max-width:420px;max-height:300px" class="thumbimage" srcset="" />
          </a>
          <?php
            /*if ($image_legend != '')
              print '<div class="thumbcaption">' . $image_legend . '</div>';*/
          ?>
    <?php
  }

  public static function render_claim_am($param, $name, $title, $two_lines = false) {
    if (isset($param[$name])) {
      if ($two_lines) {
        print '<tr><td colspan="2"><b>' . $title . '</b><br />' . $param[$name] . '</td></tr>';
      } else {
        print '<tr><th>' . $title . '</th><td>' . $param[$name] . '</td></tr>';
      }
    }
  }

  public static function render_claim($claims, $property, $labels, $title, $title_plural='') {

    if (isset($claims->{$property})) {
      $data = [];

      foreach ($claims->{$property} as $value) {
        switch ($value->mainsnak->datatype) {
          case 'time':
            $date = $value->mainsnak->datavalue->value->time;
            $date = preg_replace('/-.*$/', '', $date);
            $date = preg_replace('/\+/', '', $date);
            array_push($data, $date);
            break;
          case 'wikibase-item':
            $id = $value->mainsnak->datavalue->value->id;
            if (isset($labels[$id]))
              array_push($data, $labels[$id]);
            else
              array_push($data, $id);
            break;
        }
      }

      if (sizeof($data)>0)
        print '<tr><th>' . 
          ($title_plural != '' ? (sizeof($data)<=1 ? $title : $title_plural) : $title) . 
          '</th><td>' .
          join($data, ', ') .
          '</td></tr>';
    }
  }

  public static function render_claim2($claims, $property, $labels, $param, $name, $title, $title_plural, $two_lines = false) {

    $data = [];
    if (isset($claims->{$property})) {
      foreach ($claims->{$property} as $value) {
        switch ($value->mainsnak->datatype) {
          case 'time':
            $date = $value->mainsnak->datavalue->value->time;
            $date = preg_replace('/-.*$/', '', $date);
            $date = preg_replace('/\+/', '', $date);
            array_push($data, $date);
            break;
          case 'wikibase-item':
            $id = $value->mainsnak->datavalue->value->id;
            if (isset($labels[$id]))
              array_push($data, $labels[$id]);
            else
              array_push($data, $id);
            break;
        }
      }
    }

    if (isset($param[$name])) {
      foreach(explode(';', $param[$name]) as $p)
        if (!in_array($p, $data)) {
          if (isset($labels[$p]))
            array_push($data, $labels[$p]);
          else
            array_push($data, $p);
        }
    }

    sort($data);
    $data = array_unique($data);

    if (sizeof($data)>0) {
      if ($two_lines) {
        print '<tr><td colspan="2"><b>' . (sizeof($data)>1 ? $title_plural : $title) . '</b><br />' . join($data, ', ') . '</td></tr>';
      } else {
        print '<tr><th>' . (sizeof($data)>1 ? $title_plural : $title) . '</th><td>' . join($data, ', ') . '</td></tr>';
      }
    }
  }


  public static function render_works($name, $id) {
    if (is_null($name) && !is_null($id) && $id != '') {
      $query = "SELECT DISTINCT article FROM tmp_artist WHERE wikidata LIKE '$id' LIMIT 1";
      $result = query($query);
      $row = $result->fetch_assoc();
      if (isset($row["article"])) {
        $name = $row["article"];
        $query = "SELECT DISTINCT article, title, wikidata FROM tmp_library_2 WHERE artist LIKE '%$name%' OR artist LIKE '%$id%'";
        $result = query($query);
      } else {
        $query = "SELECT DISTINCT article, title, wikidata FROM tmp_library_2 WHERE artist LIKE '%$id%'";
        $result = query($query);
      }
    }
    else
    if (!is_null($name)) {
      $query = "SELECT DISTINCT article, title, wikidata FROM tmp_library_2 WHERE artist LIKE '%$name%'";
      $result = query($query);
    }
    else
    if (!is_null($id) && $id != '') {
      $query = "SELECT DISTINCT article, title, wikidata FROM tmp_library_2 WHERE artist LIKE '%$id%'";
      $result = query($query);
    }
    else
    if (array_key_exists('QUERY_STRING', $_SERVER)) {
      $query_string = urldecode(str_replace('_', ' ', str_replace('title=', '', $_SERVER['QUERY_STRING'])));
      $query = "SELECT DISTINCT article, title, wikidata FROM tmp_library_2 WHERE artist LIKE '%$query_string%'";
      $result = query($query);
    }

    $artworkList = [];
    if (!is_null($result)) {
      while ($row = $result->fetch_row()) {
        array_push($artworkList, [
          'article' => $row['0'],
          'label' => $row['1'],
          'wikidata'=> $row['2'],
          'origin' => 'am',
        ]);
      }
    }

    $query =
      "SELECT DISTINCT ?item ?itemLabel ?placeLabel ?countryLabel ?image WHERE {" .
      "  ?item wdt:P170 wd:" . $id . " ;" .
      "        wdt:P136 wd:Q557141 ." .
      "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr,en\" . }" .
      "} ORDER BY ?itemLabel";

    $data = Api::Sparql($query);

    foreach($data->results->bindings as $artwork) {
      array_push($artworkList, [
        'article'=> '',
        'label'=> $artwork->itemLabel->value,
        'wikidata'=> str_replace('http://www.wikidata.org/entity/', '', $artwork->item->value),
        'origin' => 'wikidata'
      ]);
    }

    // Supprime les doublons
    $artworkListTmp = [];

    for ($i=0; $i<sizeof($artworkList); $i++) {
      if ($artworkList[$i]['origin'] == 'am') {
        array_push($artworkListTmp, $artworkList[$i]);
      }
    }
    for ($i=0; $i<sizeof($artworkList); $i++) {
      if ($artworkList[$i]['origin'] == 'wikidata') {
        $found = false;
        for ($j=0; $j<sizeof($artworkList); $j++) {
          if ($artworkList[$j]['origin'] == 'am' && $artworkList[$j]['wikidata'] == $artworkList[$i]['wikidata']) {
            $found = true;
          }
        }
        if (!$found)
          array_push($artworkListTmp, $artworkList[$i]);
      }
    }

    $artworkList = $artworkListTmp;

    // Trie le tableau
    usort($artworkList, function ($item1, $item2) {
      return strcmp($item1["label"], $item2["label"]);
    });

    if (sizeof($artworkList) == 0)
      return '';

    $list = [];

    foreach($artworkList as $a) {
      if ($a['article'] == '') {
        $article = $a['wikidata'];
      } else {
        $article = $a['article'];
      }

      $link = '';
      if ($a['origin'] == 'wikidata')
        $link = 'Spécial:Wikidata/' . $a['wikidata'];
      else
        $link = $a['article'];
      $label = $a['label'];
      
      array_push($list, [
        'link' => $link,
        'label' => $label
      ]);
    }

    $text = [];
    foreach($list as $l) {
      array_push($text, '<a href="' . $l['link'] . '">' . $l['label'] . '</a>');
    }
    print '<tr><th>Œuvres</th><td>' . join($text, ', ') . '</td></tr>';
  }

  public static function parse_page_am($page) {
    return Api::call_api(array(
      'action' => 'parse',
      'page' => $page
    ), 'atlasmuseum');
  }

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

  public static function get_props($id) {
    return Api::call_api(array(
      'action' => 'wbgetentities',
      'ids' => $id
    ));
  }

  public static function get_labels($ids) {

    $labels = [];
    $split_ids = array_chunk($ids, 50);

    for ($i=0; $i<sizeof($split_ids); $i++) {
      $labels_data = Api::call_api(array(
        'action' => 'wbgetentities',
        'props' => 'labels',
        'ids' => join($split_ids[$i], '|')
      ));

      foreach($labels_data->entities as $id=>$value) {
        if (isset($value->labels->fr)) {
          $labels[$id] = $value->labels->fr->value;
        }
      }
    }

    return $labels;
  }

  public static function get_ids($data) {
    $ids = [];

    foreach ($data->entities as $q)
      foreach ($q->claims as $property=>$value)
        foreach ($value as $claim)
          if ($claim->mainsnak->datatype == 'wikibase-item')
            array_push($ids, $claim->mainsnak->datavalue->value->id);

    return $ids;
  }

  public static function renderWorks($id) {
    $query =
      "SELECT DISTINCT ?item ?itemLabel ?placeLabel ?countryLabel ?image WHERE {" .
      "  ?item wdt:P170 wd:" . $id . " ;" .
      "        wdt:P136 wd:Q557141 ." .
      "  OPTIONAL { ?item wdt:P18 ?image }" .
      "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr,en\" . }" .
      "} ORDER BY ?itemLabel";

    $data = Api::Sparql($query);
    $images = '';
    $n = 0;

    foreach($data->results->bindings as $artwork) {
      $artwork_id = str_replace(WIKIDATA_ENTITY, '', $artwork->item->value);
      if ($artwork_id != $id) {
        $title = $artwork->itemLabel->value;
        if (isset($artwork->image)) {
          $image_url = $artwork->image->value;
          $image_thumb = $image_url;
        } else {
          $image_thumb = MISSING_IMAGE_FILE;
          $image_url = MISSING_IMAGE_LINK;
        }
        $images .= '<li><div class="thumb tright"><div class="thumbinner"><a href="' . ATLASMUSEUM_PATH . 'Spécial:Wikidata/' . $artwork_id . '" class="image"><img alt="" src="' . $image_thumb . '" style="width:auto;max-width:192px;max-height:140px" srcset=""><br />' . $title . '</a></div></div></li>';
        $n++;
      }
    }

    if ($n == 0)
      return '';
    
    if ($n == 1)
      $title = 'Autre œuvre ';
    else
      $title = 'Autres œuvres ';

    if (sizeof($creators) > 1)
      $title .= 'des artistes dans l\'espace public';
    else
      $title .= 'de l\'artiste dans l\'espace public';

    print '<div class="atmslideshowCtnr">
        <div class="atmslideshowHead" onclick="toggleFold(this)"><h3>' . $title . '</h3></div>
        <ul class="atmslideshowContent" style="display:none;">' . $images . '</ul>
      </div>';
  }

  public static function renderArtist($param = array(), $wikidata=true) {
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );

    if (!is_null($param['q'])) {
      $q = $param['q'];

      $data = ArtistGetData::get_props($q);
      $ids = ArtistGetData::get_ids($data);
      $claims = $data->entities->{$q}->claims;
    } else {
      $q = '';
      $data = [];
      $ids = [];
      $labels = [];
      $claims = null;
    }

    $ids_am = ArtistGetData::get_ids2($param);
    $ids = array_merge($ids, $ids_am);
    $labels = ArtistGetData::get_labels($ids);

    ob_start();

    if ($wikidata) {
      if (array_key_exists($q, $labels)) {
        $artistTitle = $labels[$q];
        ?>
          <script>document.getElementById('firstHeading').getElementsByTagName('span')[0].textContent = "<?php print $artistTitle; ?>"</script>
        <?php
      }
      ?>
      <div class="import">
        <a href="<?php print ATLASMUSEUM_PATH; ?>Spécial:WikidataEditArtist/<?php print $q; ?>">
          <img src="http://publicartmuseum.net/tmp/w/skins/AtlasMuseum/resources/images/hmodify.png" />
          Importer cette notice dans atlasmuseum
        </a>
      </div>
      <?php
    } else {
      ?>
      <div class="import">
        <a href="<?php print ATLASMUSEUM_PATH; ?>Spécial:WikidataArtistExport/<?php print $_GET['title']; ?>">
          <img src="http://publicartmuseum.net/tmp/w/skins/AtlasMuseum/resources/images/hmodify.png" />Exporter cette notice sur Wikidata
        </a>
      </div>
      <?php
    }
    ?>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>artist.js"></script>
      <div class="pageArtiste">
        <?php
          self::render_image($data->entities->{$q}->claims, 'P18', $param, 'thumbnail');
        ?>
        <table class="wikitable">
          <?php
            self::render_claim2($data->entities->{$q}->claims, 'P734', $labels, $param, 'nom', 'Nom', 'Noms');
            self::render_claim2($data->entities->{$q}->claims, 'P735', $labels, $param, 'prenom', 'Prénom', 'Prénoms');
            self::render_claim_am($param, 'abstract', 'Résumé', false);
            self::render_claim2($data->entities->{$q}->claims, 'P569', $labels, $param, 'birthPlace', 'Date de naissance', 'Date de naissance');
            self::render_claim2($data->entities->{$q}->claims, 'P19', $labels, $param, 'birthPlace', 'Lieu de naissance', 'Lieu de naissance');
            self::render_claim2($data->entities->{$q}->claims, 'P20', $labels, $param, 'deathPlace', 'Lieu de décès', 'Lieu de décès');
            self::render_claim2($data->entities->{$q}->claims, 'P570', $labels, $param, 'deathPlace', 'Date de décès', 'Date de décès');
            self::render_claim2($data->entities->{$q}->claims, 'P27', $labels, $param, 'nationality', 'Nationalité', 'Nationalités');
            self::render_claim2($data->entities->{$q}->claims, 'P135', $labels, $param, 'movement', 'Mouvement', 'Mouvements');
            self::render_claim_am($param, 'societe_gestion_droit_auteur', 'Société de gestion  des droits d\'auteur', false);
            self::render_claim_am($param, 'nom_societe_gestion_droit_auteur', 'Nom de la société de gestion  des droits d\'auteur', false);
            self::render_works(array_key_exists('full_name', $param) ? $param['full_name'] : null, $q);
          ?>
        </table>
        <?php
        if ($q != '') {
          ?>
            <div class="wikidataLink">
              <a href="<?php print WIKIDATA_BASE; ?>wiki/<?php print $q; ?>" target="_blank">
                <img src="skins/AtlasMuseum/resources/hwikidata.png" />
                <span>Voir cette notice sur Wikidata</span>
              </a>
            </div>
          <?php
        }
        ?>
      </div>
    <?php
    ?>
    <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>artwork.css">
    <?php

    $contents = ob_get_contents();
    ob_end_clean();

    return preg_replace("/\r|\n/", "", $contents);
  }

}
