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
 * Support module : define the prepared requests functions
 *
 * @author Christophe Javouhey
 * @version 2.0
 * @since 2012-01-10
 */


 function ExtractInfosFamiliesList($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We can launch the SQL request
     $DbResult = $DbConnection->query("SELECT f.FamilyLastname, c.ChildID, c.ChildFirstname, c.ChildGrade, c.ChildClass,
                                      c.ChildWithoutPork, c.ChildSchoolDate, t.TownCode, t.TownName, f.FamilyMainEmail,
                                      f.FamilySecondEmail, sm.SupportMemberPhone, c.ChildDesactivationDate
                                      FROM Families f LEFT JOIN SupportMembers sm ON (sm.SupportMemberLastname = f.FamilyLastname), Children c, Towns t
                                      WHERE f.FamilyID = c.FamilyID AND f.TownID = t.TownID
                                      AND f.FamilyDesactivationDate IS NULL
                                      GROUP BY ChildID ORDER BY f.FamilyLastname, c.ChildFirstname");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Nom" => array(),
                                  "Prénom" => array(),
                                  "Niveau" => array(),
                                  "Classe" => array(),
                                  "Repas sans porc" => array(),
                                  "Date d'inscription" => array(),
                                  "Code postal" => array(),
                                  "Commune" => array(),
                                  "E-mail principal" => array(),
                                  "E-mail secondaire" => array(),
                                  "Téléphone" => array(),
                                  "Date de départ" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayResult['Nom'][] = $Record['FamilyLastname'];
                 $ArrayResult['Prénom'][] = $Record['ChildFirstname'];
                 $ArrayResult['Niveau'][] = $GLOBALS['CONF_GRADES'][$Record['ChildGrade']];

                 // Get the right school year to use
                 if (empty($Record['ChildDesactivationDate']))
                 {
                     // We use the current school year
                     $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));

                     $ArrayResult["Date de départ"][] = "";
                 }
                 else
                 {
                     // We use the last class room of the child, so we use the desactivation date
                     $CurrentSchoolYear = getSchoolYear($Record['ChildDesactivationDate']);

                     $ArrayResult["Date de départ"][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                             strtotime($Record['ChildDesactivationDate']));
                 }

                 $ArrayResult['Classe'][] = $GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$Record['ChildClass']];

                 if ($Record['ChildWithoutPork'] == 1)
                 {
                     $ArrayResult['Repas sans porc'][] = $GLOBALS['LANG_YES'];
                 }
                 else
                 {
                     $ArrayResult['Repas sans porc'][] = $GLOBALS['LANG_NO'];
                 }

                 $ArrayResult["Date d'inscription"][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                             strtotime($Record['ChildSchoolDate']));
                 $ArrayResult['Code postal'][] = $Record['TownCode'];
                 $ArrayResult['Commune'][] = $Record['TownName'];
                 $ArrayResult['E-mail principal'][] = $Record['FamilyMainEmail'];
                 $ArrayResult['E-mail secondaire'][] = $Record['FamilySecondEmail'];
                 $ArrayResult['Téléphone'][] = $Record['SupportMemberPhone'];
             }

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


 function ExtractFamiliesToDesactivate($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We can launch the SQL request
     $DbResult = $DbConnection->query("SELECT f.FamilyID, f.FamilyLastname, f.FamilyMainEmail, f.FamilySecondEmail, sm.SupportMemberEmail,
                                      COUNT(c.ChildID) AS NbChildren, tmp.NbNotActivatedChildren FROM Families f, SupportMembers sm,
                                      Children c, (SELECT ac.FamilyID, COUNT(ac.ChildID) AS NbNotActivatedChildren FROM Children ac
                                      WHERE ac.ChildDesactivationDate IS NOT NULL GROUP BY ac.FamilyID) AS tmp
                                      WHERE f.FamilyID AND sm.SupportMemberLastname = f.FamilyLastname AND sm.SupportMemberStateID = 5
                                      AND f.FamilyID = c.FamilyID AND f.FamilyDesactivationDate IS NULL AND f.FamilyID = tmp.FamilyID
                                      GROUP BY FamilyID HAVING NbChildren = NbNotActivatedChildren ORDER BY f.FamilyLastname");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Famille" => array(),
                                  "E-mail principal" => array(),
                                  "E-mail secondaire" => array(),
                                  "E-mail compte" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayResult["Famille"][] = $Record["FamilyLastname"];
                 $ArrayResult["E-mail principal"][] = trim($Record["FamilyMainEmail"]);
                 if (!empty($Record["FamilySecondEmail"]))
                 {
                     $ArrayResult["E-mail secondaire"][] = trim($Record["FamilySecondEmail"]);
                 }
                 else
                 {
                     $ArrayResult["E-mail secondaire"][] = "";
                 }

                 $ArrayResult["E-mail compte"][] = trim($Record["SupportMemberEmail"]);
             }

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


 function ExtractMailsFamiliesList($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We get families still activated but without activated children
     $DbResult = $DbConnection->query("SELECT f.FamilyID, f.FamilyLastname, f.FamilyMainEmail, f.FamilySecondEmail, sm.SupportMemberEmail,
                                      COUNT(c.ChildID) AS NbChildren, tmp.NbNotActivatedChildren FROM Families f, SupportMembers sm,
                                      Children c, (SELECT ac.FamilyID, COUNT(ac.ChildID) AS NbNotActivatedChildren FROM Children ac
                                      WHERE ac.ChildDesactivationDate IS NOT NULL GROUP BY ac.FamilyID) AS tmp
                                      WHERE f.FamilyID AND sm.SupportMemberLastname = f.FamilyLastname AND sm.SupportMemberStateID = 5
                                      AND f.FamilyID = c.FamilyID AND f.FamilyDesactivationDate IS NULL AND f.FamilyID = tmp.FamilyID
                                      GROUP BY FamilyID HAVING NbChildren = NbNotActivatedChildren ORDER BY f.FamilyLastname");

     $MailsToRemove = array();

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $MailsToRemove[] = trim($Record["FamilyMainEmail"]);

                 if (!empty($Record["FamilySecondEmail"]))
                 {
                     $MailsToRemove[] = trim($Record["FamilySecondEmail"]);
                 }

                 $MailsToRemove[] = trim($Record["SupportMemberEmail"]);
             }

             $MailsToRemove = array_unique($MailsToRemove);
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("SELECT f.FamilyMainEmail, f.FamilySecondEmail, sm.SupportMemberEmail
                                      FROM Families f, SupportMembers sm
                                      WHERE f.FamilyID AND sm.SupportMemberLastname = f.FamilyLastname
                                      AND f.FamilyDesactivationDate IS NULL
                                      ORDER BY f.FamilyMainEmail");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array();

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 // Check if this e-mail address is in the list
                 $sEmail = trim($Record['FamilyMainEmail']);
                 if ((!empty($sEmail)) && (!in_array($sEmail, $MailsToRemove)) && (!in_array($sEmail, $ArrayResult)))
                 {
                     // No, we add this e-mail
                     $ArrayResult[] = $sEmail;
                 }

                 $sEmail = trim($Record['FamilySecondEmail']);
                 if ((!empty($sEmail)) && (!in_array($sEmail, $MailsToRemove)) && (!in_array($sEmail, $ArrayResult)))
                 {
                     // No, we add this e-mail
                     $ArrayResult[] = $sEmail;
                 }

                 $sEmail = trim($Record['SupportMemberEmail']);
                 if ((!empty($sEmail)) && (!in_array($sEmail, $MailsToRemove)) && (!in_array($sEmail, $ArrayResult)))
                 {
                     // No, we add this e-mail
                     $ArrayResult[] = $sEmail;
                 }
             }

             // Save data in a txt file
             $sTmp = implode("\n", $ArrayResult);
             $ResultFilename = $ArrayParams["ResultFilename"];
             $bResult = file_put_contents($GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename, $sTmp);

             // Create hyperlink to download the file
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                     .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                             $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


 function ExtractMailsClassroomsList($DbConnection, $ArrayParams, $OrderBy = "")
 {
     $CurrentSchoolYear = getSchoolYear(date("Y-m-d"));

     // We get children group by class and e-mails of their families
     $DbResult = $DbConnection->query("SELECT c.ChildID, c.ChildClass, f.FamilyMainEmail, f.FamilySecondEmail
                                       FROM Children c INNER JOIN Families f ON (c.FamilyID = f.FamilyID)
                                       WHERE c.ChildDesactivationDate IS NULL AND f.FamilyDesactivationDate IS NULL
                                       ORDER BY c.ChildClass, f.FamilyLastname");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array();
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayResult[$Record['ChildClass']][] = trim($Record["FamilyMainEmail"]);
                 if (!empty($Record["FamilySecondEmail"]))
                 {
                     $ArrayResult[$Record['ChildClass']][] = trim($Record["FamilySecondEmail"]);
                 }
             }
         }
     }

     $sTmp = "";
     foreach($ArrayResult as $c => $ArrayEmails)
     {
         // Remove same e-mails for a same classroom
         if (!empty($sTmp))
         {
             $sTmp .= "\n\n";
         }

         $sTmp .= "**** ".$GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$c]." ****";
         $sTmp .= "\n".implode("\n", array_unique($ArrayEmails));
     }

     // Save data in a txt file
     $ResultFilename = $ArrayParams["ResultFilename"];
     $bResult = file_put_contents($GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename, $sTmp);

     // Create hyperlink to download the file
     $Link = generateBR(2)."<p class=\"InfoMsg\">"
             .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                     $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

     return array(array(), 0, $Link);
 }


 function ExtractTotalAmountByMonth($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We can launch the SQL request
     $StartDate = formatedDate2EngDate($ArrayParams['BillForDate'][0]);
     $EndDate = formatedDate2EngDate($ArrayParams['BillForDate'][1]);

     $DbResult = $DbConnection->query("SELECT DATE_FORMAT(b.BillForDate, '%Y-%m') AS YEARMONTH,
                                       SUM(b.BillMonthlyContribution) AS TOTALCONTRIBUTIONS,
                                       SUM(b.BillCanteenAmount) AS TOTALCANTEENS, SUM(b.BillNurseryAmount) AS TOTALNURSARIES
                                       FROM Bills b WHERE b.BillForDate BETWEEN '$StartDate' AND '$EndDate'
                                       GROUP BY YEARMONTH ORDER BY YEARMONTH");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Mois" => array(),
                                  "Total cotisations" => array(),
                                  "Total cantines" => array(),
                                  "Total garderies" => array(),
                                  "Total" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayResult['Mois'][] = $Record['YEARMONTH'];
                 $ArrayResult['Total cotisations'][] = str_replace(array('.'), array(','), $Record['TOTALCONTRIBUTIONS']);
                 $ArrayResult['Total cantines'][] = str_replace(array('.'), array(','), $Record['TOTALCANTEENS']);
                 $ArrayResult['Total garderies'][] = str_replace(array('.'), array(','), $Record['TOTALNURSARIES']);

                 $ArrayResult["Total"][] = str_replace(array('.'), array(','), $Record['TOTALCONTRIBUTIONS'] + $Record['TOTALCANTEENS']
                                                                               + $Record['TOTALNURSARIES']);
             }

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


 function ExtractSchoolYearCanteensByMonth($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We can launch the SQL request
     $StartDate = formatedDate2EngDate($ArrayParams['CanteenRegistrationForDate'][0]);
     $EndDate = formatedDate2EngDate($ArrayParams['CanteenRegistrationForDate'][1]);

     // Repas en plus
     $DbResult = $DbConnection->query("SELECT DATE_FORMAT(MoreMealForDate, '%Y-%m') AS YEARMONTH,
                                       SUM(MoreMealQuantity) AS WITHPORK, SUM(MoreMealWithoutPorkQuantity) AS WITHOUTPORK
                                       FROM MoreMeals
                                       WHERE MoreMealForDate BETWEEN '$StartDate' AND '$EndDate'
                                       GROUP BY YEARMONTH ORDER BY YEARMONTH");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayMoreMeals = array();
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayMoreMeals[$Record['YEARMONTH']]["WithPork"] = $Record['WITHPORK'];
                 $ArrayMoreMeals[$Record['YEARMONTH']]["WithoutPork"] = $Record['WITHOUTPORK'];
             }
         }
     }

     // Inscriptions à la cantine
     $DbResult = $DbConnection->query("SELECT DATE_FORMAT(CanteenRegistrationForDate, '%Y-%m') AS YEARMONTH,
                                       IF(CanteenRegistrationChildGrade < 5, 1, 2) AS CHILDGRADE, CanteenRegistrationWithoutPork AS PORK,
                                       COUNT(CanteenRegistrationID) AS TOTAL
                                       FROM CanteenRegistrations
                                       WHERE CanteenRegistrationForDate BETWEEN '$StartDate' AND '$EndDate'
                                       GROUP BY YEARMONTH, CHILDGRADE, PORK ORDER BY YEARMONTH, CHILDGRADE, PORK");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Mois" => array(),
                                  "Total cantines maternelles" => array(),
                                  "Total cantines primaires" => array(),
                                  "Total cantines sans porc" => array(),
                                  "Total" => array()
                                 );

             $PreviousYearMonth = NULL;
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 if ($Record['YEARMONTH'] != $PreviousYearMonth)
                 {
                     if (!empty($PreviousYearMonth))
                     {
                         // Repas en plus
                         if (isset($ArrayMoreMeals[$PreviousYearMonth]))
                         {
                             $TotalGrades[2] += $ArrayMoreMeals[$PreviousYearMonth]['WithPork'];
                             $TotalWithoutPork += $ArrayMoreMeals[$PreviousYearMonth]['WithoutPork'];
                         }

                         $ArrayResult['Total cantines maternelles'][] = str_replace(array('.'), array(','), $TotalGrades[1]);
                         $ArrayResult['Total cantines primaires'][] = str_replace(array('.'), array(','), $TotalGrades[2]);
                         $ArrayResult['Total cantines sans porc'][] = str_replace(array('.'), array(','), $TotalWithoutPork);
                         $ArrayResult['Total'][] = str_replace(array('.'), array(','), array_sum($TotalGrades) + $TotalWithoutPork);
                     }

                     $ArrayResult['Mois'][] = $Record['YEARMONTH'];
                     $PreviousYearMonth = $Record['YEARMONTH'];
                     $TotalGrades = array(1 => 0, 2 => 0);
                     $TotalWithoutPork = 0;
                 }

                 if ($Record['PORK'] == 1)
                 {
                     // Avec porc
                     $TotalWithoutPork += $Record['TOTAL'];
                 }
                 else
                 {
                     // Sans porc
                     switch($Record['CHILDGRADE'])
                     {
                         case 1:
                             // Maternelles
                             $TotalGrades[1] = $Record['TOTAL'];
                             break;

                         case 2:
                             // Primaires
                             $TotalGrades[2] = $Record['TOTAL'];
                             break;
                     }
                 }
             }

             // Repas en plus
             if (isset($ArrayMoreMeals[$PreviousYearMonth]))
             {
                 $TotalGrades[2] += $ArrayMoreMeals[$PreviousYearMonth]['WithPork'];
                 $TotalWithoutPork += $ArrayMoreMeals[$PreviousYearMonth]['WithoutPork'];
             }

             $ArrayResult['Total cantines maternelles'][] = str_replace(array('.'), array(','), $TotalGrades[1]);
             $ArrayResult['Total cantines primaires'][] = str_replace(array('.'), array(','), $TotalGrades[2]);
             $ArrayResult['Total cantines sans porc'][] = str_replace(array('.'), array(','), $TotalWithoutPork);
             $ArrayResult['Total'][] = str_replace(array('.'), array(','), array_sum($TotalGrades) + $TotalWithoutPork);

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


function ExtractSchoolYearSplittedCanteensByMonth($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We can launch the SQL request
     $StartDate = formatedDate2EngDate($ArrayParams['CanteenRegistrationForDate'][0]);
     $EndDate = formatedDate2EngDate($ArrayParams['CanteenRegistrationForDate'][1]);

     // Repas en plus
     $DbResult = $DbConnection->query("SELECT DATE_FORMAT(MoreMealForDate, '%Y-%m') AS YEARMONTH,
                                       SUM(MoreMealQuantity) AS WITHPORK, SUM(MoreMealWithoutPorkQuantity) AS WITHOUTPORK
                                       FROM MoreMeals
                                       WHERE MoreMealForDate BETWEEN '$StartDate' AND '$EndDate'
                                       GROUP BY YEARMONTH ORDER BY YEARMONTH");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayMoreMeals = array();
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayMoreMeals[$Record['YEARMONTH']]["WithPork"] = $Record['WITHPORK'];
                 $ArrayMoreMeals[$Record['YEARMONTH']]["WithoutPork"] = $Record['WITHOUTPORK'];
             }
         }
     }

     // Inscriptions à la cantine
     $DbResult = $DbConnection->query("SELECT DATE_FORMAT(CanteenRegistrationForDate, '%Y-%m') AS YEARMONTH,
                                       IF(CanteenRegistrationChildGrade < 5, 1, 2) AS CHILDGRADE, CanteenRegistrationWithoutPork AS PORK,
                                       COUNT(CanteenRegistrationID) AS TOTAL
                                       FROM CanteenRegistrations
                                       WHERE CanteenRegistrationForDate BETWEEN '$StartDate' AND '$EndDate'
                                       GROUP BY YEARMONTH, CHILDGRADE, PORK ORDER BY YEARMONTH, CHILDGRADE, PORK");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Mois" => array(),
                                  "Total cantines maternelles avec porc" => array(),
                                  "Total cantines maternelles sans porc" => array(),
                                  "Total cantines primaires avec porc" => array(),
                                  "Total cantines primaires sans porc" => array(),
                                  "Total ajudes avec porc" => array(),
                                  "Total ajudes sans porc" => array(),
                                  "Total" => array()
                                 );

             $PreviousYearMonth = NULL;
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 if ($Record['YEARMONTH'] != $PreviousYearMonth)
                 {
                     if (!empty($PreviousYearMonth))
                     {
                         // Repas en plus
                         $MoreMealsWithPork = 0;
                         $MoreMealsWithoutPork = 0;
                         if (isset($ArrayMoreMeals[$PreviousYearMonth]))
                         {
                             $MoreMealsWithPork = $ArrayMoreMeals[$PreviousYearMonth]['WithPork'];
                             $MoreMealsWithoutPork = $ArrayMoreMeals[$PreviousYearMonth]['WithoutPork'];
                         }

                         // Enfants
                         $ArrayResult['Total cantines maternelles avec porc'][] = str_replace(array('.'), array(','), $TotalWithPork[1]);
                         $ArrayResult['Total cantines maternelles sans porc'][] = str_replace(array('.'), array(','), $TotalWithoutPork[1]);
                         $ArrayResult['Total cantines primaires avec porc'][] = str_replace(array('.'), array(','), $TotalWithPork[2]);
                         $ArrayResult['Total cantines primaires sans porc'][] = str_replace(array('.'), array(','), $TotalWithoutPork[1]);

                         // Ajudes
                         $ArrayResult['Total ajudes avec porc'][] = str_replace(array('.'), array(','), $MoreMealsWithPork);
                         $ArrayResult['Total ajudes sans porc'][] = str_replace(array('.'), array(','), $MoreMealsWithoutPork);

                         // Total
                         $ArrayResult['Total'][] = str_replace(array('.'), array(','), array_sum($TotalWithPork)
                                                               + array_sum($TotalWithoutPork) + $MoreMealsWithPork
                                                               + $MoreMealsWithoutPork);
                     }

                     $ArrayResult['Mois'][] = $Record['YEARMONTH'];
                     $PreviousYearMonth = $Record['YEARMONTH'];
                     $TotalWithPork = array(1 => 0, 2 => 0);
                     $TotalWithoutPork = array(1 => 0, 2 => 0);
                 }

                 if ($Record['PORK'] == 1)
                 {
                     // Avec porc
                     $TotalWithoutPork[$Record['CHILDGRADE']] = $Record['TOTAL'];
                 }
                 else
                 {
                     // Sans porc
                     $TotalWithPork[$Record['CHILDGRADE']] = $Record['TOTAL'];
                 }
             }

             // Repas en plus
             $MoreMealsWithPork = 0;
             $MoreMealsWithoutPork = 0;
             if (isset($ArrayMoreMeals[$PreviousYearMonth]))
             {
                 $MoreMealsWithPork = $ArrayMoreMeals[$PreviousYearMonth]['WithPork'];
                 $MoreMealsWithoutPork = $ArrayMoreMeals[$PreviousYearMonth]['WithoutPork'];
             }

             // Enfants
             $ArrayResult['Total cantines maternelles avec porc'][] = str_replace(array('.'), array(','), $TotalWithPork[1]);
             $ArrayResult['Total cantines maternelles sans porc'][] = str_replace(array('.'), array(','), $TotalWithoutPork[1]);
             $ArrayResult['Total cantines primaires avec porc'][] = str_replace(array('.'), array(','), $TotalWithPork[2]);
             $ArrayResult['Total cantines primaires sans porc'][] = str_replace(array('.'), array(','), $TotalWithoutPork[1]);

             // Ajudes
             $ArrayResult['Total ajudes avec porc'][] = str_replace(array('.'), array(','), $MoreMealsWithPork);
             $ArrayResult['Total ajudes sans porc'][] = str_replace(array('.'), array(','), $MoreMealsWithoutPork);

             // Total
             $ArrayResult['Total'][] = str_replace(array('.'), array(','), array_sum($TotalWithPork) + array_sum($TotalWithoutPork)
                                                   + $MoreMealsWithPork + $MoreMealsWithoutPork);

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


 function ExtractPaymentsList($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We can launch the SQL request
     $StartDate = formatedDate2EngDate($ArrayParams['BillForDate'][0]);
     $EndDate = formatedDate2EngDate($ArrayParams['BillForDate'][1]);

     $DbResult = $DbConnection->query("SELECT DATE_FORMAT(b.BillForDate, '%Y-%m') AS YEARMONTH, f.FamilyLastname, p.PaymentDate,
                                       p.PaymentMode, p.PaymentCheckNb, p.PaymentAmount, bk.BankName
                                       FROM Families f, Bills b LEFT JOIN PaymentsBills pb ON (b.BillID = pb.BillID) LEFT JOIN
                                       Payments p ON (pb.PaymentID = p.PaymentID) LEFT JOIN Banks bk ON (p.BankID = bk.BankID)
                                       WHERE b.BillForDate BETWEEN '$StartDate' AND '$EndDate' AND f.FamilyID = b.FamilyID
                                       ORDER BY YEARMONTH, f.FamilyLastname");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Mois" => array(),
                                  "Nom" => array(),
                                  "Date du paiement" => array(),
                                  "Montant" => array(),
                                  "Mode de paiement" => array(),
                                  "Banque" => array(),
                                  "Numéro" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayResult['Mois'][] = $Record['YEARMONTH'];
                 $ArrayResult['Nom'][] = $Record['FamilyLastname'];

                 if (empty($Record['PaymentDate']))
                 {
                     // No payment
                     $ArrayResult['Date du paiement'][] = "";
                     $ArrayResult['Montant'][] = "";
                     $ArrayResult['Mode de paiement'][] = "";
                     $ArrayResult['Banque'][] = "";
                     $ArrayResult['Numéro'][] = "";
                 }
                 else
                 {
                     $ArrayResult['Date du paiement'][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($Record['PaymentDate']));
                     $ArrayResult['Montant'][] = str_replace(array('.'), array(','), $Record['PaymentAmount']);
                     $ArrayResult['Mode de paiement'][] = $GLOBALS['CONF_PAYMENTS_MODES'][$Record['PaymentMode']];

                     switch($Record['PaymentMode'])
                     {
                         case 1:
                         case 2:
                             // Check
                             $ArrayResult['Banque'][] = $Record['BankName'];
                             $ArrayResult['Numéro'][] = $Record['PaymentCheckNb'];
                             break;

                         default:
                             // Money and others
                             $ArrayResult['Banque'][] = "";
                             $ArrayResult['Numéro'][] = "";
                             break;
                     }
                 }
             }

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


 function ExtractFamiliesWithPaymentsPbs($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We can launch the SQL request
     $AlertBalance = $ArrayParams['AlertBalance'];

     $DbResult = $DbConnection->query("SELECT f.FamilyID, f.FamilyLastname, f.FamilyBalance, COUNT(b.BillID) AS NBBILLS
                                       FROM Families f LEFT JOIN Bills b ON (f.FamilyID = b.FamilyID)
                                       WHERE b.BillPaid = 0 AND f.FamilyBalance <= $AlertBalance
                                       GROUP BY f.FamilyID ORDER BY f.FamilyBalance ASC, NBBILLS DESC");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Famille" => array(),
                                  "Nb factures impayées" => array(),
                                  "Montant total" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayResult['Famille'][] = $Record['FamilyLastname'];
                 $ArrayResult['Nb factures impayées'][] = $Record['NBBILLS'];
                 $ArrayResult['Montant total'][] = str_replace(array('.'), array(','), $Record['FamilyBalance']);
             }

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


 function ExtractSuspensionsChildren($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We can launch the SQL request
     $DbResult = $DbConnection->query("SELECT f.FamilyLastname, c.ChildFirstname, c.ChildGrade, c.ChildClass, c.ChildDesactivationDate,
                                      s.SuspensionStartDate, s.SuspensionEndDate, s.SuspensionReason
                                      FROM Families f, Children c, Suspensions s
                                      WHERE f.FamilyID = c.FamilyID AND s.ChildID = c.ChildID
                                      ORDER BY f.FamilyLastname, c.ChildFirstname");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Nom" => array(),
                                  "Prénom" => array(),
                                  "Niveau" => array(),
                                  "Classe" => array(),
                                  "Date début suspension" => array(),
                                  "Date fin suspension" => array(),
                                  "Raison" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 // Get the right school year to use
                 if (empty($Record['ChildDesactivationDate']))
                 {
                     // We use the current school year
                     $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));
                 }
                 else
                 {
                     // We use the last class room of the child, so we use the desactivation date
                     $CurrentSchoolYear = getSchoolYear($Record['ChildDesactivationDate']);
                 }

                 $ArrayResult['Nom'][] = $Record['FamilyLastname'];
                 $ArrayResult['Prénom'][] = $Record['ChildFirstname'];
                 $ArrayResult['Niveau'][] = $GLOBALS['CONF_GRADES'][$Record['ChildGrade']];
                 $ArrayResult['Classe'][] = $GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$Record['ChildClass']];
                 $ArrayResult['Date début suspension'][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                strtotime($Record['SuspensionStartDate']));

                 // The end date can be empty
                 if (empty($Record['SuspensionEndDate']))
                 {
                     $ArrayResult['Date fin suspension'][] = "";
                 }
                 else
                 {
                     $ArrayResult['Date fin suspension'][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                  strtotime($Record['SuspensionEndDate']));
                 }

                 $ArrayResult['Raison'][] = $Record['SuspensionReason'];
             }

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


function ExtractSuspensionsChildrenGoingToSchool($DbConnection, $ArrayParams, $OrderBy = "")
 {
     // We can launch the SQL request
     $DbResult = $DbConnection->query("SELECT f.FamilyLastname, c.ChildID, c.ChildFirstname, c.ChildGrade, c.ChildClass,
                                      c.ChildDesactivationDate, s.SuspensionStartDate, s.SuspensionEndDate, s.SuspensionReason
                                      FROM Families f, Children c, Suspensions s
                                      WHERE f.FamilyID = c.FamilyID AND s.ChildID = c.ChildID
                                      ORDER BY f.FamilyLastname, c.ChildFirstname");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Nom" => array(),
                                  "Prénom" => array(),
                                  "Niveau" => array(),
                                  "Classe" => array(),
                                  "Date début suspension" => array(),
                                  "Date fin suspension" => array(),
                                  "Raison" => array(),
                                  "Mois cantine" => array(),
                                  "Mois garderie" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 // Get the right school year to use
                 if (empty($Record['ChildDesactivationDate']))
                 {
                     // We use the current school year
                     $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));
                 }
                 else
                 {
                     // We use the last class room of the child, so we use the desactivation date
                     $CurrentSchoolYear = getSchoolYear($Record['ChildDesactivationDate']);
                 }

                 $ChildID = $Record['ChildID'];
                 $StartDate = $Record['SuspensionStartDate'];
                 if (empty($Record['SuspensionEndDate']))
                 {
                     $EndtDate = date("Y-m-d");
                 }
                 else
                 {
                     $EndtDate = $Record['SuspensionReason'];
                 }

                 // Get children with a suspension period but eat to the canteen
                 $DbSubResult = $DbConnection->query("SELECT DATE_FORMAT(cr.CanteenRegistrationForDate, '%Y-%m') AS YEARMONTH
                                                      FROM CanteenRegistrations cr WHERE cr.ChildID = $ChildID AND
                                                      cr.CanteenRegistrationForDate BETWEEN '$StartDate' AND '$EndtDate'
                                                      GROUP BY YEARMONTH ORDER BY cr.CanteenRegistrationForDate");

                 $CanteenMonths = array();
                 if (!DB::isError($DbSubResult))
                 {
                     if ($DbSubResult->numRows() > 0)
                     {
                          while($SubRecord = $DbSubResult->fetchRow(DB_FETCHMODE_ASSOC))
                          {
                              $CanteenMonths[] = $SubRecord['YEARMONTH'];
                          }
                     }
                 }

                 // Get children with a suspension period but going to the nursary
                 $DbSubResult = $DbConnection->query("SELECT DATE_FORMAT(nr.NurseryRegistrationForDate, '%Y-%m') AS YEARMONTH
                                                      FROM NurseryRegistrations nr WHERE nr.ChildID = $ChildID AND
                                                      nr.NurseryRegistrationForDate BETWEEN '$StartDate' AND '$EndtDate'
                                                      GROUP BY YEARMONTH ORDER BY nr.NurseryRegistrationForDate");

                 $NurseryMonths = array();
                 if (!DB::isError($DbSubResult))
                 {
                     if ($DbSubResult->numRows() > 0)
                     {
                          while($SubRecord = $DbSubResult->fetchRow(DB_FETCHMODE_ASSOC))
                          {
                              $NurseryMonths[] = $SubRecord['YEARMONTH'];
                          }
                     }
                 }

                 if ((!empty($CanteenMonths)) && (!empty($NurseryMonths)))
                 {
                     $ArrayResult['Nom'][] = $Record['FamilyLastname'];
                     $ArrayResult['Prénom'][] = $Record['ChildFirstname'];
                     $ArrayResult['Niveau'][] = $GLOBALS['CONF_GRADES'][$Record['ChildGrade']];
                     $ArrayResult['Classe'][] = $GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$Record['ChildClass']];
                     $ArrayResult['Date début suspension'][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                    strtotime($Record['SuspensionStartDate']));

                     // The end date can be empty
                     if (empty($Record['SuspensionEndDate']))
                     {
                         $ArrayResult['Date fin suspension'][] = "";
                     }
                     else
                     {
                         $ArrayResult['Date fin suspension'][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                      strtotime($Record['SuspensionEndDate']));
                     }

                     $ArrayResult['Raison'][] = $Record['SuspensionReason'];

                     if (empty($CanteenMonths))
                     {
                         $ArrayResult["Mois cantine"][] = "";
                     }
                     else
                     {
                         $ArrayResult["Mois cantine"][] = implode(", ", $CanteenMonths);
                     }

                     if (empty($NurseryMonths))
                     {
                         $ArrayResult["Mois garderie"][] = "";
                     }
                     else
                     {
                         $ArrayResult["Mois garderie"][] = implode(", ", $NurseryMonths);
                     }
                 }
             }

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }


 function ExtractChildrenOfTowns($DbConnection, $ArrayParams, $OrderBy = "")
 {
     $Conditions = '';
     if (isset($ArrayParams['TownID']))
     {
         $Conditions = " AND t.TownID IN ".constructSQLINString($ArrayParams['TownID']);
     }
     else if (isset($ArrayParams['NotTownID']))
     {
         $Conditions = " AND t.TownID NOT IN ".constructSQLINString($ArrayParams['NotTownID']);
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("SELECT f.FamilyLastname, c.ChildID, c.ChildFirstname, c.ChildGrade, c.ChildClass,
                                      c.ChildWithoutPork, c.ChildSchoolDate, c.ChildDesactivationDate , t.TownCode, t.TownName
                                      FROM Families f, Children c, Towns t
                                      WHERE f.FamilyID = c.FamilyID AND f.TownID = t.TownID AND f.FamilyDesactivationDate IS NULL
                                      AND c.ChildDesactivationDate IS NULL $Conditions GROUP BY ChildID
                                      ORDER BY f.FamilyLastname, c.ChildFirstname");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Creation of the result array
             $ArrayResult = array(
                                  "Nom" => array(),
                                  "Prénom" => array(),
                                  "Niveau" => array(),
                                  "Classe" => array(),
                                  "Repas sans porc" => array(),
                                  "Date d'inscription" => array(),
                                  "Code postal" => array(),
                                  "Commune" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 // Get the right school year to use
                 if (empty($Record['ChildDesactivationDate']))
                 {
                     // We use the current school year
                     $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));
                 }
                 else
                 {
                     // We use the last class room of the child, so we use the desactivation date
                     $CurrentSchoolYear = getSchoolYear($Record['ChildDesactivationDate']);
                 }

                 $ArrayResult['Nom'][] = $Record['FamilyLastname'];
                 $ArrayResult['Prénom'][] = $Record['ChildFirstname'];
                 $ArrayResult['Niveau'][] = $GLOBALS['CONF_GRADES'][$Record['ChildGrade']];
                 $ArrayResult['Classe'][] = $GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$Record['ChildClass']];

                 if ($Record['ChildWithoutPork'] == 1)
                 {
                     $ArrayResult['Repas sans porc'][] = $GLOBALS['LANG_YES'];
                 }
                 else
                 {
                     $ArrayResult['Repas sans porc'][] = $GLOBALS['LANG_NO'];
                 }

                 $ArrayResult["Date d'inscription"][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                             strtotime($Record['ChildSchoolDate']));
                 $ArrayResult['Code postal'][] = $Record['TownCode'];
                 $ArrayResult['Commune'][] = $Record['TownName'];
             }

             // Export in csv format the data
             $TabData = array();
             $ArrayResultKeys = array_keys($ArrayResult);

             $ArrayResultKeysSize = count($ArrayResultKeys);
             for($i = 0 ; $i < $ArrayResultKeysSize ; $i++)
             {
                 $TabData[$i] = $ArrayResult[$ArrayResultKeys[$i]];
             }

             $ResultFilename = $ArrayParams["ResultFilename"];
             exportTableToTxtFile($ArrayResultKeys, $TabData, ";", $GLOBALS["CONF_EXPORT_DIRECTORY_HDD"].$ResultFilename);
             $Link = generateBR(2)."<p class=\"InfoMsg\">"
                    .generateStyledLinkText($GLOBALS["LANG_DOWNLOAD"], $GLOBALS["CONF_EXPORT_DIRECTORY"].$ResultFilename, "",
                                            $GLOBALS["LANG_DOWNLOAD_EXPORT_TIP"], "_blank")."</p>";

             return array(array(), 0, $Link);
         }
     }
 }
?>