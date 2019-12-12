<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ArtistPage2' );
	return true;
} else {
	die( 'This version of the ArtistPage2 extension requires MediaWiki 1.25+' );
}
