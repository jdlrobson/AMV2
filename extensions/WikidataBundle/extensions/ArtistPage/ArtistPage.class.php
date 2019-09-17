<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class ArtistPage {
	/**
	 * Bind the renderArtistPage function to the <artistPage> tag
	 * @param Parser $parser
	 * @return bool true
	 */
	public static function init( &$parser ) {
		$parser->setHook( 'artistPage', array( 'ArtistPage', 'renderArtistPage' ) );
		return true;
  }

	/**
	 * Parse the text into proper artistPage format
	 * @param string $in The text inside the artistPage tag
	 * @param array $param
	 * @param Parser $parser
	 * @param boolean $frame
	 * @return string
	 */
	public static function renderArtistPage( $in, $param = array(), $parser = null, $frame = false ) {
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );
    if (!isset($param['full_name'])) {
      $fullName = preg_replace('/^.*\/wiki\//', '', $_SERVER['REQUEST_URI']);
      $fullName = urldecode(str_replace('_', ' ', $fullName));
      $param['full_name'] = $fullName;
    }

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artistPage.php');

    return Artist::renderArtist($param, false);
	}
}
