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
 * Support module : allow a supporter to delete a workgroup from a table (view). The supporter must be logged
 * to delete the workgroup.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-03-09 : when a workgroup is deleted, the alias of referent of the workgroup is deleted too and alias
 *                    containing e-mail address of the workgroup is removed.
 *     - 2016-10-12 : taken into account Bcc and load some configuration variables from database
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

 if (!empty($_GET["Return"]))
 {
     $BackPage = (string)strip_tags($_GET["Return"]);
     if ((!empty($_GET["RCr"])) && (!empty($_GET["RId"])))
     {
         $CryptedReturnID = (string)strip_tags($_GET["RCr"]);
         $ReturnId = (string)strip_tags($_GET["RId"]);
     }
     else
     {
         $BackPage = "Cooperation.php";
     }
 }
 else
 {
     $BackPage = "Cooperation.php";
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

         // We get infos about the workgroup
         $RecordWorkGroup = getTableRecordInfos($DbCon, "WorkGroups", $Id);

         // The supporter must be allowed to access to workgroups list
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         $AccessRules = $CONF_ACCESS_APPL_PAGES[FCT_WORKGROUP];

         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_UPDATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }
         elseif ((isset($AccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
         {
             // Partial read mode
             $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
         }

         $bCanDelete = FALSE;
         if (isset($GLOBALS['CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION["SupportMemberStateID"]]))
         {
             switch($GLOBALS['CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION["SupportMemberStateID"]])
             {
                 case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ALL:
                     // To delete a workgroup, the supporter must have write access
                     if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
                     {
                         $bCanDelete = TRUE;
                     }
                     break;
             }
         }

         if ($bCanDelete)
         {
             // We delete the selected workgroup
             if (dbDeleteWorkGroup($DbCon, $Id))
             {
                 // Log event
                 logEvent($DbCon, EVT_WORKGROUP, EVT_SERV_WORKGROUP, EVT_ACT_DELETE, $_SESSION['SupportMemberID'], $Id,
                          array('WorkGroupDetails' => $RecordWorkGroup));

                 // The workgroup is deleted
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_WORKGROUP_DELETED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = $CONF_ROOT_DIRECTORY."Support/Cooperation/$BackPage?Cr=$CryptedReturnID&Id=$ReturnId"; // For the redirection

                 // Check if a notification must be sent
                 if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted']))
                     && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][Template]))
                    )
                 {
                     $EmailSubject = $LANG_SYSTEM_EMAIL_WORKGROUP_REGISTRATION_EMAIL_UPDATED_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_WORKGROUP]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_WORKGROUP].$EmailSubject;
                     }

                     // We define the content of the mail
                     $TemplateToUse = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{WorkGroupEmail}", "{WorkGroupName}"
                                                     ),
                                                array(
                                                      $RecordWorkGroup['WorkGroupEmail'], $RecordWorkGroup['WorkGroupName']
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList = array();
                     if (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][To]))
                     {
                         $MailingList["to"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][To];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][Cc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][Cc]))
                        )
                     {
                         $MailingList["cc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][Cc];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][Bcc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][Bcc]))
                        )
                     {
                         $MailingList["bcc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['WorkGroupDeleted'][Bcc];
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

                 // Delete the alias in the "Alias" table if exists
                 // First, we search the alias in relation with the deleted workgroup
                 $ArrayAlias = dbSearchAlias($DbCon, array('AliasMailingList' => '%'.$RecordWorkGroup['WorkGroupEmail'].'%'),
                                             'AliasID', 1, 0);

                 if ((isset($ArrayAlias['AliasID'])) && (count($ArrayAlias['AliasID']) > 0))
                 {
                     foreach($ArrayAlias['AliasID'] as $a => $CurrentAliasID)
                     {
                         if ($ArrayAlias['AliasMailingList'][$a] == $RecordWorkGroup['WorkGroupEmail'])
                         {
                             // The mailing-list of the alias contains just the e-mail address of the deleted workgroup :
                             // we can delete the alias
                             if (dbDeleteAlias($DbCon, $CurrentAliasID))
                             {
                                 // Log event
                                 $RecordAlias = array(
                                                      'AliasID' => $CurrentAliasID,
                                                      'AliasName' => $ArrayAlias['AliasName'][$a],
                                                      'AliasDescription' => $ArrayAlias['AliasDescription'][$a],
                                                      'AliasMailingList' => $ArrayAlias['AliasMailingList'][$a]
                                                     );

                                 logEvent($DbCon, EVT_MESSAGE, EVT_SERV_ALIAS, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                          $CurrentAliasID, array('AliasDetails' => $RecordAlias));

                                 unset($RecordAlias);
                             }
                         }
                         else
                         {
                             // The mailing-list of the alias contains the e-mail address of the deleted workgroup but other
                             // e-mail addresses : we just update the mailing-list of the alias
                             $ArrayMails = array();
                             $ArrayTmp = explode(',', $ArrayAlias['AliasMailingList'][$a]);
                             foreach($ArrayTmp as $t => $CurrentMail)
                             {
                                 $CurrentMail = trim($CurrentMail);
                                 if ($CurrentMail != $RecordWorkGroup['WorkGroupEmail'])
                                 {
                                     // We keep this e-mail address
                                     $ArrayMails[] = $CurrentMail;
                                 }
                             }

                             if (!empty($ArrayMails))
                             {
                                 // We update the mailing-list of the alias
                                 $UpdatedAliasID = dbUpdateAlias($DbCon, $CurrentAliasID, $ArrayAlias['AliasName'][$a],
                                                                 implode(', ', $ArrayMails), NULL);
                                 if ($UpdatedAliasID != 0)
                                 {
                                     // Log event
                                     logEvent($DbCon, EVT_MESSAGE, EVT_SERV_ALIAS, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                                              $UpdatedAliasID);
                                 }
                             }

                             unset($ArrayTmp, $ArrayMails);
                         }
                     }
                 }

                 // Next, we search the alias in relation with the referent of the deleted workgroup :
                 // we must delete the alias too
                 $AliasName = $LANG_WORKGROUP_REGISTRATION_REFERENT.' '.$RecordWorkGroup['WorkGroupName'];
                 $ArrayAlias = dbSearchAlias($DbCon, array('AliasName' => $AliasName), 'AliasID', 1, 0);
                 if ((isset($ArrayAlias['AliasID'])) && (count($ArrayAlias['AliasID']) > 0))
                 {
                     // Alias of referent of the workgroup found : we delete it
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
             }
             else
             {
                 // ERROR : the workgroup isn't deleted
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_DELETE_WORKGROUP;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $CONF_ROOT_DIRECTORY."Support/Cooperation/$BackPage"; // For the redirection
             }
         }
         else
         {
             // ERROR : the workgroup isn't deleted because the logged supporter isn't allowed
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_DELETE_WORKGROUP_NOT_ALLOWED;
             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = $CONF_ROOT_DIRECTORY."Support/Cooperation/$BackPage"; // For the redirection
         }
     }
     else
     {
         // ERROR : the workgroup ID is wrong
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_WRONG_WORKGROUP_ID;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = $CONF_ROOT_DIRECTORY."Support/Cooperation/$BackPage"; // For the redirection
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

 // Release the connection to the database
 dbDisconnection($DbCon);

 //################################ END FORM PROCESSING ##########################

 // Redirection to the view of workgroups of the logged supporter
 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      'WhitePage',
                      "Redirection('$UrlParameters', $CONF_TIME_LAG)"
                     );

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
     // Error because the ID of the workgroup ID and the crypted ID don't match
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_WRONG_WORKGROUP_ID, 'ErrorMsg');
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