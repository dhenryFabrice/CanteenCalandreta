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
 * Support module : display the form to swap the snack date between 2 families for the current school year.
 * The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2016-10-28
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 //################################ FORM PROCESSING ##########################
 $sErrorMsg = '';
 $sConfirmationMsg = '';
 $iNbMailsSent = 0;

 // Current school year
 $SchoolYear = getSchoolYear(date('Y-m-d'));

 if (!empty($_POST["bSubmit"]))
 {
     if ((isSet($_SESSION["SupportMemberID"])) && (isAdmin()))
     {
         $FirstSnackRegistrationID = strip_tags($_POST['lFirstSnackRegistrationID']);
         $SecondSnackRegistrationID = strip_tags($_POST['lSecondSnackRegistrationID']);

         // Get infos about snack registrations
         $FirstSnackRegistrationRecord = getTableRecordInfos($DbCon, "SnackRegistrations", $FirstSnackRegistrationID);
         $SecondSnackRegistrationRecord = getTableRecordInfos($DbCon, "SnackRegistrations", $SecondSnackRegistrationID);

         // We swap the registrations if different and if different concerned families but if same classroom
         if (($FirstSnackRegistrationID != $SecondSnackRegistrationID)
             && ($FirstSnackRegistrationRecord['FamilyID'] != $SecondSnackRegistrationRecord['FamilyID']))
         {
             // We swap the date between the 2 families
             $FirstNewDate = $SecondSnackRegistrationRecord['SnackRegistrationDate'];
             $SecondNewDate = $FirstSnackRegistrationRecord['SnackRegistrationDate'];

             $FirstID = dbUpdateSnackRegistration($DbCon, $FirstSnackRegistrationID, $FirstNewDate,
                                                  $FirstSnackRegistrationRecord['FamilyID'], NULL);

             if ($FirstID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_SNACK, EVT_SERV_PLANNING, EVT_ACT_SWAP, $_SESSION['SupportMemberID'], $FirstID);

                 $SecondID = dbUpdateSnackRegistration($DbCon, $SecondSnackRegistrationID, $SecondNewDate,
                                                       $SecondSnackRegistrationRecord['FamilyID'], NULL);

                 if ($SecondID != 0)
                 {
                     // Log event
                     logEvent($DbCon, EVT_SNACK, EVT_SERV_PLANNING, EVT_ACT_SWAP, $_SESSION['SupportMemberID'], $SecondID);

                     // Swap done
                     $sConfirmationMsg = $LANG_CONFIRM_RECORD_UPDATED;

                     $ArraySelectedFamilies[$FirstSnackRegistrationRecord['FamilyID']] = array($FirstNewDate,
                                                                                               $FirstSnackRegistrationRecord['SnackRegistrationClass']);
                     $ArraySelectedFamilies[$SecondSnackRegistrationRecord['FamilyID']] = array($SecondNewDate,
                                                                                                $SecondSnackRegistrationRecord['SnackRegistrationClass']);

                     // We send a e-mail to both families to confirm the swap of dates
                     $NotificationType = 'UpdatedSnackPlanning';
                     if ((count($ArraySelectedFamilies) > 0)
                         && (isset($CONF_SNACK_NOTIFICATIONS[$NotificationType][Template]))
                         && (!empty($CONF_SNACK_NOTIFICATIONS[$NotificationType][Template]))
                        )
                     {
                         $EmailSubject = $LANG_SNACK_PLANNING_UPDATED_EMAIL_SUBJECT;

                         if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SNACK_PLANNING]))
                         {
                             $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SNACK_PLANNING].$EmailSubject;
                         }

                         $SnackUrl = $CONF_URL_SUPPORT."Canteen/SnackPlanning.php?lYear=$SchoolYear";
                         $SnackLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                         // We define the content of the mail
                         $TemplateToUse = $CONF_SNACK_NOTIFICATIONS[$NotificationType][Template];

                         foreach($ArraySelectedFamilies as $CurrentFamilyID => $ArrayData)
                         {
                             $ReplaceInTemplate = array(
                                                        array(
                                                              "{SnackUrl}", "{SnackLinkTip}", "{SnackRegistrationClass}",
                                                              "{SnackRegistrationDate}"
                                                             ),
                                                        array(
                                                              $SnackUrl, $SnackLinkTip, $CONF_CLASSROOMS[$SchoolYear][$ArrayData[1]],
                                                              date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($ArrayData[0]))
                                                             )
                                                       );

                             // We send the notification to concerned family : we get e-mails of family
                             $MailingList = array();
                             $RecordFamily = getTableRecordInfos($DbCon, "Families", $CurrentFamilyID);
                             $MailingList["to"][] = $RecordFamily['FamilyMainEmail'];
                             if (!empty($RecordFamily['FamilySecondEmail']))
                             {
                                 $MailingList["to"][] = $RecordFamily['FamilySecondEmail'];
                             }

                             if ((isset($CONF_SNACK_NOTIFICATIONS[$NotificationType][Cc]))
                                 && (!empty($CONF_SNACK_NOTIFICATIONS[$NotificationType][Cc])))
                             {
                                 $MailingList["cc"] = $CONF_SNACK_NOTIFICATIONS[$NotificationType][Cc];
                             }

                             if ((isset($CONF_SNACK_NOTIFICATIONS[$NotificationType][Bcc]))
                                 && (!empty($CONF_SNACK_NOTIFICATIONS[$NotificationType][Bcc])))
                             {
                                 $MailingList["bcc"] = $CONF_SNACK_NOTIFICATIONS[$NotificationType][Bcc];
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
                             if ($bIsEmailSent)
                             {
                                 $iNbMailsSent++;
                             }
                         }
                     }
                 }
                 else
                 {
                     // Error on second swap
                     $sErrorMsg = $LANG_ERROR_UPDATE_RECORD;
                 }
             }
             else
             {
                 // Error on first swap
                 $sErrorMsg = $LANG_ERROR_UPDATE_RECORD;
             }
         }
         else
         {
             // Error : swap not necessary
             $sErrorMsg = $LANG_ERROR_UPDATE_RECORD;
         }
     }
     else
     {
         // Error : not allowed
         $sErrorMsg = $LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE;
     }
 }

 if ($iNbMailsSent > 0)
 {
     // A notification is sent
     $sConfirmationMsg .= '&nbsp;'.generateStyledPicture($CONF_NOTIFICATION_SENT_ICON);
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array('../Verifications.js')
                     );
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#SwapPlanning', 'Accessibility');

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu(1);

 // Content of the web page
 openArea('id="content"');

 // Display the "Admin" and the "parameters" contextual menus if the supporter isn't logged, an empty contextual menu otherwise
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("admin", 1, Admin_SwapSnackPlanning);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_ADMIN_SWAP_SNACK_PLANNING_PAGE_TITLE, 2);

 // Check if there is an error message to display
 if (!empty($sErrorMsg))
 {
     openParagraph('ErrorMsg');
     echo $sErrorMsg;
     closeParagraph();
 }
 elseif (!empty($sConfirmationMsg))
 {
     openParagraph('ConfirmationMsg');
     displayStyledText($sConfirmationMsg, 'ShortConfirmMsg');
     closeParagraph();
 }

 // We want to swap the snack's date between 2 families for the current school year
 openParagraph();
 displayStyledText($LANG_SUPPORT_ADMIN_SWAP_SNACK_PLANNING_PAGE_INTRODUCTION, "");
 closeParagraph();

 // We display the form to swap snack planning
 displaySwapSnackPlanningForm($DbCon, "SwapSnackPlanning.php", $SchoolYear, $CONF_ACCESS_APPL_PAGES[FCT_ADMIN]);

 // Release the connection to the database
 dbDisconnection($DbCon);

 // To measure the execution script time
 if ($CONF_DISPLAY_EXECUTION_TIME_SCRIPT)
 {
     openParagraph('InfoMsg');
     initEndTime();
     displayExecutionScriptTime('ExecutionTime');
     closeParagraph();
 }

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Close the <div> "Page"
     closeArea();
 }

 // Close the <div> "content"
 closeArea();

 // Footer of the application
 displayFooter($LANG_INTRANET_FOOTER);

 // Close the web page
 closeWebPage();

 closeGraphicInterface();
?>