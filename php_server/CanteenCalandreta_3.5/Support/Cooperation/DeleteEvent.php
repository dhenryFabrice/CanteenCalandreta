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
 * Support module : allow a supporter to delete an event from a table (view). The supporter must be logged to delete the event.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2013-04-12
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted event ID
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

         // We get the author of the event
         $RecordEvent = getTableRecordInfos($DbCon, "Events", $Id);
         $bCanDelete = FALSE;
         $ArrayConcernedEventID = array();
         if ((in_array($_SESSION['SupportMemberStateID'], array(1))) || ($RecordEvent["SupportMemberID"] == $_SESSION["SupportMemberID"]))
         {
             // Only admin and author of the event can delete it
             $bCanDelete = TRUE;

             // Get child-events
             $ArrayChildEvents = getEventsTree($DbCon, $Id);
             if (isset($ArrayChildEvents['EventID']))
             {
                 $ArrayConcernedEventID = $ArrayChildEvents['EventID'];
             }
             else
             {
                 $ArrayConcernedEventID[] = $Id;
             }

             unset($ArrayChildEvents);
         }

         // Only the author of the event can delete the event
         if ($bCanDelete)
         {
             $ArrayConcernedFamilyID = array();

             // Check if there are registered families for this event and its child-events
             foreach($ArrayConcernedEventID as $e => $CurrentEventID)
             {
                 $ArrayRegistrations = dbSearchEventRegistration($DbCon, array("EventID" => $CurrentEventID), "FamilyLastname", 1, 0);
                 if ((isset($ArrayRegistrations['FamilyID'])) && (!empty($ArrayRegistrations['FamilyID'])))
                 {
                     $ArrayConcernedFamilyID = array_merge($ArrayConcernedFamilyID, $ArrayRegistrations['FamilyID']);
                 }
             }

             // Keep once each registered family
             $ArrayConcernedFamilyID = array_unique($ArrayConcernedFamilyID);

             // We delete the selected event
             if (dbDeleteEvent($DbCon, $Id))
             {
                 // Log event
                 logEvent($DbCon, EVT_EVENT, EVT_SERV_EVENT, EVT_ACT_DELETE, $_SESSION['SupportMemberID'], $Id,
                          array('EventDetails' => $RecordEvent));

                 // The event is deleted
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_EVENT_DELETED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = $CONF_ROOT_DIRECTORY."Support/Cooperation/$BackPage?Cr=$CryptedReturnID&Id=$ReturnId"; // For the redirection

                 if (empty($RecordEvent['ParentEventID']))
                 {
                     // The event is a parent event : first, we delete registrations of the families for the event and its child-events...
                     foreach($ArrayConcernedEventID as $e => $CurrentEventID)
                     {
                         dbDeleteEventRegistration($DbCon, NULL, $CurrentEventID);
                     }

                     // ... next, we send a notification if nedeed
                     if ((!empty($ArrayConcernedFamilyID)) && (isset($CONF_COOP_EVENT_NOTIFICATIONS["DeletedEvent"]))
                         && (!empty($CONF_COOP_EVENT_NOTIFICATIONS["DeletedEvent"][Template])))
                     {
                         $EmailSubject = $LANG_DELETED_EVENT_EMAIL_SUBJECT;
                         if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                         {
                             $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                         }

                         $EventTitle = stripslashes($RecordEvent['EventTitle']);
                         $StartDate = $RecordEvent['EventStartDate'];

                         $RecordTown = getTableRecordInfos($DbCon, 'Towns', $RecordEvent['TownID']);
                         $TownName = $RecordTown['TownName'];
                         $TownCode = $RecordTown['TownCode'];
                         unset($RecordTown);

                         // We define the content of the mail
                         $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['DeletedEvent'][Template];
                         $ReplaceInTemplate = array(
                                                    array(
                                                          "{LANG_EVENT}", "{EventTitle}", "{LANG_TOWN}",
                                                          "{TownName}", "{TownCode}", "{LANG_EVENT_START_DATE}", "{EventStartDate}"
                                                        ),
                                                    array(
                                                          $LANG_EVENT, $EventTitle, $LANG_TOWN, $TownName, $TownCode,
                                                          $LANG_EVENT_START_DATE,
                                                          date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($StartDate))
                                                         )
                                                   );

                         // Get the recipients of the e-mail notification
                         $MailingList["to"] = array();
                         $MailingList["bcc"] = array();
                         foreach($CONF_COOP_EVENT_NOTIFICATIONS['DeletedEvent'][To] as $rt => $RecipientType)
                         {
                             switch($RecipientType)
                             {
                                 case TO_ALL_REGISTRERED_FAMILIES_EVENT:
                                     // All registered families to the event (and child-event)
                                     $ArrayFamilies = dbSearchFamily($DbCon, array(), "FamilyLastname", 1, 0);
                                     if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                                     {
                                         $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayFamilies['FamilyMainEmail']);

                                         foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
                                         {
                                             if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                             {
                                                 $MailingList["bcc"][] = $ArrayFamilies['FamilySecondEmail'][$f];
                                             }
                                         }
                                     }
                                     break;
                             }
                         }

                         if (!empty($CONF_COOP_EVENT_NOTIFICATIONS['DeletedEvent'][Cc]))
                         {
                             $MailingList["cc"] = array();
                             foreach($CONF_COOP_EVENT_NOTIFICATIONS['DeletedEvent'][Cc] as $rt => $RecipientType)
                             {

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
                     if (!empty($ArrayConcernedFamilyID))
                     {
                         // Child event : we set its registrations and registrations of its child events on the parent event
                         foreach($ArrayConcernedEventID as $e => $CurrentEventID)
                         {
                             dbPostponeEventRegistration($DbCon, $CurrentEventID, $RecordEvent['ParentEventID'], $ArrayConcernedFamilyID);
                         }
                     }
                 }
             }
             else
             {
                 // ERROR : the event isn't deleted
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_DELETE_EVENT;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $CONF_ROOT_DIRECTORY."Support/Cooperation/$BackPage"; // For the redirection
             }
         }
         else
         {
             // ERROR : the event isn't deleted because the logged supporter isn't the author of the event
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_DELETE_EVENT_WRONG_AUTHOR;
             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = $CONF_ROOT_DIRECTORY."Support/Cooperation/$BackPage"; // For the redirection
         }
     }
     else
     {
         // ERROR : the event ID is wrong
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_WRONG_EVENT_ID;
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

 // Redirection to the view of events of the logged supporter
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
     // Error because the ID of the event ID and the crypted ID don't match
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_WRONG_EVENT_ID, 'ErrorMsg');
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