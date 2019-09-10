<?php

define(DB_SERVER, "publicarmod1.mysql.db");
define(DB_NAME, "publicarmod1");
define(DB_USER, "publicarmod1");
define(DB_PASSWORD, "1dwy2Myi");

require_once('../../WikidataPaths.php');

require_once('api.php');

function get_nature($nature) {
  switch ($nature) {
    case 'Q284':
      return 'pérenne';
      break;
    case 'Q285':
      return 'éphémère';
      break;
    case 'Q286':
      return 'détruite';
      break;
    case 'Q287':
      return 'non réalisée';
      break;
    case 'Q288':
      return 'à vérifier';
      break;
    default:
      return $nature;
  }
}

function get_am() {
  $data = [];

  $mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
	$mysqli->set_charset("utf8");
  //$res = $mysqli->query("SELECT tmp_library.*,tmp_equivalence.wikidata FROM tmp_library LEFT JOIN tmp_equivalence ON tmp_library.q = tmp_equivalence.atlasmuseum");
  $res = $mysqli->query("SELECT * FROM tmp_library_2");
  
  while ($artwork = $res->fetch_assoc()) {
    if (!is_null($artwork['date'])) {
      $date = preg_replace('/-.*$/', '', str_replace('+', '', $artwork['date']));
    } else
      $date = '';

    if ($artwork['latitude'] != 0 && $artwork['longitude'] != 0)
      array_push($data, [
        'article' => $artwork['article'],
        'title' => $artwork['title'],
        'creator' => [$artwork['artist']],
        'wikidata' => $artwork['wikidata'],
        'image' => $artwork['image'],
        'date' => $date,
        'latitude' => floatval($artwork['latitude']),
        'longitude' => floatval($artwork['longitude']),
        'nature' => get_nature($artwork['nature'])
      ]);
  }

  return $data;
}

function get_wikidata_ids($data_am) {
  $ids = [];

  for ($i=0; $i<sizeof($data_am); $i++) {
    if (!is_null($data_am[$i]['wikidata'])) {
      array_push($ids, $data_am[$i]['wikidata']);
    }
  }

  return $ids;
}

function get_wikidata($ids) {
  $query =
    'SELECT DISTINCT ?q ?qLabel ?coords ?creatorLabel ?image WHERE {' .
    '  ?q wdt:P136/wdt:P279* wd:Q557141 ;' .
    '     wdt:P625 ?coords .' .
    '  OPTIONAL { ?q wdt:P170 ?creator }' .
    '  OPTIONAL { ?q wdt:P18 ?image }' .
    '  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr" . }' .
    '} ORDER BY ?q';

  $result = Api::Sparql($query);
  $data = [];

  foreach ($result->results->bindings as $artwork) {
    $id = str_replace(WIKIDATA_ENTITY, '', $artwork->q->value);

    if (!in_array($id, $ids)) {
      $title = $artwork->qLabel->value;
      $coords = explode(' ', str_replace(')', '', str_replace('Point(', '', $artwork->coords->value)));
      $image = isset($artwork->image) ? urldecode(str_replace(COMMONS_FILE_PATH, '', $artwork->image->value)) : '';
      $creator = isset($artwork->creatorLabel) ? $artwork->creatorLabel->value : '';

      if (isset($data[$id])) {
        if (!in_array($creator, $data[$id]['creator']))
          array_push($data[$id]['creator'], $creator);
      } else {
        $data[$id] = [
          'article' => '',
          'wikidata' => $id,
          'title' => $title,
          'nature' => 'wikidata',
          'latitude' => floatval($coords[1]),
          'longitude' => floatval($coords[0]),
          'image' => $image,
          'date' => '',
          'creator' => [$creator]
        ];
      }
    }
  }

  return $data;
}

$data_am = get_am();
$ids = get_wikidata_ids($data_am);
$data_wd = get_wikidata($ids);

print json_encode(array_merge($data_am, $data_wd));

?>
