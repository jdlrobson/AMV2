<?php

/**********************
 * Fonctions internes
 **********************/

function createLabel($language, $value) {
  return [
    "language" => $language,
    "value" => $value
  ];
}

function createDescription($language, $value) {
  return [
    "language" => $language,
    "value" => $value
  ];
}

function createReferenceUrl($url) {
  return [
    "snaks" => [
      "P854" => [
        [
          "snaktype" => "value",
          "property" => "P854",
          "datavalue" => [
            "value" => $url,
            "type" => "string"
          ]
        ]
      ]
    ]
  ];
}

function createClaimItem($property, $value, $reference = '') {
  $data = [
    "mainsnak" => [
      "snaktype" => "value",
      "property" => $property,
      "datavalue" => [
        "value" => [
          "id" => $value
        ],
        "type" => "wikibase-entityid"
      ]
    ],
    "type" => "statement",
    "rank" => "normal"
  ];

  if ($reference != '') {
    $data['references'] = [];
    array_push($data['references'], createReferenceUrl('http://publicartmuseum.net/wiki/' . urlencode($reference)));
  }

  return $data;
}

function createClaimCoordinates($property, $lat, $lng, $reference = '') {
  $data = [
    "mainsnak" => [
      "snaktype" => "value",
      "property" => $property,
      "datavalue" => [
        "value" => [
          "latitude" => $lat,
          "longitude" => $lng,
          "precision" => 1.0e-4,
          "globe" => "http://www.wikidata.org/entity/Q2"
        ],
        "type" => "globecoordinate"
      ]
    ],
    "type" => "statement",
    "rank" => "normal"
  ];

  if ($reference != '') {
    $data['references'] = [];
    array_push($data['references'], createReferenceUrl('http://publicartmuseum.net/wiki/' . urlencode($reference)));
  }

  return $data;
}
