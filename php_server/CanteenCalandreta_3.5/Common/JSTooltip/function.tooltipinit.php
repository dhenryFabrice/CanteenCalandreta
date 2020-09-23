<?php
/**
 * Smarty {tooltipinit} function plugin
 * init for tooltip function plugin
 * Part of tooltip package, written by Laurent Jouanneau
 * http://ljouanneau.com/softs/javascript/
 * use :
 *  {tooltipinit}
 *  {tooltipinit path="path_to_tooltip.js"}
 *
 */
function smarty_function_tooltipinit($params, &$smarty){
   extract($params);
   if(!isset($path)) $path='';

   return '<script type="text/javascript" src="'.$path.'tooltip.js"></script><div id="tooltip"></div>';
}

?>
