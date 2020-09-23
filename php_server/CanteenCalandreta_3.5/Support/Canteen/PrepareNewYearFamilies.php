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
 * Support module : display the form to prepare activated families to the next school year
 * to the logged supporter (ex : init the annual contribution)
 *
 * @author Christophe Javouhey
 * @version 3.4
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2019-07-16 : v3.3. Taken into account the FamilyAnnualContributionBalance field to split payments for bills
 *                    and payments for annual contributions
 *     - 2019-11-28 : v3.4. Init $Balance at 0.0 and taken into account $CONF_CONTRIBUTIONS_RESET_MONTHLY_CONTRIBUTION_MODE
 *
 * @since 2014-08-01
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
                                      'CONF_CLASSROOMS',
                                      'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                      'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                      'CONF_CANTEEN_PRICES',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

 //################################ FORM PROCESSING ##########################
 $sErrorMsg = '';
 $sConfirmationMsg = '';
 $iNbActivatedFamilies = 0;
 $iNbDesactivatedFamilies = 0;

 // Compute the next school year
 $CurrentDate = date('Y-m-d');
 $CurrentStamp = strtotime($CurrentDate);
 $SchoolYear = getSchoolYear($CurrentDate);
 $StartDateSchoolYear = getSchoolYearStartDate($SchoolYear);
 if (isset($CONF_SCHOOL_YEAR_START_DATES[$SchoolYear]))
 {
     if (($CurrentStamp >= strtotime($StartDateSchoolYear)) && ($CurrentStamp <= strtotime($CONF_SCHOOL_YEAR_START_DATES[$SchoolYear])))
     {
         $NextSchoolYear = $SchoolYear;
     }
     else
     {
         $NextSchoolYear = $SchoolYear + 1;
     }
 }
 else
 {
     $NextSchoolYear = $SchoolYear;
 }

 if (!empty($_POST["bSubmit"]))
 {
     if (isset($CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$NextSchoolYear]))
     {
         // Annual contribution defined for the given school year and 1 power
         if ((isset($_POST['chkFamily'])) && (count($_POST['chkFamily']) > 0))
         {
             foreach($_POST['chkFamily'] as $f => $FamilyID)
             {
                 // Get info about the current Family
                 $FamilyRecord = getTableRecordInfos($DbCon, 'Families', $FamilyID);
                 if (isset($FamilyRecord['FamilyID']))
                 {
                     // We check if we must reset the MonthlyContributionMode of the family
                     if ($CONF_CONTRIBUTIONS_RESET_MONTHLY_CONTRIBUTION_MODE)
                     {
                         // Yes, we reset with the default mode
                         dbUpdateFamily($DbCon, $FamilyID, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, MC_DEFAULT_MODE,
                                        NULL, NULL, NULL, NULL, NULL);
                     }

                     // How many contributions?
                     $NbContributions = $FamilyRecord['FamilyNbMembers'] + $FamilyRecord['FamilyNbPoweredMembers'];

                     // Price off for annual contribution?
                     $AnnualPriceOff = 0;
                     if ($FamilyRecord['FamilySpecialAnnualContribution'] == 1)
                     {
                         // Special family : we remove 1 powered number to compute the annual contribution
                         if ($FamilyRecord['FamilyNbPoweredMembers'] > 0)
                         {
                             $AnnualPriceOff = $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$NextSchoolYear][1];
                         }
                     }

                     // Compute the price of the annual contribution
                     $Balance = 0.0;
                     if (isset($CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$NextSchoolYear][$NbContributions]))
                     {
                         $Balance = (-1.00 * $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$NextSchoolYear][$NbContributions]) + $AnnualPriceOff;
                     }

                     // Update the balance of the family with the annual contribution
                     updateFamilyAnnualContributionBalance($DbCon, $FamilyID, $Balance);

                     // Log event
                     logEvent($DbCon, EVT_FAMILY, EVT_SERV_FAMILY, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $FamilyID);

                     $iNbActivatedFamilies++;
                 }
             }
         }

         $sConfirmationMsg = $LANG_SUPPORT_PREPARE_NEW_YEAR_FAMILIES_PAGE_NB_ACTIVATED_FAMILIES."<strong>$iNbActivatedFamilies</strong>\n";
     }
     else
     {
         // No annual contribution defined : no treatment
         $sErrorMsg = $LANG_SUPPORT_PREPARE_NEW_YEAR_FAMILIES_PAGE_NO_ANNUAL_CONTRIBUTION_DEFINED."\n";
     }
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

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#FamiliesList', 'Accessibility');

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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_PrepareNewYearFamilies);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_PREPARE_NEW_YEAR_FAMILIES_PAGE_TITLE, 2);

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

 // We prepare families to the next school year
 openParagraph();
 displayStyledText($LANG_SUPPORT_PREPARE_NEW_YEAR_FAMILIES_PAGE_INTRODUCTION." <strong>".($NextSchoolYear - 1)."-$NextSchoolYear</strong>.", "");
 closeParagraph();

 // We display the form to prepare families to the next school year
 displayPrepareNewYearFamiliesForm($DbCon, "PrepareNewYearFamilies.php", $NextSchoolYear, $CONF_ACCESS_APPL_PAGES[FCT_FAMILY]);

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