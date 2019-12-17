<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ArtworkMap' );
	return true;
} else {
	die( 'This version of the ArtworkMap extension requires MediaWiki 1.25+' );
}
