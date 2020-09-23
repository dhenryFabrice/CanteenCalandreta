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
 * JS plugin planning canteen auto save module : when the user check/uncheck a checkbox
 * in the planning, the canteen registration is auto save/deleted in the database
 *
 * @author Christophe Javouhey
 * @version 1.3
 *     - 2013-12-02 : taken into account the new way to display the canteen planning (without hidden input fields)
 *     - 2014-01-10 : if change not save in database, restore previous value (checkbox or input text)
 *     - 2017-11-07 : tacken into account packed lunches (Without pork = 2)
 *
 * @since 2013-09-09
 */


 var CanteenPlanningAutoSavePluginPath;
 var CanteenPlanningAutoSavePluginAjax;
 var CanteenPlanningAutoSavePluginLang;
 var CanteenPlanningAutoSavePluginCurrentCheckbox;


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2013-12-02 : taken into account the new way to display the canteen planning (without hidden input fields)
 *
 * @since 2013-09-09
 *
 * @param Lang    String    Language of the messages to display
 */
 function initCanteenPlanningAutoSavePlugin(Lang)
 {
     $A(document.getElementsByTagName("script")).findAll( function(s) {
         return (s.src && s.src.match(/JSCanteenPlanningAutoSavePlugin\.js(\?.*)?$/))
     }).each( function(s) {
         CanteenPlanningAutoSavePluginPath = s.src.replace(/JSCanteenPlanningAutoSavePlugin\.js(\?.*)?$/,'');
     });

     CanteenPlanningAutoSavePluginLang = Lang;

     // We get all checkbox of the current planning
     var objPlanning = document.getElementById('CanteenPlanning');
     var ArrayCheckbox = objPlanning.getElementsByClassName('checkbox');

     // for each, we add some new functions
     for(var i = 0; i < ArrayCheckbox.length; i++) {
         if (ArrayCheckbox[i].id == "chkCanteenRegitration[]") {
             // The checkbox is for a day and a child
             if(window.attachEvent) {
                 // IE
                 ArrayCheckbox[i].attachEvent("onclick", CanteenPlanningAutoSavePluginChildCheckboxClick);
             } else {
                 // FF
                 ArrayCheckbox[i].addEventListener("click", CanteenPlanningAutoSavePluginChildCheckboxClick, false);
             }
         } else {
             // The checkbox is for a day and a classroom
             if(window.attachEvent) {
                 // IE
                 ArrayCheckbox[i].attachEvent("onclick", CanteenPlanningAutoSavePluginClassCheckboxClick);
             } else {
                 // FF
                 ArrayCheckbox[i].addEventListener("click", CanteenPlanningAutoSavePluginClassCheckboxClick, false);
             }
         }
     }

     var ArrayTableRows = $('CanteenPlanning').childElements();
     ArrayTableRows = ArrayTableRows[ArrayTableRows.length - 1].childElements();

     // Check if the user planning view display totals
     if (ArrayTableRows[ArrayTableRows.length - 1].childElements()[0].className == 'PlanningTotalCaption') {
         // Check if the user planning view display more meals with pork
         var objInput = null;
         if (ArrayTableRows[ArrayTableRows.length - 8].childElements()[0].className == 'PlanningMoreMealsCaption') {
             var iSize = ArrayTableRows[ArrayTableRows.length - 8].childElements().length;
             for(var i = 0; i < iSize; i++) {
                 if (ArrayTableRows[ArrayTableRows.length - 8].childElements()[i].childElements().length > 0) {
                     // Get input type text
                     objInput = ArrayTableRows[ArrayTableRows.length - 8].childElements()[i].childElements()[0];
                     if (objInput.id.indexOf('sMoreMeals:') != -1) {
                         if(window.attachEvent) {
                             objInput.attachEvent("onkeyup", CanteenPlanningAutoSavePluginMoreMealsWithPorkKeyup);  // IE
                         } else {
                             objInput.addEventListener("keyup", CanteenPlanningAutoSavePluginMoreMealsWithPorkKeyup, false);  // FF
                         }
                     }
                 }
             }
         }

         // Check if the user planning view display more meals without pork
         if (ArrayTableRows[ArrayTableRows.length - 7].childElements()[0].className == 'PlanningMoreMealsCaption') {
             var iSize = ArrayTableRows[ArrayTableRows.length - 7].childElements().length;
             for(var i = 0; i < iSize; i++) {
                 if (ArrayTableRows[ArrayTableRows.length - 7].childElements()[i].childElements().length > 0) {
                     // Get input type text
                     objInput = ArrayTableRows[ArrayTableRows.length - 7].childElements()[i].childElements()[0];
                     if (objInput.id.indexOf('sMoreMealsWithoutPork:') != -1) {
                         if(window.attachEvent) {
                             objInput.attachEvent("onkeyup", CanteenPlanningAutoSavePluginMoreMealsWithoutPorkKeyup);  // IE
                         } else {
                             objInput.addEventListener("keyup", CanteenPlanningAutoSavePluginMoreMealsWithoutPorkKeyup, false);  // FF
                         }
                     }
                 }
             }
         }
     }

     if(window.XMLHttpRequest) // Firefox
         CanteenPlanningAutoSavePluginAjax = new XMLHttpRequest();
     else if(window.ActiveXObject) // Internet Explorer
         CanteenPlanningAutoSavePluginAjax = new ActiveXObject("Microsoft.XMLHTTP");
     else { // XMLHttpRequest non supporté par le navigateur
         alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
     }
 }


 function CanteenPlanningAutoSavePluginClassCheckboxClick(evt)
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
         CanteenPlanningAutoSavePluginAjax.open("GET", CanteenPlanningAutoSavePluginPath + "PHPCanteenPlanningAutoSavePlugin.php?getDateOfWeek="
                                                + Year + "|" + Week + "|" + NumDayOfWeek, false);
         CanteenPlanningAutoSavePluginAjax.send(null);
         if ((CanteenPlanningAutoSavePluginAjax.readyState == 4) && (CanteenPlanningAutoSavePluginAjax.status == 200)) {
             var DocXML = CanteenPlanningAutoSavePluginAjax.responseXML.documentElement;
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

     for(var i = 0 ; i < document.forms[0].length ; i++)
     {
         // Check if it's a field of the class
         if (document.forms[0].elements[i].name == "chkCanteenRegitration[]")
         {
             // Check if it's a field of the day
             if (document.forms[0].elements[i].value.indexOf(ClickedDate + "#" + Class + "#", 0) != -1)
             {
                 CanteenPlanningAutoSavePluginTreatChildCheckbox(document.forms[0].elements[i]);
             }
         }
     }
 }


 function CanteenPlanningAutoSavePluginChildCheckboxClick(evt)
 {
     var obj = evt.target || evt.srcElement;

     CanteenPlanningAutoSavePluginTreatChildCheckbox(obj);
 }


 function CanteenPlanningAutoSavePluginTreatChildCheckbox(obj)
 {
     // Save the current checkbox
     CanteenPlanningAutoSavePluginCurrentCheckbox = obj;

     // Define the action
     var sAction = "";
     if (obj.checked) {
         sAction = "register";
     } else {
         sAction = "delete";
     }

     // Send the Ajax request
     CanteenPlanningAutoSavePluginAjax.open("GET", CanteenPlanningAutoSavePluginPath + "PHPCanteenPlanningAutoSavePlugin.php?Action="
                                            + sAction + "&Param=" + obj.value.replace(/#/g, "|"), false);
     CanteenPlanningAutoSavePluginAjax.send(null);
     CanteenPlanningAutoSavePluginHandlerXML();
 }


 function CanteenPlanningAutoSavePluginHandlerXML()
 {
     if ((CanteenPlanningAutoSavePluginAjax.readyState == 4) && (CanteenPlanningAutoSavePluginAjax.status == 200)) {
         var DocXML = CanteenPlanningAutoSavePluginAjax.responseXML.documentElement;
         var items = DocXML.childNodes;
         var iNbItems = 0;

         if (items.length > 2) {
             var ArrayMsgs = new Array();
             var ArrayMsgTypes = new Array();
             var ArrayMsgID = new Array();
             var ArrayMsgGroup = new Array();
             var ArrayMsgWithoutPork = new Array();
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
                     ArrayMsgGroup[iNbItems] = items[i].getAttribute('group');
                     ArrayMsgWithoutPork[iNbItems] = items[i].getAttribute('withoutpork');
                     ArrayMsgAction[iNbItems] = items[i].getAttribute('action');
                     iNbItems++;
                 }
             }

             if ((iNbItems == 1) && (ArrayMsgs[0] != "-")) {
                 // Check if the message box exists ("-" = nothing to display)
                 var objMsgBox = document.getElementById('CanteenPlanningAutoSaveMsgBox');
                 if (!objMsgBox) {
                     // We create the msgbox
                     objMsgBox = document.createElement('p');
                     objMsgBox.setAttribute('id', 'CanteenPlanningAutoSaveMsgBox');
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
                     var ArrayTmp = ListToArray(CanteenPlanningAutoSavePluginCurrentCheckbox.value);
                     CanteenPlanningAutoSavePluginCurrentCheckbox.value = ArrayTmp[0] + '#' + ArrayTmp[1] + '#' + ArrayTmp[2]
                                                                          + '#' + ArrayMsgID[0];

                     // Compute the quantity to add
                     var iQuantityToAdd = -1;
                     if (CanteenPlanningAutoSavePluginCurrentCheckbox.checked) {
                         iQuantityToAdd = 1;
                     }

                     // Update the value of the "Total" row and column
                     var iNumColumn = CanteenPlanningAutoSavePluginCurrentCheckbox.parentNode.previousSiblings().length;
                     var iNumRow = CanteenPlanningAutoSavePluginCurrentCheckbox.parentNode.parentNode.previousSiblings().length;
                     var ArrayTableRows = $('CanteenPlanning').childElements();
                     ArrayTableRows = ArrayTableRows[ArrayTableRows.length - 1].childElements();

                     // First, the total of the row
                     var objTotalColumn = ArrayTableRows[iNumRow].childElements()[ArrayTableRows[ArrayTableRows.length - 1].childElements().length - 1];
                     var iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                     objTotalColumn.innerHTML = iColumnValue;

                     // Check if the user planning view display totals
                     if (ArrayTableRows[ArrayTableRows.length - 1].childElements()[0].className == 'PlanningTotalCaption') {
                         // Yes, the planning view display totals : we must update these values
                         // Next, the total of the column
                         objTotalColumn = ArrayTableRows[ArrayTableRows.length - 1].childElements()[iNumColumn];
                         iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                         objTotalColumn.innerHTML = iColumnValue;

                         // Next, the total column for the group of grades and without pork
                         switch(ArrayMsgWithoutPork[0])
                         {
                             case '1':
                                 // Without pork
                                 objTotalColumn = ArrayTableRows[ArrayTableRows.length - 5].childElements()[iNumColumn];
                                 break;

                             case '2':
                                 // Packed lunch
                                 objTotalColumn = ArrayTableRows[ArrayTableRows.length - 4].childElements()[iNumColumn];
                                 break;

                             case '0':
                             default:
                                 // With pork
                                 if (ArrayMsgGroup[0] == 1) {
                                     // First group
                                     objTotalColumn = ArrayTableRows[ArrayTableRows.length - 3].childElements()[iNumColumn];
                                 } else if (ArrayMsgGroup[0] == 2) {
                                     // Second group
                                     objTotalColumn = ArrayTableRows[ArrayTableRows.length - 2].childElements()[iNumColumn];
                                 }
                                 break;
                         }

                         iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                         objTotalColumn.innerHTML = iColumnValue;

                         // Next, the total of the row for the group of grades and without pork
                         switch(ArrayMsgWithoutPork[0])
                         {
                             case '1':
                                 // Without pork
                                 objTotalColumn = ArrayTableRows[ArrayTableRows.length - 5].childElements()[ArrayTableRows[ArrayTableRows.length - 5].childElements().length - 1];
                                 break;

                             case '2':
                                 // Packed lunch
                                 objTotalColumn = ArrayTableRows[ArrayTableRows.length - 4].childElements()[ArrayTableRows[ArrayTableRows.length - 4].childElements().length - 1];
                                 break;

                             case '0':
                             default:
                                 // With pork
                                 if (ArrayMsgGroup[0] == 1) {
                                     // First group
                                     objTotalColumn = ArrayTableRows[ArrayTableRows.length - 3].childElements()[ArrayTableRows[ArrayTableRows.length - 3].childElements().length - 1];
                                 } else if (ArrayMsgGroup[0] == 2) {
                                     // Second group
                                     objTotalColumn = ArrayTableRows[ArrayTableRows.length - 2].childElements()[ArrayTableRows[ArrayTableRows.length - 2].childElements().length - 1];
                                 }
                                 break;
                         }

                         iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                         objTotalColumn.innerHTML = iColumnValue;

                         // Next, the global total
                         objTotalColumn = ArrayTableRows[ArrayTableRows.length - 1].childElements()[ArrayTableRows[ArrayTableRows.length - 1].childElements().length - 1];
                         iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                         objTotalColumn.innerHTML = iColumnValue;
                     }
                 } else {
                     // Error message
                     objMsgBox.className = "ErrorMsgBox";

                     // Cancel the last action (checkbox checked/unchecked)
                     if (ArrayMsgAction[0] == 'register') {
                         // The checkbox was checked by the user but the dabase wasn't updated : we uncheck
                         CanteenPlanningAutoSavePluginCurrentCheckbox.checked = false;
                         CanteenPlanningAutoSavePluginCurrentCheckbox.parentNode.className = "CanteenPlanningAutoSavePluginCheckBoxError";
                     } else {
                         // The checkbox was unchecked by the user but the dabase wasn't updated : we check
                         CanteenPlanningAutoSavePluginCurrentCheckbox.checked = true;
                         CanteenPlanningAutoSavePluginCurrentCheckbox.parentNode.className = "CanteenPlanningAutoSavePluginCheckBoxError";
                     }
                 }

                 // Display the message then hide
                 $('CanteenPlanningAutoSaveMsgBox').pulsate({ pulses: 3, duration: 2});
                 $('CanteenPlanningAutoSaveMsgBox').fade({queue: 'end'});
             }
         }
     }
 }


 function CanteenPlanningAutoSavePluginMoreMealsWithPorkKeyup(evt)
 {
     var obj = evt.target || evt.srcElement;
     CanteenPlanningAutoSavePluginCurrentCheckbox = obj;

     var iQuantity = -1;
     if (obj.value == '') {
         iQuantity = 0;
     } else if (!isNaN(obj.value)) {
         // The value entered is a number
         iQuantity = parseInt(obj.value);
         if (iQuantity < 0) {
             iQuantity = 0;
         }
     }

     if (iQuantity >= 0) {
         // The quantity is a valid number : we can update
         // Send the Ajax request
         CanteenPlanningAutoSavePluginAjax.open("GET", CanteenPlanningAutoSavePluginPath +
                                                "PHPCanteenPlanningAutoSavePlugin.php?UpdateMoreMealsWithPork=" + iQuantity
                                                + "&Param=" + obj.id.replace("sMoreMeals:", ""), false);
         CanteenPlanningAutoSavePluginAjax.send(null);
         CanteenPlanningAutoSavePluginHandlerMoreMealsXML();
     }
 }


 function CanteenPlanningAutoSavePluginMoreMealsWithoutPorkKeyup(evt)
 {
     var obj = evt.target || evt.srcElement;
     CanteenPlanningAutoSavePluginCurrentCheckbox = obj;

     var iQuantity = -1;
     if (obj.value == '') {
         iQuantity = 0;
     } else if (!isNaN(obj.value)) {
         // The value entered is a number
         iQuantity = parseInt(obj.value);
         if (iQuantity < 0) {
             iQuantity = 0;
         }
     }

     if (iQuantity >= 0) {
         // The quantity is a valid number : we can update
         // Send the Ajax request
         CanteenPlanningAutoSavePluginAjax.open("GET", CanteenPlanningAutoSavePluginPath +
                                                "PHPCanteenPlanningAutoSavePlugin.php?UpdateMoreMealsWithoutPork=" + iQuantity
                                                + "&Param=" + obj.id.replace("sMoreMealsWithoutPork:", ""), false);
         CanteenPlanningAutoSavePluginAjax.send(null);
         CanteenPlanningAutoSavePluginHandlerMoreMealsXML();
     }
 }


 function CanteenPlanningAutoSavePluginHandlerMoreMealsXML()
 {
     if ((CanteenPlanningAutoSavePluginAjax.readyState == 4) && (CanteenPlanningAutoSavePluginAjax.status == 200)) {
         var DocXML = CanteenPlanningAutoSavePluginAjax.responseXML.documentElement;
         var items = DocXML.childNodes;
         var iNbItems = 0;

         if (items.length > 2) {
             var ArrayMsgs = new Array();
             var ArrayMsgTypes = new Array();
             var ArrayMsgID = new Array();
             var ArrayMsgGroup = new Array();
             var ArrayMsgWithoutPork = new Array();
             var ArrayMsgOldQuantity = new Array();
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
                     ArrayMsgGroup[iNbItems] = items[i].getAttribute('group');
                     ArrayMsgWithoutPork[iNbItems] = items[i].getAttribute('withoutpork');
                     ArrayMsgOldQuantity[iNbItems] = items[i].getAttribute('oldquantity');
                     iNbItems++;
                 }
             }

             if ((iNbItems == 1) && (ArrayMsgs[0] != "-")) {
                 // Check if the message box exists ("-" = nothing to display)
                 var objMsgBox = document.getElementById('CanteenPlanningAutoSaveMsgBox');
                 if (!objMsgBox) {
                     // We create the msgbox
                     objMsgBox = document.createElement('p');
                     objMsgBox.setAttribute('id', 'CanteenPlanningAutoSaveMsgBox');
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
                     CanteenPlanningAutoSavePluginCurrentCheckbox.className = "text";

                     // Update the value of the "Total" row and column
                     var iNumColumn = CanteenPlanningAutoSavePluginCurrentCheckbox.parentNode.previousSiblings().length;
                     var iNumRow = CanteenPlanningAutoSavePluginCurrentCheckbox.parentNode.parentNode.previousSiblings().length;
                     var ArrayTableRows = $('CanteenPlanning').childElements();
                     ArrayTableRows = ArrayTableRows[ArrayTableRows.length - 1].childElements();

                     var iQuantityToAdd = 0;
                     iQuantityToAdd = CanteenPlanningAutoSavePluginCurrentCheckbox.value - ArrayMsgOldQuantity[0];

                     // Update the "ID" and "name" attributs of the input text field with the new ID
                     if (CanteenPlanningAutoSavePluginCurrentCheckbox.id.indexOf('sMoreMealsWithoutPork:') != -1) {
                         // Update the "More meals without pork" text field with the ID
                         var ArrayTmp = ListSepToArray(CanteenPlanningAutoSavePluginCurrentCheckbox.id.replace("sMoreMealsWithoutPork:", ""), '_');
                         CanteenPlanningAutoSavePluginCurrentCheckbox.id = 'sMoreMealsWithoutPork:' + ArrayTmp[0] + '_' + ArrayMsgID[0];

                         // Update the "More meals" text field with the ID
                         var objSecondInputToUpdate = ArrayTableRows[iNumRow - 1].childElements()[iNumColumn].childElements()[ArrayTableRows[iNumRow - 1].childElements()[iNumColumn].childElements().length - 1];
                         objSecondInputToUpdate.id = 'sMoreMeals:' + ArrayTmp[0] + '_' + ArrayMsgID[0];
                     } else if (CanteenPlanningAutoSavePluginCurrentCheckbox.id.indexOf('sMoreMeals:') != -1) {
                         // Update the "More meals" text field with the ID
                         var ArrayTmp = ListSepToArray(CanteenPlanningAutoSavePluginCurrentCheckbox.id.replace("sMoreMeals:", ""), '_');
                         CanteenPlanningAutoSavePluginCurrentCheckbox.id = 'sMoreMeals:' + ArrayTmp[0] + '_' + ArrayMsgID[0];

                         // Update the "More meals without pork" text field with the ID
                         var objSecondInputToUpdate = ArrayTableRows[iNumRow + 1].childElements()[iNumColumn].childElements()[ArrayTableRows[iNumRow + 1].childElements()[iNumColumn].childElements().length - 1];
                         objSecondInputToUpdate.id = 'sMoreMealsWithoutPork:' + ArrayTmp[0] + '_' + ArrayMsgID[0];
                     }

                     CanteenPlanningAutoSavePluginCurrentCheckbox.name = CanteenPlanningAutoSavePluginCurrentCheckbox.id;
                     objSecondInputToUpdate.name = objSecondInputToUpdate.id;

                     // First, the total of the row
                     var objTotalColumn = ArrayTableRows[iNumRow].childElements()[ArrayTableRows[ArrayTableRows.length - 1].childElements().length - 1];
                     var iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                     objTotalColumn.innerHTML = iColumnValue;

                     // Next, the total of the column
                     objTotalColumn = ArrayTableRows[ArrayTableRows.length - 1].childElements()[iNumColumn];
                     iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                     objTotalColumn.innerHTML = iColumnValue;

                     // Next, the global total
                     objTotalColumn = ArrayTableRows[ArrayTableRows.length - 1].childElements()[ArrayTableRows[ArrayTableRows.length - 1].childElements().length - 1];
                     iColumnValue = parseInt(objTotalColumn.innerHTML) + iQuantityToAdd;
                     objTotalColumn.innerHTML = iColumnValue;
                 } else {
                     // Error message
                     objMsgBox.className = "ErrorMsgBox";

                     // New quantity not updated in the database : we restore the old quantity
                     CanteenPlanningAutoSavePluginCurrentCheckbox.value = ArrayMsgOldQuantity[0];
                     CanteenPlanningAutoSavePluginCurrentCheckbox.className = "CanteenPlanningAutoSavePluginInputError";
                 }

                 // Display the message then hide
                 $('CanteenPlanningAutoSaveMsgBox').pulsate({ pulses: 3, duration: 2});
                 $('CanteenPlanningAutoSaveMsgBox').fade({queue: 'end'});
             }
         }
     }
 }
