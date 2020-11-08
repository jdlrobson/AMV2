<?php
/*****************************************************************************
 * Artwork.php
 *
 * Récupère les données d'une Artwork
 *****************************************************************************/

if (!class_exists('Artwork')) {

  require_once('includes/api.php');

  class Artwork {

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

    /**
     * Process une ligne de coordonnées
     *
     * @param {string} $coordinateString
     * @return {Array} 
     */
    protected static function processCoordinates($coordinateString) {
      $coords = preg_split('/[\s]*,[\s]*/', $coordinateString);
      return [
        'type' => 'coordinates',
        'value' => [[
          'lat' => floatval($coords[0]),
          'lon' => floatval($coords[1])
        ]]
      ];
    }

    /**
     * Process un claim de coordonnées
     *
     * @param {Object} $claim
     * @return {Array} 
     */
    protected static function processCoordinatesWD($claim) {
      return [
        'type' => 'coordinates',
        'value' => [[
          'lat' => $claim[0]->mainsnak->datavalue->value->latitude,
          'lon' => $claim[0]->mainsnak->datavalue->value->longitude
        ]]
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
     * Retourne le contenu d'une œuvre sur atlasmuseum
     *
     * @param {string} $revision - contenu de l'article
     * @return {Object} Contenu de l'œuvre
     */
    protected static function getArtworkAMContent($revision) {
      $content = explode("\n", $revision);
      $data = [];
      $lineIndex = 0;

      while($lineIndex < sizeof($content) && strpos($content[$lineIndex], '/>') === false) {
        $key = strtolower(preg_replace('/^[\s]*\|[\s]*([^=]+)[\s]*=.*$/', '$1', $content[$lineIndex]));
        $value = preg_replace('/^[^=]+=[\s]*(.*)$/', '$1', $content[$lineIndex]);
        $value = str_replace('&quot;', '"', $value);
        $value = str_replace('\n', '<br />', $value);

        switch ($key) {
          case 'site_coordonnees':
            $data[$key] = self::processCoordinates($value);
            break;

          case 'inauguration':
            $data[$key] = self::processDate($value);
            break;

          case 'image_principale':
          case 'image_galerie_construction':
          case 'image_galerie_autre':
            $data[$key] = self::processImages($value);
            break;
          
          case 'mouvement_artistes':
          case 'type_art':
          case 'couleur':
          case 'materiaux':
          case 'forme':
          case 'commanditaires':
          case 'commissaires':
          case 'site_nom':
          case 'site_ville':
          case 'site_pays':
          case 'site_pmr':
          case 'artiste':
            $data[$key] = self::processItems($value);
            break;

          default:
            $data[$key] = self::processText($value);
        }

        $lineIndex++;
      }

      return $data;
    }

    /**
     * Retourne le contenu d'une œuvre sur Wikidata
     *
     * @param {Object} $entity - contenu de l'item
     * @return {Object} Contenu de l'œuvre
     */
    protected static function getArtworkWDContent($entity) {
      $data = [];

      // Identifiant Wikidata
      $data['wikidata'] = self::processText($entity->id);

      // Titre de l'œuvre : label français ou anglais
      if (!is_null($entity->labels)) {
        if (!is_null($entity->labels->fr))
          $data['titre'] = self::processText($entity->labels->fr->value);
        else
        if (!is_null($entity->labels->en))
          $data['titre'] = self::processText($entity->labels->en->value);
        else
          $data['titre'] = self::processText($entity->id);
      }

      // Nature : pérenne
      $data['nature'] = self::processText('pérenne');

      // Claims
      if (!is_null($entity->claims)) {
        $claims = $entity->claims;
        // Coordonnées
        if (!is_null($claims->P625)) {
          $data['site_coordonnees'] = self::processCoordinatesWD($claims->P625);
        }
        //-- Nature de l'élément
        if (!is_null($claims->P31)) {
          $data['type_art'] = self::processItemsWD($claims->P31);
        }
        //-- Créateurs
        if (!is_null($claims->P170)) {
          $data['artiste'] = self::processItemsWD($claims->P170);
        }
        //-- Mouvement
        if (!is_null($claims->P135)) {
          $data['mouvement_artistes'] = self::processItemsWD($claims->P135);
        }
        //-- Couleurs
        if (!is_null($claims->P462)) {
          $data['couleur'] = self::processItemsWD($claims->P462);
        }
        //-- Matériaux
        if (!is_null($claims->P186)) {
          $data['materiaux'] = self::processItemsWD($claims->P186);
        }
        //-- Commanditaires
        if (!is_null($claims->P88)) {
          $data['commanditaires'] = self::processItemsWD($claims->P88);
        }
        //-- Commissaires
        if (!is_null($claims->P1640)) {
          $data['commissaires'] = self::processItemsWD($claims->P1640);
        }
        //-- Lieux
        if (!is_null($claims->P276)) {
          $data['site_nom'] = self::processItemsWD($claims->P276);
        }
        //-- Ville
        if (!is_null($claims->P131)) {
          $data['site_ville'] = self::processItemsWD($claims->P131);
        }
        //-- Pays
        if (!is_null($claims->P17)) {
          $data['site_pays'] = self::processItemsWD($claims->P17);
        }
        //-- PMR
        if (!is_null($claims->P2846)) {
          $data['site_pmr'] = self::processItemsWD($claims->P2846);
        }
        //-- Sujet représenté
        if (!is_null($claims->P921)) {
          $data['forme'] = self::processItemsWD($claims->P921);
        }
        if (!is_null($claims->P180)) {
          $data['forme'] = self::processItemsWD($claims->P180);
        }
        //-- Image
        if (!is_null($claims->P18)) {
          $data['image_principale'] = self::processImagesWD($claims->P18);
        }
      }

      return $data;
    }

    /**
     * Retourne le contenu d'une œuvre sur Wikidata
     *
     * @param {string} $article - id Wikidata
     * @return {Object} Contenu de l'œuvre
     */
    protected static function getArtworkWD($article) {
      $result = API::callApi([
        'action' => 'wbgetentities',
        'ids' => $article
      ]);

      if (is_null($result->entities) || is_null($result->entities->{$article}))
        // L'item n'a pas été trouvé : retourne un tableau vide
        return [];

      $data = self::getArtworkWDContent($result->entities->{$article});

      return [
        'article' => $article,
        'title' => $article,
        'origin' => 'wikidata',
        'data' => $data
      ];
    }

    /**
     * Retourne le contenu d'une œuvre sur atlasmuseum
     *
     * @param {string} $article - article atlasmuseum
     * @return {Object} Contenu de l'œuvre
     */
    protected static function getArtworkAM($article) {
      $data = API::callApi([
        'action' => 'query',
        'prop' => 'revisions',
        'rvprop' => 'content',
        'titles' => $article
      ], 'atlasmuseum');

      if (!is_null($data->query->pages->{'-1'}))
        // L'article n'a pas été trouvé : retourne un tableau vide
        return [];

      foreach ($data->query->pages as $artwork) {
        $data = self::getArtworkAMContent($artwork->revisions[0]->{'*'});
      }

      return [
        'article' => $article,
        'title' => $article,
        'origin' => 'atlasmuseum',
        'data' => $data
      ];
    }

    /**
     * Recherche les images des éléments sur Wikidata
     *
     * @param {Object} $artworks - Objet contenant les données de l'œuvre
     * @return {Object} Objet d'entrée mis à jour
     */
    protected static function convertImages($artworks) {
      // Parse l'objet en entrée afin d'en récupérer toutes les images à traiter
      foreach ($artworks['data'] as $key => $element) {
        if ($element['type'] === 'image') {
          // Taille d'image, en fonction du champ
          $width = ($key === 'image_principale' ? 420 : 192);
          for ($i = 0; $i < sizeof($element['value']); $i++) {
            if ($element['value'][$i]['origin'] === 'commons') {
              $data = API::getImageWD($element['value'][$i]['value'], $width);
            } else {
              $data = API::getImageAM($element['value'][$i]['value'], $width);
            }
            foreach ($data->query->pages as $page) {
              $artworks['data'][$key]['value'][$i]['url'] = $page->imageinfo[0]->descriptionurl;
              $artworks['data'][$key]['value'][$i]['thumbnail'] = $page->imageinfo[0]->thumburl;
            }
          }
        }
      }

      return $artworks;
    }

    /**
     * Recherche les labels des éléments sur Wikidata
     *
     * @param {Object} $artworks - Objet contenant les données de l'œuvre
     * @return {Object} Objet d'entrée mis à jour
     */
    protected static function convertItems($artworks) {
      // Parse l'objet en entrée afin d'en récupérer tous les ids Wikidata à traiter
      $ids = [];
      if ($artworks['origin'] === 'wikidata')
        array_push($ids, $artworks['article']);
      foreach ($artworks['data'] as $element) {
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
          if ($artworks['origin'] === 'wikidata') {
            if (array_key_exists($artworks['article'], $labels))
              $artworks['title'] = $labels[$artworks['article']];
          }
          foreach ($artworks['data'] as $key => $element) {
            if ($element['type'] === 'item') {
              for ($i = 0; $i < sizeof($element['value']); $i++) {
                if ($element['value'][$i]['origin'] === 'wikidata') {
                  if (array_key_exists($element['value'][$i]['article'], $labels))
                    $artworks['data'][$key]['value'][$i]['label'] = $labels[$element['value'][$i]['article']];
                }
              }
            }
          }
        }
      }

      return $artworks;
    }

    protected static function findArticle($id) {
      $parameters = [
        'action' => 'ask',
        'query' => '[[Catégorie:Notices d\'œuvre]][[Wikidata::' . $id . ']]'
      ];
      $data = API::callApi($parameters, 'atlasmuseum');
      if (sizeof($data->query->results) > 0)
        return $data->query->results[0]->fulltext;
      else
        return '';
    }

    /**
     * Retourne le contenu d'une œuvre
     *
     * @param {string} $article - Nom de l'article atlasmuseum ou de l'id Wikidata à retourner
     * @param {string} $redirect - Si Wikidata, rediriger vers un éventuel article atlasmuseum
     * @return {Object} Contenu de l'œuvre
     */
    public static function getData($payload) {
      $artworks = [];

      if (preg_match('/^[qQ][0-9]+$/', $payload['article'])) {
        if ($payload['redirect']) {
          $amArticle = self::findArticle($payload['article']);
          if ($amArticle != '') {
            $artworks = self::getArtworkAM($amArticle);
          } else {
            $artworks = self::getArtworkWD($payload['article']);
          }
        } else
          $artworks = self::getArtworkWD($payload['article']);
      } else {
        $artworks = self::getArtworkAM($payload['article']);
      }

      $artworks = self::convertItems($artworks);
      //$artworks = self::convertImages($artworks);

      return $artworks;
    }

  }
}
