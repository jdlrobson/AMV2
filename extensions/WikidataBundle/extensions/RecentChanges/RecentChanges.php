<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'RecentChanges' );
	return true;
} else {
	die( 'This version of the RecentChanges extension requires MediaWiki 1.25+' );
}
