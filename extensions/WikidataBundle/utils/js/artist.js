document.addEventListener("DOMContentLoaded", function(event) { 
  var li = document.getElementById('ca-edit')
  if (li) {
    var ul = li.parentElement,
        newLi = document.createElement("li"),
        page = window.location.href.replace(/^.*index\.php\?title=/, '').replace(/^.*wiki\//, '');

    newLi.setAttribute('id', 'ca-form_edit')
    newLi.classList.add('collapsible')
    newLi.classList.add('selected')

    newLi.innerHTML = '<span><a href="http://publicartmuseum.net/wiki/Spécial:WikidataEditArtist/'+page+'">Modifier avec formulaire</a></span></li>';

    ul.insertBefore(newLi, li)
  }
});