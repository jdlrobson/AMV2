<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class ArtworkMap {
	/**
	 * Bind the renderArtworkMap function to the <artworkMap> tag
	 * @param Parser $parser
	 * @return bool true
	 */
	public static function init( &$parser ) {
		$parser->setHook( 'artworkMap', array( 'ArtworkMap', 'renderArtworkMap' ) );
		return true;
  }

	/**
	 * Parse the text into proper artworkMap format
	 * @param string $in The text inside the artworkMap tag
	 * @param array $param
	 * @param Parser $parser
	 * @param boolean $frame
	 * @return string
	 */
	public static function renderArtworkMap( $in, $param = array(), $parser = null, $frame = false ) {
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artworkMap.php');

    return GlobalArtworkMap::renderArtworkMap($param, false);
	}
}
