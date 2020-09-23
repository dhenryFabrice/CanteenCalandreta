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
 * JS plugin Calandreta GUI module : set the CSS for Calandreta
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-12-20
 */

 var CalandretaGUIPluginPath;



/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-12-20
 */
 function initCalandretaGUIPlugin(Path)
 {
     /*for(var i = 0; i < document.styleSheets.length; i++) {
         if (document.styleSheets[i].href.indexOf('xxx.css') != -1) {
             // We remove the stylesheets of the plugin
             document.styleSheets[i].href = '';
         }
     }*/

     CalandretaGUIPluginPath = Path;
 }

