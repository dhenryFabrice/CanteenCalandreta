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
 * Common module : library of database functions used for the Families table
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2012-01-16
 */


/**
 * Give the FamilyID of a family thanks to the SupportMemberID
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2013-04-16 : patch a bug (SupportMemberLastname instead of SupportMemberID)
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *
 * @since 2012-07-12
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $SupportMemberID      Integer      ID of the support member used to find the familyID [1..n]
 *
 * @return Integer              ID of the family, 0 otherwise
 */
 function getFamilyIDThanksToSupportMemberID($DbConnection, $SupportMemberID)
 {
     if (!empty($SupportMemberID))
     {
         $DbResult = $DbConnection->query("SELECT f.FamilyID FROM Families f, SupportMembers sm
                                          WHERE sm.SupportMemberID = $SupportMemberID AND f.FamilyID = sm.FamilyID
                                          GROUP BY FamilyID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() != 0)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record["FamilyID"];
             }
         }
     }

     // Error
     return 0;
 }


/**
 * Give the lastname of a family thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-16
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $FamilyID             Integer      ID of the family searched [1..n]
 *
 * @return String               Lastname of the family, empty string otherwise
 */
 function getFamilyLastname($DbConnection, $FamilyID)
 {
     if (!empty($FamilyID))
     {
         $DbResult = $DbConnection->query("SELECT FamilyLastname FROM Families WHERE FamilyID = $FamilyID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() != 0)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record["FamilyLastname"];
             }
         }
     }

     // ERROR
     return "";
 }


/**
 * Check if a family exists in the Families table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-16
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $FamilyID             Integer      ID of the family searched [1..n]
 *
 * @return Boolean              TRUE if the family exists, FALSE otherwise
 */
 function isExistingFamily($DbConnection, $FamilyID)
 {
     $DbResult = $DbConnection->query("SELECT FamilyID FROM Families WHERE FamilyID = $FamilyID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The family exists
             return TRUE;
         }
     }

     // The family doesn't exist
     return FALSE;
 }


/**
 * Add a family in the Families table
 *
 * @author Christophe Javouhey
 * @version 1.4
 *     - 2012-07-12 : taken into account the FamilySpecialAnnualContribution field and FamilyNbMembers can be = 0
 *     - 2013-10-09 : taken into account the FamilyMonthlyContributionMode field and the HistoFamilies table
 *     - 2014-08-06 : patch a bug about $FamilyID not declared ($id instead)
 *     - 2019-07-16 : taken into account the FamilyAnnualContributionBalance field
 *
 * @since 2012-01-17
 *
 * @param $DbConnection                       DB object    Object of the opened database connection
 * @param $FamilyDate                         Date         Creation date of the family (yyyy-mm-dd)
 * @param $FamilyLastname                     String       Lastname of the family
 * @param $TownID                             Integer      ID of the town where lives the family [1..n]
 * @param $FamilyMainEmail                    String       Main e-mail to contact the family
 * @param $FamilySecondEmail                  String       Second e-mail of the family
 * @param $FamilyNbMembers                    Integer      Number of "normal" members [1..n]
 * @param $FamilyNbPoweredMembers             Integer      Number of members with power [0..n]
 * @param $FamilyBalance                      Float        Balance of the family
 * @param $FamilyComment                      String       Comment about the family
 * @param $FamilyDesactivationDate            Date         Desactivation date of the family (to "close" the family)
 * @param $FamilySpecialAnnualContribution    Integer      0 => no special annual contribution,
 *                                                         1 => special annual contribution [0..1]
 * @param $FamilyMonthlyContributionMode      Integer      0 => default mode of monthy contribution
 *                                                         1 => benefactor mode of monthy contribution [0..1]
 * @param $FamilyAnnualContributionBalance    Float        Balance about annual contributions of the family
 *
 * @return Integer                            The primary key of the family [1..n], 0 otherwise
 */
 function dbAddFamily($DbConnection, $FamilyDate, $FamilyLastname, $TownID, $FamilyMainEmail, $FamilySecondEmail = '', $FamilyNbMembers = 1, $FamilyNbPoweredMembers = 0, $FamilyBalance = 0.00, $FamilyComment = '', $FamilyDesactivationDate = NULL, $FamilySpecialAnnualContribution = 0, $FamilyMonthlyContributionMode = 0, $FamilyAnnualContributionBalance = 0.00)
 {
     if ((!empty($FamilyLastname)) && ($TownID > 0) && (!empty($FamilyMainEmail)) && ($FamilyNbMembers >= 0)
         && ($FamilyNbPoweredMembers >= 0) && ($FamilySpecialAnnualContribution >= 0) && ($FamilyMonthlyContributionMode >= 0))
     {
         // Check if the family is a new family
         $DbResult = $DbConnection->query("SELECT FamilyID FROM Families WHERE FamilyLastname = \"$FamilyLastname\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the FamilyDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $FamilyDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $FamilyDate = ", FamilyDate = \"$FamilyDate\"";
                 }

                 // Check if the FamilyDesactivationDate is valide
                 if (!empty($FamilyDesactivationDate))
                 {
                     if (preg_match("[\d\d\d\d-\d\d-\d\d]", $FamilyDesactivationDate) == 0)
                     {
                         return 0;
                     }
                     else
                     {
                         $FamilyDesactivationDate = ", FamilyDesactivationDate = \"$FamilyDesactivationDate\"";
                     }
                 }

                 if (empty($FamilySecondEmail))
                 {
                     $FamilySecondEmail = "";
                 }
                 else
                 {
                     $FamilySecondEmail = ", FamilySecondEmail = \"$FamilySecondEmail\"";
                 }

                 if (empty($FamilyComment))
                 {
                     $FamilyComment = "";
                 }
                 else
                 {
                     $FamilyComment = ", FamilyComment = \"$FamilyComment\"";
                 }

                 // It's a new family
                 $id = getNewPrimaryKey($DbConnection, "Families", "FamilyID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Families SET FamilyID = $id, FamilyLastname = \"$FamilyLastname\",
                                                      TownID = $TownID, FamilyMainEmail = \"$FamilyMainEmail\",
                                                      FamilyNbMembers = $FamilyNbMembers, FamilyNbPoweredMembers = $FamilyNbPoweredMembers,
                                                      FamilySpecialAnnualContribution = $FamilySpecialAnnualContribution,
                                                      FamilyMonthlyContributionMode = $FamilyMonthlyContributionMode,
                                                      FamilyAnnualContributionBalance = $FamilyAnnualContributionBalance, FamilyBalance = $FamilyBalance
                                                      $FamilySecondEmail $FamilyDate $FamilyDesactivationDate");
                     if (!DB::isError($DbResult))
                     {
                         // Create an entry in the history of the family
                         $idHisto = dbAddHistoFamily($DbConnection, date("Y-m-d H:i:s"), $id, $TownID, $FamilyBalance,
                                                     $FamilyMonthlyContributionMode);

                         return $id;
                     }
                 }
             }
             else
             {
                 // The family already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['FamilyID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing family in the families table
 *
 * @author Christophe Javouhey
 * @version 1.5
 *     - 2012-07-12 : taken into account the FamilySpecialAnnualContribution field and FamilyNbMembers can be = 0
 *     - 2013-09-18 : patch for the case $FamilyLastname = NULL, we must get the FamilyLastname value,
 *                    taken into account the FamilyMonthlyContributionMode field and the HistoFamilies table
 *     - 2017-09-21 : taken into account FamilyMainEmailContactAllowed and FamilySecondEmailContactAllowed fields
 *     - 2019-01-21 : taken into account FamilyMainEmailInCommittee and FamilySecondEmailInCommittee fields
 *     - 2019-07-16 : taken into acount the FamilyAnnualContributionBalance field
 *
 * @since 2012-01-23
 *
 * @param $DbConnection                       DB object    Object of the opened database connection
 * @param $FamilyID                           Integer      ID of the family to update [1..n]
 * @param $FamilyDate                         Date         Creation date of the family (yyyy-mm-dd)
 * @param $FamilyLastname                     String       Lastname of the family
 * @param $TownID                             Integer      ID of the town where lives the family [1..n]
 * @param $FamilyMainEmail                    String       Main e-mail to contact the family
 * @param $FamilySecondEmail                  String       Second e-mail of the family
 * @param $FamilyNbMembers                    Integer      Number of "normal" members [1..n]
 * @param $FamilyNbPoweredMembers             Integer      Number of members with power [0..n]
 * @param $FamilyBalance                      Float        Balance of the family
 * @param $FamilyComment                      String       Comment about the family
 * @param $FamilyDesactivationDate            Date         Desactivation date of the family (to "close" the family)
 * @param $FamilySpecialAnnualContribution    Integer      0 => no special annual contribution,
 *                                                         1 => special annual contribution [0..1]
 * @param $FamilyMonthlyContributionMode      Integer      0 => default mode of monthy contribution
 *                                                         1 => benefactor mode of monthy contribution [0..1]
 * @param $FamilyMainEmailContactAllowed      Integer      0 => not allowed, 1 => allowed [0..1]
 * @param $FamilySecondEmailContactAllowed    Integer      0 => not allowed, 1 => allowed [0..1]
 * @param $FamilyMainEmailInCommittee         Integer      0 => not in committee, 1 => in committee [0..1]
 * @param $FamilySecondEmailInCommittee       Integer      0 => not in committee, 1 => in committee [0..1]
 * @param $FamilyAnnualContributionBalance    Float        Balance about annual contributions of the family
 *
 *
 * @return Integer                            The primary key of the family [1..n], 0 otherwise
 */
 function dbUpdateFamily($DbConnection, $FamilyID, $FamilyDate, $FamilyLastname, $TownID, $FamilyMainEmail, $FamilySecondEmail = NULL, $FamilyNbMembers = NULL, $FamilyNbPoweredMembers = NULL, $FamilyBalance = NULL, $FamilyComment = NULL, $FamilyDesactivationDate = NULL, $FamilySpecialAnnualContribution = NULL, $FamilyMonthlyContributionMode = NULL, $FamilyMainEmailContactAllowed = NULL, $FamilySecondEmailContactAllowed = NULL, $FamilyMainEmailInCommittee = NULL, $FamilySecondEmailInCommittee = NULL, $FamilyAnnualContributionBalance = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($FamilyID < 1) || (!isInteger($FamilyID)))
     {
         // ERROR
         return 0;
     }

     // Check if the FamilyDate is valide
     if (!is_null($FamilyDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $FamilyDate) == 0)
         {
             return 0;
         }
         else
         {
             // The FamilyDate field will be updated
             $ArrayParamsUpdate[] = "FamilyDate = \"$FamilyDate\"";
         }
     }

     if (!is_null($TownID))
     {
         if (($TownID < 1) || (!isInteger($TownID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "TownID = $TownID";
         }
     }

     if (!is_Null($FamilyLastname))
     {
         if (empty($FamilyLastname))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilyLastname field will be updated
             $ArrayParamsUpdate[] = "FamilyLastname = \"$FamilyLastname\"";
         }
     }
     else
     {
         // Get the family lastname
         $FamilyLastname = getTableFieldValue($DbConnection, "Families", $FamilyID, "FamilyLastname");
     }

     if (!is_Null($FamilyMainEmail))
     {
         if (empty($FamilyMainEmail))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilyMainEmail field will be updated
             $ArrayParamsUpdate[] = "FamilyMainEmail = \"$FamilyMainEmail\"";
         }
     }

     if (!is_Null($FamilySecondEmail))
     {
         // The FamilySecondEmail field will be updated
         $ArrayParamsUpdate[] = "FamilySecondEmail = \"$FamilySecondEmail\"";
     }

     if (!is_Null($FamilyNbMembers))
     {
         if (($FamilyNbMembers < 0) || (!isInteger($FamilyNbMembers)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilyNbMembers field will be updated
             $ArrayParamsUpdate[] = "FamilyNbMembers = $FamilyNbMembers";
         }
     }

     if (!is_Null($FamilyNbPoweredMembers))
     {
         if (($FamilyNbPoweredMembers < 0) || (!isInteger($FamilyNbPoweredMembers)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilyNbPoweredMembers field will be updated
             $ArrayParamsUpdate[] = "FamilyNbPoweredMembers = $FamilyNbPoweredMembers";
         }
     }

     if (!is_Null($FamilyAnnualContributionBalance))
     {
         if (!isFloat($FamilyAnnualContributionBalance))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilyAnnualContributionBalance field will be updated
             $ArrayParamsUpdate[] = "FamilyAnnualContributionBalance = $FamilyAnnualContributionBalance";
         }
     }

     if (!is_Null($FamilyBalance))
     {
         if (!isFloat($FamilyBalance))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilyBalance field will be updated
             $ArrayParamsUpdate[] = "FamilyBalance = $FamilyBalance";
         }
     }

     if (!is_Null($FamilyComment))
     {
         // The FamilyComment field will be updated
         $ArrayParamsUpdate[] = "FamilyComment = \"$FamilyComment\"";
     }

     if (!is_null($FamilyDesactivationDate))
     {
         if (empty($FamilyDesactivationDate))
         {
             // The FamilyDesactivationDate field will be updated
             $ArrayParamsUpdate[] = "FamilyDesactivationDate = NULL";
         }
         else
         {
             if (preg_match("[\d\d\d\d-\d\d-\d\d]", $FamilyDesactivationDate) == 0)
             {
                 return 0;
             }
             else
             {
                 // The FamilyDesactivationDate field will be updated
                 $ArrayParamsUpdate[] = "FamilyDesactivationDate = \"$FamilyDesactivationDate\"";
             }
         }
     }

     if (!is_Null($FamilySpecialAnnualContribution))
     {
         if (($FamilySpecialAnnualContribution < 0) || (!isInteger($FamilySpecialAnnualContribution)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilySpecialAnnualContribution field will be updated
             $ArrayParamsUpdate[] = "FamilySpecialAnnualContribution = $FamilySpecialAnnualContribution";
         }
     }

     if (!is_Null($FamilyMonthlyContributionMode))
     {
         if (($FamilyMonthlyContributionMode < 0) || (!isInteger($FamilyMonthlyContributionMode)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilyMonthlyContributionMode field will be updated
             $ArrayParamsUpdate[] = "FamilyMonthlyContributionMode = $FamilyMonthlyContributionMode";
         }
     }

     if (!is_Null($FamilyMainEmailContactAllowed))
     {
         if (($FamilyMainEmailContactAllowed < 0) || (!isInteger($FamilyMainEmailContactAllowed)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilyMainEmailContactAllowed field will be updated
             $ArrayParamsUpdate[] = "FamilyMainEmailContactAllowed = $FamilyMainEmailContactAllowed";
         }
     }

     if (!is_Null($FamilySecondEmailContactAllowed))
     {
         if (($FamilySecondEmailContactAllowed < 0) || (!isInteger($FamilySecondEmailContactAllowed)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilySecondEmailContactAllowed field will be updated
             $ArrayParamsUpdate[] = "FamilySecondEmailContactAllowed = $FamilySecondEmailContactAllowed";
         }
     }

     if (!is_Null($FamilyMainEmailInCommittee))
     {
         if (($FamilyMainEmailInCommittee < 0) || (!isInteger($FamilyMainEmailInCommittee)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilyMainEmailInCommittee field will be updated
             $ArrayParamsUpdate[] = "FamilyMainEmailInCommittee = $FamilyMainEmailInCommittee";
         }
     }

     if (!is_Null($FamilySecondEmailInCommittee))
     {
         if (($FamilySecondEmailInCommittee < 0) || (!isInteger($FamilySecondEmailInCommittee)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The FamilySecondEmailInCommittee field will be updated
             $ArrayParamsUpdate[] = "FamilySecondEmailInCommittee = $FamilySecondEmailInCommittee";
         }
     }

     // Here, the parameters are correct, we check if the family exists
     if (isExistingFamily($DbConnection, $FamilyID))
     {
         // We check if the family name is unique
         $DbResult = $DbConnection->query("SELECT FamilyID FROM Families WHERE FamilyLastname = \"$FamilyLastname\"
                                          AND FamilyID <> $FamilyID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The family entry exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Families SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE FamilyID = $FamilyID");
                     if (!DB::isError($DbResult))
                     {
                         // Family updated
                         // Create an entry in the history of the family
                         $idHisto = dbAddHistoFamily($DbConnection, date("Y-m-d H:i:s"), $FamilyID, $TownID, $FamilyBalance,
                                                     $FamilyMonthlyContributionMode);

                         return $FamilyID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $FamilyID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update the balance of a family, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-23
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $FamilyID             Integer      ID of the family to update the balance [1..n]
 * @param $Value                Float        Value to add or remove to the current balance of the family
 *
 * @return Float                The new balance of the family, FALSE otherwise
 */
 function updateFamilyBalance($DbConnection, $FamilyID, $Value)
 {
     $DbResult = $DbConnection->query("SELECT FamilyBalance FROM Families WHERE FamilyID = $FamilyID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Get the current balance
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);

             // Compute the new balance
             $fNewBalance = round((float)$Record['FamilyBalance'], 2) + round((float)$Value, 2);

             // Set the new balance
             $DbResult = $DbConnection->query("UPDATE Families SET FamilyBalance = $fNewBalance WHERE FamilyID = $FamilyID");
             if (!DB::isError($DbResult))
             {
                 // Create an entry in the history of the family
                 $idHisto = dbAddHistoFamily($DbConnection, date("Y-m-d H:i:s"), $FamilyID, NULL, $fNewBalance, NULL);

                 return $fNewBalance;
             }
         }
     }

     // Error
     return FALSE;
 }


/**
 * Update the annual contribution balance of a family, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-07-16
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $FamilyID             Integer      ID of the family to update the annual contribution balance [1..n]
 * @param $Value                Float        Value to add or remove to the current annual contribution balance
 *                                           of the family
 *
 * @return Float                The new annual contribuation balance of the family, FALSE otherwise
 */
 function updateFamilyAnnualContributionBalance($DbConnection, $FamilyID, $Value)
 {
     $DbResult = $DbConnection->query("SELECT FamilyAnnualContributionBalance FROM Families WHERE FamilyID = $FamilyID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Get the current annual contribution balance
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);

             // Compute the new annual contribution balance
             $fNewBalance = round((float)$Record['FamilyAnnualContributionBalance'], 2) + round((float)$Value, 2);

             // Set the new annual contribution balance
             $DbResult = $DbConnection->query("UPDATE Families SET FamilyAnnualContributionBalance = $fNewBalance WHERE FamilyID = $FamilyID");
             if (!DB::isError($DbResult))
             {
                 return $fNewBalance;
             }
         }
     }

     // Error
     return FALSE;
 }


/**
 * Check if a family is closed (desactivated), thanks to its ID
 *
 * @author Christophe Javouhey
 * @since 2012-01-23
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $FamilyID                  Integer      ID of the family to check [1..n]
 *
 * @return Boolean                   TRUE if the family is closed, FALSE otherwise
 */
 function isFamilyClosed($DbConnection, $FamilyID)
 {
     if (!empty($FamilyID))
     {
         // we used only the activation to check if the family is closed
         $DbResult = $DbConnection->query("SELECT FamilyID, FamilyDesactivationDate FROM Families WHERE FamilyID = $FamilyID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 if (!empty($Record['FamilyDesactivationDate']))
                 {
                     // The family is closed (desactivated)
                     return TRUE;
                 }
                 else
                 {
                     // The family is opened (activated)
                     return FALSE;
                 }
             }
         }
     }

     // Error
     return TRUE;
 }


/**
 * Add a new entry in the family's history if not exists (HistoFamilies table)
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-10-10
 *
 * @param $DbConnection                          DB object    Object of the opened database connection
 * @param $HistoDate                             Datetime     Creation date of the entry in the history (yyyy-mm-dd hh:mm:ss)
 * @param $FamilyID                              Integer      ID of the concerned family [1..n]
 * @param $TownID                                Integer      ID of the town where lives the family [1..n]
 * @param $HistoFamilyBalance                    Float        Balance of the family
 * @param $HistoFamilyMonthlyContributionMode    Integer      0 => default mode of monthy contribution
 *                                                            1 => benefactor mode of monthy contribution [0..1]
 *
 * @return Integer                               The primary key of the family history entry [1..n], 0 otherwise
 */
 function dbAddHistoFamily($DbConnection, $HistoDate, $FamilyID, $TownID, $HistoFamilyBalance = 0.00, $HistoFamilyMonthlyContributionMode = 0)
 {
     if ($FamilyID > 0)
     {
         if ((is_null($TownID)) || (is_null($HistoFamilyBalance)) || (is_null($HistoFamilyMonthlyContributionMode)))
         {
             // Get data about the family
             $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $FamilyID);
             if (!empty($RecordFamily))
             {
                 $TownID = $RecordFamily['TownID'];
                 $HistoFamilyBalance = $RecordFamily['FamilyBalance'];
                 $HistoFamilyMonthlyContributionMode = $RecordFamily['FamilyMonthlyContributionMode'];
             }
         }
     }
     else
     {
         // Error
         return 0;
     }

     if (($TownID > 0) && ($HistoFamilyMonthlyContributionMode >= 0))
     {
         // Check if the HistoDate is valide
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $HistoDate) == 0)
         {
             return 0;
         }
         else
         {
             $HistoDate = ", HistoDate = \"$HistoDate\"";
         }

         // We check if the previous entry in the history of tthis family is the same as the new entry
         $DbResult = $DbConnection->query("SELECT h.HistoFamilyID FROM HistoFamilies h, (SELECT MAX(th.HistoFamilyID) AS MaxID
                                          FROM HistoFamilies th WHERE th.FamilyID = $FamilyID GROUP BY th.FamilyID) AS Tmp
                                          WHERE h.HistoFamilyID = Tmp.MaxID AND h.FamilyID = $FamilyID AND h.TownID = $TownID
                                          AND h.HistoFamilyMonthlyContributionMode = $HistoFamilyMonthlyContributionMode
                                          AND h.HistoFamilyBalance = $HistoFamilyBalance");

         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // It's a new family history entry
                 $id = getNewPrimaryKey($DbConnection, "HistoFamilies", "HistoFamilyID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO HistoFamilies SET HistoFamilyID = $id, FamilyID = $FamilyID,
                                                      TownID = $TownID, HistoFamilyBalance = $HistoFamilyBalance,
                                                      HistoFamilyMonthlyContributionMode = $HistoFamilyMonthlyContributionMode
                                                      $HistoDate");
                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The family history entry already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['HistoFamilyID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Get the last entry in the history of a family for a given date
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-10-10
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $HistoDate                Date                   Date for which we want the history of the family (yyyy-mm-dd)
 * @param $FamilyID                 Integer                ID of the concerned family [1..n]
 *
 * @return Mixed array              One record of the table with the given fields, empty array otherwise
 */
 function getHistoFamilyForDate($DbConnection, $FamilyID, $HistoDate)
 {
     if ($FamilyID > 0)
     {
         // We search the last entry in the history of the family before the given date
         $DbResult = $DbConnection->query("SELECT HistoFamilyID, HistoDate, FamilyID, TownID, HistoFamilyBalance,
                                          HistoFamilyMonthlyContributionMode FROM HistoFamilies WHERE FamilyID = $FamilyID
                                          AND HistoDate <= \"$HistoDate\" ORDER BY HistoDate DESC LIMIT 0,1");

         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // No entry found, so we search the first entry in the history of the family after the given date
                 $DbResult = $DbConnection->query("SELECT HistoFamilyID, HistoDate, FamilyID, TownID, HistoFamilyBalance,
                                                  HistoFamilyMonthlyContributionMode FROM HistoFamilies WHERE FamilyID = $FamilyID
                                                  AND HistoDate >= \"$HistoDate\" ORDER BY HistoDate ASC LIMIT 0,1");

                 if ($DbResult->numRows() == 0)
                 {
                     // No entry found, so we get current data of the family from the Families table
                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $FamilyID);
                     if (!empty($RecordFamily))
                     {
                         return array(
                                      'HistoFamilyID' => 0,
                                      'HistoDate' => date('Y-m-d H:i:s'),
                                      'FamilyID' => $FamilyID,
                                      'TownID' => $RecordFamily['TownID'],
                                      'HistoFamilyBalance' => $RecordFamily['FamilyBalance'],
                                      'HistoFamilyMonthlyContributionMode' => $RecordFamily['FamilyMonthlyContributionMode']
                                     );
                     }
                 }
                 else
                 {
                     // The result comes from the HistoFamilies table
                     $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                     return $Record;
                 }
             }
             else
             {
                 // The result comes from the HistoFamilies table
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record;
             }
         }
     }

     return array();
 }


/**
 * Get families filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.8
 *     - 2012-07-12 : taken into account the FamilySpecialAnnualContribution field to compute
 *                    the number of powers and patch a bug for activated families for a school year,
 *                    allow to get all children of a family or only activated children
 *     - 2013-03-11 : patch a bug about activated families (taken into account families arrived at school after
 *                    the beginning of the school year)
 *     - 2013-04-12 : allow to search several FamilyID (array) and taken into account the "FamilyCoopContribution" and
 *                    "FamilyPbCoopContribution" criterion
 *     - 2013-09-17 : use getSchoolYearStartDate() and getSchoolYearEndDate() to compute strat date and end date of
 *                    the school year and taken into account FamilyMonthlyContributionMode field
 *     - 2016-08-30 : taken into account the FamilyDate and FamilyDesactivationDate fields
 *     - 2017-09-21 : taken into account FamilyMainEmailContactAllowed and FamilySecondEmailContactAllowed fields,
 *                    taken into account of the FamilyWorkGroups option
 *     - 2019-01-21 : taken into account FamilyMainEmailInCommittee and FamilySecondEmailInCommittee fields
 *     - 2019-07-16 : taken into account the FamilyAnnualContributionBalance field
 *
 * @since 2012-01-20
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the families
 * @param $OrderBy                  String                 Criteria used to sort the families. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of families per page to return [1..n]
 *
 * @return Array of String                                 List of families filtered, an empty array otherwise
 */
 function dbSearchFamily($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find families
     $Select = "SELECT f.FamilyID, f.FamilyLastname, f.FamilyMainEmail, f.FamilySecondEmail, f.FamilyBalance, f.FamilyAnnualContributionBalance, f.FamilyNbMembers,
               f.FamilyNbPoweredMembers, f.FamilyMonthlyContributionMode, f.FamilyMainEmailContactAllowed, f.FamilySecondEmailContactAllowed,
               f.FamilyMainEmailInCommittee, f.FamilySecondEmailInCommittee, t.TownID, t.TownName, t.TownCode";
     $From = "FROM Children c, Towns t, Families f";
     $Where = " WHERE f.TownID = t.TownID AND f.FamilyID = c.FamilyID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (!empty($ArrayParams["FamilyID"])))
         {
             if (is_array($ArrayParams["FamilyID"]))
             {
                 $Where .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
             }
             else
             {
                 $Where .= " AND f.FamilyID = ".$ArrayParams["FamilyID"];
             }
         }

         // <<< Lastname fields >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             $Where .= " AND f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"";
         }

         // <<< Family still activated for some school years >>>
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             /* An activated family is a family with :
              * a creation date <= school year start date or a creation date between school year start date and end date
              * and with a desactivaton date NULL or >= school year end date
              * and with at least one activated child for the school year :
              * same criterion for the ChildSchoolDate and ChildDesactivationDate
              */
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND ("
                      ."((f.FamilyDate <= \"$SchoolYearStartDate\") OR (f.FamilyDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                      ." AND (f.FamilyDesactivationDate IS NULL OR f.FamilyDesactivationDate >= \"$SchoolYearEndDate\")"
                      ." AND ((c.ChildSchoolDate <= \"$SchoolYearStartDate\") OR (c.ChildSchoolDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                      ." AND (c.ChildDesactivationDate IS NULL OR c.ChildDesactivationDate >= \"$SchoolYearEndDate\")"
                      .")";
         }

         // <<< FamilyMonthlyContributionMode >>>
         if ((array_key_exists("FamilyMonthlyContributionMode", $ArrayParams)) && (count($ArrayParams["FamilyMonthlyContributionMode"]) > 0))
         {
             if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
             {
                 // We search families with given monthly contributions modes for a given school year (so, now)
                 $From .= ", HistoFamilies hf, (SELECT MAX(hfTmp.HistoFamilyID) AS MaxHistoFamilyID FROM HistoFamilies hfTmp
                           WHERE hfTmp.HistoDate <= \"$SchoolYearEndDate\" GROUP BY hfTmp.FamilyID) AS TmpHistoFamilies";

                 $Where .= " AND hf.HistoFamilyID = TmpHistoFamilies.MaxHistoFamilyID AND f.FamilyID = hf.FamilyID
                           AND hf.HistoFamilyMonthlyContributionMode IN ".constructSQLINString($ArrayParams["FamilyMonthlyContributionMode"]);
             }
             else
             {
                 // We search families with given monthly contributions modes for the current school year (so, now)
                 $Where .= " AND f.FamilyMonthlyContributionMode IN ".constructSQLINString($ArrayParams["FamilyMonthlyContributionMode"]);
             }
         }

         // <<< FamilyMainEmailContactAllowed >>>
         if ((array_key_exists("FamilyMainEmailContactAllowed", $ArrayParams)) && (count($ArrayParams["FamilyMainEmailContactAllowed"]) > 0))
         {
             // We search families with a main e-mail address allowed to be contacted after the family left the school
             $Where .= " AND f.FamilyMainEmailContactAllowed IN ".constructSQLINString($ArrayParams["FamilyMainEmailContactAllowed"]);
         }

         // <<< FamilySecondEmailContactAllowed >>>
         if ((array_key_exists("FamilySecondEmailContactAllowed", $ArrayParams)) && (count($ArrayParams["FamilySecondEmailContactAllowed"]) > 0))
         {
             // We search families with a second e-mail address allowed to be contacted after the family left the school
             $Where .= " AND f.FamilySecondEmailContactAllowed IN ".constructSQLINString($ArrayParams["FamilySecondEmailContactAllowed"]);
         }

         // <<< FamilyMainEmailInCommittee >>>
         if ((array_key_exists("FamilyMainEmailInCommittee", $ArrayParams)) && (count($ArrayParams["FamilyMainEmailInCommittee"]) > 0))
         {
             // We search families with a main e-mail address which must contacted for committee
             $Where .= " AND f.FamilyMainEmailInCommittee IN ".constructSQLINString($ArrayParams["FamilyMainEmailInCommittee"]);
         }

         // <<< FamilySecondEmailInCommittee >>>
         if ((array_key_exists("FamilySecondEmailInCommittee", $ArrayParams)) && (count($ArrayParams["FamilySecondEmailInCommittee"]) > 0))
         {
             // We search families with a second e-mail address which must contacted for committee
             $Where .= " AND f.FamilySecondEmailInCommittee IN ".constructSQLINString($ArrayParams["FamilySecondEmailInCommittee"]);
         }

         // <<< TownID >>>
         if ((array_key_exists("TownID", $ArrayParams)) && (count($ArrayParams["TownID"]) > 0))
         {
             $Where .= " AND t.TownID IN ".constructSQLINString($ArrayParams["TownID"]);
         }

         // <<< TownName field >>>
         if ((array_key_exists("TownName", $ArrayParams)) && (!empty($ArrayParams["TownName"])))
         {
             $Where .= " AND t.TownName LIKE \"".$ArrayParams["TownName"]."\"";
         }

         // <<< TownCode field >>>
         if ((array_key_exists("TownCode", $ArrayParams)) && (!empty($ArrayParams["TownCode"])))
         {
             $Where .= " AND t.TownCode LIKE \"".$ArrayParams["TownCode"]."\"";
         }

         // <<< Option : get families with pb to pay >>>
         if (array_key_exists("PbPayments", $ArrayParams))
         {
             // Pbs tp pay = balance < 0
             $Where .= " AND f.FamilyBalance < 0";
         }

         // <<< Option : get families with pb to pay the contribution of a school year >>>
         if ((array_key_exists("PbAnnualContributionPayments", $ArrayParams)) && (!empty($ArrayParams["PbAnnualContributionPayments"])))
         {
             // No payment for the school year
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["PbAnnualContributionPayments"]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["PbAnnualContributionPayments"]);

             $When = "HAVING CASE NbMembers";
             foreach($GLOBALS['CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS'][$ArrayParams["PbAnnualContributionPayments"]] as $Nb => $Price)
             {
                 $When .= " WHEN $Nb THEN TotalAmount >= $Price";
             }

             $When .= " END";

             $Where .= " AND f.FamilyID NOT IN (SELECT tf.FamilyID FROM (
                        SELECT ff.FamilyID, (ff.FamilyNbMembers + ff.FamilyNbPoweredMembers - ff.FamilySpecialAnnualContribution) AS NbMembers,
                        SUM(p.PaymentAmount) AS TotalAmount FROM Families ff, Payments p WHERE ff.FamilyID = p.FamilyID
                        AND p.PaymentType = 0 AND p.PaymentDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                        GROUP BY p.FamilyID $When) AS tf)";
         }

         // <<< Option : get families respecting contributions to events of a school year >>>
         if ((array_key_exists("FamilyCoopContribution", $ArrayParams)) && (!empty($ArrayParams["FamilyCoopContribution"])))
         {
             // Concerned school year
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["FamilyCoopContribution"]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["FamilyCoopContribution"]);

             foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS'] as $c => $NbMinCoop)
             {
                 // We keep only valided registrations
                 $From .= ", (SELECT er$c.FamilyID, COUNT(er$c.EventRegistrationID) AS NB$c
                           FROM Events e$c, EventTypes et$c, EventRegistrations er$c WHERE e$c.EventTypeID = et$c.EventTypeID
                           AND et$c.EventTypeCategory = $c AND e$c.EventID = er$c.EventID
                           AND e$c.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                           AND er$c.EventRegistrationValided = 1
                           GROUP BY er$c.FamilyID HAVING NB$c >= $NbMinCoop) AS Tev$c";
                 $Where .= " AND f.FamilyID = Tev$c.FamilyID";
             }
         }

         // <<< Option : get families don't respect contributions to events of a school year >>>
         if ((array_key_exists("FamilyPbCoopContribution", $ArrayParams)) && (!empty($ArrayParams["FamilyPbCoopContribution"])))
         {
             // Concerned school year
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["FamilyPbCoopContribution"]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["FamilyPbCoopContribution"]);

             foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS'] as $c => $NbMinCoop)
             {
                 // We keep only valided registrations
                 $Select .= ", IFNULL(Tev$c.NB$c, 0) AS NB$c";
                 $From .= " LEFT JOIN (SELECT er$c.FamilyID, COUNT(er$c.EventRegistrationID) AS NB$c
                           FROM Events e$c, EventTypes et$c, EventRegistrations er$c WHERE e$c.EventTypeID = et$c.EventTypeID
                           AND et$c.EventTypeCategory = $c AND e$c.EventID = er$c.EventID
                           AND e$c.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                           AND er$c.EventRegistrationValided = 1
                           GROUP BY er$c.FamilyID) AS Tev$c ON (f.FamilyID = Tev$c.FamilyID)";

                 if (empty($Having))
                 {
                     $Having = "HAVING NB$c < $NbMinCoop";
                 }
                 else
                 {
                     $Having .= " OR NB$c < $NbMinCoop";
                 }
             }
         }

         // <<< Option : get families registered to workgroups >>>
         if (array_key_exists("FamilyWorkGroups", $ArrayParams))
         {
             if ($ArrayParams["FamilyWorkGroups"])
             {
                 // We get registrations of families in workgroups
                 $From .= ", (SELECT wgr.FamilyID, COUNT(wgr.WorkGroupRegistrationID) AS WGRNB
                           FROM WorkGroupRegistrations wgr, WorkGroups wg WHERE wgr.WorkGroupID = wg.WorkGroupID
                           GROUP BY wgr.FamilyID) AS Twg";
                 $Where .= " AND f.FamilyID = Twg.FamilyID";
             }
             else
             {
                 // We get families not registered in workgroups
                 $Select .= ", IFNULL(Twg.WGRNB, 0) AS WGRNB";
                 $From .= " LEFT JOIN (SELECT wgr.FamilyID, COUNT(wgr.WorkGroupRegistrationID) AS WGRNB
                           FROM WorkGroupRegistrations wgr, WorkGroups wg WHERE wgr.WorkGroupID = wg.WorkGroupID
                           GROUP BY wgr.FamilyID) AS Twg ON (f.FamilyID = Twg.FamilyID)";

                 if (empty($Having))
                 {
                     $Having = "HAVING WGRNB = 0";
                 }
                 else
                 {
                     $Having .= " AND WGRNB = 0";
                 }
             }
         }

         // <<< FamilyDate field >>>
         if ((array_key_exists("FamilyDate", $ArrayParams)) && (count($ArrayParams["FamilyDate"]) >= 2))
         {
             if (count($ArrayParams["FamilyDate"]) == 4)
             {
                 // [0] -> operator, [1] start date, [2] -> operator, [3] -> end date
                 $Where .= " AND f.FamilyDate ".$ArrayParams["FamilyDate"][0]
                           ." \"".$ArrayParams["FamilyDate"][1]."\""
                           ." AND f.FamilyDate ".$ArrayParams["FamilyDate"][2]
                           ." \"".$ArrayParams["FamilyDate"][3]."\"";
             }
             else
             {
                 $Where .= " AND f.FamilyDate ".$ArrayParams["FamilyDate"][0]
                           ." \"".$ArrayParams["FamilyDate"][1]."\"";
             }
         }

         // <<< FamilyDesactivationDate field >>>
         if ((array_key_exists("FamilyDesactivationDate", $ArrayParams)) && (count($ArrayParams["FamilyDesactivationDate"]) >= 2))
         {
             if (count($ArrayParams["FamilyDesactivationDate"]) == 4)
             {
                 // [0] -> operator, [1] start date, [2] -> operator, [3] -> end date
                 $Where .= " AND f.FamilyDesactivationDate ".$ArrayParams["FamilyDesactivationDate"][0]
                           ." \"".$ArrayParams["FamilyDesactivationDate"][1]."\""
                           ." AND f.FamilyDesactivationDate ".$ArrayParams["FamilyDesactivationDate"][2]
                           ." \"".$ArrayParams["FamilyDesactivationDate"][3]."\"";
             }
             else
             {
                 $Where .= " AND f.FamilyDesactivationDate ".$ArrayParams["FamilyDesactivationDate"][0]
                           ." \"".$ArrayParams["FamilyDesactivationDate"][1]."\"";
             }
         }

         // <<< Option : get activated families >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ($ArrayParams["Activated"])
             {
                 // Activated families
                 $Where .= " AND f.FamilyDesactivationDate IS NULL";
             }
             else
             {
                 // Not activated families
                 $Where .= " AND f.FamilyDesactivationDate IS NOT NULL";
             }
         }
     }

     // We take into account the page and the number of purposes per page
     if ($Page < 1)
     {
         $Page = 1;
     }

     if ($RecordsPerPage < 0)
     {
         $RecordsPerPage = 10;
     }

     $Limit = '';
     if ($RecordsPerPage > 0)
     {
         $StartIndex = ($Page - 1) * $RecordsPerPage;
         $Limit = "LIMIT $StartIndex, $RecordsPerPage";
     }

     // We take into account the order by
     if ($OrderBy == "")
     {
         $StrOrderBy = "";
     }
     else
     {
         $StrOrderBy = " ORDER BY $OrderBy";
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY FamilyID $Having $StrOrderBy $Limit");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array(),
                                   "FamilyMainEmail" => array(),
                                   "FamilyMainEmailContactAllowed" => array(),
                                   "FamilyMainEmailInCommittee" => array(),
                                   "FamilySecondEmail" => array(),
                                   "FamilySecondEmailContactAllowed" => array(),
                                   "FamilySecondEmailInCommittee" => array(),
                                   "FamilyNbMembers" => array(),
                                   "FamilyNbPoweredMembers" => array(),
                                   "FamilyAnnualContributionBalance" => array(),
                                   "FamilyBalance" => array(),
                                   "FamilyMonthlyContributionMode" => array(),
                                   "TownID" => array(),
                                   "TownName" => array(),
                                   "TownCode" => array(),
                                   "NbChildren" => array()
                                   );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
                 $ArrayRecords["FamilyMainEmail"][] = $Record["FamilyMainEmail"];
                 $ArrayRecords["FamilyMainEmailContactAllowed"][] = $Record["FamilyMainEmailContactAllowed"];
                 $ArrayRecords["FamilyMainEmailInCommittee"][] = $Record["FamilyMainEmailInCommittee"];
                 $ArrayRecords["FamilySecondEmail"][] = $Record["FamilySecondEmail"];
                 $ArrayRecords["FamilySecondEmailContactAllowed"][] = $Record["FamilySecondEmailContactAllowed"];
                 $ArrayRecords["FamilySecondEmailInCommittee"][] = $Record["FamilySecondEmailInCommittee"];
                 $ArrayRecords["FamilyNbMembers"][] = $Record["FamilyNbMembers"];
                 $ArrayRecords["FamilyNbPoweredMembers"][] = $Record["FamilyNbPoweredMembers"];
                 $ArrayRecords["FamilyAnnualContributionBalance"][] = $Record["FamilyAnnualContributionBalance"];
                 $ArrayRecords["FamilyBalance"][] = $Record["FamilyBalance"];
                 $ArrayRecords["FamilyMonthlyContributionMode"][] = $Record["FamilyMonthlyContributionMode"];
                 $ArrayRecords["TownID"][] = $Record["TownID"];
                 $ArrayRecords["TownName"][] = $Record["TownName"];
                 $ArrayRecords["TownCode"][] = $Record["TownCode"];

                 $ArrayParamsChildren = array("FamilyID" => $Record["FamilyID"]);
                 if (array_key_exists("ActivatedChildren", $ArrayParams))
                 {
                     // We get only activated children
                     $ArrayParamsChildren = array_merge($ArrayParamsChildren, array("Activated" => TRUE));

                     if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
                     {
                         // We get only activated children for a school year
                         $ArrayParamsChildren = array_merge($ArrayParamsChildren, array("SchoolYear" => $ArrayParams["SchoolYear"]));
                     }
                 }

                 $ArrayRecords["NbChildren"][] = getNbdbSearchChild($DbConnection, $ArrayParamsChildren);
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of families filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.7
 *     - 2012-07-12 : taken into account the FamilySpecialAnnualContribution field to compute
 *                    the number of powers and patch a bug for activated families for a school year
 *     - 2013-03-11 : patch a bug about activated families (taken into account families arrived at school after
 *                    the beginning of the school year)
 *     - 2013-04-12 : allow to search several FamilyID (array) and taken into account the "FamilyCoopContribution" and
 *                    "FamilyPbCoopContribution" criterion
 *     - 2013-09-17 : use getSchoolYearStartDate() and getSchoolYearEndDate() to compute strat date and end date of
 *                    the school year and taken into account FamilyMonthlyContributionMode field
 *     - 2016-08-30 : taken into account the FamilyDate and FamilyDesactivationDate fields
 *     - 2017-09-21 : taken into account FamilyMainEmailContactAllowed and FamilySecondEmailContactAllowed fields,
 *                    taken into account of the FamilyWorkGroups option
 *     - 2019-01-21 : taken into account FamilyMainEmailInCommittee and FamilySecondEmailInCommittee fields
 *
 * @since 2006-01-19
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the families
 *
 * @return Integer              Number of the families found, 0 otherwise
 */
 function getNbdbSearchFamily($DbConnection, $ArrayParams)
 {
     // SQL request to find families
     $Select = "SELECT f.FamilyID";
     $From = "FROM Children c, Towns t, Families f";
     $Where = " WHERE f.TownID = t.TownID AND f.FamilyID = c.FamilyID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (!empty($ArrayParams["FamilyID"])))
         {
             if (is_array($ArrayParams["FamilyID"]))
             {
                 $Where .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
             }
             else
             {
                 $Where .= " AND f.FamilyID = ".$ArrayParams["FamilyID"];
             }
         }

         // <<< Lastname fields >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             $Where .= " AND f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"";
         }

         // <<< Family still activated for some school years >>>
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             /* An activated family is a family with :
              * a creation date <= school year start date or a creation date between school year start date and end date
              * and with a desactivaton date NULL or >= school year end date
              * and with at least one activated child for the school year :
              * same criterion for the ChildSchoolDate and ChildDesactivationDate
              */
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND ("
                      ."((f.FamilyDate <= \"$SchoolYearStartDate\") OR (f.FamilyDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                      ." AND (f.FamilyDesactivationDate IS NULL OR f.FamilyDesactivationDate >= \"$SchoolYearEndDate\")"
                      ." AND ((c.ChildSchoolDate <= \"$SchoolYearStartDate\") OR (c.ChildSchoolDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                      ." AND (c.ChildDesactivationDate IS NULL OR c.ChildDesactivationDate >= \"$SchoolYearEndDate\")"
                      .")";
         }

         // <<< FamilyMonthlyContributionMode >>>
         if ((array_key_exists("FamilyMonthlyContributionMode", $ArrayParams)) && (count($ArrayParams["FamilyMonthlyContributionMode"]) > 0))
         {
             if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
             {
                 // We search families with given monthly contributions modes for a given school year (so, now)
                 $From .= ", HistoFamilies hf, (SELECT MAX(hfTmp.HistoFamilyID) AS MaxHistoFamilyID FROM HistoFamilies hfTmp
                           WHERE hfTmp.HistoDate <= \"$SchoolYearEndDate\" GROUP BY hfTmp.FamilyID) AS TmpHistoFamilies";

                 $Where .= " AND hf.HistoFamilyID = TmpHistoFamilies.MaxHistoFamilyID AND f.FamilyID = hf.FamilyID
                           AND hf.HistoFamilyMonthlyContributionMode IN ".constructSQLINString($ArrayParams["FamilyMonthlyContributionMode"]);
             }
             else
             {
                 // We search families with given monthly contributions modes for the current school year (so, now)
                 $Where .= " AND f.FamilyMonthlyContributionMode IN ".constructSQLINString($ArrayParams["FamilyMonthlyContributionMode"]);
             }
         }

         // <<< FamilyMainEmailContactAllowed >>>
         if ((array_key_exists("FamilyMainEmailContactAllowed", $ArrayParams)) && (count($ArrayParams["FamilyMainEmailContactAllowed"]) > 0))
         {
             // We search families with a main e-mail address allowed to be contacted after the family left the school
             $Where .= " AND f.FamilyMainEmailContactAllowed IN ".constructSQLINString($ArrayParams["FamilyMainEmailContactAllowed"]);
         }

         // <<< FamilySecondEmailContactAllowed >>>
         if ((array_key_exists("FamilySecondEmailContactAllowed", $ArrayParams)) && (count($ArrayParams["FamilySecondEmailContactAllowed"]) > 0))
         {
             // We search families with a second e-mail address allowed to be contacted after the family left the school
             $Where .= " AND f.FamilySecondEmailContactAllowed IN ".constructSQLINString($ArrayParams["FamilySecondEmailContactAllowed"]);
         }

         // <<< FamilyMainEmailInCommittee >>>
         if ((array_key_exists("FamilyMainEmailInCommittee", $ArrayParams)) && (count($ArrayParams["FamilyMainEmailInCommittee"]) > 0))
         {
             // We search families with a main e-mail address which must contacted for committee
             $Where .= " AND f.FamilyMainEmailInCommittee IN ".constructSQLINString($ArrayParams["FamilyMainEmailInCommittee"]);
         }

         // <<< FamilySecondEmailInCommittee >>>
         if ((array_key_exists("FamilySecondEmailInCommittee", $ArrayParams)) && (count($ArrayParams["FamilySecondEmailInCommittee"]) > 0))
         {
             // We search families with a second e-mail address which must contacted for committee
             $Where .= " AND f.FamilySecondEmailInCommittee IN ".constructSQLINString($ArrayParams["FamilySecondEmailInCommittee"]);
         }

         // <<< TownID >>>
         if ((array_key_exists("TownID", $ArrayParams)) && (count($ArrayParams["TownID"]) > 0))
         {
             $Where .= " AND t.TownID IN ".constructSQLINString($ArrayParams["TownID"]);
         }

         // <<< TownName fields >>>
         if ((array_key_exists("TownName", $ArrayParams)) && (!empty($ArrayParams["TownName"])))
         {
             $Where .= " AND t.TownName LIKE \"".$ArrayParams["TownName"]."\"";
         }

         // <<< TownCode fields >>>
         if ((array_key_exists("TownCode", $ArrayParams)) && (!empty($ArrayParams["TownCode"])))
         {
             $Where .= " AND t.TownCode LIKE \"".$ArrayParams["TownCode"]."\"";
         }

         // <<< Option : get families with pb to pay >>>
         if (array_key_exists("PbPayments", $ArrayParams))
         {
             // Pbs tp pay = balance < 0
             $Where .= " AND f.FamilyBalance < 0";
         }

         // <<< Option : get families with pb to pay the contribution of a school year >>>
         if ((array_key_exists("PbAnnualContributionPayments", $ArrayParams)) && (!empty($ArrayParams["PbAnnualContributionPayments"])))
         {
             // No payment for the school year
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["PbAnnualContributionPayments"]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["PbAnnualContributionPayments"]);

             $When = "HAVING CASE NbMembers";
             foreach($GLOBALS['CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS'][$ArrayParams["PbAnnualContributionPayments"]] as $Nb => $Price)
             {
                 $When .= " WHEN $Nb THEN TotalAmount >= $Price";
             }

             $When .= " END";

             $Where .= " AND f.FamilyID NOT IN (SELECT tf.FamilyID FROM (
                        SELECT ff.FamilyID, (ff.FamilyNbMembers + ff.FamilyNbPoweredMembers - ff.FamilySpecialAnnualContribution) AS NbMembers,
                        SUM(p.PaymentAmount) AS TotalAmount FROM Families ff, Payments p WHERE ff.FamilyID = p.FamilyID
                        AND p.PaymentType = 0 AND p.PaymentDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                        GROUP BY p.FamilyID $When) AS tf)";
         }

         // <<< Option : get families respecting contributions to events of a school year >>>
         if ((array_key_exists("FamilyCoopContribution", $ArrayParams)) && (!empty($ArrayParams["FamilyCoopContribution"])))
         {
             // Concerned school year
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["FamilyCoopContribution"]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["FamilyCoopContribution"]);

             foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS'] as $c => $NbMinCoop)
             {
                 // We keep only valided registrations
                 $From .= ", (SELECT er$c.FamilyID, COUNT(er$c.EventRegistrationID) AS NB$c
                           FROM Events e$c, EventTypes et$c, EventRegistrations er$c WHERE e$c.EventTypeID = et$c.EventTypeID
                           AND et$c.EventTypeCategory = $c AND e$c.EventID = er$c.EventID
                           AND e$c.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                           AND er$c.EventRegistrationValided = 1
                           GROUP BY er$c.FamilyID HAVING NB$c >= $NbMinCoop) AS Tev$c";
                 $Where .= " AND f.FamilyID = Tev$c.FamilyID";
             }
         }

         // <<< Option : get families don't respect contributions to events of a school year >>>
         if ((array_key_exists("FamilyPbCoopContribution", $ArrayParams)) && (!empty($ArrayParams["FamilyPbCoopContribution"])))
         {
             // Concerned school year
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["FamilyPbCoopContribution"]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["FamilyPbCoopContribution"]);

             foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS'] as $c => $NbMinCoop)
             {
                 // We keep only valided registrations
                 $Select .= ", IFNULL(Tev$c.NB$c, 0) AS NB$c";
                 $From .= " LEFT JOIN (SELECT er$c.FamilyID, COUNT(er$c.EventRegistrationID) AS NB$c
                           FROM Events e$c, EventTypes et$c, EventRegistrations er$c WHERE e$c.EventTypeID = et$c.EventTypeID
                           AND et$c.EventTypeCategory = $c AND e$c.EventID = er$c.EventID
                           AND e$c.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                           AND er$c.EventRegistrationValided = 1
                           GROUP BY er$c.FamilyID) AS Tev$c ON (f.FamilyID = Tev$c.FamilyID)";

                 if (empty($Having))
                 {
                     $Having = "HAVING NB$c < $NbMinCoop";
                 }
                 else
                 {
                     $Having .= " OR NB$c < $NbMinCoop";
                 }
             }
         }

         // <<< Option : get families registered to workgroups >>>
         if (array_key_exists("FamilyWorkGroups", $ArrayParams))
         {
             if ($ArrayParams["FamilyWorkGroups"])
             {
                 // We get registrations of families in workgroups
                 $From .= ", (SELECT wgr.FamilyID, COUNT(wgr.WorkGroupRegistrationID) AS WGRNB
                           FROM WorkGroupRegistrations wgr, WorkGroups wg WHERE wgr.WorkGroupID = wg.WorkGroupID
                           GROUP BY wgr.FamilyID) AS Twg";
                 $Where .= " AND f.FamilyID = Twg.FamilyID";
             }
             else
             {
                 // We get families not registered in workgroups
                 $Select .= ", IFNULL(Twg.WGRNB, 0) AS WGRNB";
                 $From .= " LEFT JOIN (SELECT wgr.FamilyID, COUNT(wgr.WorkGroupRegistrationID) AS WGRNB
                           FROM WorkGroupRegistrations wgr, WorkGroups wg WHERE wgr.WorkGroupID = wg.WorkGroupID
                           GROUP BY wgr.FamilyID) AS Twg ON (f.FamilyID = Twg.FamilyID)";

                 if (empty($Having))
                 {
                     $Having = "HAVING WGRNB = 0";
                 }
                 else
                 {
                     $Having .= " AND WGRNB = 0";
                 }
             }
         }

         // <<< FamilyDate field >>>
         if ((array_key_exists("FamilyDate", $ArrayParams)) && (count($ArrayParams["FamilyDate"]) >= 2))
         {
             if (count($ArrayParams["FamilyDate"]) == 4)
             {
                 // [0] -> operator, [1] start date, [2] -> operator, [3] -> end date
                 $Where .= " AND f.FamilyDate ".$ArrayParams["FamilyDate"][0]
                           ." \"".$ArrayParams["FamilyDate"][1]."\""
                           ." AND f.FamilyDate ".$ArrayParams["FamilyDate"][2]
                           ." \"".$ArrayParams["FamilyDate"][3]."\"";
             }
             else
             {
                 $Where .= " AND f.FamilyDate ".$ArrayParams["FamilyDate"][0]
                           ." \"".$ArrayParams["FamilyDate"][1]."\"";
             }
         }

         // <<< FamilyDesactivationDate field >>>
         if ((array_key_exists("FamilyDesactivationDate", $ArrayParams)) && (count($ArrayParams["FamilyDesactivationDate"]) >= 2))
         {
             if (count($ArrayParams["FamilyDesactivationDate"]) == 4)
             {
                 // [0] -> operator, [1] start date, [2] -> operator, [3] -> end date
                 $Where .= " AND f.FamilyDesactivationDate ".$ArrayParams["FamilyDesactivationDate"][0]
                           ." \"".$ArrayParams["FamilyDesactivationDate"][1]."\""
                           ." AND f.FamilyDesactivationDate ".$ArrayParams["FamilyDesactivationDate"][2]
                           ." \"".$ArrayParams["FamilyDesactivationDate"][3]."\"";
             }
             else
             {
                 $Where .= " AND f.FamilyDesactivationDate ".$ArrayParams["FamilyDesactivationDate"][0]
                           ." \"".$ArrayParams["FamilyDesactivationDate"][1]."\"";
             }
         }

         // <<< Option : get activated families >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ($ArrayParams["Activated"])
             {
                 // Activated families
                 $Where .= " AND f.FamilyDesactivationDate IS NULL";
             }
             else
             {
                 // Not activated families
                 $Where .= " AND f.FamilyDesactivationDate IS NOT NULL";
             }
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY FamilyID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }
?>