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
 * Support module : force to send an e-mail containg the planning of the canteen for the next day
 * For some cases, the next can be another day (because of week-end or vacations). A given date can
 * be entered
 *
 * @author Christophe Javouhey
 * @version 3.1
 *     - 2015-05-13 : patch a bug about different quantities bteween now and the provisional planning
 *                    sent before (get quantities from a file)
 *     - 2016-06-20 : taken into account $CONF_CHARSET
 *     - 2016-10-12 : taken into account Bcc and load some configuration variables from database
 *     - 2017-11-07 : taken into account CANTEEN_REGISTRATION_DEFAULT_MEAL and CANTEEN_REGISTRATION_WITHOUT_PORK
 *
 * @since 2014-06-06
 */

 if (!function_exists('getIntranetRootDirectoryHDD'))
 {
    /**
     * Give the path of the Intranet root directory on the HDD
     *
     * @author Christophe Javouhey
     * @version 1.0
     * @since 2012-03-20
     *
     * @return String             Intranet root directory on the HDD
     */
     function getIntranetRootDirectoryHDD()
     {
         $sLocalDir = str_replace(array("\\"), array("/"), dirname(__FILE__)).'/';
         $bUnixOS = FALSE;
         if ($sLocalDir{0} == '/')
         {
             $bUnixOS = TRUE;
         }

         $ArrayTmp = explode('/', $sLocalDir);

         $iPos = array_search("CanteenCalandreta", $ArrayTmp);
         if ($iPos !== FALSE)
         {
             $sLocalDir = '';
             if ($bUnixOS)
             {
                 $sLocalDir = '/';
             }

             for($i = 0; $i <= $iPos; $i++)
             {
                 $sLocalDir .= $ArrayTmp[$i].'/';
             }
         }

         return $sLocalDir;
     }
 }

 $DOCUMENT_ROOT = getIntranetRootDirectoryHDD();

 include_once($DOCUMENT_ROOT.'GUI/GraphicInterface.php');

 $CONF_URL_SUPPORT = "http://localhost/CanteenCalandreta/Support/";
 $CONF_EMAIL_TEMPLATES_DIRECTORY_HDD = $DOCUMENT_ROOT."Templates/";

 $NotificationType = 'TodayPlanning';

 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 $ArrayDatesToTreat = array();  // In this array, we set date for which we must send an e-mail

 // Get the next day
 $CurrentDateStamp = strtotime("now");
 $CurrentDate = date('Y-m-d', $CurrentDateStamp);
 $NextDate = getNextWorkingDay($DbCon, $CurrentDate);
 $NextDateStamp = strtotime($NextDate);
 $ArrayDatesToTreat[] = $NextDate;

 // Check if there is an opened special day just after this next working day
 // We limit the search of opened special dayes to the current week
 $NextSunday = date('Y-m-d', strtotime("next sunday", strtotime($NextDate)));
 $ArrayOpenedSpecialsDays = getOpenedSpecialDays($DbCon, $NextDate, $NextSunday, 'OpenedSpecialDayDate');
 if (!empty($ArrayOpenedSpecialsDays))
 {
     // We include the found opened special days in the array of dates to treat
     $ArrayDatesToTreat = array_merge($ArrayDatesToTreat, $ArrayOpenedSpecialsDays['OpenedSpecialDayDate']);
 }

 // Use the first date of the list as default date for the order of meals
 $DateOrder = $ArrayDatesToTreat[0];

 if (!empty($_POST['bSubmit']))
 {
     // Get the entered date of order
     $sEnteredDateOrder = trim(strip_tags($_POST['dDateOrder']));
     if ((!empty($sEnteredDateOrder)) && ($sEnteredDateOrder != $DateOrder))
     {
         // We reinit the dates to treat
         $ArrayDatesToTreat = array($sEnteredDateOrder);
     }

     // We check if we must send a notification
     if ((isset($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Template]))
         && (!empty($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Template]))
         && (!empty($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][To]))
        )
     {
         // We get the provisional quantities of the week from the temporary file
         $ArrayFileContent = file($CONF_CANTEEN_PROVISIONAL_QUANTITIES_TMP_FILE);
         $ArrayQuantitiesOfWeek = array();
         if (!empty($ArrayFileContent))
         {
             foreach($ArrayFileContent as $d => $Line)
             {
                 // Form of the line : date:xx|xx|xx|xx|xx|xx
                 $ArrayTmp = explode(':', $Line);
                 if (count($ArrayTmp) == 2)
                 {
                     $ArrayQuantitiesOfWeek[$ArrayTmp[0]] = explode('|', $ArrayTmp[1]);
                 }
             }
         }

         unset($ArrayFileContent, $ArrayTmp);

         foreach($ArrayDatesToTreat as $dtt => $DateToTreat)
         {
             echo "send $DateToTreat";
             $EmailTemplate = $CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Template];

             // Generate the content of the mail
             $BodyContent = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
             $BodyContent .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".$GLOBALS['CONF_LANG']."\" lang=\"".$GLOBALS['CONF_LANG']."\">\n";
             $BodyContent .= "<head>\n";
             $BodyContent .= "\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".strtolower($GLOBALS['CONF_CHARSET'])."\" />\n";

             // Get the CSS
             $StyleSheetContent = getContentFile($CONF_EMAIL_TEMPLATES_DIRECTORY_HDD."PrintStyles.css", 'rt');
             $BodyContent .= "<style type=\"text/css\" media=\"all\">\n";
             $BodyContent .= $StyleSheetContent;
             $BodyContent .= "</style>\n";
             $BodyContent .= "</head>\n<body>\n<div id=\"content\">\n";

             unset($StyleSheetContent);

             // Canteen planning for the next day or another if today is the day before the week-end or vacations
             $StartDate = $DateToTreat;
             $EndDate = $DateToTreat;
             $Week = date('W', strtotime($StartDate));

             // Display the header of the synthesis
             $BodyContent .= generateTitlePage($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_TITLE, 2, "class=\"CanteenSynthesis\"");
             $BodyContent .= "<p class\"CanteenSynthesisHeader\">";
             $BodyContent .= generateStyledText($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_CONTACT, "");
             $BodyContent .= generateBR(3);
             $BodyContent .= generateStyledText($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_FOR, "");
             $BodyContent .= generateBR(2);
             $BodyContent .= generateStyledText($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_FOR_NAME, "");
             $BodyContent .= generateBR(1);
             $BodyContent .= generateStyledText($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_FOR_CONTACT, "");
             $BodyContent .= "</p>";
             $BodyContent .= generateTitlePage($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_ORDER_TITLE, 3, "class=\"CanteenSynthesisOrderTitle\"");

             // We get canteen registrations for this day and year
             $ArrayDaysOfWeek = array($StartDate);
             $BodyContent .= "<table class=\"CanteenSynthesisTable\" cellspacing=\"0\">\n<tr>\n";
             $BodyContent .= "\t<td colspan=\"6\">".ucfirst($LANG_WEEK)." ...$Week... ".$LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_FROM." ";

             if ($dtt == 0)
             {
                 // The e-mail is for the next date
                 $BodyContent .= date($CONF_DATE_DISPLAY_FORMAT, strtotime($StartDate))."</td>\n</tr>\n";
             }
             else
             {
                 // The e-mail is for an opened special day
                 $BodyContent .= "<strong style=\"color: #f00;\">".date($CONF_DATE_DISPLAY_FORMAT, strtotime($StartDate))."</strong></td>\n</tr>\n";
             }

             $BodyContent .= "<tr>\n\t<th>".$LANG_CHILD_GRADE."</th>";
             foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
             {
                 // Display the name of the day
                 $iNumWeekDay = date('w', strtotime($CurrentDayDate));
                 if ($iNumWeekDay == 0)
                 {
                     // Sunday = 0 -> 7
                     $iNumWeekDay = 7;
                 }

                 $BodyContent .= "<th>".$CONF_DAYS_OF_WEEK[$iNumWeekDay - 1]."</th>";
             }
             $BodyContent .= "</tr>\n";

             // Stats for groups of grades
             $iGroup = 0;
             $ArrayUseCorrections = array();
             foreach($CONF_GRADES_GROUPS as $Label => $ArrayGradeID)
             {
                 // First, with pork
                 $BodyContent .= "<tr>\n\t<td>$Label</td>";
                 foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
                 {
                     $ArrayUseCorrections[$iGroup][$d][0] = FALSE;

                     // We check if there are canteen registrations without pork for this day
                     $ArrayStatsParams = array(
                                               'CanteenRegistrationWithoutPork' => array(CANTEEN_REGISTRATION_DEFAULT_MEAL),
                                               'ChildGrade' => $ArrayGradeID
                                              );
                     $ArrayCanteenRegistrationsOfDay = getCanteenRegistrations($DbCon, $CurrentDayDate, $CurrentDayDate,
                                                                               'CanteenRegistrationForDate', NULL, FALSE,
                                                                               PLANNING_BETWEEN_DATES, $ArrayStatsParams);

                     $Quantity = 0;
                     $bUpdated = FALSE;
                     if (!empty($ArrayCanteenRegistrationsOfDay))
                     {
                         $Quantity = count($ArrayCanteenRegistrationsOfDay['CanteenRegistrationID']);
                     }

                     // We use the correction of the quantity (if set)
                     if (isset($CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS[$Label][0]))
                     {
                         if ($Quantity + $GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][0] > 0)
                         {
                             if (($iGroup == 0) || (($iGroup > 0) && ($ArrayUseCorrections[$iGroup - 1][$d][0])))
                             {
                                 $Quantity = max(0, $Quantity + $GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][0]);
                                 $ArrayUseCorrections[$iGroup][$d][0] = TRUE;
                             }
                         }
                     }

                     // We check if the "more meals" (with pork) must be dispatch on this group
                     if ($Label == $GLOBALS['CONF_CANTEEN_MORE_MEALS_DISPATCHED_ON_GROUP'])
                     {
                         // yes, we must add the "more meals" quantities to the quantity of this group
                         $ArrayMoreMeals = getMoreMeals($DbCon, $CurrentDayDate, $CurrentDayDate);
                         if ((isset($ArrayMoreMeals)) && (!empty($ArrayMoreMeals['MoreMealQuantity'])))
                         {
                             $Quantity += array_sum($ArrayMoreMeals['MoreMealQuantity']);
                         }
                     }

                     // We check if the quantity has changed since the provisional planning
                     if (isset($ArrayQuantitiesOfWeek[$CurrentDayDate]))
                     {
                         if ($ArrayQuantitiesOfWeek[$CurrentDayDate][2 * $iGroup] != $Quantity)
                         {
                             $bUpdated = TRUE;
                         }
                     }

                     if ($bUpdated)
                     {
                         $BodyContent .= "<td class=\"CanteenSynthesisQuantityUpdated\">$Quantity</td>";
                     }
                     else
                     {
                         $BodyContent .= "<td>$Quantity</td>";
                     }
                 }
                 $BodyContent .= "</tr>\n";

                 // Next, without pork
                 $BodyContent .= "<tr>\n\t<td>$Label / ".$GLOBALS['LANG_MEAL_WITHOUT_PORK']."</td>";
                 foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
                 {
                     $ArrayUseCorrections[$iGroup][$d][1] = FALSE;

                     // We check if there are canteen registrations without pork for this day
                     $ArrayStatsParams = array(
                                              'CanteenRegistrationWithoutPork' => array(CANTEEN_REGISTRATION_WITHOUT_PORK),
                                               'ChildGrade' => $ArrayGradeID
                                              );
                     $ArrayCanteenRegistrationsOfDay = getCanteenRegistrations($DbCon, $CurrentDayDate, $CurrentDayDate,
                                                                               'CanteenRegistrationForDate', NULL, FALSE,
                                                                               PLANNING_BETWEEN_DATES, $ArrayStatsParams);

                     $Quantity = 0;
                     $bUpdated = FALSE;
                     if (!empty($ArrayCanteenRegistrationsOfDay))
                     {
                         $Quantity = count($ArrayCanteenRegistrationsOfDay['CanteenRegistrationID']);
                     }

                     // We use the correction of the quantity (if set)
                     if (isset($CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS[$Label][1]))
                     {
                         if ($Quantity + $GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][1] > 0)
                         {
                             if (($iGroup == 0) || (($iGroup > 0) && ($ArrayUseCorrections[$iGroup - 1][$d][1])))
                             {
                                 $Quantity = max(0, $Quantity + $GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][1]);
                                 $ArrayUseCorrections[$iGroup][$d][1] = TRUE;
                             }
                         }
                     }

                     // We check if the "more meals" (without pork) must be dispatch on this group
                     if ($Label == $GLOBALS['CONF_CANTEEN_MORE_MEALS_DISPATCHED_ON_GROUP'])
                     {
                         // yes, we must add the "more meals" quantities to the quantity of this group
                         $ArrayMoreMeals = getMoreMeals($DbCon, $CurrentDayDate, $CurrentDayDate);
                         if ((isset($ArrayMoreMeals)) && (!empty($ArrayMoreMeals['MoreMealWithoutPorkQuantity'])))
                         {
                             $Quantity += array_sum($ArrayMoreMeals['MoreMealWithoutPorkQuantity']);
                         }
                     }

                     // We check if the quantity has changed since the provisional planning
                     if (isset($ArrayQuantitiesOfWeek[$CurrentDayDate]))
                     {
                         if ($ArrayQuantitiesOfWeek[$CurrentDayDate][(2 * $iGroup) + 1] != $Quantity)
                         {
                             $bUpdated = TRUE;
                         }
                     }

                     if ($bUpdated)
                     {
                         $BodyContent .= "<td class=\"CanteenSynthesisQuantityUpdated\">$Quantity</td>";
                     }
                     else
                     {
                         $BodyContent .= "<td>$Quantity</td>";
                     }
                 }

                 $iGroup++;
                 $BodyContent .= "</tr>\n";
             }

             // Adults with pork
             $BodyContent .= "<tr>\n\t<td>".$GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_ADULTS']."</td>";
             foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
             {
                 $Quantity = 0;

                 // Get "more meals" for this day (only if these quantities aren't dispatched on one of the previous groups
                 if (empty($GLOBALS['CONF_CANTEEN_MORE_MEALS_DISPATCHED_ON_GROUP']))
                 {
                     $ArrayMoreMeals = getMoreMeals($DbCon, $CurrentDayDate, $CurrentDayDate);
                     if ((isset($ArrayMoreMeals)) && (!empty($ArrayMoreMeals['MoreMealQuantity'])))
                     {
                         $Quantity = array_sum($ArrayMoreMeals['MoreMealQuantity']);
                     }
                 }

                 // We check if the quantity has changed since the provisional planning
                 $bUpdated = FALSE;
                 if (isset($ArrayQuantitiesOfWeek[$CurrentDayDate]))
                 {
                     $iNbValues = count($ArrayQuantitiesOfWeek[$CurrentDayDate]);
                     if ($ArrayQuantitiesOfWeek[$CurrentDayDate][$iNbValues - 2] != $Quantity)
                     {
                         $bUpdated = TRUE;
                     }
                 }

                 if ($bUpdated)
                 {
                     $BodyContent .= "<td class=\"CanteenSynthesisQuantityUpdated\">".nullFormatText($Quantity, 'XHTML')."</td>";
                 }
                 else
                 {
                     $BodyContent .= "<td>".nullFormatText($Quantity, 'XHTML')."</td>";
                 }
             }
             $BodyContent .= "</tr>\n";

             // Adults without pork
             $BodyContent .= "<tr>\n\t<td>".$LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_ADULTS." / ".$LANG_MEAL_WITHOUT_PORK."</td>";
             foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
             {
                 $Quantity = 0;

                 // Get "more meals" without pork for this day (only if these quantities aren't dispatched on one of the previous groups)
                 if (empty($GLOBALS['CONF_CANTEEN_MORE_MEALS_DISPATCHED_ON_GROUP']))
                 {
                     $ArrayMoreMeals = getMoreMeals($DbCon, $CurrentDayDate, $CurrentDayDate);
                     if ((isset($ArrayMoreMeals)) && (!empty($ArrayMoreMeals['MoreMealWithoutPorkQuantity'])))
                     {
                         $Quantity = array_sum($ArrayMoreMeals['MoreMealWithoutPorkQuantity']);
                     }
                 }

                 // We check if the quantity has changed since the provisional planning
                 $bUpdated = FALSE;
                 if (isset($ArrayQuantitiesOfWeek[$CurrentDayDate]))
                 {
                     $iNbValues = count($ArrayQuantitiesOfWeek[$CurrentDayDate]);
                     if ($ArrayQuantitiesOfWeek[$CurrentDayDate][$iNbValues - 1] != $Quantity)
                     {
                         $bUpdated = TRUE;
                     }
                 }

                 if ($bUpdated)
                 {
                     $BodyContent .= "<td class=\"CanteenSynthesisQuantityUpdated\">".nullFormatText($Quantity, 'XHTML')."</td>";
                 }
                 else
                 {
                     $BodyContent .= "<td>".nullFormatText($Quantity, 'XHTML')."</td>";
                 }
             }

             $BodyContent .= "</tr>\n</table>\n";

             $BodyContent .= "<p class=\"CanteenSynthesisFooter\">\n";
             $BodyContent .= generateStyledText($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_CONCLUSION, "");
             $BodyContent .= generateBR(2);
             $BodyContent .= generateStyledText($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_WARNING, "CanteenSynthesisWarning");
             $BodyContent .= "</p>\n";

             // We close the html document
             $BodyContent .= "</div>\n</body>\n</html>";

             // We send an e-mail
             $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_CANTEEN_PLANNING]." $LANG_WEEKLY_CANTEEN_DAILY_PLANNING_EMAIL_SUBJECT "
                             .date($CONF_DATE_DISPLAY_FORMAT, strtotime($StartDate));

             $MailingList["to"] = $CONF_CANTEEN_NOTIFICATIONS[$NotificationType][To];

             if ($CONF_MODE_DEBUG)
             {
                 $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
             }

             if ((isset($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Cc])) && (!empty($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Cc])))
             {
                 $MailingList["cc"] = $CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Cc];
             }

             if ((isset($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Bcc])) && (!empty($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Bcc])))
             {
                 $MailingList["bcc"] = $CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Bcc];
             }

             // We send the e-mail
             sendEmail(NULL, $MailingList, $EmailSubject, $EmailTemplate, array(array("{BodyContent}"), array($BodyContent)));
         }
     }
 }

 // We close the database connection
 dbDisconnection($DbCon);
?>

<html>
<head>
<title><?php echo $LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_ORDER_TITLE ?></title>
</head>
<body>
<form name="fdate" action="SendEmailForcingDailyCanteenPlanning.php" method="post">
<label for="dDateOrder"><?php echo $LANG_DATE ?></label> : <input name="dDateOrder" type="text" size="10" maxlength="10" value="<?php echo $DateOrder ?>">
<br /><br />
<input type="submit" name="bSubmit" value="bSubmit">
</form>
</body>
</html>