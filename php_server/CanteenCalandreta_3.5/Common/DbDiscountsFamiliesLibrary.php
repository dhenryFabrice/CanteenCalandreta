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
 * Common module : library of database functions used for the DiscountsFamilies table
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2017-10-05
 */


/**
 * Check if a discount exists in the DiscountsFamilies table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-10-05
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $DiscountFamilyID     Integer      ID of the discount searched [1..n]
 *
 * @return Boolean              TRUE if the discount exists, FALSE otherwise
 */
 function isExistingDiscountFamily($DbConnection, $DiscountFamilyID)
 {
     $DbResult = $DbConnection->query("SELECT DiscountFamilyID FROM DiscountsFamilies WHERE DiscountFamilyID = $DiscountFamilyID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The discount exists
             return TRUE;
         }
     }

     // The discount doesn't exist
     return FALSE;
 }


/**
 * Add a discount in the DiscountsFamilies table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-10-05
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $DiscountFamilyDate            Datetime     Creation date of the discount (yyyy-mm-dd hh:mm:ss)
 * @param $FamilyID                      Integer      ID of the family linked to the discount [1..n]
 * @param $DiscountFamilyAmount          Float        Amount of the discount (<> 0.00)
 * @param $DiscountFamilyType            Integer      Type of discount [0..n]
 * @param $DiscountFamilyReasonType      Integer      Type of reason of the discount [0..n]
 * @param $DiscountFamilyReason          String       Reason of the discount
 *
 * @return Integer                       The primary key of the discount's family [1..n], 0 otherwise
 */
 function dbAddDiscountFamily($DbConnection, $DiscountFamilyDate, $FamilyID, $DiscountFamilyAmount, $DiscountFamilyType = 0, $DiscountFamilyReasonType = 0, $DiscountFamilyReason = NULL)
 {
     if ((!empty($DiscountFamilyDate)) && ($FamilyID > 0) && ($DiscountFamilyType >= 0) && ($DiscountFamilyReasonType >= 0)
         && (abs($DiscountFamilyAmount) > 0))
     {
         // Check if the DiscountFamilyDate is valide
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $DiscountFamilyDate) == 0)
         {
             return 0;
         }
         else
         {
             $DiscountFamilyDate = ", DiscountFamilyDate = \"$DiscountFamilyDate\"";
         }

         if (empty($DiscountFamilyReason))
         {
             $DiscountFamilyReason = ", DiscountFamilyReason = NULL";
         }
         else
         {
             $DiscountFamilyReason = ", DiscountFamilyReason = \"$DiscountFamilyReason\"";
         }

         // It's a new discount of family
         $id = getNewPrimaryKey($DbConnection, "DiscountsFamilies", "DiscountFamilyID");
         if ($id != 0)
         {
             $DbResult = $DbConnection->query("INSERT INTO DiscountsFamilies SET DiscountFamilyID = $id, FamilyID = $FamilyID,
                                               DiscountFamilyType = $DiscountFamilyType, DiscountFamilyReasonType = $DiscountFamilyReasonType,
                                               DiscountFamilyAmount = $DiscountFamilyAmount $DiscountFamilyDate $DiscountFamilyReason");

             if (!DB::isError($DbResult))
             {
                 return $id;
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing discount of family in the DiscountsFamilies table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-10-05
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $DiscountFamilyID              Integer      ID of the discount of the family to update [1..n]
 * @param $DiscountFamilyDate            Datetime     Creation date of the discount (yyyy-mm-dd hh:mm:ss)
 * @param $FamilyID                      Integer      ID of the family linked to the discount [1..n]
 * @param $DiscountFamilyAmount          Float        Amount of the discount (<> 0.00)
 * @param $DiscountFamilyType            Integer      Type of discount [0..n]
 * @param $DiscountFamilyReasonType      Integer      Type of reason of the discount [0..n]
 * @param $DiscountFamilyReason          String       Reason of the discount
 *
 * @return Integer                       The primary key of the discount's family [1..n], 0 otherwise
 */
 function dbUpdateDiscountFamily($DbConnection, $DiscountFamilyID, $DiscountFamilyDate, $FamilyID, $DiscountFamilyAmount, $DiscountFamilyType = NULL, $DiscountFamilyReasonType = NULL, $DiscountFamilyReason = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($DiscountFamilyID < 1) || (!isInteger($DiscountFamilyID)))
     {
         // ERROR
         return 0;
     }

     // Check if the DiscountFamilyDate is valide
     if (!is_null($DiscountFamilyDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $DiscountFamilyDate) == 0)
         {
             return 0;
         }
         else
         {
             // The DiscountFamilyDate field will be updated
             $ArrayParamsUpdate[] = "DiscountFamilyDate = \"$DiscountFamilyDate\"";
         }
     }

     if (!is_null($FamilyID))
     {
         if (($FamilyID < 1) || (!isInteger($FamilyID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "FamilyID = $FamilyID";
         }
     }

     if (!is_null($DiscountFamilyAmount))
     {
         if (abs($DiscountFamilyAmount) > 0)
         {
             // The DiscountFamilyAmount field will be updated
             $ArrayParamsUpdate[] = "DiscountFamilyAmount = $DiscountFamilyAmount";
         }
         else
         {
             // ERROR
             return 0;
         }
     }

     if (!is_Null($DiscountFamilyType))
     {
         if (($DiscountFamilyType < 0) || (!isInteger($DiscountFamilyType)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The DiscountFamilyType field will be updated
             $ArrayParamsUpdate[] = "DiscountFamilyType = $DiscountFamilyType";
         }
     }

     if (!is_Null($DiscountFamilyReasonType))
     {
         if (($DiscountFamilyReasonType < 0) || (!isInteger($DiscountFamilyReasonType)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The DiscountFamilyReasonType field will be updated
             $ArrayParamsUpdate[] = "DiscountFamilyReasonType = $DiscountFamilyReasonType";
         }
     }

     if (!is_null($DiscountFamilyReason))
     {
         if (empty($DiscountFamilyReason))
         {
             // The DiscountFamilyReason field will be updated
             $ArrayParamsUpdate[] = "DiscountFamilyReason = NULL";
         }
         else
         {
             // The DiscountFamilyReason field will be updated
             $ArrayParamsUpdate[] = "DiscountFamilyReason = \"$DiscountFamilyReason\"";
         }
     }

     // Here, the parameters are correct, we check if the discount's family exists
     if (isExistingDiscountFamily($DbConnection, $DiscountFamilyID))
     {
         // We can update if there is at least 1 parameter
         if (count($ArrayParamsUpdate) > 0)
         {
             $DbResult = $DbConnection->query("UPDATE DiscountsFamilies SET ".implode(", ", $ArrayParamsUpdate)
                                              ." WHERE DiscountFamilyID = $DiscountFamilyID");
             if (!DB::isError($DbResult))
             {
                 // Discount of family updated
                 return $DiscountFamilyID;
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the discounts of a family, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-10-05
 *
 * @param $DbConnection              DB object      Object of the opened database connection
 * @param $FamilyID                  Integer        ID of the family for which we want the discounts [1..n]
 * @param $ArrayParams               Mixed array    Contains the criterion used to filter the discounts of the given family
 * @param $OrderBy                   String         To order the disocunts
 *
 * @return Mixed array               All fields values of the disocunts of the family if it exists,
 *                                   an empty array otherwise
 */
 function getFamilyDiscounts($DbConnection, $FamilyID, $ArrayParams = array(), $OrderBy = 'DiscountFamilyDate')
 {
     if ($FamilyID > 0)
     {
         if (empty($OrderBy))
         {
             $OrderBy = 'DiscountFamilyDate';
         }

         $Conditions = '';
         if (!empty($ArrayParams))
         {
             if ((isset($ArrayParams['DiscountFamilyType'])) && (!empty($ArrayParams['DiscountFamilyType'])))
             {
                 $Conditions .= " AND df.DiscountFamilyType IN ".constructSQLINString($ArrayParams["DiscountFamilyType"]);
             }

             if ((isset($ArrayParams['DiscountFamilyReasonType'])) && (!empty($ArrayParams['DiscountFamilyReasonType'])))
             {
                 $Conditions .= " AND df.DiscountFamilyReasonType IN ".constructSQLINString($ArrayParams["DiscountFamilyReasonType"]);
             }

             if ((isset($ArrayParams['DiscountFamilyDate'])) && (count($ArrayParams['DiscountFamilyDate']) == 2))
             {
                 // $ArrayParams['DiscountFamilyDate'][0] contains the operator (>, >=, =...) and
                 // $ArrayParams['DiscountFamilyDate'][1] contains the date in english format (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
                 $Conditions .= " AND df.DiscountFamilyDate ".$ArrayParams['DiscountFamilyDate'][0]." \"".$ArrayParams['DiscountFamilyDate'][1]."\"";
             }
         }

         // We get the discounts of the family
         $DbResult = $DbConnection->query("SELECT df.DiscountFamilyID, df.DiscountFamilyDate, df.DiscountFamilyType,
                                           df.DiscountFamilyReasonType, df.DiscountFamilyReason, df.DiscountFamilyAmount
                                           FROM DiscountsFamilies df
                                           WHERE df.FamilyID = $FamilyID $Conditions ORDER BY $OrderBy");

         if (!DB::isError($DbResult))
         {
             // Creation of the result array
             $ArrayRecords = array(
                                  "DiscountFamilyID" => array(),
                                  "DiscountFamilyDate" => array(),
                                  "DiscountFamilyType" => array(),
                                  "DiscountFamilyReasonType" => array(),
                                  "DiscountFamilyReason" => array(),
                                  "DiscountFamilyAmount" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["DiscountFamilyID"][] = $Record["DiscountFamilyID"];
                 $ArrayRecords["DiscountFamilyDate"][] = $Record["DiscountFamilyDate"];
                 $ArrayRecords["DiscountFamilyType"][] = $Record["DiscountFamilyType"];
                 $ArrayRecords["DiscountFamilyReasonType"][] = $Record["DiscountFamilyReasonType"];
                 $ArrayRecords["DiscountFamilyReason"][] = $Record["DiscountFamilyReason"];
                 $ArrayRecords["DiscountFamilyAmount"][] = $Record["DiscountFamilyAmount"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Delete a discount of a family, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-10-05
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $DiscountFamilyID          Integer      ID of the discount's family to delete [1..n]
 *
 * @return Boolean                   TRUE if the discount is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeleteDiscountFamily($DbConnection, $DiscountFamilyID)
 {
     // The parameters are correct?
     if (($DiscountFamilyID > 0) && (isExistingDiscountFamily($DbConnection, $DiscountFamilyID)))
     {
         // Get the amount of the discount/increase to update the balance of the family
         $RecordDiscount = getTableRecordInfos($DbConnection, 'DiscountsFamilies', $DiscountFamilyID);

         // Delete the discount/increase in the table
         $DbResult = $DbConnection->query("DELETE FROM DiscountsFamilies WHERE DiscountFamilyID = $DiscountFamilyID");
         if (!DB::isError($DbResult))
         {
             // Update the balance : we remove the amount of the discount/increase
             updateFamilyBalance($DbConnection, $RecordDiscount['FamilyID'], $RecordDiscount['DiscountFamilyAmount']);

             unset($RecordDiscount);

             // Discount/increase deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }
?>