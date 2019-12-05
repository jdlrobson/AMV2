<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class ArtworkPage2 {
	/**
	 * Bind the renderArtworkPage2 function to the <artworkPage2> tag
	 * @param Parser $parser
	 * @return bool true
	 */
	public static function init( &$parser ) {
		$parser->setHook( 'artworkPage2', array( 'ArtworkPage2', 'renderArtworkPage2' ) );
		return true;
  }

	/**
	 * Parse the text into proper artworkPage2 format
	 * @param string $in The text inside the artworkPage2 tag
	 * @param array $param
	 * @param Parser $parser
	 * @param boolean $frame
	 * @return string
	 */
	public static function renderArtworkPage2( $in, $param = array(), $parser = null, $frame = false ) {
    if (!isset($param['full_name'])) {
      $fullName = preg_replace('/^.*\/wiki\//', '', $_SERVER['REQUEST_URI']);
      $fullName = urldecode(str_replace('_', ' ', $fullName));
      $param['full_name'] = $fullName;
    }

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'testing.php');

    return Testing::renderTesting($param);
	}
}
