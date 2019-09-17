<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'collectionGetData.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'constants.php');

class CollectionEdit {

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
          <textarea id="input_<?php print $property; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" rows="5" cols="40" style="width: 100%;resize: none;"><?php print str_replace('\\n', "\n", $data[$property]); ?></textarea>
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

  /**
   * Affiche le formulaire d'édition d'une collection
   */
  public static function renderEdit($id) {

    if (isset($id)) {
      $data = CollectionGetData::get_data_am($id);
      $data = CollectionGetData::get_labels_am($data);
      $article = $id;
    } else {
      $data = CollectionGetData::get_data('', null, null);
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
      <?php self::render_textarea($data, 'description', 'Description', 'description', false); ?>
      <?php self::render_textarea($data, 'institution', 'Institution', 'institution', false); ?>
      <?php self::render_text($data, 'visuel', 'Visuel', 'visuel', false);
      //self::render_image($data, 'visuel', 'Visuel', 'visuel', false);
      ?>
      <?php self::render_textarea($data, 'notices', 'Notices', 'notices', false); ?>
      <?php self::render_textarea($data, 'texte', 'Texte additionnel', 'texte', false); ?>
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
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>collectionEdit.js"></script>
<link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>edit.css">
<link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>autocomplete.css">
<link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
<?php

      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;

  }

}
