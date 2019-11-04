publish = function() {
  var form_data = $('#edit_form').serializeArray(),
      data = {},
      article = '',
      equivalents = {
      },
      db_data = {
        'article': '',
        'title': '',
        'wikidata': ''
      }

  console.log(form_data);
  
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

  var text = '<CollectionPage\n';

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

  text += '[[Catégorie:Collections]]'

  console.log(text)

  //console.log($('#real_edit_form').serializeArray());

  //-- Données DB
  //db_data = parse_data_for_db(data, article)
  //console.log(db_data)

  //-- Envoi des données
  document.getElementById('wpTextbox1').value = text;
  if (article != '') {
    /*
    const params = {
      action: 'update_collection',
      article: article,
      wikidata: db_data.wikidata
    }
    const ret = [];
    for (let p in params)
     ret.push(encodeURIComponent(p) + '=' + encodeURIComponent(params[p]));
    const url = 'http://publicartmuseum.net/w/extensions/WikidataBundle/utils/php/updateDB.php?' + ret.join('&')
    console.log(url)
    $.get(url, function(res) {
      console.log(res)
      */
      document.getElementById('editform').action = '/w/index.php?title=' + article + '&action=submit';
      document.getElementById("editform").submit();
      /*
    })
    */
  } else {
    /*
    const params = {
      action: 'create_artist',
      article: db_data.article,
      wikidata: db_data.wikidata
    }
    const ret = [];
    for (let p in params)
     ret.push(encodeURIComponent(p) + '=' + encodeURIComponent(params[p]));
    const url = 'http://publicartmuseum.net/w/extensions/WikidataBundle/utils/php/updateDB.php?' + ret.join('&')
    console.log(url)
    $.get(url, function(res) {
      console.log(res)
      */
      document.getElementById('editform').action = '/w/index.php?title=' + db_data.article + '&action=submit';
      document.getElementById("editform").submit();
      /*
    })
    */
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
    'visuel': 'Visuel',
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

  text += '{{#arraymap:"' + data['notices'] + '"|;|x|[[Contient la notice::x| ]]|; }}\n';

  text += '</div>\n'

  return text
}

get_image_am = function(image, width, callback) {
  const url = 'http://publicartmuseum.net/w/api.php'
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