<?php
/**
 * WikidataEditArtist SpecialPage for WikidataEditArtist extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialWikidataEditArtist extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WikidataEditArtist' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:WikidataEditArtist/subpage]].
	 */
	public function execute( $sub ) {
    require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artistEdit.php');
    $out = $this->getOutput();
    $request = $this->getRequest();

    // Récupère le titre ou l'id éventuellement passé en paramètre
    $id = preg_replace('/^.*\//', '', $request->getText('title'));

    if ($id != $request->getText('title')) {      
      if (preg_match('/^Q[0-9]+$/', $id)) {
        // Provenance : Wikidata
        require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'updateDB.php');
        $article = get_artist_from_q($id);

        if ($article == '') {
          $out->setPageTitle('Importer : ' . $id);
          $out->addHTML('<script>
            document.addEventListener("DOMContentLoaded", function(event) { 
              var body = document.getElementsByTagName("body")[0];
              body.classList.add("wikidata");
            });
          </script>');
        } else {
          $out->setPageTitle('Modifier : ' . $article);
        }
      } else {
        // Provenance : atlas museum
        $id = str_replace('_', ' ', $id);
        $out->setPageTitle('Modifier : ' . $id);
      }
    } else {
      // Création
      $id = '';
      $out->setPageTitle('Ajouter une œuvre');
    }

    $out->addHTML(artistEdit::renderEdit($id));
	}

	protected function getGroupName() {
		return 'other';
	}
}
