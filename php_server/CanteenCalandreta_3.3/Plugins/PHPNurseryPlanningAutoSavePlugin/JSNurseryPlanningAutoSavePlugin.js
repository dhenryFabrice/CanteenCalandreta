/* Copyright (C) 2012 Calandreta Del Païs Murethin
 *
 * This file is part of NurseryCalandreta.
 *
 * NurseryCalandreta is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * NurseryCalandreta is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NurseryCalandreta; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * JS plugin planning nursery auto save module : when the user check/uncheck a checkbox
 * in the planning, the nursery registration is auto save/deleted in the database
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2014-01-10 : if change not save in database, restore previous value (checkbox or input text)
 *
 * @since 2013-09-12
 */


 var NurseryPlanningAutoSavePluginPath;
 var NurseryPlanningAutoSavePluginAjax;
 var NurseryPlanningAutoSavePluginLang;
 var NurseryPlanningAutoSavePluginCurrentCheckbox;


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-09-09
 *
 * @param Lang    String    Language of the messages to display
 */
 function initNurseryPlanningAutoSavePlugin(Lang)
 {
     $A(document.getElementsByTagName("script")).findAll( function(s) {
         return (s.src && s.src.match(/JSNurseryPlanningAutoSavePlugin\.js(\?.*)?$/))
     }).each( function(s) {
         NurseryPlanningAutoSavePluginPath = s.src.replace(/JSNurseryPlanningAutoSavePlugin\.js(\?.*)?$/,'');
     });

     NurseryPlanningAutoSavePluginLang = Lang;

     // We get all checkbox of the current planning
     var objPlanning = document.getElementById('NurseryPlanning');
     var ArrayCheckbox = objPlanning.getElementsByClassName('checkbox');

     // for each, we add some new functions
     for(var i = 0; i < ArrayCheckbox.length; i++) {
         if ((ArrayCheckbox[i].id == "chkNurseryRegitrationAM[]") || (ArrayCheckbox[i].id == "chkNurseryRegitrationPM[]")) {
             // The checkbox is for a day and a child
             if(window.attachEvent) {
                 // IE
                 ArrayCheckbox[i].attachEvent("onclick", NurseryPlanningAutoSavePluginChildCheckboxClick);
             } else {
                 // FF
                 ArrayCheckbox[i].addEventListener("click", NurseryPlanningAutoSavePluginChildCheckboxClick, false);
             }
         } else {
             // The checkbox is for a day and a classroom
             if(window.attachEvent) {
                 // IE
                 ArrayCheckbox[i].attachEvent("onclick", NurseryPlanningAutoSavePluginClassCheckboxClick);
             } else {
                 // FF
                 ArrayCheckbox[i].addEventListener("click", NurseryPlanningAutoSavePluginClassCheckboxClick, false);
             }
         }
     }

     if(window.XMLHttpRequest) // Firefox
         NurseryPlanningAutoSavePluginAjax = new XMLHttpRequest();
     else if(window.ActiveXObject) // Internet Explorer
         NurseryPlanningAutoSavePluginAjax = new ActiveXObject("Microsoft.XMLHTTP");
     else { // XMLHttpRequest non supporté par le navigateur
         alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
     }
 }


 function NurseryPlanningAutoSavePluginClassCheckboxClick(evt)
 {
     var obj = evt.target || evt.srcElement;

     var ArrayTmp = ListSepToArray(obj.id, "_");
     var Class = obj.value;
     var Year = document.getElementById('lYear').value;

     // We check if the view is by month or by week
     if (document.getElementById('lMonth')) {
         // View of the planning by month
         var Day = parseInt(ArrayTmp[2]) + 1;
         if (Day < 10) {
             Day = "0" + Day.toString();
         }

         var Month = document.getElementById('lMonth').value;
         if (Month < 10) {
             Month = "0" + Month.toString();
         }

         var ClickedDate = Year + "-" + Month + "-" + Day;
     } else if (document.getElementById('lWeek')) {
         // View of the planning by week
         var NumDayOfWeek = parseInt(ArrayTmp[2]) + 1;
         var Week = document.getElementById('lWeek').value;

         // We get the date in relation with the week/year/number of day selected (1 = monday)
         NurseryPlanningAutoSavePluginAjax.open("GET", NurseryPlanningAutoSavePluginPath + "PHPNurseryPlanningAutoSavePlugin.php?getDateOfWeek="
                                                + Year + "|" + Week + "|" + NumDayOfWeek, false);
         NurseryPlanningAutoSavePluginAjax.send(null);
         if ((NurseryPlanningAutoSavePluginAjax.readyState == 4) && (NurseryPlanningAutoSavePluginAjax.status == 200)) {
             var DocXML = NurseryPlanningAutoSavePluginAjax.responseXML.documentElement;
             var items = DocXML.childNodes;
             if (items.length > 2) {
                 for(var i = 0; i < items.length; i++) {
                     if (items[i].nodeName == 'date') {
                         // We get the date
                         var ClickedDate = items[i].getAttribute('value');
                     }
                 }
             }
         }
     }

     // Check if it's for AM or PM
     var sNameToCheck = "chkNurseryRegitrationAM[]";
     if (obj.id.indexOf("chkNurseryRegitrationPMClass", 0) != -1)
     {
         // It's for PM
         sNameToCheck = "chkNurseryRegitrationPM[]";
     }

     for(i = 0 ; i < document.forms[0].length ; i++)
     {
         // Check if it's a field of the class
         if (document.forms[0].elements[i].name == sNameToCheck)
         {
             // Check if it's a field of the day
             if (document.forms[0].elements[i].value.indexOf(ClickedDate + "#" + Class + "#", 0) != -1)
             {
                 NurseryPlanningAutoSavePluginTreatChildCheckbox(document.forms[0].elements[i]);
             }
         }
     }
 }


 function NurseryPlanningAutoSavePluginChildCheckboxClick(evt)
 {
     var obj = evt.target || evt.srcElement;

     NurseryPlanningAutoSavePluginTreatChildCheckbox(obj);
 }


 function NurseryPlanningAutoSavePluginTreatChildCheckbox(obj)
 {
     // Save the current checkbox
     NurseryPlanningAutoSavePluginCurrentCheckbox = obj;

     // Define the action
     var sAction = "";
     if (obj.checked) {
         sAction = "register";
     } else {
         sAction = "delete";
     }

     // Check if AM or PM
     var sPeriod = 1;  // AM
     if (obj.id == "chkNurseryRegitrationPM[]") {
         sPeriod = 2;  // PM
     }

     // Send the Ajax request
     NurseryPlanningAutoSavePluginAjax.open("GET", NurseryPlanningAutoSavePluginPath + "PHPNurseryPlanningAutoSavePlugin.php?Action="
                                            + sAction + "&Param=" + obj.value.replace(/#/g, "|") + "|" + sPeriod, false);
     NurseryPlanningAutoSavePluginAjax.send(null);
     NurseryPlanningAutoSavePluginHandlerXML();
 }


 function NurseryPlanningAutoSavePluginHandlerXML()
 {
     if ((NurseryPlanningAutoSavePluginAjax.readyState == 4) && (NurseryPlanningAutoSavePluginAjax.status == 200)) {
         var DocXML = NurseryPlanningAutoSavePluginAjax.responseXML.documentElement;
         var items = DocXML.childNodes;
         var iNbItems = 0;

         if (items.length > 2) {
             var ArrayMsgs = new Array();
             var ArrayMsgTypes = new Array();
             var ArrayMsgID = new Array();
             var ArrayMsgAction = new Array();
             var sMsgTmp = '';
             for(var i = 0; i < items.length; i++) {
                 if (items[i].nodeName == 'message') {
                     sMsgTmp = items[i].nodeValue;
                     if (sMsgTmp == null) {
                         sMsgTmp = items[i].childNodes[0].nodeValue;
                     }

                     ArrayMsgs[iNbItems] = sMsgTmp;
                     ArrayMsgTypes[iNbItems] = items[i].getAttribute('type');
                     ArrayMsgID[iNbItems] = items[i].getAttribute('id');
                     ArrayMsgAction[iNbItems] = items[i].getAttribute('action');
                     iNbItems++;
                 }
             }

             if ((iNbItems == 1) && (ArrayMsgs[0] != "-")) {
                 // Check if the message box exists ("-" = nothing to display)
                 var objMsgBox = document.getElementById('NurseryPlanningAutoSaveMsgBox');
                 if (!objMsgBox) {
                     // We create the msgbox
                     objMsgBox = document.createElement('p');
                     objMsgBox.setAttribute('id', 'NurseryPlanningAutoSaveMsgBox');
                     var objWebPage = document.getElementById('page');
                     objWebPage.down(1).insert({before: objMsgBox});
                 } else {
                     // Reset the styles
                     objMsgBox.show();
                 }

                 // Update the message and the styles
                 objMsgBox.innerHTML = ArrayMsgs[0];
                 if (ArrayMsgTypes[0] == 1) {
                     // Success message
                     objMsgBox.className = "ConfirmMsgBox";

                     // Update the value of the checkbox with the new ID
                     var ArrayTmp = ListToArray(NurseryPlanningAutoSavePluginCurrentCheckbox.value);
                     NurseryPlanningAutoSavePluginCurrentCheckbox.value = ArrayTmp[0] + '#' + ArrayTmp[1] + '#' + ArrayTmp[2]
                                                                          + '#' + ArrayMsgID[0];

                     // Update the value of the "Total" row and column
                     var iQuantityToAdd = -1;
                     if (NurseryPlanningAutoSavePluginCurrentCheckbox.checked) {
                         iQuantityToAdd = 1;
                     }

                     var iNumColumn = NurseryPlanningAutoSavePluginCurrentCheckbox.parentNode.previousSiblings().length;
                     var iNumRow = NurseryPlanningAutoSavePluginCurrentCheckbox.parentNode.parentNode.previousSiblings().length;
                     var ArrayTableRows = $('NurseryPlanning').childElements();

                     // First, the total of the column
                     ArrayTableRows = ArrayTableRows[ArrayTableRows.length - 1].childElements();
                     var objTotalColumn = ArrayTableRows[ArrayTableRows.length - 1].childElements()[iNumColumn];
                     var iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                     objTotalColumn.innerHTML = iColumnValue;

                     // Next, the global total
                     objTotalColumn = ArrayTableRows[ArrayTableRows.length - 1].childElements()[ArrayTableRows[ArrayTableRows.length - 1].childElements().length - 1];
                     var iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                     objTotalColumn.innerHTML = iColumnValue;

                     // Then, the total of the row
                     objTotalColumn = ArrayTableRows[iNumRow].childElements()[ArrayTableRows[ArrayTableRows.length - 1].childElements().length - 1];
                     var iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                     objTotalColumn.innerHTML = iColumnValue;
                 } else {
                     // Error message
                     objMsgBox.className = "ErrorMsgBox";

                     // Cancel the last action (checkbox checked/unchecked)
                     if (ArrayMsgAction[0] == 'register') {
                         // The checkbox was checked by the user but the dabase wasn't updated : we uncheck
                         NurseryPlanningAutoSavePluginCurrentCheckbox.checked = false;
                         NurseryPlanningAutoSavePluginCurrentCheckbox.parentNode.className = "NurseryPlanningAutoSavePluginCheckBoxError";
                     } else {
                         // The checkbox was unchecked by the user but the dabase wasn't updated : we check
                         NurseryPlanningAutoSavePluginCurrentCheckbox.checked = true;
                         NurseryPlanningAutoSavePluginCurrentCheckbox.parentNode.className = "NurseryPlanningAutoSavePluginCheckBoxError";
                     }
                 }

                 // Display the message then hide
                 $('NurseryPlanningAutoSaveMsgBox').pulsate({ pulses: 3, duration: 2});
                 $('NurseryPlanningAutoSaveMsgBox').fade({queue: 'end'});
             }
         }
     }
 }


