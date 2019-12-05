<?php
/*****************************************************************************
 * closeSites.php
 *
 * Récupère les œuvres proches d'un point géographique
 *****************************************************************************/

if (!class_exists('CloseSites')) {

  require_once('includes/api.php');

  class CloseSites {
    /**
     * Valide les paramètres
     *
     * @return {Object} Tableau contenant le résultat de la validation
     */
    public static function validateQuery() {
      $latitude = getRequestParameter('latitude');
      if (is_null($latitude))
        return [
          'success' => 0,
          'error' => [
            'code' => 'no_latitude',
            'info' => 'No value provided for parameter "latitude".',
            'status' => 400
          ]
        ];
      if (!is_numeric($latitude))
        return [
          'success' => 0,
          'error' => [
            'code' => 'invalid_latitude',
            'info' => 'Invalid value provided for parameter "latitude".',
            'status' => 400
          ]
        ];
      $latitude = floatval($latitude);
      if ($latitude < -90 || $latitude > 90)
        return [
          'success' => 0,
          'error' => [
            'code' => 'invalid_latitude',
            'info' => 'Invalid value provided for parameter "latitude".',
            'status' => 400
          ]
        ];

      $longitude = getRequestParameter('longitude');
      if (is_null($longitude))
        return [
          'success' => 0,
          'error' => [
            'code' => 'no_longitude',
            'info' => 'No value provided for parameter "longitude".',
            'status' => 400
          ]
        ];
      if (!is_numeric($longitude))
        return [
          'success' => 0,
          'error' => [
            'code' => 'invalid_longitude',
            'info' => 'Invalid value provided for parameter "longitude".',
            'status' => 400
          ]
        ];
      $longitude = floatval($longitude);
      if ($longitude < -180 || $longitude > 180)
        return [
          'success' => 0,
          'error' => [
            'code' => 'invalid_longitude',
            'info' => 'Invalid value provided for parameter "longitude".',
            'status' => 400
          ]
        ];
      
      $distance = getRequestParameter('distance');
      if (is_null($distance))
        return [
          'success' => 0,
          'error' => [
            'code' => 'no_distance',
            'info' => 'No value provided for parameter "distance".',
            'status' => 400
          ]
        ];
      if (!is_numeric($distance))
        return [
          'success' => 0,
          'error' => [
            'code' => 'invalid_distance',
            'info' => 'Invalid value provided for parameter "distance".',
            'status' => 400
          ]
        ];
      $longitude = floatval($longitude);

      $payload = [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'distance' => $distance,
        'exclude' => ''
      ];

      $exclude = getRequestParameter('exclude');
      if (!is_null($exclude)) {
        $exclude = str_replace('_', ' ', $exclude);
        $payload['exclude'] = $exclude;
      }

      return [
        'success' => 1,
        'payload' => $payload
      ];
    }

    protected static function getCloseSitesWD($latitude, $longitude, $distance, $exclude) {
      $sites = [];

      $query = "SELECT ?place ?placeLabel ?location ?image ?distance WHERE {".
        "  bind(strdt(\"Point(" . $longitude . " " . $latitude . ")\", geo:wktLiteral) as ?placeLoc)".
        "  SERVICE wikibase:around {".
        "    ?place wdt:P625 ?location .".
        "    bd:serviceParam wikibase:center ?placeLoc .".
        "    bd:serviceParam wikibase:radius \"" . $distance . "\" .".
        "  } .".
        "  BIND (geof:distance(?placeLoc, ?location) AS ?distance)".
        "  OPTIONAL { ?place wdt:P18 ?image } " .
        "  FILTER EXISTS { ?place wdt:P18 ?image } .".
        "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr,en\" . } ".
        "} ORDER BY ?distance LIMIT 5";

      $data = Api::sparql($query);

      for ($i = 0; $i < sizeof($data->results->bindings); $i++) {
        $wikidata = str_replace('http://www.wikidata.org/entity/', '', $data->results->bindings[$i]->place->value);
        if (is_null($exclude) || $exclude === '' || $wikidata !== $exclude) {
          $site = [
            'wikidata' => $wikidata,
            'distance' => floatval($data->results->bindings[$i]->distance->value)
          ];

          // Image
          if (!is_null($data->results->bindings[$i]->image)) {
            $image = $data->results->bindings[$i]->image->value;
            $site['image'] = [
              'origin' => 'commons',
              'file' => urldecode(str_replace('http://commons.wikimedia.org/wiki/Special:FilePath/', '', $image))
            ];
          }

          // Titre
          if (!is_null($data->results->bindings[$i]->placeLabel))
            $site['label'] = $data->results->bindings[$i]->placeLabel->value;
          else
            $site['label'] = $site['wikidata'];
          $site['label'] = mb_strtoupper(mb_substr($site['label'], 0, 1)) . mb_substr($site['label'], 1);

          // Coordonnées
          $coords = explode(' ', str_replace('Point(', '', str_replace(')', '', $data->results->bindings[$i]->location->value)));
          $site['coordonnees'] = [
            'latitude' => $coords[1],
            'longitude' => $coords[0]
          ];

          array_push($sites, $site);
        }
      }

      return array_slice($sites, 0, 4);
    }

    /**
     * Retourne les sites proches d'un point géographique
     *
     * @param {float} $latitude
     * @param {float} $longitude
     * @param {float} $distance - Distance au point
     * @return {Object} sites
     */
    public static function getCloseSites($payload) {
      return self::getCloseSitesWD($payload['latitude'], $payload['longitude'], $payload['distance'], $payload['exclude']);
    }

  }
}
