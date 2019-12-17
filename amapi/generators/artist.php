<?php
/*****************************************************************************
 * Artist.php
 *
 * Récupère les données d'un artiste
 *****************************************************************************/

if (!class_exists('Artist')) {

  require_once('includes/api.php');

  class Artist {
    /**
     * Valide les paramètres
     *
     * @return {Object} Tableau contenant le résultat de la validation
     */
    public static function validateQuery() {
      $article = getRequestParameter('article');
      if (is_null($article))
        return [
          'success' => 0,
          'error' => [
            'code' => 'no_article',
            'info' => 'No value provided for parameter "article".',
            'status' => 400
          ]
        ];

      $redirect = getRequestParameter('redirect');

      $payload = [
        'article' => str_replace('_', ' ', urldecode($article)),
        'redirect' => !is_null($redirect) && ($redirect === "1" || strtolower($redirect) === "true")
      ];

      return [
        'success' => 1,
        'payload' => $payload
      ];
    }

    protected static function processDate($dateString) {
      return [
        'type' => 'date',
        'value' => [$dateString]
      ];
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
     * Process un claim d'images
     *
     * @param {Object} $claim
     * @return {Array} 
     */
    protected static function processImagesWD($claim) {
      $data = [];

      array_push($data, [
        'origin' => 'commons',
        'value' => $claim[0]->mainsnak->datavalue->value,
        'url' => '',
        'thumbnail' => ''
      ]);

      return [
        'type' => 'image',
        'value' => $data
      ];
    }

    /**
     * Process une ligne d'items
     *
     * @param {string} $itemString
     * @return {Array} 
     */
    protected static function processItems($itemString) {
      $items = preg_split('/[\s]*;[\s]*/', $itemString);
      $data = [];
      foreach ($items as $item) {
        if (preg_match('/^[qQ][0-9]+$/', $item)) {
          array_push($data, [
            'label' => $item,
            'article' => $item,
            'origin' => 'wikidata'
          ]);
        } else {
          array_push($data, [
            'label' => $item,
            'article' => $item,
            'origin' => 'atlasmuseum'
          ]);
        }
      }

      return [
        'type' => 'item',
        'value' => $data
      ];
    }

    /**
     * Process une ligne d'items
     *
     * @param {string} $itemString
     * @return {Array} 
     */
    protected static function processItemsWD($claim) {
      $data = [];

      for ($i = 0; $i < sizeof($claim); $i++) {
        $id = $claim[$i]->mainsnak->datavalue->value->id;
        array_push($data, [
          'label' => $id,
          'article' => $id,
          'origin' => 'wikidata'
        ]);
      }

      return [
        'type' => 'item',
        'value' => $data
      ];
    }

    /**
     * Process une ligne d'items
     *
     * @param {string} $itemString
     * @return {Array} 
     */
    protected static function processDatesWD($claim) {
      $data = [];

      for ($i = 0; $i < sizeof($claim) && $i < 1; $i++) {
        $time = $claim[$i]->mainsnak->datavalue->value->time;
        if (substr($time, 0, 1) === '+') {
          $time = preg_replace('/^\+[0]*([0-9]+)-.*$/', '$1', $time);
        } else {
          $time = preg_replace('/^-[0]*([0-9]+)-.*$/', '-$1', $time);
        }
        array_push($data, $time);
      }

      return [
        'type' => 'text',
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
     * Retourne le contenu d'un artiste sur atlasmuseum
     *
     * @param {string} $revision - contenu de l'article
     * @return {Object} Contenu de l'artiste
     */
    protected static function getArtistAMContent($revision) {
      $content = explode("\n", $revision);
      $data = [];
      $lineIndex = 0;

      while($lineIndex < sizeof($content) && strpos($content[$lineIndex], '/>') === false) {
        $key = strtolower(preg_replace('/^[\s]*\|[\s]*([^=]+)[\s]*=.*$/', '$1', $content[$lineIndex]));
        $value = preg_replace('/^[^=]+=[\s]*(.*)$/', '$1', $content[$lineIndex]);
        $value = str_replace('&quot;', '"', $value);
        $value = str_replace('\n', '<br />', $value);

        switch ($key) {
          case 'dateofbirth':
          case 'deathdate':
            $data[$key] = self::processDate($value);
            break;

          case 'thumbnail':
            $data[$key] = self::processImages($value);
            break;
          
          case 'nom':
          case 'prenom':
          case 'birthplace':
          case 'deathplace':
          case 'nationality':
          case 'movement':
            $data[$key] = self::processItems($value);
            break;

          case '{{artiste':
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
     * Retourne le contenu d'un artiste sur Wikidata
     *
     * @param {Object} $entity - contenu de l'item
     * @return {Object} Contenu de l'artiste
     */
    protected static function getArtistWDContent($entity) {
      $data = [];

      // Identifiant Wikidata
      $data['wikidata'] = self::processText($entity->id);

      // Titre de l'artiste : label français ou anglais
      if (!is_null($entity->labels)) {
        if (!is_null($entity->labels->fr))
          $data['titre'] = self::processText($entity->labels->fr->value);
        else
        if (!is_null($entity->labels->en))
          $data['titre'] = self::processText($entity->labels->en->value);
        else
          $data['titre'] = self::processText($entity->id);
      }

      // Claims
      if (!is_null($entity->claims)) {
        $claims = $entity->claims;
        //-- Pays de nationalité
        if (!is_null($claims->P27)) {
          $data['nationality'] = self::processItemsWD($claims->P27);
        }
        //-- Nom
        if (!is_null($claims->P374)) {
          $data['nom'] = self::processItemsWD($claims->P374);
        }
        //-- Prénom
        if (!is_null($claims->P735)) {
          $data['prenom'] = self::processItemsWD($claims->P735);
        }
        //-- Lieu de naissance
        if (!is_null($claims->P19)) {
          $data['birthplace'] = self::processItemsWD($claims->P19);
        }
        //-- Lieu de décès
        if (!is_null($claims->P20)) {
          $data['deathplace'] = self::processItemsWD($claims->P20);
        }
        //-- Mouvement
        if (!is_null($claims->P135)) {
          $data['movement'] = self::processItemsWD($claims->P135);
        }
        //-- Date de naissance
        if (!is_null($claims->P569)) {
          $data['dateofbirth'] = self::processDatesWD($claims->P569);
        }
        //-- Date de décès
        if (!is_null($claims->P570)) {
          $data['deathdate'] = self::processDatesWD($claims->P570);
        }
        //-- Image
        if (!is_null($claims->P18)) {
          $data['thumbnail'] = self::processImagesWD($claims->P18);
        }
      }

      return $data;
    }

    /**
     * Retourne le contenu d'un artiste sur Wikidata
     *
     * @param {string} $article - id Wikidata
     * @return {Object} Contenu de l'artiste
     */
    protected static function getArtistWD($article) {
      $result = API::callApi([
        'action' => 'wbgetentities',
        'ids' => $article
      ]);

      if (is_null($result->entities) || is_null($result->entities->{$article}))
        // L'item n'a pas été trouvé : retourne un tableau vide
        return [];

      $data = self::getArtistWDContent($result->entities->{$article});

      return [
        'article' => $article,
        'origin' => 'wikidata',
        'data' => $data
      ];
    }

    /**
     * Retourne le contenu d'un artiste sur atlasmuseum
     *
     * @param {string} $article - article atlasmuseum
     * @return {Object} Contenu de l'artiste
     */
    protected static function getArtistAM($article) {
      $data = API::callApi([
        'action' => 'query',
        'prop' => 'revisions',
        'rvprop' => 'content',
        'titles' => $article
      ], 'atlasmuseum');

      if (!is_null($data->query->pages->{'-1'}))
        // L'article n'a pas été trouvé : retourne un tableau vide
        return [];

      foreach ($data->query->pages as $Artist) {
        $data = self::getArtistAMContent($Artist->revisions[0]->{'*'});
      }

      return [
        'article' => $article,
        'origin' => 'atlasmuseum',
        'data' => $data
      ];
    }

    /**
     * Recherche les images des éléments sur Wikidata
     *
     * @param {Object} $artists - Objet contenant les données de l'artiste
     * @return {Object} Objet d'entrée mis à jour
     */
    protected static function convertImages($artists) {
      // Parse l'objet en entrée afin d'en récupérer toutes les images à traiter
      foreach ($artists['data'] as $key => $element) {
        if ($element['type'] === 'image') {
          // Taille d'image, en fonction du champ
          $width = 420;
          for ($i = 0; $i < sizeof($element['value']); $i++) {
            if ($element['value'][$i]['origin'] === 'commons') {
              $data = API::getImageWD($element['value'][$i]['value'], $width);
            } else {
              $data = API::getImageAM($element['value'][$i]['value'], $width);
            }
            foreach ($data->query->pages as $page) {
              $artists['data'][$key]['value'][$i]['url'] = $page->imageinfo[0]->descriptionurl;
              $artists['data'][$key]['value'][$i]['thumbnail'] = $page->imageinfo[0]->thumburl;
            }
          }
        }
      }

      return $artists;
    }

    /**
     * Recherche les labels des éléments sur Wikidata
     *
     * @param {Object} $artists - Objet contenant les données de l'œuvre
     * @return {Object} Objet d'entrée mis à jour
     */
    protected static function convertItems($artists) {
      // Parse l'objet en entrée afin d'en récupérer tous les ids Wikidata à traiter
      $ids = [];
      foreach ($artists['data'] as $element) {
        if ($element['type'] === 'item') {
          for ($i = 0; $i < sizeof($element['value']); $i++) {
            if ($element['value'][$i]['origin'] === 'wikidata')
              array_push($ids, $element['value'][$i]['article']);
          }
        }
      }

      if (sizeof($ids) > 0) {
        $ids = array_unique($ids);
        // Récupère les labels associés
        $labels = API::getLabels($ids);

        if (sizeof($labels) > 0) {
          // Re-parse l'objet en entrée afin de mettre à jour les labels
          foreach ($artists['data'] as $key => $element) {
            if ($element['type'] === 'item') {
              for ($i = 0; $i < sizeof($element['value']); $i++) {
                if ($element['value'][$i]['origin'] === 'wikidata') {
                  if (array_key_exists($element['value'][$i]['article'], $labels))
                    $artists['data'][$key]['value'][$i]['label'] = $labels[$element['value'][$i]['article']];
                }
              }
            }
          }
        }
      }

      return $artists;
    }

    protected static function findArticle($id) {
      $parameters = [
        'action' => 'ask',
        'query' => '[[Catégorie:Artistes]][[Wikidata::' . $id . ']]'
      ];
      $data = API::callApi($parameters, 'atlasmuseum');
      if (sizeof($data->query->results) > 0)
        return $data->query->results[0]->fulltext;
      else
        return '';
    }

    /**
     * Retourne le contenu d'un artiste
     *
     * @param {string} $article - Nom de l'article atlasmuseum ou de l'id Wikidata à retourner
     * @param {string} $redirect - Si Wikidata, rediriger vers un éventuel article atlasmuseum
     * @return {Object} Contenu de l'artiste
     */
    public static function getArtist($payload) {
      $artists = [];

      if (preg_match('/^[qQ][0-9]+$/', $payload['article'])) {
        if ($payload['redirect']) {
          $amArticle = self::findArticle($payload['article']);
          if ($amArticle != '') {
            $artists = self::getArtistAM($amArticle);
          } else {
            $artists = self::getArtistWD($payload['article']);
          }
        } else
          $artists = self::getArtistWD($payload['article']);
      } else {
        $artists = self::getArtistAM($payload['article']);
      }

      $artists = self::convertItems($artists);
      $artists = self::convertImages($artists);

      return $artists;
    }

  }
}
