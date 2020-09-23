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
 * JS check approval documents plugin module : display documents not approved by the logged user
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-06-19
 */


 var CheckApprovalDocumentsPluginPath;
 var CheckApprovalDocumentsPluginLanguage;
 var CheckApprovalDocumentsPluginAjax;


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-06-19
 */
 function initCheckApprovalDocumentsPlugin(Path, Lang)
 {
     CheckApprovalDocumentsPluginPath = Path;
     CheckApprovalDocumentsPluginLanguage = Lang;

     // We check if we must display the info area about not approval documents for the logged user
     if(window.XMLHttpRequest) // Firefox
         CheckApprovalDocumentsPluginAjax = new XMLHttpRequest();
     else if(window.ActiveXObject) // Internet Explorer
         CheckApprovalDocumentsPluginAjax = new ActiveXObject("Microsoft.XMLHTTP");
     else { // XMLHttpRequest non supporté par le navigateur
         alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
     }

     CheckApprovalDocumentsPluginAjax.onreadystatechange = CheckApprovalDocumentsPluginGetSetupXML;
     CheckApprovalDocumentsPluginAjax.open("GET", Path + "PHPCheckApprovalDocumentsPlugin.php?getDocuments=1", true);
     CheckApprovalDocumentsPluginAjax.send(null);
 }


// Get the display mode
 function CheckApprovalDocumentsPluginGetSetupXML()
 {
     if ((CheckApprovalDocumentsPluginAjax.readyState == 4) && (CheckApprovalDocumentsPluginAjax.status == 200)) {
         var DocXML = CheckApprovalDocumentsPluginAjax.responseXML.documentElement;
         var items = DocXML.childNodes;

         if (items.length > 2) {
             var ArrayDocumentNames = new Array();
             var ArrayUrls = new Array();
             var iNbItems = 0;

             for(var i = 0; i < items.length; i++) {
                 if (items[i].nodeName == 'approval-document') {
                     ArrayDocumentNames[iNbItems] = items[i].getAttribute('name');
                     ArrayUrls[iNbItems] = items[i].getAttribute('url');

                     iNbItems++;
                 }
             }

             if (iNbItems > 0) {
                 // Create the info area with not approved documents for the logged user
                 var objApprovalDocsArea = document.createElement('div');
                 objApprovalDocsArea.setAttribute('id', 'ApprovalDocumentsArea');

                 var sHTML = '<dl>';
                 var sTitle = '';
                 switch(CheckApprovalDocumentsPluginLanguage) {
                     case 'fr':
                         sTitle = "Documents à approuver :";
                         break;

                     case 'oc':
                         sTitle = "Documents à approuver :";
                         break;

                     default:
                         sTitle = "Documents to approve :";
                         break;
                 }

                 sHTML += "<dt>" + sTitle + "</dt>";

                 for(var i = 0; i < iNbItems; i++) {
                     sHTML += "<dd><a href='" + ArrayUrls[i] + "' target='_blank'>" + ArrayDocumentNames[i] + "</a></dd>";
                 }

                 sHTML += '</dl>';

                 objApprovalDocsArea.innerHTML = sHTML;

                 var objContextualMenu = document.getElementById('contextualmenu');
                 if (objContextualMenu) {
                     // Display the info area near contextual menu
                     objContextualMenu.parentNode.insertBefore(objApprovalDocsArea, objContextualMenu);
                 } else {
                     // The page has no contextual menu
                     var objContentPage = document.getElementById('content');
                     objContentPage.insertBefore(objApprovalDocsArea, objContentPage.firstChild);
                 }
             }
         }
     }
 }



