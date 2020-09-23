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
 * PHP plugin autocompletion module : add the autocompletion function for the recipients and in copy
 * fields of the "Send message" form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-02
 */


 var AutoCompletionSendMessagePluginLangage;


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-02
 */
 function initAutoCompletionSendMessagePlugin(FormFieldName, Lang)
 {
     var AutoCompletionSendMessagePluginPath= '';
     AutoCompletionSendMessagePluginLangage = Lang;

     $A(document.getElementsByTagName("script")).findAll( function(s) {
         return (s.src && s.src.match(/JSAutoCompletionSendMessagePlugin\.js(\?.*)?$/))
     }).each( function(s) {
         AutoCompletionSendMessagePluginPath = s.src.replace(/JSAutoCompletionSendMessagePlugin\.js(\?.*)?$/,'');
     });

     // We check if the form field exists
     var objFormField = document.getElementById(FormFieldName);
     if (objFormField) {
         // We create the DIV to display values found
         var AutoCompletionDiv = document.createElement('div');
         AutoCompletionDiv.setAttribute('id', FormFieldName + 'ListAutoCompletion_update');
         AutoCompletionDiv.className = 'FormFieldAutoCompletionlist_update';
         objFormField.parentNode.insertBefore(AutoCompletionDiv, objFormField.nextSibling);

         new Ajax.Autocompleter(
                                FormFieldName,
                                FormFieldName + 'ListAutoCompletion_update',
                                AutoCompletionSendMessagePluginPath + 'PHPAutoCompletionSendMessagePlugin.php',
                                {
                                    method: 'post',
                                    paramName: 'debut',
                                    minChars: 2,
                                    tokens: ';',
                                    afterUpdateElement: getAutoCompletionSendMessageSelectionId,
                                    parameters: 'FormFieldName=' + FormFieldName
                                }
                               );
     }
 }


 function getAutoCompletionSendMessageSelectionId(input, li)
 {
     var objRecipientsList = document.getElementById('objRecipientsList');
     if (objRecipientsList) {
         // Add an item in this <div>
         var objItemAdded = document.createElement('div');
         objItemAdded.setAttribute('id', 'L' + li.id);
         objItemAdded.className = 'AutoCompletionSendMessageItem';
         objItemAdded.innerHTML = li.innerHTML;

         var sTip = '';
         switch(AutoCompletionSendMessagePluginLangage) {
             case 'oc':
                 sTip = "Clicatz aici per suprimir la familha/alias " + li.innerHTML + ".";
                 break;

             case 'fr':
                 sTip = "Cliquez ici pour supprimer la destinataire " + li.innerHTML + ".";
                 break;

             case 'en':
             default:
                 sTip = "Click here to delete the " + li.innerHTML + " recipient.";
                 break;
         }

         objItemAdded.setAttribute('title', sTip);

         if (window.attachEvent) {
             objItemAdded.attachEvent("onclick", AutoCompletionSendMessageItemOnClick);            // IE
         } else {
             objItemAdded.addEventListener("click", AutoCompletionSendMessageItemOnClick, false);  // FF
         }

         objRecipientsList.appendChild(objItemAdded);
     }

     // Delete value in the input type text
     input.value = '';
 }


 function AutoCompletionSendMessageItemOnClick(evt)
 {
     var objItem = evt.target || evt.srcElement;

     // Delete the item
     objItem.parentNode.removeChild(objItem);
 }



