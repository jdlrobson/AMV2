<?php
/**
 * WikidataEditCollection SpecialPage for WikidataEditCollection extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialWikidataEditCollection extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WikidataEditCollection' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:WikidataEditCollection/subpage]].
	 */
	public function execute( $sub ) {
    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'collectionEdit.php');
    $out = $this->getOutput();
    $request = $this->getRequest();

    // Récupère le titre ou l'id éventuellement passé en paramètre
    $id = preg_replace('/^.*Spécial:WikidataEditCollection\//', '', $request->getText('title'));

    if ($id != $request->getText('title')) {      
      $id = str_replace('_', ' ', $id);
      $out->setPageTitle('Modifier : ' . $id);
    } else {
      // Création
      $id = '';
      $out->setPageTitle('Ajouter une collection');
    }

    $out->addHTML(collectionEdit::renderEdit($id));
	}

	protected function getGroupName() {
		return 'other';
	}
}
