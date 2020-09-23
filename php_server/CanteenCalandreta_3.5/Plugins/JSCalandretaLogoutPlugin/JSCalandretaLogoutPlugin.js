/* Copyright (C) 2007  STNA/7SQ (IVDS)
 *
 * This file is part of ASTRES.
 *
 * ASTRES is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ASTRES is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ASTRES; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * JS plugin logout module : replace main menu items by icons with colors of the Mureth Calandreta
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-12-31
 */

 var LogoutPluginPath;


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-12-31
 */
 function initCalandretaLogoutPlugin(Path)
 {
        // We don't activate this plugin for IE
        //if (window.attachEvent) return 0;

        LogoutPluginPath = Path;

        // Title of the application
        if (document.getElementById('header')) {
            var AppTitle = document.getElementById('header').getElementsByTagName('h1')[0];
            AppTitle.className = 'LogoutPlugin';
        }

        // Main menu bar
        if (document.getElementById('mainmenu')) {
            var MainMenuBar = document.getElementById('mainmenu');
            MainMenuBar.className = 'LogoutPlugin';

            var ArrayMainMenuItems = MainMenuBar.getElementsByTagName('li');

            var ArrayMainMenuIcons = new Array();
            var ArrayMainMenuSelectedIcons = new Array('logout_selected.png');
            var objImg;
            var objLink;

            for(i = 0; i < ArrayMainMenuItems.length; i++) {
                objLink = ArrayMainMenuItems[i].getElementsByTagName('a')[0];

                objImg = document.createElement('img');
                objImg.setAttribute('src', Path + ArrayMainMenuIcons[i]);
                objImg.setAttribute('title', objLink.getAttribute('title'));
                objImg.setAttribute('alt', objLink.getAttribute('title'));
                objImg.index = i;
                objImg.text = objLink.innerHTML;

                if (window.attachEvent) {
                        objImg.attachEvent("onmouseover", LogoutPluginMouseOver);                        // IE
                        objImg.attachEvent("onmouseout", LogoutPluginMouseOut);
                } else {
                        objImg.addEventListener("mouseover", LogoutPluginMouseOver, false);                // FF
                        objImg.addEventListener("mouseout", LogoutPluginMouseOut, false);
                }

                objLink.innerHTML = '';
                objLink.appendChild(objImg);
            }

            // Logout button
            var LogoutDiv = document.getElementById('quitapp');
            LogoutDiv.style.display = 'none';
            objLink = LogoutDiv.getElementsByTagName('a')[0];

            var LogoutImg = objLink.getElementsByTagName('img')[0];
            LogoutImg.setAttribute('src', Path + 'logout.png');
            LogoutImg.index = ArrayMainMenuItems.length;
            if(window.attachEvent) {
                LogoutImg.attachEvent("onmouseover", LogoutPluginMouseOver);                        // IE
                LogoutImg.attachEvent("onmouseout", LogoutPluginMouseOut);
            } else {
                LogoutImg.addEventListener("mouseover", LogoutPluginMouseOver, false);                // FF
                LogoutImg.addEventListener("mouseout", LogoutPluginMouseOut, false);
            }

            // Add the logout link + img in the main menu
            var LogoutItem = document.createElement('li');
            LogoutItem.appendChild(objLink);
            LogoutItem.className = 'quitapp';
            MainMenuBar.appendChild(LogoutItem);
        }
 }


 function LogoutPluginMouseOver(evt)
 {
        var ArrayMainMenuSelectedIcons = new Array('logout_selected.png');
        var objImg = evt.target || evt.srcElement;

        objImg.setAttribute('src', LogoutPluginPath + ArrayMainMenuSelectedIcons[objImg.index]);

        // Display the caption under the icon
        if (objImg.index < ArrayMainMenuSelectedIcons.length - 1) {
            var objLink = objImg.parentNode;
            var objText = document.createElement('p');
            objText.innerHTML = objImg.text;
            objLink.appendChild(objText);
        }
 }


 function LogoutPluginMouseOut(evt)
 {
        var ArrayMainMenuIcons = new Array('logout.png');
        var objImg = evt.target || evt.srcElement;

        if (objImg.index != LogoutPluginCurrentPageIndex) {
            objImg.setAttribute('src', LogoutPluginPath + ArrayMainMenuIcons[objImg.index]);
        }

        // Remove the caption under the icon
        if (objImg.index < ArrayMainMenuIcons.length - 1) {
            var objLink = document.getElementById('mainmenu').getElementsByTagName('a')[objImg.index];
            var objText = objLink.getElementsByTagName('p')[0];
            objLink.removeChild(objText);
        }
 }
