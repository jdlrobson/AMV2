<?php
/*****************************************************************************
 * updateMap.php
 *
 * Met à jour la base de données avec les éléments de la carte.
 * Permet de mettre en cache la requête, celle-ci étant très longue.
 *****************************************************************************/

// Appel API

define(DB_SERVER, "publicarmod1.mysql.db");
define(DB_NAME, "publicarmod1");
define(DB_USER, "publicarmod1");
define(DB_PASSWORD, "1dwy2Myi");
define(DB_PREFIX, 'tmp_');

function callApi($origin = 'atlasmuseum') {
  $parameters = [
    'action' => 'amgetmap',
    'origin' => $origin,
    'format' => 'json'
  ];

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
  ));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  $url = 'http://publicartmuseum.net/w/amapi/index.php';
  $url = sprintf("%s?%s", $url, http_build_query($parameters));

  $url = str_replace('%5Cn', '%0A', $url);
  curl_setopt($curl, CURLOPT_URL, $url);
  return json_decode(curl_exec($curl))->entities;
}

$dataAM = callApi('atlasmuseum');
$dataWD = callApi('wikidata');

$wikidataIdsAM = [];
for ($i = 0; $i < sizeof($dataAM); $i++)
  if ($dataAM[$i]->wikidata != '')
    array_push($wikidataIdsAM, $dataAM[$i]->wikidata);

for ($i = 0; $i < sizeof($dataWD); $i++)
  if (!in_array($dataWD[$i]->wikidata, $wikidataIdsAM))
    array_push($dataAM, $dataWD[$i]);

$j = 0;
$batchSize = 100;

$mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
$mysqli->set_charset("utf8");
$mysqli->query('TRUNCATE ' . DB_PREFIX . 'map;');

while ($j < sizeof($dataAM)) {
  $query = 'INSERT INTO ' . DB_PREFIX . 'map(article, title, artist, lat, lon, nature, wikidata) VALUES ';
  $valuesTable = [];
  for ($i = 0; $i < $batchSize && $i + $j < sizeof($dataAM); $i++)
    array_push($valuesTable, '(' .
      '"' . str_replace('"', '\"', $dataAM[$i+$j]->article) . '",' .
      '"' . str_replace('"', '\"', $dataAM[$i+$j]->title) . '",' .
      '"' . str_replace('"', '\"', $dataAM[$i+$j]->artist) . '",' .
      $dataAM[$i+$j]->lat . ',' .
      $dataAM[$i+$j]->lon . ',' .
      '"' . str_replace('"', '\"', $dataAM[$i+$j]->nature) . '",' .
      '"' . str_replace('"', '\"', $dataAM[$i+$j]->wikidata) . '"' .
      ')');

  $query .= implode(', ', $valuesTable) . ';';
  
  $mysqli->query($query);

  $j += $batchSize;
}
