<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artistGetData.php');

class ArtistExport {

  public static function exportProperty($id, $data, $property) {
    $text = '';

    foreach($data[$property] as $item)
      if ($item['id'] != '')
        $text .= $id . "\t" . $property . "\t" . $item['id'] . "\n";

    return $text;
  }

  public static function renderExport($page) {

    $data = ArtistGetData::get_page($page);
    $ids = ArtistGetData::get_ids_am($data);
    $labels = ArtistGetData::get_labels($ids);

    if ($data['id'] != '')
      $id = $data['id'];
    else
      $id = 'LAST';

    $name = str_replace('_', ' ', $page);

    $description = 'artiste';
    
    ob_start();

?>
<div>
  <textarea style="height:300px">
<?php
    if ($id == 'LAST')
      print "CREATE\n";

    print $id."\tLfr\t\"".$name."\"\n";
    print $id."\tLen\t\"".$name."\"\n";

    print $id."\tDfr\t\"".$description."\"\n";

    print $id."\tP31\tQ5\n";
    
    print self::exportProperty($id, $data, 'P735');
    print self::exportProperty($id, $data, 'P734');
    print self::exportProperty($id, $data, 'P19');
    print self::exportProperty($id, $data, 'P20');
    print self::exportProperty($id, $data, 'P135');
    print self::exportProperty($id, $data, 'P27');

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
