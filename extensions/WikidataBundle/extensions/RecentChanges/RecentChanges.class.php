<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class RecentChanges {
	/**
	 * Bind the renderRecentChanges function to the <recentChanges> tag
	 * @param Parser $parser
	 * @return bool true
	 */
	public static function init( &$parser ) {
		$parser->setHook( 'recentChanges', array( 'RecentChanges', 'renderRecentChanges' ) );
		return true;
  }

	/**
	 * Parse the text into proper recentChanges format
	 * @param string $in The text inside the recentChanges tag
	 * @param array $param
	 * @param Parser $parser
	 * @param boolean $frame
	 * @return string
	 */
	public static function renderRecentChanges( $in, $param = array(), $parser = null, $frame = false ) {
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );

    $limit = 4;
    // Récupère la limite éventuelle
    if (isset($param['limit']) && is_numeric($param['limit'])) {
      $limit = (int)$param['limit'];
    }

    // Récupère la collection éventuelle
    $collection = null;
    if (isset($param['collection'])) {
      $collection = $param['collection'];
      if (strtoupper($collection) == '{{PAGENAME}}') {
        $collection = $parser->getTitle()->mTextform;
      }
    }

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'recentChanges.php');

    return RecentChangesDisplay::renderRecentChanges($param, $collection, $limit);
	}
}
