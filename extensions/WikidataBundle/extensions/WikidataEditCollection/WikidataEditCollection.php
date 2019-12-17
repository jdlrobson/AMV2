<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikidataEditCollection' );
	wfWarn(
		'Deprecated PHP entry point used for WikidataEditCollection extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the WikidataEditCollection extension requires MediaWiki 1.25+' );
}
