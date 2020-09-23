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
 * PHP plugin canteen registrations children habits module : compute the habits of
 * canteen registrations of children
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2014-11-07 : Don't count vacation weeks before a child is arrived at school
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2014-06-10
 */


 // Include Config.php because of the name of the session
 require '../../GUI/GraphicInterface.php';
 require 'PHPCanteenRegistrationsChildrenHabitsConfig.php';
 require 'PHPCanteenRegistrationsChildrenHabitsLibrary.php';

 session_start();

 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS',
                                      'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                      'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                      'CONF_CANTEEN_PRICES',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

 //************************* STEP 1 : reinit the table and parameters ***********************
 $DbCon->query("TRUNCATE TABLE ".$CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_DB.".`".$CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_TABLE."`");

 // Compute the startdate to be the a monday
 $StartDate = $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_LIMIT_ANALYSIS_DATE;
 $StartDate = date('Y-m-d', strtotime('last monday', strtotime($StartDate)));

 // Compute the enddate to be the previous sunday in relation with the current day
 $EndDate = date('Y-m-d', strtotime('last sunday', strtotime(date('Y-m-d'))));

 // Compute nb weeks between $StartDate and $EndDate
 $iNbDays = getNbDaysBetween2Dates(strtotime($StartDate), strtotime($EndDate), FALSE);
 $iNbWeeks = round($iNbDays / 7.00);

 // We must remove the holidays form the weeks between $StartDate and $EndDate
 $ArrayHolidays = getHolidays($DbCon, $StartDate, $EndDate, 'HolidayStartDate', PLANNING_BETWEEN_DATES);
 if (isset($ArrayHolidays['HolidayID']))
 {
     foreach($ArrayHolidays['HolidayID'] as $h => $HolidayID)
     {
         $iNbDays = getNbDaysBetween2Dates(strtotime($ArrayHolidays['HolidayStartDate'][$h]),
                                           strtotime($ArrayHolidays['HolidayEndDate'][$h]), FALSE);

         if ($iNbDays >= 2)
         {
             $iNbWeeks -= round($iNbDays / 7.00);
         }
     }
 }

 //************************* STEP 2 : analyse habits of children ***********************
 // Get activated children
 echo "StartDate : $StartDate / EndDate : $EndDate / Nb weeks : $iNbWeeks<br />\n";

 $ArrayChildren = getChildrenListForCanteenPlanning($DbCon, $StartDate, $EndDate, 'FamilyLastname', TRUE);

 if (isset($ArrayChildren['ChildID']))
 {
     foreach($ArrayChildren['ChildID'] as $c => $ChildID)
     {
         // Get canteen registrations of the current child
         echo $ArrayChildren['FamilyLastname'][$c].' '.$ArrayChildren['ChildFirstname'][$c]."......";
         $ArrayWeeksProfils = array();
         $iCurrentChildNbWeeks = $iNbWeeks;

         $ArrayCanteenregistrations = getCanteenRegistrations($DbCon, $StartDate, $EndDate, 'CanteenRegistrationForDate', $ChildID);
         if ((isset($ArrayCanteenregistrations['CanteenRegistrationID']))
             && (count($ArrayCanteenregistrations['CanteenRegistrationID']) > 0))
         {
             // Check if the first canteen registration date is in the same week as $StartDate
             if (date('o-W', strtotime($ArrayCanteenregistrations['CanteenRegistrationForDate'][0])) != date('o-W', strtotime($StartDate)))
             {
                 // Not the same week : we must recompute nb weeks between the new StartDate and $EndDate
                 $CurrentStartDate = date('Y-m-d', strtotime('last monday',
                                                             strtotime($ArrayCanteenregistrations['CanteenRegistrationForDate'][0])));

                 $iNbDays = getNbDaysBetween2Dates(strtotime($CurrentStartDate), strtotime($EndDate), FALSE);
                 $iCurrentChildNbWeeks = round($iNbDays / 7.00);

                 // We must remove the holidays form the weeks between the new StartDate and $EndDate
                 $ArrayHolidays = getHolidays($DbCon, $CurrentStartDate, $EndDate, 'HolidayStartDate', PLANNING_BETWEEN_DATES);
                 if (isset($ArrayHolidays['HolidayID']))
                 {
                     foreach($ArrayHolidays['HolidayID'] as $h => $HolidayID)
                     {
                         // We take into account the holidays if the child was at school before the strat date of the holidays
                         if (strtotime($CurrentStartDate) <= strtotime($ArrayHolidays['HolidayStartDate'][$h]))
                         {
                             $iNbDays = getNbDaysBetween2Dates(strtotime($ArrayHolidays['HolidayStartDate'][$h]),
                                                               strtotime($ArrayHolidays['HolidayEndDate'][$h]), FALSE);

                             if ($iNbDays >= 2)
                             {
                                 $iCurrentChildNbWeeks -= round($iNbDays / 7.00);
                             }
                         }
                     }
                 }
             }

             foreach($ArrayCanteenregistrations['CanteenRegistrationID'] as $cr => $CanteenRegistrationID)
             {
                 $Timestamp = strtotime($ArrayCanteenregistrations['CanteenRegistrationForDate'][$cr]);
                 $YearWeek = date("o-W", $Timestamp);
                 $NumDay = date("N", $Timestamp);
                 if (isset($ArrayWeeksProfils[$YearWeek]))
                 {
                     // The year-week already exists in the data structure
                     $ArrayWeeksProfils[$YearWeek] += pow(2, $NumDay);
                 }
                 else
                 {
                     // New year-week
                     $ArrayWeeksProfils[$YearWeek] = pow(2, $NumDay);
                 }
             }

             // Now, we do stat about profils of the child
             // First, we count the number of instances of each profil of week
             $ArrayProfilsStats = array();
             foreach($ArrayWeeksProfils as $yw => $Profil)
             {
                 if (isset($ArrayProfilsStats[$Profil]))
                 {
                     $ArrayProfilsStats[$Profil]++;
                 }
                 else
                 {
                     $ArrayProfilsStats[$Profil] = 1;
                 }
             }

             unset($ArrayWeeksProfils);

             // Next, we transform counters to rates (%) and we keep only profils with a minimum rate
             foreach($ArrayProfilsStats as $p => $Rate)
             {
                 $Rate = round(100 * $Rate / $iCurrentChildNbWeeks);
                 if ($Rate >= $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_MIN_RATE_TO_KEEP)
                 {
                     // We keep the profil because > min rate
                     $ArrayProfilsStats[$p] = $Rate;

                     // We store the profil $p of the week in the database, with its rate [min rate; 100]
                     dbAddCanteenRegistrationChildHabitProfil($DbCon, $ChildID, HABIT_TYPE_1_WEEK, $p, $Rate);
                 }
                 else
                 {
                     // We don't keep this profil
                     unset($ArrayProfilsStats[$p]);
                 }
             }

             echo "...$iCurrentChildNbWeeks W...";
             print_r($ArrayProfilsStats);

             unset($ArrayProfilsStats);
         }

         echo "...OK<br />\n";
     }
 }

 // Release the connection to the database
 dbDisconnection($DbCon);
?>
