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
 * Common module : library of database functions used for the Banks table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-28
 */


/**
 * Check if a bank exists in the Banks table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-28
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $BankID               Integer      ID of the bank searched [1..n]
 *
 * @return Boolean              TRUE if the bank exists, FALSE otherwise
 */
 function isExistingBank($DbConnection, $BankID)
 {
     $DbResult = $DbConnection->query("SELECT BankID FROM Banks WHERE BankID = $BankID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The bank exists
             return TRUE;
         }
     }

     // The bank doesn't exist
     return FALSE;
 }


/**
 * Give the ID of a bank thanks to its acronym
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-04-17
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $BankAcronym          String       Acronym of the searched bank
 *
 * @return Integer              ID of the bank, empty string otherwise
 */
 function getBankID($DbConnection, $BankAcronym)
 {
     if (!empty($BankAcronym))
     {
         $DbResult = $DbConnection->query("SELECT BankID FROM Banks WHERE BankAcronym = \"$BankAcronym\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() != 0)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record["BankID"];
             }
         }
     }

     // ERROR
     return "";
 }


/**
 * Add a bank in the Banks table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-28
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $BankName                      String       Name of the bank
 * @param $BankAcronym                   String       Acronym of the bank's name
 *
 * @return Integer                       The primary key of the bank [1..n], 0 otherwise
 */
 function dbAddBank($DbConnection, $BankName, $BankAcronym = '')
 {
     if (!empty($BankName))
     {
         // Check if the bank is a new bank
         $DbResult = $DbConnection->query("SELECT BankID FROM Banks WHERE BankName = \"$BankName\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // It's a new bank
                 $id = getNewPrimaryKey($DbConnection, "Banks", "BankID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Banks SET BankID = $id, BankName = \"$BankName\",
                                                      BankAcronym = \"$BankAcronym\"");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The bank already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['BankID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing bank in the Banks table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-26
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $BankID                        Integer      ID of the bank to update [1..n]
 * @param $BankName                      String       Name of the bank
 * @param $BankAcronym                   String       Acronym of the bank's name
 *
 * @return Integer                       The primary key of the bank [1..n], 0 otherwise
 */
 function dbUpdateBank($DbConnection, $BankID, $BankName, $BankAcronym = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($BankID < 1) || (!isInteger($BankID)))
     {
         // ERROR
         return 0;
     }

     // Check if the BankName is valide
     if (!is_null($BankName))
     {
         if (empty($BankName))
         {
             return 0;
         }
         else
         {
             // The BankName field will be updated
             $ArrayParamsUpdate[] = "BankName = \"$BankName\"";
         }
     }

     if (!is_null($BankAcronym))
     {
         if (empty($BankAcronym))
         {
             // The BankAcronym field will be updated
             $ArrayParamsUpdate[] = "BankAcronym = NULL";
         }
         else
         {
             // The BankAcronym field will be updated
             $ArrayParamsUpdate[] = "BankAcronym = \"$BankAcronym\"";
         }
     }

     // Here, the parameters are correct, we check if the bank exists
     if (isExistingBank($DbConnection, $BankID))
     {
         // We check if the bank name is unique
         $DbResult = $DbConnection->query("SELECT BankID FROM Banks WHERE BankName = \"$BankName\" AND BankID <> $BankID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The bank exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Banks SET ".implode(", ", $ArrayParamsUpdate)." WHERE BankID = $BankID");
                     if (!DB::isError($DbResult))
                     {
                         // Bank updated
                         return $BankID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $BankID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }
?>