<?php

if ( function_exists( 'wfLoadExtension' ) ) {
  wfLoadExtension( 'EditArtist' );
  /*
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['EditArtist'] = __DIR__ . '/i18n';
  $wgExtensionMessagesFiles['EditArtistAlias'] = __DIR__ . '/EditArtist.i18n.alias.php';
  */
	wfWarn(
		'Deprecated PHP entry point used for EditArtist extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the EditArtist extension requires MediaWiki 1.25+' );
}
