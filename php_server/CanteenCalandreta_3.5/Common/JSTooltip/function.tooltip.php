<?php
/**
 * Smarty {tooltip} function plugin
 * show a tooltip over an HTML element when mouse is over it
 * Part of tooltip package, written by Laurent Jouanneau
 * http://ljouanneau.com/softs/javascript/
 * use :
 *  <htmlelement {tooltip} title="text of tooltip" >...</htmlelement>
 *  <htmlelement {tooltip text="text of tooltip"}>...</htmlelement>
 *  <htmlelement {tooltip text=$the_text }>...</htmlelement>
 *
 *
 */
function smarty_function_tooltip($params, &$smarty)
{
    extract($params);
    $retval =' onmouseover="return tooltip.show(this);" onmouseout="tooltip.hide(this);" ';

    if (!empty($text)) {
      $retval .= ' title="'.htmlspecialchars($text).'"';
    }

	return $retval;
}

?>
