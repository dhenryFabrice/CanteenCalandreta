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
 * PHP plugin autocompletion module : add the autocompletion function for the sEmail field of the
 * workgroup registration form
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-03-11 : min entered characters set to 3.
 *
 * @since 2015-10-21
 */


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-03-11 : min entered characters set to 3.
 *
 * @since 2015-10-21
 */
 function initAutoCompletionWorkGroupRegistrationEmailPlugin(FormFieldName)
 {
     var AutoCompletionWorkGroupRegistrationEmailPluginPath= '';

     $A(document.getElementsByTagName("script")).findAll( function(s) {
         return (s.src && s.src.match(/JSAutoCompletionWorkGroupRegistrationEmailPlugin\.js(\?.*)?$/))
     }).each( function(s) {
         AutoCompletionWorkGroupRegistrationEmailPluginPath = s.src.replace(/JSAutoCompletionWorkGroupRegistrationEmailPlugin\.js(\?.*)?$/,'');
     });

     // We check if the form field exists
     var objFormField = document.getElementById(FormFieldName);
     if (objFormField)
     {
         // We create the DIV to display values found
         var AutoCompletionDiv = document.createElement('div');
         AutoCompletionDiv.setAttribute('id', FormFieldName + 'ListAutoCompletion_update');
         AutoCompletionDiv.className = 'FormFieldAutoCompletionlist_update';
         objFormField.parentNode.insertBefore(AutoCompletionDiv, objFormField.nextSibling);

         new Ajax.Autocompleter(
                                FormFieldName,
                                FormFieldName + 'ListAutoCompletion_update',
                                AutoCompletionWorkGroupRegistrationEmailPluginPath + 'PHPAutoCompletionWorkGroupRegistrationEmailPlugin.php',
                                {
                                    method: 'post',
                                    paramName: 'debut',
                                    minChars: 3,
                                    parameters: 'FormFieldName=' + FormFieldName
                                }
                               );
     }
 }



