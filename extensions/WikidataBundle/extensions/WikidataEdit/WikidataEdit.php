<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikidataEdit' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikidataEdit'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikidataEditAlias'] = __DIR__ . '/WikidataEdit.i18n.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for WikidataEdit extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the WikidataEdit extension requires MediaWiki 1.25+' );
}
