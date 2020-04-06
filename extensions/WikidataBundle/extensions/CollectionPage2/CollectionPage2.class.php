<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class CollectionPage2 {
	/**
	 * Bind the renderCollectionPage2 function to the <collectionPage2> tag
	 * @param Parser $parser
	 * @return bool true
	 */
	public static function init( &$parser ) {
		$parser->setHook( 'collectionPage2', array( 'CollectionPage2', 'renderCollectionPage2' ) );
		return true;
  }

	/**
	 * Parse the text into proper collectionPage2 format
	 * @param string $in The text inside the collectionPage2 tag
	 * @param array $param
	 * @param Parser $parser
	 * @param boolean $frame
	 * @return string
	 */
	public static function renderCollectionPage2( $in, $param = array(), $parser = null, $frame = false ) {
    $article = null;
    if (!isset($param['full_name'])) {
      $fullName = preg_replace('/^.*\/wiki\//', '', $_SERVER['REQUEST_URI']);
      $fullName = urldecode(str_replace('_', ' ', $fullName));
      $article = $fullName;
    } else
      $article = $param['full_name'];

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'collection2.php');

    return Collection2::renderCollection($article);
	}
}
