<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'CollectionPage2' );
	return true;
} else {
	die( 'This version of the CollectionPage2 extension requires MediaWiki 1.25+' );
}
