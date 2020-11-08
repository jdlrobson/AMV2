addLine = function(id, property, key, mandatory, wikidataId="", wikidataLabel="") {
  var td = document.getElementById(id + '_cell'),
      divs = document.querySelectorAll('#' + id + '_cell' + ' .inputSpan'),
      newLine = document.createElement("div"),
      add = document.querySelectorAll('#' + id + '_cell' + '> .add_button')[0]

  if (divs.length>0)
    n = parseInt(divs[divs.length-1].id.replace(id+'_wrapper_', ''))+1
  else
    n = 0;

  newLine.classList.add('inputSpan')
  newLine.classList.add('autocomplete')
  if (mandatory)
    newLine.classList.add('mandatoryFieldSpan')

  newLine.setAttribute('id', id+'_wrapper_'+n)

  newLine.innerHTML = '<input id="'+id+'_'+n+'" class="createboxInput'+(mandatory ? ' mandatoryField' : '')+'" size="60" value="' + wikidataLabel + '" name="Edit['+key+']['+n+']"><input type="hidden" id="'+id+'_id_'+n+'" name="Edit['+key+'][id]['+n+']" value="' + wikidataId + '"></input> <span class="edit_item_button" title="Supprimer cette ligne" onclick="removeLine(\''+id+'_wrapper_'+n+'\')">[&nbsp;x&nbsp;]</span>';
  
  td.insertBefore(newLine, add)
  autocomplete(document.getElementById(id+'_'+n))
}

removeLine = function(id) {
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

importWikidataClaim = function(claim, property) {
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
        addLine('input_' + property, property, property, true, id, label)
      }
    })
  }
}

importWikidataDate = function(claim, property) {
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

importWikidataImage = function(claim, property) {
  let image = claim[0].mainsnak.datavalue.value

  document.getElementById('input_' + property).value = image
  document.getElementById('input_checkbox_' + property).checked = true
}

importWikidata = function() {
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
            importWikidataClaim(claims.P735, 'prenom')
          }

          // Nom
          if (claims.P734) {
            importWikidataClaim(claims.P734, 'nom')
          }

          // Date de naissance
          if (claims.P569) {
            importWikidataDate(claims.P569, 'dateofbirth')
          }

          // Lieu de naissance
          if (claims.P19) {
            importWikidataClaim(claims.P19, 'birthplace')
          }

          // Date de décès
          if (claims.P570) {
            importWikidataDate(claims.P570, 'deathdate')
          }

          // Lieu de décès
          if (claims.P20) {
            importWikidataClaim(claims.P20, 'deathplace')
          }

          // Mouvement
          if (claims.P135) {
            importWikidataClaim(claims.P135, 'movement')
          }

          // Pays de nationalité
          if (claims.P27) {
            importWikidataClaim(claims.P27, 'nationality')
          }

          // Image
          if (claims.P18) {
            importWikidataImage(claims.P18, 'thumbnail')
          }
        }
      }
    })
  }
}

publish = function() {
  var form_data = $('#edit_form').serializeArray(),
      data = {},
      article = ''

  //console.log(form_data);

  for (var i=0; i<form_data.length; i++)
    if (form_data[i].name == 'article') {
      article = form_data[i].value
    }
    else {
    params = form_data[i].name.replace(/Edit\[(.*)\]$/, '$1').split('][');
    const eq = params[0]

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

  // console.log(data)

  var text = '{{Artiste\n';

  for (var key in data) {
    if (Array.isArray(data[key])) {
      var r = [];
      for (var i in data[key]) {
        if (data[key][i].id != '')
          r.push(data[key][i].id)
        else
        if (data[key][i].label != '')
          r.push(data[key][i].label
            .replace(/\"/g, '&quot;')
            .replace(/\n/g, '\\n')
            .replace(/\r/g, '')
            .replace(/<br[\s]*\/[\s]*>/gi, '\\n')
          )
      }
      text += '|' + key + '=' + r.join(';') + '\n';
    } else
    if (data[key] != '')
      text += '|' + key + '=' + data[key]
        .replace(/\"/g, '&quot;')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '')
        .replace(/<br[\s]*\/[\s]*>/gi, '\\n')
        + '\n';
  }

  text += '}}\n'

  // console.log(text)

  //console.log($('#real_edit_form').serializeArray());

  //-- Envoi des données
  document.getElementById('wpTextbox1').value = text;
  if (article != '') {
    document.getElementById('editform').action = '/w/index.php?title=' + article + '&action=submit';
    document.getElementById("editform").submit();
  } else {
    article = createArticleName(data)
    document.getElementById('editform').action = '/w/index.php?title=' + article + '&action=submit';
    document.getElementById("editform").submit();
  }
}

createArticleName = function(data) {
  const article = []
  for (let i in data.prenom)
    article.push(data.prenom[i].label)
  for (let i in data.nom)
    article.push(data.nom[i].label)

  return article.join(' ')
}

get_image_am = function(image, width, callback) {
  const url = 'http://atlasmuseum.net/w/api.php'
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
