<?php
/*****************************************************************************
 * collection.php
 *
 * Récupère les données d'une collection
 *****************************************************************************/

if (!class_exists('Collection')) {

  require_once('includes/api.php');

  class Collection {

    /**
     * Valide les paramètres
     *
     * @return {Object} Tableau contenant le résultat de la validation
     */
    public static function validateQuery() {
      $collection = getRequestParameter('collection');
      if (is_null($collection))
        return [
          'success' => 0,
          'error' => [
            'code' => 'no_collection',
            'info' => 'No value provided for parameter "collection".',
            'status' => 400
          ]
        ];

      return [
        'success' => 1,
        'payload' => [
          'collection' => str_replace('_', ' ', urldecode($collection))
        ]
      ];
    }

    protected static function getWikidataLabels($ids) {
      $labels = [];
      $split_ids = array_chunk($ids, 50);

      for ($i=0; $i<sizeof($split_ids); $i++) {
        $labels_data = Api::callApi(array(
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

    protected static function query($queryString, $queryParameters) {
      $offset = 0;
      $limit = 5000;
      $results = [];

      $queryParameters['limit'] = $limit;

      foreach ($queryParameters as $key => $value) {
        $queryString .= '|' . $key . '=' . $value;
      }

      $continue = false;
      do {
        // Requête à l'API
        $parameters = [
          'action' => 'ask',
          'query' => $queryString . '|offset=' . $offset
        ];
        $tmpData = API::callApi($parameters, 'atlasmuseum');

        if (!is_null($tmpData)) {
          // Doit-on continuer la query avec un offset ?
          if (property_exists($tmpData, 'query-continue-offset')) {
            $continue = true;
            $offset += $limit;
          } else
            $continue = false;

          // Notices
          if (property_exists($tmpData, 'query') && property_exists($tmpData->query, 'results'))
            $results = array_merge($results, $tmpData->query->results);
        } else {
          $continue = false;
        }
      } while ($continue && $offset < 4000);

      return $results;
    }

    protected static function getArtistsAM($ids) {
      $artists = [];

      $queryParameters = [
        '?#' => 'article',
        '?Wikidata' => 'wikidata',
      ];

      $queryString = '[[Catégorie:Artistes]][[Wikidata::' . implode('||', $ids) . ']]';

      $results = self::query($queryString, $queryParameters);

      for ($i = 0; $i < sizeof($results); $i++) {
        $text = $results[$i]->fulltext;
        $url = $results[$i]->fullurl;
        $wikidata = $results[$i]->printouts[0]->{'0'};

        $artists[$wikidata] = [
          'text' => $text,
          'url' => $url,
        ];
      }

      return $artists;
    }

    protected static function getCollectionAM($collection) {
      $queryParameters = [
        '?#' => 'article',
        '?Titre' => 'title',
        '?Image principale' => 'image',
        '?Auteur' => 'artist',
        '?Coordonnées' => 'coordinates',
        '?Nature' => 'nature',
        '?Date d\'inauguration' => 'date'
      ];
      
      $queryString = '[[-Contient la notice::' . $collection . ']]';

      return self::query($queryString, $queryParameters);
    }

    protected static function convertArtworks($data) {
      $artworks = [];
      $ids = [];

      for ($i = 0; $i < sizeof($data); $i++) {
        $artwork = [
          'article' => $data[$i]->fullurl,
          'title' => '',
          'artists' => [],
          'image' => '',
          'lat' => 0,
          'lon' => 0,
          'nature' => 'pérenne'
        ];

        for ($j = 0; $j < sizeof($data[$i]->printouts); $j++) {
          switch ($data[$i]->printouts[$j]->label) {
            case 'title':
              $artwork['title'] = $data[$i]->printouts[$j]->{'0'};
              break;

            case 'artist':
              $name = $data[$i]->printouts[$j]->{'0'}->fulltext;
              $url = $data[$i]->printouts[$j]->{'0'}->fullurl;
              if (preg_match('/^[qQ][0-9]+$/', $name)) {
                array_push($ids, $name);
                $url = 'http://publicartmuseum.net/wiki/Sp%C3%A9cial:WikidataArtist/' . $name;
              }
              array_push($artwork['artists'], [
                'name' => $name,
                'url' => $url
              ]);
              break;

            case 'coordinates':
              $artwork['lat'] = floatval($data[$i]->printouts[$j]->{'0'}->lat);
              $artwork['lon'] = floatval($data[$i]->printouts[$j]->{'0'}->lon);
              break;

            case 'nature':
              $artwork['nature'] = $data[$i]->printouts[$j]->{'0'};
              break;

            case 'date':
              $artwork['date'] = gmdate("Y", intval($data[$i]->printouts[$j]->{'0'}));
              break;

            default:
          }
        }

        array_push($artworks, $artwork);
      }

      // Recherche les éventuelles ids Wikidata présentes en fait sur atlasmuseum
      if (sizeof($ids) > 0) {
        $artistsAM = self::getArtistsAM($ids);
        $ids = [];

        for ($i = 0; $i < sizeof($artworks); $i++) {
          for ($j = 0; $j < sizeof($artworks[$i]['artists']); $j++) {
            $name = $artworks[$i]['artists'][$j]['name'];
            if (preg_match('/^[qQ][0-9]+$/', $name)) {
              if (array_key_exists($name, $artistsAM)) {
                $artworks[$i]['artists'][$j]['name'] = $artistsAM[$name]['text'];
                $artworks[$i]['artists'][$j]['url'] = $artistsAM[$name]['url'];
              } else {
                array_push($ids, $name);
              }
            }
          }
        }

        // Récupère les labels Wikidata manquants
        $labels = self::getWikidataLabels($ids);

        for ($i = 0; $i < sizeof($artworks); $i++) {
          for ($j = 0; $j < sizeof($artworks[$i]['artists']); $j++) {
            $name = $artworks[$i]['artists'][$j]['name'];
            if (preg_match('/^[qQ][0-9]+$/', $name)) {
              if (array_key_exists($name, $labels)) {
                $artworks[$i]['artists'][$j]['name'] = $labels[$name];
              }
            }
          }
        }
      }

      return $artworks;
    }

    /**
     * Retourne les notices d'œuvres d'une collection
     *
     * @return {Object} Œuvres
     */
    public static function getData($payload) {
      $data = self::getCollectionAM($payload['collection']);
      $artworks = self::convertArtworks($data);

      return $artworks;
    }

  }
}
