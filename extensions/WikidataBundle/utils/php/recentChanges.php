<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class RecentChangesDisplay {
  public static function get_image($image, $width=320) {
    return Api::call_api(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => COMMONS_FILE_PREFIX . $image
    ), 'Commons');
  }

  public static function get_image_am($image, $width=320) {
    return Api::call_api(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => ATLASMUSEUM_FILE_PREFIX . $image
    ), 'atlasmuseum');
  }

  public static function renderRecentChanges($param = array(), $collection = null, $limit = 4) {    
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );

    $query = '[[Category:Notices d\'œuvre]]|?Image principale|sort=Modification date|limit=' . $limit . '|order=desc';
    if (!is_null($collection)) {
      $query = '[[-Contient la notice::' . $collection . ']]|?Image principale|sort=Modification date|limit=' . $limit . '|order=desc';
      var_dump($query);
    }

    $data = Api::call_api(array(
      'action' => 'ask',
      'query' => $query
    ), 'atlasmuseum');

    if (!is_null($collection))
      var_dump($data);

    if(!isset($data->query) || !isset($data->query->results) || sizeof($data->query->results) == 0)
      return '';
    
    $artworks = [];

    foreach ($data->query->results as $result) {
      $artwork = [];
      $artwork['title'] = $result->fulltext;
      $artwork['url'] = $result->fullurl;
      $image = MISSING_IMAGE_FILE;
      $imageUrl = MISSING_IMAGE_THUMB;
      if (isset($result->printouts[0]->{0})) {
        $image = $result->printouts[0]->{0};
        if (preg_match('/^Commons:/i', $image)) {
          $imageName = substr($image, 8);
          $tmp = self::get_image($imageName, 420);
          foreach($tmp->query->pages as $img)
            $imageUrl = $img->imageinfo[0]->thumburl;
        } else {
          $tmp = self::get_image_am($image, 420);
          foreach($tmp->query->pages as $img)
            $imageUrl = $img->imageinfo[0]->thumburl;
        }
      }
      $artwork['image'] = $imageUrl;

      array_push($artworks, $artwork);
    }

    ob_start();

    ?>
    <ul>
      <?php
        foreach($artworks as $artwork) {
          ?><li>
              <div class="thumb tright">
                <div class="thumbinner">
                  <a href="<?php print $artwork['url']; ?>" title="<?php print $artwork['title']; ?>">
                    <img alt="<?php print $artwork['title']; ?>" src="<?php print $artwork['image']; ?>" style="width:auto;max-width:205px;max-height:134px;">
                  </a>
                  <div class="thumbcaption"><?php print $artwork['title']; ?></div>
                </div>
              </div>
            </li><?php
        }
      ?>
    </ul>
    <?php

    $contents = ob_get_contents();
    ob_end_clean();

    return preg_replace("/\r|\n/", "", $contents);

  }

}
