<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikidataExport' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikidataExport'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikidataExportAlias'] = __DIR__ . '/WikidataExport.i18n.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for WikidataExport extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the WikidataExport extension requires MediaWiki 1.25+' );
}
