<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'CatchAll' );
	return true;
} else {
	die( 'This version of the CatchAll extension requires MediaWiki 1.25+' );
}
