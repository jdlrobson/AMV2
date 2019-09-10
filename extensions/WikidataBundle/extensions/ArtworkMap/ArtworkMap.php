<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ArtworkMap' );
	$wgMessagesDirs['ArtworkMap'] = __DIR__ . '/i18n';
	return true;
} else {
	die( 'This version of the ArtworkMap extension requires MediaWiki 1.25+' );
}
