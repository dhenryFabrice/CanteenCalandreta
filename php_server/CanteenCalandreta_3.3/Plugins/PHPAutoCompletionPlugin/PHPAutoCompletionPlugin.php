<?php
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
 * PHP plugin autocompletion module : give values for the autocompletion function of
 * a given field name of a given
 *
 * @author STNA/7SQ and Christophe Javouhey
 * @version 3.0
 *     - 2012-01-09 : allow to search value in the CustomFieldsValues table
 *     - 2016-06-20 : taken into account $GLOBALS['CONF_CHARSET']
 *     - 2016-11-02 : load some configuration variables from database
 *
 *
 * @since 2011-09-16
 */


 // Include Config.php because of the name of the session
 require_once dirname(__FILE__).'/../../Common/DefinedConst.php';
 require_once dirname(__FILE__).'/../../Common/Config.php';
 require_once dirname(__FILE__).'/../../Common/DbLibrary.php';

 // Connection to the database
 $DbCon = dbConnection();

 // Get the value
 if (isset($_POST['debut'])) {
     $sEnteredValue = $_POST['debut'];
     $sEnteredValue = strip_tags(strtolower(trim($sEnteredValue))).'%';
 } else {
     $sEnteredValue = "%";
 }

 // Get the name of the table in which we must search the entered value
 $sTableName = '';
 if (isset($_POST['TableName'])) {
    $sTableName = strip_tags(trim($_POST['TableName']));
 }

 // Get the name of the field in which we must search the entered value
 $sFieldName = '';
 if (isset($_POST['FieldName'])) {
    $sFieldName = strip_tags(trim($_POST['FieldName']));
 }

 // Get the name of the form field in which we have entered the value
 $sFormFieldName = '';
 if (isset($_POST['FormFieldName'])) {
    $sFormFieldName = strip_tags(trim($_POST['FormFieldName']));
 }

 $ArrayFoundValues = array();
 if ((!empty($sTableName)) && (!empty($sFieldName)) && (!empty($sFormFieldName)))
 {
     $CustomFieldCondition = '';
     if (strtolower($sTableName) == 'customfieldsvalues')
     {
         // Extract the custom field name
         $CFFieldName = substr($sFormFieldName, 2);

         // Get it's ID
         $CustomFieldID = getCustomFieldID($DbCon, $CFFieldName);
         if ($CustomFieldID > 0)
         {
             $CustomFieldCondition = " AND CustomFieldID = $CustomFieldID";
         }
     }

     $DbResult = $DbCon->query("SELECT $sFieldName FROM $sTableName WHERE $sFieldName LIKE\"$sEnteredValue\" $CustomFieldCondition
                               GROUP BY $sFieldName ORDER BY $sFieldName");

     if (!DB::isError($DbResult))
     {
         // There are values found
         if ($DbResult->numRows() > 0)
         {
             while ($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayFoundValues[] = $Record[$sFieldName];
             }
         }
     }
 }

 // Release the connection to the database
 dbDisconnection($DbCon);

 // We send the response to the browser
 header('Content-type: text/html; charset='.strtolower($CONF_CHARSET));
 echo '<ul class="FormFieldAutoCompletionList">';

 foreach($ArrayFoundValues as $i => $CurrentName)
 {
     echo "<li>$CurrentName</li>";
 }

 echo '</ul>';
?>
