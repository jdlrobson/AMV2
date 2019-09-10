<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikidataEditArtist' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikidataEditArtist'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikidataEditArtistAlias'] = __DIR__ . '/WikidataEditArtist.i18n.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for WikidataEditArtist extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the WikidataEditArtist extension requires MediaWiki 1.25+' );
}
