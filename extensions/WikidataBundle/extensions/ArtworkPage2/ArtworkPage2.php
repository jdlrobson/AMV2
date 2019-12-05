<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ArtworkPage2' );
	return true;
} else {
	die( 'This version of the ArtworkPage2 extension requires MediaWiki 1.25+' );
}
