<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$countries = [
  'AF' => 'Afghanistan',
  'ZA' => 'Afrique du Sud',
  'AX' => 'Åland',
  'AL' => 'Albanie',
  'DZ' => 'Algérie',
  'DE' => 'Allemagne',
  'AD' => 'Andorre',
  'AO' => 'Angola',
  'AI' => 'Anguilla',
  'AQ' => 'Antarctique',
  'AG' => 'Antigua-et-Barbuda',
  'SA' => 'Arabie saoudite',
  'AR' => 'Argentine',
  'AM' => 'Arménie',
  'AW' => 'Aruba',
  'AU' => 'Australie',
  'AT' => 'Autriche',
  'AZ' => 'Azerbaïdjan',
  'BS' => 'Bahamas',
  'BH' => 'Bahreïn',
  'BD' => 'Bangladesh',
  'BB' => 'Barbade',
  'BY' => 'Biélorussie',
  'BE' => 'Belgique',
  'BZ' => 'Belize',
  'BJ' => 'Bénin',
  'BM' => 'Bermudes',
  'BT' => 'Bhoutan',
  'BO' => 'Bolivie',
  'BQ' => 'Bonaire, Saint-Eustache et Saba',
  'BA' => 'Bosnie-Herzégovine',
  'BW' => 'Botswana',
  'BV' => 'Île Bouvet',
  'BR' => 'Brésil',
  'BN' => 'Brunei',
  'BG' => 'Bulgarie',
  'BF' => 'Burkina Faso',
  'BI' => 'Burundi',
  'KY' => 'Îles Caïmanes',
  'KH' => 'Cambodge',
  'CM' => 'Cameroun',
  'CA' => 'Canada',
  'CV' => 'Cap-Vert',
  'CF' => 'République centrafricaine',
  'CL' => 'Chili',
  'CN' => 'Chine',
  'CX' => 'Île Christmas',
  'CY' => 'Chypre',
  'CC' => 'Îles Cocos',
  'CO' => 'Colombie',
  'KM' => 'Comores',
  'CG' => 'République du Congo',
  'CD' => 'République démocratique du Congo',
  'CK' => 'Îles Cook',
  'KR' => 'Corée du Sud',
  'KP' => 'Corée du Nord',
  'CR' => 'Costa Rica',
  'CI' => 'Côte d\'Ivoire',
  'HR' => 'Croatie',
  'CU' => 'Cuba',
  'CW' => 'Curaçao',
  'DK' => 'Danemark',
  'DJ' => 'Djibouti',
  'DO' => 'République dominicaine',
  'DM' => 'Dominique',
  'EG' => 'Égypte',
  'SV' => 'Salvador',
  'AE' => 'Émirats arabes unis',
  'EC' => 'Équateur',
  'ER' => 'Érythrée',
  'ES' => 'Espagne',
  'EE' => 'Estonie',
  'US' => 'États-Unis',
  'ET' => 'Éthiopie',
  'FK' => 'Îles Malouines',
  'FO' => 'Îles Féroé',
  'FJ' => 'Fidji',
  'FI' => 'Finlande',
  'FR' => 'France',
  'GA' => 'Gabon',
  'GM' => 'Gambie',
  'GE' => 'Géorgie',
  'GS' => 'Géorgie du Sud-et-les Îles Sandwich du Sud',
  'GH' => 'Ghana',
  'GI' => 'Gibraltar',
  'GR' => 'Grèce',
  'GD' => 'Grenade',
  'GL' => 'Groenland',
  'GP' => 'Guadeloupe',
  'GU' => 'Guam',
  'GT' => 'Guatemala',
  'GG' => 'Guernesey',
  'GN' => 'Guinée',
  'GW' => 'Guinée-Bissau',
  'GQ' => 'Guinée équatoriale',
  'GY' => 'Guyana',
  'GF' => 'Guyane',
  'HT' => 'Haïti',
  'HM' => 'Îles Heard-et-MacDonald',
  'HN' => 'Honduras',
  'HK' => 'Hong Kong',
  'HU' => 'Hongrie',
  'IM' => 'Île de Man',
  'UM' => 'Îles mineures éloignées des États-Unis',
  'VG' => 'Îles Vierges britanniques',
  'VI' => 'Îles Vierges américaines',
  'IN' => 'Inde',
  'ID' => 'Indonésie',
  'IR' => 'Iran',
  'IQ' => 'Iraq',
  'IE' => 'Irlande',
  'IS' => 'Islande',
  'IL' => 'Israël',
  'IT' => 'Italie',
  'JM' => 'Jamaïque',
  'JP' => 'Japon',
  'JE' => 'Jersey',
  'JO' => 'Jordanie',
  'KZ' => 'Kazakhstan',
  'KE' => 'Kenya',
  'KG' => 'Kirghizistan',
  'KI' => 'Kiribati',
  'KW' => 'Koweït',
  'LA' => 'Laos',
  'LS' => 'Lesotho',
  'LV' => 'Lettonie',
  'LB' => 'Liban',
  'LR' => 'Liberia',
  'LY' => 'Libye',
  'LI' => 'Liechtenstein',
  'LT' => 'Lituanie',
  'LU' => 'Luxembourg',
  'MO' => 'Macao',
  'MK' => 'MKD',
  'MG' => 'Madagascar',
  'MY' => 'Malaisie',
  'MW' => 'Malawi',
  'MV' => 'Maldives',
  'ML' => 'Mali',
  'MT' => 'Malte',
  'MP' => 'Îles Mariannes du Nord',
  'MA' => 'Maroc',
  'MH' => 'Marshall',
  'MQ' => 'Martinique',
  'MU' => 'Maurice',
  'MR' => 'Mauritanie',
  'YT' => 'Mayotte',
  'MX' => 'Mexique',
  'FM' => 'Micronésie',
  'MD' => 'Moldavie',
  'MC' => 'Monaco',
  'MN' => 'Mongolie',
  'ME' => 'Monténégro',
  'MS' => 'Montserrat',
  'MZ' => 'Mozambique',
  'MM' => 'Birmanie',
  'NA' => 'Namibie',
  'NR' => 'Nauru',
  'NP' => 'Népal',
  'NI' => 'Nicaragua',
  'NE' => 'Niger',
  'NG' => 'Nigeria',
  'NU' => 'Niue',
  'NF' => 'Norfolk',
  'NO' => 'Norvège',
  'NC' => 'Nouvelle-Calédonie',
  'NZ' => 'Nouvelle-Zélande',
  'IO' => 'Territoire britannique de l\'océan Indien',
  'OM' => 'Oman',
  'UG' => 'Ouganda',
  'UZ' => 'Ouzbékistan',
  'PK' => 'Pakistan',
  'PW' => 'Palaos',
  'PS' => 'Palestine',
  'PA' => 'Panama',
  'PG' => 'Papouasie-Nouvelle-Guinée',
  'PY' => 'Paraguay',
  'NL' => 'Pays-Bas',
  'PE' => 'Pérou',
  'PH' => 'Philippines',
  'PN' => 'Pitcairn',
  'PL' => 'Pologne',
  'PF' => 'Polynésie française',
  'PR' => 'Porto Rico',
  'PT' => 'Portugal',
  'QA' => 'Qatar',
  'RE' => 'Réunion',
  'RO' => 'Roumanie',
  'GB' => 'Royaume-Uni',
  'RU' => 'Russie',
  'RW' => 'Rwanda',
  'EH' => 'République arabe sahraouie démocratique',
  'BL' => 'Saint-Barthélemy',
  'KN' => 'Saint-Christophe-et-Niévès',
  'SM' => 'Saint-Marin',
  'MF' => 'Saint-Martin',
  'SX' => 'Sint Maarten',
  'PM' => 'Saint-Pierre-et-Miquelon',
  'VA' => 'Vatican',
  'VC' => 'Saint-Vincent-et-les-Grenadines',
  'SH' => 'Sainte-Hélène, Ascension et Tristan da Cunha',
  'LC' => 'Sainte-Lucie',
  'SB' => 'Îles Salomon',
  'WS' => 'Samoa',
  'AS' => 'Samoa américaines',
  'ST' => 'Sao Tomé-et-Principe',
  'SN' => 'Sénégal',
  'RS' => 'Serbie',
  'SC' => 'Seychelles',
  'SL' => 'Sierra Leone',
  'SG' => 'Singapour',
  'SK' => 'Slovaquie',
  'SI' => 'Slovénie',
  'SO' => 'Somalie',
  'SD' => 'Soudan',
  'SS' => 'Soudan du Sud',
  'LK' => 'Sri Lanka',
  'SE' => 'Suède',
  'CH' => 'Suisse',
  'SR' => 'Suriname',
  'SJ' => 'Svalbard et île Jan Mayen',
  'SZ' => 'SWZ',
  'SY' => 'Syrie',
  'TJ' => 'Tadjikistan',
  'TW' => 'Taïwan',
  'TZ' => 'Tanzanie',
  'TD' => 'Tchad',
  'CZ' => 'Tchéquie',
  'TF' => 'Terres australes et antarctiques françaises',
  'TH' => 'Thaïlande',
  'TL' => 'Timor oriental',
  'TG' => 'Togo',
  'TK' => 'Tokelau',
  'TO' => 'Tonga',
  'TT' => 'Trinité-et-Tobago',
  'TN' => 'Tunisie',
  'TM' => 'Turkménistan',
  'TC' => 'Îles Turques-et-Caïques',
  'TR' => 'Turquie',
  'TV' => 'Tuvalu',
  'UA' => 'Ukraine',
  'UY' => 'Uruguay',
  'VU' => 'Vanuatu',
  'VE' => 'Venezuela',
  'VN' => 'Viêt Nam',
  'WF' => 'Wallis-et-Futuna',
  'YE' => 'Yémen',
  'ZM' => 'Zambie',
  'ZW' => 'Zimbabwe'
];

function getPlace() {
  global $countries;

  $result = [
    'country' => '',
    'place' => ''
  ];

  if (["latitude"] !== null && $_GET["longitude"] !== null) {
    $latitude  = $_GET["latitude"];
    $longitude = $_GET["longitude"];

    $contents = file_get_contents('http://api.geonames.org/extendedFindNearby?username=poulpy2&lat=' . $latitude . '&lng=' . $longitude);
    $data = explode("\n", $contents);

    $places = [];
    $currentName = '';
    $countryCode = '';
    $largestAdm = 0;

    foreach($data as $line) {
      if (preg_match('/^[\s]*<name>.*<\/name>$/', $line)) {
        $currentName = preg_replace('/^[\s]*<name>(.*)<\/name>$/', '$1', $line);
      } else
      if (preg_match('/^[\s]*<countryCode>.*<\/countryCode>$/', $line)) {
        $countryCode = preg_replace('/^[\s]*<countryCode>(.*)<\/countryCode>$/', '$1', $line);
      }
      if (preg_match('/^[\s]*<fcode>.*<\/fcode>$/', $line)) {
        $fcode = preg_replace('/^[\s]*<fcode>(.*)<\/fcode>$/', '$1', $line);
        if ($fcode == 'PCLI')
          $places[$fcode] = $countryCode;
        else
          $places[$fcode] = $currentName;
        if (preg_match('/^ADM[0-9]+$/', $fcode)) {
          $index = intval(preg_replace('/^ADM([0-9]+)$/', '$1', $fcode));
          if ($index > $largestAdm) {
            $largestAdm = $index;
          }
        }
      }
    }

    if (isset($places['PCLI'])) {
      $country = $places['PCLI'];
      if (isset($countries[$country]))
        $country = $countries[$country];
      $town = '';
      if ($largestAdm >= 3) {
        $town = $places['ADM' . $largestAdm];
      }

      $result['country'] = $country;
      $result['place'] = $town;
    }
  }

  return $result;
}

print json_encode(getPlace());

?>
