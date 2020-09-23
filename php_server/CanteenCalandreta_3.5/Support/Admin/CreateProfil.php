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
 * Support module : display the form to create a new support member profil
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2015-10-09 : taken into account the FamilyID field of SupportMembers table
 *     - 2016-10-21 : page moved to Admin module and load some configuration variables from database
 *
 * @since 2014-08-06
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
 $bIsEmailSent = FALSE;
 $ConfirmationCaption = '';
 $ConfirmationSentence = '';
 $ConfirmationStyle = '';

 if (!empty($_POST["bSubmit"]))
 {
     // User infos
     $Lastname = strip_tags(trim($_POST["sLastname"]));
     $Firstname = strip_tags(trim($_POST["sFirstname"]));
     $Email = strip_tags(trim($_POST["sEmail"]));
     $Phone = strip_tags(trim($_POST["sPhoneNumber"]));
     $UserStateID = strip_tags($_POST["lSupportMemberStateID"]);

     // Selected family associated to the support membre account
     $FamilyID = strip_tags($_POST["lFamilyID"]);
     if (empty($FamilyID))
     {
         // No associated family for this account
         $FamilyID = NULL;
     }

     // Login / password / web service key (all in MD5)
     $NewLogin = strip_tags($_POST["sLogin"]);
     $NewClearLogin = strip_tags($_POST["hidLogin"]);

     $NewPassword = strip_tags($_POST["sPassword"]);
     $NewClearPassword = strip_tags($_POST["hidPassword"]);

     $ConfirmPassword = strip_tags($_POST["sConfirmPassword"]);

     $WebServiceKey = strip_tags($_POST["sWebServiceKey"]);

     // Check if the login / pwd must be send by e-mail to the user
     $bSendByEmail = FALSE;
     if (isset($_POST["chkSendMail"]))
     {
         // Yes
         $bSendByEmail = TRUE;
     }

     // Verification that the parameters are correct
     if ((!empty($Lastname)) && (!empty($Firstname)) && (isValideEmailAddress($Email)) && ($UserStateID > 0) && (!empty($NewLogin))
         && (!empty($NewPassword)) && ($NewPassword == $ConfirmPassword) && (strlen($NewLogin) == 32) && (strlen($NewPassword) == 32)
         && ($NewLogin == md5($NewClearLogin)) && ($NewPassword == md5($NewClearPassword)))
     {
         $NewSupportMemberID = dbAddSupportMember($DbCon, $Lastname, $Firstname, $Email, $UserStateID, $Phone, 1, $FamilyID);
         if ($NewSupportMemberID > 0)
         {
             // Log event
             logEvent($DbCon, EVT_PROFIL, EVT_SERV_PROFIL, EVT_ACT_CREATE, $_SESSION['SupportMemberID'], $NewSupportMemberID);

             if (dbSetLoginPwdSupportMember($DbCon, $NewSupportMemberID, $NewLogin, $NewPassword))
             {
                 // Log event
                 logEvent($DbCon, EVT_PROFIL, EVT_SERV_LOGIN, EVT_ACT_CREATE, $_SESSION['SupportMemberID'], $NewSupportMemberID);

                 // Update the web service key
                 dbSetWebServiceKeySupportMember($DbCon, $NewSupportMemberID, $WebServiceKey);

                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_CREATE_PROFIL;
                 $ConfirmationStyle = "ConfirmationMsg";

                 if ($bSendByEmail)
                 {
                     // Login and password must be send by e-mail to the user
                     $EmailSubject = $LANG_CREATE_PROFIL_PAGE_EMAIL_SUBJECT;
                     $TemplateToUse = "EmailNewSupportMember";

                     $ReplaceInTemplate = array(
                                                array(
                                                      "{LANG_LASTNAME}", "{SupportMemberLastname}", "{LANG_LOGIN_NAME}",
                                                      "{SupportMemberLogin}", "{LANG_PASSWORD}", "{SupportMemberPassword}",
                                                      "{CONF_URL_SUPPORT}"
                                                     ),
                                                array(
                                                      $LANG_LASTNAME, $Lastname, $LANG_LOGIN_NAME, $NewClearLogin, $LANG_PASSWORD,
                                                      $NewClearPassword, $CONF_URL_SUPPORT
                                                     )
                                               );

                     $MailingList["to"] = array($Email);

                     if ($CONF_MODE_DEBUG)
                     {
                         $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
                     }

                     $bIsEmailSent = sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate, array());
                 }
             }
             else
             {
                 // Wrong parameters
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_LOGIN_PWD;
                 $ConfirmationStyle = "ErrorMsg";
             }
         }
         else
         {
             // The profil can't be recorded in the database
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_CREATE_PROFIL;
             $ConfirmationStyle = "ErrorMsg";
         }
     }
     else
     {
         // Errors
         if ((!empty($Lastname)) && (!empty($Firstname)) && (isValideEmailAddress($Email)) && ($UserStateID > 0))
         {
             // Error in user's infos
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_CREATE_PROFIL;
             $ConfirmationStyle = "ErrorMsg";
         }
         elseif ((!empty($NewLogin)) && (!empty($NewPassword)) && ($NewPassword == $ConfirmPassword) && (strlen($NewLogin) == 32)
                 && (strlen($NewPassword) == 32) && ($NewLogin == md5($NewClearLogin)) && ($NewPassword == md5($NewClearPassword)))
         {
             // Error in login / password
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_NO_LOGIN_PWD;
             $ConfirmationStyle = "ErrorMsg";
         }
     }
 }
 //################################ END FORM PROCESSING ##########################

 if ($bIsEmailSent)
 {
     // A notification is sent
     $ConfirmationSentence .= '&nbsp;'.generateStyledPicture($CONF_NOTIFICATION_SENT_ICON);
 }

 if (!empty($ConfirmationCaption))
 {
     // Display this page if there is a confirmation / error message
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen'
                               ),
                          array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                          'WhitePage',
                          "Redirection('".$CONF_ROOT_DIRECTORY."Support/Admin/CreateProfil.php', $CONF_TIME_LAG)"
                     );

     // Content of the web page
     openArea('id="content"');

     openFrame($ConfirmationCaption);
     displayStyledText($ConfirmationSentence, $ConfirmationStyle);
     closeFrame();

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

     // Close the <div> "content"
     closeArea();

     closeGraphicInterface();
 }
 else
 {
     // Display this page by default (no confirmation /error message to display)
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen'
                               ),
                          array(
                                '../Verifications.js', '../../Common/JSMD5/MD5.js'
                               )
                         );

     openWebPage();

     // Display invisible link to go directly to content
     displayStyledLinkText($LANG_GO_TO_CONTENT, '#NewProfil', 'Accessibility');

     // Display the header of the application
     displayHeader($LANG_INTRANET_HEADER);

     // Display the main menu at the top of the web page
     displaySupportMainMenu(1);

     // Content of the web page
     openArea('id="content"');

     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Open the contextual menu area
         openArea('id="contextualmenu"');

         displaySupportMemberContextualMenu("admin", 1, Admin_CreateSupportMember);
         displaySupportMemberContextualMenu("parameters", 1, 0);

         // Display information about the logged user
         displayLoggedUser($_SESSION);

         // Close the <div> "contextualmenu"
         closeArea();

         openArea('id="page"');
     }

     // Display the informations, forms, etc. on the right of the web page
     displayTitlePage($LANG_CREATE_PROFIL_PAGE_TITLE, 2);

     openParagraph();
     displayStyledText($LANG_CREATE_PROFIL_PAGE_INTRODUCTION, '');
     closeParagraph();

     if (isSet($_SESSION["SupportMemberID"]))
     {
         // We display the form to prepare children to the next school year
         displayCreateSupportMemberProfilForm($DbCon, "CreateProfil.php", $CONF_ACCESS_APPL_PAGES[FCT_SYSTEM]);
     }
     else
     {
         // The user isn't logged
         openParagraph('InfoMsg');
         displayStyledText($LANG_ERROR_NOT_LOGGED, 'ErrorMsg');
         closeParagraph();
     }

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
 }
?>