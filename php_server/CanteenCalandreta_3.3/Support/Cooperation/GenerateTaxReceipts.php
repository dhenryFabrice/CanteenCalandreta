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
 * Support module : display the form to generate tax receipts of a year to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2016-06-08
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

 // Include the stats library
 include_once('../Stats/StatsLibrary.php');

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array());

 // To take into account the year and month to generate the bills
 if (!empty($_POST["lYear"]))
 {
     $Year = trim(strip_tags($_POST["lYear"]));
 }
 else
 {
     if (!empty($_GET["lYear"]))
     {
         $Year = trim(strip_tags($_GET["lYear"]));
     }
     else
     {
         $Year = date("Y", strtotime("last year"));  // Last year
     }
 }

 if ($Year < 2003)
 {
     $Year = (integer)(date("Y"));
 }

 if ($Year > 2037)
 {
     $Year = (integer)(date("Y"));
 }

 //################################ FORM PROCESSING ##########################
 $sErrorMsg = '';
 $sConfirmationMsg = '';
 $iNbTaxReceiptsToSend = 0;
 $iNbMailsToSend = 0;
 $iNbMailsSent = 0;
 $iNbTotalTaxReceipts = 0;
 $iNbJobsCreated = 0;

 if (!empty($_POST["bGenerate"]))
 {
     // We generate the tax receipt for each donation, for the selected year.
     // Next, we generate the pdf for each tax receipt,
     // then we send each pdf tax receipt to the family by mail if e-mail address is set
     // finaly, we generate one pdf for the logged supporter with all tax receipts
     // about donations without e-mail address

     // So, we get donations for the selected year
     $ArrayParams = array(
                          'StartDate' => array(">=", "$Year-01-01"),
                          'EndDate' => array("<=", "$Year-12-31"),
                          'DonationType' => $CONF_DONATION_TAX_RECEIPT_FOR_TYPES
                         );

     $ArrayDonations = dbSearchDonations($DbCon, $ArrayParams, "DonationReference", 1, 0);

     if ((isset($ArrayDonations['DonationID'])) && (!empty($ArrayDonations['DonationID'])))
     {
         $iNbTotalTaxReceipts = count($ArrayDonations['DonationID']);
         $ArrayDonationsWithoutEmails = array();
         $FileSuffix = $Year;

         // ******** STEP 1 : we generate the tax receipt of donations with an e-mail address ********
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

             foreach($ArrayDonations['DonationID'] as $f => $DonationID)
             {
                 // We define the recipients
                 $ArrayRecipients = array();
                 if (!empty($ArrayDonations['DonationMainEmail'][$f]))
                 {
                     $ArrayRecipients[] = $ArrayDonations['DonationMainEmail'][$f];
                 }

                 if (!empty($ArrayDonations['DonationSecondEmail'][$f]))
                 {
                     $ArrayRecipients[] = $ArrayDonations['DonationSecondEmail'][$f];
                 }

                 if ((empty($ArrayRecipients)) || (!$bEmailTemplateDefined))
                 {
                     // No e-mail template to send tax receipt by e-mail or donation without e-mail :
                     // we send the tax receipt by mail (paper)
                     $ArrayDonationsWithoutEmails[] = $DonationID;
                 }
                 else
                 {
                     // We can send the tax receipt by e-mail
                     $iNbMailsToSend++;

                     // Generate the tax receipt in HTML/CSS, then we convert the HTML/CSS file to PDF
                     $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_DONATION_TAX_RECEIPTS_PRINT_FILENAME."$FileSuffix-$DonationID.html";
                     @unlink($HTMLFilename);

                     printDetailsDonationForm($DbCon, $DonationID, "GenerateTaxReceipts.php", $HTMLFilename);
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

                             // Send the tax receipt by e-mail
                             // Subject of the mail
                             $EmailSubject = "$LANG_TAX_RECEIPT_EMAIL_SUBJECT $Year";
                             if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_DONATION]))
                             {
                                 $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_DONATION].$EmailSubject;
                             }

                             $DonationValue = str_replace(array('.'), array(','), $ArrayDonations['DonationValue'][$f])
                                              .' '.$CONF_PAYMENTS_UNIT;
                             $DonationReceptionDate = date($CONF_DATE_DISPLAY_FORMAT,
                                                           strtotime($ArrayDonations['DonationReceptionDate'][$f]));

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

                             // We send the e-mail : now or after ?
                             if ((isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL])) && (isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_DONATION]))
                                 && (count($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_DONATION]) == 2))
                             {
                                 // The message is delayed (job)
                                 $bIsEmailSent = FALSE;

                                 // Compute the planned date/time
                                 if ($iNbJobsCreated == 0)
                                 {
                                     // First job
                                     $PlannedDateStamp = strtotime("+1 min", strtotime("now"));
                                 }
                                 elseif (($iNbJobsCreated % $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_DONATION][JobSize]) == 0)
                                 {
                                     // New planned date for jobs
                                     // Compute date/time for the next job
                                     $PlannedDateStamp += $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_DONATION][DelayBetween2Jobs] * 60;
                                 }

                                 $ArrayJobParams = array(
                                                         array(
                                                               "JobParameterName" => "mailinglist",
                                                               "JobParameterValue" => base64_encode(serialize($MailingList))
                                                              ),
                                                         array(
                                                               "JobParameterName" => "subject",
                                                               "JobParameterValue" => $EmailSubject
                                                              ),
                                                         array(
                                                               "JobParameterName" => "template-name",
                                                               "JobParameterValue" => $TemplateToUse
                                                              ),
                                                         array(
                                                               "JobParameterName" => "replace-in-template",
                                                               "JobParameterValue" => base64_encode(serialize($ReplaceInTemplate))
                                                              ),
                                                         array(
                                                               "JobParameterName" => "attachment",
                                                               "JobParameterValue" => base64_encode(serialize($ArrayPDF))
                                                              )
                                                        );

                                 // Create the job to send a delayed e-mail
                                 $JobID = dbAddJob($DbCon, $_SESSION['SupportMemberID'], JOB_EMAIL,
                                                   date('Y-m-d H:i:s', $PlannedDateStamp), NULL, 0, NULL, $ArrayJobParams);

                                 if ($JobID > 0)
                                 {
                                     $iNbJobsCreated++;
                                     $bIsEmailSent = TRUE;
                                 }
                             }
                             else
                             {
                                 // We can send the e-mail
                                 $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate,
                                                           $ArrayPDF);
                             }

                             if ($bIsEmailSent)
                             {
                                 $iNbMailsSent++;
                             }
                         }
                     }
                 }
             }
         }

         if ($iNbMailsToSend > 0)
         {
             $sConfirmationMsg = ucfirst($LANG_SUPPORT_GENERATE_DONATION_TAX_RECEIPTS_PAGE_NB_GENERATED_TAX_RECEIPTS)." : $iNbMailsToSend, "
                                         .$LANG_SUPPORT_GENERATE_MONTHLY_BILLS_PAGE_NB_SENT_EMAILS." : $iNbMailsSent.";
         }

         // ******** STEP 2 : we generate the tax receipts for donations without an e-mail address and sent by mail (paper) ********
         if (!empty($ArrayDonationsWithoutEmails))
         {
             $iNbTaxReceiptsToSend = count($ArrayDonationsWithoutEmails);

             // Several donations are concerned
             $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_DONATION_TAX_RECEIPTS_WITHOUT_EMAIL_PRINT_FILENAME."$FileSuffix.html";
             @unlink($HTMLFilename);
             printDetailsSeveralDonationsForm($DbCon, $ArrayDonationsWithoutEmails, "GenerateTaxReceipts.php", $HTMLFilename);

             if (file_exists($HTMLFilename))
             {
                 $PDFFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_DONATION_TAX_RECEIPTS_WITHOUT_EMAIL_PRINT_FILENAME."$FileSuffix.pdf";

                 // Generate the PDF
                 @unlink($PDFFilename);
                 if (html2pdf($HTMLFilename, $PDFFilename, 'landscape', ALL_DONATION_TAX_RECEIPT_DOCTYPE,
                              $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Year]))
                 {
                     // Delete the HTML file
                     unlink($HTMLFilename);

                     // Create link to download the PDF containing several tax receipts of the year
                     if (file_exists($PDFFilename))
                     {
                         if (!empty($sConfirmationMsg))
                         {
                             $sConfirmationMsg .= generateBR(1);
                         }

                         $sConfirmationMsg .= ucfirst($LANG_SUPPORT_GENERATE_DONATION_TAX_RECEIPTS_PAGE_NB_TAX_RECEIPTS_TO_SEND)
                                              ." : $iNbTaxReceiptsToSend.";
                         $sConfirmationMsg .= generateBR(2).generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"],
                                                                                   $GLOBALS["CONF_EXPORT_DIRECTORY"].$CONF_DONATION_TAX_RECEIPTS_WITHOUT_EMAIL_PRINT_FILENAME."$FileSuffix.pdf",
                                                                                   "", $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank");

                         // Force the download of the PDF file
                         /*$PDFsize = filesize($PDFFilename);
                         $PDFTmpFilename = basename($PDFFilename);
                         header("Content-Type: application/octet-stream");
                         header("Content-Length: $PDFsize");
                         header("Content-disposition: attachment; filename=$PDFTmpFilename");
                         header("Pragma: no-cache;");
                         header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
                         header("Expires: 0");
                         readfile($PDFFilename);*/
                     }
                 }
             }
         }
     }
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
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#lYear', 'Accessibility');

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

     displaySupportMemberContextualMenu("cooperation", 1, Coop_GenerateTaxReceipt);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_GENERATE_DONATION_TAX_RECEIPTS_PAGE_TITLE, 2);

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

 // We generate the tax receipts of the selected year
 openParagraph();
 displayStyledText($LANG_SUPPORT_GENERATE_DONATION_TAX_RECEIPTS_PAGE_INTRODUCTION." <strong>$Year</strong>.", "");
 closeParagraph();

 // We display the form to generate tax receipts
 displayGenerateDonationTaxReceiptsForm($DbCon, "GenerateTaxReceipts.php", $Year, $CONF_ACCESS_APPL_PAGES[FCT_DONATION]);

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