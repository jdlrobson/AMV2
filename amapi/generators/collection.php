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
      $props = getRequestParameter('props');
      if (is_null($props))
        $props = 'info|image|artworks';

      return [
        'success' => 1,
        'payload' => [
          'collection' => str_replace('_', ' ', urldecode($collection)),
          'props' => explode('|', $props)
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

    protected static function query2($queryConditions, $queryPrintouts, $queryParameters) {
      $offset = 0;
      $limit = 5000;
      $results = [];

      $queryParameters['limit'] = $limit;

      foreach ($queryParameters as $key => $value) {
        $queryString .= '|' . $key . '=' . $value;
      }

      $continue = false;
      do {
        $queryParametersString = [];
        foreach ($queryParameters as $key => $value) {
          array_push($queryParametersString, $key . '%3D' . $value);
        }
        if ($offset > 0)
          array_push($queryParametersString,'offset%3D' . $offset);

        // Requête à l'API
        $parameters = [
          'action' => 'askargs',
          'conditions' => implode('|', $queryConditions),
          'printouts' => implode('|', $queryPrintouts),
          'parameters' => implode('|', $queryParametersString)
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

    /**
     * Process une ligne d'images
     *
     * @param {string} $imageString
     * @return {Array} 
     */
    protected static function processImages($imageString) {
      $images = preg_split('/[\s]*;[\s]*/', $imageString);
      $data = [];
      foreach ($images as $image) {
        if (preg_match('/^commons:/i', $image)) {
          array_push($data, [
            'origin' => 'commons',
            'value' => preg_replace('/^commons:/i', '', $image),
            'url' => '',
            'thumbnail' => ''
          ]);
        } else {
          array_push($data, [
            'origin' => 'atlasmuseum',
            'value' => $image,
            'url' => '',
            'thumbnail' => ''
          ]);
        }
      }

      return [
        'type' => 'image',
        'value' => $data
      ];
    }
    
    /**
     * Process une ligne de texte
     *
     * @param {string} $text
     * @return {Array} 
     */
    protected static function processText($text) {
      return [
        'type' => 'text',
        'value' => [$text]
      ];
    }

    /**
     * Retourne le contenu d'une collection sur atlasmuseum
     *
     * @param {string} $revision - contenu de l'article
     * @return {Object} Contenu de la collection
     */
    protected static function getCollectionContent($revision) {
      $content = explode("\n", $revision);
      $data = [];
      $lineIndex = 0;

      while($lineIndex < sizeof($content) && strpos($content[$lineIndex], '/>') === false) {
        $key = strtolower(preg_replace('/^[\s]*\|[\s]*([^=]+)[\s]*=.*$/', '$1', $content[$lineIndex]));
        $value = preg_replace('/^[^=]+=[\s]*(.*)$/', '$1', $content[$lineIndex]);
        $value = str_replace('&quot;', '"', $value);
        $value = str_replace('\n', '<br />', $value);

        switch ($key) {
          case 'visuel':
            $data[$key] = self::processImages($value);
            break;

          case '{{collection':
          case '}}':
            break;

          default:
            $data[$key] = self::processText($value);
        }

        $lineIndex++;
      }

      return $data;
    }

    /**
     * Retourne le contenu d'une collection iste sur atlasmuseum
     *
     * @param {string} $article - article atlasmuseum
     * @return {Object} Contenu de la collection
     */
    protected static function getCollection($article) {
      $data = API::callApi([
        'action' => 'query',
        'prop' => 'revisions',
        'rvprop' => 'content',
        'titles' => $article
      ], 'atlasmuseum');

      if (!is_null($data->query->pages->{'-1'}))
        // L'article n'a pas été trouvé : retourne un tableau vide
        return [];

      foreach ($data->query->pages as $collection) {
        $data = self::getCollectionContent($collection->revisions[0]->{'*'});
      }

      return [
        'article' => $article,
        'data' => $data
      ];
    }

    protected static function getArtworks($collection) {
      $queryConditions = ['-Contient la notice::' . $collection];

      $queryPrintouts = [
        '#',
        'Titre',
        'Image principale',
        'Auteur',
        'Coordonnées',
        'Nature',
        'Date d\'inauguration',
        'Date de modification'
      ];

      $queryParameters = [
        'link' => 1
      ];

      return self::query2($queryConditions, $queryPrintouts, $queryParameters);
    }

    protected static function convertArtworks($data) {
      $artworks = [];
      $ids = [];

      for ($i = 0; $i < sizeof($data); $i++) {
        $article = $data[$i]->fullurl;
        if (is_null($data[$i]->exists))
          $article = str_replace('http://publicartmuseum.net/wiki/', 'http://publicartmuseum.net/wiki/Sp%C3%A9cial:EditArtwork/', $article);

        $artwork = [
          'article' => $article,
          'title' => '',
          'artists' => [],
          'image' => '',
          'lat' => 0,
          'lon' => 0,
          'nature' => 'pérenne',
          'date' => null,
          'modification' => null
        ];

        for ($j = 0; $j < sizeof($data[$i]->printouts); $j++) {
          switch ($data[$i]->printouts[$j]->label) {
            case 'Titre':
              $artwork['title'] = $data[$i]->printouts[$j]->{'0'};
              break;

            case 'Auteur':
              $name = $data[$i]->printouts[$j]->{'0'}->fulltext;
              $url = $data[$i]->printouts[$j]->{'0'}->fullurl;
              if (preg_match('/^[qQ][0-9]+$/', $name)) {
                array_push($ids, $name);
                $url = 'http://publicartmuseum.net/wiki/Sp%C3%A9cial:WikidataArtist/' . $name;
              } else
              if (is_null($data[$i]->printouts[$j]->{'0'}->exists)) {
                $url = 'http://publicartmuseum.net/wiki/Sp%C3%A9cial:EditArtist/' . $name;
              }
              array_push($artwork['artists'], [
                'name' => $name,
                'url' => $url
              ]);
              break;

            case 'Coordonnées':
              $artwork['lat'] = floatval($data[$i]->printouts[$j]->{'0'}->lat);
              $artwork['lon'] = floatval($data[$i]->printouts[$j]->{'0'}->lon);
              break;

            case 'Nature':
              $artwork['nature'] = $data[$i]->printouts[$j]->{'0'};
              break;

            case 'Date d\'inauguration':
              $artwork['date'] = gmdate("Y", intval($data[$i]->printouts[$j]->{'0'}));
              break;
            
            case 'Date de modification':
              $artwork['modification'] = intval($data[$i]->printouts[$j]->{'0'});
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
     * Recherche les images
     *
     * @param {Array} $images - Tableau contenant les images
     * @return {Array} Tableau d'entrée mis à jour
     */
    protected static function convertImages($images) {
      for ($i = 0; $i < sizeof($images); $i++) {
        $width = 315;
        if ($images[$i]['origin'] === 'commons') {
          $data = API::getImageWD($images[$i]['value'], $width);
        } else {
          $data = API::getImageAM($images[$i]['value'], $width);
        }
        foreach ($data->query->pages as $page) {
          $images[$i]['url'] = $page->imageinfo[0]->descriptionurl;
          $images[$i]['thumbnail'] = $page->imageinfo[0]->thumburl;
        }
      }

      return $images;
    }

    /**
     * Retourne les données d'une collection
     *
     * @return {Object} Œuvres
     */
    public static function getData($payload) {
      $collection = self::getCollection($payload['collection']);

      if (!is_null($collection)) {
        if (in_array('image', $payload['props']) && !is_null($collection['data']['visuel']) && sizeof($collection['data']['visuel']['value']) > 0) {
          $collection['data']['visuel']['value'] = self::convertImages($collection['data']['visuel']['value']);
        }

        if (in_array('artworks', $payload['props'])) {
          $artworks = self::getArtworks($payload['collection']);
          $collection['data']['artworks'] = self::convertArtworks($artworks);
        }
      }

      return $collection;
    }

  }
}
