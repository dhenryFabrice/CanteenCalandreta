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
 * Support module : delete a workgroup registration (if allowed by user rights). The supporter must be logged to
 * delete the workgroup registration.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-03-08 : when a workgroup registration is deleted with "referent" option checked,
 *                    the e-mail address is remove from the alias of referent if it exists.
 *     - 2016-10-12 : taken into account Bcc and load some configuration variables from database
 *
 * @since 2015-10-19
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted workgroup registration ID
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

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // the ID and the md5 crypted ID must be equal
     if (($Id != '') && (md5($Id) == $CryptedID))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS'));

         // We get the workgroup reference and the family reference
         $RecordWorkGroupRegistration = getTableRecordInfos($DbCon, "WorkGroupRegistrations", $Id);
         $FamilyID = 0;
         $WorkGroupID = 0;
         $SupportMemberID = 0;
         if (!empty($RecordWorkGroupRegistration))
         {
             $FamilyID = $RecordWorkGroupRegistration["FamilyID"];
             $WorkGroupID = $RecordWorkGroupRegistration["WorkGroupID"];
             $SupportMemberID = $RecordWorkGroupRegistration["SupportMemberID"];
         }

         // Get the FamilyID of the logged supporter
         $LoggedFamilyID = $_SESSION['FamilyID'];

         $bCanDelete = FALSE;
         if (isset($CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS[$_SESSION['SupportMemberStateID']]))
         {
             /* The user can delete his workgroup registration if :
              * - family of logged supporter is the family concerned by the workgroup registration
              * - the user is a referent
              * - the user is an admin
              */
             $cUserAccess = FCT_ACT_NO_RIGHTS;
             if ((isset($CONF_ACCESS_APPL_PAGES[FCT_WORKGROUP_REGISTRATION][FCT_ACT_UPDATE]))
                 && (in_array($_SESSION["SupportMemberStateID"], $CONF_ACCESS_APPL_PAGES[FCT_WORKGROUP_REGISTRATION][FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }

             switch($cUserAccess)
             {
                 case FCT_ACT_UPDATE:
                     switch($CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS[$_SESSION['SupportMemberStateID']])
                     {
                         case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ALL:
                             $bCanDelete = TRUE;
                             break;

                         case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                             // The used is associated to the family of the workgroup registration
                             if ($LoggedFamilyID == $FamilyID)
                             {
                                 $bCanDelete = TRUE;
                             }
                             else
                             {
                                 // We get referents of the workgroup
                                 $ArrayParams = array(
                                                      'WorkGroupID' => $WorkGroupID,
                                                      'WorkGroupRegistrationReferent' => array(1)
                                                     );

                                 $ArrayReferents = dbSearchWorkGroupRegistration($DbCon, $ArrayParams, "WorkGroupRegistrationLastname",
                                                                                 1, 0);

                                 if ((isset($ArrayReferents['WorkGroupRegistrationID']))
                                     && (in_array($_SESSION['SupportMemberEmail'], $ArrayReferents['WorkGroupRegistrationEmail'])))
                                 {
                                     // The user is a referent thanks to his e-mail address
                                     $bCanDelete = TRUE;
                                 }
                             }
                             break;
                     }
                     break;
             }
         }

         // We delete the selected workgroup registration
         if ($bCanDelete)
         {
             // Yes, we can delete this workgroup registration
             if (dbDeleteWorkGroupRegistration($DbCon, $Id))
             {
                 // Log event
                 logEvent($DbCon, EVT_WORKGROUP, EVT_SERV_WORKGROUP_REGISTRATION, EVT_ACT_DELETE, $_SESSION['SupportMemberID'], $Id,
                          array('WorkGroupRegistrationDetails' => $RecordWorkGroupRegistration));

                 // The workgroup registration is deleted
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_WORKGROUP_REGISTRATION_DELETED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($WorkGroupID)."&Id=$WorkGroupID"; // For the redirection

                 // Check if a notification must be sent
                 if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated']))
                     && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][Template])))
                 {
                     $EmailSubject = $LANG_SYSTEM_EMAIL_WORKGROUP_REGISTRATION_EMAIL_UPDATED_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_WORKGROUP_REGISTRATION]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_WORKGROUP_REGISTRATION].$EmailSubject;
                     }

                     $RecordWorkGroup = getTableRecordInfos($DbCon, 'WorkGroups', $WorkGroupID);

                     // Type of change
                     $sWorkGroupRegistrationChangedEmail = $RecordWorkGroupRegistration["WorkGroupRegistrationEmail"]
                                                           ." $LANG_SYSTEM_EMAIL_WORKGROUP_REGISTRATION_EMAIL_UPDATED_EMAIL_REMOVED";

                     $WorkGroupUrl = $CONF_URL_SUPPORT."Cooperation/UpdateWorkGroup.php?Cr=".md5($WorkGroupID)."&amp;Id=$WorkGroupID";
                     $WorkGroupLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     // We define the content of the mail
                     $TemplateToUse = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{WorkGroupRegistrationLastname}", "{WorkGroupRegistrationFirstname}",
                                                      "{WorkGroupRegistrationChangedEmail}", "{WorkGroupEmail}", "{WorkGroupName}",
                                                      "{WorkGroupUrl}", "{WorkGroupLinkTip}"
                                                     ),
                                                array(
                                                      $RecordWorkGroupRegistration["WorkGroupRegistrationLastname"],
                                                      $RecordWorkGroupRegistration["WorkGroupRegistrationFirstname"],
                                                      $sWorkGroupRegistrationChangedEmail, $RecordWorkGroup['WorkGroupEmail'],
                                                      $RecordWorkGroup['WorkGroupName'], $WorkGroupUrl, $WorkGroupLinkTip
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList = array();
                     if (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][To]))
                     {
                         $MailingList["to"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][To];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][Cc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][Cc]))
                        )
                     {
                         $MailingList["cc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][Cc];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][Bcc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][Bcc]))
                        )
                     {
                         $MailingList["bcc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][Bcc];
                     }

                     // DEBUG MODE
                     if ($GLOBALS["CONF_MODE_DEBUG"])
                     {
                         if (!in_array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS, $MailingList["to"]))
                         {
                             // Without this test, there is a server mail error...
                             $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
                         }
                     }

                     // We send the e-mail
                     $bIsEmailSent = sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                 }

                 // We check if we must remove the e-mail address of the user in the alias of referent if he wes a referent
                 if ($RecordWorkGroupRegistration['WorkGroupRegistrationReferent'] == 1)
                 {
                     // We get the alias name
                     $RecordWorkGroup = getTableRecordInfos($DbCon, "WorkGroups", $WorkGroupID);
                     if (!empty($RecordWorkGroup))
                     {
                         $AliasName = $LANG_WORKGROUP_REGISTRATION_REFERENT.' '.$RecordWorkGroup['WorkGroupName'];
                         $ArrayAlias = dbSearchAlias($DbCon, array('AliasName' => $AliasName), 'AliasID', 1, 0);
                         if ((isset($ArrayAlias['AliasID'])) && (count($ArrayAlias['AliasID']) > 0))
                         {
                             // Alias found : we try to remove the e-mail address of the deleted workgroup registration
                             $ArrayMails = array();
                             $ArrayTmp = explode(',', $ArrayAlias['AliasMailingList'][0]);
                             foreach($ArrayTmp as $t => $CurrentMail)
                             {
                                 $CurrentMail = trim($CurrentMail);
                                 if ($CurrentMail != $RecordWorkGroupRegistration['WorkGroupRegistrationEmail'])
                                 {
                                     // We keep this e-mail address
                                     $ArrayMails[] = $CurrentMail;
                                 }
                             }

                             if (!empty($ArrayMails))
                             {
                                 // We update the mailing-list of the alias
                                 $UpdatedAliasID = dbUpdateAlias($DbCon, $ArrayAlias['AliasID'][0], $ArrayAlias['AliasName'][0],
                                                                 implode(', ', $ArrayMails), NULL);
                                 if ($UpdatedAliasID != 0)
                                 {
                                     // Log event
                                     logEvent($DbCon, EVT_MESSAGE, EVT_SERV_ALIAS, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                                              $UpdatedAliasID);
                                 }
                             }
                             else
                             {
                                 // No more e-mail address in the alias : we must delete it
                                 if (dbDeleteAlias($DbCon, $ArrayAlias['AliasID'][0]))
                                 {
                                     // Log event
                                     $RecordAlias = array(
                                                          'AliasID' => $ArrayAlias['AliasID'][0],
                                                          'AliasName' => $ArrayAlias['AliasName'][0],
                                                          'AliasDescription' => $ArrayAlias['AliasDescription'][0],
                                                          'AliasMailingList' => $ArrayAlias['AliasMailingList'][0]
                                                         );

                                     logEvent($DbCon, EVT_MESSAGE, EVT_SERV_ALIAS, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                              $ArrayAlias['AliasID'][0], array('AliasDetails' => $RecordAlias));

                                     unset($RecordAlias);
                                 }
                             }

                             unset($ArrayTmp, $ArrayMails);
                         }
                     }
                 }
             }
             else
             {
                 // ERROR : the workgroup registration isn't deleted
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_DELETE_WORKGROUP_REGISTRATION;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "Cr=".md5($WorkGroupID)."&Id=$WorkGroupID"; // For the redirection
             }
         }
         else
         {
             // Error : the user isn't allowed to delete the workgroup registration (because of wrong rights)
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_NOT_ALLOWED_DELETE_WORKGROUP_REGISTRATION;
             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = "Cr=".md5($WorkGroupID)."&Id=$WorkGroupID"; // For the redirection
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the workgroup registration ID is wrong
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_WRONG_WORKGROUP_REGISTRATION_ID;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = ""; // For the redirection
     }
 }
 else
 {
     // ERROR : the supporter isn't logged
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = ""; // For the redirection
 }

 if ($bIsEmailSent)
 {
     // A notification is sent
     $ConfirmationSentence .= '&nbsp;'.generateStyledPicture($CONF_NOTIFICATION_SENT_ICON);
 }
 //################################ END FORM PROCESSING ##########################

 if ($UrlParameters == '')
 {
     // No redirection
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen'
                               ),
                          array('../Verifications.js'),
                          'WhitePage'
                         );
 }
 else
 {
     // Redirection to the details of the workgroup
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
 }

 // Content of the web page
 openArea('id="content"');

 // the ID and the md5 crypted ID must be equal
 if (($Id != '') && (md5($Id) == $CryptedID))
 {
     openFrame($ConfirmationCaption);
     displayStyledText($ConfirmationSentence, $ConfirmationStyle);
     closeFrame();
 }
 else
 {
     // Error because the ID of the workgroup registration ID and the crypted ID don't match
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_WRONG_WORKGROUP_REGISTRATION_ID, 'ErrorMsg');
     closeFrame();
 }

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