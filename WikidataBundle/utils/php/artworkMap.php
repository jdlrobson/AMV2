<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class GlobalArtworkMap {

  public static function render_map($lat, $lng) {

    if ($lng>-100) {
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
              src: PICTO_GRIS,
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

  public static function get_wikidata_artworks() {

    $query =
        'SELECT DISTINCT ?q ?qLabel ?coords ?creatorLabel ?image WHERE {' .
        '  ?q wdt:P136/wdt:P279* wd:Q557141 ;' .
        '     wdt:P625 ?coords .' .
        '  OPTIONAL { ?q wdt:P170 ?creator }' .
        '  OPTIONAL { ?q wdt:P18 ?image }' .
        '  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr" . }' .
        '} ORDER BY ?q';

    $result = Api::Sparql($query);
    $data = [];

    foreach ($result->results->bindings as $artwork) {
      $id = str_replace(WIKIDATA_ENTITY, '', $artwork->q->value);
      //$id = preg_replace('/^.*Q/', 'Q', $id);

      $title = $artwork->qLabel->value;
      $coords = explode(' ', str_replace(')', '', str_replace('Point(', '', $artwork->coords->value)));
      $image = isset($artwork->image) ? urldecode(str_replace(COMMONS_FILE_PATH, '', $artwork->image->value)) : '';
      $creator = isset($artwork->creatorLabel) ? $artwork->creatorLabel->value : '';

      if (isset($data[$id])) {
        if (!in_array($creator, $data[$id]['creator']))
          array_push($data[$id]['creator'], $creator);
      } else {
        $data[$id] = [
          'id' => $id,
          'title' => $title,
          'latitude' => floatval($coords[1]),
          'longitude' => floatval($coords[0]),
          'image' => $image,
          'creator' => [$creator]
        ];
      }
    }

    return $data;
  }

  public static function renderArtworkMap($param = array(), $wikidata=true) {
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );

    ob_start();

?>
<div id="map"></div>
<div id="map-popup" class="ol-popup" class="popupOeuvre">
  <a href="#" id="map-popup-closer" class="ol-popup-closer"></a>
  <p id="map-popup-content"></p>
</div>
<div class="mapLgd">
    <table>
      <tr>
        <td>
          <input type="checkbox" id="checkbox-perenne" class="map-checkbox" onclick="changeMarkers()" checked>
          <label for="checkbox-perenne"><span class="imgWrapper"><img alt="Picto-gris.png" src="/tmp/w/images/a/a0/Picto-gris.png" width="48" height="48"></span> œuvres pérennes</label>
        </td>
        <td>
          <input type="checkbox" id="checkbox-ephemere" class="map-checkbox" onclick="changeMarkers()" checked>
          <label for="checkbox-ephemere"><span class="imgWrapper"><img alt="Picto-jaune.png" src="/tmp/w/images/4/49/Picto-jaune.png" width="48" height="48"></span> œuvres éphémères</label>
        </td>
        <td>
          <input type="checkbox" id="checkbox-detruite" class="map-checkbox" onclick="changeMarkers()" checked>
          <label for="checkbox-detruite"><span class="imgWrapper"><img alt="Picto-rouge.png" src="/tmp/w/images/a/a8/Picto-rouge.png" width="24" height="24"></span> œuvres détruites</label>
        </td>
      </tr>
      <tr>
        <td>
          <input type="checkbox" id="checkbox-verifier" class="map-checkbox" onclick="changeMarkers()" checked>
          <label for="checkbox-verifier"><span class="imgWrapper"><img alt="Picto-bleu.png" src="/tmp/w/images/9/90/Picto-bleu.png" width="32" height="32"></span> œuvres à vérifier</label>
        </td>
        <td>
          <input type="checkbox" id="checkbox-non-realisee" class="map-checkbox" onclick="changeMarkers()" checked>
          <label for="checkbox-non-realisee"><span class="imgWrapper"><img alt="Picto-blanc.png" src="/tmp/w/images/2/2d/Picto-blanc.png" width="32" height="32"></span> œuvres non réalisées</label>
        </td>
        <td>
          <input type="checkbox" id="checkbox-wikidata" class="map-checkbox" onclick="changeMarkers()" checked>
          <label for="checkbox-wikidata"><span class="imgWrapper"><img alt="Picto-Wikidata.png" src="/tmp/w/images/d/dd/Picto-Wikidata.png" width="48" height="48"></span> Wikidata</label>
        </td>
      </tr>
  </table>
</div>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery.min.js"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
<script src="<?php print OPEN_LAYER_JS; ?>"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>map.js"></script>
<link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
<link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>map.css" type="text/css">
<?php

    $wd_data = self::get_wikidata_artworks();
    $contents = ob_get_contents();
    ob_end_clean();

    return preg_replace("/\r|\n/", "", $contents);
  }

}
