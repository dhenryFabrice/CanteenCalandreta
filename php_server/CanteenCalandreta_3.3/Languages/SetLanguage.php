<?php
/* Copyright (C) 2012 Calandreta Del Païs Murethin
 *
 * This file is part of CanteenCalandreta.
 *
 * CanteenCalandreta is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CanteenCalandreta is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CanteenCalandreta; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Language : include the good language file ; it depends on the value of the $LANG constante
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2013-01-02 : taken into account of Occitan language
 *
 * @since 2012-01-12
 */

 switch($CONF_LANG)
 {
     case 'fr': include_once('Francais.lang.php');
                break;
                
     case 'oc': include_once('Occitan.lang.php');
                break;

     default:   include_once('English.lang.php');
                break;
 }
?>
