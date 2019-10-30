export_artwork = function() {
  const article = decodeURI(window.location.href.replace(/^.*:WikidataExport\//, '')).replace(/_/g, ' ')
  const textarea = document.getElementById('export_data');
  const lines = textarea.value.split('\n')

  let id = ''
  let i = 1
  if (lines[0] !== 'CREATE') {
    id = lines[0].replace(/\t.*$/, '')
    i = 0
  }

  let data = {}
  for (i; i < lines.length; i++)
    if (lines[i] != '') {
      const l = lines[i].split('	')
      switch (l[1]) {
        case 'Lfr':
          data['L'] = {
            fr: l[2].replace(/^\"(.*)\"$/, '$1')
          }
          break;
        case 'Dfr':
            data['D'] = {
              fr: l[2].replace(/^\"(.*)\"$/, '$1')
            }
          break;
        case 'P625':
          const c = l[2].replace('@', '').split('/')
          data['P625'] = {
            lat: parseFloat(c[0]),
            lng: parseFloat(c[1])
          }
          break;
        default:
          if (!data[l[1]])
            data[l[1]] = []
          data[l[1]].push(l[2])
          break;
      }
    }

  /*
  let url = 'http://lerunigo.fr/tmp/am/updateWD.php?'
  if (id != '')
    url += 'id=' + id + '&'
  url += 'origin=' + encodeURIComponent(article) + '&'
  url += 'data=' + encodeURIComponent(JSON.stringify(data))
  */

  const url = 'http://lerunigo.fr/tmp/am/updateWD.php'
  const params = {
    origin: 'article',
    data: JSON.stringify(data),
    format: 'json',
    origin: '*',
  }
  if (id != '')
    params['id'] = id

  $.getJSON(url, params, function(res) {
    console.log(res.id)

    if (id === '') {
      // Update database
      const params = {
        action: 'update_artwork_wikidata',
        article: article,
        wikidata: res.id
      }
      const ret = [];
      for (let p in params)
      ret.push(encodeURIComponent(p) + '=' + encodeURIComponent(params[p]));
      const url = 'http://publicartmuseum.net/w/extensions/WikidataBundle/utils/php/updateDB.php?' + ret.join('&')
      $.get(url, function(res) {
        // Update article
      })
    }
  })
}
