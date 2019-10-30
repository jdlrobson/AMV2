<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: PUT, POST, PATCH, DELETE, GET");

/***************************
 * Main code
 ***************************/

// view-source:http://lerunigo.fr/tmp/am/updateWD.php?id=Q72288066&data=%7B%22L%22%3A%7B%22fr%22%3A%22Test%22%7D%2C%22D%22%3A%7B%22fr%22%3A%22This+is+a+test%22%7D%2C%22P31%22%3A%5B%22Q838948%22%5D%2C%22P131%22%3A%5B%22Q340%22%5D%2C%22P17%22%3A%5B%22Q16%22%5D%2C%22P136%22%3A%5B%22Q557141%22%5D%2C%22P170%22%3A%5B%22Q593621%22%5D%2C%22P625%22%3A%7B%22lat%22%3A45.520665441586%2C%22lng%22%3A-73.564872264687%7D%2C%22origin%22%3A%22Neuf+couleurs+au+vent+%28Daniel+Buren%29%22%7D

require_once('api.php');
require_once('data.php');

$id = '';
$params = [];

if (isset($_GET)) {
  if (isset($_GET['id']))
    $id = $_GET['id'];
  if (isset($_GET['data']))
    $params = json_decode($_GET['data'], true);
}

// var_dump($params);

$origin = '';
if (isset($params['origin']))
  $origin = $params['origin'];

//$id = "Q4115189";
//$id = 'Q72288066';

$data = [];
if ($id != '')
  $data = getData($id);
// var_dump($data);

$labels = [];
if (isset($params['L'])) {
  foreach($params['L'] as $language => $value)
    if (!labelExists($data, $language))
      array_push($labels, createLabel($language, $value));
}


$descriptions = [];
if (isset($params['D'])) {
  foreach($params['D'] as $language => $value)
    if (!descriptionExists($data, $language))
      array_push($descriptions, createDescription($language, $value));
}

$claims = [];
foreach($params as $property => $claim) {
  if ($property[0] == 'P') {
    if ($property == 'P625') {
      if (!claimCoordinatesExists($data))
        array_push($claims, createClaimCoordinates('P625', $claim['lat'], $claim['lng'], $origin));
    } else {
      for ($i=0; $i < sizeof($claim); $i++) {
        if (!claimItemExists($data, $property, $claim[$i]))
          array_push($claims, createClaimItem($property, $claim[$i], $origin));
      }
    }
  }
}

$data = [];
if (sizeof($labels) > 0)
  $data['labels'] = $labels;
if (sizeof($descriptions) > 0)
  $data['descriptions'] = $descriptions;
if (sizeof($claims) > 0)
  $data['claims'] = $claims;

if (sizeof($data) > 0) {
  login();
  $result = editItem($id, $data);
  echo json_encode([
    'id' => $result
  ]);
} else {
  echo json_encode([
    'id' => $id
  ]);
}
