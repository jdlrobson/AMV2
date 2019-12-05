<?php
/**
 * Wikidata SpecialPage for Wikidata extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialWikidata extends SpecialPage {
	public function __construct() {
		parent::__construct( 'Wikidata' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:Wikidata/subpage]].
	 */
	public function execute( $sub ) {
    $request = $this->getRequest();
    $id = preg_replace('/^.*\//', '', $request->getText('title'));

    if (preg_match('/^Q[0-9]+$/', $id)) {
      require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artwork_old.php');

      $out = $this->getOutput();

      $out->setPageTitle($id);
      $out->addHTML(ArtworkOld::renderArtwork(['q' => $id]));
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
