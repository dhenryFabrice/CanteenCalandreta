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
 * @version 2.0
 *     - 2014-01-10 : if change not save in database, restore previous value (checkbox or input text)
 *     - 2020-02-20 : taken into account the other timeslots
 *
 * @since 2013-09-12
 */


 var NurseryPlanningAutoSavePluginPath;
 var NurseryPlanningAutoSavePluginAjax;
 var NurseryPlanningAutoSavePluginLang;
 var NurseryPlanningAutoSavePluginCurrentCheckbox;
 var NurseryPlanningAutoSavePluginOtherTimeslots = new Array();


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

     if(window.XMLHttpRequest) // Firefox
         NurseryPlanningAutoSavePluginAjax = new XMLHttpRequest();
     else if(window.ActiveXObject) // Internet Explorer
         NurseryPlanningAutoSavePluginAjax = new ActiveXObject("Microsoft.XMLHTTP");
     else { // XMLHttpRequest non supporté par le navigateur
         alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
     }

     // We want to get other timeslots if exist for this month or week of the concerned school year
     var sParam = '';
     var Year = document.getElementById('lYear').value;

     // We check if the view is by month or by week
     if (document.getElementById('lMonth')) {
         // View of the planning by month
         var Month = document.getElementById('lMonth').value;
         if (Month < 10) {
             Month = "0" + Month.toString();
         }

         sParam = Year + "-" + Month;
     } else if (document.getElementById('lWeek')) {
         // View of the planning by week
         var Week = document.getElementById('lWeek').value;
         sParam = Year + "-W" + Week;
     }

     // Synchrone mode!!!
     NurseryPlanningAutoSavePluginAjax.open("GET", NurseryPlanningAutoSavePluginPath + "PHPNurseryPlanningAutoSavePlugin.php?getOtherTimeslots=" + sParam, false);
     NurseryPlanningAutoSavePluginAjax.send(null);
     if ((NurseryPlanningAutoSavePluginAjax.readyState == 4) && (NurseryPlanningAutoSavePluginAjax.status == 200)) {
         var DocXML = NurseryPlanningAutoSavePluginAjax.responseXML.documentElement;
         var items = DocXML.childNodes;
         if (items.length > 2) {
             for(var i = 0; i < items.length; i++) {
                 if (items[i].nodeName == 'othertimeslot') {
                     // We get the timeslot
                     NurseryPlanningAutoSavePluginOtherTimeslots.push({'id': items[i].getAttribute('id'),
                                                                       'checkcanteen': items[i].getAttribute('check-canteen'),
                                                                       'linked2canteen': items[i].getAttribute('linked2canteen'),
                                                                       'checknursery': items[i].getAttribute('check-nursery')});
                 }
             }
         }
     }

     // We define the list of possible checkbox ID
     var ConcernedCheckboxID = new Array();
     ConcernedCheckboxID.push("chkNurseryRegitrationAM[]", "chkNurseryRegitrationPM[]");
     if (NurseryPlanningAutoSavePluginOtherTimeslots.length > 0) {
         // There are some timeslots
         for(var i = 0; i < NurseryPlanningAutoSavePluginOtherTimeslots.length; i++) {
             ConcernedCheckboxID.push("chkNurseryRegitrationOtherTimeslot" + NurseryPlanningAutoSavePluginOtherTimeslots[i].id + "[]");
         }
     }

     // We get all checkbox of the current planning
     var objPlanning = document.getElementById('NurseryPlanning');
     var ArrayCheckbox = objPlanning.getElementsByClassName('checkbox');

     // for each, we add some new functions
     for(var i = 0; i < ArrayCheckbox.length; i++) {
         if (NurseryPlanningAutoSavePluginIn_Array(ConcernedCheckboxID, ArrayCheckbox[i].id)) {
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
 }


 // To check/unchkeck all checkboxes of a class
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

     // Check if it's for AM, PM or other timeslot
     var sNameToCheck = "chkNurseryRegitrationAM[]";
     if (obj.id.indexOf("chkNurseryRegitrationPMClass", 0) != -1) {
         // It's for PM
         sNameToCheck = "chkNurseryRegitrationPM[]";
     } else if (obj.id.indexOf("chkNurseryRegitrationOtherTimeslotClass", 0) != -1) {
         // It's for another timeslot. We extract its id (at the end)
         var sOtherTimeslotID = obj.id.slice(obj.id.lastIndexOf('_') + 1);
         sNameToCheck = "chkNurseryRegitrationOtherTimeslot" + sOtherTimeslotID + "[]";
     }

     for(i = 0 ; i < document.forms[0].length ; i++) {
         // Check if it's a field of the class
         if (document.forms[0].elements[i].name == sNameToCheck) {
             // Check if it's a field of the day
             if (document.forms[0].elements[i].value.indexOf(ClickedDate + "#" + Class + "#", 0) != -1) {
                 NurseryPlanningAutoSavePluginTreatChildCheckbox(document.forms[0].elements[i]);
             }
         }
     }
 }


 // To check/uncheck a checkbox of a child and a date
 function NurseryPlanningAutoSavePluginChildCheckboxClick(evt)
 {
     var obj = evt.target || evt.srcElement;

     NurseryPlanningAutoSavePluginTreatChildCheckbox(obj);
 }


 // To treat the check or uncheck of a checkbox of a child, for a date
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

     // Check if AM, PM or other timeslot
     var sPeriod = 0;
     var ArrayTmp = NurseryPlanningAutoSavePluginListSepToArray(obj.value, '#');
     var ChkDateValue = ArrayTmp[0];
     var ChkChildIDValue = ArrayTmp[2];
     var ArrayOtherTimeslotsToCheck = new Array();

     if (obj.id == "chkNurseryRegitrationAM[]") {
         sPeriod = 1; // AM
     } else if (obj.id == "chkNurseryRegitrationPM[]") {
         sPeriod = 2 + NurseryPlanningAutoSavePluginOtherTimeslots.length;  // PM
     } else {
         // Other timeslot : we get its ID and search its position in other timeslots
         if (NurseryPlanningAutoSavePluginOtherTimeslots.length > 0) {
             var sOtherTimeslotID = 'chkNurseryRegitrationOtherTimeslot';
             sOtherTimeslotID = obj.id.slice(sOtherTimeslotID.length, obj.id.length - 2);
             var iPos = -1;
             for(var i = 0; i < NurseryPlanningAutoSavePluginOtherTimeslots.length; i++) {
                 if (NurseryPlanningAutoSavePluginOtherTimeslots[i].id == sOtherTimeslotID) {
                     iPos = i;
                 }
             }

             if (iPos > -1) {
                 sPeriod = 2 + iPos;

                 // Check if this other timeslot is linked to other timeslots
                 if (sAction == 'register') {
                     if (NurseryPlanningAutoSavePluginOtherTimeslots[iPos].checknursery != '') {
                         ArrayOtherTimeslotsToCheck = NurseryPlanningAutoSavePluginListSepToArray(NurseryPlanningAutoSavePluginOtherTimeslots[iPos].checknursery, ',');
                     }
                 }
             }
         }
     }

     // Send the Ajax request
     NurseryPlanningAutoSavePluginAjax.open("GET", NurseryPlanningAutoSavePluginPath + "PHPNurseryPlanningAutoSavePlugin.php?Action="
                                            + sAction + "&Param=" + obj.value.replace(/#/g, "|") + "|" + sPeriod, false);
     NurseryPlanningAutoSavePluginAjax.send(null);
     NurseryPlanningAutoSavePluginHandlerXML();

     if (ArrayOtherTimeslotsToCheck.length > 0) {
         var ArrayCheckboxesOfConcernedOTS;
         var ArrayCurrentChkValue;

         for(var ots = 0; ots < ArrayOtherTimeslotsToCheck.length; ots++) {
             // Get the checkbox to check if not checked
             ArrayCheckboxesOfConcernedOTS = document.getElementsByName('chkNurseryRegitrationOtherTimeslot' + ArrayOtherTimeslotsToCheck[ots] + '[]');
             if (ArrayCheckboxesOfConcernedOTS.length > 0) {
                 for(var j = 0; j < ArrayCheckboxesOfConcernedOTS.length; j++) {
                     // Value of checkbox : Day#Class#ChildID#NurseryRegistrationID
                     ArrayCurrentChkValue = NurseryPlanningAutoSavePluginListSepToArray(ArrayCheckboxesOfConcernedOTS[j].value, '#');
                     if ((ArrayCurrentChkValue[0] == ChkDateValue) && (ArrayCurrentChkValue[2] == ChkChildIDValue)) {
                         // This checkbox is for the same date as the current checked checkbox and the same child ID.
                         // So, we have to check if too if not checked
                         if (!ArrayCheckboxesOfConcernedOTS[j].checked) {
                             ArrayCheckboxesOfConcernedOTS[j].click();
                         }
                     }
                 }
             }
         }
     }
 }


 // Treat the answer of the server after a checkbox or several have been checked/unchecked
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


 function NurseryPlanningAutoSavePluginIn_Array(Table, Value)
 {
     var IsInArray = false;
     var i = 0;
     var NbElements = Table.length;
     while((i < NbElements) && (IsInArray == false))
     {
         if (Table[i] == Value)
         {
             IsInArray = true;
         }
         else
         {
             i++;
         }
     }

     return IsInArray;
 }


 function NurseryPlanningAutoSavePluginListSepToArray(List, Separator)
 {
     var ArrayResult = new Array();

     if (List != "")
     {
         var i = 0;
         var PosInit = 0;
         var Pos = List.indexOf(Separator, PosInit);
         while (Pos != -1)
         {
             // We extract the value
             ArrayResult[i] = List.substring(PosInit, Pos);
             PosInit = Pos + 1;
             i++;

             // We extract the next value
             Pos = List.indexOf(Separator, PosInit);
         }

         // We try to extract the last value
         var sLastValue = List.substring(PosInit, List.length);
         if (sLastValue != "")
         {
             ArrayResult[i] = sLastValue;
         }
     }

     return ArrayResult;
 }


