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
 * Support module : process the creation of a new donation. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2016-05-31
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
 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array());

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We get the values entered by the user
         $Reference = trim(strip_tags($_POST["sReference"]));
         if (empty($Reference))
         {
             $ContinueProcess = FALSE;
         }

         $DonationEntity = trim(strip_tags($_POST["lEntity"]));
         $FamilyID = trim(strip_tags($_POST["lFamilyID"]));
         $DonationFamilyRelationship = trim(strip_tags($_POST["lRelationship"]));
         if (empty($FamilyID))
         {
             if ($DonationFamilyRelationship > 0)
             {
                 // No family selected so no relationship
                 $DonationFamilyRelationship = 0;
             }
         }
         else
         {
             if (empty($DonationFamilyRelationship))
             {
                 // error : no relationship selected
                 $ContinueProcess = FALSE;
             }
         }

         $Lastname = trim(strip_tags($_POST["sLastname"]));
         if (empty($Lastname))
         {
             $ContinueProcess = FALSE;
         }

         $Firstname = trim(strip_tags($_POST["sFirstname"]));
         if (empty($Firstname))
         {
             $ContinueProcess = FALSE;
         }

         $Address = trim(strip_tags($_POST["sAddress"]));
         if (empty($Address))
         {
             $ContinueProcess = FALSE;
         }

         $Phone = trim(strip_tags($_POST["sPhoneNumber"]));

         // We get the town
         $TownID = trim(strip_tags($_POST["lTownID"]));
         if ($TownID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         $MailEmail = trim(strip_tags($_POST["sMainEmail"]));
         if (!empty($MailEmail))
         {
             if (!isValideEmailAddress($MailEmail))
             {
                 // Wrong e-mail
                 $ContinueProcess = FALSE;
             }
         }

         $SecondEmail = trim(strip_tags($_POST["sSecondEmail"]));
         if (!empty($SecondEmail))
         {
             if (!isValideEmailAddress($SecondEmail))
             {
                 // Wrong e-mail
                 $ContinueProcess = FALSE;
             }
         }

         $PaymentMode = strip_tags($_POST["lPaymentMode"]);
         $BankID = strip_tags($_POST["lBankID"]);

         $sCheckNb = trim(strip_tags($_POST["sCheckNb"]));
         if (in_array($PaymentMode, $CONF_PAYMENTS_MODES_BANK_REQUIRED))
         {
             if (($BankID <= 0) || (empty($sCheckNb)))
             {
                 $ContinueProcess = FALSE;
             }
         }

         $fAmount = (float)trim(strip_tags($_POST["fAmount"]));
         if ((empty($fAmount)) || ($fAmount <= 0.0))
         {
             $ContinueProcess = FALSE;
         }

         // We have to convert the payment receipt date in english format (format used in the database)
         $DonationReceptionDate = nullFormatText(formatedDate2EngDate($_POST["donationDate"]), "NULL");
         if (empty($DonationReceptionDate))
         {
             $ContinueProcess = FALSE;
         }

         $DonationType = trim(strip_tags($_POST["lDonationType"]));
         $DonationNature = trim(strip_tags($_POST["lDonationNature"]));
         $Reason = trim(strip_tags($_POST["sReason"]));

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // We can create the new donation
             $DonationID = dbAddDonation($DbCon, $Reference, $Lastname, $Firstname, $Address, $TownID, $DonationReceptionDate,
                                         $fAmount, $DonationType, $DonationNature, $PaymentMode, $BankID, $sCheckNb,
                                         $DonationEntity, $FamilyID, $DonationFamilyRelationship, $MailEmail, $SecondEmail, $Phone,
                                         $Reason);

             if ($DonationID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_DONATION, EVT_SERV_DONATION, EVT_ACT_CREATE, $_SESSION['SupportMemberID'], $DonationID);

                 // The donation is added
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = "$LANG_CONFIRM_DONATION_ADDED ($DonationID)";
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "UpdateDonation.php?Cr=".md5($DonationID)."&Id=$DonationID"; // For the redirection
             }
             else
             {
                 // The donation can't be added
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_ADD_DONATION;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = 'CreateDonation.php?'.$QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($Reference))
             {
                 // The reference is empty
                 $ConfirmationSentence = $LANG_ERROR_DONATION_REFERENCE;
             }
             elseif ((!empty($FamilyID)) && (empty($DonationFamilyRelationship)))
             {
                 // Selected family but no relationship selected
                 $ConfirmationSentence = $LANG_ERROR_DONATION_RELATIONSHIP;
             }
             elseif (empty($Lastname))
             {
                 // The lastname is empty
                 $ConfirmationSentence = $LANG_ERROR_DONATION_LASTNAME;
             }
             elseif (empty($Firstname))
             {
                 // The firstname is empty
                 $ConfirmationSentence = $LANG_ERROR_DONATION_FIRSTNAME;
             }
             elseif ($TownID == 0)
             {
                 // No town
                 $ConfirmationSentence = $LANG_ERROR_TOWN;
             }
             elseif ((!empty($MailEmail)) && (!isValideEmailAddress($MailEmail)))
             {
                 // The mail e-mail is wrong
                 $ConfirmationSentence = $LANG_ERROR_WRONG_MAIN_EMAIL;
             }
             elseif ((!empty($SecondEmail)) && (!isValideEmailAddress($SecondEmail)))
             {
                 // The second e-mail is wrong
                 $ConfirmationSentence = $LANG_ERROR_WRONG_SECOND_EMAIL;
             }
             elseif ((empty($fAmount)) || ($fAmount <= 0.0))
             {
                 // The donation value is wrong
                 $ConfirmationSentence = $LANG_ERROR_DONATION_AMOUNT;
             }
             elseif ((in_array($PaymentMode, $CONF_PAYMENTS_MODES_BANK_REQUIRED)) && ($BankID <= 0))
             {
                 // No selected bank
                 $ConfirmationSentence = $LANG_ERROR_PAYMENT_BANK;
             }
             elseif ((in_array($PaymentMode, $CONF_PAYMENTS_MODES_BANK_REQUIRED)) && (empty($sCheckNb)))
             {
                 // No check number
                 $ConfirmationSentence = $LANG_ERROR_PAYMENT_CHECK_NB;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = 'CreateDonation.php?'.$QUERY_STRING; // For the redirection
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
         $UrlParameters = 'CreateDonation.php?'.$QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the CreateDonation.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = 'CreateDonation.php?'.$QUERY_STRING; // For the redirection
 }

 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      '',
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/$UrlParameters', $CONF_TIME_LAG)"
                     );
 openWebPage();

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu(1);

 // Content of the web page
 openArea('id="content"');

 // Display the "Cooperation" and the "parameters" contextual menus if the supporter isn't logged, an empty contextual menu otherwise
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("cooperation", 1, Coop_CreateDonation);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
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