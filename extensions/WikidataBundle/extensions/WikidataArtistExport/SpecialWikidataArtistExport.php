<?php
/**
 * WikidataArtistExport SpecialPage for WikidataArtistExport extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialWikidataArtistExport extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WikidataArtistExport' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:WikidataArtistExport/subpage]].
	 */
	public function execute( $sub ) {
    $request = $this->getRequest();
    $id = preg_replace('/^.*\//', '', $request->getText('title'));

    if (!preg_match('/^Q[0-9]+$/', $id)) {
      require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artistExport.php');

      $out = $this->getOutput();
      $out->setPageTitle('Exporter : ' . str_replace('_', ' ', $id));
      $out->addHTML(ArtistExport::renderExport($id));
    }
	}

	protected function getGroupName() {
		return 'other';
	}
}
