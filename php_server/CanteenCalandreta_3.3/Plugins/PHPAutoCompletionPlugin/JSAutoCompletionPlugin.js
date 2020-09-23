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
 * PHP plugin autocompletion module : add the autocompletion function
 * for a given field of a given form
 *
 * @author STNA/7SQ
 * @version 3.7
 * @since 2011-09-16
 */


/**
 * Function used to init this plugin
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2012-01-09 : allow to search value in the CustomFieldsValues table
 *
 * @since 2011-09-16
 */
 function initAutoCompletion(FormFieldName, DbTableName, DbFieldName)
 {
     var AutoCompletionPluginPath= '';

     $A(document.getElementsByTagName("script")).findAll( function(s) {
         return (s.src && s.src.match(/JSAutoCompletionPlugin\.js(\?.*)?$/))
     }).each( function(s) {
         AutoCompletionPluginPath = s.src.replace(/JSAutoCompletionPlugin\.js(\?.*)?$/,'');
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
                                AutoCompletionPluginPath + 'PHPAutoCompletionPlugin.php',
                                {
                                    method: 'post',
                                    paramName: 'debut',
                                    parameters: 'TableName=' + DbTableName + '&FieldName=' + DbFieldName + '&FormFieldName=' + FormFieldName
                                }
                               );
     }
 }



