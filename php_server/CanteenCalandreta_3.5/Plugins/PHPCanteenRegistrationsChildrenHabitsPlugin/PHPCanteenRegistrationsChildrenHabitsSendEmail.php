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
 * PHP plugin canteen registrations children habits module : Send an e-mail to families who haven't the same
 * canteen registrations for the next period as the previous to remind to register their children
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2014-11-06 : don't do analysis if vacations
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2014-06-11
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

 // Include the stats library
 include_once($DOCUMENT_ROOT.'Support/Stats/StatsLibrary.php');

 $CONF_URL_SUPPORT = "http://www.calandreta-mureth.dsmynas.org/CanteenCalandreta/Support/";

 require 'PHPCanteenRegistrationsChildrenHabitsConfig.php';
 require 'PHPCanteenRegistrationsChildrenHabitsLibrary.php';

 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS',
                                      'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                      'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                      'CONF_CANTEEN_PRICES',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

 // We check if we must send a notification
 if ((isset($CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_NOTIFICATIONS['NoConformity']))
     && (!empty($CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_NOTIFICATIONS['NoConformity']))
    )
 {
     // First, we define the next period
     $StartDate = date('Y-m-d', strtotime('next Monday', strtotime(date('Y-m-d'))));
     $EndDate = date('Y-m-d', strtotime('next Sunday', strtotime($StartDate)));

     echo "$StartDate / $EndDate<br />\n";

     // We check if the period is full vacations
     $StartMonth = date('m', strtotime($StartDate));
     $Year = date('Y', strtotime($StartDate));
     $EndMonth = date('m', strtotime($EndDate));
     $EndYear = date('Y', strtotime($EndDate));
     $OpenedDays = jours_ouvres(
                                range($StartMonth, (($EndYear - $Year) * 12) + $EndMonth),
                                $Year
                               );

     $Holidays = ferie(
                       range($StartMonth, (($EndYear - $Year) * 12) + $EndMonth),
                       $Year
                      );

     // Get the days of the period between start date and end date
     $Days = getRepeatedDates(strtotime($StartDate), strtotime($EndDate), REPEAT_DAILY, 1, FALSE);
     $NbDays = count($Days);

     // Get the opened special days (days normaly not opened to canteen registrations but they are)
     $ArrayOpenedSpecialDays = getOpenedSpecialDays($DbCon, $StartDate, $EndDate, 'OpenedSpecialDayDate');
     if (empty($ArrayOpenedSpecialDays))
     {
         // No opened special day for this period : we init just the key 'OpenedSpecialDayDate'
         $ArrayOpenedSpecialDays['OpenedSpecialDayDate'] = array();
     }

     // Get school holidays
     $ArraySchoolHolidays = getHolidays($DbCon, $StartDate, $EndDate, 'HolidayStartDate', PLANNING_BETWEEN_DATES);
     if ((!isset($ArraySchoolHolidays['HolidayID'])) || ((isset($ArraySchoolHolidays['HolidayID']))
         && (empty($ArraySchoolHolidays['HolidayID']))))
     {
         $ArraySchoolHolidays = getHolidays($DbCon, $StartDate, $EndDate, 'HolidayStartDate', DATES_BETWEEN_PLANNING);
     }

     if (isset($ArraySchoolHolidays['HolidayID']))
     {
         $StartTime = strtotime($StartDate);
         $EndTime = strtotime($EndDate);

         foreach($ArraySchoolHolidays['HolidayID'] as $h => $HolidayID)
         {
             $ArrayTmpDays = array_keys(getPeriodIntervalsStats($ArraySchoolHolidays['HolidayStartDate'][$h],
                                                                $ArraySchoolHolidays['HolidayEndDate'][$h], 'd'));

             foreach($ArrayTmpDays as $d => $CurrTmpDay)
             {
                 $CurrTime = strtotime($CurrTmpDay);
                 if (($CurrTime >= $StartTime) && ($CurrTime <= $EndTime))
                 {
                     // This day is in the current month
                     $iDay = (integer)date('d', $CurrTime);
                     $CurrentMonth = (integer)date('m', $CurrTime);
                     $CurrentYear = date('Y', $CurrTime);

                     // We compute the offset of the current day in the array of working days and holidays
                     // max() because for some years (ex : 2008), first day is in the previous year
                     $Offset = $CurrentMonth + 12 * max(0, $CurrentYear - $Year);

                     if (!isset($Holidays[$Offset][$iDay]))
                     {
                         $Holidays[$Offset][$iDay] = $ArraySchoolHolidays['HolidayDescription'][$h];

                         if (isset($OpenedDays[$Offset][$iDay]))
                         {
                             unset($OpenedDays[$Offset][$iDay]);
                         }
                     }
                 }
             }

             unset($ArrayTmpDays);
         }
     }

     // Check if at least one day is a working day (so, canteen is opened)
     $ArrayOpenedDaysToKeep = array();
     $ArrayDaysNotToTakeIntoAccount = array();

     foreach($Days as $d => $CurrentDayDate)
     {
         $iDay = (integer)date('d', strtotime($CurrentDayDate));
         $CurrentMonth = (integer)date('m', strtotime($CurrentDayDate));
         $CurrentYear = date('Y', strtotime($CurrentDayDate));
         $Offset = $CurrentMonth + 12 * max(0, $CurrentYear - $Year);

         $NumDay = date("N", strtotime($CurrentDayDate));

         // We keep only days the canteen is opened
         if ($CONF_CANTEEN_OPENED_WEEK_DAYS[$NumDay - 1])
         {
             if (isset($OpenedDays[$Offset][$iDay]))
             {
                 $ArrayOpenedDaysToKeep[$CurrentDayDate] = $NumDay;
             }
             else
             {
                 $ArrayDaysNotToTakeIntoAccount[] = $CurrentDayDate;
             }
         }
     }

     unset($ArraySchoolHolidays, $Days);

     if (count($ArrayOpenedDaysToKeep) > 0)
     {
         // Compute the startdate of the analysed period to be the a monday
         $StartDateAnalysedPeriod = $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_LIMIT_ANALYSIS_DATE;
         $StartDateAnalysedPeriod = date('Y-m-d', strtotime('last monday', strtotime($StartDateAnalysedPeriod)));

         // Next, we get activated children for this period
         $ArrayChildren = getChildrenListForCanteenPlanning($DbCon, $StartDate, $EndDate, 'FamilyLastname', TRUE);

         if (isset($ArrayChildren['ChildID']))
         {
             foreach($ArrayChildren['ChildID'] as $c => $ChildID)
             {
                 echo "<br />\n".$ArrayChildren['FamilyLastname'][$c].' '.$ArrayChildren['ChildFirstname'][$c].'......';

                 // Check if the profil of canteen registrations for the period is the same as the previous
                 $ArrayProfils = dbDetectNoConformityCanteenRegistrationChildHabit($DbCon, $ChildID, HABIT_TYPE_1_WEEK, $StartDate,
                                                                                   $EndDate, $ArrayDaysNotToTakeIntoAccount);
                 if ($ArrayProfils === TRUE)
                 {
                     echo "...OK";
                 }
                 elseif ((is_array($ArrayProfils)) && (!empty($ArrayProfils['CanteenRegistrationChildHabitProfil'])))
                 {
                     // Check if we don't find a good profil because of one or several vacations days
                     $bProfilFound = FALSE;

                     // For each profil, extract names of the days
                     foreach($ArrayProfils['CanteenRegistrationChildHabitProfil'] as $p => $Profil)
                     {
                         $ArrayProfils['CanteenRegistrationChildHabitProfilDecomposition'][$p] = array();
                         $CurrentProfil = 0;
                         for($i = 1; $i <= 7; $i++)
                         {
                             if ($Profil & pow(2, $i))
                             {
                                 $ArrayProfils['CanteenRegistrationChildHabitProfilDecomposition'][$p][] = $CONF_DAYS_OF_WEEK[$i - 1];
                                 $CurrentProfil += pow(2, $i);
                                 if ($CurrentProfil >= $Profil)
                                 {
                                     // Stop
                                     break;
                                 }
                             }
                         }
                     }

                     // No good profil found : perhaps, there is a pb with canteen registration : we send an e-mail to family
                     if (!$bProfilFound)
                     {
                         $ProfilsList = "<ul>";

                         foreach($ArrayProfils['CanteenRegistrationChildHabitProfilDecomposition'] as $p => $Decomposition)
                         {
                             if ($p > 0)
                             {
                                 echo " / ";
                             }

                             $sTmp = implode($ArrayProfils['CanteenRegistrationChildHabitProfilDecomposition'][$p], ', ')
                                     ." (".$ArrayProfils['CanteenRegistrationChildHabitRate'][$p]."%)";

                             $ProfilsList .= "<li>$sTmp</li>";

                             echo $sTmp;
                         }

                         $ProfilsList .= "</ul>";

                         echo "......MAIL";

                         // Prepare the e-mail to send to the family of the concerned child
                         $EmailSubject = $LANG_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_EMAIL_SUBJECT;

                         if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_CANTEEN_PLANNING]))
                         {
                             $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_CANTEEN_PLANNING].$EmailSubject;
                         }

                         // We define the content of the mail
                         $TemplateToUse = $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_NOTIFICATIONS['NoConformity'];
                         $ReplaceInTemplate = array(
                                                    array(
                                                          "{StartDate}", "{EndDate}", "{ChildFirstname}", "{StartDateAnalysedPeriod}",
                                                          "{ProfilsList}"
                                                         ),
                                                    array(
                                                          date($CONF_DATE_DISPLAY_FORMAT, strtotime($StartDate)),
                                                          date($CONF_DATE_DISPLAY_FORMAT, strtotime($EndDate)),
                                                          $ArrayChildren['ChildFirstname'][$c],
                                                          date($CONF_DATE_DISPLAY_FORMAT, strtotime($StartDateAnalysedPeriod)),
                                                          $ProfilsList
                                                         )
                                                   );

                         // Get the recipients of the e-mail notification (e-mails of the family)
                         $MailingList["to"] = array();

                         $ArrayFamilies = dbSearchFamily($DbCon, array('FamilyID' => $ArrayChildren['FamilyID'][$c]),
                                                         "FamilyLastname", 1, 0);
                         $MailingList["to"][] = $ArrayFamilies['FamilyMainEmail'][0];
                         if (!empty($ArrayFamilies['FamilySecondEmail'][0]))
                         {
                             $MailingList["to"][] = $ArrayFamilies['FamilySecondEmail'][0];
                         }

                         // DEBUG MODE
                         if ($GLOBALS["CONF_MODE_DEBUG"])
                         {
                             if (!in_array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS, $MailingList["to"]))
                             {
                                 // Without this test, there is a server mail error...
                                 $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
                             }
                         }

                         // We send the e-mail
                         sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate, array(),
                                   $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_EMAIL_TEMPLATES_DIRECTORY_HDD);
                     }
                 }
             }
         }
     }
     else
     {
         echo "//////";
     }
 }

 // We close the database connection
 dbDisconnection($DbCon);
?>