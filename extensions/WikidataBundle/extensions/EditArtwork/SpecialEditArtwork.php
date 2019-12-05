<?php
/**
 * EditArtwork SpecialPage for EditArtwork extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialEditArtwork extends SpecialPage {
	public function __construct() {
		parent::__construct( 'EditArtwork' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:EditArtwork/subpage]].
	 */
	public function execute( $sub ) {

    $request = $this->getRequest();
    // Récupère le titre ou l'id éventuellement passé en paramètre
    $id = preg_replace('/^.*Spécial:EditArtwork\//', '', $request->getText('title'));

    // Si l'id est identique au titre, c'est qu'il n'y en a en fait aucun
    if ($id == $request->getText('title'))
      $id = null;

    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artworkEditTest.php');

    $out = $this->getOutput();
    $data = artworkEditTest::renderEdit($id);
    $out->setPageTitle($data['title']);
    $out->addHTML($data['content']);
	}

	protected function getGroupName() {
		return 'other';
	}
}
