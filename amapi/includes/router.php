<?php
/*****************************************************************************
 * route.php
 *
 * Détermination de la route
 *****************************************************************************/

require_once ('./includes/utils.php');
require_once ('./includes/config.php');
require_once ('./includes/response.php');

if (!class_exists('Router')) {

  class Router {

    /**
     * Retourne la réponse en fonction de la route
     *
     * @return {Response} la réponse
     */
    public static function getResponse() {
      $response = new Response();

      /**
       * Détermine l'action à mener
       */
      $action = getRequestParameter('action');

      if (is_null($action)) {
        // Pas de code d'action -> erreur
        $response->setError('no_action', 'No value for parameter "action".', 204);
      } else {
        // Détermination de la réponse en fonction du code d'action
        switch (strtolower($action)) {
          case 'amgetmap':
            // Carte
            require_once ('generators/map.php');
            $validation = Map::validateQuery();
            if ($validation['success']) {
              $data = [];
              $origin = strtolower(getRequestParameter('origin'));
              if (is_null($origin)) 
                $data = Map::getMap();
              else {
                if ($origin == 'atlasmuseum')
                  $data = Map::getMapAM();
                else
                if ($origin == 'wikidata')
                  $data = Map::getMapWD();
              }
              $response->setValue('entities', $data);
              $response->setSuccess(true);
              $response->setStatusCode(200);
            } else {
              $response->setError($validation['error']['code'], $validation['error']['info'], $validation['error']['status']);
            }
            break;

          default:
            // Action inconnue -> erreur
            $response->setError('unknown_action', 'Unrecognized value for parameter "action": ' . $action . '.', 400);
        }
      }

      return $response;
    }
  }

}

?>
