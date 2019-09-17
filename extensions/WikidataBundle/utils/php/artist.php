<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'updateDB.php');

class Artist {

  public static function get_props($id) {
    return Api::call_api(array(
      'action' => 'wbgetentities',
      'ids' => $id
    ));
  }

  public static function get_labels($ids) {

    $labels = [];
    $split_ids = array_chunk($ids, 50);

    for ($i=0; $i<sizeof($split_ids); $i++) {
      $labels_data = Api::call_api(array(
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

  public static function get_ids($data) {
    $ids = [];

    foreach ($data->entities as $q)
      foreach ($q->claims as $property=>$value)
        foreach ($value as $claim)
          if ($claim->mainsnak->datatype == 'wikibase-item' && !is_null($claim->mainsnak->datavalue->value->id))
            array_push($ids, $claim->mainsnak->datavalue->value->id);

    return $ids;
  }

  public static function get_image_commons($image, $width=320) {
    return Api::call_api(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => 'File:'.$image
    ), 'Commons');
  }

  public static function get_image_am($image, $width=320) {
    $image = str_replace(' ', '_', $image);
    return Api::call_api(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => 'File:'.$image
    ), 'atlasmuseum');
  }

  public static function render_claim_wd_from_artwork($claims, $property, $labels, $title, $title_plural='') {

    if (isset($claims->{$property})) {
      $data = [];

      foreach ($claims->{$property} as $value) {
        switch ($value->mainsnak->datatype) {
          case 'time':
            $date = $value->mainsnak->datavalue->value->time;
            $date = preg_replace('/-.*$/', '', $date);
            $date = preg_replace('/\+/', '', $date);
            array_push($data, $date);
            break;
          case 'wikibase-item':
            $id = $value->mainsnak->datavalue->value->id;
            if (isset($labels[$id]))
              array_push($data, $labels[$id]);
            else
              array_push($data, $id);
            break;
        }
      }

      if (sizeof($data)>0)
        print '<tr><th>' . 
          ($title_plural != '' ? (sizeof($data)<=1 ? $title : $title_plural) : $title) . 
          '</th><td>' .
          join($data, ', ') .
          '</td></tr>';
    }
  }

  public static function get_label_from_id($id) {
    $labels_data = Api::call_api(array(
      'action' => 'wbgetentities',
      'props' => 'labels',
      'ids' => $id,
    ));

    if (isset($labels_data->entities) && isset($labels_data->entities->{$id}) && isset($labels_data->entities->{$id}->labels)) {
      if (isset($labels_data->entities->{$id}->labels->fr))
        return $labels_data->entities->{$id}->labels->fr->value;
      else
      if (isset($labels_data->entities->{$id}->labels->en))
        return $labels_data->entities->{$id}->labels->en->value;
      else
        return $id;
    } else
      return $id;
  }

  public static function render_claim_am_from_artwork($param, $name, $title, $two_lines = false) {
    if (isset($param[$name])) {
      $value = $param[$name];
      if (preg_match('/^[qQ][0-9]+$/', $param[$name])) {
        $value = self::get_label_from_id($param[$name]);
      }

      if ($two_lines) {
        print '<tr><td colspan="2"><b>' . $title . '</b><br />' . $value . '</td></tr>';
      } else {
        print '<tr><th>' . $title . '</th><td>' . $value . '</td></tr>';
      }
    }
  }

  public static function convert_artist($artist) {

    $article = get_artist($artist);
    if ($article != '')
      $artist = $article;

    $output = Api::call_api([
      "action"	=> "query",
      "prop"		=> "revisions",
      "rvlimit" => 1,
      "rvprop"  => "content",
      "titles"  => $artist,
      "continue" => ''
    ], 'atlasmuseum');

    $error = false;
  
    $values = [];

    if (is_null($output->query->pages->{'-1'})) {
      foreach ($output->query->pages as $page) {
        $content = $page->revisions[0]->{'*'};
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
          if ($line != '{{Artiste' && $line != '<Artist' && $line != '}}') {
            if (preg_match('/#^[\s]*\|[\s]*[^=]*[\s]*=[\s]*/', $line)) {
              $error  =true;
              break;
            } else
            if (preg_match('/^(.*)=(.*)$/', $line)) {
              $parameter = preg_replace('/^(.*)=.*$/', '$1', $line);
              $parameter = strtolower(str_replace(' ', '_', $parameter));
              $parameter = str_replace('é', 'e', $parameter);
              $value = preg_replace('/^.*=\"(.*)\"$/', '$1', $line);
              $value = str_replace('"', '\"', $value);

              if (!is_null($multiple) && array_key_exists($parameter, $multiple)) {
                $value = str_replace(';', '\;', $value);
                $value = str_replace(', ', ';', $value);
              }
    
              $values[$parameter] = $value;
            }
            else {
              $parameter = preg_replace('/^[\s]*\|[\s]*([^=]*)[\s]*=[\s]*.*$/', '$1', $line);
              $parameter = strtolower(str_replace(' ', '_', $parameter));
              $parameter = str_replace('é', 'e', $parameter);
              $value = preg_replace('/^[\s]*\|[\s]*[^=]*[\s]*=[\s]*(.*)$/', '$1', $line);
              $value = str_replace('"', '\"', $value);
    
              if (!is_null($multiple) && array_key_exists($parameter, $multiple)) {
                $value = str_replace(';', '\;', $value);
                $value = str_replace(', ', ';', $value);
              }
    
              $values[$parameter] = $value;
            }
          }
        }
      }
      $values['found'] = true;
    } else {
      $values['found'] = false;
    }

    $values['article'] = $article;

    return $values;
  }

  public static function render_artists_for_artwork_am($name, $artist) {
    $image_thumb = MISSING_IMAGE_FILE;
    $image_url = MISSING_IMAGE_LINK;

    if (isset($artist['thumbnail'])) {
      if (preg_match('/^Commons:/i', $artist['thumbnail'])) {
        $image_name = substr($artist['thumbnail'], 8);
        $image_url = COMMONS_PATH . COMMONS_FILE_PREFIX + $image_name;
        $tmp = self::get_image_commons($image_name, 420);
        foreach($tmp->query->pages as $image)
          $image_thumb = $image->imageinfo[0]->thumburl;
      } else {
        $image_url = ATLASMUSEUM_PATH . ATLASMUSEUM_FILE_PREFIX . $artist['thumbnail'];
        $tmp = self::get_image_am($artist['thumbnail'], 420);
        foreach($tmp->query->pages as $image)
          $image_thumb = $image->imageinfo[0]->thumburl;
      }
    }

    $articleLink = 
      ($artist["article"] != '' )
      ? $artistLink = $artist["article"]
      : $artistLink = 'Spécial:WikidataArtist/' . $name;
    if (!is_null($name) && $name != '' && !preg_match('/^[qQ][0-9]+$/', $name))
      $artistLink = $name;

    ?>
      <h3>
        <span class="mw-headline" id="">
          <a href="<?php print ATLASMUSEUM_PATH . $artistLink; ?>" title=""><?php print $artist["article"] != '' ? $artist["article"] : $name; ?></a>
        </span>
      </h3>
      <p style="text-align:center">
        <a href="<?php print $image_url; ?>" class="image">
          <img alt="" src="<?php print ATLASMUSEUM_PATH . $image_thumb; ?>" style="width:auto;max-width224px;max-height:149px" />
        </a>
      </p>
      <table class="wikitable">
        <?php
          self::render_claim_am_from_artwork($artist, 'abstract', '', true);
          self::render_claim_am_from_artwork($artist, 'birthplace', 'Lieu de naissance');
          self::render_claim_am_from_artwork($artist, 'dateofbirth', 'Date de naissance');
          self::render_claim_am_from_artwork($artist, 'deathplace', 'Lieu de décès');
          self::render_claim_am_from_artwork($artist, 'deathdate', 'Date de décès');
          self::render_claim_am_from_artwork($artist, 'nationality', 'Nationalité');
        ?>
      </table>
    <?php
  }

  public static function render_artists_for_artwork($artists_wd, $artists_am) {
    $leftovers = [];
    foreach (explode(';',$artists_am) as $artist)
      if ($artist != '') {
        $data = self::convert_artist($artist);
        if ($data['found']) {
          self::render_artists_for_artwork_am($artist, $data);
        } else {
          array_push($leftovers, $artist);
        }
      }

    if (is_null($artists_am) || $artists_am == '')
    if (!is_null($artists_wd)) {
      foreach ($artists_wd as $artist) {
        $artist_id = $artist->mainsnak->datavalue->value->id;

        // Recherche si l'artiste possède déjà une notice am
        $query = 'SELECT article FROM tmp_artist WHERE wikidata="' . $artist_id . '" LIMIT 1';
        $result = query($query);
        $row = $result->fetch_assoc();

        if (!is_null($row)) {
          $data = self::convert_artist($row['article']);
          self::render_artists_for_artwork_am($row['article'], $data);
        } else {
          $artist_data = self::get_props($artist_id);
          $artist_ids = self::get_ids($artist_data);
          array_push($artist_ids, $artist_id);
          $artist_labels = self::get_labels($artist_ids);

          $image_thumb = MISSING_IMAGE_FILE;
          $image_url = MISSING_IMAGE_LINK;

          if (isset($artist_data->entities->{$artist_id}->claims->P18)) {
            $image_url = 'https://commons.wikimedia.org/wiki/File:' . $artist_data->entities->{$artist_id}->claims->P18[0]->mainsnak->datavalue->value;
            $tmp = self::get_image_commons($artist_data->entities->{$artist_id}->claims->P18[0]->mainsnak->datavalue->value);
            foreach($tmp->query->pages as $image)
              $image_thumb = $image->imageinfo[0]->thumburl;
          }

          ?>
          <h3>
            <span class="mw-headline" id="">
              <a href="<?php print ATLASMUSEUM_PATH . 'Spécial:WikidataArtist/' . $artist_id; ?>" title=""><?php (isset($artist_labels[$artist_id]) ? print $artist_labels[$artist_id] : print $artist_id); ?></a>
            </span>
          </h3>
          <p style="text-align:center">
            <a href="<?php print $image_url; ?>" class="image">
              <img alt="" src="<?php print $image_thumb; ?>" style="width:auto;max-width224px;max-height:149px" />
            </a>
          </p>
          <table class="wikitable">
            <?php
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P19', $artist_labels, 'Lieu de naissance');
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P569', $artist_labels, 'Date de naissance');
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P20', $artist_labels, 'Lieu de décès');
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P570', $artist_labels, 'Date de décès');
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P27', $artist_labels, 'Pays de nationalité');
            ?>
          </table>
          <?php
        }
      }
    }

    if (sizeof($leftovers) > 0) {
      foreach ($leftovers as $artist_id) {
        // Recherche si l'artiste possède déjà une notice am
        $query = 'SELECT article FROM tmp_artist WHERE wikidata="' . $artist_id . '" LIMIT 1';
        $result = query($query);
        $row = $result->fetch_assoc();

        if (!is_null($row)) {
          $data = self::convert_artist($row['article']);
          self::render_artists_for_artwork_am($row['article'], $data);
        } else {
          $artist_data = self::get_props($artist_id);
          $artist_ids = self::get_ids($artist_data);
          array_push($artist_ids, $artist_id);
          $artist_labels = self::get_labels($artist_ids);

          $image_thumb = MISSING_IMAGE_FILE;
          $image_url = MISSING_IMAGE_LINK;

          if (isset($artist_data->entities->{$artist_id}->claims->P18)) {
            $image_url = 'https://commons.wikimedia.org/wiki/File:' . $artist_data->entities->{$artist_id}->claims->P18[0]->mainsnak->datavalue->value;
            $tmp = self::get_image_commons($artist_data->entities->{$artist_id}->claims->P18[0]->mainsnak->datavalue->value);
            foreach($tmp->query->pages as $image)
              $image_thumb = $image->imageinfo[0]->thumburl;
          }

          ?>
          <h3>
            <span class="mw-headline" id="">
              <a href="<?php print ATLASMUSEUM_PATH . 'Spécial:WikidataArtist/' . $artist_id; ?>" title=""><?php (isset($artist_labels[$artist_id]) ? print $artist_labels[$artist_id] : print $artist_id); ?></a>
            </span>
          </h3>
          <p style="text-align:center">
            <a href="<?php print $image_url; ?>" class="image">
              <img alt="" src="<?php print $image_thumb; ?>" style="width:auto;max-width224px;max-height:149px" />
            </a>
          </p>
          <table class="wikitable">
            <?php
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P19', $artist_labels, 'Lieu de naissance');
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P569', $artist_labels, 'Date de naissance');
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P20', $artist_labels, 'Lieu de décès');
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P570', $artist_labels, 'Date de décès');
              self::render_claim_wd_from_artwork($artist_data->entities->{$artist_id}->claims, 'P27', $artist_labels, 'Pays de nationalité');
            ?>
          </table>
          <?php
        }
      }
    }
  }

}
