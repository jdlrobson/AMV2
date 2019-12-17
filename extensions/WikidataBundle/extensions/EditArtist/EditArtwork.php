<?php

if ( function_exists( 'wfLoadExtension' ) ) {
  wfLoadExtension( 'EditArtwork' );
	wfWarn(
		'Deprecated PHP entry point used for EditArtwork extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the EditArtwork extension requires MediaWiki 1.25+' );
}
