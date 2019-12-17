<?php
/*****************************************************************************
 * config.php
 *
 * Fichier de configuration pour l'api
 *****************************************************************************/

/**
 * URLs Wikidata
 */
define('WIKIDATA_BASE', 'https://www.wikidata.org/');
define('WIKIDATA_BASE_HTTP', 'http://www.wikidata.org/');
define('WIKIDATA_ENTITY', WIKIDATA_BASE_HTTP . 'entity/');
define('WIKIDATA_API', WIKIDATA_BASE . 'w/api.php');
define('WIKIDATA_SPARQL', 'https://query.wikidata.org/bigdata/namespace/wdq/sparql');

/**
 * URLs atlasmuseum
 */
define('ATLASMUSEUM_BASE', 'http://publicartmuseum.net/w/');
define('ATLASMUSEUM_API', ATLASMUSEUM_BASE . 'api.php');

/**
 * URLs Wikimedia Commons
 */
define('COMMONS_BASE', 'https://commons.wikimedia.org/w/');
define('COMMONS_API', COMMONS_BASE . 'api.php');

/**
 * Base de données
 */
define('DB_SERVER', "publicarmod1.mysql.db");
define('DB_NAME', "publicarmod1");
define('DB_USER', "publicarmod1");
define('DB_PASSWORD', "1dwy2Myi");

define('DATABASE_PREFIX', 'tmp_');
define('DATABASE_LIBRARY', DATABASE_PREFIX . 'library_2');
define('DATABASE_ARTISTS', DATABASE_PREFIX . 'artists');
define('DATABASE_MAP', DATABASE_PREFIX . 'map');

?>