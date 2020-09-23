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
 * Support module : display the form to generate bills of a year to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-03-26
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Include the stats library
 include_once('../Stats/StatsLibrary.php');

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

 // To take into account the year to generate the annaul bill of a year for all families
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
         $Year = date("Y", strtotime("now"));  // Current
     }
 }

 // We check if the "send mail" checkbox is checked
 $bChkSendmail = NULL;
 if (isset($_POST["chkSendMail"]))
 {
     $bChkSendmail = TRUE;
 }
 elseif (isset($_GET["chkSendMail"]))
 {
     $bChkSendmail = TRUE;
 }
 elseif (!empty($_POST["bGenerate"]))
 {
     // Checkbox "send mail" not checked
     $bChkSendmail = FALSE;
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
 if (!empty($_POST["bGenerate"]))
 {
     // We get the monthly bills for each family, for the selected school year,
     // next, we generate the pdf for each annual bill,
     // then we send each pdf annual bill to the family by mail
     // finaly, we generate one pdf for the logged supporter with all annual bills

     // So, we get the monthly bills of the selected school year
     $StartDate = "$Year-01-01";
     if (isset($CONF_SCHOOL_YEAR_START_DATES[$Year]))
     {
         $StartDate = $CONF_SCHOOL_YEAR_START_DATES[$Year];
     }

     $EndDate = date("Y-m-t", strtotime("$Year-$CONF_SCHOOL_YEAR_LAST_MONTH-01"));
     $ArrayMonthlyBills = getBills($DbCon, $StartDate, $EndDate, 'FamilyLastname, BillForDate', PLANNING_BETWEEN_DATES);

     if ((isset($ArrayMonthlyBills['FamilyID'])) && (!empty($ArrayMonthlyBills['FamilyID'])))
     {
         // ******** STEP 1 : we generate the annual bill for each family in the database ********
         $AnnualBillDate = date('Y-m-d');
         $PreviousFamilyID = NULL;

         $ArrayAnnualBills = array();
         foreach($ArrayMonthlyBills['FamilyID'] as $f => $FamilyID)
         {
             if ((is_null($PreviousFamilyID)) || ($FamilyID != $PreviousFamilyID))
             {
                 $PreviousFamilyID = $FamilyID;
                 $ArrayAnnualBills[$FamilyID] = array(
                                                      'FamilyID' => $FamilyID,
                                                      'FamilyLastname' => $ArrayMonthlyBills['FamilyLastname'][$f],
                                                      'FamilyMainEmail' => $ArrayMonthlyBills['FamilyMainEmail'][$f],
                                                      'FamilySecondEmail' => $ArrayMonthlyBills['FamilySecondEmail'][$f],
                                                      'FamilyBalance' => -1 * getTableFieldValue($DbCon, 'Families', $FamilyID, 'FamilyBalance'),
                                                      'Year' => $Year,
                                                      'BillID' => array()
                                                     );
             }

             $ArrayAnnualBills[$FamilyID]['BillID'][] = $ArrayMonthlyBills['BillID'][$f];
         }

         // ******** STEP 2 : we generate each annul bill in PDF and send it to the family by e-mail ********
         // We get bills of the year/month
         if (!empty($ArrayAnnualBills))
         {
             $FileSuffix = formatFilename($Year);
             foreach($ArrayAnnualBills as $FamilyID => $ArrayData)
             {
                 // Generate the bill in HTML/CSS, then we convert the HTML/CSS file to PDF
                 $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_FILENAME."$FileSuffix.html";

                 @unlink($HTMLFilename);
                 printDetailsAnnualBillForm($DbCon, $ArrayData, "GenerateAnnualBills.php", $HTMLFilename);
                 if (file_exists($HTMLFilename))
                 {
                     $PDFFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_FILENAME."$FileSuffix.pdf";

                     // Generate the PDF
                     @unlink($PDFFilename);
                     if (html2pdf($HTMLFilename, $PDFFilename, 'portrait', ANNUAL_BILL_DOCTYPE))
                     {
                         // Delete the HTML file
                         unlink($HTMLFilename);

                         // Send the PDF by e-mail if template defined and checkbox "send mail" checked
                         if (($bChkSendmail) && (!empty($CONF_BILLS_FAMILIES_ANNUAL_NOTIFICATION)))
                         {
                             // Subject of the mail
                             $EmailSubject = "$LANG_BILL_ANNUAL_EMAIL_SUBJECT $Year";

                             if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_BILL]))
                             {
                                 $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_BILL].$EmailSubject;
                             }

                             // We define the content of the mail
                             $ReplaceInTemplate = array(
                                                        array(
                                                              "{LANG_FAMILY_LASTNAME}", "{FamilyLastname}", "{LANG_BILL_FOR_DATE}",
                                                              "{BillForDate}", "{LANG_YEAR}", "{Year}"
                                                             ),
                                                        array(
                                                              $LANG_FAMILY_LASTNAME, $ArrayData['FamilyLastname'],
                                                              $LANG_BILL_FOR_DATE, $AnnualBillDate, ucfirst($LANG_YEAR), $Year
                                                             )
                                                       );

                             // Set the PDF file in attachment
                             $ArrayPDF = array($PDFFilename);

                             // We define the mailing-list
                             $MailingList["to"] = array($ArrayData['FamilyMainEmail']);
                             if (!empty($ArrayData['FamilySecondEmail']))
                             {
                                 $MailingList["to"][] = $ArrayData['FamilySecondEmail'];
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
                             $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $CONF_BILLS_FAMILIES_NOTIFICATION,
                                                       $ReplaceInTemplate, $ArrayPDF);
                         }
                     }
                 }
             }

             // ******** STEP 3 : we generate one PDF with all annual bills of the year ********
             $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_GLOBAL_ANNUAL_FILENAME."$FileSuffix.html";

             @unlink($HTMLFilename);
             printDetailsSeveralAnnualBillsForm($DbCon, $ArrayAnnualBills, "GenerateAnnualBills.php", $HTMLFilename);
             if (file_exists($HTMLFilename))
             {
                 $PDFFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_GLOBAL_ANNUAL_FILENAME."$FileSuffix.pdf";

                 // To remove a previous PDF file
                 @unlink($PDFFilename);

                 // Generate the PDF
                 if (html2pdf($HTMLFilename, $PDFFilename, 'portrait', ALL_ANNUAL_BILLS_DOCTYPE))
                 {
                     // Delete the HTML file
                     unlink($HTMLFilename);

                     // Create link to download the PDF containing all bills of the year
                     if (file_exists($PDFFilename))
                     {
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

 // Display the "Canteen" and the "parameters" contextual menus if the supporter isn't logged, an empty contextual menu otherwise
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("canteen", 1, Canteen_GenerateAnnualBill);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_GENERATE_ANNUAL_BILLS_PAGE_TITLE, 2);

 // We generate the bills of the selected year and month
 openParagraph();
 displayStyledText($LANG_SUPPORT_GENERATE_ANNUAL_BILLS_PAGE_INTRODUCTION." $Year.", "");
 closeParagraph();

 // We display the form
 displayGenerateAnnualBillsForm($DbCon, "GenerateAnnualBills.php", $Year, $CONF_ACCESS_APPL_PAGES[FCT_BILL], $bChkSendmail);

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