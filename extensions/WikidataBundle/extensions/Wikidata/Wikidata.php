<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Wikidata' );
	wfWarn(
		'Deprecated PHP entry point used for Wikidata extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the Wikidata extension requires MediaWiki 1.25+' );
}
