<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artistGetData.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'constants.php');

class ArtistEditTest {
  /**
   * En-tête
   */
  protected static function renderHeader($entity) {
    // Nom de l'article en cours (vide si création)
    $article = '';
    if (!is_null($entity)) {
      // De base, on prend le nom de l'article (ou de l'id Wikidata)
      $article = $entity->article;

      // Si origine Wikidata, il faut vérifier s'il existe un champ "titre"
      if ($entity->origin === 'wikidata' && !is_null($entity->data->titre))
        $article = $entity->data->titre->value[0];
    }

    ?>
    <form name="createbox" id="edit_form" onsubmit="return false;">
      <input type="hidden" id="article" name="article" value="<?php print $article; ?>">
        <table class="formtable">
          <tbody>
    <?php
  }

  protected static function renderRights() {
    ?>
        </tbody>
      </table>
      <h2><span class="mw-headline" id="Droit_d.27auteur">Droit d'auteur</span></h2>
      <div style="margin: 0px; padding-top: 0px; padding-right: 0px; padding-bottom: 15px; padding-left: 0px;"><span style="font-size:16px; font-style: italic;">Cette partie du formulaire est réservée aux artistes qui créent leur propre notice.</span></div>
      <div>Si vous êtes adhérent d'une société d'auteurs pour la gestion de vos droits, vous n'êtes pas en mesure de céder vos droits de reproduction et de représentation sur vos œuvres et ainsi de les verser dans Atlasmuseum, merci de <a rel="nofollow" class="external text" href="mailto:contact@atlasmuseum.org">contacter l'association A-Pack</a>.
      <p>Exception (rappel)&nbsp;: les œuvres installées dans les pays acceptant la "liberté de panorama" peuvent être documentées dans Atlasmuseum. La liberté de panorama est une exception au droit d'auteur par laquelle il est permis de reproduire une œuvre protégée se trouvant dans l'espace public. Selon les pays, cette exception peut concerner les œuvres d'art ou les œuvres d'architecture.</p>
      <p>
      Liberté de panorama en Europe&nbsp;: <a rel="nofollow" class="external free"
      href="<?php print COMMONS_PATH . COMMONS_FILE_PREFIX; ?>Freedom_of_Panorama_in_Europe.svg"><?php print COMMONS_PATH . COMMONS_FILE_PREFIX; ?>Freedom_of_Panorama_in_Europe.svg</a></p>
      Liberté de panorama dans le monde&nbsp;: <a rel="nofollow" class="external free" href="<?php print COMMONS_PATH; ?>Commons:Freedom_of_panorama"><?php print COMMONS_PATH; ?>Commons:Freedom_of_panorama</a></div>
      <table class="formtable">
        <tbody>
    <?php
  }

  /**
   * Pied de page
   */
  protected static function renderFooter() {
    ?>
          </tbody>
        </table>
        <div class="edit_publish">
          <input type="button" value="Publier" name="wpSave" onclick="publish();">
        </div>
      </form>

      <div style="display:none">
      <form id="editform" name="editform" method="post" action="" enctype="multipart/form-data">
        <div id="antispam-container" style="display: none;"><input type="text" name="wpAntispam" id="wpAntispam" value="" /></div>
        <input type="hidden" name="editingStatsId" id="editingStatsId" value="" />
        <input type='hidden' value="" name="wpSection"/>
        <input type='hidden' value="" name="wpStarttime" />
        <input type='hidden' value="" name="wpEdittime" />
        <input type='hidden' value="" name="wpScrolltop" id="wpScrolltop" />
        <input type="hidden" value="" name="wpAutoSummary"/>
        <input type="hidden" value="" name="oldid"/>
        <input type="hidden" value="30307" name="parentRevId"/>
        <input type="hidden" value="text/x-wiki" name="format"/>
        <input type="hidden" value="wikitext" name="model"/>
        <input type="hidden" value=<?php print Api::get_token(); ?> name="wpEditToken"/>
        <textarea tabindex="1" accesskey="," id="wpTextbox1" cols="80" rows="25" style="" lang="fr" dir="ltr" name="wpTextbox1"></textarea>
      </form>
      </div>

      <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
      <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
      <script type="text/javascript" src="<?php print OPEN_LAYER_JS; ?>"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>autocomplete.js"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>artistEdit2.js"></script>
      <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>edit.css">
      <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>autocomplete.css">
      <link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
    <?php
  }

  public static function renderWikidata($entity) {
    $wikidataID = !is_null($entity->data->wikidata) ? $entity->data->wikidata->value[0] : '';
    $wikidataOrigin = ($entity->origin === 'wikidata');
    ?>
      <tr>
        <th>Wikidata</th>
        <td><span class="inputSpan"><input id="input_label" class="createboxInput" size="70" value="<?php print $wikidataID; ?>" name="Edit[wikidata]">
        <?php
          if (!$wikidataOrigin) {
            ?>
              <br /><br /><button id="amImportButton" onclick="importWikidata();">Importer</button>
            <?php
          }
        ?>
        </span></td>
      </tr>
    <?php
  }

  public static function get_image_am($image, $width=320) {
    return Api::call_api(array(
      'action' => 'query',
      'prop' => 'imageinfo',
      'iiprop' => 'url',
      'iiurlwidth' => $width,
      'titles' => 'File:'.$image
    ), 'atlasmuseum');
  }

  public static function renderImage($data) {
    $imageFile = '';
    $imageOrigin = 'atlasmuseum';
    $imageThumb = '';
    if (!is_null($data)) {
      $imageFile = $data->value[0]->value;
      $imageOrigin = $data->value[0]->origin;
      $imageThumb = $data->value[0]->thumbnail;
    }
    ?>
      <tr>
        <th>Portrait</th>
        <td id="input_thumbnail_cell">
          <input type="text" id="input_thumbnail" value="<?php print $imageFile; ?>" name="Edit[thumbnail]" class="createboxInput createboxInputMainImage" size="45">
          <a data-fancybox data-type="iframe" data-src="<?php print BASE_MAIN; ?>index.php?title=Sp%C3%A9cial:UploadWindow&amp;pfInputID=input_thumbnail" href="javascript:;">Importer un fichier</a>
          <br />
          <input id="input_checkbox_thumbnail" name="Edit[thumbnail_origin]" type="checkbox" class="createboxInput" <?php if ($imageOrigin == 'commons') print 'checked'; ?>><i>Cette image provient de Wikimedia Commons</i>
          <?php
            if ($imageThumb !== '') {
            ?>
              <div id="input_thumbnail_thumb"  class="image_thumb">
                <img src="<?php print $imageThumb; ?>" />
              </div>
            <?php
            }
          ?>
          <div class="image_disclaimer">
            Avant d'importer une image, assurez vous que vous avez les droits suffisants pour le faire (œuvres originales dont vous êtes l'auteur, œuvres dans le domaine public, œuvres sous licence libre). Veuillez consulter l'aide sur les droits d'auteur.<br />
            Si vous n'avez pas les droits sur l'image ou si avez un doute, laissez le nom de l'image : image-manquante.jpg dans le zone de saisie "Image principale".
          </div>
        </td>
      </tr>
    <?
  }

  public static function renderText($data, $key, $title, $mandatory=false) {
    $text = '';
    if (!is_null($data))
      $text = $data->value[0];
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $key; ?>">
          <input type="text" id="input_<?php print $key; ?>" value="<?php print $text; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" size="45">
        </td>
      </tr>
    <?
  }

  public static function renderTextarea($data, $key, $title, $mandatory=false) {
    $text = '';
    if (!is_null($data)) {
      $text = str_replace('\\n', "\n", $data->value[0]);
    }
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $key; ?>">
          <textarea id="input_<?php print $key; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" rows="5" cols="40" style="width: 100%"><?php print $text; ?></textarea>
        </td>
      </tr>
    <?
  }

  public static function renderItem($data, $key, $title, $mandatory=false) {
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $key; ?>_cell">
          <?php
            if (!is_null($data)) {
              foreach ($data->value as $index => $value) {
              ?>
                <div id="input_<?php print $key; ?>_wrapper_<?php print $index; ?>" class="inputSpan<?php if ($mandatory) print ' mandatoryFieldSpan'; ?> autocomplete">
                  <input id="input_<?php print $key; ?>_<?php print $index; ?>" class="createboxInput<?php if ($mandatory) print ' mandatoryField'; ?>" size="60" value="<?php print $value->label; ?>" name="Edit[<?php print $key; ?>][<?php print $index; ?>]">
                  <input type="hidden" id="input_<?php print $key; ?>_id_<?php print $index; ?>" name="Edit[<?php print $key; ?>][id][<?php print $index; ?>]" value="<?php print $value->article; ?>">
                  <span class="edit_item_button" title="Supprimer cette ligne" onclick="removeLine('input_<?php print $key; ?>_wrapper_<?php print $index; ?>');">
                    [&nbsp;x&nbsp;]
                  </span>
                </div>
              <?php
              }
            }
          ?>
          <div class="edit_item_button add_button" title="Ajouter une ligne" onclick="addLine('input_<?php print $key; ?>', '<?php print $key; ?>', '<?php print $key; ?>', <?php print $mandatory; ?>);">
            [&nbsp;+&nbsp;]
          </div>
        </td>
      </tr>
    <?
  }

  public static function renderSociete($data) {
    $checked = (!is_null($data) && $data->value[0] == 'oui');
    ?>
      <tr>
        <th>Société de gestion des droits d'auteur</th>
        <td>
          <input name="Edit[societe_gestion_droit_auteur]" type="checkbox" class="createboxInput" tabindex="15" <?php if ($checked) print 'checked'; ?>>
          <i>Si vous êtes adhérent d'une société d'auteurs pour la gestion de vos droits, merci de cocher cette case.</i>
        </td>
      </tr>
    <?php
  }

  public static function renderEditOld($id) {

    if (isset($id) && preg_match('/^Q[0-9]+$/', $id)) {
      require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'updateDB.php');
      $article = get_artist_from_q($id);

      $values = ArtistGetData::get_props($id);
      $ids = ArtistGetData::get_ids($values);
      $labels = ArtistGetData::get_labels($ids);

      $data = ArtistGetData::get_data($id, $values, $labels);
      if ($article != '') {
        $data_am = ArtistGetData::get_data_am($article);
        $data = ArtistGetData::merge_data($data_am, $data);
      }
    } else
    if (isset($id)) {
      $data = ArtistGetData::get_data_am($id);
      $data = ArtistGetData::get_labels_am($data);
      $article = $id;
    } else {
      $data = ArtistGetData::get_data('', null, null);
      $article = '';
    }

      ob_start();

      if ($article == '' && array_key_exists('label', $data)) {
        $article = $data['label'];
        ?>
          <script>document.getElementById('firstHeading').getElementsByTagName('span')[0].textContent = "Importer : <?php print $data['label']; ?>"</script>
        <?php
      }
?>
<form name="createbox" id="edit_form" onsubmit="return false;">
  <input type="hidden" id="article" name="article" value="<?php print $article; ?>">
  <table class="formtable">
    <tbody>
    self::render_wikidata($data, $article == ''); ?>
    self::render_item($data, 'P735', 'Prénom', 'prenom', false); ?>
    self::render_item($data, 'P734', 'Nom', 'nom', true); ?>
    self::render_textarea($data, 'abstract', 'Résumé', 'abstract', false); ?>
    self::render_item($data, 'P19', 'Lieu de naissance', 'birthplace', false); ?>
    self::render_text($data, 'P569', 'Date de naissance', 'dateofbirth', false); ?>
    self::render_item($data, 'P20', 'Lieu de décès', 'deathplace', false); ?>
    self::render_text($data, 'P570', 'Date de décès', 'deathdate', false); ?>
    self::render_item($data, 'P135', 'Mouvement', 'movement', false); ?>
    self::render_item($data, 'P27', 'Pays de nationalité', 'nationality', false); ?>
    self::render_image($data, 'P18', 'Portrait', 'thumbnail', false); ?>
    </tbody>
  </table>
  <h2><span class="mw-headline" id="Droit_d.27auteur">Droit d'auteur</span></h2>
  <div style="margin: 0px; padding-top: 0px; padding-right: 0px; padding-bottom: 15px; padding-left: 0px;"><span style="font-size:16px; font-style: italic;">Cette partie du formulaire est réservée aux artistes qui créent leur propre notice.</span></div>
  <div>Si vous êtes adhérent d'une société d'auteurs pour la gestion de vos droits, vous n'êtes pas en mesure de céder vos droits de reproduction et de représentation sur vos œuvres et ainsi de les verser dans Atlasmuseum, merci de <a rel="nofollow" class="external text" href="mailto:contact@atlasmuseum.org">contacter l'association A-Pack</a>.
  <p>Exception (rappel)&nbsp;: les œuvres installées dans les pays acceptant la "liberté de panorama" peuvent être documentées dans Atlasmuseum. La liberté de panorama est une exception au droit d'auteur par laquelle il est permis de reproduire une œuvre protégée se trouvant dans l'espace public. Selon les pays, cette exception peut concerner les œuvres d'art ou les œuvres d'architecture.</p>
  <p>
  Liberté de panorama en Europe&nbsp;: <a rel="nofollow" class="external free"
  href="<?php print COMMONS_PATH . COMMONS_FILE_PREFIX; ?>Freedom_of_Panorama_in_Europe.svg"><?php print COMMONS_PATH . COMMONS_FILE_PREFIX; ?>Freedom_of_Panorama_in_Europe.svg</a></p>
  Liberté de panorama dans le monde&nbsp;: <a rel="nofollow" class="external free" href="<?php print COMMONS_PATH; ?>Commons:Freedom_of_panorama"><?php print COMMONS_PATH; ?>Commons:Freedom_of_panorama</a></div>

  <table class="formtable">
    <tbody>
    self::render_societe_droit_auteur($data); ?>
    self::render_text($data, 'nom_societe_gestion_droit_auteur', 'Si oui laquelle', 'nom_societe_gestion_droit_auteur', false); ?>
    </tbody>
  </table>
  <div class="edit_publish">
    <input type="button" value="Publier" name="wpSave" onclick="publish();">
  </div>
</form>

<div style="display:none">
<form id="editform" name="editform" method="post" action="" enctype="multipart/form-data">
  <div id="antispam-container" style="display: none;"><input type="text" name="wpAntispam" id="wpAntispam" value="" /></div>
  <input type="hidden" name="editingStatsId" id="editingStatsId" value="" />
  <input type='hidden' value="" name="wpSection"/>
  <input type='hidden' value="" name="wpStarttime" />
  <input type='hidden' value="" name="wpEdittime" />
  <input type='hidden' value="" name="wpScrolltop" id="wpScrolltop" />
  <input type="hidden" value="" name="wpAutoSummary"/>
  <input type="hidden" value="" name="oldid"/>
  <input type="hidden" value="30307" name="parentRevId"/>
  <input type="hidden" value="text/x-wiki" name="format"/>
  <input type="hidden" value="wikitext" name="model"/>
  <input type="hidden" value=<?php print Api::get_token(); ?> name="wpEditToken"/>
  <textarea tabindex="1" accesskey="," id="wpTextbox1" cols="80" rows="25" style="" lang="fr" dir="ltr" name="wpTextbox1"></textarea>
</form>
</div>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
<script type="text/javascript" src="<?php print OPEN_LAYER_JS; ?>"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>autocomplete.js"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>artistEdit.js"></script>
<link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>edit.css">
<link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>autocomplete.css">
<link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
<?php

      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;

  }

  /**
   * Affiche le formulaire d'édition d'une œuvre
   */
  public static function renderEntityEdit($entity) {
    ob_start();

    // En-tête
    self::renderHeader($entity);

    self::renderWikidata($entity);
    self::renderItem($entity->data->prenom, 'prenom', 'Prénom');
    self::renderItem($entity->data->nom, 'nom', 'Nom');
    self::renderTextarea($entity->data->abstract, 'abstract', 'Résumé');
    self::renderItem($entity->data->birthplace, 'birthplace', 'Lieu de naissance');
    self::renderText($entity->data->dateofbirth, 'dateofbirth', 'Date de naissance');
    self::renderItem($entity->data->deathplace, 'deathplace', 'Lieu de décès');
    self::renderText($entity->data->deathdate, 'deathdate', 'Date de décès');
    self::renderItem($entity->data->movement, 'movement', 'Mouvement');
    self::renderItem($entity->data->nationality, 'nationality', 'Pays de nationalité');
    self::renderImage($entity->data->thumbnail);

    self::renderRights();

    self::renderSociete($entity->data->societe_gestion_droit_auteur);
    self::renderText($entity->data->nom_societe_gestion_droit_auteur, 'nom_societe_gestion_droit_auteur', 'Si oui laquelle');

    // Pied de page
    self::renderFooter();

    $contents = ob_get_contents();
    ob_end_clean();

    return $contents;
  }

  /**
   * Rendu si erreur
   */
  protected static function renderError() {
    return '<div>Problème lors de la récupération des données... Veuillez recharger la page.</div>';
  }

  /**
   * Rendu d'édition d'un artiste
   */
  public static function renderEdit($id = null) {
    $title = 'Ajouter un artiste';

    if (!is_null($id)) {
      // Récupération des données de l'artiste
      $parameters = [
        'action' => 'amgetartist',
        'article' => $id
      ];
      $data = API::call_api($parameters, 'am');

      if ($data->success === 1) {
        // Artiste ok
        if ($data->entities->origin === 'wikidata') {
          if (!is_null($data->entities->data->titre))
            $title = 'Importer : ' . $data->entities->data->titre->value[0];
          else
            $title = 'Importer : ' . $data->entities->article;
        }
        else
          $title = 'Modifier : ' . $data->entities->article;
        $content = self::renderEntityEdit($data->entities);
      } else {
        // Problème de données
        if ($data->error->code === 'no_data') {
          // L'artiste n'existe pas : le créer
          $data = (object)[
            'article' => '',
            'title' => '',
            'origin' => 'atlasmuseum',
            'data' => (object)[]
          ];
          $content = self::renderEntityEdit($data);
        } else {
          // Autre erreur
          $content = self::renderError();
        }
      }
    } else {
      $data = (object)[
        'article' => '',
        'title' => '',
        'origin' => 'atlasmuseum',
        'data' => (object)[]
      ];
      $content = self::renderEntityEdit($data);
    }

    return [
      'title' => $title,
      'content' => preg_replace("/\r|\n/", "", $content),
    ];
  }

}
