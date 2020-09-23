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
 * Support module : process the update of a workgroup. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-03-09 : when a workgroup is updated, the alias in relation with the workgroup
 *                    are updated too.
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2015-10-13
 */

 // Include the graphic primitives library
  require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted workgroup ID
 // Crypted ID
 if (!empty($_GET["Cr"]))
 {
     $CryptedID = (string)strip_tags($_GET["Cr"]);
 }
 else
 {
     $CryptedID = "";
 }

 // No-crypted ID
 if (!empty($_GET["Id"]))
 {
     $Id = (string)strip_tags($_GET["Id"]);
 }
 else
 {
     $Id = "";
 }

 //################################ FORM PROCESSING ##########################
 $bIsEmailSent = FALSE;

 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS'));

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We identify the workgroup
         if (isExistingWorkGroup($DbCon, $Id))
         {
             // The workgroup exists
             $WorkGroupID = $Id;
         }
         else
         {
             // ERROR : the workgroup doesn't exist
             $ContinueProcess = FALSE;
         }

         $Name = strip_tags($_POST["sWorkGroupName"]);
         if (empty($Name))
         {
             $ContinueProcess = FALSE;
         }

         $Description = strip_tags($_POST["sWorkGroupDescription"]);

         $Email = strip_tags($_POST["sWorkGroupEmail"]);
         if (!empty($Email))
         {
             if (!isValideEmailAddress($Email))
             {
                 // Wrong e-mail
                 $ContinueProcess = FALSE;
             }
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // We can update the workgroup with the new values
             // Get the old values
             $RecordOldWorkGroup = getTableRecordInfos($DbCon, "WorkGroups", $WorkGroupID);

             $WorkGroupID = dbUpdateWorkGroup($DbCon, $WorkGroupID, $Name, $Description, $Email);
             if ($WorkGroupID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_WORKGROUP, EVT_SERV_WORKGROUP, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $WorkGroupID);

                 // The workgroup is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_WORKGROUP_UPDATED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($WorkGroupID)."&Id=$WorkGroupID"; // For the redirection

                 // We check if the e-mail address of the workgroup changed
                 if ($Email != $RecordOldWorkGroup['WorkGroupEmail'])
                 {
                     // The e-mail address changed : we must update the alias of the workgroup
                     // First, we get alias with the old e-mail address of the workgroup
                     $ArrayAlias = dbSearchAlias($DbCon, array('AliasMailingList' => '%'.$RecordOldWorkGroup['WorkGroupEmail'].'%'),
                                                 'AliasID', 1, 0);

                     if ((isset($ArrayAlias['AliasID'])) && (count($ArrayAlias['AliasID']) > 0))
                     {
                         foreach($ArrayAlias['AliasID'] as $a => $CurrentAliasID)
                         {
                             // Replace the old e-mail address of the workgroup by the new e-mail address
                             $ArrayAlias['AliasMailingList'][$a] = str_replace(array($RecordOldWorkGroup['WorkGroupEmail']),
                                                                               array($Email), $ArrayAlias['AliasMailingList'][$a]);

                             // We update the mailing-list of the alias
                             $UpdatedAliasID = dbUpdateAlias($DbCon, $CurrentAliasID, $Name, $ArrayAlias['AliasMailingList'][$a],
                                                             NULL);
                             if ($UpdatedAliasID != 0)
                             {
                                 // Log event
                                 logEvent($DbCon, EVT_MESSAGE, EVT_SERV_ALIAS, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                                          $UpdatedAliasID);
                             }
                         }
                     }
                 }
             }
             else
             {
                 // The workgroup can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_WORKGROUP;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($Name))
             {
                 // The name is empty
                 $ConfirmationSentence = $LANG_ERROR_WORKGROUP_NAME;
             }
             elseif ((!empty($Email)) && (!isValideEmailAddress($Email)))
             {
                 // The e-mail is wrong
                 $ConfirmationSentence = $LANG_ERROR_WRONG_WORKGROUP_EMAIL;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = $QUERY_STRING; // For the redirection
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the supporter isn't logged
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = $QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the UpdateWorkGroup.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = $QUERY_STRING; // For the redirection
 }

 if ($bIsEmailSent)
 {
     // A notification is sent
     $ConfirmationSentence .= '&nbsp;'.generateStyledPicture($CONF_NOTIFICATION_SENT_ICON);
 }

 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      'WhitePage',
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/UpdateWorkGroup.php?$UrlParameters', $CONF_TIME_LAG)"
                     );

 // Content of the web page
 openArea('id="content"');

 openFrame($ConfirmationCaption);
 displayStyledText($ConfirmationSentence, $ConfirmationStyle);
 closeFrame();

 // To measure the execution script time
 if ($CONF_DISPLAY_EXECUTION_TIME_SCRIPT)
 {
     openParagraph('InfoMsg');
     initEndTime();
     displayExecutionScriptTime('ExecutionTime');
     closeParagraph();
 }

 // Close the <div> "content"
 closeArea();

 closeGraphicInterface();
?>