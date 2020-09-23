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
 * JS plugin main menu module : replace main menu items by icons
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2012-01-12
 */

 var MainMenuPluginPath;
 var MainMenuPluginCurrentPageIndex;
 var MainMenuPluginIsAdmin = false;


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2013-04-03 : taken into account the "Cooperation" module
 *     - 2016-10-21 : taken into account the "Admin" module
 *
 * @since 2012-01-12
 */
 function initMainMenuPlugin(Path)
 {
        // We don't activate this plugin for IE
        /*if (window.attachEvent) {
            for(var i = 0; i < document.styleSheets.length; i++) {
                if (document.styleSheets[i].href.indexOf('JSMainMenuPluginStyles.css') != -1) {
                    // We remove the stylesheets of the plugin
                    document.styleSheets[i].href = '';
                }
            }

            return 0;
        } */

        // We get the resolution of the screen to use the right styles
        var iScreenWidth = screen.width;
        var sSuffixStyle = '';
        if (iScreenWidth <= 1024) {
            sSuffixStyle = 'W1024';
        }

        MainMenuPluginPath = Path;
        MainMenuPluginCurrentPageIndex = -1;   // AowFollow by default (because of index.php and other pages in /Support/)

        // Get in which main menu item the user is in relation with the current url
        var ArrayMainMenuUrl = new Array('Canteen', 'Cooperation');
        var sCurrentPageUrl = new String(document.location);
        var reg = new RegExp("index.php", "g");
        if ((sCurrentPageUrl.match(reg)) && (document.getElementById('contextualmenu'))) {
            MainMenuPluginCurrentPageIndex = 0;
        }

        // Title of the application
        if (document.getElementById('header')) {
            var AppTitle = document.getElementById('header').getElementsByTagName('h1')[0];
            AppTitle.className = 'MaimMenuPlugin' + sSuffixStyle;
        }

        // Main menu bar
        if (document.getElementById('mainmenu')) {
            var MainMenuBar = document.getElementById('mainmenu');
            MainMenuBar.className = 'MaimMenuPlugin' + sSuffixStyle;

            var ArrayMainMenuItems = MainMenuBar.getElementsByTagName('li');
            if (ArrayMainMenuItems.length == ArrayMainMenuUrl.length + 1) {
                // The loggued supporter is an admin
                MainMenuPluginIsAdmin = true;
            }

            var ArrayMainMenuIcons = new Array('canteen.png', 'cooperation.png');
            var ArrayMainMenuSelectedIcons = new Array('canteen_selected.png', 'cooperation_selected.png');

            if (MainMenuPluginIsAdmin) {
                ArrayMainMenuUrl.push('Admin');
                ArrayMainMenuIcons.push('admin.png');
                ArrayMainMenuSelectedIcons.push('admin_selected.png');
            }

            for(i = 0; i < ArrayMainMenuUrl.length; i++) {
                reg = new RegExp(ArrayMainMenuUrl[i], "g");
                if (sCurrentPageUrl.match(reg)) {
                    MainMenuPluginCurrentPageIndex = i;
                }
            }

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
                        objImg.attachEvent("onmouseover", MainMenuPluginMouseOver);                        // IE
                        objImg.attachEvent("onmouseout", MainMenuPluginMouseOut);
                } else {
                        objImg.addEventListener("mouseover", MainMenuPluginMouseOver, false);                // FF
                        objImg.addEventListener("mouseout", MainMenuPluginMouseOut, false);
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
                LogoutImg.attachEvent("onmouseover", MainMenuPluginMouseOver);                        // IE
                LogoutImg.attachEvent("onmouseout", MainMenuPluginMouseOut);
            } else {
                LogoutImg.addEventListener("mouseover", MainMenuPluginMouseOver, false);                // FF
                LogoutImg.addEventListener("mouseout", MainMenuPluginMouseOut, false);
            }

            // Add the logout link + img in the main menu
            var LogoutItem = document.createElement('li');
            LogoutItem.appendChild(objLink);
            LogoutItem.className = 'quitapp';
            MainMenuBar.appendChild(LogoutItem);

            // Select the item in the main menu in relation with the current url of the displayed page
            if (MainMenuPluginCurrentPageIndex >= 0) {
                objImg = MainMenuBar.getElementsByTagName('img')[MainMenuPluginCurrentPageIndex];
                objImg.setAttribute('src', MainMenuPluginPath + ArrayMainMenuSelectedIcons[MainMenuPluginCurrentPageIndex]);
            }
        }

        // Add the icon of the main menu in the background of the displayed page
        if (MainMenuPluginCurrentPageIndex >= 0) {
            var PageDiv = document.getElementById('content');
            if (document.getElementById('page')) {
                PageDiv = document.getElementById('page');
            }

            PageDiv.className = ArrayMainMenuUrl[MainMenuPluginCurrentPageIndex];
        }

        // Modify the contextual menu
        if (document.getElementById('contextualmenu')) {
            var ContextualMenuDiv = document.getElementById('contextualmenu');
            var ArrayHeaderContextualMenu = ContextualMenuDiv.getElementsByTagName('h3');
            var ArrayListContextualMenu = ContextualMenuDiv.getElementsByTagName('ul');
            var ArrayInfosContextualMenu = ContextualMenuDiv.getElementsByTagName('p');
            var ArrayDecorationDiv = new Array();
            for(var i = 0; i < ArrayHeaderContextualMenu.length; i++) {
                ArrayDecorationDiv[i] = document.createElement('div');
                ArrayDecorationDiv[i].className = 'decoInt';
                ArrayDecorationDiv[i].appendChild(ArrayListContextualMenu[0]);
            }

            for(var i = 0; i < ArrayHeaderContextualMenu.length; i++) {
                var tmpDiv = document.createElement('div');
                tmpDiv.className = 'decoExt';
                tmpDiv.appendChild(ArrayHeaderContextualMenu[0]);
                tmpDiv.appendChild(ArrayDecorationDiv[i]);
                ContextualMenuDiv.appendChild(tmpDiv);
            }

            for(var i = 0; i < ArrayInfosContextualMenu.length; i++) {
                ContextualMenuDiv.appendChild(ArrayInfosContextualMenu[0]);
            }
        }
 }


 function MainMenuPluginMouseOver(evt)
 {
        if (MainMenuPluginIsAdmin) {
            var ArrayMainMenuSelectedIcons = new Array('canteen_selected.png', 'cooperation_selected.png', 'admin_selected.png', 'logout_selected.png');
        }  else {
            var ArrayMainMenuSelectedIcons = new Array('canteen_selected.png', 'cooperation_selected.png', 'logout_selected.png');
        }

        var objImg = evt.target || evt.srcElement;

        objImg.setAttribute('src', MainMenuPluginPath + ArrayMainMenuSelectedIcons[objImg.index]);

        // Display the caption under the icon
        if (objImg.index < ArrayMainMenuSelectedIcons.length - 1) {
            var objLink = objImg.parentNode;
            var objText = document.createElement('p');
            objText.innerHTML = objImg.text;
            objLink.appendChild(objText);
        }
 }


 function MainMenuPluginMouseOut(evt)
 {
        if (MainMenuPluginIsAdmin) {
            var ArrayMainMenuIcons = new Array('canteen.png', 'cooperation.png', 'admin.png', 'logout.png');
        } else {
            var ArrayMainMenuIcons = new Array('canteen.png', 'cooperation.png', 'logout.png');
        }

        var objImg = evt.target || evt.srcElement;

        if (objImg.index != MainMenuPluginCurrentPageIndex) {
            objImg.setAttribute('src', MainMenuPluginPath + ArrayMainMenuIcons[objImg.index]);
        }

        // Remove the caption under the icon
        if (objImg.index < ArrayMainMenuIcons.length - 1) {
            var objLink = document.getElementById('mainmenu').getElementsByTagName('a')[objImg.index];
            var objText = objLink.getElementsByTagName('p')[0];
            objLink.removeChild(objText);
        }
 }

