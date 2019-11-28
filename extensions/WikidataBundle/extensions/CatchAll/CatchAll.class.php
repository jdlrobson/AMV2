<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class CatchAll {
	/**
	 * Bind the renderCatchAll function to the <catchAll> tag
	 * @param Parser $parser
	 * @return bool true
	 */
	public static function init( &$parser ) {
		$parser->setHook( 'catchAll', array( 'CatchAll', 'renderCatchAll' ) );
		return true;
  }

	/**
	 * Parse the text into proper catchAll format
	 * @param string $in The text inside the catchAll tag
	 * @param array $param
	 * @param Parser $parser
	 * @param boolean $frame
	 * @return string
	 */
	public static function renderCatchAll( $in, $param = array(), $parser = null, $frame = false ) {
    if (!isset($param['full_name'])) {
      $fullName = preg_replace('/^.*\/wiki\//', '', $_SERVER['REQUEST_URI']);
      $fullName = urldecode(str_replace('_', ' ', $fullName));
      $param['full_name'] = $fullName;
    }

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'testing.php');

    return Testing::renderTesting($param);
	}
}
