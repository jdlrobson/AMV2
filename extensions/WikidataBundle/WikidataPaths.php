<?php
/**
 * Ce fichier permet de charger les différentes extensions nécessaires
 * au foctionnement des liens entre Wikidata et atlasmuseum
 */

/**
 * Definie une valeur par défault en vérifiant qu'elle n'existe pas déjà
 */
function define_value($path_name, $value) {
  if (!defined($path_name)) {
    define($path_name, $value);
    return true;
  }
  else
    return false;
}

/**
 * Main paths
 */

define_value('BASE_AM', 'http://publicartmuseum.net/');
define_value('BASE_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/');
define_value('BASE_FOLDER', 'tmp/w/');
define_value('BASE_WIKI', 'tmp/wiki/');
define_value('BASE_MAIN', 'http://publicartmuseum.net/tmp/w/');
#define_value('BASE_FOLDER', 'w/');
define_value('ATLASMUSEUM_MAIN_PATH', BASE_ROOT . BASE_FOLDER);
define_value('ATLASMUSEUM_MAIN_PATH_2', BASE_MAIN);
//define_value('ATLASMUSEUM_PATH', BASE_MAIN . 'index.php?title=');
define_value('ATLASMUSEUM_PATH', BASE_AM . BASE_WIKI);
//define_value('ATLASMUSEUM_PATH_2', ATLASMUSEUM_MAIN_PATH . 'index.php?title=');
define_value('WIKIDATA_BUNDLE_PATH', 'WikidataBundle/');
define_value('WIKIDATA_BUNDLE_EXTENSIONS_PATH', WIKIDATA_BUNDLE_PATH . 'extensions/');
define_value('ATLASMUSEUM_UTILS_PATH', ATLASMUSEUM_MAIN_PATH . 'extensions/' . WIKIDATA_BUNDLE_PATH . 'utils/');
define_value('ATLASMUSEUM_UTILS_REDUCED_PATH', 'extensions/WikidataBundle/utils/');
define_value('MISSING_IMAGE', BASE_MAIN . 'images/5/5f/Image-manquante.jpg');

/**
 * Files
 */

define_value('COMMONS_BASE', 'https://commons.wikimedia.org/');
define_value('COMMONS_BASE_HTTP', 'http://commons.wikimedia.org/');
define_value('COMMONS_PATH', COMMONS_BASE . 'wiki/');
define_value('COMMONS_BASE_PATH', COMMONS_BASE . 'w/');
define_value('COMMONS_FILE_PATH', COMMONS_BASE_HTTP . 'wiki/Special:FilePath/');
define_value('COMMONS_FILE_PREFIX', 'File:');
define_value('ATLASMUSEUM_FILE_PREFIX', 'Fichier:');
define_value('ATLASMUSEUM_IMAGE_PATH', ATLASMUSEUM_MAIN_PATH . 'image/');

define_value('PICTO_GRIS', BASE_FOLDER + 'images/a/a0/Picto-gris.png');
//define_value('MISSING_IMAGE_THUMB', ATLASMUSEUM_MAIN_PATH . 'images/thumb/5/5f/Image-manquante.jpg/220px-Image-manquante.jpg');
define_value('MISSING_IMAGE_THUMB', BASE_MAIN . 'images/thumb/5/5f/Image-manquante.jpg/220px-Image-manquante.jpg');
//define_value('MISSING_IMAGE_FILE', ATLASMUSEUM_MAIN_PATH . 'images/5/5f/Image-manquante.jpg');
define_value('MISSING_IMAGE_FILE', BASE_MAIN . 'images/5/5f/Image-manquante.jpg');
define_value('MISSING_IMAGE_LINK', ATLASMUSEUM_PATH . ATLASMUSEUM_FILE_PREFIX . 'Image-manquante.jpg');

/**
 * Utils
 */
define_value('ATLASMUSEUM_UTILS_PATH_JS', ATLASMUSEUM_UTILS_PATH . 'js/');
define_value('ATLASMUSEUM_UTILS_FULL_PATH_JS', BASE_MAIN . 'extensions/' . WIKIDATA_BUNDLE_PATH . 'utils/js/');
define_value('ATLASMUSEUM_UTILS_PATH_CSS', ATLASMUSEUM_UTILS_PATH . 'css/');
define_value('ATLASMUSEUM_UTILS_FULL_PATH_CSS', BASE_MAIN . 'extensions/' . WIKIDATA_BUNDLE_PATH . 'utils/css/');
define_value('ATLASMUSEUM_UTILS_PATH_PHP', ATLASMUSEUM_UTILS_PATH . 'php/');
define_value('ATLASMUSEUM_UTILS_REDUCED_PATH_JS', ATLASMUSEUM_UTILS_REDUCED_PATH . 'js/');
define_value('ATLASMUSEUM_UTILS_REDUCED_PATH_CSS', ATLASMUSEUM_UTILS_REDUCED_PATH . 'css/');
define_value('ATLASMUSEUM_UTILS_REDUCED_PATH_PHP', ATLASMUSEUM_UTILS_REDUCED_PATH . 'php/');

/**
 * Open Layers
 */

define_value('OPEN_LAYER_JS', 'https://openlayers.org/en/v4.6.5/build/ol.js');
define_value('OPEN_LAYER_CSS', 'https://openlayers.org/en/v4.6.5/css/ol.css');

/**
 * API
 */

define_value('WIKIDATA_BASE', 'https://www.wikidata.org/');
define_value('WIKIDATA_BASE_HTTP', 'http://www.wikidata.org/');
define_value('WIKIDATA_ENTITY', WIKIDATA_BASE_HTTP . 'entity/');
define_value('WIKIDATA_API', WIKIDATA_BASE . 'w/api.php');
define_value('ATLASMUSEUM_API', ATLASMUSEUM_MAIN_PATH_2 . 'api.php');
define_value('COMMONS_API', COMMONS_BASE_PATH . 'api.php');
define_value('WIKIDATA_SPARQL', 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?');
