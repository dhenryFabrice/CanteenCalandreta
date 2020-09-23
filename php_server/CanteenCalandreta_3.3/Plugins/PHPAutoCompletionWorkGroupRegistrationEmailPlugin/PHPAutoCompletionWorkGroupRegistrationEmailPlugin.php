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
 * PHP plugin autocompletion module : add the autocompletion function for the sEmail field of the
 * workgroup registration form
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-03-11 : change the search "$sEnteredValue%" to "%$sEnteredValue%";
 *     - 2016-06-20 : taken into account $CONF_CHARSET
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2015-10-21
 */


 // Include Config.php because of the name of the session
 require_once dirname(__FILE__).'/../../Common/DefinedConst.php';
 require_once dirname(__FILE__).'/../../Common/Config.php';
 require_once dirname(__FILE__).'/../../Common/DbLibrary.php';

 session_start();

 // Connection to the database
 $DbCon = dbConnection();

 // Get the value
 $sCompareValue = '';
 if (isset($_POST['debut'])) {
     $sEnteredValue = $_POST['debut'];
     $sEnteredValue = strip_tags(strtolower(trim($sEnteredValue)));
     $sCompareValue = $sEnteredValue;
     $sEnteredValue = "%$sEnteredValue%";
 } else {
     $sEnteredValue = "";
 }

 // Get the name of the form field in which we have entered the value
 $sFormFieldName = '';
 if (isset($_POST['FormFieldName'])) {
    $sFormFieldName = strip_tags(trim($_POST['FormFieldName']));
 }

 $ArrayFoundValues = array();
 if (!empty($sFormFieldName))
 {
     if (isset($CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS[$_SESSION['SupportMemberStateID']]))
     {
         switch($CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS[$_SESSION['SupportMemberStateID']])
         {
             case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ALL:
                 // We get all e-mail addresses of activated families
                 $DbResult = $DbCon->query("SELECT f.FamilyMainEmail, f.FamilySecondEmail, s.SupportMemberEmail,
                                            wgr.WorkGroupRegistrationEmail
                                            FROM Families f INNER JOIN SupportMembers s ON (f.FamilyID = s.FamilyID)
                                            LEFT JOIN WorkGroupRegistrations wgr ON (wgr.SupportMemberID = s.SupportMemberID)
                                            WHERE f.FamilyDesactivationDate IS NULL AND s.SupportMemberActivated > 0
                                            AND ((FamilyMainEmail LIKE \"$sEnteredValue\") OR (FamilySecondEmail LIKE \"$sEnteredValue\")
                                            OR (SupportMemberEmail LIKE \"$sEnteredValue\")
                                            OR (WorkGroupRegistrationEmail LIKE \"$sEnteredValue\"))");

                 break;

             default:
                 // We filter only on e-mails of the family
                 $DbResult = $DbCon->query("SELECT f.FamilyMainEmail, f.FamilySecondEmail, s.SupportMemberEmail,
                                            wgr.WorkGroupRegistrationEmail
                                            FROM Families f INNER JOIN SupportMembers s ON (f.FamilyID = s.FamilyID)
                                            LEFT JOIN WorkGroupRegistrations wgr ON (wgr.SupportMemberID = s.SupportMemberID)
                                            WHERE s.SupportMemberID = ".$_SESSION['SupportMemberID']
                                            ." AND f.FamilyDesactivationDate IS NULL AND s.SupportMemberActivated > 0");

                 break;

         }

         if (!DB::isError($DbResult))
         {
             // There are values found
             if ($DbResult->numRows() > 0)
             {
                 while ($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                 {
                     if (stripos(strToLower($Record['FamilyMainEmail']), $sCompareValue) !== FALSE)
                     {
                         $ArrayFoundValues[] = $Record['FamilyMainEmail'];
                     }

                     if (stripos(strToLower($Record['SupportMemberEmail']), $sCompareValue) !== FALSE)
                     {
                         $ArrayFoundValues[] = $Record['SupportMemberEmail'];
                     }

                     if ((!empty($Record['FamilySecondEmail']))
                         && (stripos(strToLower($Record['FamilySecondEmail']), $sCompareValue) !== FALSE))
                     {
                         $ArrayFoundValues[] = $Record['FamilySecondEmail'];
                     }

                     if ((!empty($Record['WorkGroupRegistrationEmail']))
                         && (stripos(strToLower($Record['WorkGroupRegistrationEmail']), $sCompareValue) !== FALSE))
                     {
                         $ArrayFoundValues[] = $Record['WorkGroupRegistrationEmail'];
                     }
                 }
             }
         }

         // Keep only one same e-mail address
         $ArrayFoundValues = array_unique($ArrayFoundValues);
         sort($ArrayFoundValues, SORT_STRING);
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
