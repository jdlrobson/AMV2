show_tab = function(tab, section) {
  var list = document.getElementsByClassName('edit_section');
  for (var i=0; i<list.length; i++) {
    list[i].classList.remove('section_selected');
    list[i].classList.add('section_unselected');
  }
  document.getElementById(section).classList.remove('section_unselected');
  document.getElementById(section).classList.add('section_selected');

  list = document.getElementsByClassName('edit_tab');
  for (var i=0; i<list.length; i++) {
    list[i].classList.remove('tab_selected');
    list[i].classList.add('tab_unselected');
  }
  tab.classList.remove('tab_unselected');
  tab.classList.add('tab_selected');
}

add_line = function(id, property, key, mandatory, wikidataId="", wikidataLabel="") {
  var td = document.getElementById(id),
      divs = document.querySelectorAll('#' + id + ' .inputSpan'),
      newLine = document.createElement("div"),
      add = document.querySelectorAll('#' + id + '> .add_button')[0]

  if (divs.length>0)
    n = parseInt(divs[divs.length-1].id.replace(id+'_wrapper_', ''))+1
  else
    n = 0;

  newLine.classList.add('inputSpan')
  newLine.classList.add('autocomplete')
  if (mandatory)
    newLine.classList.add('mandatoryFieldSpan')

  newLine.setAttribute('id', id+'_wrapper_'+n)

  newLine.innerHTML = '<input id="'+id+'_'+n+'" class="createboxInput'+(mandatory ? ' mandatoryField' : '')+'" size="60" value="' + wikidataLabel + '" name="Edit['+key+']['+n+']"><input type="hidden" id="'+id+'_id_'+n+'" name="Edit['+key+'][id]['+n+']" value="' + wikidataId + '"></input> <span class="edit_item_button" title="Supprimer cette ligne" onclick="remove_line(\''+id+'_wrapper_'+n+'\')">[&nbsp;x&nbsp;]</span>';
  
  td.insertBefore(newLine, add)
  autocomplete(document.getElementById(id+'_'+n))
}

remove_line = function(id) {
  document.getElementById(id).remove()
}

get_label = function(id, callback) {
  const url = 'https://www.wikidata.org/w/api.php'
  const params = {
    action: 'wbgetentities',
    ids: id,
    languages: 'fr',
    props: 'labels',
    format: 'json',
    origin: '*',
  }
  $.getJSON(url, params, function(res) {
    if (res && res.entities && res.entities[id] && res.entities[id].labels && res.entities[id].labels.fr)
      callback(res.entities[id].labels.fr.value)
    else
      callback(id)
  })
}

import_wikidata_claim = function(claim, property) {
  for (let i = 0; i < claim.length; i++) {
    let id = claim[i].mainsnak.datavalue.value.id
    get_label(id, function(label) {
      let j = 0
      let found = false
      while (document.getElementById('input_' + property + '_' + j)) {
        const elementLabel = document.getElementById('input_' + property + '_' + j).value
        const elementId = document.getElementById('input_' + property + '_id_' + j).value
        if (elementId === id) {
          found = true
          break
        }
        if (elementLabel === label) {
          found = true
          document.getElementById('input_' + property + '_id_' + j).value = id
          break
        }
        j++
      }
      if (!found) {
        add_line('input_' + property, property, property, true, id, label)
      }
    })
  }
}

import_wikidata = function() {
  let wikidataId = document.getElementById('input_label').value
  let pattern = /^[qQ][0-9]+$/
  if (pattern.test(wikidataId)) {
    const url = 'https://www.wikidata.org/w/api.php'
    const params = {
      action: 'wbgetentities',
      ids: wikidataId,
      languages: 'fr',
      props: 'labels|claims',
      format: 'json',
      origin: '*',
    }
    $.getJSON(url, params, function(res) {
      if (res.entities && res.entities[wikidataId]) {
        const labels = res.entities[wikidataId].labels
        const claims = res.entities[wikidataId].claims
        if (labels && labels.fr) {
          // Label
          if (document.getElementById('input_2').value === '') {
            document.getElementById('input_2').value = labels.fr.value
          }
        }
        if (claims) {
          // Coordonnées
          if (claims.P625) {
            if (document.getElementById('coordinates_input').value === '' || document.getElementById('coordinates_input').value === '0, 0') {
              latitude = claims.P625[0].mainsnak.datavalue.value.latitude
              longitude = claims.P625[0].mainsnak.datavalue.value.longitude
              document.getElementById('coordinates_input').value = latitude + ', ' + longitude
            }
          }

          // Créateurs
          if (claims.P170) {
            import_wikidata_claim(claims.P170, 'P170')
          }

          // Localisation
          if (claims.P131) {
            import_wikidata_claim(claims.P131, 'P131')
          }

          // Pays
          if (claims.P17) {
            import_wikidata_claim(claims.P17, 'P17')
          }

          // Site
          if (claims.P276) {
            import_wikidata_claim(claims.P276, 'P276')
          }

          // Commanditaires
          if (claims.P88) {
            import_wikidata_claim(claims.P88, 'P88')
          }

          // Commissaires
          if (claims.P1640) {
            import_wikidata_claim(claims.P1640, 'P1640')
          }
        }
      }
    })
  }
}

publish = function() {
  var form_data = $('#edit_form').serializeArray(),
      data = {},
      article = '',
      equivalents = {
        'id': 'q',
        'label': 'titre',
        'nature': 'nature',
        'P31': 'type_art',
        'P170': 'artiste',
        'P625': 'site_coordonnees',
        'P131': 'site_ville',
        'P17': 'site_pays',
        'P2846' : 'site_pmr',
        'P462': 'couleur',
        'P186': 'materiaux',
        'P135': 'mouvement',
        'P88': 'commanditaires',
        'P1640': 'commissaires',
        'P276': 'site_nom',
        'P921': 'forme',
      },
      db_data = {
        'article': '',
        'title': '',
        'artist': '',
        'nature': '',
        'latitude': '',
        'longitude': '',
        'image': '',
        'date': '',
        'wikidata': ''
      }

  //console.log(form_data);

  for (var i=0; i<form_data.length; i++)
    if (form_data[i].name == 'article') {
      article = form_data[i].value
    }
    else {
    params = form_data[i].name.replace(/Edit\[(.*)\]$/, '$1').split('][');
    let eq
    if (equivalents[params[0]])
      eq = equivalents[params[0]]
    else
      eq = params[0]

    if(params.length == 1) {
      data[eq] = form_data[i].value;
    } else
    if(params.length == 2) {
      let index = params[1]
      if (!data[eq])
        data[eq] = [];
      if (!data[eq][index])
        data[eq][index] = {'label':'','id':''};
      data[eq][index]['label'] = form_data[i].value;
    } else
    if(params.length == 3) {
      let index = params[2]
      if (!data[eq])
        data[eq] = [];
      if (!data[eq][index]) 
        data[eq][index] = {'label':'','id':''};
      data[eq][index][params[1]] = form_data[i].value;
    }
  }

  console.log(data)

  var text = '<ArtworkPage\n';

  for (var key in data) {
    if (Array.isArray(data[key])) {
      var r = [];
      for (var i in data[key]) {
        if (data[key][i].id != '')
          r.push(data[key][i].id)
        else
        if (data[key][i].label != '')
          r.push(data[key][i].label.replace(/\"/g, '&quot;').replace(/\n/g, '\\n').replace(/\r/g, ''))
      }
      text += key + '="' + r.join(';') + '"\n';
    } else {
      if (data[key] != '')
        text += key + '="' + data[key].replace(/\"/g, '&quot;').replace(/\n/g, '\\n').replace(/\r/g, '') + '"\n';
    }
  }

  text += '/>\n'

  text += get_semantic(data)

  text += '[[Catégorie:Notices d\'œuvre]]'

  console.log(text)

  //console.log($('#real_edit_form').serializeArray());

  //-- Données DB
  db_data = parse_data_for_db(data)
  console.log(db_data)

  //-- Envoi des données
  document.getElementById('wpTextbox1').value = text;
  if (article != '') {
    const params = {
      action: 'update_artwork',
      article: article,
      title: db_data.title,
      artist: db_data.artist,
      nature: db_data.nature,
      latitude: db_data.latitude,
      longitude: db_data.longitude,
      image: db_data.image,
      date: db_data.date,
      wikidata: db_data.wikidata
    }
    const ret = [];
    for (let p in params)
     ret.push(encodeURIComponent(p) + '=' + encodeURIComponent(params[p]));
    const url = 'http://publicartmuseum.net/tmp/w/extensions/WikidataBundle/utils/php/updateDB.php?' + ret.join('&')
    console.log(url)
    $.get(url, function(res) {
      console.log(res)
      document.getElementById('editform').action = '/tmp/w/index.php?title=' + article + '&action=submit';
      document.getElementById("editform").submit();
    })
  } else {
    const params = {
      action: 'create_artwork',
      article: db_data.article,
      title: db_data.title,
      artist: db_data.artist,
      nature: db_data.nature,
      latitude: db_data.latitude,
      longitude: db_data.longitude,
      image: db_data.image,
      date: db_data.date,
      wikidata: db_data.wikidata
    }
    const ret = [];
    for (let p in params)
     ret.push(encodeURIComponent(p) + '=' + encodeURIComponent(params[p]));
    const url = 'http://publicartmuseum.net/tmp/w/extensions/WikidataBundle/utils/php/updateDB.php?' + ret.join('&')
    console.log(url)
    $.get(url, function(res) {
      console.log(res)
      document.getElementById('editform').action = '/tmp/w/index.php?title=' + db_data.article + '&action=submit';
      document.getElementById("editform").submit();
    })
  }
}

parse_data_for_db = function(data) {
  var db_data = {
    'article': '',
    'title': '',
    'artist': '',
    'nature': '',
    'latitude': 0,
    'longitude': 0,
    'image': '',
    'date': '',
    'wikidata': ''
  }

  db_data.title = (data.titre ? data.titre : '')

  var artist = []
  for (var i in data.artiste)
    artist.push(data.artiste[i].label)
  db_data.artist = artist.join(';')

  db_data.article = db_data.title + ' (' + artist.join(', ') + ')'

  db_data.nature = (data.nature ? data.nature : '')

  if (data.site_coordonnees) {
    var coords = data.site_coordonnees.split(', ')
    db_data.latitude = coords[0]
    db_data.longitude = coords[1]
  }

  db_image = (data.image_principale ? data.image_principale : '')

  db_data.date = (data.inauguration ? data.inauguration : '')

  db_data.wikidata = (data.q ? data.q : '')

  return db_data
}

get_semantic = function(data) {
  semantic = {
    'a_influence': 'A influencé',
    'artiste': 'Auteur',
    'commissaires': 'Commissaires',
    'conservation': 'Conservation',
    'site_coordonnees': 'Coordonnées',
    'couleur': 'Couleur',
    'inauguration': 'Date d\'inauguration',
    'fin': 'Date de fin',
    'restauration': 'Date de restauration',
    'site_departement': 'Département',
    'diametre': 'Diamètre',
    'forme': 'Forme',
    'has_imagegalerieautre': 'Has ImageGalerieAutre',
    'has_imagegalerieconstruction': 'Has ImageGalerieConstruction',
    'hauteur': 'Hauteur',
    'image_principale': 'Image principale',
    'influences': 'Influences',
    'largeur': 'Largeur',
    'site_lieu_dit': 'Lieu-dit',
    'longueur': 'Longueur',
    'materiaux': 'Matériaux',
    'mot_cle': 'Mot-clé',
    'mouvement_artistes': 'Mouvement d\'artistes',
    'nature': 'Nature',
    'numero_inventaire': 'Numéro d\'inventaire',
    'site_pays': 'Pays',
    'periode_art': 'Période_d\'art',
    'site_pmr': 'PMR',
    'precision_etat_conservation': 'Précision état de conservation',
    'programme': 'Programme',
    'proprietaire': 'Propriétaire',
    'site_region': 'Région',
    'site_nom': 'Site',
    'surface': 'Surface',
    'symbole': 'Symbole',
    'titre': 'Titre',
    'type_art': 'Type d\'art',
    'site_ville': 'Ville',
  }

  text = '<div style="visibility:hidden; height:0px">\n'

  for (var key in data) {
    if (data[key] != '' && semantic[key]) {
      if (Array.isArray(data[key])) {
        for (var i in data[key])
          if (data[key][i].id != '')
            text += '[[' + semantic[key] + '::' + data[key][i].id + ']]\n'
          else
            text += '[[' + semantic[key] + '::' + data[key][i].label + ']]\n'
      } else {
        s = data[key].split(';')
        for (var i=0; i<s.length; i++)
          text += '[[' + semantic[key] + '::' + s[i] + ']]\n'
      }
    }
  }

  text += '</div>\n'

  return text
}

change_map_input = function() {
  coords = document.getElementById('coordinates_input').value.split(/[\s]*,[\s]*/)

  if (coords.length == 2) {
    var latitude = parseFloat(coords[0]),
        longitude = parseFloat(coords[1]);

    if (!isNaN(latitude) && !isNaN(longitude) && latitude>=-90 && latitude<=90 && longitude>=-180 && longitude <=180) {
      
      var new_location = ol.proj.transform([longitude, latitude], "EPSG:4326", "EPSG:3857")
      marker.getGeometry().setCoordinates(new_location);
      map.getView().setCenter(new_location);
    }
  }
}

change_map_address = function() {
  address = document.getElementById('address_input').value
  if (address != "") {
    var params = {
      "address": address,
      "key": "AIzaSyBlETt3Lsnmn6Rz7eE42Fwtci0ZU6UUBkU"
    }
    
    $.getJSON('https://maps.google.com/maps/api/geocode/json', params).then(function(res) {
    
      if (res.results.length > 0) {
        var new_location = ol.proj.transform([res.results[0].geometry.location.lng, res.results[0].geometry.location.lat], "EPSG:4326", "EPSG:3857")
        marker.getGeometry().setCoordinates(new_location);
        map.getView().setCenter(new_location);

        document.getElementById('coordinates_input').value = res.results[0].geometry.location.lat + ", " + res.results[0].geometry.location.lng
      }
    });
  }
}

document.addEventListener("DOMContentLoaded", function(event) {
  coords = document.getElementById('coordinates_input').value.split(/[\s]*,[\s]*/)

  marker = new ol.Feature({
    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(coords[1]), parseFloat(coords[0])], "EPSG:4326", "EPSG:3857"))
  });
  var extent = marker.getGeometry().getExtent().slice(0);
  var raster = new ol.layer.Tile({
    source: new ol.source.OSM()
  });
  var vectorSource = new ol.source.Vector({
    features: [marker]
  });
  var iconStyle = new ol.style.Style({
    image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
      anchor: [0.5, 46],
      anchorXUnits: 'fraction',
      anchorYUnits: 'pixels',
      opacity: 0.75,
      src: 'http://publicartmuseum.net/tmp/w/images/a/a0/Picto-gris.png'
    }))
  });
  var vectorLayer = new ol.layer.Vector({
    source: vectorSource,
    style: iconStyle
  });
  map = new ol.Map({
    layers: [raster, vectorLayer],
    target: "map"
  });
  map.getView().fit(extent);
  map.getView().setZoom(15);

	map.on('click', function(evt) {
		map.getView().setCenter(evt.coordinate);
		var coordinates = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
		marker.getGeometry().setCoordinates(evt.coordinate);

    document.getElementById('coordinates_input').value = coordinates[1] + ", " + coordinates[0];
	});
});