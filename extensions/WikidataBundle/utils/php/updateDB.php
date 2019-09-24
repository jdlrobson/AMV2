<?php

define(DB_SERVER, "publicarmod1.mysql.db");
define(DB_NAME, "publicarmod1");
define(DB_USER, "publicarmod1");
define(DB_PASSWORD, "1dwy2Myi");


function query($query) {
  $mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
  $mysqli->set_charset("utf8");
  return $mysqli->query($query);
}

/**
 * Récupère le titre d'une œuvre AM à partir de son identifiant Wikidata
 *
 * @param {string} $id - Identifiant de l'œuvre
 * @return {string} Titre de l'œuvre ; chaîne vide si inexistante
 */
function get_artwork_from_q($id) {
  $query = 'SELECT article FROM tmp_library_2 WHERE wikidata = "' . $id . '" LIMIT 1';
  $data = query($query);
  $row = $data->fetch_assoc();
  if (is_null($row))
    return '';
  else
    return $row['article'];
}

function get_artworks_from_artist($id) {
  $query = 'SELECT * FROM tmp_library_2 WHERE artist = "' . $id . '"';
  //var_dump($query);
  $data = query($query);
  return $data;
}

function create_artwork() {

  if (isset($_GET['article'])) {

    $article = str_replace('"', '\"', urldecode($_GET['article']));
    $title = str_replace('"', '\"', urldecode($_GET['title']));
    $artist = str_replace('"', '\"', urldecode($_GET['artist']));
    $nature = str_replace('"', '\"', urldecode($_GET['nature']));
    $latitude = (is_null($_GET['latitude']) ? 0 : $_GET['latitude']);
    $longitude = (is_null($_GET['longitude']) ? 0 : $_GET['longitude']);
    $image = str_replace('"', '\"', urldecode($_GET['image']));
    $date = str_replace('"', '\"', urldecode($_GET['date']));
    $wikidata = $_GET['wikidata'];

    $query = 'INSERT INTO tmp_library_2(article, tmp_library_2.title, artist, nature, latitude, longitude, image, date, wikidata) VALUES( ' .
      '"' . $article . '", ' .
      '"' . $title . '", ' .
      '"' . $artist . '", ' .
      '"' . $nature . '", ' .
      $latitude . ', ' .
      $longitude . ', ' .
      '"' . $image . '", ' .
      '"' . $date . '", ' .
      '"' . $wikidata . '")';

    query($query);
  
    $result = [
      'result' => 'ok'
    ];
  } else {
    $result = [
      'result' => 'ko'
    ];
  }

  print json_encode($result);
}

function update_artwork() {

  if (isset($_GET['article'])) {

    $article = str_replace('"', '\"', urldecode($_GET['article']));
    $title = str_replace('"', '\"', urldecode($_GET['title']));
    $artist = str_replace('"', '\"', urldecode($_GET['artist']));
    $nature = str_replace('"', '\"', urldecode($_GET['nature']));
    $latitude = (is_null($_GET['latitude']) ? 0 : $_GET['latitude']);
    $longitude = (is_null($_GET['longitude']) ? 0 : $_GET['longitude']);
    $image = str_replace('"', '\"', urldecode($_GET['image']));
    $date = str_replace('"', '\"', urldecode($_GET['date']));
    $wikidata = urldecode($_GET['wikidata']);

    $query = 'UPDATE tmp_library_2 SET ' .
      'tmp_library_2.title = "' . $title . '", ' .
      'artist = "' . $artist . '", ' .
      'nature = "' . $nature . '", ' .
      'latitude = ' . $latitude . ', ' .
      'longitude = ' . $longitude . ', ' .
      'image = "' . $image . '", ' .
      'date = "' . $date . '", ' .
      'wikidata = "' . $wikidata . '" ' .
      'WHERE article="' . $article . '"';

    query($query);

    var_dump($query);
  
    $result = [
      'result' => 'ok',
    ];
  } else {
    $result = [
      'result' => 'ko'
    ];
  }

  print json_encode($result);
}

function get_artist($id) {
  $query = "SELECT article FROM tmp_artist WHERE wikidata='$id' LIMIT 1";
  $data = query($query);
  $row = $data->fetch_assoc();
  if (is_null($row))
    return '';
  else
    return $row['article'];
}

function get_artists_from_ids($labels) {
  $artists = implode("','", $labels);
  $query = "SELECT article,wikidata FROM tmp_artist WHERE article IN ('$artists')";
  $data = query($query);
  return $data;
}

function get_artists_names($ids) {
  $ids_array = implode("','", $ids);
  $query = "SELECT article,wikidata FROM tmp_artist WHERE wikidata IN ('$ids_array')";
  $data = query($query);
  return $data;
}

function get_artworks_from_artists($labels) {
  $key = [];
  foreach($labels as $label)
    array_push($key, 'artist LIKE \'%' . $label . '%\'');
  $query = "SELECT DISTINCT article, title, image, wikidata FROM tmp_library_2 WHERE " . join(' OR ', $key) . " ORDER BY title";

  $data = query($query);
  return $data;
}

function get_artist_from_q($id) {
  $query = 'SELECT article FROM tmp_artist WHERE wikidata = "' . $id . '" LIMIT 1';
  $data = query($query);
  $row = $data->fetch_assoc();
  if (is_null($row))
    return '';
  else
    return $row['article'];
}

function update_artist() {

  $result = [];

  if (isset($_GET['article'])) {

    $article = str_replace('"', '\"', urldecode($_GET['article']));
    $wikidata = urldecode($_GET['wikidata']);

    $query = 'SELECT COUNT(*) AS count FROM tmp_artist WHERE article = "' . $article . '"';
    $data = query($query);
    $row = $data->fetch_assoc();

    if ((int)$row['count'] == 0) {
      // Création
      $query = 'INSERT INTO tmp_artist(article, wikidata) VALUES("' . $article . '", "' . $wikidata . '")';
      $data = query($query);
    } else {
      // Mise à jour
      $query = 'UPDATE tmp_artist SET wikidata="' . $wikidata . '" WHERE article="' . $article . '"';
      $data = query($query);
    }
  
    $result = [
      'result' => 'ok'
    ];
  } else {
    $result = [
      'result' => 'ko'
    ];
  }

  print json_encode($result);
}

/********************************************************
 * Actions
 ********************************************************/

if (isset($_GET['action'])) {
  switch ($_GET['action']) {
    case 'update_artwork':
      update_artwork();
      break;
    case 'update_artist':
      update_artist();
      break;
  }
  switch ($_GET['action']) {
    case 'create_artwork':
      create_artwork();
      break;
  }
}

