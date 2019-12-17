var artworks_map = [];
var clusterSource;

$(document).ready(function() {
  let data = null

 $.getJSON('http://publicartmuseum.net/w/amapi/index.php?action=amgetmap')
    .then(function(result) {
      data = result.entities
      if (data) {
        initMap(data)
      }
    })
})

/**
 * Initialisation de la carte, après réception des données
 */
initMap = function (data) {
  $('#map-loader').hide()
  artworks_map = data
  createMap(data)
  $('#map-loader').remove()
  $('.map-checkbox').removeAttr("disabled")
}

/**
 * Création d'une carte
 */
createMap = function(artworksData, divId = 'map') {
  const features = []
  for (let key in artworksData)
    features.push(new ol.Feature({
      geometry: new ol.geom.Point(ol.proj.transform([artworksData[key].lon, artworksData[key].lat], "EPSG:4326", "EPSG:3857")),
      title: artworksData[key].title,
      artist: artworksData[key].artist,
      id: artworksData[key].wikidata,
      nature: artworksData[key].nature,
      article: artworksData[key].article,
    }))

  const extent = features[0].getGeometry().getExtent().slice(0)
  features.forEach(function(feature){ ol.extent.extend(extent,feature.getGeometry().getExtent())})
    
  const raster = new ol.layer.Tile({
    source: new ol.source.OSM()
  })

  const source = new ol.source.Vector({
    features: features
  })

  clusterSource = new ol.source.Cluster({
    distance: 50,
    source: source
  })

  const clusters = createClusters(clusterSource)

  const popupContainer = document.getElementById(divId + "-popup")
  const popupContent = document.getElementById(divId + "-popup-content")
  const popupCloser = document.getElementById(divId + "-popup-closer")

  popupCloser.onclick = function() {
    overlay.setPosition(undefined)
    popupCloser.blur()
    return false
  }
  
  const overlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
    element: popupContainer,
    autoPan: true,
    autoPanAnimation: {
      duration: 250
    },
  }))

  const map = new ol.Map({
    layers: [raster, clusters],
    overlays: [overlay],
    target: divId,
  })

  map.getView().setMinZoom(1)
  map.getView().fit(extent)

  map.on("pointermove", function (e) {
    let newStyle = this.hasFeatureAtPixel(e.pixel) ? "pointer" : ""
    this.getTargetElement().style.cursor = newStyle
  })

  createPopup(map, overlay, popupContainer, popupContent, popupCloser)
}

createClusters = function(clusterSource) {
  const styleCache = {}

  return new ol.layer.Vector({
    source: clusterSource,
    style: function(feature) {
      const size = feature.get("features").length;
      let style = styleCache[size];
      if (!style || size==1) {
        if (size<5) {
          radius = 10
          color_in = [0, 140, 255]
          color_out = [0, 140, 255, 0.3]
        }
        else if (size<10) {
          radius = 12
          color_in = [255, 191, 0]
          color_out = [255, 191, 0, 0.3]
        }
        else if (size<20) {
          radius = 14
          color_in = [255, 0, 0]
          color_out = [255, 0, 0, 0.3]
        }
        else {
          radius = 15
          color_in = [255, 0, 237]
          color_out = [255, 0, 237, 0.3]
        }
        
        if (size>1)
          style = [new ol.style.Style({
            image: new ol.style.Circle({
              radius: radius+5,
              fill: new ol.style.Fill({ color: color_out })
            })
          }),new ol.style.Style({
            image: new ol.style.Circle({
              radius: radius,
              fill: new ol.style.Fill({ color: color_in })
            }),
            text: new ol.style.Text({
              text: size.toString(),
              font: 'bold 11px Arial, Verdana, Helvetica, sans-serif',
              fill: new ol.style.Fill({ color: "#000" })
            })
          })];
          else {
            let icon_src = "";
            switch (feature.get("features")[0].get("nature")) {
              case "pérenne":
                icon_src = "http://publicartmuseum.net/w/images/a/a0/Picto-gris.png";
                break;
              case "éphémère":
                icon_src = "http://publicartmuseum.net/w/images/4/49/Picto-jaune.png";
                break;
              case "détruite":
                icon_src = "http://publicartmuseum.net/w/images/a/a8/Picto-rouge.png";
                break;
              case "non réalisée":
                icon_src = "http://publicartmuseum.net/w/images/2/2d/Picto-blanc.png";
                break;
              case "à vérifier":
                icon_src = "http://publicartmuseum.net/w/images/9/90/Picto-bleu.png";
                break;
              default:
                icon_src = "http://publicartmuseum.net/w/images/d/dd/Picto-Wikidata.png";
            }
                
            style = new ol.style.Style({
              image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                anchor: [0.5, 46],
                anchorXUnits: "fraction",
                anchorYUnits: "pixels",
                src: icon_src
              }))
            });
          }
          styleCache[size] = style
      }
      return style
    }
  });
}

createPopup = function (map, overlay, container, content, closer) {
  map.on("click", function(evt) {
    let feature = map.forEachFeatureAtPixel(evt.pixel, function(feature) { return feature })
    container.style.visibility = "visible"
    if (typeof feature === "undefined") {
      overlay.setPosition(undefined)
      closer.blur()
      return false
    }
    else if (feature.get("features").length == 1) {
      const features = feature.get("features")
      let article = features[0].get("article")
      let title = features[0].get("title")
      let artist = features[0].get("artist")
      const nature = features[0].get("nature")
      const id = features[0].get("id")

      const coordinate = evt.coordinate

      let link = ''
      if (article != '')
        link = article
      else
        link = "Spécial:Wikidata/" + id

      let text = "<p><b><a href=\"" + link + "\">" + title + "</a></b></p><hr />"

      text += '<table><tbody>'

      if (artist != "")
        text += "<tr><th>Auteur : </th><td>"+artist+"</td></tr>"
      if (nature != "" && nature != "wikidata")
        text += "<tr><th>Nature : </th><td>"+nature+"</td></tr>"

      text += '</tbody></table>'

      content.innerHTML = text
      overlay.setPosition(coordinate)
    }
  });
}

changeMarkers = function() {
  clusterSource.getSource().clear()
  features = Array()
  for (let i = 0; i < artworks_map.length; i++) {
    if ((artworks_map[i].nature == "pérenne" && $("#checkbox-perenne").is(":checked")) ||
        (artworks_map[i].nature == "éphémère" && $("#checkbox-ephemere").is(":checked")) ||
        (artworks_map[i].nature == "détruite" && $("#checkbox-detruite").is(":checked")) ||
        (artworks_map[i].nature == "non réalisée" && $("#checkbox-non-realisee").is(":checked")) ||
        (artworks_map[i].nature == "à vérifier" && $("#checkbox-verifier").is(":checked")) ||
        (artworks_map[i].nature == "wikidata" && $("#checkbox-wikidata").is(":checked")))
      features.push(new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.transform([artworks_map[i].lon, artworks_map[i].lat], "EPSG:4326", "EPSG:3857")),
        title: artworks_map[i].title,
        artist: artworks_map[i].artist,
        id: artworks_map[i].wikidata,
        nature: artworks_map[i].nature,
        article: artworks_map[i].article,
      }))
  }

  clusterSource.getSource().addFeatures(features)
}
