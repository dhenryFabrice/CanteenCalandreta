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
 * Support module : delete an event registration (if allowed by user rights and delays). The supporter must be logged to
 * delete the event registration.
 *
 * @author Christophe Javouhey
 * @version 3.1
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2018-02-12 : taken into account "Inhibition" parameter of $CONF_COOP_EVENT_NOTIFICATIONS
 *
 * @since 2013-04-23
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted event registration ID
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

         // We get the event reference and the family reference
         $RecordEventRegistration = getTableRecordInfos($DbCon, "EventRegistrations", $Id);
         $FamilyID = 0;
         $EventID = 0;
         $SupportMemberID = 0;
         if (!empty($RecordEventRegistration))
         {
             $FamilyID = $RecordEventRegistration["FamilyID"];
             $EventID = $RecordEventRegistration["EventID"];
             $SupportMemberID = $RecordEventRegistration["SupportMemberID"];
         }

         // Get the FamilyID of the logged supporter
         $LoggedFamilyID = $_SESSION['FamilyID'];

         // We check if the user is allowed to delete this event registration
         if (isExistingEvent($DbCon, $EventID))
         {
             $RecordEvent = getTableRecordInfos($DbCon, 'Events', $EventID);
             $CurrentSchoolYear = getSchoolYear($RecordEvent["EventStartDate"]);
             $iRegistrationDelay = $RecordEvent["EventRegistrationDelay"];
             $iNbMaxRegistrations = $RecordEvent["EventMaxParticipants"];
         }
         else
         {
             $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));
             $iRegistrationDelay = $CONF_COOP_EVENT_DEFAULT_REGISTRATION_DELAY;
             $iNbMaxRegistrations = $CONF_COOP_EVENT_DEFAULT_MAX_FAMILIES;
         }

         $bCanDelete = FALSE;
         $bCheckMinNbRegistrations = FALSE;
         if (in_array($_SESSION['SupportMemberStateID'], $CONF_COOP_EVENT_DELAYS_RESTRICTIONS))
         {
             $bCheckMinNbRegistrations = TRUE;

             /* The user can delete his event registration if :
              * - family of logged supporter is the family concerned by the event registration
              * - delay OK,
              * - event not closed
              * - cooperation OK
              */
             if (($LoggedFamilyID == $FamilyID) && (!isEventClosed($DbCon, $EventID))
                 && (dbFamilyCoopContribution($DbCon, $FamilyID, $CurrentSchoolYear))
                 && (getNbDaysBetween2Dates(strtotime(date('Y-m-d')),
                                            strtotime($RecordEvent["EventStartDate"]), FALSE) > $iRegistrationDelay))
             {
                 $bCanDelete = TRUE;
             }
         }
         else
         {
             $bCanDelete = TRUE;
         }

         // We delete the selected event registration
         if ($bCanDelete)
         {
             // Yes, we can delete this event registration
             if (dbDeleteEventRegistration($DbCon, $Id))
             {
                 // Log event
                 logEvent($DbCon, EVT_EVENT, EVT_SERV_EVENT_REGISTRATION, EVT_ACT_DELETE, $_SESSION['SupportMemberID'], $Id,
                          array('EventRegistrationDetails' => $RecordEventRegistration));

                 // The event registration is deleted
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_EVENT_REGISTRATION_DELETED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($EventID)."&Id=$EventID"; // For the redirection

                 // Check if a notification must be sent because the event registration is deleted by another person than
                 // the family itself
                 $bIsEmailSentFamily = FALSE;
                 $bCanSendNotification = FALSE;

                 if (($LoggedFamilyID != $FamilyID) && (isset($CONF_COOP_EVENT_NOTIFICATIONS['FamilyUnregisreredEvent']))
                     && (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilyUnregisreredEvent'][Template])))
                 {
                     $bCanSendNotification = TRUE;

                     // We check if there is an inhibition
                     if ((isset($CONF_COOP_EVENT_NOTIFICATIONS['FamilyUnregisreredEvent'][Inhibition]))
                         && (in_array($RecordEvent['EventTypeID'], $CONF_COOP_EVENT_NOTIFICATIONS['FamilyUnregisreredEvent'][Inhibition])))
                     {
                         // No notification for this event type
                         $bCanSendNotification = FALSE;
                     }
                 }

                 // We can send a notification ?
                 if ($bCanSendNotification)
                 {
                     // Yes !
                     // The registration isn't deleted by the family itself
                     $EmailSubject = $LANG_DELETED_EVENT_REGISTRATION_EMAIL_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                     }

                     $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($EventID)."&amp;Id=$EventID";
                     $EventLink = stripslashes($RecordEvent['EventTitle']);
                     $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     $RecordTown = getTableRecordInfos($DbCon, 'Towns', $RecordEvent['TownID']);
                     $TownName = $RecordTown['TownName'];
                     $TownCode = $RecordTown['TownCode'];
                     unset($RecordTown);

                     // We define the content of the mail
                     $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['FamilyUnregisreredEvent'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{LANG_EVENT}", "{EventUrl}", "{EventLink}", "{EventLinkTip}", "{LANG_TOWN}",
                                                      "{TownName}", "{TownCode}", "{LANG_EVENT_START_DATE}", "{EventStartDate}"
                                                     ),
                                                array(
                                                      $LANG_EVENT, $EventUrl, $EventLink, $EventLinkTip, $LANG_TOWN, $TownName, $TownCode,
                                                      $LANG_EVENT_START_DATE,
                                                      date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordEvent['EventStartDate']))
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList["to"] = array();
                     $MailingList["bcc"] = array();
                     foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilyUnregisreredEvent'][To] as $rt => $RecipientType)
                     {
                         $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType, $FamilyID);
                         if (!empty($ArrayRecipients))
                         {
                             $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayRecipients);
                         }
                     }

                     if (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilyUnregisreredEvent'][Cc]))
                     {
                         $MailingList["cc"] = array();
                         foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilyUnregisreredEvent'][Cc] as $rt => $RecipientType)
                         {
                             $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType, $FamilyID);
                             if (!empty($ArrayRecipients))
                             {
                                $MailingList["cc"] = array_merge($MailingList["cc"], $ArrayRecipients);
                             }
                         }
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

                     // We can send the e-mail
                     $bIsEmailSentFamily = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                 }

                 // Check if a notification must be sent because too low registrations for the event
                 $bIsEmailSentMinRegistrations = FALSE;
                 $bIsEmailSentMinRegistrationsAuthor = FALSE;
                 if (($bCheckMinNbRegistrations) && (isset($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEvent']))
                     && (!empty($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEvent'][Template])))
                 {
                     // We get the number of registered families for this event
                     $iNbRegistrations = getNbEventRegistrationTree($DbCon, $EventID);
                     $Ratio = round((double)$iNbRegistrations / (double)$iNbMaxRegistrations, 0);
                     if ($Ratio < $CONF_COOP_EVENT_MIN_REGISTRATION_RATE)
                     {
                         // 1) Too low registrations for this event : we must send a notification to families
                         $EmailSubject = $LANG_MIN_RATIO_EVENT_REGISTRATIONS_EMAIL_SUBJECT;

                         if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                         {
                             $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                         }

                         $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($EventID)."&amp;Id=$EventID";
                         $EventLink = stripslashes($RecordEvent['EventTitle']);
                         $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                         $RecordTown = getTableRecordInfos($DbCon, 'Towns', $RecordEvent['TownID']);
                         $TownName = $RecordTown['TownName'];
                         $TownCode = $RecordTown['TownCode'];
                         unset($RecordTown);

                         // We define the content of the mail
                         $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEvent'][Template];
                         $ReplaceInTemplate = array(
                                                    array(
                                                          "{LANG_EVENT}", "{EventUrl}", "{EventLink}", "{EventLinkTip}", "{LANG_TOWN}",
                                                          "{TownName}", "{TownCode}", "{LANG_EVENT_START_DATE}", "{EventStartDate}"
                                                         ),
                                                    array(
                                                          $LANG_EVENT, $EventUrl, $EventLink, $EventLinkTip, $LANG_TOWN, $TownName, $TownCode,
                                                          $LANG_EVENT_START_DATE,
                                                          date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordEvent['EventStartDate']))
                                                         )
                                                   );

                         // Get the recipients of the e-mail notification
                         $MailingList["to"] = array();
                         $MailingList["bcc"] = array();
                         foreach($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEvent'][To] as $rt => $RecipientType)
                         {
                             $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType, $FamilyID);
                             if (!empty($ArrayRecipients))
                             {
                                 $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayRecipients);
                             }
                         }

                         if (!empty($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEvent'][Cc]))
                         {
                             $MailingList["cc"] = array();
                             foreach($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEvent'][Cc] as $rt => $RecipientType)
                             {
                                 $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType, $FamilyID);
                                 if (!empty($ArrayRecipients))
                                 {
                                     $MailingList["cc"] = array_merge($MailingList["cc"], $ArrayRecipients);
                                 }
                             }
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

                         // We can send the e-mail
                         $bIsEmailSentMinRegistrations = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse,
                                                                   $ReplaceInTemplate);

                         // 2) Too low registrations for this event : we must send a notification to the author of the event too
                         if ((isset($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEventAuthor']))
                              && (!empty($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEventAuthor'][Template])))
                         {
                             $EmailSubject = $LANG_MIN_RATIO_EVENT_REGISTRATIONS_TO_AUTHOR_EMAIL_SUBJECT;

                             if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                             {
                                 $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                             }

                             // We define the content of the mail
                             $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEventAuthor'][Template];
                             $ReplaceInTemplate = array(
                                                        array(
                                                              "{LANG_EVENT}", "{EventUrl}", "{EventLink}", "{EventLinkTip}", "{LANG_TOWN}",
                                                              "{TownName}", "{TownCode}", "{LANG_EVENT_START_DATE}", "{EventStartDate}"
                                                             ),
                                                        array(
                                                              $LANG_EVENT, $EventUrl, $EventLink, $EventLinkTip, $LANG_TOWN, $TownName,
                                                              $TownCode, $LANG_EVENT_START_DATE,
                                                              date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordEvent['EventStartDate']))
                                                             )
                                                       );

                             // Get the recipients of the e-mail notification
                             $MailingList["to"] = array();
                             $MailingList["bcc"] = array();
                             foreach($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEventAuthor'][To] as $rt => $RecipientType)
                             {
                                 $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType);
                                 if (!empty($ArrayRecipients))
                                 {
                                     $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayRecipients);
                                 }
                             }

                             if (!empty($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEventAuthor'][Cc]))
                             {
                                 $MailingList["cc"] = array();
                                 foreach($CONF_COOP_EVENT_NOTIFICATIONS['MinRegistrationRatioEventAuthor'][Cc] as $rt => $RecipientType)
                                 {
                                     $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType);
                                     if (!empty($ArrayRecipients))
                                     {
                                         $MailingList["cc"] = array_merge($MailingList["cc"], $ArrayRecipients);
                                     }
                                 }
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

                             // We can send the e-mail
                             $bIsEmailSentMinRegistrationsAuthor = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse,
                                                                             $ReplaceInTemplate);
                         }
                     }
                 }

                 // A notification was sent with success?
                 if (($bIsEmailSentFamily) || ($bIsEmailSentMinRegistrations) || ($bIsEmailSentMinRegistrationsAuthor))
                 {
                     $bIsEmailSent = TRUE;
                 }
             }
             else
             {
                 // ERROR : the event registration isn't deleted
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_DELETE_EVENT_REGISTRATION;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "Cr=".md5($EventID)."&Id=$EventID"; // For the redirection
             }
         }
         else
         {
             // Error : the user isn't allowed to delete the event registration (because of the delay or wrong rights)
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_NOT_ALLOWED_DELETE_EVENT_REGISTRATION;
             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = "Cr=".md5($EventID)."&Id=$EventID"; // For the redirection
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the event registration ID is wrong
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_WRONG_EVENT_REGISTRATION_ID;
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
     // Redirection to the details of the event
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen'
                               ),
                          array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                          'WhitePage',
                          "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/UpdateEvent.php?$UrlParameters', $CONF_TIME_LAG)"
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
     // Error because the ID of the event registration ID and the crypted ID don't match
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_WRONG_EVENT_REGISTRATION_ID, 'ErrorMsg');
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