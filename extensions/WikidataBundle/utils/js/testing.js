$(document).ready(function() {
  loadMap()
  loadArtists()
  loadImages()
})

/**
 * Initialisation de la carte
 */
function loadMap() {
  const mapDiv = $('#map')
  let lat = 0
  let lon = 0

  // Récupération latitude/longitude passées dans la div d'id "map"
  if (mapDiv) {
    if (mapDiv.data('lat'))
      lat = mapDiv.data('lat')
    if (mapDiv.data('lon'))
      lon = mapDiv.data('lon')
  }

  // Création de la carte
  marker = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.transform([lon, lat], "EPSG:4326", "EPSG:3857")) })
  const extent = marker.getGeometry().getExtent().slice(0)
  const raster = new ol.layer.Tile({ source: new ol.source.OSM() })
  const vectorSource = new ol.source.Vector({ features: [marker] })
  const iconStyle = new ol.style.Style({
    image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
      anchor: [0.5, 46],
      anchorXUnits: 'fraction',
      anchorYUnits: 'pixels',
      opacity: 0.75,
      src: 'http://publicartmuseum.net/w/images/a/a0/Picto-gris.png'
    }))
  })
  const vectorLayer = new ol.layer.Vector({
    source: vectorSource,
    style: iconStyle
  })
  const map = new ol.Map({
    layers: [raster, vectorLayer],
    target: "map"
  })
  map.getView().fit(extent)
  map.getView().setZoom(15)
}

toggleFold = function(e) {
  parent = e.parentElement;
  for (var i = 0; i < parent.childNodes.length; i++)
    if (parent.childNodes[i].classList && parent.childNodes[i].classList.contains('atmslideshowContent')) {
      if (parent.childNodes[i].style.display == 'none') {
        parent.childNodes[i].style.display = 'block'
        e.classList.add('atmslideshowHeadExt');
      } else {
        parent.childNodes[i].style.display = 'none'
        e.classList.remove('atmslideshowHeadExt');
      }
    }
}

toggleNoticePlus = function(e) {
  parent = e.parentElement;
  if (parent.classList.contains('noticePlusExpanded'))
    parent.classList.remove('noticePlusExpanded')
  else
    parent.classList.add('noticePlusExpanded')
}

toggleNoticePlusHeader = function(e) {
  parent = e.parentElement
  folded = e.classList.contains('ibCtnrDisplayed')
  for (var i = 0; i < parent.childNodes.length; i++)
    if (parent.childNodes[i].style && parent.childNodes[i] != e) {
      if (folded)
        parent.childNodes[i].style.display = 'table'
      else
        parent.childNodes[i].style.display = 'none'
    }

  if (folded)
    e.classList.remove('ibCtnrDisplayed')
  else
    e.classList.add('ibCtnrDisplayed')
}

/**
 * Chargement des artistes
 */
function loadArtists() {
  $('.artist').each((index, artistDiv) => {
    const article = $(artistDiv).data('article')
    if (article) {
      const params = {
        action: 'amgetartist',
        article,
        redirect: 'true',
      }
      
      $.getJSON('http://publicartmuseum.net/w/amapi/index.php?' + $.param(params))
        .then(data => {
          // Supprime le loader
          $(artistDiv).find('.loader').remove()
          if (data.success === 1) {
            // Image
            if (data.entities.data.thumbnail) {
              $(artistDiv).append('<p style="text-align:center"><a href="' + data.entities.data.thumbnail.value[0].url + '" class="image"><img alt="" src="' + data.entities.data.thumbnail.value[0].thumbnail + '" style="width:auto;max-width224px;max-height:149px"></a></p>')
            } else {
              $(artistDiv).append('<p style="text-align:center"><a href="http://publicartmuseum.net/wiki/Fichier:Image-manquante.jpg" class="image"><img alt="" src="http://publicartmuseum.net/w/images/5/5f/Image-manquante.jpg" style="width:auto;max-width224px;max-height:149px"></a></p>')
            }

            let table = '<table class="wikitable"><tbody>'
            if (data.entities.data.abstract) {
              table += '<td colspan="2"><br />' + data.entities.data.abstract.value[0] +'</td></tr>'
            }
            if (data.entities.data.birthplace) {
              table += '<th>Lieu de naissance</th><td>' + data.entities.data.birthplace.value.map(item => item.label).join(', ') +'</td></tr>'
            }
            if (data.entities.data.dateofbirth) {
              table += '<th>Date de naissance</th><td>' + data.entities.data.dateofbirth.value.join(', ') +'</td></tr>'
            }
            if (data.entities.data.deathplace) {
              table += '<th>Lieu de décès</th><td>' + data.entities.data.deathplace.value.map(item => item.label).join(', ') +'</td></tr>'
            }
            if (data.entities.data.deathdate) {
              table += '<th>Date de décès</th><td>' + data.entities.data.deathdate.value.join(', ') +'</td></tr>'
            }
            if (data.entities.data.nationality) {
              table += '<th>Nationalité</th><td>' + data.entities.data.nationality.value.map(item => item.label).join(', ') +'</td></tr>'
            }
            table += '</tbody></table>'
            $(artistDiv).append(table)
          }
        })
    }
});
}

/**
 * Chargement des images
 */
function loadImages() {
  $('.image-loader').each((index, imageDiv) => {
    const origin = $(imageDiv).data('origin')
    const value = $(imageDiv).data('value')
    const width = $(imageDiv).data('width') ? $(imageDiv).data('width') : 192
    console.log(origin, value, width)
    const params = {
      action: 'amgetimage',
      image: value,
      origin,
      width,
    }

    /*
    $.getJSON('http://publicartmuseum.net/w/amapi/index.php?' + $.param(params))
      .then(data => {
      })
    */
  })
}
