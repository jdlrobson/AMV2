<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artworkGetData.php');

class ArtworkExport {

  public static function exportItem($id, $data, $property, $source='') {
    $text = '';

    if (!is_null($data)) {
      foreach($data->value as $item) {
        if ($item->origin == 'wikidata') {
          $text .= $id . "\t" . $property . "\t" . $item->article;
          if ($source != '')
            $text .= "\t" . $source;
          $text .= "\n";
        }
      }
    }

    return $text;
  }

  public static function exportNumber($id, $data, $property, $source='') {
    $text = '';

    if (!is_null($data)) {
      foreach($data->value as $value) {
        $text .= $id . "\t" . $property . "\t" . $value;
        if ($source != '')
          $text .= "\t" . $source;
        $text .= "\n";
      }
    }

    return $text;
  }

  public static function exportDate($id, $data, $property, $source='') {
    $text = '';

    if (!is_null($data)) {
      foreach($data->value as $value) {
        $text .= $id . "\t" . $property . "\t+" . $value . "-01-01T00:00:00Z/9";
        if ($source != '')
          $text .= "\t" . $source;
        $text .= "\n";
      }
    }

    return $text;
  }

  public static function exportCoordinates($id, $data, $property, $source='') {
    $text = '';

    if (!is_null($data)) {
      $text .= $id . "\t" . $property . "\t@" . $data->value[0]->lat . "/" . $data->value[0]->lon;
      if ($source != '')
        $text .= "\t" . $source;
      $text .= "\n";
    }

    return $text;
  }

  public static function renderExport($page) {
    // Récupération des données de l'œuvre
    $parameters = [
      'action' => 'amgetartwork',
      'article' => $page
    ];
    $data = API::call_api($parameters, 'am');
    //var_dump($data->entities->data);

    /*
    $data = ArtworkGetData::get_page($page);
    $ids = ArtworkGetData::get_ids_am($data);
    $labels = ArtworkGetData::get_labels($ids);
    */

    $source = 'S854' . "\t" . '"http://atlasmuseum.net/wiki/' . $page . '"';

    if (!is_null($data->entities->data->wikidata)) {
      $id = $data->entities->data->wikidata->value[0];
    } else {
      $id = 'LAST';
    }

    $artists = [];
    if (!is_null($data->entities->data->artiste)) {
      foreach($data->entities->data->artiste->value as $artist) {
        if ($artist->label != ''  )
          array_push($artists, $artist->label);
      }
    }

    if (sizeof($artists) == 0)
      $description = 'œuvre';
    else
    if (sizeof($artists) == 1) {
      $firstChar = strtolower(substr($artists[0], 0, 1));
      if ($firstChar == 'a' || $firstChar == 'â' || $firstChar == 'à' || $firstChar == 'á'
        || $firstChar == 'e' || $firstChar == 'é' || $firstChar == 'è' || $firstChar == 'ê'
        || $firstChar == 'i' || $firstChar == 'î' || $firstChar == 'í' || $firstChar == 'ì'
        || $firstChar == 'o' || $firstChar == 'ó' || $firstChar == 'ò' || $firstChar == 'ô'
        || $firstChar == 'u' || $firstChar == 'ú' || $firstChar == 'ù' || $firstChar == 'û')
        $description = 'œuvre d\'' . $artists[0];
      else
        $description = 'œuvre de ' . $artists[0];
    }
    else {
      $last  = array_slice($artists, -1);
      $first = join(', ', array_slice($artists, 0, -1));
      $both  = array_filter(array_merge(array($first), $last), 'strlen');
      $description = 'œuvre de ' . join(' et ', $both);
    }
    
    ob_start();
?>
<div>
  <!--
  <p>
    Pour exporter une œuvre vers Wikidata, deux options sont possibles.
  </p>
  -->
  <h2>Utiliser QuickStatements</h2>
  <p>
    <a href="https://tools.wmflabs.org/quickstatements/#/" target="_blank">QuickStatements</a> est un utilitaire qui peut éditer Wikidata, à partir d'un ensemble de commandes texte. Si vous disposez d'un compte sur Wikidata, il vous permet d'utiliser ce compte afin de réaliser l'export depuis atlasmuseum. Pour ce faire, il vous est possible de copier les lignes de commandes ci-dessous dans la section «&nbsp;<a href="https://tools.wmflabs.org/quickstatements/#/batch" target="_blank">Batch</a>&nbsp;» de QuickStatements et de lancer l'utilitaire.
  </p>
  <textarea style="height:300px" id="export_data"><?php
    if ($id == 'LAST')
      print "CREATE\n";

    if (!is_null($data->entities->data->titre))
      print $id."\tLfr\t\"".$data->entities->data->titre->value[0]."\"\n";

    print $id."\tDfr\t\"".$description."\"\n";

    print self::exportItem($id, $data->entities->data->type_art, 'P31', $source);
    print self::exportItem($id, $data->entities->data->artiste, 'P170', $source);
    print self::exportItem($id, $data->entities->data->site_pays, 'P17', $source);
    print self::exportItem($id, $data->entities->data->site_ville, 'P131', $source);
    print self::exportItem($id, $data->entities->data->couleur, 'P462', $source);
    print self::exportItem($id, $data->entities->data->materiaux, 'P186', $source);
    print self::exportItem($id, $data->entities->data->commanditaires, 'P88', $source);
    print self::exportItem($id, $data->entities->data->commissaires, 'P1640', $source);
    print self::exportItem($id, $data->entities->data->site_nom, 'P276', $source);
    print self::exportItem($id, $data->entities->data->site_pmr, 'P2846', $source);
    print self::exportItem($id, $data->entities->data->forme, 'P921', $source);
    print self::exportItem($id, $data->entities->data->programme, 'P195', $source);
    print self::exportNumber($id, $data->entities->data->hauteur, 'P2048', $source);
    print self::exportNumber($id, $data->entities->data->longueur, 'P5524', $source);
    print self::exportNumber($id, $data->entities->data->diametre, 'P2386', $source);
    print self::exportNumber($id, $data->entities->data->surface, 'P2046', $source);
    print self::exportDate($id, $data->entities->data->inauguration, 'P571', $source);
    print self::exportDate($id, $data->entities->data->fin, 'P576', $source);
    print self::exportCoordinates($id, $data->entities->data->site_coordonnees, 'P625', $source);
    /*
    print self::exportProperty($id, $data, 'P135', $source);
    */
?></textarea>
  <!--
  <h2>Utiliser le compte Wikidata d'atlasmuseum</h2>
  <p>
    Si vous ne souhaitez pas utiliser votre propre compte Wikidata, il vous est possible d'utiliser le compte générique d'atlasmuseum pour réaliser cet export. Cliquez sur le bouton ci-dessous.
  </p>
  <p>
    <button onclick="export_artwork()">Exporter l'œuvre avec le compte Wikidata d'atlasmuseum</button>
  </p>
  -->
</div>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery.min.js"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>export.js"></script>
<?php

      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;
  }

}
