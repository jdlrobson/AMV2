<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class Artist {
  /**
   * En-tête (titre de l'œuvre si provenant de Wikidata, bouton d'import / export)
   */
  protected static function renderHeader($entity) {
    if ($entity->origin === 'wikidata') {
      // Mise à jour du titre de la page avec le titre de l'œuvre, si elle existe
      if (!is_null($entity) && !is_null($entity->data) && !is_null($entity->data->titre)) {
        $artistTitle = $entity->data->titre->value[0];
        ?>
          <script>document.getElementById('firstHeading').getElementsByTagName('span')[0].textContent = "<?php print $artistTitle; ?>"</script>
        <?php
      }

      // Affichage "Importer cette notice..."
      ?>
      <div class="import">
        <a href="<?php print ATLASMUSEUM_PATH; ?>Spécial:EditArtist/<?php print $entity->article; ?>">
          <img src="http://publicartmuseum.net/w/skins/AtlasMuseum/resources/images/hmodify.png" />Importer cette notice dans atlasmuseum
        </a>
      </div>
      <?php
    } else {
      // Affichage "Exporter cette notice..."
      ?>
      <div class="import">
        <a href="<?php print ATLASMUSEUM_PATH; ?>Spécial:WikidataArtistExport/<?php print $entity->article; ?>">
          <img src="http://publicartmuseum.net/w/skins/AtlasMuseum/resources/images/hmodify.png" />Exporter cette notice sur Wikidata
        </a>
      </div>
      <?php
    }
  }

  /**
   * Pied de page (inclusion des fichiers .js et .css)
   */
  protected static function renderFooter() {
    ?>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>artist.js"></script>
      <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>artwork.css">
    <?php
  }

  /**
   * Écriture de l'image
   */
  protected static function renderImage($image) {
    $imageUrl = MISSING_IMAGE_LINK;
    $imageThumbnail = MISSING_IMAGE_FILE;

    if (!is_null($image)) {
      $imageUrl = $image->value[0]->url;
      $imageThumbnail = $image->value[0]->thumbnail;
    }

    print '<a href="' . $imageUrl . '" class="image">';
    print '<img alt="" src="' . $imageThumbnail . '" style="width:auto;max-width:420px;max-height:300px" class="thumbimage" srcset="" />';
    print '</a>';
  }

  /**
   * Ligne standard
   */
  protected static function renderLine($titleSingular, $titlePlural, $data, $twoLines = false) {
    if (!is_null($data)) {
      $text = [];
      for ($i=0; $i < sizeof($data->value) ; $i++) {
        if ($data->type === 'text' || $data->type === 'date')
          array_push($text, $data->value[$i]);
        else
        if ($data->type === 'item')
          array_push($text, $data->value[$i]->label);
      }

      print '<tr>';
      print ($twoLines ? '<td colspan="2"><b>' : '<th>');
      print (sizeof($text) > 1 ? $titleSingular : $titleSingular);
      print ($twoLines ? '</b><br />' : '</th><td>');
      print implode(', ', $text);
      print '</td></tr>';
    }
  }

  protected static function renderWikidataLink($q) {
    if (!is_null($q)) {
      print '<div class="wikidataLink"><a href="https://www.wikidata.org/wiki/' . $q->value[0] . '" target="_blank"><img src="http://publicartmuseum.net/w/skins/AtlasMuseum/resources/hwikidata.png" /><span>Voir cette notice sur Wikidata</span></a></div>';
    }
  }

  protected static function renderArtworks($artworks) {
    if (is_null($artworks))
      return;

    $titles = [];
    for ($i = 0; $i < sizeof($artworks->entities); $i++) {
      if ($artworks->entities[$i]->origin === 'atlasmuseum') {
        array_push($titles, '<a href="http://publicartmuseum.net/wiki/'
          . urlencode(str_replace(' ', '_', $artworks->entities[$i]->article))
          . '">'
          . $artworks->entities[$i]->titre
          . '</a>'
        );
      } else
      if ($artworks->entities[$i]->origin === 'wikidata') {
        array_push($titles, '<a href="http://publicartmuseum.net/wiki/Spécial:Wikidata/'
          . $artworks->entities[$i]->article
          . '">'
          . $artworks->entities[$i]->titre
          . '</a>'
        );
      }
    }

    if (sizeof($titles) > 0) {
      print '<tr><th>Œuvres</th><td>' . join($titles, ', ') . '</td></tr>';
    }
  }

  /**
   * Écriture d'un artiste
   */
  protected static function renderEntity($entity, $artworks) {
    ob_start();
    
    // En-tête
    self::renderHeader($entity);

    print '<div class="pageArtiste">';

    self::renderImage($entity->data->thumbnail);

    print '<table class="wikitable">';

    self::renderLine('Nom', 'Noms', $entity->data->nom);
    self::renderLine('Prénom', 'Prénoms', $entity->data->prenom);
    self::renderLine('Résumé', 'Résumé', $entity->data->abstract);
    self::renderLine('Date de naissance', 'Date de naissance', $entity->data->dateofbirth);
    self::renderLine('Lieu de naissance', 'Lieu de naissance', $entity->data->birthplace);
    self::renderLine('Date de décès', 'Date de décès', $entity->data->deathdate);
    self::renderLine('Lieu de décès', 'Lieu de décès', $entity->data->deathplace);
    self::renderLine('Pays de nationalité', 'Pays de nationalité', $entity->data->nationality);
    self::renderLine('Mouvement', 'Mouvements', $entity->data->movement);
    self::renderLine('Société de gestion  des droits d\'auteur', 'Société de gestion  des droits d\'auteur', $entity->data->societe_gestion_droit_auteur);
    self::renderLine('Nom de la société de gestion  des droits d\'auteur', 'Nom de la société de gestion  des droits d\'auteur', $entity->data->nom_societe_gestion_droit_auteur);
    self::renderArtworks($artworks);

    print '</table>';

    // Lien Wikidata
    self::renderWikidataLink($entity->data->wikidata);

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

    $link = 'http://publicartmuseum.net/w/index.php?title=' . urlencode($article) . '&action=purge';

    $text = '<div style="margin-bottom: 20px;">Erreur lors de la récupération des données...</div>';
    $text .= '<div><button onclick="window.location.href=\'' . $link . '\';">Recharger la page</button></div>';

    return $text;
  }

  /**
   * Rendu d'un artiste
   */
  public static function renderArtist($article) {
    if (is_null($article))
      return '';

    // Récupération des données de l'artiste
    $parameters = [
      'action' => 'amgetartist',
      'article' => $article
    ];
    $data = API::call_api($parameters, 'am');

    if ($data->success === 1) {
      // Artiste ok

      // Récupération des œuvres de l'artiste
      $artworksParameters = [
        'action' => 'amgetartworksbyartists',
        'artists' => $data->entities->article
      ];
      $artworks = API::call_api($artworksParameters, 'am');
      $contents = self::renderEntity($data->entities, $artworks->success === 1 ? $artworks : null);
    } else {
      // Problème de données
      $contents = self::renderError($article);
    }

    return preg_replace("/\r|\n/", "", $contents);
  }

}
