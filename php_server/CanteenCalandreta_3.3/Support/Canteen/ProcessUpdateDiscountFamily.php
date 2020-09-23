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
 * Support module : process the update of a discount/increase for a family. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.3
 *     - 2019-06-21 : patch a bug about some not existing fields in the form
 *
 * @since 2017-10-06
 */

 // Include the graphic primitives library
  require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted disocunt/increase ID
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
 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
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

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We identify the discount/increase of the family
         if (isExistingDiscountFamily($DbCon, $Id))
         {
             // The discount/increase exists
             $DiscountFamilyID = $Id;

             // Get previous amount of the discount/increase
             $RecordOldDiscount = getTableRecordInfos($DbCon, "DiscountsFamilies", $DiscountFamilyID);
         }
         else
         {
             // ERROR : the discount/increase doesn't exist
             $ContinueProcess = FALSE;
         }

         // We get the ID of the family of the discount/increase
         $FamilyID = $_POST["hidFamilyID"];
         if ($FamilyID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // We get the values entered by the user
         $DiscountFamilyType = NULL;
         if (isset($_POST["lDiscountType"]))
         {
             $DiscountFamilyType = strip_tags($_POST["lDiscountType"]);
         }
         else
         {
             $DiscountFamilyType = $RecordOldDiscount['DiscountFamilyType'];
         }

         $DiscountFamilyReasonType = NULL;
         if (isset($_POST["lDiscountReasonType"]))
         {
             $DiscountFamilyReasonType = strip_tags($_POST["lDiscountReasonType"]);
         }
         else
         {
             $DiscountFamilyReasonType = $RecordOldDiscount['DiscountFamilyReasonType'];
         }

         $DiscountFamilyReason = trim(strip_tags($_POST["sReason"]));

         $fAmount = (float)trim(strip_tags($_POST["fAmount"]));
         if ((empty($fAmount)) || (abs($fAmount) == 0.00))
         {
             $ContinueProcess = FALSE;
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // Update the discount/increase
             $DiscountFamilyID = dbUpdateDiscountFamily($DbCon, $DiscountFamilyID, NULL, $FamilyID, $fAmount, $DiscountFamilyType,
                                                        $DiscountFamilyReasonType, $DiscountFamilyReason);

             if ($DiscountFamilyID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_PAYMENT, EVT_SERV_DISCOUNT, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $DiscountFamilyID);

                 // Change the balance of the family
                 $fOldDiscountAmount = $RecordOldDiscount['DiscountFamilyAmount'];
                 $fUpdateDiscountAmount = 0.00;
                 if ($fOldDiscountAmount != $fAmount)
                 {
                     $fUpdateDiscountAmount = $fOldDiscountAmount - $fAmount;
                     $fNewBalance = updateFamilyBalance($DbCon, $FamilyID, $fUpdateDiscountAmount);
                 }

                 // The discount/increase is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_DISCOUNT_UPDATED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "UpdateDiscountFamily.php?Cr=".md5($DiscountFamilyID)."&Id=$DiscountFamilyID"; // For the redirection
             }
             else
             {
                 // The discount/increase can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_DISCOUNT;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "UpdateDiscountFamily.php?".$QUERY_STRING; // For the redirection
             }

         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if ((empty($fAmount)) || (abs($fAmount) == 0.00))
             {
                 // Wrong amount
                 $ConfirmationSentence = $LANG_ERROR_DISCOUNT_AMOUNT;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = "UpdateDiscountFamily.php?".$QUERY_STRING; // For the redirection
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
         $UrlParameters = "UpdateDiscountFamily.php?".$QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the UpdateDiscountFamily.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = "UpdateDiscountFamily.php?".$QUERY_STRING; // For the redirection
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Canteen/$UrlParameters', $CONF_TIME_LAG)"
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