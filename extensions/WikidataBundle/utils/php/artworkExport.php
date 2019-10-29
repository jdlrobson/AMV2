<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artworkGetData.php');

class ArtworkExport {

  public static function exportProperty($id, $data, $property, $source='') {
    $text = '';

    foreach($data[$property] as $item)
      if ($item['id'] != '') {
        $text .= $id . "\t" . $property . "\t" . $item['id'];
        if ($source != '')
          $text .= "\t" . $source;
        $text .= "\n";
      }

    return $text;
  }

  public static function exportCoordinates($id, $data, $property, $source='') {
    $text = '';

    if (isset($data[$property]) && isset($data[$property]['latitude']) && isset($data[$property]['longitude'])) {
      $text .= $id . "\t" . $property . "\t@" . $data['P625']['latitude'] . "/" . $data['P625']['longitude'];
      
      if ($source != '')
        $text .= "\t" . $source;
      $text .= "\n";
    }

    return $text;
  }

  public static function renderExport($page) {
    $data = ArtworkGetData::get_page($page);
    $ids = ArtworkGetData::get_ids_am($data);
    $labels = ArtworkGetData::get_labels($ids);

    $source = 'S854' . "\t" . '"http://http://publicartmuseum.net/wiki/' . $page . '"';

    if ($data['id'] != '')
      $id = $data['id'];
    else
      $id = 'LAST';

    $artists = [];
    foreach($data['P170'] as $artist)
      if ($artist['label'] != ''  )
        array_push($artists, $artist['label']);
      else
      if ($artist['id'] != '') {
        $tmp = ArtworkGetData::get_labels([$artist['id']]);
        if(isset($tmp[$artist['id']]))
          array_push($artists, $tmp[$artist['id']]);
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
  <p>
    Pour exporter une œuvre vers Wikidata, deux options sont possibles.
  </p>
  <h2>Utiliser QuickStatements</h2>
  <p>
    <a href="https://tools.wmflabs.org/quickstatements/#/">QuickStatements</a> est un utilitaire qui peut éditer Wikidata, à partir d'un ensemble de commandes texte. Si vous disposez d'un compte sur Wikidata, il vous permet d'utiliser ce compte afin de réaliser l'export depuis atlasmuseum. Pour ce faire, il vous est possible de copier les lignes de commandes ci-dessous dans la section «&nbsp;<a href="https://tools.wmflabs.org/quickstatements/#/batch">Batch</a>&nbsp;» de QuickStatements et de lancer l'utilitaire.
  </p>
  <textarea style="height:300px" id="export_data"><?php
    if ($id == 'LAST')
      print "CREATE\n";

    if ($data['label'])
      print $id."\tLfr\t\"".$data['label']."\"\n";

    print $id."\tDfr\t\"".$description."\"\n";

    print self::exportProperty($id, $data, 'P31', $source);
    print self::exportProperty($id, $data, 'P170', $source);
    print self::exportProperty($id, $data, 'P17', $source);
    print self::exportProperty($id, $data, 'P131', $source);
    print self::exportProperty($id, $data, 'P135', $source);
    print self::exportProperty($id, $data, 'P462', $source);
    print self::exportProperty($id, $data, 'P186', $source);
    print self::exportProperty($id, $data, 'P88', $source);
    print self::exportProperty($id, $data, 'P1640', $source);
    print self::exportProperty($id, $data, 'P276', $source);
    print self::exportProperty($id, $data, 'P2846', $source);
    print self::exportProperty($id, $data, 'P921', $source);
    print self::exportCoordinates($id, $data, 'P625', $source);
?></textarea>
  <h2>Utiliser le compte Wikidata d'atlasmuseum</h2>
  <p>
    Si vous ne souhaitez pas utiliser votre propre compte Wikidata, il vous est possible d'utiliser le compte générique d'atlasmuseum pour réaliser cet export. Cliquez sur le bouton ci-dessous.
  </p>
  <p>
    <button onclick="export_artwork()">Exporter l'œuvre avec le compte Wikidata d'atlasmuseum</button>
  </p>
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
