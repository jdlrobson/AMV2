<?php

define(DB_SERVER, "publicarmod1.mysql.db");
define(DB_NAME, "publicarmod1");
define(DB_USER, "publicarmod1");
define(DB_PASSWORD, "1dwy2Myi");

define(MISSING_IMAGE_THUMB, "http://publicartmuseum.net/w/images/5/5f/Image-manquante.jpg");

require_once('Wikidata/php/api.php');

class WikidataQuery {

  public static function renderQuery() {
    $data = [];

    $query = '';
    if (isset($_POST['query'])) {
      $query = $_POST['query'];

      $result = Api::Sparql($query);
      foreach ($result->results->bindings as $artwork) {
        if (isset($artwork->q) && isset($artwork->qLabel)) {
          $id = str_replace('http://www.wikidata.org/entity/', '', $artwork->q->value);
          $title = $artwork->qLabel->value;
          $data[$id] = [
            'article' => '',
            'wikidata' => $id,
            'title' => $title,
            'nature' => 'wikidata'
          ];
        }
      }
    } else {
      $query = '# Cette requête retourne tous les éléments « art public » possédant' . "\n" .
              '# des coordonnées sur Wikidata et créés par Daniel Buren.' . "\n" .
              '# Pour être prise en compte, la requête doit retourner les champs « q »' . "\n" .
              '# et qLabel.' ."\n\n" .
              'SELECT DISTINCT ?q ?qLabel ?coords ?creatorLabel ?image WHERE {' . "\n" .
              '  ?q wdt:P136/wdt:P279* wd:Q557141 ; # Sous-classes d\'« art public »' . "\n" .
              '     wdt:P625 ?coords ; # Coordonnées' . "\n" .
              '     wdt:P170 wd:Q593621 . # Créé par Daniel Buren' . "\n" .
              '  OPTIONAL { ?q wdt:P18 ?image } # Image' . "\n" .
              '  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr" . }' . "\n" .
              '} ORDER BY ?qLabel';
    }

    ob_start();
    ?>
      <div>
        <form action="index.php" method="post">
          <input type="hidden" name="title" value="Spécial:WikidataQuery" />
          <textarea style="height:300px" name="query"><?php print $query; ?></textarea>
          <button type="submit">Exécuter</button>
        </form>
      </div>
      <script type="text/javascript" src="http://publicartmuseum.net/tmp/w/Wikidata/js/jquery.min.js"></script>
      <script type="text/javascript" src="http://publicartmuseum.net/tmp/w/Wikidata/js/jquery-ui.min.js"></script>
    <?php
    if (sizeof($data)>0) {
      ?>
        <table class="sortable wikitable smwtable jquery-tablesorter" width="100%">
          <thead>
            <tr>
              <th class="headerSort">Id</th>
              <th class="headerSort">Titre</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $i = 1;
            foreach ($data as $artwork) {
              ?>
                <tr class="row-<?php if ($i % 2 == 0) print 'even'; else print 'odd'; ?>">
                  <td><a href="Spécial:Wikidata/<?php print $artwork['wikidata']; ?>"><?php print $artwork['wikidata']; ?></a></td>
                  <td><?php print $artwork['title']; ?></td>
                </tr>
              <?php
              $i++;
            }
          ?>
          </tbody>
        </table>
      <?php
    }

      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;
  }

}
