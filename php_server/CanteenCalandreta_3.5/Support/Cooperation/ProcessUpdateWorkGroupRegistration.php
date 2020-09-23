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
 * Support module : process the update of a workgroup registration. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-03-09 : when a workgroup registration is created with "referent" option checked,
 *                    an alias is created too or updated if it already exists
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2015-10-20
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

         // We identify the workgroup registration
         if (isExistingWorkGroupRegistration($DbCon, $Id))
         {
             // The workgroup registration exists
             $WorkGroupRegistrationID = $Id;
         }
         else
         {
             // ERROR : the workgroup registration doesn't exist
             $ContinueProcess = FALSE;
         }

         // We get the values entered by the user
         $WorkGroupID = $_POST["hidWorkGroupID"];
         if ($WorkGroupID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // We get the values entered by the user
         $sLastname = trim(strip_tags($_POST["sLastname"]));
         if (empty($sLastname))
         {
             // Error
             $ContinueProcess = FALSE;
         }

         $sFirstname = trim(strip_tags($_POST["sFirstname"]));
         if (empty($sFirstname))
         {
             // Error
             $ContinueProcess = FALSE;
         }

         $sEmail = trim(strip_tags($_POST["sEmail"]));
         if (!isValideEmailAddress($sEmail))
         {
             // Error
             $ContinueProcess = FALSE;
         }

         $SupportMemberID = strip_tags($_POST["hidSupportMemberID"]);
         if ($SupportMemberID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         $FamilyID = strip_tags($_POST["lFamilyID"]);

         $IsReferent = 0;
         if ((array_key_exists("chkWorkGroupRegistrationReferent", $_POST)) && (!empty($_POST['chkWorkGroupRegistrationReferent'])))
         {
             // The registration is for a referent of the workgroup
             $IsReferent = 1;
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // We get the previous values of the workgroup registration
             $RecordOldWorkGroupRegistration = getTableRecordInfos($DbCon, "WorkGroupRegistrations", $WorkGroupRegistrationID);

             // We can update the workgroup registration with the new values
             $WorkGroupRegistrationID = dbUpdateWorkGroupRegistration($DbCon, $WorkGroupRegistrationID, NULL, $WorkGroupID, NULL,
                                                                      $sLastname, $sFirstname, $sEmail, $IsReferent, $FamilyID);
             if ($WorkGroupRegistrationID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_WORKGROUP, EVT_SERV_WORKGROUP_REGISTRATION, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                          $WorkGroupRegistrationID);

                 // The workgroup registration is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_WORKGROUP_REGISTRATION;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($WorkGroupRegistrationID)."&Id=$WorkGroupRegistrationID"; // For the redirection

                 // Check if a notification must be sent (e-mail changed)
                 if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated']))
                     && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupRegistrationEmailUpdated'][Template]))
                     && ($RecordOldWorkGroupRegistration['WorkGroupRegistrationEmail'] != $sEmail)
                    )
                 {
                     $EmailSubject = $LANG_SYSTEM_EMAIL_WORKGROUP_REGISTRATION_EMAIL_UPDATED_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_WORKGROUP_REGISTRATION]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_WORKGROUP_REGISTRATION].$EmailSubject;
                     }

                     $RecordWorkGroup = getTableRecordInfos($DbCon, 'WorkGroups', $WorkGroupID);

                     // Type of change
                     $sWorkGroupRegistrationChangedEmail = $RecordOldWorkGroupRegistration['WorkGroupRegistrationEmail']
                                                          ." ".$LANG_SYSTEM_EMAIL_WORKGROUP_REGISTRATION_EMAIL_UPDATED_EMAIL_REPLACED_BY
                                                          ." $sEmail";

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
                                                      stripslashes($sLastname), stripslashes($sFirstname),
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

                 // Create an alias for the "referent" function of this workgroup or update the alias if it already exists
                 $RecordWorkGroup = getTableRecordInfos($DbCon, "WorkGroups", $WorkGroupID);
                 if ($IsReferent == 1)
                 {
                     // First, we check if the alias already exists : we get the name of the workgroup
                     if (!empty($RecordWorkGroup))
                     {
                         $AliasName = $LANG_WORKGROUP_REGISTRATION_REFERENT.' '.$RecordWorkGroup['WorkGroupName'];
                         $ArrayAlias = dbSearchAlias($DbCon, array('AliasName' => $AliasName), 'AliasID', 1, 0);
                         if ((isset($ArrayAlias['AliasID'])) && (count($ArrayAlias['AliasID']) > 0))
                         {
                             // An alias already exists : we just update its mailing-list
                             $OldAliasMailingList = $ArrayAlias['AliasMailingList'][0];

                             // We check if the e-mail address changed
                             if ($RecordOldWorkGroupRegistration['WorkGroupRegistrationEmail'] != $sEmail)
                             {
                                 // We try to remove the old e-mail address
                                 $ArrayMails = array();
                                 $ArrayTmp = explode(',', $ArrayAlias['AliasMailingList'][0]);
                                 foreach($ArrayTmp as $t => $CurrentMail)
                                 {
                                     $CurrentMail = trim($CurrentMail);
                                     if ($CurrentMail != $RecordOldWorkGroupRegistration['WorkGroupRegistrationEmail'])
                                     {
                                         // We keep this e-mail address
                                         $ArrayMails[] = $CurrentMail;
                                     }
                                 }

                                 $ArrayAlias['AliasMailingList'][0] = implode(', ', $ArrayMails);
                                 unset($ArrayTmp, $ArrayMails);
                             }

                             // We check if the e-mail address of the referent isn't in the mailing-list of the alias
                             if (stripos($ArrayAlias['AliasMailingList'][0], $sEmail) === FALSE)
                             {
                                 // The e-mail address of the referent isn't in the mailing-list of the alias :
                                 // we update the maiking-list of the alias
                                 if (!empty($ArrayAlias['AliasMailingList'][0]))
                                 {
                                     $ArrayAlias['AliasMailingList'][0] .= ", ";
                                 }

                                 $ArrayAlias['AliasMailingList'][0] .= $sEmail;
                             }

                             // We update the mailing-list of the alias has changed
                             if ($ArrayAlias['AliasMailingList'][0] != $OldAliasMailingList)
                             {
                                 $UpdatedAliasID = dbUpdateAlias($DbCon, $ArrayAlias['AliasID'][0], $ArrayAlias['AliasName'][0],
                                                                 $ArrayAlias['AliasMailingList'][0], NULL);
                                 if ($UpdatedAliasID != 0)
                                 {
                                     // Log event
                                     logEvent($DbCon, EVT_MESSAGE, EVT_SERV_ALIAS, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                                              $UpdatedAliasID);
                                 }
                             }
                         }
                         else
                         {
                             // The alias doesn't exist : we create it
                             $AliasDescription = "";
                             $AliasMailingList = $sEmail;

                             $AliasID = dbAddAlias($DbCon, $AliasName, $AliasMailingList, $AliasDescription);

                             if ($AliasID != 0)
                             {
                                 // Log event
                                 logEvent($DbCon, EVT_MESSAGE, EVT_SERV_ALIAS, EVT_ACT_COPY, $_SESSION['SupportMemberID'], $AliasID,
                                          array('WorkGroupDetails' => $RecordWorkGroup));
                             }
                         }
                     }
                 }
                 elseif ($RecordOldWorkGroupRegistration['WorkGroupRegistrationReferent'] != $IsReferent)
                 {
                     // Before, the user was a referent and now, not : we must remove his old e-mail address
                     // First, we check if the alias already exists : we get the name of the workgroup
                     if (!empty($RecordWorkGroup))
                     {
                         $AliasName = $LANG_WORKGROUP_REGISTRATION_REFERENT.' '.$RecordWorkGroup['WorkGroupName'];
                         $ArrayAlias = dbSearchAlias($DbCon, array('AliasName' => $AliasName), 'AliasID', 1, 0);
                         if ((isset($ArrayAlias['AliasID'])) && (count($ArrayAlias['AliasID']) > 0))
                         {
                             $ArrayMails = array();
                             $ArrayTmp = explode(',', $ArrayAlias['AliasMailingList'][0]);
                             foreach($ArrayTmp as $t => $CurrentMail)
                             {
                                 $CurrentMail = trim($CurrentMail);
                                 if ($CurrentMail != $RecordOldWorkGroupRegistration['WorkGroupRegistrationEmail'])
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
                 // The workgroup registration can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_EVENT;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($WorkGroupID))
             {
                 // The workgroup is empty
                 $ConfirmationSentence = $LANG_ERROR_WORKGROUP_REGISTRATION_WORKGROUP;
             }
             elseif (empty($sLastname))
             {
                 // The lastname is empty
                 $ConfirmationSentence = $LANG_ERROR_WORKGROUP_REGISTRATION_LASTNAME;
             }
             elseif (empty($sFirstname))
             {
                 // The firstname is empty
                 $ConfirmationSentence = $LANG_ERROR_WORKGROUP_REGISTRATION_FIRSTNAME;
             }
             elseif (!isValideEmailAddress($sEmail))
             {
                 // The e-mail isn't wrong
                 $ConfirmationSentence = $LANG_ERROR_WORKGROUP_REGISTRATION_EMAIL;
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
     // The supporter doesn't come from the UpdateWorkGroupRegistration.php page
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/UpdateWorkGroupRegistration.php?$UrlParameters', $CONF_TIME_LAG)"
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