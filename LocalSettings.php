<?php
# This file was automatically generated by the MediaWiki 1.26.4
# installer. If you make manual changes, please keep track in case you
# need to recreate them later.
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# https://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename = "atlasmuseum";
$wgMetaNamespace = "Atlasmuseum";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
#$wgScriptPath = "/tmp/w";
#$wgScriptPath = "/tmp/w_old";
$wgScriptPath = "/w";
#$wgScriptExtension = ".php";
#$wgArticlePath = "/tmp/wiki/$1";
$wgArticlePath = "/wiki/$1";
$wgUsePathInfo = true;

## The protocol and server name to use in fully-qualified URLs
$wgServer = "http://publicartmuseum.net";


## The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

## The URL path to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogo = "$wgResourceBasePath/resources/assets/wiki.png";

## UPO means: this is also a user preference option

$wgEnableEmail = true;
$wgEnableUserEmail = true; # UPO

$wgEmergencyContact = "apache@publicartmuseum.net";
$wgPasswordSender = "apache@publicartmuseum.net";

$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = false; # UPO
$wgEmailAuthentication = true;

## Database settings
$wgDBtype = "mysql";
$wgDBserver = "publicarmod1.mysql.db";
$wgDBname = "publicarmod1";
$wgDBuser = "publicarmod1";
$wgDBpassword = "1dwy2Myi";

# MySQL specific settings
$wgDBprefix = "tmp_";

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Experimental charset support for MySQL 5.0.
$wgDBmysql5 = false;

## Shared memory settings
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = array();

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads = true;
$wgUseImageMagick = false;
$wgImageMagickConvertCommand = "/usr/bin/convert";

# InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = false;

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "en_US.utf8";

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
#$wgHashedUploadDirectory = false;

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publically accessible from the web.
$wgCacheDirectory = "$IP/cache";

# Site language code, should be one of the list in ./languages/Names.php
$wgLanguageCode = "fr";

$wgSecretKey = "f28f49c06a98a5366c5fe361d9e9b87c1619f5d8f1c80aa615bbf91c9f9a5c1f";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = "38e470d9ee620abb";

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "https://creativecommons.org/licenses/by-sa/3.0/";
$wgRightsText = "Creative Commons attribution partage à l'identique";
$wgRightsIcon = "$wgResourceBasePath/resources/assets/licenses/cc-by-sa.png";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'vector', 'monobook':
#$wgDefaultSkin = "vector";
$wgDefaultSkin = "atlasmuseum";

# Enabled skins.
# The following skins were automatically enabled:
wfLoadSkin( 'Vector' );
wfLoadSkin( 'AtlasMuseum' );

# Enabled Extensions. Most extensions are enabled by including the base extension file here
# but check specific extension documentation for more details
# The following extensions were automatically enabled:
wfLoadExtension( 'Cite' );
wfLoadExtension( 'Gadgets' );
wfLoadExtension( 'Nuke' );
wfLoadExtension( 'ParserFunctions' );
wfLoadExtension( 'Poem' );
wfLoadExtension( 'Renameuser' );
wfLoadExtension( 'SpamBlacklist' );
wfLoadExtension( 'WikiEditor' );

# Extensions permettant le lien entre Wikidata et atlasmuseum

require_once( "$IP/extensions/WikidataBundle/WikidataBundle.php" );

# End of automatically generated settings.
# Add more configuration options below.

require_once( "$IP/extensions/SemanticBundle/SemanticBundleSettings.php" );
require_once( "$IP/extensions/SemanticBundle/SemanticBundle.php" );


#$wgEnableWikibaseRepo = true;
#$wgEnableWikibaseClient = true;
#require_once "$IP/extensions/Wikibase/repo/Wikibase.php";
#require_once "$IP/extensions/Wikibase/repo/ExampleSettings.php";
#require_once "$IP/extensions/Wikibase/client/WikibaseClient.php";
#require_once "$IP/extensions/Wikibase/client/ExampleSettings.php";

//$wgGenerateThumbnailOnParse = true;
$wgGenerateThumbnailOnParse = true;

$wgJobRunRate = 10000;

$wgFavicon = "http://publicartmuseum.net/w/skins/common/images/favicon.ico";

$wgMaxShellMemory = 100524288;
$wgMemoryLimit = "64M";
$wgMaxImageArea = 10e7;

$wgGroupPermissions['autoconfirmed']['upload_by_url'] = true;
$wgAllowCopyUploads = true;
$wgCopyUploadsFromSpecialUpload = true;

wfLoadExtension( 'PageForms' );
