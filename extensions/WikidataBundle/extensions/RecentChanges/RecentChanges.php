<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'RecentChanges' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['RecentChanges'] = __DIR__ . '/i18n';
	/* wfWarn(
		'Deprecated PHP entry point used for RecentChanges extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return true;
} else {
	die( 'This version of the RecentChanges extension requires MediaWiki 1.25+' );
}
