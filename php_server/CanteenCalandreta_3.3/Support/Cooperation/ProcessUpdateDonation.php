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
 * Support module : process the update of a donation. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2016-06-03
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Include the PDF libraries
 require_once('../../Common/FPDF/fpdf.php');
 require_once('../../Common/FPDI/fpdi.php');
 require_once('../../Common/Num2Letters/Nuts.php');

 // To take into account the crypted and no-crypted donation ID
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
         loadDbConfigParameters($DbCon, array());

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We identify the donation
         if (isExistingDonation($DbCon, $Id))
         {
             // The donation exists
             $DonationID = $Id;
         }
         else
         {
             // ERROR : the donation doesn't exist
             $ContinueProcess = FALSE;
         }

         // Reference not updatable !!!

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

         $Lastname = existedPOSTFieldValue("sLastname", NULL);
         if (!is_Null($Lastname))
         {
             $Lastname = trim(strip_tags($_POST["sLastname"]));
             if (empty($Lastname))
             {
                 $ContinueProcess = FALSE;
             }
         }

         $Firstname = existedPOSTFieldValue("sFirstname", NULL);
         {
             $Firstname = trim(strip_tags($_POST["sFirstname"]));
             if (empty($Firstname))
             {
                 $ContinueProcess = FALSE;
             }
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
             // We can update the donation with the new values
             $DonationID = dbUpdateDonation($DbCon, $DonationID, NULL, $Lastname, $Firstname, $Address, $TownID,
                                            $DonationReceptionDate, $fAmount, $DonationType, $DonationNature, $PaymentMode, $BankID,
                                            $sCheckNb, $DonationEntity, $FamilyID, $DonationFamilyRelationship, $MailEmail,
                                            $SecondEmail, $Phone, $Reason);
             if ($DonationID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_DONATION, EVT_SERV_DONATION, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $DonationID);

                 // The donation is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_DONATION_UPDATED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($DonationID)."&Id=$DonationID"; // For the redirection
             }
             else
             {
                 // The donation can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_DONATION;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;
             if ((!empty($FamilyID)) && (empty($DonationFamilyRelationship)))
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
 elseif (!empty($_POST["bGenerate"]))
 {
     // To generate the tax receipt of the donation if exists
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array());

         // We identify the donation
         if (isExistingDonation($DbCon, $Id))
         {
             // The donation exists : we can genarate the tax receipt
             $DonationID = $Id;
             $RecordDonation = getTableRecordInfos($DbCon, 'Donations', $DonationID);

             $Year = date('Y', strtotime($RecordDonation['DonationReceptionDate']));
             $FileSuffix = $Year;

             // We check if the parameters of th tax receipt exist for this year
             if ((isset($CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Year])) && isset($CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Year][Template])
                 && (!empty($CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Year][Template])))
             {
                 $NotificationType = 'TaxReceipt';
                 $bEmailTemplateDefined = TRUE;
                 if ((!isset($CONF_DONATION_NOTIFICATIONS[$NotificationType]))
                     || ((isset($CONF_DONATION_NOTIFICATIONS[$NotificationType]))
                         && (empty($CONF_DONATION_NOTIFICATIONS[$NotificationType][Template]))))
                 {
                     // No e-mail template to send tax receipt defined
                     $bEmailTemplateDefined = FALSE;
                 }

                 // Generate the tax receipt in HTML/CSS, then we convert the HTML/CSS file to PDF
                 $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_DONATION_TAX_RECEIPTS_PRINT_FILENAME."$FileSuffix-$DonationID.html";
                 @unlink($HTMLFilename);

                 printDetailsDonationForm($DbCon, $DonationID, "UpdateDonation.php", $HTMLFilename);
                 if (file_exists($HTMLFilename))
                 {
                     $PDFFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_DONATION_TAX_RECEIPTS_PRINT_FILENAME."$FileSuffix-$DonationID.pdf";

                     // Generate the PDF
                     @unlink($PDFFilename);
                     if (html2pdf($HTMLFilename, $PDFFilename, 'portrait', DONATION_TAX_RECEIPT_DOCTYPE,
                                  $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Year]))
                     {
                         // Delete the HTML file
                         unlink($HTMLFilename);

                         // We define the recipients
                         $ArrayRecipients = array();
                         if (!empty($RecordDonation['DonationMainEmail']))
                         {
                             $ArrayRecipients[] = $RecordDonation['DonationMainEmail'];
                         }

                         if (!empty($RecordDonation['DonationSecondEmail']))
                         {
                             $ArrayRecipients[] = $RecordDonation['DonationSecondEmail'];
                         }

                         if ((empty($ArrayRecipients)) || (!$bEmailTemplateDefined))
                         {
                             // No e-mail template to send tax receipt by e-mail or donation without e-mail :
                             // we send the tax receipt by mail (paper)
                             // Force the download of the PDF file
                             $PDFsize = filesize($PDFFilename);
                             $PDFTmpFilename = basename($PDFFilename);
                             header("Content-Type: application/octet-stream");
                             header("Content-Length: $PDFsize");
                             header("Content-disposition: attachment; filename=$PDFTmpFilename");
                             header("Pragma: no-cache;");
                             header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
                             header("Expires: 0");
                             readfile($PDFFilename);
                         }
                         else
                         {
                             // We can send the tax receipt by e-mail
                             // Subject of the mail
                             $EmailSubject = "$LANG_TAX_RECEIPT_EMAIL_SUBJECT $Year";
                             if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_DONATION]))
                             {
                                 $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_DONATION].$EmailSubject;
                             }

                             $DonationValue = str_replace(array('.'), array(','), $RecordDonation['DonationValue'])
                                              .' '.$CONF_PAYMENTS_UNIT;
                             $DonationReceptionDate = date($CONF_DATE_DISPLAY_FORMAT,
                                                           strtotime($RecordDonation['DonationReceptionDate']));

                             // We define the content of the mail
                             $TemplateToUse = $CONF_DONATION_NOTIFICATIONS[$NotificationType][Template];
                             $ReplaceInTemplate = array(
                                                        array(
                                                              "{Year}", "{DonationValue}", "{DonationReceptionDate}"
                                                             ),
                                                        array(
                                                              $Year, $DonationValue, $DonationReceptionDate
                                                             )
                                                       );

                             // Set the PDF file in attachment
                             $ArrayPDF = array($PDFFilename);

                             // We define the mailing-list
                             $MailingList["to"] = $ArrayRecipients;
                             if ((isset($CONF_DONATION_NOTIFICATIONS[$NotificationType][To]))
                                 && (!empty($CONF_DONATION_NOTIFICATIONS[$NotificationType][To])))
                             {
                                 $MailingList["to"] = array_merge($MailingList["to"], $CONF_DONATION_NOTIFICATIONS[$NotificationType][To]);
                             }

                             if ((isset($CONF_DONATION_NOTIFICATIONS[$NotificationType][Cc]))
                                 && (!empty($CONF_DONATION_NOTIFICATIONS[$NotificationType][Cc])))
                             {
                                 $MailingList["bcc"] = $CONF_DONATION_NOTIFICATIONS[$NotificationType][Cc];
                             }

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
                             $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate,
                                                       $ArrayPDF);
                         }
                     }
                 }
             }

             unset($RecordDonation);

             $ConfirmationCaption = $LANG_CONFIRMATION;
             $ConfirmationSentence = $LANG_CONFIRM_DONATION_TAX_RECEIPT_REGENERATED;
             $ConfirmationStyle = "ConfirmationMsg";
             $UrlParameters = "Cr=".md5($DonationID)."&Id=$DonationID"; // For the redirection
         }
         else
         {
             // ERROR : the donation doesn't exist
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_WRONG_DONATION_ID;
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
     // The supporter doesn't come from the UpdateDonation.php page
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/UpdateDonation.php?$UrlParameters', $CONF_TIME_LAG)"
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