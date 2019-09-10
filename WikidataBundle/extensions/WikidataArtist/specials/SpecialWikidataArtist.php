<?php
/**
 * WikidataArtist SpecialPage for WikidataArtist extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialWikidataArtist extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WikidataArtist' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:WikidataArtist/subpage]].
	 */
	public function execute( $sub ) {
    $request = $this->getRequest();
    $id = preg_replace('/^.*\//', '', $request->getText('title'));

    if (preg_match('/^Q[0-9]+$/', $id)) {
      require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artistPage.php');

      $out = $this->getOutput();

      $out->setPageTitle($id);
      $out->addHTML(Artist::renderArtist(['q' => $id]));
      $out->addHTML('<script>
        document.addEventListener("DOMContentLoaded", function(event) { 
          var body = document.getElementsByTagName("body")[0];
          body.classList.add("wikidata");
        });
      </script>');

    }
	}

	protected function getGroupName() {
		return 'other';
	}
}
