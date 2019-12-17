<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikidataArtistExport' );
	wfWarn(
		'Deprecated PHP entry point used for WikidataArtistExport extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the WikidataArtistExport extension requires MediaWiki 1.25+' );
}
