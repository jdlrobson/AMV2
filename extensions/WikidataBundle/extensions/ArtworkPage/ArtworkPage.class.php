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
    $article = null;
    if (!isset($param['full_name'])) {
      $fullName = '';
      if (strpos($_SERVER['REQUEST_URI'], '/w/index.php?') !== false) {
        $params = explode('&', preg_replace('/^.*\?/', '', $_SERVER['REQUEST_URI']));
        for ($i = 0; $i < sizeof($params); $i++) {
          $data = explode('=', $params[$i]);
          if (strtolower($data[0]) === 'title')
            $fullName = $data[1];
        }
      } else {
        $fullName = preg_replace('/^.*\/wiki\//', '', $_SERVER['REQUEST_URI']);
        $fullName = urldecode(str_replace('_', ' ', $fullName));
      }
      $article = $fullName;
    } else
      $article = $param['full_name'];

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artwork.php');

    return Artwork::renderArtwork($article);
	}
}
