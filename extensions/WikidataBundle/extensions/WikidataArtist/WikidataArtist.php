<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikidataArtist' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikidataArtist'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikidataArtistAlias'] = __DIR__ . '/WikidataArtist.i18n.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for WikidataArtist extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the WikidataArtist extension requires MediaWiki 1.25+' );
}
