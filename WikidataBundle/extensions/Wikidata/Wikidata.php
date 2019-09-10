<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Wikidata' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Wikidata'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikidataAlias'] = __DIR__ . '/Wikidata.i18n.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for Wikidata extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the Wikidata extension requires MediaWiki 1.25+' );
}
