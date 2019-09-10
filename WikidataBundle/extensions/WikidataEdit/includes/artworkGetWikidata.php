<?php

require_once('extensions/Wikidata/includes/api.php');

class ArtworkGetWikidata {

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
          if ($claim->mainsnak->datatype == 'wikibase-item')
            array_push($ids, $claim->mainsnak->datavalue->value->id);

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

  public static function get_data($id, $values, $labels) {
    $data = [
      'id' => $id,
      'nature' => 'pérenne',
      'label' => '',
      'P625' => [
        'latitude' => 0,
        'longitude' => 0
      ],
      'P31' => [],
      '170' => []
    ];

    if (isset($values) && isset($values->entities) && isset($values->entities->{$id})) {
      //-- Label
      $data['label'] = self::get_label($values->entities->{$id}->labels, 'fr');
      //-- Coordonnées
      $data['P625'] = self::get_claims_coordinates($values->entities->{$id}->claims, 'P625');
      //-- Nature de l'élément
      $data['P31'] = self::get_claims_item($values->entities->{$id}->claims, 'P31', $labels);
      //-- Crétateurs
      $data['P170'] = self::get_claims_item($values->entities->{$id}->claims, 'P170', $labels);
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

    $data = [
      'id' => '',
      'nature' => 'pérenne',
      'label' => '',
      'P625' => [
        'latitude' => 0,
        'longitude' => 0
      ],
      'P31' => [],
      '170' => []
    ];

    if (isset($data_am['q']))
      $data['id'] = $data_am['q'];

    if (isset($data_am['titre']))
      $data['label'] = $data_am['titre'];

    if (isset($data_am['nature']))
      $data['nature'] = $data_am['nature'];

    if (isset($data_am['Site_coordonnees'])) {
      $tmp = preg_split('/[\s]*,[\s]*/', $data_am['Site_coordonnees']);
      $data['P625']['latitude'] = floatval($tmp[0]);
      $data['P625']['longitude'] = floatval($tmp[1]);
    }

    $data['P31'] = self::convert_item($data_am, 'type_art');
    $data['P170'] = self::convert_item($data_am, 'artiste');

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

}

