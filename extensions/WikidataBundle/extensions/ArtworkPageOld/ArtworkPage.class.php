<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class ArtworkPage {
	/**
	 * Bind the renderArtworkPage function to the <artworkPage> tag
	 * @param Parser $parser
	 * @return bool true
	 */
	public static function init( &$parser ) {
		$parser->setHook( 'artworkPage', array( 'ArtworkPage', 'renderArtworkPage' ) );
		return true;
  }

	/**
	 * Parse the text into proper artworkPage format
	 * @param string $in The text inside the artworkPage tag
	 * @param array $param
	 * @param Parser $parser
	 * @param boolean $frame
	 * @return string
	 */
	public static function renderArtworkPage( $in, $param = array(), $parser = null, $frame = false ) {
    if (!isset($param['full_name'])) {
      $fullName = preg_replace('/^.*\/wiki\//', '', $_SERVER['REQUEST_URI']);
      $fullName = urldecode(str_replace('_', ' ', $fullName));
      $param['full_name'] = $fullName;
    }

    write_log_separator();
    write_log('renderArtworkPage - page: ' . $param['full_name']);

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artwork_old.php');

    return ArtworkOld::renderArtwork($param, false);
	}
}
