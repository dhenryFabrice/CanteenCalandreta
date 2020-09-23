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
 * Common module : library of database functions used for the Towns table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-04-06
 */


/**
 * Check if a town exists in the Towns table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-04-06
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $TownID               Integer      ID of the town searched [1..n]
 *
 * @return Boolean              TRUE if the town exists, FALSE otherwise
 */
 function isExistingTown($DbConnection, $TownID)
 {
     $DbResult = $DbConnection->query("SELECT TownID FROM Towns WHERE TownID = $TownID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The town exists
             return TRUE;
         }
     }

     // The town doesn't exist
     return FALSE;
 }


/**
 * Add a town in the Towns table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-04-04
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $TownName                      String       Name of the town
 * @param $TownCode                      String       Zip code of the town
 *
 * @return Integer                       The primary key of the town [1..n], 0 otherwise
 */
 function dbAddTown($DbConnection, $TownName, $TownCode)
 {
     if ((!empty($TownName)) && (!empty($TownCode)))
     {
         // Check if the town is a new town
         $DbResult = $DbConnection->query("SELECT TownID FROM Towns WHERE TownName = \"$TownName\" AND TownCode = \"$TownCode\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // It's a new town
                 $id = getNewPrimaryKey($DbConnection, "Towns", "TownID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Towns SET TownID = $id, TownName = \"$TownName\",
                                                      TownCode = \"$TownCode\"");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The town already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['TownID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing town in the Towns table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-04-06
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $TownID                        Integer      ID of the town to update [1..n]
 * @param $TownName                      String       Name of the town
 * @param $TownCode                      String       Zip code of the town
 *
 * @return Integer                       The primary key of the town [1..n], 0 otherwise
 */
 function dbUpdateTown($DbConnection, $TownID, $TownName = NULL, $TownCode = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($TownID < 1) || (!isInteger($TownID)))
     {
         // ERROR
         return 0;
     }

     // Check if the TownName is valide
     if (!is_null($TownName))
     {
         if (empty($TownName))
         {
             return 0;
         }
         else
         {
             // The TownName field will be updated
             $ArrayParamsUpdate[] = "TownName = \"$TownName\"";
         }
     }

     // Check if the TownCode is valide
     if (!is_null($TownCode))
     {
         if (empty($TownCode))
         {
             return 0;
         }
         else
         {
             // The TownCode field will be updated
             $ArrayParamsUpdate[] = "TownCode = \"$TownCode\"";
         }
     }

     // Here, the parameters are correct, we check if the town exists
     if (isExistingTown($DbConnection, $TownID))
     {
         // We check if the town name is unique
         $DbResult = $DbConnection->query("SELECT TownID FROM Towns WHERE TownName = \"$TownName\" AND TownCode = \"$TownCode\"
                                          AND  TownID <> $TownID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The town exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Towns SET ".implode(", ", $ArrayParamsUpdate)." WHERE TownID = $TownID");
                     if (!DB::isError($DbResult))
                     {
                         // Town updated
                         return $TownID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $TownID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }
?>