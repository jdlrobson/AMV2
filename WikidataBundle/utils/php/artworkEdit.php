<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artworkGetData.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'constants.php');

class ArtworkEdit {

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
          <p>
            <span class="inputSpan mandatoryFieldSpan"><input class="createboxInput mandatoryField" id="coordinates_input" size="40" value="<?php print $data['P625']['latitude'] . ', ' . $data['P625']['longitude']; ?>" name="Edit[P625]"></span>
            <button onclick="change_map_input()">Mise à jour de la carte</button>
          </p>
          <p>
            <span class="inputSpan"><input class="createboxInput" id="address_input" size="40" value="" name=""></span>
            <button onclick="change_map_address()">Estimer les coordonnées</button>
          </p>
          <div id="map" style="height:350px"></div>
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

  public static function render_text($data, $property, $title, $key, $mandatory=false) {
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $property; ?>">
          <input type="text" id="input_<?php print $property; ?>" value="<?php print $data[$property]; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" size="45">
        </td>
      </tr>
    <?
  }

  public static function render_textarea($data, $property, $title, $key, $mandatory=false) {
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $property; ?>">
          <textarea id="input_<?php print $property; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" rows="5" cols="40" style="width: auto"><?php print $data[$property]; ?></textarea>
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

  public static function render_item_checkboxes($data, $property, $title, $key, $list, $mandatory=false) {
    $d = [];

    if (is_array($data[$property])) {
      for ($i=0; $i<sizeof($data[$property]); $i++) {
        if (is_array($data[$property][$i])) {
          
          if ($data[$property][$i]['label'] != '')
            array_push($d, $data[$property][$i]['label']);
          if ($data[$property][$i]['id'] != '')
            array_push($d, $data[$property][$i]['id']);
        }
        else
          array_push($d, $data[$property][$i]);
      }
    }
    else
      array_push($d, $data[$property]);
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $property; ?>">
          <div class="checkboxes3columns">
            <span class="checkboxesSpan">
    <?php
      for ($i=0; $i<sizeof($list); $i++) {
        $checked = (in_array($list[$i]['label'], $d) || ($list[$i]['id'] != '' && in_array($list[$i]['id'], $d)));
    ?>
              <label class="checkboxLabel">
                <input id="input_<?php print $property . '_' . $i; ?>" class="createboxInput" type="checkbox" value="<?php if ($list[$i]['id'] != '') print $list[$i]['id']; else print $list[$i]['label']; ?>" name=Edit[<?php print $key; ?>][<?php print $i; ?>] <?php if ($checked) print 'checked'; ?>> <?php print $list[$i]['label']; ?>
              </label>
    <?php
      }
    ?>
            </span>
          </div>
        </td>
      </tr>
    <?
  }

  public static function render_item_radio($data, $property, $title, $key, $list, $mandatory=false) {
    $d = [];
    for ($i=0; $i<sizeof($data[$property]); $i++) {
      if ($data[$property][$i]['label'] != '')
        array_push($d, $data[$property][$i]['label']);
      if ($data[$property][$i]['id'] != '')
        array_push($d, $data[$property][$i]['id']);
    }
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $property; ?>">
          <div class="checkboxes1column">
            <span class="radioButtonSpan">
    <?php
      for ($i=0; $i<sizeof($list); $i++) {
        $checked = (in_array($list[$i]['label'], $d) || ($list[$i]['id'] != '' && in_array($list[$i]['id'], $d)));
    ?>
              <label class="radioButtonItem">
                <input id="input_<?php print $property . '_' . $i; ?>" class="createboxInput" type="radio" value="<?php if ($list[$i]['id'] != '') print $list[$i]['id']; else print $list[$i]['label']; ?>" name=Edit[<?php print $key; ?>] <?php if ($checked) print 'checked'; ?>> <?php print $list[$i]['label']; ?>
              </label>
    <?php
      }
    ?>
            </span>
          </div>
        </td>
      </tr>
    <?
  }

  /**
   * Affiche le formulaire d'édition d'une œuvre
   */
  public static function renderEdit($id) {
    if (isset($id) && preg_match('/^Q[0-9]+$/', $id)) {

      require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'updateDB.php');
      $article = get_artwork_from_q($id);

      $values = ArtworkGetData::get_props($id);
      $ids = ArtworkGetData::get_ids($values);
      $labels = ArtworkGetData::get_labels($ids);

      $data = ArtworkGetData::get_data($id, $values, $labels);
      if ($article != '') {
        $data_am = ArtworkGetData::get_data_am($article);
        $data = ArtworkGetData::merge_data($data_am, $data);
      }
    } else
    if (isset($id)) {
      $data = ArtworkGetData::get_data_am($id);
      $data = ArtworkGetData::get_labels_am($data);
      $article = $id;
    } else {
      $data = ArtworkGetData::get_data('', null, null);
      $article = '';
    }

      ob_start();

      if ($article == '' && array_key_exists('label', $data)) {
        ?>
          <script>document.getElementById('firstHeading').getElementsByTagName('span')[0].textContent = "Importer : <?php print $data['label']; ?>"</script>
        <?php
      }
?>
<form id="edit_form" onsubmit="return false;">
  <input type="hidden" id="article" name="article" value="<?php print $article; ?>">
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
          <?php self::render_wikidata($data, $article == ''); ?>
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
          <?php self::render_text($data, 'image_principale', 'Image principale', 'image_principale', false);
          /*self::render_image($data); */?>
        </tbody>
      </table>
    </div>

    <div id="Description" class="edit_section section_unselected">
      <h2> <span class="mw-headline" id="Description_oeuvre"> Description de l'œuvre </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_text($data, 'sous-titre', 'Sous-titre', 'sous-titre', false); ?>
          <?php self::render_textarea($data, 'description', 'Description', 'description', false); ?>
          <?php self::render_item_checkboxes($data, 'P31', 'Domaine(s)', 'P31', Constants::get_domains(), false); ?>
          <?php self::render_text($data, 'precision_type_art', 'Précision sur le domaine', 'precision_type_art', false); ?>
          <?php self::render_item_checkboxes($data, 'P462', 'Couleur(s)', 'P462', Constants::get_colors(), false); ?>
          <?php self::render_text($data, 'precision_couleur', 'Précision sur les couleurs', 'precision_couleur', false); ?>
          <?php self::render_item_checkboxes($data, 'P186', 'Matériau(s)', 'P186', Constants::get_materials(), false); ?>
          <?php self::render_text($data, 'precision_materiaux', 'Précision sur les matériaux', 'precision_materiaux', false); ?>
          <?php self::render_text($data, 'techniques', 'Techniques', 'techniques', false); ?>
          <?php self::render_text($data, 'hauteur', 'Hauteur (m)', 'hauteur', false); ?>
          <?php self::render_text($data, 'profondeur', 'Profondeur (m)', 'profondeur', false); ?>
          <?php self::render_text($data, 'largeur', 'Largeur (m)', 'largeur', false); ?>
          <?php self::render_text($data, 'diametre', 'Diamètre (m)', 'diametre', false); ?>
          <?php self::render_text($data, 'surface', 'Surface (m²)', 'surface', false); ?>
          <?php self::render_text($data, 'precision_dimensions', 'Précision sur les dimensions', 'precision_dimensions', false); ?>
          <?php self::render_text($data, 'symbole', 'Références', 'symbole', false); ?>
          <?php self::render_item($data, 'P921', 'Sujet représenté', 'P921', false); ?>
          <?php self::render_text($data, 'mot_cle', 'Mots clés', 'mot_cle', false); ?>
          <?php self::render_text($data, 'influences', 'Influences', 'influences', false); ?>
          <?php self::render_text($data, 'a_influence', 'À influencé', 'a_influence', false); ?>
          <?php self::render_textarea($data, 'notice_augmentee', 'Notice augmentée', 'notice_augmentee', false); ?>
        </tbody>
      </table>
    </div>

    <div id="Production" class="edit_section section_unselected">
      <h2> <span class="mw-headline" id="Production_oeuvre"> Production de l'œuvre </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_text($data, 'inauguration', 'Date d\'inauguration', 'inauguration', false); ?>
          <?php self::render_text($data, 'restauration', 'Date de restauration', 'restauration', false); ?>
          <?php self::render_text($data, 'fin', 'Date de fin', 'fin', false); ?>
          <?php self::render_text($data, 'precision_date', 'Précision sur les dates', 'precision_date', false); ?>
          <?php self::render_text($data, 'programme', 'Procédure', 'programme', false); ?>
          <?php self::render_text($data, 'numero_inventaire', 'Numéro d\'inventaire', 'numero_inventaire', false); ?>
          <?php self::render_text($data, 'contexte_production', 'Contexte de production', 'contexte_production', false); ?>
          <?php self::render_text($data, 'periode_art', 'Période(s)', 'periode_art', false); ?>
          <?php self::render_item_checkboxes($data, 'P135', 'Mouvement(s)', 'P135', Constants::get_movements(), false); ?>
          <?php self::render_text($data, 'precision_mouvement_artistes', 'Précision sur le mouvement', 'precision_mouvement_artistes', false); ?>
        </tbody>
      </table>
    </div>

    <div id="Site" class="edit_section section_unselected">
      <h2> <span class="mw-headline" id="Site_oeuvre"> Site </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_item($data, 'P276', 'Nom du site', 'P276', false); ?>
          <?php self::render_textarea($data, 'site_details', 'Détails sur le site', 'site_details', false); ?>
          <?php self::render_text($data, 'site_lieu_dit', 'Lieu-dit', 'site_lieu_dit', false); ?>
          <?php self::render_text($data, 'site_adresse', 'Adresse', 'site_adresse', false); ?>
          <?php self::render_text($data, 'site_code_postal', 'Code postal', 'site_code_postal', false); ?>
          <?php self::render_item($data, 'P131', 'Ville, localité...', 'P131', false); ?>
          <?php self::render_text($data, 'site_departement', 'Département, comté, district...', 'site_departement', false); ?>
          <?php self::render_text($data, 'site_region', 'Région, province...', 'site_region', false); ?>
          <?php self::render_item($data, 'P17', 'Pays', 'P17', false); ?>
          <?php self::render_textarea($data, 'site_acces', 'Accès', 'site_acces', false); ?>
          <?php self::render_textarea($data, 'site_visibilite', 'Visibilité', 'site_visibilite', false); ?>
          <?php self::render_item_radio($data, 'P2846', 'Accessibilité PMR', 'P2846', Constants::get_pmr(), false); ?>
          <?php self::render_textarea($data, 'site_urls', 'URLs', 'site urls', false); ?>
          <?php self::render_textarea($data, 'site_pois', 'Points d\'intérêt', 'site_pois', false); ?>
        </tbody>
      </table>
    </div>

    <div id="Acteurs" class="edit_section section_unselected">
      <h2> <span class="mw-headline" id="Acteurs_oeuvre"> Acteurs </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_item($data, 'P1640', 'Commissaires', 'P1640', false); ?>
          <?php self::render_item($data, 'P88', 'Commanditaires', 'P88', false); ?>
          <?php self::render_text($data, 'partenaires_publics', 'Partenaire(s) public(s)', 'partenaires_publics', false); ?>
          <?php self::render_text($data, 'partenaires_prives', 'Partenaire(s) privé(s)', 'partenaires_prives', false); ?>
          <?php self::render_text($data, 'collaborateurs', 'Collaborateur(s)', 'collaborateurs', false); ?>
          <?php self::render_text($data, 'maitrise_oeuvre', 'Maîtrise d\'œuvre', 'maitrise_oeuvre', false); ?>
          <?php self::render_text($data, 'maitrise_oeuvre_deleguee', 'Maîtrise d\'œuvre déléguée', 'maitrise_oeuvre_deleguee', false); ?>
          <?php self::render_text($data, 'maîtrise_ouvrage', 'Maîtrise d\'ouvrage', 'maîtrise_ouvrage', false); ?>
          <?php self::render_text($data, 'maitrise_ouvrage_deleguee', 'Maîtrise d\'ouvrage déléguée', 'maitrise_ouvrage_deleguee', false); ?>
          <?php self::render_text($data, 'proprietaire', 'Propriétaire', 'proprietaire', false); ?>
        </tbody>
      </table>
    </div>

    <div id="Conservation" class="edit_section section_unselected">
      <h2> <span class="mw-headline" id="Conservation_oeuvre"> État de conservation </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_item_checkboxes($data, 'conservation', 'État de conservation', 'conservation', Constants::get_conservation(), false); ?>
          <?php self::render_item_checkboxes($data, 'precision_etat_conservation', 'Précisions sur l\'état de conservation', 'precision_etat_conservation', Constants::get_conservation_precision(), false); ?>
          <?php self::render_textarea($data, 'autre_precision_etat_conservation', 'Autres précisions sur l\'état de conservation', 'autre_precision_etat_conservation', false); ?>
        </tbody>
      </table>
    </div>

    <div id="Sources" class="edit_section section_unselected">
      <h2> <span class="mw-headline" id="Description_oeuvre"> Sources </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_textarea($data, 'source', 'Source', 'source', false); ?>
        </tbody>
      </table>
    </div>

    <div id="Photos" class="edit_section section_unselected">
      <h2> <span class="mw-headline" id="Description_oeuvre"> Construction / installation / Montage </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_textarea($data, 'has_imagegalerieconstruction', 'Construction', 'has_imagegalerieconstruction', false); ?> 
        </tbody>
      </table>

      <h2> <span class="mw-headline" id="Description_oeuvre"> Autres prises de vue </span></h2>
      <table class="formtable">
        <tbody>
          <?php self::render_textarea($data, 'has_imagegalerieautre', 'Autres', 'has_imagegalerieautre', false); ?> 
        </tbody>
      </table>
    </div>

  </div>
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
<script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>edit.js"></script>
<link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>edit.css">
<link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>autocomplete.css">
<link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
<?php

      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;

  }

}
