<?php

require_once('extensions/Wikidata/includes/api.php');

class Artwork {

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
        return '<tr><th>' . 
          ($title_plural != '' ? (sizeof($data)<=1 ? $title : $title_plural) : $title) . 
          '</th><td>' .
          join($data, ', ') .
          '</td></tr>';
        else
          return '';
    } else 
      return '';
  }
  
  public static function render_claim_am($param, $name, $title, $two_lines = false) {
    if (isset($param[$name])) {
      if ($two_lines) {
        return '<tr><td colspan="2"><b>' . $title . '</b><br />' . $param[$name] . '</td></tr>';
      } else {
        return '<tr><th>' . $title . '</th><td>' . $param[$name] . '</td></tr>';
      }
    } else
      return '';
  }

  public static function render_artists($artists) {
    foreach ($artists as $artist) {
      $artist_id = $artist->mainsnak->datavalue->value->id;
      $artist_data = self::get_props($artist_id);
      $artist_ids = self::get_ids($artist_data);
      array_push($artist_ids, $artist_id);
      $artist_labels = self::get_labels($artist_ids);

      $image_thumb = 'http://publicartmuseum.net/tmp/w/images/5/5f/Image-manquante.jpg';
      $image_url = 'http://publicartmuseum.net/tmp/w/index.php?title=Fichier:Image-manquante.jpg';

      if (isset($artist_data->entities->{$artist_id}->claims->P18)) {
        $image_url = 'https://commons.wikimedia.org/wiki/File:' . $artist_data->entities->{$artist_id}->claims->P18[0]->mainsnak->datavalue->value;
        $tmp = self::get_image($artist_data->entities->{$artist_id}->claims->P18[0]->mainsnak->datavalue->value);
        foreach($tmp->query->pages as $image)
          $image_thumb = $image->imageinfo[0]->thumburl;
      }

      return '<h3><span class="mw-headline" id=""><a href="" title="">' . (isset($artist_labels[$artist_id]) ? $artist_labels[$artist_id] : $artist_id) . '</a></span></h3>' . 
      '<p><a href="' . $image_url . '" class="image"><img alt="" src="' . $image_thumb . '" width="224" height="149" /></a></p>' .
      '<table class="wikitable">' .
        self::render_claim($artist_data->entities->{$artist_id}->claims, 'P19', $artist_labels, 'Lieu de naissance') .
        self::render_claim($artist_data->entities->{$artist_id}->claims, 'P569', $artist_labels, 'Date de naissance') .
        self::render_claim($artist_data->entities->{$artist_id}->claims, 'P20', $artist_labels, 'Lieu de décès') .
        self::render_claim($artist_data->entities->{$artist_id}->claims, 'P570', $artist_labels, 'Date de décès') .
        self::render_claim($artist_data->entities->{$artist_id}->claims, 'P27', $artist_labels, 'Pays de nationalité') .
      '</table>
      ';
    }
  }

  public static function render_image($claims, $property, $param, $param_name) {

    $image_thumb = 'http://publicartmuseum.net/tmp/w/images/5/5f/Image-manquante.jpg';
    $image_url = 'http://publicartmuseum.net/tmp/w/index.php?title=Fichier:Image-manquante.jpg';
    $image_legend = '';

    if (isset($param[$param_name])) {
      $image_url = 'http://publicartmuseum.net/tmp/w/index.php?title=Fichier:' . $param[$param_name];
      $tmp = self::get_image_am($param[$param_name], 420);
      foreach($tmp->query->pages as $image)
        $image_thumb = $image->imageinfo[0]->thumburl;
      $tmp = self::parse_page_am('Fichier:'.$param[$param_name]);
      $image_legend = $tmp->parse->text->{'*'};
    } else
    if (isset($claims->{$property})) {
      $image_url = 'https://commons.wikimedia.org/wiki/File:' . $claims->{$property}[0]->mainsnak->datavalue->value;
      $tmp = self::get_image($claims->{$property}[0]->mainsnak->datavalue->value, 420);
      foreach($tmp->query->pages as $image)
        $image_thumb = $image->imageinfo[0]->thumburl;
    }

    return '<div class="topImgCtnr">
      <div class="thumb tright">
        <div class="thumbinner" style="width:422px;"><a href="/wiki/Fichier:Serpentinerouge.jpg" class="image"><img alt="" src="' . $image_thumb . '" width="420" height="209" class="thumbimage" srcset="" /></a>' . ($image_legend != '' ? '<div class="thumbcaption">' . $image_legend . '</div>' : '') .
        '</div>
      </div>
    </div>';
  }

  public static function get_coordinates($claims, $property, $param, $param_name) {
    $lat = -100;
    $lng = 0;

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

  public static function render_map($lat, $lng) {

    if ($lng>-100) {
      return '<div class="topImgCtnr floatright">
        <div id="map" style="height:250px"></div>
      </div><!--
      --><script>
        document.addEventListener("DOMContentLoaded", function(event) { 
          var map = new ol.Map({
            target: "map",
            layers: [
              new ol.layer.Tile({
                source: new ol.source.OSM()
              })
            ],
            view: new ol.View({
              center: ol.proj.fromLonLat(['.$lng.','.$lat.']),
              zoom: 15
            })
          });
        });
      </script>
      ';
    } else {
      return '<div class="topImgCtnr floatright">
        <div id="open_layer_1" style="width: 420px; height: 268px; background-color: #cccccc; overflow: hidden;" class="maps-map maps-openlayers">
        </div>
      </div>';
    }
  }

  public static function render_galerie($param, $name, $title) {

    if (isset($param[$name])) {

      $images = '';
      foreach(explode(';', $param[$name]) as $image) {
        $image_url = 'http://publicartmuseum.net/wiki/Fichier:' . $image;
        $tmp = self::get_image_am($image, 192);
        foreach($tmp->query->pages as $image)
          $image_thumb = $image->imageinfo[0]->thumburl;

        $images .= '<li><div class="thumb tright"><div class="thumbinner"><a href="' . $image_url . '" class="image"><img alt="" src="' . $image_thumb . '" width="192" height="140" srcset=""></a></div></div></li>';
      }

      return '<div class="atmslideshowCtnr">
        <div class="atmslideshowHead"><h3>' . $title . '</h3></div>
        <ul>' . $images . '</ul>
      </div>';
    } else 
      return '';
  }

  public static function render_other_works($id, $creators) {

    $query = "SELECT DISTINCT ?item ?itemLabel ?placeLabel ?countryLabel ?image WHERE {" .
      "?item wdt:P170 wd:" . $creators[0]->mainsnak->datavalue->value->id . " ;" .
      "      wdt:P136 wd:Q557141 ." .
      "  OPTIONAL { ?item wdt:P18 ?image }" .
      "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr\" . }" .
      "} ORDER BY ?itemLabel";

    $data = Api::Sparql($query);
    $images = '';
    $n = 0;

    foreach($data->results->bindings as $artwork) {
      $artwork_id = str_replace('http://www.wikidata.org/entity/', '', $artwork->item->value);
      if ($artwork_id != $id) {
        $title = $artwork->itemLabel->value;
        if (isset($artwork->image)) {
          $image_url = $artwork->image->value;
          $image_thumb = $image_url;
        } else {
          $image_thumb = 'http://publicartmuseum.net/tmp/w/images/5/5f/Image-manquante.jpg';
          $image_url = 'http://publicartmuseum.net/tmp/w/index.php?title=Fichier:Image-manquante.jpg';
        }
        $images .= '<li><div class="thumb tright"><div class="thumbinner"><a href="http://publicartmuseum.net/tmp/w/index.php?title=Spécial:Wikidata/' . $artwork_id . '" class="image"><img alt="" src="' . $image_thumb . '" width="192" height="140" srcset=""><br />' . $title . '</a></div></div></li>';
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

    return '<div class="atmslideshowCtnr">
        <div class="atmslideshowHead"><h3>' . $title . '</h3></div>
        <ul>' . $images . '</ul>
      </div>';
  }

  public static function render_near_sites($id, $lat, $lng) {
    /*$query = "SELECT DISTINCT ?place ?placeLabel ?distance ?coords ?image WHERE {" .
      "  bind(strdt(\"Point(".$lng." ".$lat.")\", geo:wktLiteral) as ?location) .".
      "  SERVICE wikibase:around {".
      "    ?place wdt:P625 ?coords .".
      "    bd:serviceParam wikibase:center ?location .".
      "    bd:serviceParam wikibase:radius \"2\" .".
      "  } .".
      "  BIND (geof:distance(?coords, ?location) AS ?distance)" .
      "  ?place wdt:P18 ?image .".
      "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr\" . }".
       "}".
       " ORDER BY ?distance ".
       "LIMIT 100";*/
       /*
       $lat_rad = deg2rad($lat);
       $lng_rad = deg2rad($lng);
       
       //-- Longueur en km d'un degré de parallèle à la latitude considérée
       $lng_length = 111.317099692*cos($lng_rad);
       
       //-- Longueur en km d'un degré de méridien à la latitude considérée
       $lat_length = 111.317099692;
       
       $query = "SELECT DISTINCT ?place ?placeLabel ?distance ?coords ?image WHERE {".
         "  ?place p:P625/psv:P625 ?coords ; wdt:P18 ?image . ".
         "  ?coords wikibase:geoLatitude ?lat ;".
         "          wikibase:geoLongitude ?lon .".
         "  FILTER ( ABS(?lat - ".$lat.") < ".(2/$lat_length)." )".
         "  FILTER ( ABS(?lon - ".$lng.") < ".(2/$lng_length)." )".
         "  BIND (ABS(?lat - ".$lat.") + ABS(?lon - ".$lng.") AS ?distance)" .
         "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr\" }".
         "}".
         "ORDER BY ?distance ?placeLabel";*/
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
          "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr\" . } ".
          "} ORDER BY ?distance";

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
          $image_thumb = 'http://publicartmuseum.net/tmp/w/images/5/5f/Image-manquante.jpg';
          $image_url = 'http://publicartmuseum.net/tmp/w/index.php?title=Fichier:Image-manquante.jpg';
        }
        $images .= '<li><div class="thumb tright"><div class="thumbinner"><a href="https://www.wikidata.org/wiki/' . $place_id . '" class="image"><img alt="" src="' . $image_thumb . '" width="192" height="140" srcset=""><br />' . $title . '</a></div></div></li>';
        $n++;
      }
      if ($n == 4)
        break;
    }

    return '<div class="atmslideshowCtnr">
        <div class="atmslideshowHead"><h3>Sites proches</h3></div>
        <ul>' . $images . '</ul>
      </div>';
  }

  public static function render_near_artworks($id, $lat, $lng) {
    /*$lat_rad = deg2rad($lat);
		$lng_rad = deg2rad($lng);
		
		//-- Longueur en km d'un degré de parallèle à la latitude considérée
		$lng_length = 111.317099692*cos($lng_rad);
		
		//-- Longueur en km d'un degré de méridien à la latitude considérée
    $lat_length = 111.317099692;
    
    $query = "SELECT DISTINCT ?artwork ?artworkLabel ?lat ?lon ?image WHERE {".
      "  ?artwork wdt:P136 wd:Q557141 ;".
      "           p:P625/psv:P625 ?coords .".
      "  ?coords wikibase:geoLatitude ?lat ;".
      "          wikibase:geoLongitude ?lon .".
      "  FILTER ( ABS(?lat - ".$lat.") < ".(10/$lat_length)." )".
      "  FILTER ( ABS(?lon - ".$lng.") < ".(10/$lng_length)." )".
      "  BIND (ABS(?lat - ".$lat.") + ABS(?lon - ".$lng.") AS ?dist)" .
      "  OPTIONAL { ?artwork wdt:P18 ?image }".
      "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr\" }".
      "}".
      "ORDER BY ?dist ?artwork";*/

      $query = "SELECT ?artwork ?artworkLabel ?location ?image ?distance WHERE {".
      "  bind(strdt(\"Point(-1.6777592897415 47.19653108157)\", geo:wktLiteral) as ?artworkLoc)".
      "  SERVICE wikibase:around {".
      "    ?artwork wdt:P625 ?location .".
      "    bd:serviceParam wikibase:center ?artworkLoc .".
      "    bd:serviceParam wikibase:radius \"10\" .".
      "  } .".
      "  BIND (geof:distance(?artworkLoc, ?location) AS ?distance)".
      "  OPTIONAL { ?artwork wdt:P18 ?image . }".
      "  FILTER EXISTS { ?artwork wdt:P136 wd:Q557141 } .".
      "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr\" . } ".
      "} ORDER BY ?distance";

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
          $image_thumb = 'http://publicartmuseum.net/tmp/w/images/5/5f/Image-manquante.jpg';
          $image_url = 'http://publicartmuseum.net/tmp/w/index.php?title=Fichier:Image-manquante.jpg';
        }
        $images .= '<li><div class="thumb tright"><div class="thumbinner"><a href="http://publicartmuseum.net/tmp/w/index.php?title=Spécial:Wikidata/' . $artwork_id . '" class="image"><img alt="" src="' . $image_thumb . '" width="192" height="140" srcset=""><br />' . $title . '</a></div></div></li>';
        $n++;
      }
      if ($n == 8)
        break;
    }

    return '<div class="atmslideshowCtnr">
        <div class="atmslideshowHead"><h3>Œuvres proches</h3></div>
        <ul>' . $images . '</ul>
      </div>';
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

  public static function renderArtwork($param = array()) {
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );
    if (!is_null($param['q'])) {

      $q = $param['q'];

      $data = self::get_props($q);
      $ids = self::get_ids($data);
      $labels = self::get_labels($ids);

      list($lat, $lng) = self::get_coordinates($data->entities->{$q}->claims, 'P625', $param, 'site_coordonnees');

      return '<div class="dalm">
        <div class="topCtnr">' .
        self::render_image($data->entities->{$q}->claims, 'P18', $param, 'image_principale') .
        self::render_map($lat, $lng) .
        '</div>
        <div class="ibCtnr">
          <div class="ibOeuvre">
            <h2> <span class="mw-headline" id=".C5.92uvre"> Œuvre </span></h2>
            <table class="wikitable">
              <tr>
                <th>Titre</th>
                <td>' . $data->entities->{$q}->labels->fr->value . '</td>
              </tr>' .
              self::render_claim_am($param, 'description', 'Description', true) .
              self::render_claim($data->entities->{$q}->claims, 'P571', $labels, 'Date', 'Date') .
              self::render_claim_am($param, 'nature', 'Nature', false) .
              self::render_claim_am($param, 'contexte_production', 'Contexte de production', true) .
              self::render_claim_am($param, 'période_art', 'Période', false) .
              self::render_claim($data->entities->{$q}->claims, 'P31', $labels, 'Domaine', 'Domaines') .
              self::render_claim($data->entities->{$q}->claims, 'P462', $labels, 'Couleur', 'Couleurs') .
              self::render_claim($data->entities->{$q}->claims, 'P186', $labels, 'Matériau', 'Matériaux') .
              self::render_claim_am($param, 'precision_materiaux', 'Précision sur les matériaux', false) .
              self::render_claim_am($param, 'diametre', 'Diamètre (m)', false) .
              self::render_claim_am($param, 'precision_dimensions', 'Précision sur les dimensions', false) .
              self::render_claim_am($param, 'forme', 'Sujet représenté', false) .
              self::render_claim_am($param, 'mot_cle', 'Mots clés', false) .
              self::render_claim_am($param, 'influences', 'Influences', false) .
              self::render_claim_am($param, 'a_influence', 'A influencé', false) .
              self::render_claim_am($param, 'commanditaires', 'Commanditaire(s)', false) .
              self::render_claim_am($param, 'commissaires', 'Commissaires', false) .
              self::render_claim_am($param, 'partenaires_prives', 'Partenaires privés', false) .
            '</table>
          </div>
          <div class="ibSite">
            <h2> <span class="mw-headline" id="Site"> Site </span></h2>
            <table class="wikitable">' .
              self::render_claim($data->entities->{$q}->claims, 'P276', $labels, 'Lieu', 'Lieux') .
              self::render_claim($data->entities->{$q}->claims, 'P131', $labels, 'Ville', 'Villes') .
              self::render_claim($data->entities->{$q}->claims, 'P17', $labels, 'Pays', 'Pays') .
              self::render_claim($data->entities->{$q}->claims, 'P2846', $labels, 'PMR', 'PMR') .
              self::render_claim_am($param, 'site_details', 'Détails sur le site', false) .
              self::render_claim_am($param, 'site_visibilite', 'Visibilité', false) .
              self::render_claim_am($param, 'site_pois', 'Points d\'intérêt', false) .
              self::render_claim_am($param, 'site_coordonnees', 'Latitude/Longitude', false) .
            '</table>
          </div>
          <div class="ibArtiste">
          <h2> <span class="mw-headline" id="Artiste"> Artiste' . (sizeof($data->entities->{$q}->claims->P170)>1 ? 's' : '') . ' </span></h2>' .
            self::render_artists($data->entities->{$q}->claims->P170) .
          '</div>
          <div class="clearfix"></div>
        </div>' .
        ( isset($param['source']) ? '<div class="mapCtnr"><b>Sources :</b><br />' . $param['source'] . '</div>' : '') .
      '<div class="atlasCtnr">
        <h2> <span class="mw-headline" id="ATLAS"> ATLAS </span></h2>' .
        self::render_galerie($param, 'image_galerie_construction', 'Construction / installation / Montage') .
        self::render_galerie($param, 'image_galerie_autre', 'Autres prises de vues') .
        self::render_other_works($q, $data->entities->{$q}->claims->P170) .
        self::render_near_sites($q, $lat, $lng) .
        self::render_near_artworks($q, $lat, $lng) .
      '</div>
      </div><script src="https://openlayers.org/en/v4.6.5/build/ol.js"></script>
      <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
      ';
    } else
      return Html::rawElement( 'div', $attribs, '' );
  }

}
