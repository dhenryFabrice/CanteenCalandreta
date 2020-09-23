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
 * Support module : process the update of a swap of event registration. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2015-10-20 : use the error message $LANG_ERROR_UPDATE_EVENT_SWAPPED_REGISTRATION
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2013-05-23
 */

 // Include the graphic primitives library
  require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted event swapped registration ID
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
 $bHasChanged = FALSE;

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

         // We identify the event swapped registration
         if (isExistingEventSwappedRegistration($DbCon, $Id))
         {
             // The event swapped registration exists
             $EventSwappedRegistrationID = $Id;
         }
         else
         {
             // ERROR : the event swapped registration doesn't exist
             $ContinueProcess = FALSE;
         }

         // We get the values entered by the user
         $RequestorFamilyID = strip_tags($_POST["lRequestorFamilyID"]);
         if (empty($RequestorFamilyID))
         {
             // Error
             $ContinueProcess = FALSE;
         }

         $SupportMemberID = strip_tags($_POST["hidSupportMemberID"]);
         if (empty($SupportMemberID))
         {
             // Error
             $ContinueProcess = FALSE;
         }

         $AcceptorFamilyID = strip_tags($_POST["lAcceptorFamilyID"]);
         if (empty($AcceptorFamilyID))
         {
             // Error
             $ContinueProcess = FALSE;
         }

         $AcceptorEventID = strip_tags($_POST["lAcceptorEventID"]);
         if (empty($AcceptorEventID))
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // We check if the acceptor family is registered to the acceptor event
         $ArrayAcceptorEventRegistrations = dbSearchEventRegistration($DbCon, array('EventID' => $AcceptorEventID,
                                                                       'FamilyID' => $AcceptorFamilyID), "EventID", 1, 0);

         if ((isset($ArrayAcceptorEventRegistrations['EventRegistrationID']))
             && (!empty($ArrayAcceptorEventRegistrations['EventRegistrationID'])))
         {
             $bFamilyMatchToEvent = TRUE;
         }
         else
         {
             $bFamilyMatchToEvent = FALSE;

             // Error
             $ContinueProcess = FALSE;
         }

         // Get the current values of the swap of event registration
         $RecordOldEventSwappedRegistration = getTableRecordInfos($DbCon, "EventSwappedRegistrations", $EventSwappedRegistrationID);
         $RequestorEventID = $RecordOldEventSwappedRegistration['RequestorEventID'];

         $EventSwappedRegistrationClosingDate = '';
         $bRequestorMatch = FALSE;
         if (array_key_exists("lRequestorEventID", $_POST))
         {
             // The logged supporter is the acceptor family : he accepts the swap of event registration.
             // So we can close the swap
             $EventSwappedRegistrationClosingDate = date('Y-m-d H:i:s');

             // Yet we check the requesotr family selected by the acceptor family and the event match with the real
             // requestor family and event recorded in the database
             $SelectedRequestorEventID = strip_tags($_POST["lRequestorEventID"]);

             if (($RequestorFamilyID == $RecordOldEventSwappedRegistration['RequestorFamilyID'])
                 && ($SelectedRequestorEventID == $RecordOldEventSwappedRegistration['RequestorEventID']))
             {
                 // It's matching : it's OK
                 $bRequestorMatch = TRUE;
             }
             else
             {
                 // Error
                 $ContinueProcess = FALSE;
             }
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // We can update the event swapped registration with the new values
             $EventSwappedRegistrationID = dbUpdateEventSwappedRegistration($DbCon, $EventSwappedRegistrationID, NULL, $SupportMemberID,
                                                                            $RequestorFamilyID, $RequestorEventID, $AcceptorFamilyID,
                                                                            $AcceptorEventID, $EventSwappedRegistrationClosingDate);
             if ($EventSwappedRegistrationID != 0)
             {
                 // The event swapped registration is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;

                 if (empty($EventSwappedRegistrationClosingDate))
                 {
                     // The event swapped registration is just upated by the author or requestor family
                     $ConfirmationSentence = $LANG_CONFIRM_EVENT_SWAPPED_REGISTRATION_UPDATED;

                     // Log event
                     logEvent($DbCon, EVT_EVENT, EVT_SERV_EVENT_SWAPPED_REGISTRATION, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                              $EventSwappedRegistrationID);
                 }
                 else
                 {
                     // The event swapped registration is accepted and closed by the acceptor family
                     $ConfirmationSentence = $LANG_CONFIRM_EVENT_SWAPPED_REGISTRATION_CLOSED;

                     // Log event
                     logEvent($DbCon, EVT_EVENT, EVT_SERV_EVENT_SWAPPED_REGISTRATION, EVT_ACT_DIFFUSED, $_SESSION['SupportMemberID'],
                              $EventSwappedRegistrationID);

                     // We must now swap the 2 event registrations
                     // First, we update the event registration of the acceptor family -> registered for the requestor event
                     // We delete the comment of the event registration
                     dbUpdateEventRegistration($DbCon, $ArrayAcceptorEventRegistrations['EventRegistrationID'][0], NULL,
                                               $RequestorEventID, $AcceptorFamilyID, NULL, NULL, '');

                     // Log event
                     logEvent($DbCon, EVT_EVENT, EVT_SERV_EVENT_REGISTRATION, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                              $ArrayAcceptorEventRegistrations['EventRegistrationID'][0]);

                     // Next, we update the event registration of the requestor family -> registered for the acceptor event
                     $ArrayRequestorEventRegistrations = dbSearchEventRegistration($DbCon, array('EventID' => $RequestorEventID,
                                                                                                'FamilyID' => $RequestorFamilyID),
                                                                                   "EventID", 1, 0);

                     // We delete the comment of the event registration
                     dbUpdateEventRegistration($DbCon, $ArrayRequestorEventRegistrations['EventRegistrationID'][0], NULL,
                                               $AcceptorEventID, $RequestorFamilyID, NULL, NULL, '');

                     // Log event
                     logEvent($DbCon, EVT_EVENT, EVT_SERV_EVENT_REGISTRATION, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                              $ArrayRequestorEventRegistrations['EventRegistrationID'][0]);
                 }

                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($EventSwappedRegistrationID)."&Id=$EventSwappedRegistrationID"; // For the redirection

                 // We check if the acceptor family has changed (or the acceptor event)
                 if (($RecordOldEventSwappedRegistration['AcceptorFamilyID'] !== $AcceptorFamilyID)
                     || ($RecordOldEventSwappedRegistration['AcceptorEventID'] !== $AcceptorEventID))
                 {
                     $bHasChanged = TRUE;

                     // We check if we must send a notification to the new acceptor family
                     if ((isset($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapRequestRegisteredEvent']))
                         && (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapRequestRegisteredEvent'][Template])))
                     {
                         $EmailSubject = $LANG_NEW_EVENT_SWAP_REGISTRATION_REQUEST_EMAIL_SUBJECT;

                         if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                         {
                             $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                         }

                         // Get info about the event of the requestor family
                         $RecordEvent = getTableRecordInfos($DbCon, 'Events', $RequestorEventID);
                         $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($RequestorEventID)."&amp;Id=$RequestorEventID";
                         $EventLink = stripslashes($RecordEvent['EventTitle']);
                         $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                         $RecordTown = getTableRecordInfos($DbCon, 'Towns', $RecordEvent['TownID']);
                         $TownName = $RecordTown['TownName'];
                         $TownCode = $RecordTown['TownCode'];
                         unset($RecordTown);

                         // Get info about the event of the acceptor family
                         $RecordAcceptorEvent = getTableRecordInfos($DbCon, 'Events', $AcceptorEventID);
                         $AcceptorEventTitle = stripslashes($RecordAcceptorEvent['EventTitle'])
                                                            .date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                                  strtotime($RecordAcceptorEvent['EventStartDate']));

                         // Get lastname of the acceptor family
                         $AcceptorFamilyLastname = getFamilyLastname($DbCon, $AcceptorFamilyID);

                         // Link to the request of swap of event registration
                         $SwapEventRegistrationUrl = $CONF_URL_SUPPORT."Cooperation/UpdateSwapEventRegistration.php?Cr="
                                                     .md5($EventSwappedRegistrationID)."&amp;Id=$EventSwappedRegistrationID";
                         $SwapEventRegistrationLink = $LANG_NEW_EVENT_SWAP_REGISTRATION_REQUEST_EMAIL_GO_TO_REQUEST;
                         $SwapEventRegistrationLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                         // We define the content of the mail
                         $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapRequestRegisteredEvent'][Template];
                         $ReplaceInTemplate = array(
                                                    array(
                                                          "{LANG_EVENT}", "{EventUrl}", "{EventLink}", "{EventLinkTip}", "{LANG_TOWN}",
                                                          "{TownName}", "{TownCode}", "{LANG_EVENT_START_DATE}", "{EventStartDate}",
                                                          "{RequestorFamilyLastname}", "{AcceptorEventTitle}", "{SwapEventRegistrationUrl}",
                                                          "{SwapEventRegistrationLinkTip}", "{SwapEventRegistrationLink}"
                                                         ),
                                                    array(
                                                          $LANG_EVENT, $EventUrl, $EventLink, $EventLinkTip, $LANG_TOWN, $TownName, $TownCode,
                                                          $LANG_EVENT_START_DATE,
                                                          date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordEvent['EventStartDate'])),
                                                          $AcceptorFamilyLastname, $AcceptorEventTitle, $SwapEventRegistrationUrl,
                                                          $SwapEventRegistrationLinkTip, $SwapEventRegistrationLink
                                                         )
                                                   );

                         // Get the recipients of the e-mail notification
                         $MailingList["to"] = array();
                         $MailingList["bcc"] = array();
                         foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapRequestRegisteredEvent'][To] as $rt => $RecipientType)
                         {
                             $ArrayRecipients = getEmailRecipients($DbCon, $RequestorEventID, $RecipientType, $AcceptorFamilyID);
                             if (!empty($ArrayRecipients))
                             {
                                 $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayRecipients);
                             }
                         }

                         if (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapRequestRegisteredEvent'][Cc]))
                         {
                             $MailingList["cc"] = array();
                             foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapRequestRegisteredEvent'][Cc] as $rt => $RecipientType)
                             {
                                 $ArrayRecipients = getEmailRecipients($DbCon, $RequestorEventID, $RecipientType, $AcceptorFamilyID);
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
                         $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                     }
                 }
                 elseif ((!empty($EventSwappedRegistrationClosingDate))
                         && (isset($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapAcceptRegisteredEvent']))
                         && (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapAcceptRegisteredEvent'][Template])))
                 {
                     // Check if a notification must be sent to the requestor family (swap closed)
                     $EmailSubject = $LANG_NEW_EVENT_SWAP_REGISTRATION_ACCEPT_EMAIL_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                     }

                     // Get info about the event of the requestor family
                     $RecordRequestorEvent = getTableRecordInfos($DbCon, 'Events', $RequestorEventID);
                     $RequestorEventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($RequestorEventID)."&amp;Id=$RequestorEventID";
                     $RequestorEventLink = stripslashes($RecordRequestorEvent['EventTitle']);
                     $RequestorEventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;
                     $RequestorStartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordRequestorEvent['EventStartDate']));

                     $RecordRequestorTown = getTableRecordInfos($DbCon, 'Towns', $RecordRequestorEvent['TownID']);
                     $RequestorTownName = $RecordRequestorTown['TownName'];
                     $RequestorTownCode = $RecordRequestorTown['TownCode'];

                     // Get lastname of the requestor family
                     $RequestorFamilyLastname = getFamilyLastname($DbCon, $RequestorFamilyID);

                     // Get info about the event of the acceptor family
                     $RecordAcceptorEvent = getTableRecordInfos($DbCon, 'Events', $AcceptorEventID);
                     $AcceptorEventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($AcceptorEventID)."&amp;Id=$AcceptorEventID";
                     $AcceptorEventLink = stripslashes($RecordAcceptorEvent['EventTitle']);
                     $AcceptorEventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;
                     $AcceptorStartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordAcceptorEvent['EventStartDate']));

                     $RecordAcceptorTown = getTableRecordInfos($DbCon, 'Towns', $RecordAcceptorEvent['TownID']);
                     $AcceptorTownName = $RecordAcceptorTown['TownName'];
                     $AcceptorTownCode = $RecordAcceptorTown['TownCode'];

                     // Get lastname of the acceptor family
                     $AcceptorFamilyLastname = getFamilyLastname($DbCon, $AcceptorFamilyID);

                     unset($RecordRequestorTown, $RecordRequestorEvent, $RecordAcceptorEvent, $RecordAcceptorTown);

                     // We define the content of the mail
                     $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapAcceptRegisteredEvent'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{LANG_SWAP_EVENT_REGISTRATION_REQUESTOR_EVENT}", "{RequestorEventUrl}",
                                                      "{RequestorEventLink}", "{RequestorEventLinkTip}", "{LANG_TOWN}",
                                                      "{RequestorTownName}", "{RequestorTownCode}", "{LANG_EVENT_START_DATE}",
                                                      "{RequestorEventStartDate}", "{RequestorFamilyLastname}",
                                                      "{LANG_SWAP_EVENT_REGISTRATION_ACCEPTOR_EVENT}", "{AcceptorEventUrl}",
                                                      "{AcceptorEventLink}", "{AcceptorEventLinkTip}", "{AcceptorTownName}",
                                                      "{AcceptorTownCode}", "{AcceptorEventStartDate}", "{AcceptorFamilyLastname}"
                                                     ),
                                                array(
                                                      $LANG_SWAP_EVENT_REGISTRATION_REQUESTOR_EVENT, $RequestorEventUrl, $RequestorEventLink,
                                                      $RequestorEventLinkTip, $LANG_TOWN, $RequestorTownName, $RequestorTownCode,
                                                      $LANG_EVENT_START_DATE, $RequestorStartDate, $RequestorFamilyLastname,
                                                      $LANG_SWAP_EVENT_REGISTRATION_ACCEPTOR_EVENT, $AcceptorEventUrl, $AcceptorEventLink,
                                                      $AcceptorEventLinkTip, $AcceptorTownName, $AcceptorTownCode,
                                                      $AcceptorStartDate, $AcceptorFamilyLastname
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList["to"] = array();
                     $MailingList["bcc"] = array();
                     foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapAcceptRegisteredEvent'][To] as $rt => $RecipientType)
                     {
                         $ArrayRecipients = getEmailRecipients($DbCon, $RequestorEventID, $RecipientType, $AcceptorFamilyID);
                         if (!empty($ArrayRecipients))
                         {
                             $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayRecipients);
                         }
                     }

                     if (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapAcceptRegisteredEvent'][Cc]))
                     {
                         $MailingList["cc"] = array();
                         foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilySwapAcceptRegisteredEvent'][Cc] as $rt => $RecipientType)
                         {
                             $ArrayRecipients = getEmailRecipients($DbCon, $RequestorEventID, $RecipientType, $AcceptorFamilyID);
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
                     $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                 }
             }
             else
             {
                 // The event swapped registration can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_EVENT_SWAPPED_REGISTRATION;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($RequestorEventID))
             {
                 // The event of the requestor is empty
                 $ConfirmationSentence = $LANG_ERROR_SWAP_EVENT_REGISTRATION_REQUESTOR_EVENT;
             }
             elseif (empty($RequestorFamilyID))
             {
                 // The family who requests the swap of registration is empty
                 $ConfirmationSentence = $LANG_ERROR_SWAP_EVENT_REGISTRATION_REQUESTOR_FAMILY;
             }
             elseif (empty($AcceptorFamilyID))
             {
                 // The family who requests the swap of registration is empty
                 $ConfirmationSentence = $LANG_ERROR_SWAP_EVENT_REGISTRATION_ACCEPTOR_FAMILY;
             }
             elseif (empty($AcceptorEventID))
             {
                 // The family who must accept the swap of registration is empty
                 $ConfirmationSentence = $LANG_ERROR_SWAP_EVENT_REGISTRATION_ACCEPTOR_EVENT;
             }
             elseif (!$bFamilyMatchToEvent)
             {
                 // Acceptor family and acceptor event don't match
                 $ConfirmationSentence = $LANG_ERROR_SWAP_EVENT_REGISTRATION_FAMILY_NO_MATCH;
             }
             elseif ((!empty($EventSwappedRegistrationClosingDate)) && (!$bRequestorMatch))
             {
                 // The logged user is the acceptor family and the selected requestor family and event don't match
                 $ConfirmationSentence = $LANG_ERROR_SWAP_EVENT_REGISTRATION_REQUESTOR_NO_MATCH;
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
     // The supporter doesn't come from the UpdateSwapEventRegistration.php page
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/UpdateSwapEventRegistration.php?$UrlParameters', $CONF_TIME_LAG)"
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