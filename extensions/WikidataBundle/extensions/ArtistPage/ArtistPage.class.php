<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class ArtistPage {
	/**
	 * Bind the renderArtistPage function to the <ArtistPage> tag
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
    $article = null;
    if (!isset($param['full_name'])) {
      $fullName = preg_replace('/^.*\/wiki\//', '', $_SERVER['REQUEST_URI']);
      $fullName = urldecode(str_replace('_', ' ', $fullName));
      $article = $fullName;
    } else
      $article = $param['full_name'];

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artist.php');

    return Artist::renderArtist($article);
	}
}
