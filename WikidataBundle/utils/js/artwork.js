document.addEventListener("DOMContentLoaded", function(event) { 
  var li = document.getElementById('ca-edit'),
      ul = li.parentElement,
      newLi = document.createElement("li"),
      page = window.location.href.replace(/^.*index\.php\?title=/, '');

  newLi.setAttribute('id', 'ca-form_edit')
  newLi.classList.add('collapsible')
  newLi.classList.add('selected')

  newLi.innerHTML = '<span><a href="index.php?title=Spécial:WikidataEdit/'+page+'">Modifier avec formulaire</a></span></li>';

  ul.insertBefore(newLi, li)
});

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
