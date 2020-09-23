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
 * Interface module : XHTML Graphic primitives menus library used to create menus
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2012-01-12
 */


/**
 * Display the main menu at the top of the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2007-01-11 : new interface
 *
 * @since 2003-12-20
 *
 * @param $TabItemsMenu         Array of Strings      List of the items captions displayed in the main menu
 * @param $TabHREFItemsMenu     Array of Strings      List of the items links displayed in the main menu
 * @param $TabTipsItemsMenu     Array of Strings      List of the tips displayed for each item of the main menu
 */
 function displayMainMenu($TabItemsMenu, $TabHREFItemsMenu, $TabTipsItemsMenu)
 {
     // the 3 arrays must have the same number of elements
     $NbElements = count($TabItemsMenu);
     if ($NbElements == (count($TabHREFItemsMenu)) && ($NbElements == count($TabTipsItemsMenu)))
     {
         echo "\n<!-- Main menu -->\n";
         echo "<ul id=\"mainmenu\">\n";

         // Display each item of the main menu
         foreach($TabItemsMenu as $i => $CurrentValue)
         {
             echo "\t<li><a href=\"$TabHREFItemsMenu[$i]\" title=\"$TabTipsItemsMenu[$i]\" accesskey=\"".($i + 1)."\">$CurrentValue</a></li>\n";
         }

         // Close the table of the main menu
         echo "</ul>\n";

         // Display the button to destroy the session
         echo "<div id=\"quitapp\">\n";
         echo "\t<a href=\"".getRelativeUrlDepth($_SERVER["PHP_SELF"])."Common/Disconnection.php\" accesskey=\"q\" title=\"".$GLOBALS["LANG_DISCONNECTION_TIP"]."\">\n";
         echo "\t\t<img src=\"".$GLOBALS["CONF_DISCONNECTION"]."\" title=\"".$GLOBALS["LANG_DISCONNECTION_TIP"]."\" alt=\"".$GLOBALS["LANG_DISCONNECTION_TIP"]."\" />\n";
         echo "\t</a>\n";
         echo "</div>\n";
     }
 }


/**
 * Display the contextual menu in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.1
 *     - 2005-02-11 : can display separators between items
 *     - 2007-01-11 : new interface and take into account the visibility of the items
 *     - 2011-08-10 : allow an ID for the <UL> of the contextual menu
 *
 * @since 2003-12-20
 *
 * @param $Caption              String                Caption of the contextual menu
 * @param $TabItemsMenu         Array of Strings      List of the items captions displayed in the main menu
 * @param $TabHREFItemsMenu     Array of Strings      List of the items links displayed in the main menu
 * @param $TabTipsItemsMenu     Array of Strings      List of the tips displayed for each item of the main menu
 * @param $NumItemSelected      Integer               Position number of the selected item [1..n]
 * @param $MenuID               String                ID of the contextual menu (for <ul>)
 * @param $TabSeparators        Array of Integers     List of the items followed by a separator
 * @param $TabVisibilities      Array of Boolean      List of the visible and not visible items. If empty array,
 *                                                    all items are visible
 */
 function displayContextualMenu($Caption, $TabItemsMenu, $TabHREFItemsMenu, $TabTipsItemsMenu, $NumItemSelected, $MenuID = '', $TabSeparators = array(), $TabVisibilities = array())
 {
     // The 3 arrays must have the same number of elements
     $NbElements = count($TabItemsMenu);

     // We check if the array of the visibilities isn't empty
     if (count($TabVisibilities) == 0)
     {
         // We fill it with the right number of TRUE values
         $TabVisibilities = array_fill(0, $NbElements, TRUE);
     }

     if (($NbElements > 0) && ($NbElements == count($TabHREFItemsMenu))
         && ($NbElements == count($TabTipsItemsMenu)) && ($NbElements == count($TabVisibilities))
        )
     {
         // Display the caption of the contextual menu
         echo "\n<!-- Contectual menu -->\n";
         echo "<h3>$Caption</h3>\n";

         // Display each item of the contextual menu
         if (empty($MenuID))
         {
             echo "<ul>\n";
         }
         else
         {
             // Add an ID in the contextual menu <ul>
             echo "<ul id=\"$MenuID\">\n";
         }

         $bPreviousItemIsSeparator = TRUE;
         foreach($TabItemsMenu as $i => $CurrentValue)
         {
             // We check if this item is visible
             if ($TabVisibilities[$i])
             {
                 // This item is visible
                 echo '<li';
                 if ($i + 1 == $NumItemSelected)
                 {
                     // This contextual menu item is selected
                     echo ' class="selected"';
                 }

                 echo "><a href=\"$TabHREFItemsMenu[$i]\" title=\"$TabTipsItemsMenu[$i]\">$CurrentValue</a></li>\n";

                 // This displayed item isn't a separator
                 $bPreviousItemIsSeparator = FALSE;
             }

             // Display a separator between 2 contextual menu items
             // But we don't display 2 separtors one just after another
             if ((in_array($i + 1, $TabSeparators)) && (!$bPreviousItemIsSeparator))
             {
                 echo "<li class=\"separator\">&nbsp;</li>\n";
                 $bPreviousItemIsSeparator = TRUE;
             }
         }

         // Close the contextual menu
         echo "</ul>\n";
     }
 }


/**
 * Display a shortcut menu in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2007-01-22 : new interface
 *
 * @since 2004-04-04
 *
 * @param $TabItemsMenu         Array of Strings      List of the items captions displayed in the main menu
 * @param $TabHREFItemsMenu     Array of Strings      List of the items links displayed in the main menu
 * @param $TabTipsItemsMenu     Array of Strings      List of the tips displayed for each item of the main menu
 */
 function displayShortcutMenu($TabItemsMenu, $TabHREFItemsMenu, $TabTipsItemsMenu)
 {
     // the 3 arrays must have the same number of elements
     $NbElements = count($TabItemsMenu);
     if ($NbElements == (count($TabHREFItemsMenu)) && ($NbElements == count($TabTipsItemsMenu)))
     {
         // Display each item of the shortcut menu
         echo "<!-- Shortcut menu //-->\n";
         echo "<ul class=\"shortcutmenu\">\n";

         foreach($TabItemsMenu as $i => $CurrentValue)
         {
             echo "\t<li><a href=\"#$TabHREFItemsMenu[$i]\" title=\"$TabTipsItemsMenu[$i]\">$CurrentValue</a></li>\n";
         }
         echo "</ul>\n";
     }
 }


/**
 * Display the contextual menu of the support area in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 3.2
 *     - 2012-07-12 : taken into account the "family details" item
 *     - 2013-04-03 : taken into account the items of the "Cooperation" module
 *     - 2014-02-03 : taken into account the "nursery delays" item
 *     - 2014-03-12 : taken into account the "annual registrations" (for canteen) item
 *     - 2014-08-05 : taken into account the "new year families", "new year children" (for canteen) and
 *                    "Create profil" items
 *     - 2015-06-15 : taken into account the "Snack planning", "Laundry planning" and "Exit permissions" items
 *     - 2015-10-12 : taken into account the "Create workgroup" and "Workgroups list" items
 *     - 2016-03-01 : taken into account the "Create alias", "List alias", "Send message", "Create donation",
 *                    "List donations" and "Generate tax receipts" items
 *     - 2016-10-21 : taken into account the "Admin" contextual menu
 *     - 2017-09-20 : taken into account "nursery day synthesis" and "messages jobs" items
 *     - 2019-05-07 : taken into account "add document approval" and "documents approvals list" items
 *
 * @since 2012-01-12
 *
 * @param $MenuName             String                  Name of the contextual menu to display
 * @param $Depth                Integer                 Depth of the web page in the tree of the intranet
 * @param $NumItemSelected      Integer                 Position number of the selected item [1..n]
 */
 function displaySupportMemberContextualMenu($MenuName, $Depth, $NumItemSelected)
 {
     $RootLink = '';
     for($i = 0 ; $i < abs($Depth) ; $i++)
     {
         $RootLink .= '../';
     }

     if ($Depth < 0)
     {
         $RootLink .= "Support/";
     }

     switch(strtolower($MenuName))
     {
         default:
         case 'canteen':
               displayContextualMenu(
                                     $GLOBALS['LANG_MAIN_MENU_SUPPORT_CANTEEN_MANAGEMENT'],
                                     array(
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_CREATE_FAMILY"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_FAMILIES_LIST"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_ADD_PAYMENT"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_PAYMENTS_SYNTHESIS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_GENERATE_MONTHLY_BILLS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_GENERATE_ANNUAL_BILLS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_PREPARE_NEW_YEAR_CHILDREN"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_PREPARE_NEW_YEAR_FAMILIES"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_CANTEEN_PLANNING"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_ANNUAL_REGISTRATIONS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_WEEK_SYNTHESIS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_DAY_SYNTHESIS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_NURSERY_PLANNING"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_NURSERY_DAY_SYNTHESIS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_NURSERY_DELAYS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_SNACK_PLANNING"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_LAUNDRY_PLANNING"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_EXIT_PERMISSIONS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_ADD_DOCUMENT_APPROVAL"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_DOCUMENTS_APPROVALS_LIST"]
                                          ),
                                     array(
                                           $RootLink."Canteen/CreateFamily.php?Cr=".md5('')."&amp;Id=",
                                           $RootLink."Canteen/FamiliesList.php",
                                           $RootLink."Canteen/CreatePayment.php",
                                           $RootLink."Canteen/PaymentsSynthesis.php",
                                           $RootLink."Canteen/GenerateMonthlyBills.php",
                                           $RootLink."Canteen/GenerateAnnualBills.php",
                                           $RootLink."Canteen/PrepareNewYearChildren.php",
                                           $RootLink."Canteen/PrepareNewYearFamilies.php",
                                           $RootLink."Canteen/CanteenPlanning.php",
                                           $RootLink."Canteen/CanteenAnnualRegistrations.php",
                                           $RootLink."Canteen/WeekSynthesis.php",
                                           $RootLink."Canteen/DaySynthesis.php",
                                           $RootLink."Canteen/NurseryPlanning.php",
                                           $RootLink."Canteen/NurseryDaySynthesis.php",
                                           $RootLink."Canteen/NurseryDelays.php",
                                           $RootLink."Canteen/SnackPlanning.php",
                                           $RootLink."Canteen/LaundryPlanning.php",
                                           $RootLink."Canteen/ExitPermissions.php",
                                           $RootLink."Canteen/AddDocumentApproval.php?Cr=".md5('')."&amp;Id=",
                                           $RootLink."Canteen/DocumentsApprovalsList.php"
                                          ),
                                     array(
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_CREATE_FAMILY_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_FAMILIES_LIST_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_ADD_PAYMENT_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_PAYMENTS_SYNTHESIS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_GENERATE_MONTHLY_BILLS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_GENERATE_ANNUAL_BILLS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_PREPARE_NEW_YEAR_CHILDREN_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_PREPARE_NEW_YEAR_FAMILIES_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_CANTEEN_PLANNING_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_ANNUAL_REGISTRATIONS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_WEEK_SYNTHESIS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_DAY_SYNTHESIS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_NURSERY_PLANNING_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_NURSERY_DAY_SYNTHESIS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_NURSERY_DELAYS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_SNACK_PLANNING_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_LAUNDRY_PLANNING_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_EXIT_PERMISSIONS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_ADD_DOCUMENT_APPROVAL_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_CANTEEN_DOCUMENTS_APPROVALS_LIST_TIP"]
                                          ),
                                     $NumItemSelected,
                                     'canteen_contextualmenu',
                                     array(Canteen_PrepareNewYearFamilies, Canteen_DaySynthesis, Canteen_NurseryDelays,
                                           Canteen_LaundryPlanning, Canteen_ExitPermissions),
                                     array_values($GLOBALS['ACCESS_CONTEXTUALMENUS'][$_SESSION["SupportMemberStateID"]]['canteen'])
                                    );
               break;

        case 'cooperation':
               displayContextualMenu(
                                     $GLOBALS['LANG_MAIN_MENU_SUPPORT_COOP_MANAGEMENT'],
                                     array(
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_CREATE_EVENT"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_FESTIVE_EVENTS_LIST"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_MAINT_EVENTS_LIST"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_CLOSED_EVENTS_LIST"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_FAMILIES_LIST"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_CREATE_WORKGROUP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_WORKGROUPS_LIST"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_CREATE_DONATION"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_DONATIONS_LIST"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_GENERATE_TAX_RECEIPTS"]
                                          ),
                                     array(
                                           $RootLink."Cooperation/CreateEvent.php?Cr=".md5('')."&amp;Id=",
                                           $RootLink."Cooperation/FestiveEventsList.php",
                                           $RootLink."Cooperation/MaintenanceEventsList.php",
                                           $RootLink."Cooperation/ClosedEventsList.php",
                                           $RootLink."Cooperation/FamiliesList.php",
                                           $RootLink."Cooperation/CreateWorkGroup.php?Cr=".md5('')."&amp;Id=",
                                           $RootLink."Cooperation/WorkGroupsList.php",
                                           $RootLink."Cooperation/CreateDonation.php?Cr=".md5('')."&amp;Id=",
                                           $RootLink."Cooperation/DonationsList.php",
                                           $RootLink."Cooperation/GenerateTaxReceipts.php"
                                          ),
                                     array(
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_CREATE_EVENT_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_FESTIVE_EVENTS_LIST_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_MAINT_EVENTS_LIST_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_CLOSED_EVENTS_LIST_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_FAMILIES_LIST_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_CREATE_WORKGROUP_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_WORKGROUPS_LIST_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_CREATE_DONATION_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_DONATIONS_LIST_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_COOP_GENERATE_TAX_RECEIPTS_TIP"]
                                          ),
                                     $NumItemSelected,
                                     'cooperation_contextualmenu',
                                     array(Coop_CreateEvent, Coop_ClosedEventsList, Coop_FamiliesList, Coop_WorkGroupsList),
                                     array_values($GLOBALS['ACCESS_CONTEXTUALMENUS'][$_SESSION["SupportMemberStateID"]]['cooperation'])
                                    );
               break;

        case 'admin':
               displayContextualMenu(
                                     $GLOBALS['LANG_MAIN_MENU_SUPPORT_ADMIN_MANAGEMENT'],
                                     array(
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_CREATE_SUPPORT_MEMBER"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SUPPORTMEMBERS_LIST"],
                                           $GLOBALS['LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SUPPORTMEMBERSSTATES_LIST'],
                                           $GLOBALS['LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_HOLIDAYS_LIST'],
                                           $GLOBALS['LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SPECIAL_DAYS_LIST'],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SWAP_SNACK_PLANNING"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SWAP_LAUNDRY_PLANNING"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_EVENT_TYPES_LIST"],
                                           $GLOBALS['LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_CONFIG_PARAMETERS_LIST']
                                          ),
                                     array(
                                           $RootLink."Admin/CreateProfil.php",
                                           $RootLink."Admin/SupportMembersList.php",
                                           $RootLink."Admin/SupportMembersStatesList.php",
                                           $RootLink."Admin/HolidaysList.php",
                                           $RootLink."Admin/OpenedSpecialDaysList.php",
                                           $RootLink."Admin/SwapSnackPlanning.php",
                                           $RootLink."Admin/SwapLaundryPlanning.php",
                                           $RootLink."Admin/EventTypesList.php",
                                           $RootLink."Admin/ConfigParametersList.php"
                                          ),
                                     array(
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_CREATE_SUPPORT_MEMBER_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SUPPORTMEMBERS_LIST_TIP"],
                                           $GLOBALS['LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SUPPORTMEMBERSSTATES_LIST_TIP'],
                                           $GLOBALS['LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_HOLIDAYS_LIST_TIP'],
                                           $GLOBALS['LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SPECIAL_DAYS_LIST_TIP'],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SWAP_SNACK_PLANNING_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_SWAP_LAUNDRY_PLANNING_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_EVENT_TYPES_LIST_TIP"],
                                           $GLOBALS['LANG_CONTEXTUAL_MENU_SUPPORT_ADMIN_CONFIG_PARAMETERS_LIST_TIP']
                                          ),
                                     $NumItemSelected,
                                     'admin_contextualmenu',
                                     array(),
                                     array_values($GLOBALS['ACCESS_CONTEXTUALMENUS'][$_SESSION["SupportMemberStateID"]]['admin'])
                                    );
               break;

        case 'parameters':
               displayContextualMenu(
                                     $GLOBALS['LANG_CONTEXTUAL_MENU_SUPPORT'],
                                     array(
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_PROFIL_SUPPORTER"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_PREPARED_REQUESTS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_FAMILY_DETAILS_SUPPORTER"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_CREATE_ALIAS"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_ALIAS_LIST"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SEND_MESSAGE"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_MESSAGES_JOBS_LIST"]
                                          ),
                                     array(
                                           $RootLink."Profil.php",
                                           $RootLink."PreparedRequests.php",
                                           $RootLink."Canteen/FamilyDetails.php",
                                           $RootLink."CreateAlias.php?Cr=".md5('')."&amp;Id=",
                                           $RootLink."AliasList.php",
                                           $RootLink."SendMessage.php",
                                           $RootLink."MessagesJobsList.php"
                                          ),
                                     array(
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_PROFIL_SUPPORTER_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_PREPARED_REQUESTS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_FAMILY_DETAILS_SUPPORTER_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_CREATE_ALIAS_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_ALIAS_LIST_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_SEND_MESSAGE_TIP"],
                                           $GLOBALS["LANG_CONTEXTUAL_MENU_MESSAGES_JOBS_LIST_TIP"]
                                          ),
                                     $NumItemSelected,
                                     'parameters_contextualmenu',
                                     array(Param_FamilyDetails),
                                     array_values($GLOBALS['ACCESS_CONTEXTUALMENUS'][$_SESSION["SupportMemberStateID"]]['parameters'])
                                    );
               break;
     }
 }


/**
 * Display the Support main menu at the top of the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2013-04-03 : taken into account the "Cooperation" item
 *     - 2016-10-21 : taken into account the "Admin" item
 *
 * @since 2012-01-12
 *
 * @param $Depth                Integer          Depth of the web page in the tree of the intranet
 */
 function displaySupportMainMenu($Depth = 0)
 {
     $RootLink = "";
     for($i = 0 ; $i < abs($Depth) ; $i++)
     {
         $RootLink .= "../";
     }

     if ($Depth < 0)
     {
         $RootLink .= "Support/";
     }

     $ArrayMainMenu = array(
                            $GLOBALS["LANG_MAIN_MENU_SUPPORT_CANTEEN_MANAGEMENT"],
                            $GLOBALS["LANG_MAIN_MENU_SUPPORT_COOP_MANAGEMENT"]
                           );

     $ArrayMainMenuLinks = array(
                                 $RootLink."index.php",
                                 $RootLink."Cooperation/Cooperation.php"
                                );

     $ArrayMainMenuTips = array(
                                $GLOBALS["LANG_MAIN_MENU_SUPPORT_CANTEEN_MANAGEMENT_TIP"],
                                $GLOBALS["LANG_MAIN_MENU_SUPPORT_COOP_MANAGEMENT_TIP"]
                               );

     if ((isset($_SESSION['SupportMemberStateID'])) && ($_SESSION['SupportMemberStateID'] == 1))
     {
         // The logged suppoter is an admin
         $ArrayMainMenu[] = $GLOBALS['LANG_MAIN_MENU_SUPPORT_ADMIN_MANAGEMENT'];
         $ArrayMainMenuLinks[] = $RootLink."Admin/Admin.php";
         $ArrayMainMenuTips[] = $GLOBALS['LANG_MAIN_MENU_SUPPORT_ADMIN_MANAGEMENT_TIP'];
     }

     displayMainMenu($ArrayMainMenu, $ArrayMainMenuLinks, $ArrayMainMenuTips);
 }
?>