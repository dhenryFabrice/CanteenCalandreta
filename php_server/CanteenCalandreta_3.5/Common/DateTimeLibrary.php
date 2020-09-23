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
 * Common module : library of date/time functions
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2012-07-10
 */


//########################### DATE FUNCTIONS ###############################
/**
 * Check if the value of the parameter is a valide date
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2004-04-15 : check a date which uses another separator than this defined in the configuration, but
 *                    respect the english format order (hours, minutes, secondes)
 *
 * @since 2004-01-13
 *
 * @param $DateValue            String     Value to ckeck if it's a valide date
 * @param $DateSeparator        String     Seperator between the day, month and year
 *
 * @return Boolean                         TRUE if the value is a valide date, FALSE otherwise
 */
 function isValideDate($DateValue, $DateSeparator = "")
 {
     if (($DateValue == "") || (is_Null($DateValue)))
     {
         return TRUE;
     }

     // Position of the day, month and year in the $DateValue string
     $PosDay = -1;
     $PosMonth = -1;
     $PosYear = -1;

     // If the date seperator given is an empty string, we use the display date configuration (Config.php)
     if ($DateSeparator == "")
     {
         // We analyse the date format ($CONF_DATE_DISPLAY_FORMAT)
         $arrayDateDisplayFormat = explode($GLOBALS["CONF_DATE_SEPARATOR"], $GLOBALS["CONF_DATE_DISPLAY_FORMAT"]);
         $arrayDateTmp = explode($GLOBALS["CONF_DATE_SEPARATOR"], $DateValue);
     }
     else
     {
         // Otherwise, we use the default date format : yyyy-mm-dd (=> Y-m-d)
         $arrayDateDisplayFormat = array("Y", "m", "d");
         $arrayDateTmp = explode($DateSeparator, $DateValue);
     }

     // The date must have 3 fields : day, month and year at least (name/number of the day is optional)
     if ((count($arrayDateDisplayFormat) >= 3) && (count($arrayDateDisplayFormat) <= 4)
         && (count($arrayDateDisplayFormat) == count($arrayDateTmp)))
     {
         foreach($arrayDateTmp as $i => $CurrentValue)
         {
             switch($arrayDateDisplayFormat[$i])
             {
                 // Values in relation with the day format
                 case "d":
                           // Is the day coded on 2 digits?
                           if (strlen($CurrentValue) == 2)
                           {
                               if (($CurrentValue >= 1) && ($CurrentValue <= 31))
                               {
                                   $PosDay = $i;
                               }
                           }
                           break;

                 // Values in relation with the month format
                 case "m":
                           // Is the month coded on 2 digits?
                           if (strlen($CurrentValue) == 2)
                           {
                               if (($CurrentValue >= 1) || ($CurrentValue <= 12))
                               {
                                   $PosMonth = $i;
                               }
                           }
                           break;

                 // Values in relation with the year format
                 case "Y":
                           // Is the year coded on 4 digits?
                           if (preg_match("[\d\d\d\d]", $CurrentValue) == 1)
                           {
                               $PosYear = $i;
                           }
                           break;
             }
         }

         // The date format is ok : is the date a valide date?
         if (($PosDay != -1) && ($PosMonth != -1) && ($PosYear != -1))
         {
             return checkDate($arrayDateTmp[$PosMonth], $arrayDateTmp[$PosDay], $arrayDateTmp[$PosYear]);
         }
     }

     return FALSE ;
 }


/**
 * Convert the value of the parameter into an english format date
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-01-14
 *
 * @param $DateValue            String     Value to ckeck if it's a valide date. This value must have the config format
 *
 * @return String                          The date formated in the english format date, an empty string otherwise
 */
 function formatedDate2EngDate($DateValue)
 {
     if (($DateValue == "") || (is_Null($DateValue)))
     {
         return "";
     }

     // Check if the date is a valide date
     if (isValideDate($DateValue))
     {
         // We analyse the date format ($CONF_DATE_DISPLAY_FORMAT)
         $arrayDateDisplayFormat = explode($GLOBALS["CONF_DATE_SEPARATOR"], $GLOBALS["CONF_DATE_DISPLAY_FORMAT"]);
         $arrayDateTmp = explode($GLOBALS["CONF_DATE_SEPARATOR"], $DateValue);

         foreach($arrayDateTmp as $i => $CurrentValue)
         {
             switch($arrayDateDisplayFormat[$i])
             {
                 // Values in relation with the day format
                 case "d":
                           $PosDay = $i;
                           break;

                 // Values in relation with the month format
                 case "m":
                           $PosMonth = $i;
                           break;

                 // Values in relation with the year format
                 case "Y":
                           $PosYear = $i;
                           break;
             }
         }

         // English format date : AAAA-MM-JJ
         return $arrayDateTmp[$PosYear]."-".$arrayDateTmp[$PosMonth]."-".$arrayDateTmp[$PosDay];
     }

     return "";
 }


/**
 * Get the given date with another format
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-05-03
 *
 * @param $DateValue           String     Date, in config format or english format, for which we want to get its week number
 * @param $Format              String     Format of the date to return
 * @param $DateSeparator       String     Seperator between the day, month and year
 *
 * @return String                         The date with a given format, an empty string otherwise
 */
 function formatDate($DateValue, $Format, $DateSeparator = "")
 {
     if (isValideDate($DateValue, $DateSeparator))
     {
         if ($DateSeparator == "")
         {
             // The date has the config format
             $DateValue = formatedDate2EngDate($DateValue);
         }
         else
         {
             // The date has the english format (AAAA MM DD) but we have to modify the separator
             if ($DateSeparator != "-")
             {
                 $DateValue = str_replace($DateSeparator, "-", $DateValue);
             }
         }

         // We get the timestamp of the date and return the date with the given format
         return date($Format, strtotime($DateValue));
     }

     // ERROR
     return "";
 }


/**
 * Give the nomber of days between 2 dates : date2 - date1
 *
 * @author STNA/7SQ
 * @version 2.1
 *     - 2005-06-06 : taken into account only the working days
 *     - 2005-07-12 : patch of the returned number of working days
 *
 * @since 2004-07-23
 *
 * @param $Date1                 Timestamp     The first date
 * @param $Date2                 Timestamp     The second date
 * @param $bOnlyWorkingDays      Boolean       Count only the working days
 *
 * @return Integer               Number of days between the 2 dates, with or without only working days
 */
 function getNbDaysBetween2Dates($Date1, $Date2, $bOnlyWorkingDays = FALSE)
 {
     if ($bOnlyWorkingDays)
     {
         // Only working days are taken into account
         $StartDay = (integer)date("d", $Date1);
         $StartMonth = (integer)date("m", $Date1);
         $StartYear = date("Y", $Date1);
         $EndDay = (integer)date("d", $Date2);
         $EndMonth = (integer)date("m", $Date2);
         $EndYear = date("Y", $Date2);

         // We get the working days for all these months
         $ArrayWorkingDays = jours_ouvres(
                                          range($StartMonth, (($EndYear - $StartYear) * 12) + $EndMonth),
                                          $StartYear
                                         );

         // We remove the working days which are before $Date1...
         foreach($ArrayWorkingDays[$StartMonth] as $i => $CurrentValue)
         {
             if ($i < $StartDay)
             {
                 unset($ArrayWorkingDays[$StartMonth][$i]);
             }
         }

         //... and after $Date2
         // We get the last index of the month in the array
         $ArrayKeys = array_keys($ArrayWorkingDays);
         $EndMonthInArray = $ArrayKeys[count($ArrayKeys) - 1];
         foreach($ArrayWorkingDays[$EndMonthInArray] as $i => $CurrentValue)
         {
             if ($i > $EndDay)
             {
                 unset($ArrayWorkingDays[$EndMonthInArray][$i]);
             }
         }

         // We count the working days
         $NbWorkingDays = 0;
         foreach($ArrayWorkingDays as $i => $CurrentMonth)
         {
             $NbWorkingDays += count($CurrentMonth);
         }

         // The current day must not be counted
         return $NbWorkingDays;
     }
     else
     {
         // Working days and holidays are taken into account
         return floor(($Date2 - $Date1) / 86400);
     }
 }


/**
 * Liste des jours ouvrés dans un mois. Pour avoir la liste des jours ouvrés de l'annee, passez un
 * tableau de mois : jours_ouvres(range(1, 12), $an);
 * Pour les avoir sur plusieurs années : jours_ouvres(range(36, 12), $an);
 *
 * @author : Damien Seguy <damien.seguy@nexen.net>
 * @version 1.0
 * @since 2005-06-06
 *
 * @param $mois      Integer      Month
 * @param $an        Integer      Year
 *
 * @return Array of Integers      List of the working days (their number of day in the month) for each
 *                                month
 */
 function jours_ouvres($mois, $an)
 {
     if (is_array($mois))
     {
         $retour = array();
         foreach ($mois as $m)
         {
             $retour[$m] = jours_ouvres($m, $an);
         }
         return $retour;
     }

     if (mktime(0, 0, 0, $mois, 0, $an) == -1)
     {
         return FALSE;
     }

     list($mois, $an) = explode("-", date("m-Y", mktime(0, 0, 0, $mois + 1, 0, $an)));
     $an = intval($an);
     $mois = intval($mois);

     $nb = date("t", mktime(0, 0, 0, $mois, 1, $an));
     $tous = range(0, $nb);
     unset($tous[0]);

     $premier =  date("w", mktime(0,0,0,$mois, 1, $an));
     $samedi = 7 - $premier;
     $dimanche =  (8 - $premier) % 8;
     if ($dimanche == 0)
     {
         $dimanche = 1;
     }

     $i = 0;
     while ($i < 5)
     {
         unset($tous[$samedi + 7 * $i]);
         unset($tous[$dimanche + 7*$i]);
         $i++ ;
     }

     $ferie = array_keys(ferie($mois, $an));
     foreach ($ferie as $f)
     {
         unset($tous[$f]);
     }

     return $tous;
}


/**
 * Liste des jours ouvrés Fait la liste des jours fériés dans un ou plusieurs mois. Utilise le
 * calendrier Francais.
 *
 * @author : Damien Seguy <damien.seguy@nexen.net>
 * @version 1.1
 *     - 2019-12-09 : v1.1. Replace each() by foreach() because deprecated
 *
 * @since 2005-06-06
 *
 * @param $mois      Integer      Month
 * @param $an        Integer      Year
 *
 * @return Array of strings       List of the no working days (number of day in the month + name
 *                                of the day)
 */
 function ferie($mois, $an)
 {
     // pour avoir tous les jours feries de l'annee,
     // passez un tableau de mois (ferie(range(1,12), $an);
     // pour les avoir sur plusieurs annees
     // ferie(range(1,24), $an); ferie(range(36,12), $an);
     if (is_array($mois))
     {
         $retour = array();
         foreach ($mois as $m)
         {
             $r = ferie($m, $an);
             $retour[$m] = ferie($m, $an);
         }
         return $retour;
     }

     // calcul des jours feries pour un seul mois.
     if (mktime(0, 0, 0, $mois, 1, $an) == -1)
     {
         return FALSE;
     }

     list($mois, $an) = explode("-", date("m-Y", mktime(0, 0, 0, $mois, 1, $an)));
     $an = intval($an);
     $mois = intval($mois);

     // une constante
     $jour = 3600*24;

     // quelques fetes mobiles
     $lundi_de_paques['mois'] = date( "n", easter_date($an)+1*$jour);
     $lundi_de_paques['jour'] = date( "j", easter_date($an)+1*$jour);
     $lundi_de_paques['nom']  = "Lundi de P&acirc;ques";

     $ascencion['mois'] = date( "n", easter_date($an)+39*$jour);
     $ascencion['jour'] = date( "j", easter_date($an)+39*$jour);
     $ascencion['nom']  = "Jeudi de l'ascenscion";

     $vendredi_saint['mois'] = date( "n", easter_date($an)-2*$jour);
     $vendredi_saint['jour'] = date( "j", easter_date($an)-2*$jour);
     $vendredi_saint['nom']  = "Vendredi Saint";

     $lundi_de_pentecote['mois'] = date( "n", easter_date($an)+50*$jour);
     $lundi_de_pentecote['jour'] = date( "j", easter_date($an)+50*$jour);
     $lundi_de_pentecote['nom']  = "Lundi de Pentec&ocirc;te";

     // France
     $ferie["Jour de l'an"][1] = 1;
     $ferie["Armistice 39-45 "][5] = 8;
     $ferie["Toussaint"][11] = 1;
     $ferie["Armistice 14-18"][11] = 11;
     $ferie["Assomption"][8] = 15;
     $ferie["F&ecirc;te du travail "][5] = 1;
     $ferie["F&ecirc;te nationale"][7] = 14;
     $ferie["No&euml;l"][12] = 25;
     $ferie["Lendemain de No&euml;l (Alsace seulement)"][12] = 25;
     $ferie[$lundi_de_paques['nom']][$lundi_de_paques['mois']] = $lundi_de_paques['jour'];
     $ferie[$lundi_de_pentecote['nom']][$lundi_de_pentecote['mois']] = $lundi_de_pentecote['jour'];
     $ferie[$ascencion['nom']][$ascencion['mois']] = $ascencion['jour'];
     //$ferie[$vendredi_saint['nom']." (Alsace)"][$vendredi_saint['mois']] = $vendredi_saint['jour'];

     // reponse
     $reponse = array();
     foreach($ferie as $nom => $date)
     {
         if (isset($date[$mois]))
         {
             // une fete a date calculable
             $reponse[$date[$mois]] = $nom;
         }
     }
     ksort($reponse);

     return $reponse;
}


/**
 * Jour férié? Donner un timestamp unix en paramètre. Retourne 0 si jour_férié ou week-end
 *
 * @author : Plom <tbb.plom@libertysurf.fr>
 * @version 1.1
 *     2012-05-15 : patch a bug on date_ascension (+39 and not +38)
 *
 * @since 2005-06-06
 *
 * @param $date        timestamp      Date to check if it's a working day
 *
 * @return Integer                    0 if the given date isn't a working day, NULL otherwise
 */
 function jour_ferie($date)
 {
     // Donner un timestamp unix en paramètre
     // Retourne si jour_férié ou week-end
     $jour = date("d", $date);
     $mois = date("m", $date);
     $annee = date("Y", $date);

     if ($jour == 1 && $mois == 1) return 0; // 1er janvier
     if ($jour == 1 && $mois == 5) return 0; // 1er mai
     if ($jour == 8 && $mois == 5) return 0; // 8 mai
     if ($jour == 14 && $mois == 7) return 0; // 14 juillet
     if ($jour == 15 && $mois == 8) return 0; // 15 aout
     if ($jour == 1 && $mois == 11) return 0; // 1 novembre
     if ($jour == 11 && $mois == 11) return 0; // 11 novembre
     if ($jour == 25 && $mois == 12) return 0; // 25 décembre

     $date_paques = easter_date($annee);
     $jour_paques = date("d", $date_paques);
     $mois_paques = date("m", $date_paques);
     if ($jour_paques == $jour && $mois_paques == $mois) return 0; // Pâques

     $date_ascension = mktime(date("H", $date_paques),
                               date("i", $date_paques),
                               date("s", $date_paques),
                               date("m", $date_paques),
                               date("d", $date_paques) + 39,
                               date("Y", $date_paques)
                              );

     $jour_ascension = date("d", $date_ascension);
     $mois_ascension = date("m", $date_ascension);
     if ($jour_ascension == $jour && $mois_ascension == $mois) return 0; // Ascension

     $date_pentecote = mktime(date("H", $date_ascension),
                               date("i", $date_ascension),
                               date("s", $date_ascension),
                               date("m", $date_ascension),
                               date("d", $date_ascension) + 11,
                               date("Y", $date_ascension)
                              );
     $jour_pentecote = date("d", $date_pentecote);
     $mois_pentecote = date("m", $date_pentecote);
     if ($jour_pentecote == $jour && $mois_pentecote == $mois) return 0; // Pentecote

     $jour_julien = unixtojd($date);
     $jour_semaine = jddayofweek($jour_julien, 0);
     if ($jour_semaine == 0 || $jour_semaine == 6) return 0; // Jour de la semaine (0 pour dimanche et 6 pour samedi)
 }


/**
 * Give periodic dates between 2 dates.
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2011-07-12 : add more repeat modes (3 months, 6 months...)
 *
 * @since 2007-10-02
 *
 * @param $StartDate           Timestamp     The start date
 * @param $EndDate             Timestamp     The end date
 * @param $RepetitionMode      Const         The mode of repetition
 * @param $Frequency           Integer       The frequency of the repetition
 * @param $bOnlyWorkingDays    Boolean       Keep only the working days
 *
 * @return Array of dates      the dates (yyyy-mm-dd) matching to the repetition mode and the frequency
 *                             between 2 dates, empty array otherwise
 */
 function getRepeatedDates($StartDate, $EndDate, $RepetitionMode = REPEAT_DAILY, $Frequency = 1, $bOnlyWorkingDays = FALSE)
 {
     // We check the parameters
     if (($StartDate < $EndDate) && ($RepetitionMode > 0) && ((integer)$Frequency > 0))
     {
         // Contains the periodic dates between the start and end dates
         $ArrayDates = array();

         $CurrentStamp = $StartDate;
         $EndStamp = $EndDate;

         // Treat special repetition modes
         switch($RepetitionMode)
         {
             case REPEAT_EVERY_2_MONTHS:
                 $RepetitionMode = REPEAT_MONTHLY;
                 $Frequency = 2 * $Frequency;
                 break;

             case REPEAT_EVERY_3_MONTHS:
                 $RepetitionMode = REPEAT_MONTHLY;
                 $Frequency = 3 * $Frequency;
                 break;

             case REPEAT_EVERY_6_MONTHS:
                 $RepetitionMode = REPEAT_MONTHLY;
                 $Frequency = 6 * $Frequency;
                 break;

             case REPEAT_EVERY_YEAR:
                 $RepetitionMode = REPEAT_MONTHLY;
                 $Frequency = 12 * $Frequency;
                 break;

             case REPEAT_EVERY_2_YEARS:
                 $RepetitionMode = REPEAT_MONTHLY;
                 $Frequency = 24 * $Frequency;
                 break;

             case REPEAT_EVERY_3_YEARS:
                 $RepetitionMode = REPEAT_MONTHLY;
                 $Frequency = 36 * $Frequency;
                 break;

             case REPEAT_EVERY_4_YEARS:
                 $RepetitionMode = REPEAT_MONTHLY;
                 $Frequency = 48 * $Frequency;
                 break;

             case REPEAT_EVERY_5_YEARS:
                 $RepetitionMode = REPEAT_MONTHLY;
                 $Frequency = 60 * $Frequency;
                 break;
         }

         // Compute dates
         switch($RepetitionMode)
         {
             case REPEAT_WEEKLY:
                 // We generate all dates between start and end date
                 // with a periodicity of the week and a given frequency
                 while($CurrentStamp <= $EndStamp)
                 {
                     $ArrayDates[] = date("Y-m-d", $CurrentStamp);
                     $CurrentStamp = strtotime("+$Frequency week", $CurrentStamp);  // +n weeks
                 }
                 break;

             case REPEAT_MONTHLY:
                 // We generate all dates between start and end date
                 // with a periodicity of the month and a given frequency
                 $InitialDay = date('d', $CurrentStamp);
                 $CurrentMonth = date('m', $CurrentStamp);
                 $CurrentYear = date('Y', $CurrentStamp);
                 while($CurrentStamp <= $EndStamp)
                 {
                     // The used algorithm is the same as this of Lotus Notes :
                     // wrong dates aren't kept
                     if ($InitialDay == date('d', $CurrentStamp))
                     {
                         $ArrayDates[] = date("Y-m-d", $CurrentStamp);
                     }

                     $CurrentDay = $InitialDay;
                     $CurrentMonth += $Frequency;
                     if ($CurrentMonth > 12)
                     {
                         $CurrentYear += floor($CurrentMonth / 12);
                         $CurrentMonth %= 12;
                     }

                     // We check if it's a valid date
                     while((!checkdate($CurrentMonth, $CurrentDay, $CurrentYear)) && ($CurrentDay > 0))
                     {
                         // We decrease the day until it will becaume a valid date
                         $CurrentDay--;
                     }

                     $CurrentStamp = strtotime("$CurrentYear-$CurrentMonth-$CurrentDay");
                 }
                 break;

             case REPEAT_DAILY:
             default:
                 // We generate all dates between start and end date
                 // with a periodicity of the day and a given frequency
                 while($CurrentStamp <= $EndStamp)
                 {
                     $ArrayDates[] = date("Y-m-d", $CurrentStamp);
                     $CurrentStamp = strtotime("+$Frequency day", $CurrentStamp);  // +n days
                 }
                 break;
         }

         if ($bOnlyWorkingDays)
         {
             // We keep only the working days
             foreach($ArrayDates as $i => $CurrentDate)
             {
                 if (jour_ferie(strtotime($CurrentDate)) === 0)
                 {
                     // We don't keep this day
                     unset($ArrayDates[$i]);
                 }
             }
         }

         return array_values($ArrayDates);
     }
     else
     {
         // Error
         return array();
     }
 }


/**
 * Give the number of weeks of a year.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-10-04
 *
 * @param $Year         Integer     The year for which we want the number of weeks
 *
 * @return Integer      The number of weeks of the given year.
 */
 function getNbWeeksOfYear($Year)
 {
     $CurrentStamp = strtotime("$Year-12-31");
     $NbWeeks = date("W", $CurrentStamp);   // here, could be == 1 for some years
     while($NbWeeks < 50)
     {
         $CurrentStamp = strtotime("-1 day", $CurrentStamp);
         $NbWeeks = date("W", $CurrentStamp);
     }

     return $NbWeeks;
 }


/**
 * Give the date of the monday of a given week.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-10-08
 *
 * @param $Week         Integer     The week for which we want the date of its monday [1..53]
 * @param $Year         Integer     The year of the week
 *
 * @return Date         The date of the monday of the week.
 */
 function getFirstDayOfWeek($Week, $Year)
 {
     if ($Week > 0)
     {
         if (date('W', mktime(0, 0, 0, 01, 01, $Year)) == 1)
         {
             $FirstDayOfYearStamp = mktime(0, 0, 0, 01, (01 + (($Week - 1) * 7)), $Year);
         }
         else
         {
             $FirstDayOfYearStamp = mktime(0, 0, 0, 01, (01 + ($Week * 7)), $Year);
         }

         $Shift = 0;

         // For the years beginning with a sunday
         if (date('w', $FirstDayOfYearStamp) == 0)
         {
             $Shift = 6 * 60 * 60 * 24;
         }
         else if (date('w', $FirstDayOfYearStamp) > 1)
         {
             $Shift = ((date('w', $FirstDayOfYearStamp) - 1) * 60 * 60 * 24);
         }

         return date('Y-m-d', $FirstDayOfYearStamp - $Shift);
     }

     // Error
     return '';
 }


/**
 * Give the date of a given week, year and num of day.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-09-12
 *
 * @param $Week         Integer     The week for which we want the date [1..53]
 * @param $Year         Integer     The year of the week
 * @param $NumDay       Integer     The number of the day in the week [1..7] (1 = monday)
 *
 * @return Date         The date of the week, year and number of the day.
 */
 function getDateOfYearWeekNumDay($Week, $Year, $NumDay)
 {
     if (($Week > 0) && ($Week < 54) && ($NumDay > 0) && ($NumDay < 8))
     {
         $MondayDate = getFirstDayOfWeek($Week, $Year);
         $NumDay--;
         if ($NumDay == 0)
         {
             return $MondayDate;
         }
         else
         {
             return date('Y-m-d', strtotime("+$NumDay days", strtotime($MondayDate)));
         }
     }

     // Error
     return '';
 }


/**
 * Give the year and week for a given day.
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2013-01-25 : use date() function with right parameters
 *
 * @since 2013-01-04
 *
 * @param $Date         Date     The date for which we want the year and week (YYYY-mm-dd format)
 *
 * @return String       The year and week separated with a "-" of the give day, FALSE otherwise.
 */
 function getYearWeekOfDay($Date)
 {
     if (!empty($Date))
     {
         return date("o-W", strtotime($Date));
     }

     // Error
     return FALSE;
 }


/**
 * Get the school year in relation with the given date
 *
 * @author Christophe Javouhey
 * @version 2.1
 *     - 2012-10-01 : patch the bug about wrong school year given for some months at the end of the year
 *     - 2013-06-21 : uses getSchoolYearStartDate() and getSchoolYearEndDate() functions
 *     - 2014-07-10 : change the condition for $SchoolYear++ (replace $GivenDateTime >= $CurrentSchoolYearEndDateTime
 *                    by $GivenDateTime > $CurrentSchoolYearEndDateTime
 *
 * @since 2012-01-17
 *
 * @param $GivenDate           Date        The date for which we want the school year (yyyy-mm-dd)
 *
 * @return Integer             The school year in relation with the given date, FALSE otherwise
 */
 function getSchoolYear($GivenDate)
 {
     if (!empty($GivenDate))
     {
         $GivenDateTime = strtotime($GivenDate);
         $SchoolYear = date('Y', $GivenDateTime);
         $CurrentSchoolStartDateTime = strtotime(getSchoolYearStartDate($SchoolYear));
         $CurrentSchoolYearEndDateTime = strtotime(getSchoolYearEndDate($SchoolYear));

         if (isset($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'][$SchoolYear + 1]))
         {
             $NextSchoolDateTime = strtotime($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'][$SchoolYear + 1]);
         }
         else
         {
             $NextSchoolDateTime = strtotime(date('Y-m-d'));
         }

         $NextSchoolYearEndDateTime = strtotime(date('Y-m-05', strtotime(($SchoolYear + 1).'-'.$GLOBALS['CONF_SCHOOL_YEAR_LAST_MONTH'].'-01')));

         if ($CurrentSchoolStartDateTime >= $GivenDateTime)
         {
             // We use the previous school year
             $SchoolYear--;
         }
         elseif (($GivenDateTime > $CurrentSchoolYearEndDateTime) && ($GivenDateTime <= $NextSchoolYearEndDateTime))
         {
             // We use the next school year
             $SchoolYear++;
         }

         return $SchoolYear;
     }

     // Error
     return FALSE;
 }


/**
 * Get the school year start date in relation with the given year
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2015-06-23 : allow to get the true start date of a school year
 *
 * @since 2013-06-18
 *
 * @param $GivenYear           Date        The year for which we want the school year start date (yyyy)
 * @param $bTrueDate           Boolean     TRUE to get the start date mentionned in $CONF_SCHOOL_YEAR_START_DATES
 *
 * @return Date                The school year start date (yyyy-mm-dd) in relation with the given year,
 *                             FALSE otherwise
 */
 function getSchoolYearStartDate($GivenYear, $bTrueDate = FALSE)
 {
     if ((!empty($GivenYear)) && ($GivenYear > 2000))
     {
         // Check if the school year start date is defined in the Config.php
         $StartDate = "";
         if (isset($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'][$GivenYear]))
         {
             // The school year start date is one month before the real start date of the school year
             $StartDate = $GLOBALS['CONF_SCHOOL_YEAR_START_DATES'][$GivenYear];
             if (!$bTrueDate)
             {
                 $StartDate = date("Y-m-01", strtotime("1 month ago", strtotime($StartDate)));
             }
         }
         else
         {
             // We use a default start date of school year
             if ($bTrueDate)
             {
                 $StartDate = "$GivenYear-09-01";
             }
             else
             {
                 $StartDate = "$GivenYear-08-01";
             }
         }

         return $StartDate;
     }

     // Error
     return FALSE;
 }


/**
 * Get the school year end date in relation with the given year
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-06-18
 *
 * @param $GivenYear           Date        The year for which we want the school year end date (yyyy)
 *
 * @return Date                The school year end date (yyyy-mm-dd) in relation with the given year,
 *                             FALSE otherwise
 */
 function getSchoolYearEndDate($GivenYear)
 {
     if ((!empty($GivenYear)) && ($GivenYear > 2000))
     {
         // We use a default end date of school year
         return date('Y-m-t', strtotime($GivenYear.'-'.$GLOBALS['CONF_SCHOOL_YEAR_LAST_MONTH'].'-01'));
     }

     // Error
     return FALSE;
 }


/**
 * Give the next working day of the given day
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-05-15
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $CurrentDate          Date         The date for which we want the next working day (yyyy-mm-dd)
 *
 * @return Date                 The next working day, FALSE otherwise
 */
 function getNextWorkingDay($DbConnection, $CurrentDate = NULL)
 {
     if (empty($CurrentDate))
     {
         $CurrentDate = date('Y-m-d');
     }

     $NextDate = date('Y-m-d', strtotime("+1 day", strtotime($CurrentDate)));

     // Check if the next day is wednesday, saturday or the first day of vacations
     $bMustFindAnotherNextDay = FALSE;
     if ((jour_ferie(strtotime($NextDate)) === 0) || (date('N', strtotime($NextDate)) == 3))
     {
         $bMustFindAnotherNextDay = TRUE;
     }
     else
     {
         $ArraySchoolHolidays = getHolidays($DbConnection, $NextDate, $NextDate, 'HolidayStartDate', DATES_INCLUDED_IN_PLANNING);
         if (!empty($ArraySchoolHolidays))
         {
             $bMustFindAnotherNextDay = TRUE;
         }
     }

     while($bMustFindAnotherNextDay)
     {
         // Find another next day
         $NextDate = date('Y-m-d', strtotime("+1 day", strtotime($NextDate)));

         // Check if the next day is saturday or the first day of vacations
         $bMustFindAnotherNextDay = FALSE;
         if ((jour_ferie(strtotime($NextDate)) === 0) || (date('N', strtotime($NextDate)) == 3))
         {
             $bMustFindAnotherNextDay = TRUE;
         }
         else
         {
             $ArraySchoolHolidays = getHolidays($DbConnection, $NextDate, $NextDate, 'HolidayStartDate', DATES_INCLUDED_IN_PLANNING);
             if (!empty($ArraySchoolHolidays))
             {
                 $bMustFindAnotherNextDay = TRUE;
             }
         }
     }

     return $NextDate;
 }


//########################### TIME FUNCTIONS ###############################
/**
 * Display a time taking into account the configuration of the time and its display format
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-03-26
 *
 * @param $TimeValue           String     Value of the time to display
 *
 * @return String              Formated value
 */
 function cfgFormatedTime($TimeValue)
 {
     $FormatedValue = "";

     switch($GLOBALS["CONF_AOW_UNIT_OF_TIMES"])
     {
         case "d":
               // The unit of the value is the day
               $FormatedValue = "$TimeValue ".$GLOBALS["LANG_DAY"];
               if ($TimeValue > 1)
               {
                   $FormatedValue .= "s";
               }
               break;

         case "h":
               // The unit of the value is the hour
               // Is the time value greater than 1 day
               $NbDays = floor($TimeValue / $GLOBALS["CONF_AOW_NB_HOURS_PER_DAY"]);
               if ($NbDays > 0)
               {
                   $FormatedValue = "$NbDays ".$GLOBALS["LANG_DAY"];
                   if ($NbDays > 1)
                   {
                       $FormatedValue .= "s";
                   }
                   $TimeValue = $TimeValue - ($NbDays * $GLOBALS["CONF_AOW_NB_HOURS_PER_DAY"]);
               }
               $FormatedValue .= " ".date($GLOBALS["CONF_TIME_DISPLAY_FORMAT"], mktime($TimeValue, 0, 0, 1, 1, 2004));
               break;

         case "m":
               // The unit of the value is the minute
               // Is the time value greater than 1 day
               $NbMinutesPerDay = $GLOBALS["CONF_AOW_NB_HOURS_PER_DAY"] * 60;
               $NbDays = floor($TimeValue / $NbMinutesPerDay);
               if ($NbDays > 0)
               {
                   $FormatedValue = "$NbDays ".$GLOBALS["LANG_DAY"];
                   if ($NbDays > 1)
                   {
                       $FormatedValue .= "s";
                   }
                   $TimeValue = $TimeValue - ($NbDays * $NbMinutesPerDay);
               }
               $FormatedValue .= " ".date($GLOBALS["CONF_TIME_DISPLAY_FORMAT"], mktime(0, $TimeValue, 0, 1, 1, 2004));
               break;

         case "s":
               // The unit of the value is the second
               // Is the time value greater than 1 day
               $NbSecondsPerDay = $GLOBALS["CONF_AOW_NB_HOURS_PER_DAY"] * 3600;
               $NbDays = floor($TimeValue / $NbSecondsPerDay);
               if ($NbDays > 0)
               {
                   $FormatedValue = "$NbDays ".$GLOBALS["LANG_DAY"];
                   if ($NbDays > 1)
                   {
                       $FormatedValue .= "s";
                   }
                   $TimeValue = $TimeValue - ($NbDays * $NbSecondsPerDay);
               }
               $FormatedValue .= " ".date($GLOBALS["CONF_TIME_DISPLAY_FORMAT"], mktime(0, 0, $TimeValue, 1, 1, 2004));
               break;
     }

     return $FormatedValue;
 }


/**
 * Check if a time is a valide time
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-04-15
 *
 * @param $TimeValue           String     Value of the time to chevk, with the format hh:mm:ss
 * @param $TimeSeperator       String     Seperator between the seconds, minutes and hours
 *
 * @return Boolean                        TRUE if the time value is a valide time, FALSE otherwise
 */
 function isValideTime($TimeValue, $TimeSeperator = "")
 {
     if (($TimeValue == "") || (is_Null($TimeValue)))
     {
         return TRUE;
     }

     // Position of the seconds, minutes and hours in the $TimeValue string
     $PosSec = -1;
     $PosMin = -1;
     $PosHour = -1;

     // If the time seperator given is an empty string, we use the display time configuration (Config.php)
     if ($TimeSeperator == "")
     {
         // We analyse the date format ($CONF_TIME_DISPLAY_FORMAT)
         $ArrayTimeFormat = explode($GLOBALS["CONF_TIME_SEPARATOR"], $GLOBALS["CONF_TIME_DISPLAY_FORMAT"]);
         $ArrayTimeTmp = explode($GLOBALS["CONF_TIME_SEPARATOR"], $TimeValue);
     }
     else
     {
         // Otherwise, we use the default time format : hh:mm:ss (=> H:i:s)
         $ArrayTimeFormat = array("H", "i", "s");
         $ArrayTimeTmp = explode($TimeSeperator, $TimeValue);
     }

     // The time must have 3 fields : seconds, minutes and hours
     if ((count($ArrayTimeFormat) == 3)  && (count($ArrayTimeFormat) == count($ArrayTimeTmp)))
     {
         foreach($ArrayTimeTmp as $i => $CurrentValue)
         {
             switch($ArrayTimeFormat[$i])
             {
                 // Values in relation with the seconds format
                 case "s":
                           // Is the seconds coded on 2 digits?
                           if (strlen($CurrentValue) == 2)
                           {
                               if (($CurrentValue >= 0) && ($CurrentValue <= 59))
                               {
                                   $PosSec = $i;
                               }
                           }
                           break;

                 // Values in relation with the minutes format
                 case "i":
                           // Is the minutes coded on 2 digits?
                           if (strlen($CurrentValue) == 2)
                           {
                               if (($CurrentValue >= 0) || ($CurrentValue <= 59))
                               {
                                   $PosMin = $i;
                               }
                           }
                           break;

                 // Values in relation with the hours format
                 case "H":
                           // Is the hours coded on 2 digits?
                           if (strlen($CurrentValue) == 2)
                           {
                               if (($CurrentValue >= 0) || ($CurrentValue <= 23))
                               {
                                   $PosHour = $i;
                               }
                           }
                           break;
             }
         }

         // The time format is ok : is the date a valide date?
         if (($PosSec != -1) && ($PosMin != -1) && ($PosHour != -1))
         {
             // The time format is ok
             return TRUE;
         }
     }

     // ERROR : wrong time format
     return FALSE;
 }


/**
 * Generate a list of times between start time and end time with a given step
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $StartTime           String     Value of the start time (hh:mm:ss)
 * @param $EndTime             String     Value of the end time (hh:mm:ss)
 * @param $Step                Integer    Step of the range, in minutes [1..n]
 *
 * @return Array of Strings    Array containing all time slots between start time
 *                             and end time with the given step, empty array otherwise
 */
 function generateTimeSlots($StartTime, $EndTime, $Step = 60)
 {
     if ($Step > 0)
     {
         $StartTimestamp = strtotime(date("Y-m-d $StartTime"));
         $EndTimestamp = strtotime(date("Y-m-d $EndTime"));
         $Step *= 60; // To be in seconds

         // We define the format of the hours
         $ArrayFormat = explode($GLOBALS['CONF_TIME_SEPARATOR'], $GLOBALS['CONF_TIME_DISPLAY_FORMAT']);
         if (count($ArrayFormat) >= 2)
         {
             $Format = $ArrayFormat[0].$GLOBALS['CONF_TIME_SEPARATOR'].$ArrayFormat[1];
         }
         else
         {
             $Format = $ArrayFormat[0];
         }

         $ArrayTimes = array();
         foreach(range($StartTimestamp, $EndTimestamp, $Step) as $CurrentTime)
         {
             $ArrayTimes[] = date($Format, $CurrentTime);
         }

         return $ArrayTimes;
     }

     return array();
 }


/**
 *  Find the nearest time slot of the given time value, in relation with the given step
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $TimeValue           String     Value of the time fo which we search the nearest time slot (hh:mm:ss)
 * @param $Step                Integer    Step of the range, in minutes [1..n]
 *
 * @return String              The nearest time slot of the given time value, empty string otherwise
 */
 function getNearTimeSlot($TimeValue, $Step = 60)
 {
     if ((!empty($TimeValue)) && ($Step > 0))
     {
         $Timestamp = strtotime(date("Y-m-d $TimeValue"));
         $TimeHourStamp = strtotime(date("Y-m-d H:00:00", $Timestamp));

         // We generate time slots for 1 hour
         $ArrayTimes = generateTimeSlots(date("H:i:s", $TimeHourStamp), date("H:i:s", $TimeHourStamp + 3600), $Step);

         // We search the nearest time slot of $TimeValue
         $NearestTime = '';
         $Min = 10000;
         foreach($ArrayTimes as $CurrentTime)
         {
             $fDiff = abs(strtotime(date("Y-m-d $CurrentTime:00")) - $Timestamp);
             if ($fDiff < $Min)
             {
                 $NearestTime = $CurrentTime;
                 $Min = $fDiff;
             }
         }

         return $NearestTime;
     }

     // Error
     return '';
 }
?>