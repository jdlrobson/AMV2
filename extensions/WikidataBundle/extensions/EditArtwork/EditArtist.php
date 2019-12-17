<?php

if ( function_exists( 'wfLoadExtension' ) ) {
  wfLoadExtension( 'EditArtist' );
	wfWarn(
		'Deprecated PHP entry point used for EditArtist extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the EditArtist extension requires MediaWiki 1.25+' );
}
