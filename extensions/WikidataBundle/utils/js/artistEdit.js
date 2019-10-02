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

import_wikidata_claim = function(claim, property, key) {
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
        add_line('input_' + property, property, key, true, id, label)
      }
    })
  }
}

import_wikidata_date = function(claim, property) {
  let tmp = claim[0].mainsnak.datavalue.value.time.replace(/T.*$/g, '').substring(1).split('-')
  let precision = claim[0].mainsnak.datavalue.value.precision
  let time = ''

  if (precision >= 11) 
    time = tmp[2] + '-' + tmp[1] + '-' + tmp[0]
  else
  if (precision === 10)
    time = tmp[1] + '-' + tmp[0]
  else
    time = tmp[0]

  document.getElementById('input_' + property).value = time
}

import_wikidata_image = function(claim, property) {
  let image = claim[0].mainsnak.datavalue.value

  document.getElementById('input_' + property).value = image
  document.getElementById('input_checkbox_' + property).checked = true
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
        /*
        if (labels && labels.fr) {
          // Label
          if (document.getElementById('input_2').value === '') {
            document.getElementById('input_2').value = labels.fr.value
          }
        }
        */
        if (claims) {
          // Prénom
          if (claims.P735) {
            import_wikidata_claim(claims.P735, 'P735', 'prenom')
          }

          // Nom
          if (claims.P734) {
            import_wikidata_claim(claims.P734, 'P734', 'nom')
          }

          // Date de naissance
          if (claims.P569) {
            import_wikidata_date(claims.P569, 'P569')
          }

          // Lieu de naissance
          if (claims.P19) {
            import_wikidata_claim(claims.P19, 'P19', 'birthplace')
          }

          // Date de décès
          if (claims.P570) {
            import_wikidata_date(claims.P570, 'P570')
          }

          // Lieu de décès
          if (claims.P20) {
            import_wikidata_claim(claims.P20, 'P20', 'deathplace')
          }

          // Mouvement
          if (claims.P135) {
            import_wikidata_claim(claims.P135, 'P135', 'movement')
          }

          // Pays de nationalité
          if (claims.P27) {
            import_wikidata_claim(claims.P27, 'P27', 'nationality')
          }

          // Image
          if (claims.P18) {
            import_wikidata_image(claims.P18, 'P18')
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

  if (data['societe_gestion_droit_auteur'] && data['societe_gestion_droit_auteur'] === 'on') {
    data['societe_gestion_droit_auteur'] = 'oui'
  } else {
    data['societe_gestion_droit_auteur'] = 'non'
  }

  if (data['thumbnail_origin'] && data['thumbnail_origin'] === 'on') {
    if (data['thumbnail']) {
      data['thumbnail'] = 'Commons:' + data['thumbnail']
    }
    delete data['thumbnail_origin']
  }

  console.log(data)

  var text = '<ArtistPage\n';

  for (var key in data) {
    if (Array.isArray(data[key])) {
      var r = [];
      for (var i in data[key]) {
        if (data[key][i].id != '')
          r.push(data[key][i].id)
        else
        if (data[key][i].label != '')
          r.push(data[key][i].label.replace(/\"/g, '&quot;').replace(/\n/g, '\\n').replace(/\r/g, '').replace(/</g, '&lt;').replace(/>/g, '&gt;'))
      }
      text += key + '="' + r.join(';') + '"\n';
    } else
    if (data[key] != '')
      text += key + '="' + data[key].replace(/\"/g, '&quot;').replace(/\n/g, '\\n').replace(/\r/g, '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '"\n';
  }

  text += '/>\n'

  text += get_semantic(data)

  text += '[[Catégorie:Artistes]]'

  console.log(text)

  //console.log($('#real_edit_form').serializeArray());

  //-- Données DB
  db_data = parse_data_for_db(data, article)
  console.log(db_data)

  //-- Envoi des données
  document.getElementById('wpTextbox1').value = text;
  if (article != '') {
    const params = {
      action: 'update_artist',
      article: article,
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
      action: 'create_artist',
      article: db_data.article,
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

parse_data_for_db = function(data, article='') {
  var db_data = {
    'article': article,
    'wikidata': ''
  }

  db_data.article = db_data.article

  db_data.wikidata = (data.q ? data.q : '')

  return db_data
}

get_semantic = function(data) {
  semantic = {
    'nom': 'Nom',
    'abstract': 'Abstract',
    'dateofbirth': 'Date de naissance',
    'birthplace': 'Lieu de naissance',
    'deathdate': 'Date de décès',
    'deathplace': 'Lieu de décès',
    'nationality': 'Nationalité',
    'movement': 'Mouvement',
    'societe_gestion_droit_auteur': 'societe_gestion_droit_auteur',
    'nom_societe_gestion_droit_auteur': 'nom_societe_gestion_droit_auteur',
    'titre': 'Titre',
    'thumbnail': 'Portrait',
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

get_image_am = function(image, width, callback) {
  const url = 'http://publicartmuseum.net/tmp/w/api.php'
  const params = {
    action: 'query',
    prop: 'imageinfo',
    iiprop: 'url',
    iiurlwidth: width,
    titles: 'File:' + image,
    format: 'json',
  }
  $.getJSON(url, params, function(res) {
    if (res && res.query && res.query.pages) {
      const keys = Object.keys(res.query.pages)
      callback(res.query.pages[keys[0]].imageinfo[0].thumburl)
    } else {
      callback('')
    }
  })
}

change_image_thumb = function(inputId) {
  const input = document.getElementById(inputId);
  if (input) {
    const thumb = document.getElementById(inputId + '_thumb');
    if (thumb) {
      const imageName = input.value
      get_image_am(imageName, 200, function(imageUrl) {
        if (imageUrl != '')
          thumb.getElementsByTagName('img')[0].src = imageUrl
      })
    }
  }
}
