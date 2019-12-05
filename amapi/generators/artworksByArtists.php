<?php
/*****************************************************************************
 * artworksByArtists.php
 *
 * Récupère les œuvres d'un ou plusieurs artistes
 *****************************************************************************/

if (!class_exists('ArtworksByArtists')) {

  require_once('includes/api.php');

  class ArtworksByArtists {
    /**
     * Valide les paramètres
     *
     * @return {Object} Tableau contenant le résultat de la validation
     */
    public static function validateQuery() {
      $artists = getRequestParameter('artists');
      if (is_null($artists))
        return [
          'success' => 0,
          'error' => [
            'code' => 'no_artists',
            'info' => 'No value provided for parameter "artists".',
            'status' => 400
          ]
        ];

      $payload = [
        'artists' => explode('|', str_replace('_', ' ', $artists)),
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

    protected static function getIdsWikidata($artists) {
      $ids = [];

      for ($i = 0; $i < sizeof($artists); $i++) {
        if (preg_match('/^[qQ][0-9]+$/', $artists[$i]))
          array_push($ids, $artists[$i]);
      }

      $queryParameters = [
        '?Wikidata' => 'wikidata',
      ];

      $queryString = '[[Catégorie:Artistes]][[' . implode('||', $artists) . ']]';
      $results = API::ask($queryString, $queryParameters);

      for ($i = 0; $i < sizeof($results) ; $i++) {
        if (!is_null($results[$i]->printouts[0]->{'0'}))
          array_push($ids, $results[$i]->printouts[0]->{'0'});
      }

      return array_unique($ids);
    }

    protected static function convertArtistsWikidata($ids) {
      $articles = [];

      if (sizeof($ids) > 0) {
        $queryParameters = [
          '?#' => 'article',
        ];

        $queryString = '[[Catégorie:Artistes]][[Wikidata::' . implode('||', $ids) . ']]';
        $results = API::ask($queryString, $queryParameters);

        for ($i = 0; $i < sizeof($results) ; $i++) {
          array_push($articles, $results[$i]->fulltext);
        }
      }

      return $articles;
    }

    protected static function getArtworksByArtistsAM($artists) {
      $artworks = [];

      $queryParameters = [
        '?#' => 'article',
        '?Image principale' => 'image',
        '?Titre' => 'titre',
        '?Coordonnées' => 'coordonnees',
        '?Wikidata' => 'wikidata'
      ];

      $queryString = '[[Catégorie:Notices d\'œuvre]][[Auteur::' . implode('||', $artists) . ']]';
      $results = API::ask($queryString, $queryParameters);

      for ($i = 0; $i < sizeof($results) ; $i++) {
        $artwork = [
          'article' => $results[$i]->fulltext,
          'origin' => 'atlasmuseum'
        ];
        // Image
        if (!is_null($results[$i]->printouts[0]->{'0'})) {
          $fileName = $results[$i]->printouts[0]->{'0'};
          if (strtolower(substr($fileName, 0, 8)) === 'commons:') {
            // L'image provient de Commons
            $artwork['image'] = [
              'origin' => 'commons',
              'file' => substr($fileName, 8)
            ];
          } else {
            // L'image provient d'atlasmuseum
            $artwork['image'] = [
              'origin' => 'atlasmuseum',
              'file' => $fileName
            ];
          }
        }
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
        }
        // Wikidata
        if (!is_null($results[$i]->printouts[3]->{'0'}))
          $artwork['wikidata'] = $results[$i]->printouts[3]->{'0'};

        array_push($artworks, $artwork);
      }

      return $artworks;
    }

    protected static function getArtworksByArtistsWD($ids) {
      $artworks = [];

      $wdIDs = [];
      for ($i = 0; $i < sizeof($ids); $i++)
        array_push($wdIDs, 'wd:' . $ids[$i]);

      $query =
        'SELECT DISTINCT ?artwork ?artworkLabel ?location ?image WHERE {' .
        '  ?artwork wdt:P136/wdt:P279* ?genre ;' .
        '           wdt:P625 ?location ;' .
        '           wdt:P170 ?creator .' .
        '  VALUES ?genre { wd:Q557141 wd:Q219423 wd:Q17516 wd:Q326478 wd:Q2740415 }' .
        '  VALUES ?creator { ' . implode(' ', $wdIDs) . ' }' .
        '  OPTIONAL { ?artwork wdt:P18 ?image . }'.
        '  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr, en" . }' .
        '}';

      $data = Api::sparql($query);

      $ids = []; // Tableau des ids déjà récupérées ; permet d'éliminer les doublons (entité avec plusieurs images, par exemple)

      for ($i = 0; $i < sizeof($data->results->bindings); $i++) {
        $id = str_replace('http://www.wikidata.org/entity/', '', $data->results->bindings[$i]->artwork->value);
        if (!in_array($id, $ids)) {
          array_push($ids, $id);
          $artwork = [
            'origin' => 'wikidata',
            'wikidata' => $id
          ];
          $artwork['article'] = $artwork['wikidata'];

          // Image
          if (!is_null($data->results->bindings[$i]->image)) {
            $image = $data->results->bindings[$i]->image->value;
            $artwork['image'] = [
              'origin' => 'commons',
              'file' => urldecode(str_replace('http://commons.wikimedia.org/wiki/Special:FilePath/', '', $image))
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
      }

      return $artworks;
    }

    protected static function mergeArtworks($artworksAM, $artworksWD, $exclude = null) {
      // Tableau de retour
      $artworks = [];

      // Tableau des articles/ids à exclure
      $excludeArray = [];
      if (!is_null($exclude))
        $excludeArray = explode('|', $exclude);

      // Tableau des ids Wikidata déjà présents sur atlasmuseum
      $ids = [];

      // Supprimer les éventuels espaces insécables
      $exclude = str_replace("\xc2\xa0", ' ', $exclude);

      for ($i = 0; $i < sizeof($artworksAM); $i++) {
        if (!in_array($artworksAM[$i]['article'], $excludeArray))
          array_push($artworks, $artworksAM[$i]);

        if (!is_null($artworksAM[$i]['wikidata']))
          array_push($ids, $artworksAM[$i]['wikidata']);
      }
      
      // Regarde si chaque œuvre de Wikidata n'existe pas déjà sur atlasmuseum
      for ($i = 0; $i < sizeof($artworksWD); $i++) {
        if (!in_array($artworksWD[$i]['wikidata'], $ids) && !in_array($artworksWD[$i]['article'], $excludeArray))
          array_push($artworks, $artworksWD[$i]);
      }

      // Trie les œuvres par titre
      usort($artworks, function ($a, $b) {
        return strcmp($a['titre'], $b['titre']);
      });

      return $artworks;
    }

    /**
     * Retourne les sites proches d'un point géographique
     *
     * @param {float} $latitude
     * @param {float} $longitude
     * @param {float} $distance - Distance au point
     * @return {Object} sites
     */
    public static function getArtworksByArtists($payload) {
      $idsWikidata = self::getIdsWikidata($payload['artists']);
      $equivalentsAtlasmuseum = self::convertArtistsWikidata($idsWikidata);

      $artists = array_unique(array_merge($payload['artists'], $idsWikidata, $equivalentsAtlasmuseum));

      $artworksAM = self::getArtworksByArtistsAM($artists);
      $artworksWD = self::getArtworksByArtistsWD($idsWikidata);
      $artworks = self::mergeArtworks($artworksAM, $artworksWD, $payload['exclude']);

      return $artworks;
    }

  }
}
