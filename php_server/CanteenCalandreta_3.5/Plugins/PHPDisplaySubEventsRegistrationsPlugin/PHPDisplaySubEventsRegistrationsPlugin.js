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
 * JS plugin displaying registrations of sub-events module : for some groups of users, families registered on
 * sub-events are dispayed on the parent event
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2014-01-02 : taken into account english language
 *     - 2014-04-17 : taken into account occitan language
 *
 * @since 2013-10-14
 */


 var DisplaySubEventsRegistrationsPluginAjax;
 var DisplaySubEventsRegistrationsPath;
 var DisplaySubEventsRegistrationsLanguage;


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2014-01-02 : taken into account english language
 *
 * @since 2013-10-14
 *
 * @param Language         String    Language of the messages to display
 */
 function initDisplaySubEventsRegistrationsPlugin(Language)
 {
     DisplaySubEventsRegistrationsLanguage = Language;

     $A(document.getElementsByTagName("script")).findAll( function(s) {
         return (s.src && s.src.match(/PHPDisplaySubEventsRegistrationsPlugin\.js(\?.*)?$/))
     }).each( function(s) {
         DisplaySubEventsRegistrationsPath = s.src.replace(/PHPDisplaySubEventsRegistrationsPlugin\.js(\?.*)?$/,'');
     });

     // We check if the user has read/write access to the current event
     var objParentID = document.getElementById('lParentEventID');
     if (objParentID) {
         // We get the correlated asks of work
         if(window.XMLHttpRequest) // Firefox
             DisplaySubEventsRegistrationsPluginAjax = new XMLHttpRequest();
         else if(window.ActiveXObject) // Internet Explorer
             DisplaySubEventsRegistrationsPluginAjax = new ActiveXObject("Microsoft.XMLHTTP");
         else { // XMLHttpRequest non supporté par le navigateur
             alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
         }

         var DisplaySubEventsRegistrationsPluginMD5Url = document.location.href;
         DisplaySubEventsRegistrationsPluginMD5Url = DisplaySubEventsRegistrationsPluginMD5Url.substr(DisplaySubEventsRegistrationsPluginMD5Url.indexOf('?', 0));

         // Send the Ajax request
         DisplaySubEventsRegistrationsPluginAjax.onreadystatechange = DisplaySubEventsRegistrationsPluginHandlerXML;
         DisplaySubEventsRegistrationsPluginAjax.open("GET", DisplaySubEventsRegistrationsPath + "PHPDisplaySubEventsRegistrationsPlugin.php" + DisplaySubEventsRegistrationsPluginMD5Url, true);
         DisplaySubEventsRegistrationsPluginAjax.send(null);
     }
 }


 function DisplaySubEventsRegistrationsPluginHandlerXML()
 {
     if ((DisplaySubEventsRegistrationsPluginAjax.readyState == 4) && (DisplaySubEventsRegistrationsPluginAjax.status == 200)) {
         var DocXML = DisplaySubEventsRegistrationsPluginAjax.responseXML.documentElement;
         var items = DocXML.childNodes;
         var iNbItems = 0;

         if (items.length > 2) {
             var objRegisteredFamilies = new Object;
             objRegisteredFamilies.EventID = new Array();
             objRegisteredFamilies.EventTitle = new Array();
             objRegisteredFamilies.FamilyID = new Array();
             objRegisteredFamilies.FamilyUrl = new Array();
             objRegisteredFamilies.FamilyLastname = new Array();
             objRegisteredFamilies.EventRegistrationComment = new Array();

             for(var i = 0; i < items.length; i++) {
                 if (items[i].nodeName == 'registeredfamily') {
                     objRegisteredFamilies.EventID[iNbItems] = items[i].getAttribute('eventid');
                     objRegisteredFamilies.EventTitle[iNbItems] = items[i].getAttribute('eventtitle');
                     objRegisteredFamilies.FamilyID[iNbItems] = items[i].getAttribute('familyid');
                     objRegisteredFamilies.FamilyUrl[iNbItems] = items[i].getAttribute('familyurl');
                     objRegisteredFamilies.FamilyLastname[iNbItems] = items[i].getAttribute('familylastname');
                     objRegisteredFamilies.EventRegistrationComment[iNbItems] = items[i].getAttribute('eventregistrationcomment');
                     iNbItems++;
                 }
             }

             if (iNbItems > 0) {
                 // We create an area to display the registered families
                 var objAreaRegisteredFamilies = document.createElement('dl');
                 objAreaRegisteredFamilies.setAttribute('id', 'RegisteredFamiliesSubEventsList');

                 var sListLabel = '';
                 var sListTip = '';
                 var sFamilyUrlTip = '';
                 switch(DisplaySubEventsRegistrationsLanguage) {
                     case 'oc':
                         sListLabel = "Familhas marcadas sus aqueste eveniment e los jos-eveniments...";
                         sListTip = "Clicatz per afichar/amagar la tièra de las familhas inscrichas als jos-eveniments.";
                         sFamilyUrlTip = "Clicatz sul ligan per veire lo detalh.";
                         break;

                     case 'fr':
                         sListLabel = "Familles inscrites sur cet événement et ses sous-événements...";
                         sListTip = "Cliquez pour afficher/masquer la liste des familles inscrites sur les sous-événements.";
                         sFamilyUrlTip = "Cliquez sur le lien pour visualiser le détail.";
                         break;

                     default:
                         sListLabel = "Families registered to this event and its sub-events...";
                         sListTip = "Click to show/hide the list of the families registered to sub-events.";
                         sFamilyUrlTip = "Click on the link to display the detail.";
                         break;
                 }

                 objAreaRegisteredFamilies.innerHTML = '<dt onclick="DisplaySubEventsRegistrationsShowHideList(' + iNbItems +
                                                       ');" title="' + sListTip + '">' + sListLabel + "</dt>";

                 // Add families in the list
                 var sComment = null;
                 var iPreviouEventID = 0;
                 for(var f = 0; f < iNbItems; f++) {
                     sComment = '';
                     if (objRegisteredFamilies.EventRegistrationComment[f] != '') {
                         // The family has set a comment of his registration
                         sComment = " (" + objRegisteredFamilies.EventRegistrationComment[f] + ")";
                     }

                     objAreaRegisteredFamilies.innerHTML += '<dd id="ddRF' + f + '"><a href="' + objRegisteredFamilies.FamilyUrl[f]
                                                            + '" title="' + sFamilyUrlTip + '" target="_blank">'
                                                            + objRegisteredFamilies.FamilyLastname[f] + '</a>' + sComment + '</dd>';
                 }

                 // We get the area where registered families list is displayed on the event
                 var objEventFamiliesList = document.getElementById('lParentEventID').parentNode.parentNode;
                 for(var n = 0; n < 13 ; n++) {
                     objEventFamiliesList = objEventFamiliesList.nextSibling;
                     if (objEventFamiliesList.nodeType != 1) {
                         objEventFamiliesList = objEventFamiliesList.nextSibling;
                     }
                 }

                 objEventFamiliesList = objEventFamiliesList.firstChild;
                 if (objEventFamiliesList.nodeType != 1) {
                     objEventFamiliesList = objEventFamiliesList.nextSibling;
                 }

                 objEventFamiliesList = objEventFamiliesList.nextSibling;

                 // We add, at the end of the area, the complete list of registered families on sub-events
                 objEventFamiliesList.appendChild(objAreaRegisteredFamilies);
             }
         }
     }
 }


 function DisplaySubEventsRegistrationsShowHideList(iNb)
 {
     if (iNb > 0) {
         // Ge the first registered family item
         var objDD = document.getElementById('ddRF0');
         if (objDD) {
             if (objDD.style.display == 'block') {
                 // Hide the first Family
                 objDD.style.display = 'none';
                 var iDisplayType = 'none';
             } else {
                 // Show the first Family
                 objDD.style.display = 'block';
                 var iDisplayType = 'block';
             }

             for(var i = 1; i < iNb; i++) {
                 objDD = document.getElementById('ddRF' + i);
                 objDD.style.display = iDisplayType;
             }
         }
     }
 }

