<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artistGetData.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'constants.php');

class ArtistEdit {

  public static function render_wikidata($data, $wikidata = false) {
    ?>
      <tr>
        <th>Wikidata</th>
        <td><span class="inputSpan"><input id="input_label" class="createboxInput" size="70" value="<?php print $data['id']; ?>" name="Edit[id]">
        <?php
          if (!$wikidata) {
            ?>
              <br /><br /><button id="amImportButton" onclick="import_wikidata();">Importer</button></span></td>
            <?php
          }
        ?>
      </tr>
    <?php
  }

  public static function render_text($data, $property, $title, $key, $mandatory=false) {
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $property; ?>">
          <input type="text" id="input_text_<?php print $property; ?>" value="<?php print $data[$property]; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" size="45">
        </td>
      </tr>
    <?
  }

  public static function render_textarea($data, $property, $title, $key, $mandatory=false) {
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $property; ?>">
          <textarea id="input_<?php print $property; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" rows="5" cols="40" style="width: 100%;resize: none;"><?php print $data[$property]; ?></textarea>
        </td>
      </tr>
    <?
  }

  public static function render_image($data, $property, $title, $key, $mandatory=false) {
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $property; ?>">
          <input type="text" id="input_text_<?php print $property; ?>" value="<?php print $data[$property]['file']; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" size="45"><br /><br />
          <input id="input_checkbox_<?php print $property; ?>" name="Edit[<?php print $key; ?>_origin]" type="checkbox" class="createboxInput" <?php if ($data[$property]['origin'] == 'commons') print 'checked'; ?>><i>Cette image provient de Wikimedia Commons</i>
        </td>
      </tr>
    <?
  }

  public static function render_item($data, $property, $title, $key, $mandatory=false) {
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $property; ?>">
    <?php
      foreach ($data[$property] as $index => $value) {
    ?>
          <div id="input_<?php print $property; ?>_wrapper_<?php print $index; ?>" class="inputSpan<?php if ($mandatory) print ' mandatoryFieldSpan'; ?> autocomplete">
            <input id="input_<?php print $property; ?>_<?php print $index; ?>" class="createboxInput<?php if ($mandatory) print ' mandatoryField'; ?>" size="60" value="<?php print $value['label']; ?>" name="Edit[<?php print $key; ?>][<?php print $index; ?>]">
            <input type="hidden" id="input_<?php print $property; ?>_id_<?php print $index; ?>" name="Edit[<?php print $key; ?>][id][<?php print $index; ?>]" value="<?php print $value['id']; ?>">
            <span class="edit_item_button" title="Supprimer cette ligne" onclick="remove_line('input_<?php print $property; ?>_wrapper_<?php print $index; ?>');">
              [&nbsp;x&nbsp;]
            </span>
          </div>
    <?php
      }
    ?>
          <div class="edit_item_button add_button" title="Ajouter une ligne" onclick="add_line('input_<?php print $property; ?>', '<?php print $property; ?>', '<?php print $key; ?>', <?php print $mandatory; ?>);">
            [&nbsp;+&nbsp;]
          </div>
        </td>
      </tr>
    <?
  }

  public static function render_societe_droit_auteur($data) {
    $checked = (array_key_exists('societe_gestion_droit_auteur', $data) && $data['societe_gestion_droit_auteur'] == 'oui');
    ?>
      <tr>
        <th>Societe de gestion des droits d'auteur</th>
        <td>
          <input name="Edit[societe_gestion_droit_auteur]" type="checkbox" class="createboxInput" tabindex="15" <?php if ($checked) print 'checked'; ?>>
          <i>Si vous êtes adhérent d'une société d'auteurs pour la gestion de vos droits, merci de cocher cette case.</i>
        </td>
      </tr>
    <?php
  }

  /**
   * Affiche le formulaire d'édition d'une œuvre
   */
  public static function renderEdit($id) {

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
      <?php self::render_wikidata($data, $article == ''); ?>
      <?php self::render_item($data, 'P735', 'Prénom', 'prenom', false); ?>
      <?php self::render_item($data, 'P734', 'Nom', 'nom', true); ?>
      <?php self::render_textarea($data, 'abstract', 'Résumé', 'abstract', false); ?>
      <?php self::render_item($data, 'P19', 'Lieu de naissance', 'birthplace', false); ?>
      <?php self::render_text($data, 'P569', 'Date de naissance', 'dateofbirth', false); ?>
      <?php self::render_item($data, 'P20', 'Lieu de décès', 'deathplace', false); ?>
      <?php self::render_text($data, 'P570', 'Date de décès', 'deathdate', false); ?>
      <?php self::render_item($data, 'P135', 'Mouvement', 'movement', false); ?>
      <?php self::render_item($data, 'P27', 'Pays de nationalité', 'nationality', false); ?>
      <?php self::render_image($data, 'P18', 'Portrait', 'thumbnail', false); ?>
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
      <?php self::render_societe_droit_auteur($data); ?>
      <?php self::render_text($data, 'nom_societe_gestion_droit_auteur', 'Si oui laquelle', 'nom_societe_gestion_droit_auteur', false); ?>
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

<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery.min.js"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php print OPEN_LAYER_JS; ?>"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>autocomplete.js"></script>
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>artistEdit.js"></script>
<link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>edit.css">
<link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>autocomplete.css">
<link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
<?php

      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;

  }

}
