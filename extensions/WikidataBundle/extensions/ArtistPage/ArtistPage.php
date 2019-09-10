<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ArtistPage' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['ArtistPage'] = __DIR__ . '/i18n';
	/* wfWarn(
		'Deprecated PHP entry point used for ArtistPage extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return true;
} else {
	die( 'This version of the ArtistPage extension requires MediaWiki 1.25+' );
}
