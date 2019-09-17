<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artworkGetData.php');

class Artwork {

  public static function render_title($labels, $property, $param, $param_name, $th_title) {
    $title = '';

    if (isset($param[$param_name]))
      $title = $param[$param_name];
    else {
      foreach ($property as $language) {
        if (isset($labels->{$language}) && isset($labels->{$language}->value)) {
          $title = $labels->{$language}->value;
          break;
        }
      }
    }

    print '<tr><th>' . $th_title . '</th><td>' . $title . '</td></tr>';
  }

  public static function render_image($claims, $property, $param, $param_name) {

    $image_thumb = MISSING_IMAGE_FILE;
    $image_url = MISSING_IMAGE;
    $image_legend = '';

    if (isset($param[$param_name])) {
      $image_url = ATLASMUSEUM_PATH . ATLASMUSEUM_FILE_PREFIX . $param[$param_name];
      $tmp = self::get_image_am($param[$param_name], 420);
      if (isset($tmp->query->pages))
        foreach($tmp->query->pages as $image)
          $image_thumb = $image->imageinfo[0]->thumburl;
      $tmp = self::parse_page_am('Fichier:'.$param[$param_name]);
      $image_legend = $tmp->parse->text->{'*'};
    } else
    if (isset($claims->{$property})) {
      $image_url = COMMONS_PATH . COMMONS_FILE_PREFIX . $claims->{$property}[0]->mainsnak->datavalue->value;
      $tmp = self::get_image($claims->{$property}[0]->mainsnak->datavalue->value, 420);
      foreach($tmp->query->pages as $image)
        $image_thumb = $image->imageinfo[0]->thumburl;
    }

    ?>
    <div class="topImgCtnr">
      <div class="thumb tright">
        <div class="thumbinner" style="width:422px;">
          <a href="<?php print $image_url; ?>" class="image">
            <img alt="" src="<?php print $image_thumb; ?>" style="width:auto;max-width:420px;max-height:209px" class="thumbimage" srcset="" />
          </a>
          <?php
            /*if ($image_legend != '')
              print '<div class="thumbcaption">' . $image_legend . '</div>';*/
          ?>
        </div>
      </div>
    </div>
    <?php
  }

  public static function render_map($lat, $lng) {
    if ($lat > -100 && $lng>-500) {
      ?>
      <div class="topImgCtnr floatright">
        <div id="map" style="height:250px"></div>
      </div>
      <script>
        document.addEventListener("DOMContentLoaded", function(event) {
          marker = new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.transform([<?php print $lng.','.$lat; ?>], "EPSG:4326", "EPSG:3857"))
          });
          var extent = marker.getGeometry().getExtent().slice(0);
          var raster = new ol.layer.Tile({
            source: new ol.source.OSM()
          });
          var vectorSource = new ol.source.Vector({
            features: [marker]
          });
          var iconStyle = new ol.style.Style({
            image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
              anchor: [0.5, 46],
              anchorXUnits: 'fraction',
              anchorYUnits: 'pixels',
              opacity: 0.75,
              src: '<?php print BASE_MAIN; ?>images/a/a0/Picto-gris.png'
            }))
          });
          var vectorLayer = new ol.layer.Vector({
            source: vectorSource,
            style: iconStyle
          });
          map = new ol.Map({
            layers: [raster, vectorLayer],
            target: "map"
          });
          map.getView().fit(extent);
          map.getView().setZoom(15);
        });
      </script>
      <?php
    } else {
      ?>
      <div class="topImgCtnr floatright">
        <div id="open_layer_1" style="width: 420px; height: 268px; background-color: #cccccc; overflow: hidden;" class="maps-map maps-openlayers">
        </div>
      </div>
      <?php
    }
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

  public static function render_claim_coords($lat, $lng, $title) {
    if ($lat>-100 && $lng>-500) {

      if ($lat >= 0)
        $NS = 'N';
      else {
        $NS = 'S';
        $lat = -$lat;
      }

      if ($lng >= 0)
        $EW = 'E';
      else {
        $EW = 'O';
        $lng = -$lng;
      }

      $lat_deg = floor($lat);
      $lat_min = floor(($lat-$lat_deg)*60);
      $lat_sec = round(($lat-$lat_deg-$lat_min/60)*3600);

      $lng_deg = floor($lng);
      $lng_min = floor(($lng-$lng_deg)*60);
      $lng_sec = round(($lng-$lng_deg-$lng_min/60)*3600);

      print '<tr><th>' . $title . '</th><td>' . $lat_deg . '° ' . $lat_min . '\' ' . $lat_sec . '" ' . $NS . '<br />' . 
                                                $lng_deg . '° ' . $lng_min . '\' ' . $lng_sec . '" ' . $EW . '</td></tr>';
    }
  }

  public static function render_artists($artists_wd, $artists_am) {
    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artist.php');
    Artist::render_artists_for_artwork($artists_wd, $artists_am);
  }

  public static function render_galerie($param, $name, $title) {
    if (isset($param[$name])) {
      ?>
      <div class="atmslideshowCtnr">
        <div class="atmslideshowHead" onclick="toggleFold(this)"><h3><?php print $title; ?></h3></div>
        <ul class="atmslideshowContent" style="display:none;">
        <?php
          foreach(explode(';', $param[$name]) as $image) {
            $image_url = 'http://publicartmuseum.net/wiki/Fichier:' . $image;
            $tmp = self::get_image_am($image, 192);
            foreach($tmp->query->pages as $image)
              $image_thumb = $image->imageinfo[0]->thumburl;
            ?>
            <li>
              <div class="thumb tright">
                <div class="thumbinner">
                  <a href="<?php print $image_url; ?>" class="image">
                    <img alt="" src="<?php print $image_thumb; ?>" width="192" height="140" srcset="">
                  </a>
                </div>
              </div>
            </li>
            <?php
          }
        ?>
        </ul>
      </div>
      <?php
    }
  }

  public static function render_other_works($id, $creators, $param) {
    $currentArticle = html_entity_decode(str_replace('_', ' ', str_replace('title=', '', $_SERVER['QUERY_STRING'])));

    $artists_id = [];
    $artists_names = [];
    for ($i=0; $i<sizeof($creators); $i++) {
      array_push($artists_id, $creators[0]->mainsnak->datavalue->value->id);
      $result = get_artists_names($artists_id);
      while ($row = $result->fetch_row()) {
        array_push($artists_names, $row[0]);
      }
    }

    if (array_key_exists('artiste', $param)) {
      $artists_am = explode (';', $param['artiste']);
      foreach ($artists_am as $a) {
        if (preg_match('/^[Qq][0-9]+/', $a))
          array_push($artists_id, $a);
        else
          array_push($artists_names, $a);
      }

      $result = get_artists_from_ids($artists_am);
      while ($row = $result->fetch_row()) {
        array_push($artists_id, $row[1]);
      }
      $result = get_artists_names($artists_id);
      while ($row = $result->fetch_row()) {
        array_push($artists_names, $row[0]);
      }
    }

    $artworks = [];

    $data2 = get_artworks_from_artists(array_merge($artists_id, $artists_names));
    if (!is_null($data2) && $data2) {
      while ($row = $data2->fetch_row()) {
        if ($row[2] != '') {
          $tmp = Artist::get_image_am($row[2], 420);
          if (isset($tmp->query->pages))
            foreach($tmp->query->pages as $image)
              $image_thumb = $image->imageinfo[0]->thumburl;
          $image_url = ATLASMUSEUM_PATH . ATLASMUSEUM_FILE_PREFIX . $row[2];
          if (is_null($image_thumb)) {
            $image_thumb = MISSING_IMAGE_FILE;
            $image_url = MISSING_IMAGE_LINK;
          }
        } else {
          $image_thumb = MISSING_IMAGE_FILE;
          $image_url = MISSING_IMAGE_LINK;
        }

        //if ($row[0] != $currentArticle)
          array_push($artworks, [
            'article' => ATLASMUSEUM_PATH . $row[0],
            'canonicalArticle' => $row[0],
            'title' => $row[1],
            'image_url' => $image_url,
            'image_thumb' => $image_thumb,
            'wikidata' => $row[3],
            'ok' => true
          ]);

      }
    }

    $query =
      "SELECT DISTINCT ?item ?itemLabel ?placeLabel ?countryLabel ?image WHERE {" .
      "  ?item wdt:P170 ?creator ;" .
      "        wdt:P136 wd:Q557141 ." .
      "  VALUES ?creator  { wd:" . implode(' wd:', $artists_id) . " } " .
      "  OPTIONAL { ?item wdt:P18 ?image }" .
      "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr,en\" . }" .
      "} ORDER BY ?itemLabel";

    $data = Api::Sparql($query);
    $images = '';

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

        array_push($artworks, [
          'article' => 'Spécial:Wikidata/' . $artwork_id,
          'canonicalArticle' => $artwork_id,
          'title' => $title,
          'image_url' => $image_url,
          'image_thumb' => $image_thumb,
          'wikidata' => $artwork_id,
          'ok' => true
        ]);
      }
    }

    for ($i=0; $i<sizeof($artworks)-1; $i++) {
      for ($j=$i+1; $j<sizeof($artworks); $j++) {
        if ($artworks[$i]['wikidata'] != '' && $artworks[$i]['wikidata'] == $artworks[$j]['wikidata']) {
          if ($artworks[$i]['image_url'] == MISSING_IMAGE_LINK) {
            $artworks[$i]['image_url'] = $artworks[$j]['image_url'];
            $artworks[$i]['image_thumb'] = $artworks[$j]['image_thumb'];
          }
          $artworks[$j]['ok'] = false;
        }
      }
    }

    usort($artworks, function ($item1, $item2) {
      return strcmp($item1["label"], $item2["label"]);
    });

    $n = 0;
    foreach($artworks as $artwork) {
      if ($artwork['ok'] && $artwork['canonicalArticle'] != $currentArticle) {
        $images .= '<li><div class="thumb tright"><div class="thumbinner"><a href="' . $artwork['article'] . '" class="image"><img alt="" src="' . $artwork['image_thumb'] . '" style="width:auto;max-width:192px;max-height:140px" srcset=""><br />' . $artwork['title'] . '</a></div></div></li>';
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

  public static function render_near_sites($id, $lat, $lng) {
         $query = "SELECT ?place ?placeLabel ?location ?image ?distance WHERE {".
          "  bind(strdt(\"Point(".$lng." ".$lat.")\", geo:wktLiteral) as ?placeLoc)".
          "  SERVICE wikibase:around {".
          "    ?place wdt:P625 ?location .".
          "    bd:serviceParam wikibase:center ?placeLoc .".
          "    bd:serviceParam wikibase:radius \"2\" .".
          "  } .".
          "  BIND (geof:distance(?placeLoc, ?location) AS ?distance)".
          "  OPTIONAL { ?place wdt:P18 ?image } " .
          "  FILTER EXISTS { ?place wdt:P18 ?image } .".
          "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr,en\" . } ".
          "} ORDER BY ?distance LIMIT 16";

    $data = Api::Sparql($query);
    $images = '';
    $n = 0;
    foreach($data->results->bindings as $place) {
      $place_id = str_replace('http://www.wikidata.org/entity/', '', $place->place->value);
      if ($place_id != $id) {
        $title = $place->placeLabel->value;
        if (isset($place->image)) {
          $image_url = $place->image->value;
          $image_thumb = $image_url;
        } else {
          $image_thumb = MISSING_IMAGE_FILE;
          $image_url = MISSING_IMAGE_LINK;
        }
        $images .= '<li><div class="thumb tright"><div class="thumbinner"><a href="https://www.wikidata.org/wiki/' . $place_id . '" class="image"><img alt="" src="' . $image_thumb . '" style="width:auto;max-width:192px;max-height:140px;" srcset=""><br />' . $title . '</a></div></div></li>';
        $n++;
      }
      if ($n == 4)
        break;
    }
    if ($images != '') {
      print '<div class="atmslideshowCtnr">
          <div class="atmslideshowHead" onclick="toggleFold(this)"><h3>Sites proches</h3></div>
          <ul class="atmslideshowContent" style="display:none;">' . $images . '</ul>
        </div>';
    }
  }
  
  public static function render_near_artworks($id, $lat, $lng) {

      $query = "SELECT DISTINCT ?artwork ?artworkLabel ?location ?image ?distance WHERE {".
      "  bind(strdt(\"Point(".$lng." ".$lat.")\", geo:wktLiteral) as ?artworkLoc)".
      "  SERVICE wikibase:around {".
      "    ?artwork wdt:P625 ?location .".
      "    bd:serviceParam wikibase:center ?artworkLoc .".
      "    bd:serviceParam wikibase:radius \"10\" .".
      "  } .".
      "  BIND (geof:distance(?artworkLoc, ?location) AS ?distance)".
      "  OPTIONAL { ?artwork wdt:P18 ?image . }".
      "  FILTER EXISTS { ?artwork wdt:P136 wd:Q557141 } .".
      "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr,en\" . } ".
      "} ORDER BY ?distance LIMIT 16";

    $data = Api::Sparql($query);
    $images = '';
    $n = 0;

    foreach($data->results->bindings as $artwork) {
      $artwork_id = str_replace('http://www.wikidata.org/entity/', '', $artwork->artwork->value);
      if ($artwork_id != $id) {
        $title = $artwork->artworkLabel->value;
        if (isset($artwork->image)) {
          $image_url = $artwork->image->value;
          $image_thumb = $image_url;
        } else {
          $image_thumb = MISSING_IMAGE_FILE;
          $image_url = MISSING_IMAGE_LINK;
        }
        $images .= '<li><div class="thumb tright"><div class="thumbinner"><a href="Spécial:Wikidata/' . $artwork_id . '" class="image"><img alt="" src="' . $image_thumb . '" style="width:auto;max-width:192px;max-height:140px;" srcset=""><br />' . $title . '</a></div></div></li>';
        $n++;
      }
      if ($n == 8)
        break;
    }

    if ($images != '') {
      print '<div class="atmslideshowCtnr">
          <div class="atmslideshowHead" onclick="toggleFold(this)"><h3>Œuvres proches</h3></div>
          <ul class="atmslideshowContent" style="display:none;">' . $images . '</ul>
        </div>';
    }
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
      'titles' => 'File:'.$image
    ), 'Commons');
  }

  public static function get_image_am($image, $width=320) {
    return Api::call_api(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => 'File:'.$image
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

  public static function get_coordinates($claims, $property, $param, $param_name) {
    $lat = -100;
    $lng = -500;

    if (isset($param[$param_name])) {
      $tmp = explode(',', $param[$param_name]);
      $lat = floatval($tmp[0]);
      $lng = floatval($tmp[1]);
    } else
    if (isset($claims->{$property})) {
      $lat = $claims->{$property}[0]->mainsnak->datavalue->value->latitude;
      $lng = $claims->{$property}[0]->mainsnak->datavalue->value->longitude;
    }

    return [$lat, $lng];
  }

  public static function renderArtwork($param = array(), $wikidata=true) {    
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );

    if (!is_null($param['q'])) {
      $q = $param['q'];

      $data = ArtworkGetData::get_props($q);
      $ids = ArtworkGetData::get_ids($data);
      array_push($ids, $q);

      $claims = $data->entities->{$q}->claims;
    } else {
      $q = '';
      $data = [];
      $ids = [];
      $labels = [];
      $claims = null;
    }

    $ids_am = ArtworkGetData::get_ids2($param);
    $ids = array_merge($ids, $ids_am);
    $labels = ArtworkGetData::get_labels($ids);

    list($lat, $lng) = self::get_coordinates($claims, 'P625', $param, 'site_coordonnees');

    ob_start();

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
          <img src="http://publicartmuseum.net/tmp/w/skins/AtlasMuseum/resources/images/hmodify.png" />
          Importer cette œuvre dans atlasmuseum
        </a>
      </div>
      <?php
    } else {
      ?>
      <div class="import">
        <a href="<?php print ATLASMUSEUM_PATH; ?>Spécial:WikidataExport/<?php print $_GET['title']; ?>">
          <img src="http://publicartmuseum.net/tmp/w/skins/AtlasMuseum/resources/images/hmodify.png" />Exporter cette œuvre sur Wikidata
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
                <img src="http://publicartmuseum.net/tmp/w/skins/AtlasMuseum/resources/hwikidata.png" />
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

    $contents = ob_get_contents();
    ob_end_clean();

    return preg_replace("/\r|\n/", "", $contents);

  }

}
