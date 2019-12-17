<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ArtistPage' );
	return true;
} else {
	die( 'This version of the ArtistPage extension requires MediaWiki 1.25+' );
}
