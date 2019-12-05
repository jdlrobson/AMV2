<?php
/*****************************************************************************
 * closeArtworks.php
 *
 * Récupère les œuvres proches d'un point géographique
 *****************************************************************************/

if (!class_exists('CloseArtworks')) {

  require_once('includes/api.php');

  class CloseArtworks {
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

    protected static function getDistance($lat1, $lon1, $lat2, $lon2) {
      $earthRadiusKm = 6371;

      $dLat = deg2rad($lat2-$lat1);
      $dLon = deg2rad($lon2-$lon1);

      $lat1 = deg2rad($lat1);
      $lat2 = deg2rad($lat2);

      $a = sin($dLat/2) * sin($dLat/2) + sin($dLon/2) * sin($dLon/2) * cos($lat1) * cos($lat2); 
      $c = 2 * atan2(sqrt($a), sqrt(1-$a));

      return $earthRadiusKm * $c;
    }

    protected static function getCloseArtworksAM($latitude, $longitude, $distance) {
      $artworks = [];

      $queryParameters = [
        '?#' => 'article',
        '?Image principale' => 'image',
        '?Titre' => 'titre',
        '?Coordonnées' => 'coordonnees',
        '?Wikidata' => 'wikidata'
      ];

      $queryString = '[[Catégorie:Notices d\'œuvre]][[Coordonnées::' . $latitude . ', ' . $longitude . ' (' . $distance . ' km)]]';
      $results = API::ask($queryString, $queryParameters);

      for ($i = 0; $i < sizeof($results) ; $i++) {
        $artwork = [
          'article' => $results[$i]->fulltext,
          'origin' => 'atlasmuseum'
        ];
        // Image
        if (!is_null($results[$i]->printouts[0]->{'0'}))
          $artwork['image'] = [
            'origin' => 'atlasmuseum',
            'file' => $results[$i]->printouts[0]->{'0'}
          ];
        // Titre
        if (!is_null($results[$i]->printouts[1]->{'0'}))
          $artwork['titre'] = $results[$i]->printouts[1]->{'0'};
        // Coordonnées
        if (!is_null($results[$i]->printouts[2]->{'0'})) {
          $lat = $results[$i]->printouts[2]->{'0'}->lat;
          $lon = $results[$i]->printouts[2]->{'0'}->lon;
          $artwork['coordonnees'] = [
            'latitude' => $lat,
            'longitude' => $lon
          ];
          $artwork['distance'] = self::getDistance($latitude, $longitude, $lat, $lon);
        }
        // Wikidata
        if (!is_null($results[$i]->printouts[3]->{'0'}))
          $artwork['wikidata'] = $results[$i]->printouts[3]->{'0'};

        array_push($artworks, $artwork);
      }

      return $artworks;
    }

    protected static function getCloseArtworksWD($latitude, $longitude, $distance) {
      $artworks = [];

      $query = "SELECT DISTINCT ?artwork ?artworkLabel ?location ?image ?distance WHERE {".
        "  bind(strdt(\"Point(" . $longitude . " " . $latitude . ")\", geo:wktLiteral) as ?artworkLoc)".
        "  SERVICE wikibase:around {".
        "    ?artwork wdt:P625 ?location .".
        "    bd:serviceParam wikibase:center ?artworkLoc .".
        "    bd:serviceParam wikibase:radius \"10\" .".
        "  } .".
        "  BIND (geof:distance(?artworkLoc, ?location) AS ?distance)".
        "  OPTIONAL { ?artwork wdt:P18 ?image . }".
        "  FILTER EXISTS { ?artwork wdt:P136 wd:Q557141 } .".
        "  SERVICE wikibase:label { bd:serviceParam wikibase:language \"fr,en\" . } ".
        "} ORDER BY ?distance LIMIT 17";

      $data = Api::sparql($query);

      for ($i = 0; $i < sizeof($data->results->bindings); $i++) {
        $artwork = [
          'origin' => 'wikidata',
          'wikidata' => str_replace('http://www.wikidata.org/entity/', '', $data->results->bindings[$i]->artwork->value),
          'distance' => floatval($data->results->bindings[$i]->distance->value)
        ];
        $artwork['article'] = $artwork['wikidata'];

        // Image
        if (!is_null($data->results->bindings[$i]->image)) {
          $image = $data->results->bindings[$i]->image->value;
          $artwork['image'] = [
            'origin' => 'commons',
            'file' => $image
          ];
        }
        // Titre
        if (!is_null($data->results->bindings[$i]->artworkLabel))
          $artwork['titre'] = $data->results->bindings[$i]->artworkLabel->value;
        else
          $artwork['titre'] = $artwork['wikidata'];
        // Coordonnées
        $coords = explode(' ', str_replace('Point(', '', str_replace(')', '', $data->results->bindings[$i]->location->value)));
        $artwork['coordonnees'] = [
          'latitude' => $coords[1],
          'longitude' => $coords[0]
        ];

        array_push($artworks, $artwork);
      }

      return $artworks;
    }

    protected static function mergeArtworks($artworksAM, $artworksWD, $exclude = null) {
      // Tableau de retour
      $artworks = [];

      // Tableau des ids Wikidata déjà présents sur atlasmuseum
      $ids = [];

      for ($i = 0; $i < sizeof($artworksAM); $i++) {
        if (is_null($exclude) || $exclude === '' || $artworksAM[$i]['article'] !== $exclude)
          array_push($artworks, $artworksAM[$i]);

        if (!is_null($artworksAM[$i]['wikidata']))
          array_push($ids, $artworksAM[$i]['wikidata']);
      }

      // Regarde si chaque œuvre de Wikidata n'existe pas déjà sur atlasmuseum
      for ($i = 0; $i < sizeof($artworksWD); $i++) {
        if (!in_array($artworksWD[$i]['wikidata'], $ids) && (is_null($exclude) || $exclude === '' || $artworkWD[$i]['article'] != $exclude))
          array_push($artworks, $artworksWD[$i]);
      }

      // Trie les œuvres par distance croissante
      usort($artworks, function ($a, $b) {
        return $a['distance'] - $b['distance'];
      });

      // Limite le retour à 16 éléments
      return array_slice($artworks, 0, 16);
    }

    /**
     * Retourne les œuvres proches d'un point géographique
     *
     * @param {float} $latitude
     * @param {float} $longitude
     * @param {float} $distance - Distance au point
     * @return {Object} Œuvres
     */
    public static function getCloseArtworks($payload) {
      $artworks = [];

      $artworksAM = self::getCloseArtworksAM($payload['latitude'], $payload['longitude'], $payload['distance']);
      $artworksWD = self::getCloseArtworksWD($payload['latitude'], $payload['longitude'], $payload['distance']);
      $artworks = self::mergeArtworks($artworksAM, $artworksWD, $payload['exclude']);

      return $artworks;
    }

  }
}
