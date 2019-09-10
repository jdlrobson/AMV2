show_tab = function(tab, section) {
console.log(section)
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

add_line = function(id, property, key, mandatory) {
  var td = document.getElementById(id),
      divs = document.querySelectorAll('#' + id + ' .inputSpan'),
      newLine = document.createElement("div"),
      add = document.querySelectorAll('#' + id + '> .add_button')[0]

  if (divs.length>0) {
    console.log(divs[divs.length-1].id)
    n = parseInt(divs[divs.length-1].id.replace(id+'_wrapper_', ''))+1
  } else
    n = 0;

  newLine.classList.add('inputSpan')
  newLine.classList.add('autocomplete')
  if (mandatory)
    newLine.classList.add('mandatoryFieldSpan')

  newLine.setAttribute('id', id+'_wrapper_'+n)

  newLine.innerHTML = '<input id="'+id+'_'+n+'" class="createboxInput'+(mandatory ? ' mandatoryField' : '')+'" size="60" value="" name="Edit['+key+']['+n+']"><input type="hidden" id="'+id+'_id_'+n+'" name="Edit['+key+'][id]['+n+']" value=""></input> <span class="edit_item_button" title="Supprimer cette ligne" onclick="remove_line(\''+id+'_wrapper_'+n+'\')">[&nbsp;x&nbsp;]</span>';
  
  td.insertBefore(newLine, add)
  autocomplete(document.getElementById(id+'_'+n))
}

remove_line = function(id) {
  document.getElementById(id).remove()
}

publish = function() {
  var form_data = $('#edit_form').serializeArray(),
      data = {},
      equivalents = {
        'id': 'q',
        'label': 'titre',
        'nature': 'nature',
        'P170': 'artiste',
        'P625': 'Site_coordonnees'
      }

  console.log(form_data);

  for (var i=0; i<form_data.length; i++) {
    params = form_data[i].name.replace(/Edit\[(.*)\]$/, '$1').split('][');
    let eq = equivalents[params[0]]

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
      for (var i=0; i<data[key].length; i++) {
        if (data[key][i].id != '')
          r.push(data[key][i].id)
        else
          r.push(data[key][i].label.replace(/\"/g, '\\\"').replace(/\n/g, '\\\n'))
      }
      text += key + '="' + r.join(';') + '"\n';
    } else
      text += key + '="' + data[key].replace(/\"/g, '\\\"').replace(/\n/g, '\\\n') + '"\n';
  }

  text += '/>'

  console.log(text)

  //console.log($('#real_edit_form').serializeArray());

  document.getElementById('wpTextbox1').value = text;
  document.getElementById('editform').action = '/tmp/w/index.php?title=Test&action=submit';
  //document.getElementById("editform").submit();
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