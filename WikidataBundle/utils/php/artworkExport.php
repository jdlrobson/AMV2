<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artworkGetData.php');

class ArtworkExport {

  public static function exportProperty($id, $data, $property) {
    $text = '';

    foreach($data[$property] as $item)
      if ($item['id'] != '')
        $text .= $id . "\t" . $property . "\t" . $item['id'] . "\n";

    return $text;
  }

  public static function renderExport($page) {

    $data = ArtworkGetData::get_page($page);
    $ids = ArtworkGetData::get_ids_am($data);
    $labels = ArtworkGetData::get_labels($ids);

    if ($data['id'] != '')
      $id = $data['id'];
    else
      $id = 'LAST';

    $artists = [];
    foreach($data['P170'] as $artist)
      if ($artist['label'] != ''  )
        array_push($artists, $artist['label']);
    if (sizeof($artists) == 0)
      $description = 'œuvre';
    else
    if (sizeof($artists) == 1)
      $description = 'œuvre de ' . $artists[0];
    else {
      $last  = array_slice($artists, -1);
      $first = join(', ', array_slice($artists, 0, -1));
      $both  = array_filter(array_merge(array($first), $last), 'strlen');
      $description = 'œuvre de ' . join(' et ', $both);
    }
    
    ob_start();
?>
<div>
  <textarea style="height:300px">
<?php
    if ($id == 'LAST')
      print "CREATE\n";

    if ($data['label'])
      print $id."\tLFr\t\"".$data['label']."\"\n";

    print $id."\tDfr\t\"".$description."\"\n";

    print self::exportProperty($id, $data, 'P31');
    print self::exportProperty($id, $data, 'P170');
    print self::exportProperty($id, $data, 'P17');
    print self::exportProperty($id, $data, 'P131');
    print self::exportProperty($id, $data, 'P135');
    print self::exportProperty($id, $data, 'P462');
    print self::exportProperty($id, $data, 'P186');
    print self::exportProperty($id, $data, 'P88');
    print self::exportProperty($id, $data, 'P1640');
    print self::exportProperty($id, $data, 'P276');
    print self::exportProperty($id, $data, 'P2846');
    print self::exportProperty($id, $data, 'P921');

    if ($data['P625'])
      print $id."\tP625\t@".$data['P625']['latitude']."/".$data['P625']['longitude']."\n";
?>
  </textarea>
</div>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery.min.js"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
<?php

      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;
  }

}
