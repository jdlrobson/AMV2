<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class CollectionPage {
	/**
	 * Bind the renderCollectionPage function to the <collectionPage> tag
	 * @param Parser $parser
	 * @return bool true
	 */
	public static function init( &$parser ) {
		$parser->setHook( 'collectionPage', array( 'CollectionPage', 'renderCollectionPage' ) );
		return true;
  }

	/**
	 * Parse the text into proper collectionPage format
	 * @param string $in The text inside the collectionPage tag
	 * @param array $param
	 * @param Parser $parser
	 * @param boolean $frame
	 * @return string
	 */
	public static function renderCollectionPage( $in, $param = array(), $parser = null, $frame = false ) {
    $attribs = Sanitizer::validateTagAttributes( $param, 'div' );

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'collection.php');

    return Collection::renderCollection($param, false);
	}
}
