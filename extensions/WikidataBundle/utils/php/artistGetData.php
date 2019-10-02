<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class ArtistGetData {

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

    if (!is_null($data->entities))
      foreach ($data->entities as $q)
        foreach ($q->claims as $property=>$value)
          foreach ($value as $claim)
            if ($claim->mainsnak->datatype == 'wikibase-item' && !is_null($claim->mainsnak->datavalue->value->id))
              array_push($ids, $claim->mainsnak->datavalue->value->id);

    return $ids;
  }

  public static function get_ids2($param) {
    $ids = [];

    foreach($param as $value) {
      $table = explode(';', $value);
      foreach($table as $v)
        if (preg_match('/^Q[0-9]+$/', $v) && !is_null($v))
          array_push($ids, $v);
    }

    return $ids;
  }

  public static function get_label($labels, $language) {
    if (!is_null($labels) && isset($labels->{$language}))
        return $labels->{$language}->value;
      else
        return '';
  }

  public static function get_claims_item($claims, $property, $labels) {
    $data = [];

    if (!is_null($claims) && isset($claims->{$property})) {
      foreach($claims->{$property} as $claim) {
        $id = $claim->mainsnak->datavalue->value->id;
        $label = (isset($labels[$id]) ? $labels[$id] : $id);
        array_push($data, [
          'id' => $id,
          'label' => $label
        ]);
      }
    }

    return $data;
  }

  public static function get_claims_image($claims, $property) {
    $data = [];
    if (!is_null($claims) && isset($claims->{$property})) {
      $data = [
        'file' => $claims->{$property}[0]->mainsnak->datavalue->value,
        'origin' => 'commons'
      ];
    }

    return $data;
  }

  public static function get_claims_date($claims, $property) {
    if (!is_null($claims) && isset($claims->{$property})) {
      $time = $claims->{$property}[0]->mainsnak->datavalue->value->time;
      $time = preg_replace('/T.*$/', '', $time);
      $positive = $time[0] == '+';
      $time = substr($time, 1);
      $timeArray = explode('-', $time);
      $precision = $claims->{$property}[0]->mainsnak->datavalue->value->precision;

      if ($precision >= 11) 
        return $timeArray[2] . '-' . $timeArray[1] . '-' . $timeArray[0];
      else
      if ($precision == 10)
        return $timeArray[1] . '-' . $timeArray[0];
      else
        return $timeArray[0];
    }

    return '';
  }

  public static function get_empty_data() {
    return [
      'id' => '',
      'article' => '',
      'nature' => 'pérenne',
      'label' => '',
      'P625' => [
        'latitude' => 0,
        'longitude' => 0
      ],
      'P31' => [],
      '170' => []
    ];
  }

  /**
   * Fusionne des données provenant de deux sources distinctes
   *
   * @param {object} $data1 - Première source, prioritaire
   * @param {object} $data2 - Deuxième source
   * @return {object} Sources fusionnées
   */
  public static function merge_data($data1, $data2) {
    foreach ($data2 as $key => $value) {
      if (!isset($data1[$key]) || $data1[$key]  == '') {
        $data1[$key] = $value;
      }
    }

    return $data1;
  }

  public static function get_data($id, $values, $labels) {
    $data = self::get_empty_data();
    $data['id'] = $id;

    if (isset($values) && isset($values->entities) && isset($values->entities->{$id})) {
      //-- Label
      $data['label'] = self::get_label($values->entities->{$id}->labels, 'fr');
      //-- Nature de l'élément
      $data['P31'] = self::get_claims_item($values->entities->{$id}->claims, 'P31', $labels);
      //-- Prénom
      $data['735'] = self::get_claims_item($values->entities->{$id}->claims, 'P735', $labels);
      //-- Nom
      $data['734'] = self::get_claims_item($values->entities->{$id}->claims, 'P734', $labels);
      //-- Pays de nationalité
      $data['P27'] = self::get_claims_item($values->entities->{$id}->claims, 'P27', $labels);
      //-- Date de naissance
      $data['P569'] = self::get_claims_date($values->entities->{$id}->claims, 'P569');
      //-- Lieu de naissance
      $data['P19'] = self::get_claims_item($values->entities->{$id}->claims, 'P19', $labels);
      //-- Date de décès
      $data['P570'] = self::get_claims_date($values->entities->{$id}->claims, 'P570');
      //-- Lieu de décès
      $data['P20'] = self::get_claims_item($values->entities->{$id}->claims, 'P20', $labels);
      //-- Mouvement
      $data['P135'] = self::get_claims_item($values->entities->{$id}->claims, 'P135', $labels);
      //-- Image
      $data['P18'] = self::get_claims_image($values->entities->{$id}->claims, 'P18');
    }

    return $data;

  }

  public static function convert_item($data, $key) {
    $r = [];

    if (isset($data[$key]))
      $tmp = preg_split('/[\s]*,[\s]*/', $data[$key]);
      foreach ($tmp as $a)
        if (preg_match('/^Q[0-9]+$/', $a))
          array_push($r, [
            'label' => '',
            'id' => $a
          ]);
        else
          array_push($r, [
            'label' => $a,
            'id' => ''
          ]);

    return $r;
  }

  public static function get_page($page) {
    $result = Api::call_api(array(
      'action' => 'query',
      'prop' => 'revisions',
      'titles' => $page,
      'rvprop' => 'content'
    ), 'atlasmuseum');

    foreach($result->query->pages as $r) {
      $values = $r->revisions[0]->{'*'};
      break;
    }
    $values = preg_replace('/^.*<ArtworkPage/', '', $values);
    $lines = explode(PHP_EOL, $values);
    $data_am = [];
    foreach ($lines as $line) {
      $param = preg_replace('/^([^=]+)=.*$/', '$1', $line);
      if ($param != $line)
        $data_am[$param] = preg_replace('/\"$/', '', str_replace($param.'="', '', $line));
    }

    $data = self::get_empty_data();

    if (isset($data_am['q']))
      $data['id'] = $data_am['q'];

    if (isset($data_am['titre']))
      $data['label'] = $data_am['titre'];

    if (isset($data_am['nature']))
      $data['nature'] = $data_am['nature'];

    if (isset($data_am['site_coordonnees'])) {
      $tmp = preg_split('/[\s]*,[\s]*/', $data_am['site_coordonnees']);
      $data['P625']['latitude'] = floatval($tmp[0]);
      $data['P625']['longitude'] = floatval($tmp[1]);
    }

    if (isset($data_am['thumbnail'])) {
      $data['P18'] = $data_am['thumbnail'];
    }

    $data['P31'] = self::convert_item($data_am, 'type');
    $data['P21'] = self::convert_item($data_am, 'genre');
    $data['P27'] = self::convert_item($data_am, 'nationality');
    $data['P735'] = self::convert_item($data_am, 'prenom');
    $data['P734'] = self::convert_item($data_am, 'nom');
    $data['P569'] = self::convert_item($data_am, 'dateofBirth');
    /*$data['P19'] = self::convert_date($data_am, 'birthPlace');
    $data['P20'] = self::convert_date($data_am, 'deathPlace');*/
    $data['P27'] = self::convert_item($data_am, 'nationality');
    $data['P135'] = self::convert_item($data_am, 'movement');

    return $data;

  }

  public static function get_ids_am($data) {
    $ids = [];

    foreach ($data as $q) {
      if (is_array($q)) {
        foreach ($q as $p) {
          if (is_array($p)) {
            if (isset($p['id']) && $p['id']!='')
              array_push($ids, $p['id']);
          }
          else
          if (is_string($p) && preg_match('/^Q[0-9]+$/', $p))
            array_push($ids, $p);
        }
      }
      else
      if (is_string($q) && preg_match('/^Q[0-9]+$/', $q))
        array_push($ids, $q);
    }

    return $ids;
  }

  public static function convert_am_item($value) {
    $data = [];
    $table = explode(';', $value);
    foreach($table as $t)
      array_push($data, [
        'label' => $t,
        'id' => ''
      ]);

    return $data;
  }

  public static function get_data_am($page) {
    $data = self::get_empty_data();

    $tmp = Api::call_api([
      "action"	=> "query",
      "prop"		=> "revisions",
      "rvlimit" => 1,
      "rvprop"  => "content",
      "titles"  => $page,
      "continue" => ''
    ], 'atlasmuseum');

    if (!isset($tmp->query->pages->{-1})) {
      $data['article'] = $page;
      foreach($tmp->query->pages as $t) {
        foreach(explode("\n", $t->revisions[0]->{'*'}) as $line) {
          if (preg_match('/^[\s]*[^=\"]+[\s]*=[\s]*\".*\"$/', $line)) {
            $parameter = preg_replace('/^[\s]*([^=\"]+)[\s]*=[\s]*\".*\"$/', '$1', $line);
            $parameter = str_replace('é', 'e', str_replace(' ', '_', strtolower($parameter)));
            $value = preg_replace('/^[\s]*[^=\"]+[\s]*=[\s]*\"(.*)\"$/', '$1', $line);
            switch ($parameter) {
              case 'q':
                $data['id'] = $value;
                break;
              case 'titre':
                $data['label'] = $value;
                break;
              case 'prenom':
                $data['P735'] = self::convert_am_item($value);
                break;
              case 'nom':
                $data['P734'] = self::convert_am_item($value);
                break;
              case 'movement':
                $data['P135'] = self::convert_am_item($value);
                break;
              case 'birthplace':
                $data['P19'] = self::convert_am_item($value);
                break;
              case 'deathplace':
                $data['P20'] = self::convert_am_item($value);
                break;
              case 'nationality':
                $data['P27'] = self::convert_am_item($value);
                break;
              case 'thumbnail':
                $data['P18'] = [
                  "file" => $value,
                  "origin" => 'atlasmuseum'
                ];
                break;
              default:
                $data[$parameter] = $value;
            }
          }
        }
      }
    }
    
    return $data;
  }

  public static function get_labels_am($data) {
    $labels = [];
    $ids = [];

    foreach($data as $key => $property) {
      if (preg_match('/^[pP][0-9]+$/', $key)) {
        foreach($property as $key2 => $value) {
          if (isset($value['label']) && preg_match('/^[qQ][0-9]+$/', $value['label'])) {
            array_push($ids, $value['label']);
          }
        }
      }
    }

    $labels = self::get_labels($ids);
    
    foreach($data as $key => $property) {
      if (preg_match('/^[pP][0-9]+$/', $key)) {
        foreach($property as $key2 => $value) {
          if (isset($value['label']) && preg_match('/^[qQ][0-9]+$/', $value['label'])) {
            if (isset($labels[$value['label']]))
              $data[$key][$key2]['label'] = $labels[$value['label']];
            $data[$key][$key2]['id'] = $value['label'];
          }
        }
      }
    }

    return $data;
  }

}
