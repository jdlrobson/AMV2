<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ArtworkPage' );
	return true;
} else {
	die( 'This version of the ArtworkPage extension requires MediaWiki 1.25+' );
}
