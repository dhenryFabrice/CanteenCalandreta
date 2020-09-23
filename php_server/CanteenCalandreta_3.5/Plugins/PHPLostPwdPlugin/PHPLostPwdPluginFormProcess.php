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
 * PHP plugin LostPwd module : process the form to send a new password to the supporter if
 * password lost
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2014-01-02 : taken into account english language
 *     - 2014-04-17 : taken into account Occitan language
 *     - 2014-06-11 : remove accents of the support member lastname to compute MD5 (because of pbs
 *                    with MD5 javascript lib to manage accents)
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-09-06
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();


 //######## Langage variables ########
 switch(strtolower($CONF_LANG))
 {
     case 'oc':
         // Messages
         $LANG_LOST_PWD_PLUGIN_EMAIL_SUBJECT = "Aplech Cantina Calandreta : vòstres novèls identificants";

         // Errors
         $LANG_ERROR_LOST_PWD_PLUGIN_WRONG_EMAIL    = "L'adreça e-mèl correspond pas a cap de compte. Dintratz una de novèla!";
         $LANG_ERROR_LOST_PWD_PLUGIN_SEND_NEW_PWD   = "Lo novèl senhal poguèt pas èstre envajat per e-mèl!";

         // Confirmations
         $LANG_CONFIRM_LOST_PWD_PLUGIN_NEW_PWD_SENT = "Novèl senhal envejat a la vòstra novèla adreça e-mèl.";
         break;

     case 'fr':
         // Messages
         $LANG_LOST_PWD_PLUGIN_EMAIL_SUBJECT = "Outil Cantine Calandreta : vos nouveaux identifiants";

         // Errors
         $LANG_ERROR_LOST_PWD_PLUGIN_WRONG_EMAIL    = "L'adresse e-mail saisie ne correspond à aucun compte. Veuillez en saisir une nouvelle!";
         $LANG_ERROR_LOST_PWD_PLUGIN_SEND_NEW_PWD   = "Le nouveau mot de passe n'a pu être envoyé par e-mail!";

         // Confirmations
         $LANG_CONFIRM_LOST_PWD_PLUGIN_NEW_PWD_SENT = "Nouveau mot de passe envoyé à votre adresse e-mail.";
         break;

     case 'en':
     default:
         // Messages
         $LANG_LOST_PWD_PLUGIN_EMAIL_SUBJECT = "Canteen Calandreta tool : your new login/password";

         // Errors
         $LANG_ERROR_LOST_PWD_PLUGIN_WRONG_EMAIL    = "The entered e-mail address dosen't exist. Enter an existing e-mail address!";
         $LANG_ERROR_LOST_PWD_PLUGIN_SEND_NEW_PWD   = "The new password hasn't been send by e-mail because of a technical problem!";

         // Confirmations
         $LANG_CONFIRM_LOST_PWD_PLUGIN_NEW_PWD_SENT = "New password sent to your e-mail address.";
         break;
 }


 //######## Configuration variables ########
 $CONF_LOST_PWD_PLUGIN_SUPPORT_MEMBERS_STATUS_ID = array(4, 5);
 $CONF_LOST_PWD_PLUGIN_DEFAULT_PASSWORD          = "motdepasse";


 //################################ FORM PROCESSING ##########################
 if (!empty($_POST["sEmailLostPwdPlugin"]))
 {
     // Connection to the database
     $DbCon = dbConnection();

     // Load all configuration variables from database
     loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                          'CONF_CLASSROOMS',
                                          'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                          'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                          'CONF_CANTEEN_PRICES',
                                          'CONF_NURSERY_PRICES',
                                          'CONF_NURSERY_DELAYS_PRICES'));

     $sEmail = strip_tags($_POST["sEmailLostPwdPlugin"]);

     // Verification that the parameters are correct
     if ((!empty($sEmail)) && (isValideEmailAddress($sEmail)))
     {
         // We search the supporter with the entered e-mail
         $SupportMemberStateCondition = '';
         if (!empty($CONF_LOST_PWD_PLUGIN_SUPPORT_MEMBERS_STATUS_ID))
         {
             $SupportMemberStateCondition = " AND SupportMemberStateID IN ".constructSQLINString($CONF_LOST_PWD_PLUGIN_SUPPORT_MEMBERS_STATUS_ID);
         }

         $DbResult = $DbCon->query("SELECT SupportMemberID, SupportMemberLastname FROM SupportMembers
                                           WHERE SupportMemberEmail = \"$sEmail\" $SupportMemberStateCondition");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 while ($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                 {
                     // Change the login of the supporter and remove accents
                     $sNewLogin = strtr(strtolower($Record['SupportMemberLastname']), "âäàéèêëîïôöûüùç", "aaaeeeeiioouuuc");
                     $sNewLoginMD5 = md5($sNewLogin);

                     // Change the password of the supporter
                     mt_srand();
                     $iRandValue = mt_rand() % 100;
                     $sNewPassword = strtolower($CONF_LOST_PWD_PLUGIN_DEFAULT_PASSWORD).$iRandValue;
                     $sNewPasswordMD5 = md5($sNewPassword);

                     if (dbSetLoginPwdSupportMember($DbCon, $Record['SupportMemberID'], $sNewLoginMD5, $sNewPasswordMD5))
                     {
                         // Lofin and password changed
                         // Log event
                         logEvent($DbCon, EVT_PROFIL, EVT_SERV_PROFIL, EVT_ACT_UPDATE, $Record['SupportMemberID'],
                                  $Record['SupportMemberID']);

                         // We define the content of the mail
                         $EmailSubject = $LANG_LOST_PWD_PLUGIN_EMAIL_SUBJECT;
                         $ReplaceInTemplate = array(
                                                    array(
                                                          "{LANG_LOGIN_NAME}", "{LoginName}", "{LANG_PASSWORD}", "{Password}"
                                                         ),
                                                    array(
                                                          $LANG_LOGIN_NAME, $sNewLogin, $LANG_PASSWORD, $sNewPassword
                                                         )
                                                   );

                         // We define the mailing-list
                         $MailingList["to"] = array($sEmail);

                         // DEBUG MODE
                         if ($GLOBALS["CONF_MODE_DEBUG"])
                         {
                             if (!in_array($GLOBALS["CONF_EMAIL_INTRANET_EMAIL_ADDRESS"], $MailingList["to"]))
                             {
                                 // Without this test, there is a server mail error...
                                 $MailingList["to"] = array_merge(array($GLOBALS["CONF_EMAIL_INTRANET_EMAIL_ADDRESS"]),
                                                                  $MailingList["to"]);
                             }
                         }

                         // We can send the e-mail
                         $bIsEmailSent = sendEmail(NULL, $MailingList, $EmailSubject, "EmailNewPassword", $ReplaceInTemplate,
                                                   array(), dirname(__FILE__).'/');

                         $ConfirmationCaption = $LANG_CONFIRMATION;
                         $ConfirmationSentence = $LANG_CONFIRM_LOST_PWD_PLUGIN_NEW_PWD_SENT;
                         $ConfirmationStyle = "ConfirmationMsg";
                     }
                     else
                     {
                         // Password not changed
                         $ConfirmationCaption = $LANG_ERROR;
                         $ConfirmationSentence = $LANG_ERROR_LOST_PWD_PLUGIN_SEND_NEW_PWD;
                         $ConfirmationStyle = "ErrorMsg";
                     }
                 }
             }
             else
             {
                 // Wrong parameters
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_LOST_PWD_PLUGIN_WRONG_EMAIL;
                 $ConfirmationStyle = "ErrorMsg";
             }
         }
         else
         {
             // Wrong parameters
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_LOST_PWD_PLUGIN_WRONG_EMAIL;
             $ConfirmationStyle = "ErrorMsg";
         }
     }
     else
     {
         // Wrong parameters
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_LOST_PWD_PLUGIN_WRONG_EMAIL;
         $ConfirmationStyle = "ErrorMsg";
     }

     // Release the connection to the database
     dbDisconnection($DbCon);

 }
 else
 {
     // The supporter doesn't come from the index.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../../Support/Styles_Support.css' => 'screen'
                           ),
                      array()
                     );
 openWebPage();

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu();

 // Content of the web page
 openArea('id="content"');

 // Display the informations, forms, etc. on the right of the web page
 openFrame($ConfirmationCaption);
 displayStyledText($ConfirmationSentence, $ConfirmationStyle);
 closeFrame();

 // Display a link to go back to the login page
 openParagraph('InfoMsg');
 displayStyledLinkText($LANG_GO_BACK, "../../Support/index.php");
 closeParagraph();

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

 // Footer of the application
 displayFooter($LANG_INTRANET_FOOTER);

 // Close the web page
 closeWebPage();

 closeGraphicInterface();
?>