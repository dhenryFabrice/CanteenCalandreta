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
 * Support module : process the creation of a new event registration. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.1
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table and patch a bug with the
 *                    redirection ($UrlParameters when error occurs)
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2018-02-12 : taken into account "Inhibition" parameter of $CONF_COOP_EVENT_NOTIFICATIONS
 *
 * @since 2013-04-19
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

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

         // We get the ID of the event of the registration
         $EventID = $_POST["hidEventID"];
         if ($EventID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // We get the values entered by the user
         $Comment = trim(strip_tags($_POST["sComment"]));
         $FamilyID = strip_tags($_POST["lFamilyID"]);
         if ($FamilyID == 0)
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

         $RegistrationValided = 0;
         if ((array_key_exists("chkRegistrationValided", $_POST)) && (!empty($_POST['chkRegistrationValided'])))
         {
             // The registration is valided
             $RegistrationValided = 1;
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             $EventRegistrationID = dbAddEventRegistration($DbCon, date('Y-m-d H:i:s'), $EventID, $FamilyID, $SupportMemberID,
                                                           $RegistrationValided, $Comment);

             if ($EventRegistrationID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_EVENT, EVT_SERV_EVENT_REGISTRATION, EVT_ACT_ADD, $_SESSION['SupportMemberID'],
                          $EventRegistrationID);

                 // The event registration is added
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_EVENT_REGISTRATION_ADDED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "UpdateEventRegistration.php?Cr=".md5($EventRegistrationID)."&Id=$EventRegistrationID"; // For the redirection

                 // Check if a notification must be sent (only if the family is registered by another person than the family itself)
                 $LoggedFamilyID = $_SESSION['FamilyID'];

                 $RecordEvent = getTableRecordInfos($DbCon, 'Events', $EventID);

                 $bCanSendNotification = FALSE;
                 if (($LoggedFamilyID != $FamilyID) && (isset($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent']))
                     && (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Template])))
                 {
                     $bCanSendNotification = TRUE;

                     // We check if there is an inhibition
                     if ((isset($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Inhibition]))
                         && (in_array($RecordEvent['EventTypeID'], $CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Inhibition])))
                     {
                         // No notification for this event type
                         $bCanSendNotification = FALSE;
                     }
                 }

                 // We can send a notification ?
                 if ($bCanSendNotification)
                 {
                     // Yes !
                     $EmailSubject = $LANG_NEW_EVENT_REGISTRATION_EMAIL_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                     }

                     $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEventRegistration.php?Cr=".md5($EventRegistrationID)."&amp;Id=$EventRegistrationID";
                     $EventLink = stripslashes($RecordEvent['EventTitle']);
                     $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     $RecordTown = getTableRecordInfos($DbCon, 'Towns', $RecordEvent['TownID']);
                     $TownName = $RecordTown['TownName'];
                     $TownCode = $RecordTown['TownCode'];
                     unset($RecordTown);

                     // We define the content of the mail
                     $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Template];
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
                     foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][To] as $rt => $RecipientType)
                     {
                         $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType, $FamilyID);
                         if (!empty($ArrayRecipients))
                         {
                             $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayRecipients);
                         }
                     }

                     if (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Cc]))
                     {
                         $MailingList["cc"] = array();
                         foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Cc] as $rt => $RecipientType)
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
                     $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                 }
             }
             else
             {
                 // The event registration can't be added
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_ADD_EVENT_REGISTRATION;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "AddEventRegistration.php?".$QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($EventID))
             {
                 // The event is empty
                 $ConfirmationSentence = $LANG_ERROR_EVENT_REGISTRATION_EVENT;
             }
             elseif (empty($FamilyID))
             {
                 // The family is empty
                 $ConfirmationSentence = $LANG_ERROR_EVENT_REGISTRATION_FAMILY;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = "AddEventRegistration.php?".$QUERY_STRING; // For the redirection
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
         $UrlParameters = "AddEventRegistration.php?".$QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the AddEventRegistration.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = "AddEventRegistration.php?".$QUERY_STRING; // For the redirection
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/$UrlParameters', $CONF_TIME_LAG)"
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