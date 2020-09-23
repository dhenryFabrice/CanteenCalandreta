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
 * Common module : library of database functions used for the configuration parameters table
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2016-11-02
 */


/**
 * Check if a config parameter exists in the ConfigParameters table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-02
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $ConfigParameterID    Integer      ID of the config parameter searched [1..n]
 *
 * @return Boolean              TRUE if the config parameter exists, FALSE otherwise
 */
 function isExistingConfigParameter($DbConnection, $ConfigParameterID)
 {
     $DbResult = $DbConnection->query("SELECT ConfigParameterID FROM ConfigParameters
                                       WHERE ConfigParameterID = $ConfigParameterID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The config parameter exists
             return TRUE;
         }
     }

     // The config parameter doesn't exist
     return FALSE;
 }


/**
 * Give the ID of a config parameter thanks to its reference
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-02
 *
 * @param $DbConnection           DB object    Object of the opened database connection
 * @param $ConfigParameterName    String       Name of the config parameter searched
 *
 * @return Integer                ID of the config parameter, 0 otherwise
 */
 function getConfigParameterID($DbConnection, $DonationRef)
 {
     $DbResult = $DbConnection->query("SELECT ConfigParameterID FROM ConfigParameters
                                       WHERE ConfigParameterName = \"$ConfigParameterName\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["ConfigParameterID"];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the name of a config parameter thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-02
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $DonationID           Integer      ID of the config parameter searched
 *
 * @return String               Name of the config parameter, empty string otherwise
 */
 function getConfigParameterName($DbConnection, $DonationID)
 {
     $DbResult = $DbConnection->query("SELECT ConfigParameterName FROM ConfigParameters
                                       WHERE ConfigParameterID = $ConfigParameterID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["ConfigParameterName"];
         }
     }

     // ERROR
     return "";
 }


/**
 * Add a config parameter in the ConfigParameters table
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2019-08-30 : use escapeSQLString() function to escape " in the value
 *
 * @since 2016-11-02
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $ConfigParameterName           String       Name of the config parameter
 * @param $ConfigParameterType           String       Type of the config parameter
 * @param $ConfigParameterValue          String       Value of the config parameter
 *
 * @return Integer                       The primary key of the config parameter [1..n], 0 otherwise
 */
 function dbAddConfigParameter($DbConnection, $ConfigParameterName, $ConfigParameterType, $ConfigParameterValue = '')
 {
     if ((!empty($ConfigParameterName)) && (!empty($ConfigParameterType)))
     {
         // Check if the config parameter is a new config parameter
         $DbResult = $DbConnection->query("SELECT ConfigParameterID FROM ConfigParameters
                                           WHERE ConfigParameterName = \"$ConfigParameterName\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // It's a new config parameter
                 $id = getNewPrimaryKey($DbConnection, "ConfigParameters", "ConfigParameterID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO ConfigParameters SET ConfigParameterID = $id,
                                                       ConfigParameterName = \"$ConfigParameterName\",
                                                       ConfigParameterType = \"$ConfigParameterType\",
                                                       ConfigParameterValue = \"".escapeSQLString($ConfigParameterValue)."\"");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The config parameter already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['ConfigParameterID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing config parameter in the ConfigParameters table
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2019-08-30 : use escapeSQLString() function to escape " in the value
 *
 * @since 2016-11-02
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $ConfigParameterID             Integer      ID of the config parameter to update [1..n]
 * @param $ConfigParameterName           String       Name of the config parameter
 * @param $ConfigParameterType           String       Type of the config parameter
 * @param $ConfigParameterValue          String       Value of the config parameter
 *
 * @return Integer                       The primary key of the config parameter [1..n], 0 otherwise
 */
 function dbUpdateConfigParameter($DbConnection, $ConfigParameterID, $ConfigParameterName = NULL, $ConfigParameterType = NULL, $ConfigParameterValue = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($ConfigParameterID < 1) || (!isInteger($ConfigParameterID)))
     {
         // ERROR
         return 0;
     }

     // Check if the ConfigParameterName is valide
     if (!is_null($ConfigParameterName))
     {
         if (empty($ConfigParameterName))
         {
             return 0;
         }
         else
         {
             // The ConfigParameterName field will be updated
             $ArrayParamsUpdate[] = "ConfigParameterName = \"$ConfigParameterName\"";
         }
     }
     else
     {
         // We get the name
         $ConfigParameterName = getTableFieldValue($DbConnection, 'ConfigParameters', $ConfigParameterID, 'ConfigParameterName');
     }

     // Check if the ConfigParameterType is valide
     if (!is_null($ConfigParameterType))
     {
         if (empty($ConfigParameterType))
         {
             return 0;
         }
         else
         {
             // The ConfigParameterType field will be updated
             $ArrayParamsUpdate[] = "ConfigParameterType = \"$ConfigParameterType\"";
         }
     }

     // Check if the ConfigParameterValue is valide
     if (!is_null($ConfigParameterValue))
     {
         // The ConfigParameterValue field will be updated
         $ArrayParamsUpdate[] = "ConfigParameterValue = \"".escapeSQLString($ConfigParameterValue)."\"";
     }

     // Here, the parameters are correct, we check if the config parameter exists
     if (isExistingConfigParameter($DbConnection, $ConfigParameterID))
     {
         // We check if the name is unique
         $DbResult = $DbConnection->query("SELECT ConfigParameterID FROM ConfigParameters
                                           WHERE ConfigParameterName = \"$ConfigParameterName\"
                                           AND ConfigParameterID <> $ConfigParameterID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The config parameter exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE ConfigParameters SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE ConfigParameterID = $ConfigParameterID");
                     if (!DB::isError($DbResult))
                     {
                         // Config parameter updated
                         return $ConfigParameterID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $ConfigParameterID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the whole fields values of a config parameter, thanks to his name
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-02
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $ConfigParameterName       String       Name of the config parameter searched
 *
 * @return Mixed array               All fields values of a config parameter if he exists,
 *                                   an empty array otherwise
 */
 function getConfigParameterInfos($DbConnection, $ConfigParameterName)
 {
     $DbResult = $DbConnection->query("SELECT ConfigParameterID, ConfigParameterName, ConfigParameterType, ConfigParameterValue
                                       FROM ConfigParameters WHERE ConfigParameterName = \"$ConfigParameterName\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             return $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
         }
     }

     // ERROR
     return array();
 }


/**
 * Delete a config parameter, thanks to its ID.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-04
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $ConfigParameterID         Integer      ID of the config parameter to delete [1..n]
 *
 * @return Boolean                   TRUE if the config parameter is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeleteConfigParameter($DbConnection, $ConfigParameterID)
 {
     // The parameters are correct?
     if ($ConfigParameterID > 0)
     {
         // Delete the config parameter in the table
         $DbResult = $DbConnection->query("DELETE FROM ConfigParameters WHERE ConfigParameterID = $ConfigParameterID");
         if (!DB::isError($DbResult))
         {
             // Config parameter deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Get config parameters filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-02
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the config parameters
 * @param $OrderBy                  String                 Criteria used to sort the config parameters. If < 0, DESC is used,
 *                                                         otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of config parameters per page to return [1..n]
 *
 * @return Array of String                                 List of config parameters filtered, an empty array otherwise
 */
 function dbSearchConfigParameters($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find config parameters
     $Select = "SELECT cp.ConfigParameterID, cp.ConfigParameterName, cp.ConfigParameterType, cp.ConfigParameterValue";
     $From = "FROM ConfigParameters cp";
     $Where = "WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< ConfigParameterID field >>>
         if ((array_key_exists("ConfigParameterID", $ArrayParams)) && (!empty($ArrayParams["ConfigParameterID"])))
         {
             if (is_array($ArrayParams["ConfigParameterID"]))
             {
                 $Where .= " AND cp.ConfigParameterID IN ".constructSQLINString($ArrayParams["ConfigParameterID"]);
             }
             else
             {
                 $Where .= " AND cp.ConfigParameterID = ".$ArrayParams["ConfigParameterID"];
             }
         }

         // <<< ConfigParameterName field >>>
         if ((array_key_exists("ConfigParameterName", $ArrayParams)) && (!empty($ArrayParams["ConfigParameterName"])))
         {
             $Where .= " AND cp.ConfigParameterName LIKE \"".$ArrayParams["ConfigParameterName"]."\"";
         }

         // <<< ConfigParameterType field >>>
         if ((array_key_exists("ConfigParameterType", $ArrayParams)) && (count($ArrayParams["ConfigParameterType"]) > 0))
         {
             $Where .= " AND cp.ConfigParameterType IN ".constructSQLINString($ArrayParams["ConfigParameterType"]);
         }

         // <<< ConfigParameterValue field >>>
         if ((array_key_exists("ConfigParameterValue", $ArrayParams)) && (!empty($ArrayParams["ConfigParameterValue"])))
         {
             $Where .= " AND cp.ConfigParameterValue LIKE \"".$ArrayParams["ConfigParameterValue"]."\"";
         }
     }

     // We take into account the page and the number of config parameters per page
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY ConfigParameterID $Having $StrOrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "ConfigParameterID" => array(),
                                   "ConfigParameterName" => array(),
                                   "ConfigParameterType" => array(),
                                   "ConfigParameterValue" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["ConfigParameterID"][] = $Record["ConfigParameterID"];
                 $ArrayRecords["ConfigParameterName"][] = $Record["ConfigParameterName"];
                 $ArrayRecords["ConfigParameterType"][] = $Record["ConfigParameterType"];
                 $ArrayRecords["ConfigParameterValue"][] = $Record["ConfigParameterValue"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of config parameters filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-02
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the config parameters
 *
 * @return Integer              Number of the config parameters found, 0 otherwise
 */
 function getNbdbSearchConfigParameters($DbConnection, $ArrayParams)
 {
     // SQL request to find config parameters
     $Select = "SELECT cp.ConfigParameterID";
     $From = "FROM ConfigParameters cp";
     $Where = "WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< ConfigParameterID field >>>
         if ((array_key_exists("ConfigParameterID", $ArrayParams)) && (!empty($ArrayParams["ConfigParameterID"])))
         {
             if (is_array($ArrayParams["ConfigParameterID"]))
             {
                 $Where .= " AND cp.ConfigParameterID IN ".constructSQLINString($ArrayParams["ConfigParameterID"]);
             }
             else
             {
                 $Where .= " AND cp.ConfigParameterID = ".$ArrayParams["ConfigParameterID"];
             }
         }

         // <<< ConfigParameterName field >>>
         if ((array_key_exists("ConfigParameterName", $ArrayParams)) && (!empty($ArrayParams["ConfigParameterName"])))
         {
             $Where .= " AND cp.ConfigParameterName LIKE \"".$ArrayParams["ConfigParameterName"]."\"";
         }

         // <<< ConfigParameterType field >>>
         if ((array_key_exists("ConfigParameterType", $ArrayParams)) && (count($ArrayParams["ConfigParameterType"]) > 0))
         {
             $Where .= " AND cp.ConfigParameterType IN ".constructSQLINString($ArrayParams["ConfigParameterType"]);
         }

         // <<< ConfigParameterValue field >>>
         if ((array_key_exists("ConfigParameterValue", $ArrayParams)) && (!empty($ArrayParams["ConfigParameterValue"])))
         {
             $Where .= " AND cp.ConfigParameterValue LIKE \"".$ArrayParams["ConfigParameterValue"]."\"";
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY ConfigParameterID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }
?>