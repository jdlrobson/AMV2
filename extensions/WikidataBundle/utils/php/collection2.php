<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class Collection2 {
  /**
   * En-tête
   */
  protected static function renderHeader($entity) {
  }

  /**
   * Pied de page (inclusion des fichiers .js et .css)
   */
  protected static function renderFooter() {
    ?>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery.min.js"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
      <script src="<?php print OPEN_LAYER_JS; ?>"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>collection.js"></script>
      <link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
      <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>map.css" type="text/css">
    <?php
  }

  protected static function renderDescription($data) {
    if (is_null($data))
      return;

    print '<div class="description">';
    for ($i = 0; $i < sizeof($data->value); $i++)
      print API::convert_to_wiki_text($data->value[$i]);
    print '</div>';
  }

  protected static function renderInstitution($data) {
    if (is_null($data))
      return;

    print '<div class="institution">';
    for ($i = 0; $i < sizeof($data->value); $i++)
      print API::convert_to_wiki_text($data->value[$i]);
    print '</div>';
  }

  protected static function renderMap($article) {
    if (is_null($article))
      return;
    
    ?>
      <div class="mapCtnr dalm">

      <div id="map" style="height:400px">
        <div id="map-loader">
          <div id="map-loader-text">
            Veuillez attendre le chargement de la carte
          </div>
          <div id="map-loader-disks">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
      </div>
      <div id="map-popup" class="ol-popup map-popup">
        <a href="#" id="map-popup-closer" class="ol-popup-closer map-popup-closer"></a>
        <div id="map-popup-content" class="map-popup-content"></div>
      </div>
      <div class="mapLgd">
        <table>
          <tbody>
            <tr>
              <td><div class="mapLgdInput"><input type="checkbox" id="checkbox-perenne" class="map-checkbox" onclick="changeMarkers()" checked disabled><label for="checkbox-perenne"><span class="imgWrapper"><img alt="Picto-gris.png" src="http://atlasmuseum.net/w/images/a/a0/Picto-gris.png" width="48" height="48"></span> œuvres pérennes</label></div></td>
              <td><div class="mapLgdInput"><input type="checkbox" id="checkbox-ephemere" class="map-checkbox" onclick="changeMarkers()" checked disabled><label for="checkbox-ephemere"><span class="imgWrapper"><img alt="Picto-jaune.png" src="http://atlasmuseum.net/w/images/4/49/Picto-jaune.png" width="48" height="48"></span> œuvres éphémères</label></div></td>
              <td><div class="mapLgdInput"><input type="checkbox" id="checkbox-detruite" class="map-checkbox" onclick="changeMarkers()" checked disabled><label for="checkbox-detruite"><span class="imgWrapper"><img alt="Picto-rouge.png" src="http://atlasmuseum.net/w/images/a/a8/Picto-rouge.png" width="24" height="24"></span> œuvres détruites</label></div></td>
            </tr>
            <tr>
              <td><div class="mapLgdInput"><input type="checkbox" id="checkbox-verifier" class="map-checkbox" onclick="changeMarkers()" checked disabled><label for="checkbox-verifier"><span class="imgWrapper"><img alt="Picto-bleu.png" src="http://atlasmuseum.net/w/images/9/90/Picto-bleu.png" width="32" height="32"></span> œuvres à vérifier</label></div></td>
              <td><div class="mapLgdInput"><input type="checkbox" id="checkbox-non-realisee" class="map-checkbox" onclick="changeMarkers()" checked disabled><label for="checkbox-non-realisee"><span class="imgWrapper"><img alt="Picto-blanc.png" src="http://atlasmuseum.net/w/images/2/2d/Picto-blanc.png" width="32" height="32"></span> œuvres non réalisées</label></div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <?php
  }

  protected static function renderRecentChanges($data) {
    if (is_null($data))
      return;

    ?>
    <div class="homeCtnr dalm">
      <div class="atmslideshowCtnr">
        <div class="atmslideshowHead"><h3> <span class="mw-headline" id="Contributions_les_plus_r.C3.A9centes">Contributions les plus récentes</span></h3></div>
          <ul>
          </ul>
        </div>
      </div>
    </div>
    <?php
  }

  protected static function renderList($data) {
    if (is_null($data))
      return;

    ?>
    <div class="homeCtnr dalm">
      <h3><span class="mw-headline" id="Les_.C5.93uvres_de_la_collection">Les œuvres de la collection</span></h3>
      <table id="collectionTable" class="sortable wikitable smwtable jquery-tablesorter" width="100%">
        <thead>
          <tr>
            <th class="Titre-de-l'œuvre headerSort" tabindex="0" role="columnheader button" title="Tri croissant"><a href="Attribut:Titre" title="Attribut:Titre">Titre de l'œuvre</a></th>
            <th class="Artiste headerSort" tabindex="0" role="columnheader button" title="Tri croissant"><a href="Attribut:Auteur" title="Attribut:Auteur">Artiste</a></th>
            <th class="Date headerSort" tabindex="0" role="columnheader button" title="Tri croissant"><a href="Attribut:Date_d%27inauguration" title="Attribut:Date d'inauguration">Date</a></th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <?php
  }

  /**
   * Écriture d'une collection
   */
  protected static function renderEntity($entity) {
    ob_start();
    
    // En-tête
    self::renderHeader($entity);

    // Description
    self::renderDescription($entity->data->description);

    // Institution
    self::renderInstitution($entity->data->institution);

    // Map
    self::renderMap($entity->data->artworks);

    // Modifications récentes
    self::renderRecentChanges($entity->data->artworks);

    // Liste
    self::renderList($entity->data->artworks);

    // Pied de page
    self::renderFooter();

    $contents = ob_get_contents();
    ob_end_clean();

    return $contents;
  }

  /**
   * Écriture si erreur
   */
  protected static function renderError($article) {
    $link = 'http://atlasmuseum.net/w/index.php?title=' . urlencode($article) . '&action=purge';

    $text = '<div style="margin-bottom: 20px;">Erreur lors de la récupération des données...</div>';
    $text .= '<div><button onclick="window.location.href=\'' . $link . '\';">Recharger la page</button></div>';

    return $text;
  }

  /**
   * Rendu d'une collection
   */
  public static function renderCollection($article) {
    if (is_null($article))
      return '';

    // Récupération des données de la collection
    $parameters = [
      'action' => 'amgetcollection',
      'collection' => $article
    ];
    $data = API::call_api($parameters, 'am');

    if ($data->success === 1) {
      // Collection ok
      $contents = self::renderEntity($data->entities);
    } else {
      // Problème de données
      $contents = self::renderError($article);
    }

    return preg_replace("/\r|\n/", "", $contents);
  }

}
