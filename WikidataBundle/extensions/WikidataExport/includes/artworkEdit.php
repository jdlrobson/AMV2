<?php

require_once('extensions/WikidataEdit/includes/artworkGetWikidata.php');

class ArtworkEdit {
  
  public static function render_wikidata($data) {
    ?>
      <tr>
        <th>Wikidata</th>
        <td><span class="inputSpan"><input id="input_label" class="createboxInput" size="70" value="<?php print $data['id']; ?>" name="Edit[id]"></span></td>
      </tr>
    <?php
  }

  public static function render_label($data) {
    ?>
      <tr>
        <th>Titre<span class="mandatory">*</span></th>
        <td> <span class="inputSpan mandatoryFieldSpan"><input id="input_2" tabindex="2" class="createboxInput mandatoryField" size="70" value="<?php print $data['label']; ?>" name="Edit[label]"></span></td>
      </tr>
    <?php
  }

  public static function render_coordinates($data) {
    ?>
      <tr>
        <th>Coordonnées<span class="mandatory">*</span></th>
        <td>
        </td>
      </tr>
    <?php
  }

  public static function render_nature($data) {
    ?>
      <tr>
        <th>Nature</th>
        <td>
          <span id="span_10" class="radioButtonSpan mandatoryFieldSpan">
            <label class="radioButtonItem"><input name="Edit[nature]" type="radio" value="pérenne" checked="checked" id="input_6" tabindex="6"> pérenne</label>
            <label class="radioButtonItem"><input name="Edit[nature]" type="radio" value="éphémère" id="input_7" tabindex="7"> éphémère</label>
            <label class="radioButtonItem"><input name="Edit[nature]" type="radio" value="détruite" id="input_8" tabindex="8"> détruite</label>
            <label class="radioButtonItem"><input name="Edit[nature]" type="radio" value="non réalisée" id="input_9" tabindex="9"> non réalisée</label>
            <label class="radioButtonItem"><input name="Edit[nature]" type="radio" value="à vérifier" id="input_10" tabindex="10"> à vérifier</label>
          </span>
        </td>
      </tr>
    <?php
  }
  
  public static function render_image($data) {
    ?>
      <tr>
        <th>Image principale</th>
        <td>
        </td>
      </tr>
    <?php
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

  public static function renderEdit($page) {

    $data = ArtworkGetWikidata::get_page($page);
    $ids = ArtworkGetWikidata::get_ids_am($data);
    $labels = ArtworkGetWikidata::get_labels($ids);
    
    ob_start();
?>
<form id="edit_form">
  <div class="jquery-large headertabs">
    <div>
      <ul class="edit-tabs">
        <li class="edit_tab tab_selected" onclick="show_tab(this, 'Infos_essentielles');">Infos essentielles</li>
        <li class="edit_tab tab_unselected" onclick="show_tab(this, 'Description');">Description</li>
        <li class="edit_tab tab_unselected" onclick="show_tab(this, 'Production');">Production</li>
        <li class="edit_tab tab_unselected" onclick="show_tab(this, 'Site');">Site</li>
        <li class="edit_tab tab_unselected" onclick="show_tab(this, 'Acteurs');">Acteurs</li>
        <li class="edit_tab tab_unselected" onclick="show_tab(this, 'Conservation');">Conservation</li>
        <li class="edit_tab tab_unselected" onclick="show_tab(this, 'Sources');">Sources</li>
        <li class="edit_tab tab_unselected" onclick="show_tab(this, 'Photos');">Photos</li>
      </ul>
    </div>

    <div id="Infos_essentielles" class="edit_section section_selected">
      <h2> <span class="mw-headline" id="Wikidata"> Wikidata </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_wikidata($data); ?>
        </tbody>
      </table>

      <h2> <span class="mw-headline" id="Titre_et_coordonn.C3.A9es"> Titre et coordonnées </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_label($data); ?>
          <?php self::render_coordinates($data); ?>
        </tbody>
      </table>

      <h2> <span class="mw-headline" id="Artiste">Artiste</span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_item($data, 'P170', 'Artiste', 'P170', true); ?>
        </tbody>
      </table>

      <h2> <span class="mw-headline" id="Nature"> Nature </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_nature($data); ?>
        </tbody>
      </table>

      <h2> <span class="mw-headline" id="Image_principale"> Image principale </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_image($data); ?>
        </tbody>
      </table>
    </div>

    <div id="Description" class="edit_section section_unselected">
    </div>

    <div id="Production" class="edit_section section_unselected">
    </div>

    <div id="Site" class="edit_section section_unselected">
    </div>

    <div id="Acteurs" class="edit_section section_unselected">
    </div>

    <div id="Conservation" class="edit_section section_unselected">
    </div>

    <div id="Sources" class="edit_section section_unselected">
    </div>

    <div id="Photos" class="edit_section section_unselected">
    </div>

  </div>
  <div class="edit_publish">
    <input type="button" value="Publier" name="wpSave" onclick="publish();">
  </div>
</form>
<script type="text/javascript" src="http://publicartmuseum.net/tmp/w/extensions/WikidataEdit/includes/jquery.min.js"></script>
<script type="text/javascript" src="http://publicartmuseum.net/tmp/w/extensions/WikidataEdit/includes/jquery-ui.min.js"></script>
<script type="text/javascript" src="http://publicartmuseum.net/tmp/w/extensions/WikidataEdit/includes/autocomplete.js"></script>
<script type="text/javascript" src="http://publicartmuseum.net/tmp/w/extensions/WikidataEdit/includes/edit.js"></script>
<link rel="stylesheet" href="http://publicartmuseum.net/tmp/w/extensions/WikidataEdit/includes/edit.css">
<link rel="stylesheet" href="http://publicartmuseum.net/tmp/w/extensions/WikidataEdit/includes/autocomplete.css">
<?php

      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;
  }

}
