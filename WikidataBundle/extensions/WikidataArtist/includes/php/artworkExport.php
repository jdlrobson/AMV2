<?php

require_once('extensions/WikidataEdit/includes/artworkGetWikidata.php');

class ArtworkExport {

  public static function renderExport($page) {

    $data = ArtworkGetWikidata::get_page($page);
    $ids = ArtworkGetWikidata::get_ids_am($data);
    $labels = ArtworkGetWikidata::get_labels($ids);

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
      print $id."\tLFr\t".$data['label']."\n";

    print $id."\tDfr\t".$description."\n";

    foreach($data['P31'] as $item)
      if ($item['id'] != '')
        print $id."\tP31\t".$item['id']."\n";
        
    foreach($data['P170'] as $item)
      if ($item['id'] != '')
        print $id."\tP170\t".$item['id']."\n";

    if ($data['P625'])
      print $id."\tP625\t@".$data['P625']['latitude']."/".$data['P625']['longitude']."\n";
?>
  </textarea>
</div>
<script type="text/javascript" src="http://publicartmuseum.net/tmp/w/extensions/WikidataEdit/includes/jquery.min.js"></script>
<script type="text/javascript" src="http://publicartmuseum.net/tmp/w/extensions/WikidataEdit/includes/jquery-ui.min.js"></script>
<?php

      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;
  }

}
