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
 * Support module : process the creation of a new family. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.3
 *     - 2012-07-12 : taken into account the FamilySpecialAnnualContribution field, NbNumbers can be = 0
 *     - 2013-10-09 : taken into account the FamilyMonthlyContributionMode field
 *     - 2016-06-02 : add trim() on some text fields
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2019-07-16 : taken into account the FamilyAnnualContributionBalance field to split payments for bills
 *                    and payments for annual contributions
 *
 * @since 2012-01-17
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
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS',
                                              'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                              'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                              'CONF_CANTEEN_PRICES',
                                              'CONF_NURSERY_PRICES',
                                              'CONF_NURSERY_DELAYS_PRICES'));

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We get the values entered by the user
         $Lastname = trim(strip_tags($_POST["sLastname"]));
         if (empty($Lastname))
         {
             $ContinueProcess = FALSE;
         }

         $MainEmail = trim(strip_tags($_POST["sMainEmail"]));
         if ((empty($MainEmail)) || (!isValideEmailAddress($MainEmail)))
         {
             // Wrong e-mail
             $ContinueProcess = FALSE;
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

         $NbNumbers = nullFormatText(strip_tags($_POST["sNbMembers"]), "NULL");
         if (is_Null($NbNumbers))
         {
             // By default, 1 family = 1 member
             $NbNumbers = 1;
         }
         else
         {
             // The number of members must be an integer >= 0
             if ((integer)$NbNumbers < 0)
             {
                 $ContinueProcess = FALSE;
             }
         }

         $NbPoweredNumbers = nullFormatText(strip_tags($_POST["sNbPoweredMembers"]), "NULL");
         if (is_Null($NbPoweredNumbers))
         {
             // By default, 1 family = 0 member
             $NbPoweredNumbers = 0;
         }
         else
         {
             // The number of powered members must be an integer >= 0
             if ((integer)$NbPoweredNumbers < 0)
             {
                 $ContinueProcess = FALSE;
             }
         }

         // We check if the family is a "special" family about the annual contribution
         $SpecialAnnualContribution = existedPOSTFieldValue("chkSpecialAnnualContribution", NULL);
         if (is_null($SpecialAnnualContribution))
         {
             $SpecialAnnualContribution = 0;
         }

         // Get the monthly contribution mode
         $MonthyContributionMode = strip_tags($_POST["lMonthlyContributionMode"]);

         // We get the town
         $TownID = $_POST["lTownID"];
         if ($TownID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // We have to convert the desactivation date in english format (format used in the database)
         $DesactivationDate = nullFormatText(formatedDate2EngDate($_POST["desactivationDate"]), "NULL");
         $Comment = formatText($_POST["sComment"]);

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // Compute the balance of the new family (annual contribution)
             $Balance = 0.00;
             $CurrentDate = date('Y-m-d');

             // We search the current school year
             $SchoolYear = getSchoolYear($CurrentDate);

             // How many contributions?
             $NbContributions = $NbNumbers + $NbPoweredNumbers;

             // Price off for annual contribution?
             $AnnualPriceOff = 0;
             if ($SpecialAnnualContribution == 1)
             {
                 // Special family : we remove 1 powered number to compute the annual contribution
                 if ($NbPoweredNumbers > 0)
                 {
                     $AnnualPriceOff = $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SchoolYear][1];
                 }
             }

             // Compute the price of the annual contribution
             if (isset($CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SchoolYear][$NbContributions]))
             {
                 $AnnualContributionBalance = (-1.00 * $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SchoolYear][$NbContributions]) + $AnnualPriceOff;
             }

             // Balance (for bills) of the family set to 0.00
             $Balance = 0.00;

             $FamilyID = dbAddFamily($DbCon, $CurrentDate, $Lastname, $TownID, $MainEmail, $SecondEmail, $NbNumbers, $NbPoweredNumbers,
                                     $Balance, $Comment, $DesactivationDate, $SpecialAnnualContribution, $MonthyContributionMode, $AnnualContributionBalance);

             if ($FamilyID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_FAMILY, EVT_SERV_FAMILY, EVT_ACT_CREATE, $_SESSION['SupportMemberID'], $FamilyID);

                 // The family is added
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = "$LANG_CONFIRM_FAMILY_ADDED ($FamilyID)";
                 $ConfirmationStyle = "ConfirmationMsg";
             }
             else
             {
                 // The family can't be added
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_ADD_FAMILY;
                 $ConfirmationStyle = "ErrorMsg";
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($Lastname))
             {
                 // The lastname is empty
                 $ConfirmationSentence = $LANG_ERROR_FAMILY_LASTNAME;
             }
             elseif ($TownID == 0)
             {
                 // No town
                 $ConfirmationSentence = $LANG_ERROR_TOWN;
             }
             elseif ((empty($MainEmail)) || (!isValideEmailAddress($MainEmail)))
             {
                 // The main e-mail is empty or wrong
                 $ConfirmationSentence = $LANG_ERROR_WRONG_MAIN_EMAIL;
             }
             elseif ((!empty($SecondEmail)) && (!isValideEmailAddress($SecondEmail)))
             {
                 // The second e-mail is wrong
                 $ConfirmationSentence = $LANG_ERROR_WRONG_SECOND_EMAIL;
             }
             elseif ((!empty($NbMembers)) && ((integer)$NbMembers < 1))
             {
                 // Wrong nb members
                 $ConfirmationSentence = $LANG_ERROR_WRONG_NB_MEMBERS;
             }
             elseif ((!empty($NbPoweredNumbers)) && ((integer)$NbPoweredNumbers < 0))
             {
                 // Wrong nb powered members
                 $ConfirmationSentence = $LANG_ERROR_WRONG_NB_POWERED_MEMBERS;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
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
     }
 }
 else
 {
     // The supporter doesn't come from the CreateFamily.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array()
                     );
 openWebPage();

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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_CreateFamily);
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

 // Display the link to go on creating the family
 if ($ConfirmationStyle == 'ConfirmationMsg')
 {
     openParagraph('toolbar');
     displayStyledLinkText($LANG_SUPPORT_CREATE_FAMILY_PAGE_GOON_CREATING_FAMILY, "UpdateFamily.php?Cr=".md5($FamilyID)."&amp;Id=$FamilyID",
                           'Purposes', $LANG_SUPPORT_CREATE_FAMILY_PAGE_GOON_CREATING_FAMILY_TIP, '_blank');
     closeParagraph();
 }

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