<?php
/**
 * WikidataQuery SpecialPage for WikidataQuery extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialWikidataQuery extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WikidataQuery' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:WikidataQuery/subpage]].
	 */
	public function execute( $sub ) {
    $request = $this->getRequest();
    //$id = preg_replace('/^.*\//', '', $request->getText('title'));

    //if (!preg_match('/^Q[0-9]+$/', $id)) {
      require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'wikidataQuery.php');

      $out = $this->getOutput();
      $out->setPageTitle('Requête Wikidata');
      $out->addHTML(WikidataQuery::renderQuery());
    //}
	}

	protected function getGroupName() {
		return 'other';
	}
}
