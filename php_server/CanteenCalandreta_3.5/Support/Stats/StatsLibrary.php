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
 * Stats module : library of database functions used for the Statisticals functions
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2012-12-31 : patch a bug to get the good number of week in the year when the current date
 *                    is yyyy-12-31
 *     - 2015-06-23 : getGraphicAxeXValuesStats() function modified with "wd" mode
 *
 * @since 2012-02-02
 */


/**
 * Give the captions linked to some criticities values
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-07-29
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $Reactivities         Array of Integer      List of the reactivity values which have to be displayed.
 *
 * @return Array of Strings                           Captions linked to criticities values
 */
 function getReactivitiesCaptions($Reactivities)
 {
     // Reactivities captions
     $Captions = array();

     $ReactivitiesSize = count($Reactivities);
     foreach($Reactivities as $i => $CurrentValue)
     {
         if (($i == $ReactivitiesSize - 1) && ($i > 0))
         {
             // We get the previous caption and we add the ">" string
             $Captions[] = "> ".$Captions[$i - 1];
         }
         else
         {
             switch($CurrentValue)
             {
                 case 1:
                     // 1 day
                     $Captions[] = ucfirst($GLOBALS["LANG_DAY"]);
                     break;

                 case 5:
                     // 1 week
                     $Captions[] = ucfirst($GLOBALS["LANG_WEEK"]);
                     break;

                 case 10:
                     // 2 weeks
                     $Captions[] = ucfirst($GLOBALS["LANG_2_WEEKS"]);
                     break;

                 case 23:
                     // 1 month
                     $Captions[] = ucfirst($GLOBALS["LANG_MONTH"]);
                     break;

                 case 217:
                     // 1 year
                     $Captions[] = ucfirst($GLOBALS["LANG_YEAR"]);
                     break;

                 default:
                     // Other
                     $Captions[] = $CurrentValue." ".$GLOBALS["LANG_DAYS"];
                     break;
             }
         }
     }

     // Return the captions
     return $Captions;
 }


/**
 * Generate the array keys in relation with a given period between 2 dates fo arrys which contain stats
 *
 * @author STNA/7SQ
 * @version 1.2
 *     - 2005-10-26 : patch the generation of the weeks between 2 dates
 *     - 2006-02-15 : patch the generation of the months between 2 dates
 *     - 2012-12-31 : patch a bug to get the goo number of week in the year when the current date
 *                    is yyyy-12-31
 *
 * @since 2004-07-30
 *
 * @param $StartDate            date (yyyy-mm-dd)     Start date of the X axe.
 * @param $EndDate              date (yyyy-mm-dd)     End date of the X axe.
 * @param $Period               String                Amount of time (« d » for day, « w » for week,
 *                                                    « m » for month et « y » for year).
 *
 * @return Array of Strings                           The array keys
 */
 function getPeriodIntervalsStats($StartDate, $EndDate, $Period = "w")
 {
     // Array which will contain the counts
     $ArrayPeriods = array();

     // Initialize the stamps
     $CurrentStamp = strtotime($StartDate);
     $EndStamp = strtotime($EndDate);

     switch(strToLower($Period))
     {
         case "d":
             // We generate each day between the StartDate and the EndDate
             while($CurrentStamp <= $EndStamp)
             {
                 $ArrayPeriods[date("Y-m-d", $CurrentStamp)] = 0;
                 $CurrentStamp += 86400;  // +1 day
             }

             if ($CurrentStamp - 86400 < $EndStamp)
             {
                 $ArrayPeriods[date("Y-m-d", $EndStamp)] = 0;
             }
             break;

         default:
         case "w":
             // We generate each n°week between the StartDate and the EndDate
             $PreviousYear = date("Y", $CurrentStamp);
             while($CurrentStamp <= $EndStamp)
             {
                 $CurrentYear = date("Y", $CurrentStamp);
                 $WeekNumber = (integer)date("W", $CurrentStamp);
                 if ($WeekNumber < 10)
                 {
                     $WeekNumber = "0$WeekNumber";
                 }

                 if ($PreviousYear < $CurrentYear)
                 {
                     if (!array_key_exists(getYearWeekOfDay("$PreviousYear-12-31"), $ArrayPeriods))
                     {
                         $ArrayPeriods[getYearWeekOfDay("$PreviousYear-12-31")] = 0;
                     }
                     $PreviousYear = $CurrentYear;
                 }

                 $ArrayPeriods[date("Y-$WeekNumber", $CurrentStamp)] = 0;
                 $CurrentStamp += 604800;  // +7 days
             }

             $WeekNumber = (integer)date("W", $EndStamp);
             $YearEnd = date('Y', $EndStamp);
             if ((date("m", $EndStamp) == 12) && ($WeekNumber == 1))
             {
                 // Ex : in case of yyyy-12-31 and the week is 01, so the year is yyyy+1
                 $YearEnd++;
             }

             if ($WeekNumber < 10)
             {
                 $WeekNumber = "0$WeekNumber";
             }

             $ArrayPeriods["$YearEnd-$WeekNumber"] = 0;  // Because sometimes the week of the EndDate is skipped
             break;

         case "m":
             // We generate each n°month between the StartDate and the EndDate
             // To generate the months, the used day is the first (because some months haven't 29, 30, 31 days)
             $CurrentStamp = strtotime(date("Y-m-01", $CurrentStamp));
             while($CurrentStamp <= $EndStamp)
             {
                 $ArrayPeriods[date("Y-m", $CurrentStamp)] = 0;
                 $CurrentStamp = mktime(0, 0, 0, date("m", $CurrentStamp) + 1, date("d", $CurrentStamp), date("Y", $CurrentStamp));
             }

             $ArrayPeriods[date("Y-m", $EndStamp)] = 0;  // Beacuse sometimes the month of the EndDate is skipped
             break;

         case "y":
             // We generate each year between the StartDate and the EndDate
             while($CurrentStamp <= $EndStamp)
             {
                 $ArrayPeriods[date("Y", $CurrentStamp)] = 0;
                 $CurrentStamp = mktime(0, 0, 0, date("m", $CurrentStamp), date("d", $CurrentStamp), date("Y", $CurrentStamp) + 1);
             }

             $ArrayPeriods[date("Y", $EndStamp)] = 0;  // Beacuse sometimes the year of the EndDate is skipped
             break;
     }

     return $ArrayPeriods;
 }


/**
 * Give the values of the X axe in relation with the period and if there is a smoothline graphic.
 *
 * @author STNA/7SQ
 * @version 1.2
 *     - 2006-02-14 : patch the generation of the months between 2 dates
 *     - 2015-06-23 : taken into account the mode "wd" (week, displayed with a date) and patch a bug
 *                    with the mode "w" and "wd"
 *
 * @since 2004-07-30
 *
 * @param $StartDate            date (yyyy-mm-dd)     Start date of the X axe.
 * @param $EndDate              date (yyyy-mm-dd)     End date of teh X axe.
 * @param $Period               String                Amount of time (« d » for day, « w » for week,
 *                                                    « wd » for week/day, « m » for month et « y » for year).
 * @param $SmoothLine           Boolean               There is a smoothline or not
 *
 * @return Mixed array                                The values of the X axe in relation with the
 *                                                    period and if there is a smoothline graphic.
 */
 function getGraphicAxeXValuesStats($StartDate, $EndDate, $Period = "w", $SmoothLine = FALSE)
 {
     // Result array
     $ArrayAxeXValues = array();

     // Initialize the stamps
     $CurrentStamp = strtotime($StartDate);
     $EndStamp = strtotime($EndDate);

     switch(strToLower($Period))
     {
         case "d":
             if ($SmoothLine)
             {
                 // Days in timestamp
                 while($CurrentStamp <= $EndStamp)
                 {
                     $ArrayAxeXValues[] = $CurrentStamp;
                     if (date("d", $CurrentStamp + 86400) == date("d", $CurrentStamp))
                     {
                         $CurrentStamp += 90000;  // +1 day and take into account the summer/winter time
                     }
                     else
                     {
                         $CurrentStamp += 86400;  // +1 day
                     }
                 }

                 if ($CurrentStamp - 86400 < $EndStamp)
                 {
                     $ArrayAxeXValues[] = $EndStamp;
                 }
             }
             else
             {
                 // Days displayed with the date format
                 while($CurrentStamp <= $EndStamp)
                 {
                     $ArrayAxeXValues[] = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], $CurrentStamp);
                     if (date("d", $CurrentStamp + 86400) == date("d", $CurrentStamp))
                     {
                         $CurrentStamp += 90000;  // +1 day and take into account the summer/winter time
                     }
                     else
                     {
                         $CurrentStamp += 86400;  // +1 day
                     }
                 }

                 if ($CurrentStamp - 86400 < $EndStamp)
                 {
                     $ArrayAxeXValues[] = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], $EndStamp);
                 }
             }
             break;

         default:
         case "w":
         case "wd":
             if ($SmoothLine)
             {
                 // Weeks in timestamp
                 while($CurrentStamp <= $EndStamp)
                 {
                     $ArrayAxeXValues[] = $CurrentStamp;
                     if ((date("W", $CurrentStamp + 604800) == date("W", $CurrentStamp))
                         || (date("N", $CurrentStamp + 604800) != date("N", $CurrentStamp)))
                     {
                         $CurrentStamp += 608400;   // +7 days and take into account the summer/winter time
                     }
                     else
                     {
                         $CurrentStamp += 604800;   // +7 days
                     }
                 }

                 if ((integer)date("W", $CurrentStamp - 604800) < (integer)date("W", $EndStamp))
                 {
                     // Because sometimes the week of the EndDate is skipped
                     $ArrayAxeXValues[] = $CurrentStamp;
                 }
             }
             else
             {
                 // Weeks displayed with the format "Sn°week"
                 while($CurrentStamp <= $EndStamp)
                 {
                     if (strToLower($Period) == 'wd')
                     {
                         $ArrayAxeXValues[] = date("Y-m-d", $CurrentStamp);
                     }
                     else
                     {
                         $ArrayAxeXValues[] = substr(ucfirst($GLOBALS["LANG_WEEK"]), 0, 1).date("W", $CurrentStamp);
                     }

                     if ((date("W", $CurrentStamp + 604800) == date("W", $CurrentStamp))
                         || (date("N", $CurrentStamp + 604800) != date("N", $CurrentStamp)))
                     {
                         $CurrentStamp += 608400;  // +7 days and take into account the summer/winter time
                     }
                     else
                     {
                         $CurrentStamp += 604800;  // +7 days
                     }
                 }

                 if ((integer)date("W", $CurrentStamp - 604800) < (integer)date("W", $EndStamp))
                 {
                     // Because sometimes the week of the EndDate is skipped
                     if (strToLower($Period) == 'wd')
                     {
                         $ArrayAxeXValues[] = date("Y-m-d", $CurrentStamp);
                     }
                     else
                     {
                         $ArrayAxeXValues[] = substr(ucfirst($GLOBALS["LANG_WEEK"]), 0, 1).date("W", $EndStamp);
                     }
                 }
             }
             break;

         case "m":
             // To generate the months, the used day is the first (because some months haven't 29, 30, 31 days)
             $CurrentStamp = strtotime(date("Y-m-01", $CurrentStamp));
             if ($SmoothLine)
             {
                 // Months in timestamp
                 while($CurrentStamp <= $EndStamp)
                 {
                     $ArrayAxeXValues[] = $CurrentStamp;
                     $CurrentStamp = mktime(0, 0, 0, date("m", $CurrentStamp) + 1, date("d", $CurrentStamp), date("Y", $CurrentStamp));
                 }

                 if ((integer)date("m", mktime(0, 0, 0, date("m", $CurrentStamp) - 1, date("d", $CurrentStamp), date("Y", $CurrentStamp))) < (integer)date("m", $EndStamp))
                 {
                     // Because sometimes the month of the EndDate is skipped
                     $ArrayAxeXValues[] = $CurrentStamp;
                 }
             }
             else
             {
                 // Months displayed with the format "mm-yyyy"
                 while($CurrentStamp <= $EndStamp)
                 {
                     $ArrayAxeXValues[] = date("m-Y", $CurrentStamp);
                     $CurrentStamp = mktime(0, 0, 0, date("m", $CurrentStamp) + 1, date("d", $CurrentStamp), date("Y", $CurrentStamp));
                 }

                 if ((integer)date("m", mktime(0, 0, 0, date("m", $CurrentStamp) - 1, date("d", $CurrentStamp), date("Y", $CurrentStamp))) < (integer)date("m", $EndStamp))
                 {
                     // Because sometimes the month of the EndDate is skipped
                     $ArrayAxeXValues[] = date("m-Y", $CurrentStamp);
                 }
             }
             break;

         case "y":
             if ($SmoothLine)
             {
                 // Years in timestamp
                 while($CurrentStamp <= $EndStamp)
                 {
                     $ArrayAxeXValues[] = $CurrentStamp;
                     $CurrentStamp = mktime(0, 0, 0, date("m", $CurrentStamp), date("d", $CurrentStamp), date("Y", $CurrentStamp) + 1 );
                 }

                 if ((integer)date("m", mktime(0, 0, 0, date("m", $CurrentStamp), date("d", $CurrentStamp), date("Y", $CurrentStamp) - 1)) < (integer)date("Y", $EndStamp))
                 {
                     // Because sometimes the year of the EndDate is skipped
                     $ArrayAxeXValues[] = $CurrentStamp;
                 }
             }
             else
             {
                 // Years displayed with the format "yyyy"
                 $StartYear = (integer)date("Y", $CurrentStamp);
                 $EndYear = (integer)date("Y", $EndStamp);
                 foreach(range($StartYear, $EndYear) as $Year)
                 {
                     $ArrayAxeXValues[] = $Year;
                 }
             }
             break;
     }

     // Return the X axe values
     return $ArrayAxeXValues;
 }


/**
 * Give the values of the X axe in relation with the period and if there is a smoothline graphic.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-07-30
 *
 * @param $Period               String                Amount of time (« d » for day, « w » for week,
 *                                                    « m » for month et « y » for year).
 * @param $SmoothLine           Boolean               There is a smoothline or not
 *
 * @return String                                     The values of the X axe in relation with the
 *                                                    period and if there is a smoothline graphic.
 */
 function getLabelFormatCallbackStats($Period = "w", $SmoothLine = FALSE)
 {
     if ($SmoothLine)
     {
         switch(strToLower($Period))
         {
             case "d":
                 //$graph->xaxis->SetLabelFormatCallback('xScaleCallbackDay');
                 $fonctionSmoothLine='xScaleCallbackDay';
                 break;

             case "w":
                 //$graph->xaxis->SetLabelFormatCallback('xScaleCallbackWeek');
                 $fonctionSmoothLine='xScaleCallbackWeek';
                 break;

             case "m":
                 //$graph->xaxis->SetLabelFormatCallback('xScaleCallbackMonth');
                 $fonctionSmoothLine='xScaleCallbackMonth';
                 break;

             case "y":
                 //$graph->xaxis->SetLabelFormatCallback('xScaleCallbackYear');
                 $fonctionSmoothLine='xScaleCallbackYear';
                 break;
         }

         // smoothed line
     }
     else
     {
         $fonctionSmoothLine = "";
     }

     // Return the X function name
     return $fonctionSmoothLine;
 }


/**
 * Give the values of sql period used by select
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-07-30
 *
 * @param $Period               String           Amount of time (« d » for day, « w » for week,
 *                                               « m » for month et « y » for year).
 *
 * @return String                                The value used by SELECT keywork AS
 */
 function getSqlPeriodAowStats($Period = "w")
 {
     // Construct a string which contains the values of DATE_FORMAT expressions and usable by the SQL IN instruction
     // Depending on period
     switch(strToLower($Period))
     {
         // day
         case "d" :
              return "DAY";
              break;

         // Default week
         default:
         case "w" :
              return "WEEK";
              break;

         // month
         case "m" :
              return "YEARMONTH";
              break;

         // year
         case "y" :
              return "YEAR";
              break;

     }
}


/**
 * Give the values of sql period used by select
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2005-06-02 : taken into account another fildname than "h.AowStatusHistoryDate"
 *
 * @since 2004-07-30
 *
 * @param $Period               String           Amount of time (« d » for day, « w » for week,
 *                                               « m » for month and « y » for year).
 * @param $SqlPeriod            String           Values given by getSqlPeriodAowStats
 * @param $Aggregate            String           Aggregates function MIN, MAX
 * @param $Fieldname            String           Name of the field to format
 *
 * @return String                                The value used by SELECT keywork date_format
 */
 function getSqlPeriodTextAowStats($Period = "w", $SqlPeriod = "WEEK", $Aggregate = "", $Fieldname = "h.AowStatusHistoryDate")
 {
     // Construct the aggregate string function
     if ($Aggregate == "")
     {
         $StartAggregate = "";
         $EndAggregate = "";
     }
     else
     {
         $StartAggregate = $Aggregate . "(";
         $EndAggregate = ")";
     }

     // Construct a string which contains the values of DATE_FORMAT expressions and usable by the SQL IN instruction
     // Depending on period
     switch(strToLower($Period))
     {
         // day
         case "d" :
              return  "$StartAggregate DATE_FORMAT($Fieldname,'%Y-%m-%d')$EndAggregate AS $SqlPeriod";
              break;

         // Default week
         default:
         case "w" :
              return "$StartAggregate DATE_FORMAT($Fieldname,'%Y-%u')$EndAggregate AS $SqlPeriod";
              break;

         // month
         case "m" :
              return "$StartAggregate DATE_FORMAT($Fieldname,'%Y-%m')$EndAggregate AS $SqlPeriod";
              break;

         // year
         case "y" :
              return "$StartAggregate DATE_FORMAT($Fieldname,'%Y')$EndAggregate AS $SqlPeriod";
              break;

     }
}


/**
 * Give the values of sql BETWEEN  used by select date
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2005-06-02 : taken into account another fildname than "h.AowStatusHistoryDate"
 *
 * @since 2004-07-30
 *
 * @param $StartDate            date (yyyy-mm-dd)     Start date event
 * @param $EndDate              date (yyyy-mm-dd)     End date event
 * @param $Fieldname            String                Name of the field to format
 *
 * @return String                                     The value used by SELECT keywork date_format
 */
 function getDateConditionTextAowStats($StartDate, $EndDate, $Fieldname = "h.AowStatusHistoryDate")
 {
     // Check StartDate if the string is empty, the request doesn't contain date conditions
     if ($StartDate == "")
     {
         // StartDate empty
         if ($EndDate == "")
         {
             // the two dates are empties strings
             $AowDateCondition = "";
         }
         else
         {
             $AowDateCondition = " AND DATE_FORMAT($Fieldname,'%Y-%m-%d') <= '$EndDate'";
         }
     }
     else
     {
         // StartDate not empty
         if ($EndDate == "")
         {
             $AowDateCondition = " AND DATE_FORMAT($Fieldname,'%Y-%m-%d') BETWEEN '$StartDate' AND CURDATE()";
         }
         else
         {
             $AowDateCondition = " AND DATE_FORMAT($Fieldname,'%Y-%m-%d') BETWEEN '$StartDate' AND '$EndDate'";
         }
     }

     return $AowDateCondition;
 }
?>