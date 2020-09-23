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
 * Common module : library of database functions used for the donations table
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2016-05-30
 */


/**
 * Check if a donation exists in the Donations table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-30
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $DonationID           Integer      ID of the donation searched [1..n]
 *
 * @return Boolean              TRUE if the donation exists, FALSE otherwise
 */
 function isExistingDonation($DbConnection, $DonationID)
 {
     $DbResult = $DbConnection->query("SELECT DonationID FROM Donations WHERE DonationID = $DonationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The donation exists
             return TRUE;
         }
     }

     // The donation doesn't exist
     return FALSE;
 }


/**
 * Give the ID of a donation thanks to its reference
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-30
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $DonationRef          String       Reference of the donation searched
 *
 * @return Integer              ID of the donation, 0 otherwise
 */
 function getDonationID($DbConnection, $DonationRef)
 {
     $DbResult = $DbConnection->query("SELECT DonationID FROM Donations WHERE DonationReference = \"$DonationRef\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["DonationID"];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the reference of a donation thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-30
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $DonationID           Integer      ID of the donation searched
 *
 * @return String               Reference of the donation, empty string otherwise
 */
 function getDonationRef($DbConnection, $DonationID)
 {
     $DbResult = $DbConnection->query("SELECT DonationReference FROM Donations WHERE DonationID = $DonationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["DonationReference"];
         }
     }

     // ERROR
     return "";
 }


/**
 * Generate a reference for a new donation
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-31
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 *
 * @return String               Reference of the new donation, empty string otherwise
 */
 function generateDonationRef($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT DonationID FROM Donations ORDER BY DonationID DESC LIMIT 0,1");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return ($Record["DonationID"] + 1);
         }
         else
         {
             // First reference
             return 1;
         }
     }

     // ERROR
     return "";
 }


/**
 * Check if a payment of a donation is unique for a bank and a check, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-31
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $BankID                    Integer      ID of the bank if payment is a check [1..n], NULL otherwise
 * @param $DonationPaymentCheckNb    String    Check number
 *
 * @return Boolean                   TRUE if the payement of the donation is unique, FALSE otherwise
 */
 function isUniqueDonationPayment($DbConnection, $DonationID, $BankID, $DonationPaymentCheckNb)
 {
     if ($DonationID > 0)
     {
         if (empty($BankID))
         {
             return TRUE;
         }
         else if ($BankID > 0)
         {
             if (!empty($PaymentCheckNb))
             {
                 // Check if the payment is unique for the bank
                 $DbResult = $DbConnection->query("SELECT DonationID FROM Donations WHERE DonationID <> $DonationID AND BankID = $BankID
                                                   AND DonationPaymentCheckNb = \"$DonationPaymentCheckNb\"");
                 if (!DB::isError($DbResult))
                 {
                     if ($DbResult->numRows() == 0)
                     {
                         return TRUE;
                     }
                 }
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Add a donation in the Donations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-30
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $DonationReference             String       Reference of the donation
 * @param $DonationLastname              String       Lastname of the donator of the donation
 * @param $DonationFirstname             String       Firstname of the donator of the donation
 * @param $DonationAddress               String       Address of the donator
 * @param $TownID                        Integer      ID of the town of the donator
 * @param $DonationReceptionDate         Date         Date of the receipt of the payment (yyyy-mm-dd)
 * @param $DonationValue                 Float        Amount or value of the donation
 * @param $DonationType                  Integer      Type of the donation [0..n]
 * @param $DonationNature                Integer      Nature of the doantion [0..n]
 * @param $DonationPaymentMode           Integer      Mode of the payment [0..n]
 * @param $BankID                        Integer      ID of the bank if payment is a check [1..n], NULL otherwise
 * @param $DonationPaymentCheckNb        String       Check number of the payment for the donation
 * @param $DonationEntity                Integer      Entity of the donator [1..n]
 * @param $FamilyID                      Integer      Family associated to the donator [1..n], NULL if no association
 * @param $DonationFamilyRelationship    Integer      Relationship between the family selected and the donator
 * @param $DonationMainEmail             String       Mail e-mail of the donator
 * @param $DonationSecondEmail           String       Second e-mail of the donator
 * @param $DonationPhone                 String       Phone of the donator
 * @param $DonationReason                String       Reason of the donation (in donation in nature)
 *
 * @return Integer                       The primary key of the donation [1..n], 0 otherwise
 */
 function dbAddDonation($DbConnection, $DonationReference, $DonationLastname, $DonationFirstname, $DonationAddress, $TownID, $DonationReceptionDate, $DonationValue, $DonationType = 0, $DonationNature = 0, $DonationPaymentMode = 0, $BankID = NULL, $DonationPaymentCheckNb = NULL, $DonationEntity = 0, $FamilyID = NULL, $DonationFamilyRelationship = 0, $DonationMainEmail = '', $DonationSecondEmail = '', $DonationPhone = '', $DonationReason = '')
 {
     if ((!empty($DonationReference)) && (!empty($DonationLastname)) && (!empty($DonationFirstname)) && (!empty($DonationAddress))
         && ($TownID > 0) && ($DonationValue > 0) && ($DonationType >= 0) && ($DonationNature >= 0)
         && ($DonationPaymentMode >= 0) && ($DonationEntity >= 0) && ($DonationFamilyRelationship >= 0))
     {
         // Check if the donation is a new donation
         $DbResult = $DbConnection->query("SELECT DonationID FROM Donations WHERE DonationReference = \"$DonationReference\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the DonationReceptionDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $DonationReceptionDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $DonationReceptionDate = ", DonationReceptionDate = \"$DonationReceptionDate\"";
                 }

                 if (empty($FamilyID))
                 {
                     $FamilyID = ", FamilyID = NULL";
                 }
                 elseif ($FamilyID < 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $FamilyID = ", FamilyID = $FamilyID";
                 }

                 if (empty($BankID))
                 {
                     $BankID = ", BankID = NULL";
                 }
                 elseif ($BankID < 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $BankID = ", BankID = $BankID";
                 }

                 if (empty($DonationPaymentCheckNb))
                 {
                     $DonationPaymentCheckNb = ", DonationPaymentCheckNb = NULL";
                 }
                 else
                 {
                     $DonationPaymentCheckNb = ", DonationPaymentCheckNb = \"$DonationPaymentCheckNb\"";
                 }

                 if (empty($DonationReason))
                 {
                     $DonationReason = ", DonationReason = NULL";
                 }
                 else
                 {
                     $DonationReason = ", DonationReason = \"$DonationReason\"";
                 }

                 // It's a new donation
                 $id = getNewPrimaryKey($DbConnection, "Donations", "DonationID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Donations SET DonationID = $id, DonationReference = \"$DonationReference\",
                                                       DonationLastname = \"$DonationLastname\", DonationFirstname = \"$DonationFirstname\",
                                                       DonationAddress = \"$DonationAddress\", TownID = $TownID,
                                                       DonationMainEmail = \"$DonationMainEmail\", DonationValue = $DonationValue,
                                                       DonationType = $DonationType, DonationNature = $DonationNature,
                                                       DonationPaymentMode = $DonationPaymentMode, DonationEntity = $DonationEntity,
                                                       DonationFamilyRelationship = $DonationFamilyRelationship,
                                                       DonationSecondEmail = \"$DonationSecondEmail\", DonationPhone = \"$DonationPhone\"
                                                       $FamilyID $BankID $DonationReceptionDate $DonationPaymentCheckNb $DonationReason");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The donation already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['DonationID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing donation in the Donations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-20
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $DonationID                    Integer      ID of the donation to update [1..n]
 * @param $DonationReference             String       Reference of the donation
 * @param $DonationLastname              String       Lastname of the donator of the donation
 * @param $DonationFirstname             String       Firstname of the donator of the donation
 * @param $DonationAddress               String       Address of the donator
 * @param $TownID                        Integer      ID of the town of the donator
 * @param $DonationReceptionDate         Date         Date of the receipt of the payment (yyyy-mm-dd)
 * @param $DonationValue                 Float        Amount or value of the donation
 * @param $DonationType                  Integer      Type of the donation [0..n]
 * @param $DonationNature                Integer      Nature of the doantion [0..n]
 * @param $DonationPaymentMode           Integer      Mode of the payment [0..n]
 * @param $BankID                        Integer      ID of the bank if payment is a check [1..n], NULL otherwise
 * @param $DonationPaymentCheckNb        String       Check number of the payment for the donation
 * @param $DonationEntity                Integer      Entity of the donator [1..n]
 * @param $FamilyID                      Integer      Family associated to the donator [1..n], NULL if no association
 * @param $DonationFamilyRelationship    Integer      Relationship between the family selected and the donator
 * @param $DonationMainEmail             String       Mail e-mail of the donator
 * @param $DonationSecondEmail           String       Second e-mail of the donator
 * @param $DonationPhone                 String       Phone of the donator
 * @param $DonationReason                String       Reason of the donation (in donation in nature)
 *
 * @return Integer                       The primary key of the donation [1..n], 0 otherwise
 */
 function dbUpdateDonation($DbConnection, $DonationID, $DonationReference = NULL, $DonationLastname = NULL, $DonationFirstname = NULL, $DonationAddress = NULL, $TownID = NULL, $DonationReceptionDate = NULL, $DonationValue = NULL, $DonationType = NULL, $DonationNature = NULL, $DonationPaymentMode = NULL, $BankID = NULL, $DonationPaymentCheckNb = NULL, $DonationEntity = NULL, $FamilyID = NULL, $DonationFamilyRelationship = NULL, $DonationMainEmail = NULL, $DonationSecondEmail = NULL, $DonationPhone = NULL, $DonationReason = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($DonationID < 1) || (!isInteger($DonationID)))
     {
         // ERROR
         return 0;
     }

     // Check if the DonationReference is valide
     if (!is_null($DonationReference))
     {
         if (empty($DonationReference))
         {
             return 0;
         }
         else
         {
             // The DonationReference field will be updated
             $ArrayParamsUpdate[] = "DonationReference = \"$DonationReference\"";
         }
     }
     else
     {
         // We get the reference
         $DonationReference = getTableFieldValue($DbConnection, 'Donations', $DonationID, 'DonationReference');
     }

     // Check if the DonationLastname is valide
     if (!is_null($DonationLastname))
     {
         if (empty($DonationLastname))
         {
             return 0;
         }
         else
         {
             // The DonationLastname field will be updated
             $ArrayParamsUpdate[] = "DonationLastname = \"$DonationLastname\"";
         }
     }

     // Check if the DonationFirstname is valide
     if (!is_null($DonationFirstname))
     {
         if (empty($DonationFirstname))
         {
             return 0;
         }
         else
         {
             // The DonationFirstname field will be updated
             $ArrayParamsUpdate[] = "DonationFirstname = \"$DonationFirstname\"";
         }
     }

     // Check if the DonationAddress is valide
     if (!is_null($DonationAddress))
     {
         if (empty($DonationAddress))
         {
             return 0;
         }
         else
         {
             // The DonationAddress field will be updated
             $ArrayParamsUpdate[] = "DonationAddress = \"$DonationAddress\"";
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

     // Check if the DonationReceptionDate is valide
     if (!is_null($DonationReceptionDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $DonationReceptionDate) == 0)
         {
             return 0;
         }
         else
         {
             // The DonationReceptionDate field will be updated
             $ArrayParamsUpdate[] = "DonationReceptionDate = \"$DonationReceptionDate\"";
         }
     }

     if (!is_null($DonationValue))
     {
         if ($DonationValue <= 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The DonationValue field will be updated
             $ArrayParamsUpdate[] = "DonationValue = $DonationValue";
         }
     }

     if (!is_Null($DonationType))
     {
         if (($DonationType < 0) || (!isInteger($DonationType)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The DonationType field will be updated
             $ArrayParamsUpdate[] = "DonationType = $DonationType";
         }
     }

     if (!is_Null($DonationNature))
     {
         if (($DonationNature < 0) || (!isInteger($DonationNature)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The DonationNature field will be updated
             $ArrayParamsUpdate[] = "DonationNature = $DonationNature";
         }
     }

     if (!is_Null($DonationEntity))
     {
         if (($DonationEntity < 0) || (!isInteger($DonationEntity)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The DonationEntity field will be updated
             $ArrayParamsUpdate[] = "DonationEntity = $DonationEntity";
         }
     }

     if (!is_null($FamilyID))
     {
         if (($FamilyID < 0) || (!isInteger($FamilyID)))
         {
             // ERROR
             return 0;
         }
         elseif (empty($FamilyID))
         {
             $ArrayParamsUpdate[] = "FamilyID = NULL";
         }
         else
         {
             $ArrayParamsUpdate[] = "FamilyID = $FamilyID";
         }
     }
     else
     {
         // We get the FamilyID
         $FamilyID = getTableFieldValue($DbConnection, 'Donations', $DonationID, 'FamilyID');
     }

     if (!is_Null($DonationFamilyRelationship))
     {
         if (($DonationFamilyRelationship < 0) || (!isInteger($DonationFamilyRelationship)))
         {
             // ERROR
             return 0;
         }
         elseif ((!is_null($FamilyID)) && ($FamilyID > 0) && ($DonationFamilyRelationship <= 0))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The DonationFamilyRelationship field will be updated
             $ArrayParamsUpdate[] = "DonationFamilyRelationship = $DonationFamilyRelationship";
         }
     }

     if (!is_Null($DonationPaymentMode))
     {
         if (($DonationPaymentMode < 0) || (!isInteger($DonationPaymentMode)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The DonationPaymentMode field will be updated
             $ArrayParamsUpdate[] = "DonationPaymentMode = $DonationPaymentMode";
         }
     }

     if (!is_null($DonationPaymentCheckNb))
     {
         if (empty($DonationPaymentCheckNb))
         {
             // The DonationPaymentCheckNb field will be updated
             $ArrayParamsUpdate[] = "DonationPaymentCheckNb = NULL";
         }
         else
         {
             // The DonationPaymentCheckNb field will be updated
             $ArrayParamsUpdate[] = "DonationPaymentCheckNb = \"$DonationPaymentCheckNb\"";
         }
     }

     if (!is_null($BankID))
     {
         if (($BankID < 0) || (!isInteger($BankID)))
         {
             // ERROR
             return 0;
         }
         elseif (empty($BankID))
         {
             $ArrayParamsUpdate[] = "BankID = NULL";
         }
         else
         {
             $ArrayParamsUpdate[] = "BankID = $BankID";
         }
     }

     if (!is_Null($DonationMainEmail))
     {
         // The DonationMainEmail field will be updated
         $ArrayParamsUpdate[] = "DonationMainEmail = \"$DonationMainEmail\"";
     }

     if (!is_Null($DonationSecondEmail))
     {
         // The DonationSecondEmail field will be updated
         $ArrayParamsUpdate[] = "DonationSecondEmail = \"$DonationSecondEmail\"";
     }

     if (!is_Null($DonationPhone))
     {
         // The DonationPhone field will be updated
         $ArrayParamsUpdate[] = "DonationPhone = \"$DonationPhone\"";
     }

     if (!is_Null($DonationReason))
     {
         // The DonationReason field will be updated
         $ArrayParamsUpdate[] = "DonationReason = \"$DonationReason\"";
     }

     // Here, the parameters are correct, we check if the donation exists
     if (isExistingDonation($DbConnection, $DonationID))
     {
         // We check if the reference is unique
         $DbResult = $DbConnection->query("SELECT DonationID FROM Donations WHERE DonationReference = \"$DonationReference\"
                                           AND DonationID <> $DonationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The donation exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Donations SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE DonationID = $DonationID");
                     if (!DB::isError($DbResult))
                     {
                         // Donation updated
                         return $DonationID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $DonationID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Get donations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-30
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the donations
 * @param $OrderBy                  String                 Criteria used to sort the donations. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of donations per page to return [1..n]
 *
 * @return Array of String                                 List of donations filtered, an empty array otherwise
 */
 function dbSearchDonations($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find donations
     $Select = "SELECT d.DonationID, d.DonationReference, d.DonationEntity, d.DonationLastname, d.DonationFirstname,
                d.DonationMainEmail, d.DonationSecondEmail, d.DonationType, d.DonationNature, d.DonationReceptionDate,
                d.DonationValue, f.FamilyID, f.FamilyLastname";
     $From = "FROM Towns t, Donations d LEFT JOIN Families f ON (d.FamilyID = f.FamilyID) LEFT JOIN Banks b ON (d.BankID = b.BankID)";
     $Where = "WHERE t.TownID = d.TownID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< DonationID field >>>
         if ((array_key_exists("DonationID", $ArrayParams)) && (!empty($ArrayParams["DonationID"])))
         {
             if (is_array($ArrayParams["DonationID"]))
             {
                 $Where .= " AND d.DonationID IN ".constructSQLINString($ArrayParams["DonationID"]);
             }
             else
             {
                 $Where .= " AND d.DonationID = ".$ArrayParams["DonationID"];
             }
         }

         // <<< DonationReference field >>>
         if ((array_key_exists("DonationReference", $ArrayParams)) && (!empty($ArrayParams["DonationReference"])))
         {
             $Where .= " AND d.DonationReference LIKE \"".$ArrayParams["DonationReference"]."\"";
         }

         // <<< Donations for years >>>
         if ((array_key_exists("Year", $ArrayParams)) && (count($ArrayParams["Year"]) > 0))
         {
             $YearStartDate = $ArrayParams["Year"][0].'-01-01';
             $YearEndDate = $ArrayParams["Year"][0].'-12-31';

             $Where .= " AND d.DonationReceptionDate BETWEEN \"$YearStartDate\" AND \"$YearEndDate\"";
         }

         // <<< Donations of school years >>>
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND d.DonationReceptionDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         // <<< Donations between 2 given dates >>>
         if ((array_key_exists("StartDate", $ArrayParams)) && (count($ArrayParams["StartDate"]) == 2))
         {
             // [0] -> operator (>, <, >=...), [1] -> date
             $Where .= " AND d.DonationReceptionDate ".$ArrayParams["StartDate"][0]." \"".$ArrayParams["StartDate"][1]."\"";
         }

         if ((array_key_exists("EndDate", $ArrayParams)) && (count($ArrayParams["EndDate"]) == 2))
         {
             // [0] -> operator (>, <, >=...), [1] -> date
             $Where .= " AND d.DonationReceptionDate ".$ArrayParams["EndDate"][0]." \"".$ArrayParams["EndDate"][1]."\"";
         }

         // <<< DonationType field >>>
         if ((array_key_exists("DonationType", $ArrayParams)) && (count($ArrayParams["DonationType"]) > 0))
         {
             $Where .= " AND d.DonationType IN ".constructSQLINString($ArrayParams["DonationType"]);
         }

         // <<< DonationNature field >>>
         if ((array_key_exists("DonationNature", $ArrayParams)) && (count($ArrayParams["DonationNature"]) > 0))
         {
             $Where .= " AND d.DonationNature IN ".constructSQLINString($ArrayParams["DonationNature"]);
         }

         // <<< DonationEntity field >>>
         if ((array_key_exists("DonationEntity", $ArrayParams)) && (count($ArrayParams["DonationEntity"]) > 0))
         {
             $Where .= " AND d.DonationEntity IN ".constructSQLINString($ArrayParams["DonationEntity"]);
         }

         // <<< DonationLastname field >>>
         if ((array_key_exists("DonationLastname", $ArrayParams)) && (!empty($ArrayParams["DonationLastname"])))
         {
             $Where .= " AND d.DonationLastname LIKE \"".$ArrayParams["DonationLastname"]."\"";
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

         // <<< Family lastname fields >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             $Where .= " AND f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"";
         }
     }

     // We take into account the page and the number of donations per page
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY DonationID $Having $StrOrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "DonationID" => array(),
                                   "DonationReference" => array(),
                                   "DonationEntity" => array(),
                                   "DonationLastname" => array(),
                                   "DonationFirstname" => array(),
                                   "DonationMainEmail" => array(),
                                   "DonationSecondEmail" => array(),
                                   "DonationType" => array(),
                                   "DonationNature" => array(),
                                   "DonationReceptionDate" => array(),
                                   "DonationValue" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["DonationID"][] = $Record["DonationID"];
                 $ArrayRecords["DonationReference"][] = $Record["DonationReference"];
                 $ArrayRecords["DonationEntity"][] = $Record["DonationEntity"];
                 $ArrayRecords["DonationLastname"][] = $Record["DonationLastname"];
                 $ArrayRecords["DonationFirstname"][] = $Record["DonationFirstname"];
                 $ArrayRecords["DonationMainEmail"][] = $Record["DonationMainEmail"];
                 $ArrayRecords["DonationSecondEmail"][] = $Record["DonationSecondEmail"];
                 $ArrayRecords["DonationType"][] = $Record["DonationType"];
                 $ArrayRecords["DonationNature"][] = $Record["DonationNature"];
                 $ArrayRecords["DonationReceptionDate"][] = $Record["DonationReceptionDate"];
                 $ArrayRecords["DonationValue"][] = $Record["DonationValue"];
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of donations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-30
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the donations
 *
 * @return Integer              Number of the donations found, 0 otherwise
 */
 function getNbdbSearchDonations($DbConnection, $ArrayParams)
 {
     // SQL request to find donations
     $Select = "SELECT d.DonationID";
     $From = "FROM Towns t, Donations d LEFT JOIN Families f ON (d.FamilyID = f.FamilyID) LEFT JOIN Banks b ON (d.BankID = b.BankID)";
     $Where = "WHERE t.TownID = d.TownID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< DonationID field >>>
         if ((array_key_exists("DonationID", $ArrayParams)) && (!empty($ArrayParams["DonationID"])))
         {
             if (is_array($ArrayParams["DonationID"]))
             {
                 $Where .= " AND d.DonationID IN ".constructSQLINString($ArrayParams["DonationID"]);
             }
             else
             {
                 $Where .= " AND d.DonationID = ".$ArrayParams["DonationID"];
             }
         }

         // <<< DonationReference field >>>
         if ((array_key_exists("DonationReference", $ArrayParams)) && (!empty($ArrayParams["DonationReference"])))
         {
             $Where .= " AND d.DonationReference LIKE \"".$ArrayParams["DonationReference"]."\"";
         }

         // <<< Donations for years >>>
         if ((array_key_exists("Year", $ArrayParams)) && (count($ArrayParams["Year"]) > 0))
         {
             $YearStartDate = $ArrayParams["Year"][0].'-01-01';
             $YearEndDate = $ArrayParams["Year"][0].'-12-31';

             $Where .= " AND d.DonationReceptionDate BETWEEN \"$YearStartDate\" AND \"$YearEndDate\"";
         }

         // <<< Donations of school years >>>
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND d.DonationReceptionDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         // <<< Donations between 2 given dates >>>
         if ((array_key_exists("StartDate", $ArrayParams)) && (count($ArrayParams["StartDate"]) == 2))
         {
             // [0] -> operator (>, <, >=...), [1] -> date
             $Where .= " AND d.DonationReceptionDate ".$ArrayParams["StartDate"][0]." \"".$ArrayParams["StartDate"][1]."\"";
         }

         if ((array_key_exists("EndDate", $ArrayParams)) && (count($ArrayParams["EndDate"]) == 2))
         {
             // [0] -> operator (>, <, >=...), [1] -> date
             $Where .= " AND d.DonationReceptionDate ".$ArrayParams["EndDate"][0]." \"".$ArrayParams["EndDate"][1]."\"";
         }

         // <<< DonationType field >>>
         if ((array_key_exists("DonationType", $ArrayParams)) && (count($ArrayParams["DonationType"]) > 0))
         {
             $Where .= " AND d.DonationType IN ".constructSQLINString($ArrayParams["DonationType"]);
         }

         // <<< DonationNature field >>>
         if ((array_key_exists("DonationNature", $ArrayParams)) && (count($ArrayParams["DonationNature"]) > 0))
         {
             $Where .= " AND d.DonationNature IN ".constructSQLINString($ArrayParams["DonationNature"]);
         }

         // <<< DonationEntity field >>>
         if ((array_key_exists("DonationEntity", $ArrayParams)) && (count($ArrayParams["DonationEntity"]) > 0))
         {
             $Where .= " AND d.DonationEntity IN ".constructSQLINString($ArrayParams["DonationEntity"]);
         }

         // <<< DonationLastname field >>>
         if ((array_key_exists("DonationLastname", $ArrayParams)) && (!empty($ArrayParams["DonationLastname"])))
         {
             $Where .= " AND d.DonationLastname LIKE \"".$ArrayParams["DonationLastname"]."\"";
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

         // <<< Family lastname fields >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             $Where .= " AND f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"";
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY DonationID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Get the total amount of donations for each nature of donation, filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-12-27
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the donations
 *
 * @return Integer              Total amount for each donation nature found, empty array otherwise
 */
 function getdbSearchDonationsTotalsByNature($DbConnection, $ArrayParams)
 {
     // SQL request to find donations
     $Select = "SELECT d.DonationNature, SUM(d.DonationValue) AS Total";
     $From = "FROM Towns t, Donations d LEFT JOIN Families f ON (d.FamilyID = f.FamilyID) LEFT JOIN Banks b ON (d.BankID = b.BankID)";
     $Where = "WHERE t.TownID = d.TownID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< DonationID field >>>
         if ((array_key_exists("DonationID", $ArrayParams)) && (!empty($ArrayParams["DonationID"])))
         {
             if (is_array($ArrayParams["DonationID"]))
             {
                 $Where .= " AND d.DonationID IN ".constructSQLINString($ArrayParams["DonationID"]);
             }
             else
             {
                 $Where .= " AND d.DonationID = ".$ArrayParams["DonationID"];
             }
         }

         // <<< DonationReference field >>>
         if ((array_key_exists("DonationReference", $ArrayParams)) && (!empty($ArrayParams["DonationReference"])))
         {
             $Where .= " AND d.DonationReference LIKE \"".$ArrayParams["DonationReference"]."\"";
         }

         // <<< Donations for years >>>
         if ((array_key_exists("Year", $ArrayParams)) && (count($ArrayParams["Year"]) > 0))
         {
             $YearStartDate = $ArrayParams["Year"][0].'-01-01';
             $YearEndDate = $ArrayParams["Year"][0].'-12-31';

             $Where .= " AND d.DonationReceptionDate BETWEEN \"$YearStartDate\" AND \"$YearEndDate\"";
         }

         // <<< Donations of school years >>>
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND d.DonationReceptionDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         // <<< Donations between 2 given dates >>>
         if ((array_key_exists("StartDate", $ArrayParams)) && (count($ArrayParams["StartDate"]) == 2))
         {
             // [0] -> operator (>, <, >=...), [1] -> date
             $Where .= " AND d.DonationReceptionDate ".$ArrayParams["StartDate"][0]." \"".$ArrayParams["StartDate"][1]."\"";
         }

         if ((array_key_exists("EndDate", $ArrayParams)) && (count($ArrayParams["EndDate"]) == 2))
         {
             // [0] -> operator (>, <, >=...), [1] -> date
             $Where .= " AND d.DonationReceptionDate ".$ArrayParams["EndDate"][0]." \"".$ArrayParams["EndDate"][1]."\"";
         }

         // <<< DonationType field >>>
         if ((array_key_exists("DonationType", $ArrayParams)) && (count($ArrayParams["DonationType"]) > 0))
         {
             $Where .= " AND d.DonationType IN ".constructSQLINString($ArrayParams["DonationType"]);
         }

         // <<< DonationNature field >>>
         if ((array_key_exists("DonationNature", $ArrayParams)) && (count($ArrayParams["DonationNature"]) > 0))
         {
             $Where .= " AND d.DonationNature IN ".constructSQLINString($ArrayParams["DonationNature"]);
         }

         // <<< DonationEntity field >>>
         if ((array_key_exists("DonationEntity", $ArrayParams)) && (count($ArrayParams["DonationEntity"]) > 0))
         {
             $Where .= " AND d.DonationEntity IN ".constructSQLINString($ArrayParams["DonationEntity"]);
         }

         // <<< DonationLastname field >>>
         if ((array_key_exists("DonationLastname", $ArrayParams)) && (!empty($ArrayParams["DonationLastname"])))
         {
             $Where .= " AND d.DonationLastname LIKE \"".$ArrayParams["DonationLastname"]."\"";
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

         // <<< Family lastname fields >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             $Where .= " AND f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"";
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY DonationNature $Having ORDER BY DonationNature");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayResult = array();

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayResult[$Record['DonationNature']] = $Record['Total'];
             }

             // Return result
             return $ArrayResult;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get donators who make a donation in previous years but not for a given year, filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-22
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the donations
 * @param $OrderBy                  String                 Criteria used to sort the donations. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of donations per page to return [1..n]
 *
 * @return Array of String                                 List of donators filtered, an empty array otherwise
 */
 function dbGetDonators($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find donators
     $Select = "SELECT d.DonationID, d.DonationReference, d.DonationEntity, d.DonationLastname, d.DonationFirstname,
                d.DonationMainEmail, d.DonationSecondEmail, d.DonationType, d.DonationNature, d.DonationReceptionDate,
                d.DonationValue, f.FamilyID, f.FamilyLastname";
     $From = "FROM Towns t, Donations d LEFT JOIN Families f ON (d.FamilyID = f.FamilyID) LEFT JOIN Banks b ON (d.BankID = b.BankID)";
     $Where = "WHERE t.TownID = d.TownID";
     $Having = "";

     $FromDonators = '';
     if (count($ArrayParams) >= 0)
     {
         // <<< Donators for a year >>>
         if ((array_key_exists("Year", $ArrayParams)) && (count($ArrayParams["Year"]) > 0))
         {
             $YearStartDate = $ArrayParams["Year"][0].'-01-01';
             $YearEndDate = $ArrayParams["Year"][0].'-12-31';

             $FromDonators = ", (SELECT tmp.DonationID AS DonatID, tmp.FullName, tmp2.DonationID
                               FROM (SELECT dtmp.DonationID, CONCAT( dtmp.DonationLastname, ' ', dtmp.DonationFirstname ) AS FullName
                                     FROM Donations dtmp WHERE dtmp.DonationReceptionDate < '$YearStartDate') AS tmp
                                     LEFT JOIN (SELECT dtmp2.DonationID, CONCAT( dtmp2.DonationLastname, ' ', dtmp2.DonationFirstname ) AS FullName
                                                FROM Donations dtmp2 WHERE dtmp2.DonationReceptionDate BETWEEN '$YearStartDate'
                                                AND '$YearEndDate') AS tmp2 ON (tmp2.FullName = tmp.FullName
                                                AND tmp2.DonationID <> tmp.DonationID)
                                     HAVING tmp2.DonationID IS NULL
                                    ) AS Dtors";

             $Where .= " AND d.DonationID = Dtors.DonatID";
         }

         // <<< Donations of school years >>>
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND d.DonationReceptionDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         // <<< Donations between 2 given dates >>>
         if ((array_key_exists("StartDate", $ArrayParams)) && (count($ArrayParams["StartDate"]) == 2))
         {
             // [0] -> operator (>, <, >=...), [1] -> date
             $Where .= " AND d.DonationReceptionDate ".$ArrayParams["StartDate"][0]." \"".$ArrayParams["StartDate"][1]."\"";
         }

         if ((array_key_exists("EndDate", $ArrayParams)) && (count($ArrayParams["EndDate"]) == 2))
         {
             // [0] -> operator (>, <, >=...), [1] -> date
             $Where .= " AND d.DonationReceptionDate ".$ArrayParams["EndDate"][0]." \"".$ArrayParams["EndDate"][1]."\"";
         }

         // <<< DonationType field >>>
         if ((array_key_exists("DonationType", $ArrayParams)) && (count($ArrayParams["DonationType"]) > 0))
         {
             $Where .= " AND d.DonationType IN ".constructSQLINString($ArrayParams["DonationType"]);
         }

         // <<< DonationNature field >>>
         if ((array_key_exists("DonationNature", $ArrayParams)) && (count($ArrayParams["DonationNature"]) > 0))
         {
             $Where .= " AND d.DonationNature IN ".constructSQLINString($ArrayParams["DonationNature"]);
         }

         // <<< DonationEntity field >>>
         if ((array_key_exists("DonationEntity", $ArrayParams)) && (count($ArrayParams["DonationEntity"]) > 0))
         {
             $Where .= " AND d.DonationEntity IN ".constructSQLINString($ArrayParams["DonationEntity"]);
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
     }

     // We take into account the page and the number of donations per page
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
     $DbResult = $DbConnection->query("$Select $From $FromDonators $Where GROUP BY DonationID $Having $StrOrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "DonationID" => array(),
                                   "DonationReference" => array(),
                                   "DonationEntity" => array(),
                                   "DonationLastname" => array(),
                                   "DonationFirstname" => array(),
                                   "DonationMainEmail" => array(),
                                   "DonationSecondEmail" => array(),
                                   "DonationType" => array(),
                                   "DonationNature" => array(),
                                   "DonationReceptionDate" => array(),
                                   "DonationValue" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["DonationID"][] = $Record["DonationID"];
                 $ArrayRecords["DonationReference"][] = $Record["DonationReference"];
                 $ArrayRecords["DonationEntity"][] = $Record["DonationEntity"];
                 $ArrayRecords["DonationLastname"][] = $Record["DonationLastname"];
                 $ArrayRecords["DonationFirstname"][] = $Record["DonationFirstname"];
                 $ArrayRecords["DonationMainEmail"][] = $Record["DonationMainEmail"];
                 $ArrayRecords["DonationSecondEmail"][] = $Record["DonationSecondEmail"];
                 $ArrayRecords["DonationType"][] = $Record["DonationType"];
                 $ArrayRecords["DonationNature"][] = $Record["DonationNature"];
                 $ArrayRecords["DonationReceptionDate"][] = $Record["DonationReceptionDate"];
                 $ArrayRecords["DonationValue"][] = $Record["DonationValue"];
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Give the older date of the donations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-08
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the older date of the Donations table,
 *                              empty string otherwise
 */
 function getDonationMinDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MIN(DonationReceptionDate) As minDate FROM Donations");
     if (!DB::isError($DbResult))
     {
         $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
         if (!empty($Record["minDate"]))
         {
             return $Record["minDate"];
         }
     }

     // ERROR
     return '';
 }
?>