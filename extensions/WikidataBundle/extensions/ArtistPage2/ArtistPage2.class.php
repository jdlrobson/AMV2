<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class ArtistPage2 {
	/**
	 * Bind the renderArtistPage2 function to the <ArtistPage2> tag
	 * @param Parser $parser
	 * @return bool true
	 */
	public static function init( &$parser ) {
		$parser->setHook( 'artistPage2', array( 'ArtistPage2', 'renderArtistPage2' ) );
		return true;
  }

	/**
	 * Parse the text into proper artistPage2 format
	 * @param string $in The text inside the artistPage2 tag
	 * @param array $param
	 * @param Parser $parser
	 * @param boolean $frame
	 * @return string
	 */
	public static function renderArtistPage2( $in, $param = array(), $parser = null, $frame = false ) {
    $article = null;
    if (!isset($param['full_name'])) {
      $fullName = preg_replace('/^.*\/wiki\//', '', $_SERVER['REQUEST_URI']);
      $fullName = urldecode(str_replace('_', ' ', $fullName));
      $article = $fullName;
    } else
      $article = $param['full_name'];

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artist2.php');

    return Artist2::renderArtist($article);
	}
}
