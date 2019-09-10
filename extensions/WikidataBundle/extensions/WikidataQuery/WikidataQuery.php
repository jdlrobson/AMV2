<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikidataQuery' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikidataQuery'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikidataQueryAlias'] = __DIR__ . '/WikidataQuery.i18n.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for WikidataQuery extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the WikidataQuery extension requires MediaWiki 1.25+' );
}
