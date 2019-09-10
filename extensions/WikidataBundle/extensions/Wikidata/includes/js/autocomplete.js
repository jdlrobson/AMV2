function autocomplete(inp) {

  var currentFocus;

  inp.addEventListener("input", function(e) {
    var a, b, i, val = this.value;

    closeAllLists();

    if (!val) { return false;}
    
    currentFocus = -1;

    a = document.createElement("div");
    a.setAttribute("id", this.id + "autocomplete-list");
    a.setAttribute("class", "autocomplete-items");
    this.parentNode.appendChild(a);

    var params = {
      'action' : 'wbsearchentities',
      'search' : val,
      'format' : 'json',
      'language' : 'fr',
      'type' : 'item'
    };

    $.getJSON('https://www.wikidata.org/w/api.php'+'?callback=?&' + $.param(params),function(json){
      for (i = 0; i < json.search.length; i++) {
        b = document.createElement("div");
        b.setAttribute('data-id', json.search[i].id);
        b.setAttribute('data-label', json.search[i].label);
        b.setAttribute('data-description', json.search[i].description);
        b.innerHTML ='<span>'+json.search[i].label+'<span><br /><span><small>'+json.search[i].description+'</small></span>';
        b.addEventListener("click", function(e) {
          var targetElement = e.target || e.srcElement;
          targetElement = targetElement.closest('div')
          inp.value = targetElement.dataset.label;
          var hidden_id = inp.id.replace(/^(.*)_/, '$1_id_');
          document.getElementById(hidden_id).value=targetElement.dataset.id;

          closeAllLists();
        }, false);
        a.appendChild(b);
      }
    });
  });

  inp.addEventListener("keydown", function(e) {
      var x = document.getElementById(this.id + "autocomplete-list");
      if (x) x = x.getElementsByTagName("div");
      if (e.keyCode == 40) { //-- down
        currentFocus++;
        addActive(x);
      } else if (e.keyCode == 38) { //-- up
        currentFocus--;
        addActive(x);
      } else if (e.keyCode == 13) { //-- enter
        e.preventDefault();
        if (currentFocus > -1)
          if (x) x[currentFocus].click();
      }
  });

  function addActive(x) {
    if (!x) return false;
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (x.length - 1);
    x[currentFocus].classList.add("autocomplete-active");
  }

  function removeActive(x) {
    for (var i = 0; i < x.length; i++)
      x[i].classList.remove("autocomplete-active");
  }

  function closeAllLists(elmnt) {
    var x = document.getElementsByClassName("autocomplete-items");
    for (var i = 0; i < x.length; i++)
      if (elmnt != x[i] && elmnt != inp)
        x[i].parentNode.removeChild(x[i])
  }

  document.addEventListener("click", function (e) {
    closeAllLists(e.target);
  })

}

document.addEventListener("DOMContentLoaded", function(event) { 
  var list = document.getElementsByClassName('autocomplete');
  for (var i=0; i<list.length; i++)
    autocomplete(list[i].getElementsByTagName('input')[0]);
});
