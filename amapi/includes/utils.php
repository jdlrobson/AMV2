<?php
/*****************************************************************************
 * utils.php
 *
 * Fonctions utiles
 *****************************************************************************/

/**
 * Retourne la valeur d'un paramètre passé par GET ou POST
 *
 * @param {string} $key - Paramètre
 * @return {string} Value du paramètre
 */
function getRequestParameter($key) {
  if (array_key_exists($key, $_REQUEST))
    return $_REQUEST[$key];
  else
    return null;
}

?>