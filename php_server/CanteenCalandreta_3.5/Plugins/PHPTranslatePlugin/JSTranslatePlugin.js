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
 * JS plugin translate module : display translation of a pointed text in other langage
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2015-06-17 : add the "notprintable" style to the translation <p>
 *
 * @since 2014-10-31
 */

 var TranslatePluginPath;
 var TranslatePluginAjax;
 var TranslatePluginObjWebPage;
 var TranslatePluginMousePosX = 0;
 var TranslatePluginMousePosY = 0;
 var bTitleAttributeDetected = false;

 // Parameters
 var TranslatePluginToolboxOffset = 35;  // Offset, in px, because of the displayed "title" attribute
 var TranslatePluginHideTimeOut = 3;     // Nb of secondes after the toolbox is hidden


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-10-31
 */
 function initTranslatePlugin(Path, Offset, HideTimeout)
 {
     // Taken into account parameters of the plugin
     TranslatePluginPath = Path;
     TranslatePluginToolboxOffset = parseInt(Offset);
     TranslatePluginHideTimeOut = parseInt(HideTimeout);

     TranslatePluginObjWebPage = document.getElementById('webpage');
     if (!TranslatePluginObjWebPage) {
         // id = webpage doesn't exist : we use the body
         TranslatePluginObjWebPage = document.body || document.documentElement;
     }

     if (TranslatePluginObjWebPage) {
         if (window.attachEvent) {
             TranslatePluginObjWebPage.attachEvent("onmouseover", TranslatePluginTranslateElementUnderMouse);           // IE
         } else {
             TranslatePluginObjWebPage.addEventListener("mouseover", TranslatePluginTranslateElementUnderMouse, false); // FF
         }

         if(window.XMLHttpRequest) // Firefox
             TranslatePluginAjax = new XMLHttpRequest();
         else if(window.ActiveXObject) // Internet Explorer
             TranslatePluginAjax = new ActiveXObject("Microsoft.XMLHTTP");
         else { // XMLHttpRequest non supporté par le navigateur
             alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
         }
     }
 }


 function TranslatePluginTranslateElementUnderMouse(evt)
 {
     // Get the scroll position (for mouse)
     var scrollX = document.body.scrollLeft || document.documentElement.scrollLeft;
     var scrollY = document.body.scrollTop || document.documentElement.scrollTop;

     // Get text of the HTML tag under the mouse
     if (evt.target) {
         var objDetected = evt.target;

         TranslatePluginMousePosX = evt.clientX;
         TranslatePluginMousePosY = evt.clientY;
     } else {
         var objDetected = event.srcElement;

         TranslatePluginMousePosX = event.clientX;
         TranslatePluginMousePosY = event.clientY;
     }

     TranslatePluginMousePosX += scrollX;
     TranslatePluginMousePosY += scrollY;

     if (!objDetected.tagName) {
         objDetected = objDetected.parentNode;
     }

     // The message to translate can be different for an input tag
     var sMsgToTranslate = '';
     if (objDetected.nodeName == 'INPUT') {
         switch(objDetected.type) {
             case 'text':
             case 'checkbox':
                 sMsgToTranslate = objDetected.title;
                 break;

             default:
                 sMsgToTranslate = objDetected.value;
                 break;
         }
     } else if (objDetected.nodeName == 'A') {
         if (objDetected.title != '') {
             sMsgToTranslate = objDetected.title;
         } else {
             sMsgToTranslate = objDetected.textContent;
         }
     } else if ((objDetected.nodeName == 'TEXTAREA') || (objDetected.nodeName == 'IMG')) {
         sMsgToTranslate = objDetected.title;
     } else {
         sMsgToTranslate = objDetected.textContent;
     }

     // Check if there is a not empty title attribute
     if ((objDetected.title) && (objDetected.title != '')) {
         bTitleAttributeDetected = true;
     } else {
         bTitleAttributeDetected = false;
     }

     // Get translation of the text
     if (sMsgToTranslate != '') {
         TranslatePluginAjax.onreadystatechange = TranslatePluginGetTranslationHandlerHTML;
         TranslatePluginAjax.open("GET", TranslatePluginPath + "PHPTranslatePlugin.php?MsgToTranslate=" + sMsgToTranslate, true);
         TranslatePluginAjax.send(null);
     }
 }


 function TranslatePluginGetTranslationHandlerHTML()
 {
     if ((TranslatePluginAjax.readyState == 4) && (TranslatePluginAjax.status == 200)) {
         var objTranslation = document.getElementById('PHPTranslatePluginInfoBox');

         if (TranslatePluginAjax.responseText == '503') {
             // Hide the toolbox;
             if (objTranslation) {
                 objTranslation.style.display = 'none';
             }
         } else {
             var sTranslation = TranslatePluginAjax.responseText;

             // Display the translation in a toolbox
             if (!objTranslation) {
                 // Create the toolbox
                 objTranslation = document.createElement('div');
                 objTranslation.setAttribute('id', 'PHPTranslatePluginInfoBox');
                 TranslatePluginObjWebPage.appendChild(objTranslation);
             }

             objTranslation.style.display = 'table';
             objTranslation.style.position = 'absolute';

             if (bTitleAttributeDetected) {
                 // Add an offset because of the displayed "title" attribute
                 objTranslation.style.top = TranslatePluginMousePosY + TranslatePluginToolboxOffset + 'px';
             } else {
                 objTranslation.style.top = TranslatePluginMousePosY + 'px';
             }

             objTranslation.style.left = TranslatePluginMousePosX + 'px';
             objTranslation.innerHTML = "<p class='notprintable'>" + sTranslation + "</p>";

             // Hide the toolbox after x seconds
             setTimeout(function() {
                 var objTranslation = document.getElementById('PHPTranslatePluginInfoBox');
                 if (objTranslation) {
                     objTranslation.style.display = 'none';
                 }
             }, TranslatePluginHideTimeOut * 1000);
         }
     }
 }



