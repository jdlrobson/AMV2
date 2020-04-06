<?php
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');
class ArtworkGetData {
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
      if (isset($labels_data->entities)) {
        foreach($labels_data->entities as $id=>$value) {
          if (isset($value->labels->fr)) {
            $labels[$id] = $value->labels->fr->value;
          } else
          if (isset($value->labels->en)) {
            $labels[$id] = $value->labels->en->value;
          }
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
          if ($claim->mainsnak->datatype == 'wikibase-item')
            array_push($ids, $claim->mainsnak->datavalue->value->id);
    return $ids;
  }
  public static function get_ids2($param) {
    $ids = [];
    foreach($param as $value) {
      $table = explode(';', $value);
      foreach($table as $v)
        if (preg_match('/^Q[0-9]+$/', $v))
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
  public static function get_claims_coordinates($claims, $property) {
    if (!is_null($claims) && isset($claims->{$property}))
      return [
        'latitude' => $claims->{$property}[0]->mainsnak->datavalue->value->latitude,
        'longitude' => $claims->{$property}[0]->mainsnak->datavalue->value->longitude
      ];
    else
      return [
        'latitude' => 0,
        'longitude' =>0
      ];
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
      //-- Coordonnées
      $data['P625'] = self::get_claims_coordinates($values->entities->{$id}->claims, 'P625');
      //-- Nature de l'élément
      $data['P31'] = self::get_claims_item($values->entities->{$id}->claims, 'P31', $labels);
      //-- Créateurs
      $data['P170'] = self::get_claims_item($values->entities->{$id}->claims, 'P170', $labels);
      //-- Mouvement
      $data['P135'] = self::get_claims_item($values->entities->{$id}->claims, 'P135', $labels);
      //-- Couleurs
      $data['P462'] = self::get_claims_item($values->entities->{$id}->claims, 'P462', $labels);
      //-- Matériaux
      $data['P186'] = self::get_claims_item($values->entities->{$id}->claims, 'P186', $labels);
      //-- Commanditaires
      $data['P88'] = self::get_claims_item($values->entities->{$id}->claims, 'P88', $labels);
      //-- Commissaires
      $data['P1640'] = self::get_claims_item($values->entities->{$id}->claims, 'P1640', $labels);
      //-- Lieux
      $data['P276'] = self::get_claims_item($values->entities->{$id}->claims, 'P276', $labels);
      //-- Ville
      $data['P131'] = self::get_claims_item($values->entities->{$id}->claims, 'P131', $labels);
      //-- Pays
      $data['P17'] = self::get_claims_item($values->entities->{$id}->claims, 'P17', $labels);
      //-- PMR
      $data['P2846'] = self::get_claims_item($values->entities->{$id}->claims, 'P2846', $labels);
      //-- Sujet représenté
      $data['P921'] = self::get_claims_item($values->entities->{$id}->claims, 'P921', $labels);
      //-- Image
      $data['P18'] = self::get_claims_image($values->entities->{$id}->claims, 'P18');
    }
    $data['nature'] = 'Pérenne';
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
    $data['P31'] = self::convert_item($data_am, 'type_art');
    $data['P170'] = self::convert_item($data_am, 'artiste');
    $data['P131'] = self::convert_item($data_am, 'site_ville');
    $data['P17'] = self::convert_item($data_am, 'site_pays');
    $data['P135'] = self::convert_item($data_am, 'mouvement_artistes');
    $data['P462'] = self::convert_item($data_am, 'couleur');
    $data['P186'] = self::convert_item($data_am, 'materiaux');
    $data['P88'] = self::convert_item($data_am, 'commanditaires');
    $data['P1640'] = self::convert_item($data_am, 'commissaires');
    $data['P276'] = self::convert_item($data_am, 'site_nom');
    $data['P2846'] = self::convert_item($data_am, 'site_pmr');
    $data['P921'] = self::convert_item($data_am, 'forme');
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
              case 'site_coordonnees':
                $c = preg_split('/[\s]*,[\s]*/', $value);
                $data['P625'] = [
                  'latitude' => floatval($c[0]),
                  'longitude' => floatval($c[1])
                ];
                break;
              case 'type_art':
                $data['P31'] = self::convert_am_item($value);
                break;
              case 'artiste':
                $data['P170'] = self::convert_am_item($value);
                break;
              case 'mouvement_artistes':
                $data['P135'] = self::convert_am_item($value);
                break;
              case 'couleur':
                $data['P462'] = self::convert_am_item($value);
                break;
              case 'materiaux':
                $data['P186'] = self::convert_am_item($value);
                break;
              case 'commanditaires':
                $data['P88'] = self::convert_am_item($value);
                break;
              case 'commissaires':
                $data['P1640'] = self::convert_am_item($value);
                break;
              case 'site_nom':
                $data['P276'] = self::convert_am_item($value);
                break;
              case 'site_ville':
                $data['P131'] = self::convert_am_item($value);
                break;
              case 'site_pays':
                $data['P17'] = self::convert_am_item($value);
                break;
              case 'site_pmr':
                $data['P2846'] = self::convert_am_item($value);
                break;
              case 'forme':
                $data['P921'] = self::convert_am_item($value);
                break;
              case 'image_principale':
                if (preg_match('/^Commons:/i', $value))
                  $data['P18'] = [
                    "file" => substr($value, 8),
                    "origin" => 'commons'
                  ];
                else
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