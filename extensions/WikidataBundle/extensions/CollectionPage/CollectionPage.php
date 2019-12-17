<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'CollectionPage' );
	return true;
} else {
	die( 'This version of the CollectionPage extension requires MediaWiki 1.25+' );
}
