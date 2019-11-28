<?php
/**
 * Ce fichier permet de charger les différentes extensions nécessaires
 * au foctionnement des liens entre Wikidata et atlasmuseum
 */

require_once('WikidataPaths.php');

/**
 * Extensions
 */


wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'ArtworkMap'); // Carte des œuvres
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'ArtworkPage'); // Page d'œuvre
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'ArtistPage'); // Page d'artiste
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'CollectionPage'); // Page d'une collection
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'RecentChanges'); // Modifications récentes
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'Wikidata'); // Page d'une œuvre provenant de Wikidata
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'WikidataArtist'); // Page d'un artiste provenant de Wikidata
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'WikidataEdit'); // Édition d'une œuvre
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'WikidataEditArtist'); // Édition d'un artiste
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'WikidataEditCollection'); // Édition d'une collection
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'WikidataExport'); // Export d'une œuvre
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'WikidataArtistExport'); // Export d'un artiste
wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'WikidataQuery'); // Page de requête

wfLoadExtension(WIKIDATA_BUNDLE_EXTENSIONS_PATH . 'CatchAll'); // Test
