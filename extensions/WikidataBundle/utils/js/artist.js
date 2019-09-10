document.addEventListener("DOMContentLoaded", function(event) { 
  var li = document.getElementById('ca-edit'),
      ul = li.parentElement,
      newLi = document.createElement("li"),
      page = window.location.href.replace(/^.*index\.php\?title=/, '');

  newLi.setAttribute('id', 'ca-form_edit')
  newLi.classList.add('collapsible')
  newLi.classList.add('selected')

  newLi.innerHTML = '<span><a href="index.php?title=Spécial:WikidataEditArtist/'+page+'">Modifier avec formulaire</a></span></li>';
console.log('hop');
  ul.insertBefore(newLi, li)
});