<?php
/*****************************************************************************
 * Image.php
 *
 * Récupère les données d'une image
 *****************************************************************************/

if (!class_exists('Image')) {

  require_once('includes/api.php');

  class Image {
    /**
     * Valide les paramètres
     *
     * @return {Object} Tableau contenant le résultat de la validation
     */
    public static function validateQuery() {
      $image = getRequestParameter('image');
      if (is_null($image))
        return [
          'success' => 0,
          'error' => [
            'code' => 'no_image',
            'info' => 'No value provided for parameter "image".',
            'status' => 400
          ]
        ];

      $origin = getRequestParameter('origin');
      if (!is_null($origin)) {
        $origin = strtolower($origin);
        if ($origin !== 'atlasmuseum' && $origin !== 'commons')
          return [
            'success' => 0,
            'error' => [
              'code' => 'unknown_origin',
              'info' => 'Unknown  value provided for parameter "origin": ' . $origin,
              'status' => 400
            ]
          ];
      } else
        $origin = 'atlasmuseum';

      $width = getRequestParameter('width');
      $legend = strtolower(getRequestParameter('legend'));

      $image = str_replace('_', ' ', urldecode($image));

      if (is_null($origin))
        $origin = 'atlasmuseum';
      else
        $origin = strtolower($origin);

      if (is_null($width) || !is_numeric($width))
        $width = 420;
      else
        $width = intval($width);
      
      $legend = (!is_null($legend) && $legend === 'true');

      return [
        'success' => 1,
        'payload' => [
          'image' => $image,
          'origin' => $origin,
          'width' => $width,
          'legend' => $legend
        ]
      ];
    }

    protected static function getImageAM($image, $width, $legend) {
      $data = [
        'file' => MISSING_IMAGE_FILE,
        'url' => MISSING_IMAGE_URL,
        'thumbnail' => MISSING_IMAGE_THUMBNAIL,
        'legend' => ''
      ];

      $result = API::callApi([
        'action' => 'query',
        'prop' => 'imageinfo',
        'iiprop' => 'url',
        'iiurlwidth' => $width,
        'titles' => 'File:' . $image
      ], 'atlasmuseum');

      if (is_null($result->query->pages->{'-1'})) {
        foreach ($result->query->pages as $page) {
          $data['file'] = 'Fichier' . $image;
          $data['url'] = $page->imageinfo[0]->descriptionurl;
          $data['thumbnail'] = (!is_null($page->imageinfo[0]->thumburl) ? $page->imageinfo[0]->thumburl : $page->imageinfo[0]->url);
        }

        if ($legend) {
          $legend = Api::callApi([
            'action' => 'query',
            'prop' => 'revisions',
            'rvprop' => 'content',
            'titles' => 'Fichier:' . $image
          ], 'atlasmuseum');

          if (is_null($legend->query->pages->{'-1'})) {
            foreach ($legend->query->pages as $l) {
              $data['legend'] = API::convertToWikiText($l->revisions[0]->{'*'});
            }
          }
        }
      }

      return $data;
    }

    protected static function getImageCommons($image, $width) {
      $data = [
        'file' => MISSING_IMAGE_FILE,
        'url' => MISSING_IMAGE_URL,
        'thumbnail' => MISSING_IMAGE_THUMBNAIL,
        'legend' => ''
      ];

      $result = API::callApi([
        'action' => 'query',
        'prop' => 'imageinfo',
        'iiprop' => 'url',
        'iiurlwidth' => $width,
        'titles' => 'File:' . $image
      ]);

      if (!is_null($result->query->pages->{'-1'}) && !is_null($result->query->pages->{'-1'}->imageinfo)) {
        $data['file'] = $result->query->pages->{'-1'}->title;
        $data['url'] = $result->query->pages->{'-1'}->imageinfo[0]->descriptionurl;
        $data['thumbnail'] = $result->query->pages->{'-1'}->imageinfo[0]->thumburl;
      }

      return $data;
    }

    /**
     * Retourne le contenu d'une image
     *
     * @param {string} $image - Nom de l'image
     * @param {string} $origin - Origine de l'image ("atlasmuseum" ou "commons")
     * @param {number} $width - Taille de la vignette à retourner
     * @param {bool} $legend - Faut-il inclure la légende ?
     * @return {Object} Contenu de l'image
     */
    public static function getData($payload) {
      // image, $origin = 'atlasmuseum', $width = 420, $legend = false) {
      $images = [];

      if ($payload['origin'] === 'atlasmuseum') {
        $images = self::getImageAM($payload['image'], $payload['width'], $payload['legend']);
      } else
      if ($payload['origin'] === 'commons') {
        $images = self::getImageCommons($payload['image'], $payload['width']);
      }

      return $images;
    }

  }
}
