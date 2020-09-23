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
 * @version 3.1
 * @since 2012-01-16
 */


/**
 * Add a new supporter in the SupportMembers table without login and password
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2012-01-16 : taken into account the SupportMemberActivated field
 *     - 2015-10-06 : taken into account the FamilyID field
 *
 * @since 2012-01-16
 *
 * @param $DbConnection              DB object  Object of the opened database connection
 * @param $Lastname                  String     Lastname of the supporter
 * @param $Firstname                 String     Firstname of the supporter
 * @param $Email                     String     E-mail address of the supporter
 * @param $StateID                   Integer    ID of the state in which the supporter is
 * @param $Phone                     String     Phone number of the supporter
 * @param $Activated                 Integer    1 to active the supporter profil, 0 otherwise
 * @param $FamilyID                  Integer    ID of the family associated to the support member account [1..n], NULL otherwise
 *
 * @return Integer                   The primary key of the supporter added, 0 otherwise
 */
 function dbAddSupportMember($DbConnection, $Lastname, $Firstname, $Email, $StateID, $Phone = "", $Activated = 1, $FamilyID = NULL)
 {
     // The parameters are correct?
     if (($Lastname != "") && ($Firstname != "") && ($Email != "") && ($StateID > 0) && ($Activated >= 0) && ($Activated <= 1))
     {
         // The supporter is a new supporter?
         $DbResult = $DbConnection->query("SELECT SupportMemberID FROM SupportMembers WHERE SupportMemberLastname = \"$Lastname\"
                                          AND SupportMemberFirstname = \"$Firstname\" AND SupportMemberEmail = \"$Email\"
                                          AND SupportMemberStateID = $StateID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 if (empty($FamilyID))
                 {
                     $BankFamilyIDID = ", FamilyID = NULL";
                 }
                 elseif ($FamilyID <= 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $FamilyID = ", FamilyID = $FamilyID";
                 }

                 // New supporter : it's added
                 // For the auto-incrementation functionality
                 $id = getNewPrimaryKey($DbConnection, "SupportMembers", "SupportMemberID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO SupportMembers SET SupportMemberID = $id,
                                                      SupportMemberLastname = \"$Lastname\", SupportMemberFirstname = \"$Firstname\",
                                                      SupportMemberPhone = \"$Phone\", SupportMemberEmail = \"$Email\",
                                                      SupportMemberActivated = $Activated, SupportMemberStateID = $StateID $FamilyID");
                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // Old supporter : we return its ID
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record["SupportMemberID"];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Check if a supporter exists in the SupportMembers table, thanks to its ID
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-05-04
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $SupportMemberID      Integer      ID of the supporter searched
 *
 * @return Boolean                           TRUE if the supporter ID exists, FALSE otherwise
 */
 function isExistingSupportMember($DbConnection, $SupportMemberID)
 {
     $DbResult = $DbConnection->query("SELECT SupportMemberID FROM SupportMembers WHERE SupportMemberID = $SupportMemberID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The supporter exists
             return TRUE;
         }
     }

     // The supporter doesn't exist
     return FALSE;
 }


/**
 * Check if a supporter exists in the SupportMembers table, thanks to
 * his web service key
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-02-08
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $WebServiceKey        String       Web service key of the supporter searched
 *
 * @return Boolean              TRUE if the supporter ID is found, FALSE otherwise
 */
 function getSupportMemberByWebServiceKey($DbConnection, $WebServiceKey)
 {
     if (strlen(trim($WebServiceKey)) == 32)
     {
         $DbResult = $DbConnection->query("SELECT SupportMemberID FROM SupportMembers WHERE SupportMemberWebServiceKey = \"$WebServiceKey\"
                                          AND SupportMemberActivated > 0");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 1)
             {
                 // The supporter is found
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['SupportMemberID'];
             }
         }
     }

     // The supporter isn't found
     return FALSE;
 }


/**
 * Add/update the login and the password of a supporter in the SupportMembers table
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-05-03
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $SupportMemberID           Integer      ID of the supporter
 * @param $Login                     String       Login, in md5, of the supporter
 * @param $Password                  String       password, in md5, of the supporter
 *
 * @return Boolean                   TRUE if the login and the password have been added/updated,
 *                                   FALSE otherwise
 */
 function dbSetLoginPwdSupportMember($DbConnection, $SupportMemberID, $Login, $Password)
 {
     // Check if the supporter exists
     if (isExistingSupportMember($DbConnection, $SupportMemberID))
     {
         // Check if the couple login-password already exists
         $DbResult = $DbConnection->query("SELECT SupportMemberID FROM SupportMembers WHERE SupportMemberID NOT IN ($SupportMemberID)
                                          AND SupportMemberLogin = \"$Login\" AND SupportMemberPassword = \"$Password\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // We can update the couple login-password of the supporter
                 $DbResult = $DbConnection->query("UPDATE SupportMembers SET SupportMemberLogin = \"$Login\",
                                                  SupportMemberPassword = \"$Password\" WHERE SupportMemberID = $SupportMemberID");
                 if (!DB::isError($DbResult))
                 {
                     return TRUE;
                 }
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Add/update the OpenID url of a supporter in the SupportMembers table
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-04-06
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $SupportMemberID           Integer      ID of the supporter
 * @param $OpenIDUrl                 String       OpenID url of the supporter
 *
 * @return Boolean                   TRUE if the OpenID url has been added/updated,
 *                                   FALSE otherwise
 */
 function dbSetOpenIdUrlSupportMember($DbConnection, $SupportMemberID, $OpenIDUrl)
 {
     // Check if the supporter exists
     if (isExistingSupportMember($DbConnection, $SupportMemberID))
     {
         $bContinue = FALSE;
         if (empty($OpenIDUrl))
         {
             $bContinue = TRUE;
         }
         else
         {
             // Check if the OpenID url already exists
             $DbResult = $DbConnection->query("SELECT SupportMemberID FROM SupportMembers WHERE SupportMemberID NOT IN ($SupportMemberID)
                                              AND SupportMemberOpenIdUrl = \"$OpenIDUrl\"");
             if (!DB::isError($DbResult))
             {
                 if ($DbResult->numRows() == 0)
                 {
                     $bContinue = TRUE;
                 }
             }
         }

         if ($bContinue)
         {
             // We can update the OpenID url of the supporter
             $DbResult = $DbConnection->query("UPDATE SupportMembers SET SupportMemberOpenIdUrl = \"$OpenIDUrl\"
                                              WHERE SupportMemberID = $SupportMemberID");
             if (!DB::isError($DbResult))
             {
                 return TRUE;
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Add/update the web service key of a supporter in the SupportMembers table
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-04-06
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $SupportMemberID           Integer      ID of the supporter
 * @param $WebServiceKey             String       Web service key of the supporter
 *
 * @return Boolean                   TRUE if the web service key has been added/updated,
 *                                   FALSE otherwise
 */
 function dbSetWebServiceKeySupportMember($DbConnection, $SupportMemberID, $WebServiceKey)
 {
     // Check if the supporter exists
     if (isExistingSupportMember($DbConnection, $SupportMemberID))
     {
         $bContinue = FALSE;
         if (empty($WebServiceKey))
         {
             $bContinue = TRUE;
         }
         else
         {
             // Check if the Web Service key already exists
             $DbResult = $DbConnection->query("SELECT SupportMemberID FROM SupportMembers WHERE SupportMemberID NOT IN ($SupportMemberID)
                                              AND SupportMemberWebServiceKey = \"$WebServiceKey\"");
             if (!DB::isError($DbResult))
             {
                 if ($DbResult->numRows() == 0)
                 {
                     $bContinue = TRUE;
                 }
             }
         }

         if ($bContinue)
         {
             // We can update the Web Service key of the supporter
             $DbResult = $DbConnection->query("UPDATE SupportMembers SET SupportMemberWebServiceKey = \"$WebServiceKey\"
                                              WHERE SupportMemberID = $SupportMemberID");
             if (!DB::isError($DbResult))
             {
                 return TRUE;
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Update informations of a supporter in the SupportMembers table without login and password
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2012-01-16 : taken into account the SupportMemberActivated field
 *     - 2015-10-06 : taken into account the FamilyID field
 *
 * @since 2012-01-16
 *
 * @param $DbConnection              DB object  Object of the opened database connection
 * @param $SupportMemberID           Integer    ID of the supporter
 * @param $Lastname                  String     Lastname of the supporter
 * @param $Firstname                 String     Firstname of the supporter
 * @param $Email                     String     E-mail address of the supporter
 * @param $StateID                   Integer    ID of the state in which the supporter is
 * @param $Phone                     String     Phone number of the supporter
 * @param $Activated                 Integer    1 to active the supporter profil, 0 otherwise
 * @param $FamilyID                  Integer    ID of the family associated to the support member account [1..n], NULL otherwise
 *
 * @return Integer                   The primary key of the supporter, 0 otherwise
 */
 function dbUpdateSupportMember($DbConnection, $SupportMemberID, $Lastname, $Firstname, $Email, $StateID, $Phone = "", $Activated = 1, $FamilyID = NULL)
 {
     // The parameters are correct?
     if (($SupportMemberID > 0) && ($Lastname != "") && ($Firstname != "") && ($Email != "") && ($StateID > 0) && ($Activated >= 0)
         && ($Activated <= 1))
     {
         if (!is_null($FamilyID))
         {
             if (($FamilyID < 0) || (!isInteger($FamilyID)))
             {
                 // ERROR
                 return 0;
             }
             elseif (empty($FamilyID))
             {
                 $FamilyID = ", FamilyID = NULL";
             }
             else
             {
                 $FamilyID = ", FamilyID = $FamilyID";
             }
         }

         // Is the supporter the same as an other supporter?
         $DbResult = $DbConnection->query("SELECT SupportMemberID FROM SupportMembers WHERE SupportMemberID NOT IN ($SupportMemberID)
                                          AND SupportMemberLastname = \"$Lastname\" AND SupportMemberFirstname = \"$Firstname\"
                                          AND SupportMemberEmail = \"$Email\" AND SupportMemberStateID = $StateID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // We can update the profil of the supporter
                 $DbResult = $DbConnection->query("UPDATE SupportMembers SET SupportMemberLastname = \"$Lastname\",
                                                  SupportMemberFirstname = \"$Firstname\", SupportMemberPhone = \"$Phone\",
                                                  SupportMemberEmail = \"$Email\", SupportMemberActivated = $Activated,
                                                  SupportMemberStateID = $StateID $FamilyID WHERE SupportMemberID = $SupportMemberID");
                 if (!DB::isError($DbResult))
                 {
                     return $SupportMemberID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update the support member state ID of a supporter in the SupportMembers table
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2017-10-03
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $SupportMemberID           Integer      ID of the supporter to update
 * @param $SupportMemberStateID      Integer      ID of the supporter state to set
 *
 * @return Boolean                   TRUE if the support member state has been updated,
 *                                   FALSE otherwise
 */
 function dbSetSupportMemberStateOfSupportMember($DbConnection, $SupportMemberID, $SupportMemberStateID)
 {
     // Check if the supporter exists
     if ((isExistingSupportMember($DbConnection, $SupportMemberID)) && ($SupportMemberStateID > 0))
     {
         $DbResult = $DbConnection->query("UPDATE SupportMembers SET SupportMemberStateID = $SupportMemberStateID
                                           WHERE SupportMemberID = $SupportMemberID");

         if (!DB::isError($DbResult))
         {
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Give the whole fields values of a supporter, thanks to his ID
 *
 * @author STNA/7SQ
 * @version 1.3
 *     - 2004-08-27 : taken into account the SupportMemberActivated field
 *     - 2010-03-17 : taken into account the SupportMemberOpenIdUrl and SupportMemberWebServiceKey fields
 *     - 2015-10-06 : taken into account the FamilyID field
 *
 * @since 2004-04-21
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $SupportMemberID           Integer      ID of the supporter searched
 *
 * @return Mixed array               All fields values of a supporter if he exists,
 *                                   an empty array otherwise
 */
 function getSupportMemberInfos($DbConnection, $SupportMemberID)
 {
     $DbResult = $DbConnection->query("SELECT SupportMemberID, SupportMemberLastname, SupportMemberFirstname, SupportMemberPhone,
                                      SupportMemberEmail, SupportMemberActivated, SupportMemberOpenIdUrl, SupportMemberWebServiceKey,
                                      SupportMemberStateID, FamilyID FROM SupportMembers WHERE SupportMemberID = $SupportMemberID");
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
 * Give the whole fields values of supporters, thanks to their initials
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-02-03
 *
 * @param $DbConnection        DB object      Object of the opened database connection
 * @param $Initials            String         Initials of the supporters searched
 * @param $ArrayParams         Mixed Array    Optional search parameters
 *
 * @return Mixed array         All fields values of the supporters found,
 *                             an empty array otherwise
 */
 function getSupportMemberInfosByInitials($DbConnection, $Initials, $ArrayParams = array('SupportMemberActivated' => array(1)))
 {
     $iSize = strlen($Initials);
     if ($iSize >= 3)
     {
         $ArraySupportersFound = array(
                                       'SupportMemberID' => array(),
                                       'SupportMemberLastname' => array(),
                                       'SupportMemberFirstname' => array(),
                                       'SupportMemberPhone' => array(),
                                       'SupportMemberEmail' => array(),
                                       'SupportMemberActivated' => array(),
                                       'SupportMemberStateID' => array()
                                      );

         $sConditions = '';
         switch($iSize)
         {
             case 3:
                 $sFirstname = substr($Initials, 0, 1);
                 $sLastname = substr($Initials, 1, 2);
                 $sConditions .= "((SupportMemberFirstname LIKE \"$sFirstname%\" AND SupportMemberLastname LIKE \"$sLastname%\") OR (SupportMemberFirstname LIKE \"$sFirstname%\" AND SupportMemberLastname LIKE \"".$sLastname[0]."%-".$sLastname[1]."%\"))";
                 break;

             case 4:
                 $sFirstname = substr($Initials, 0, 2);
                 $sLastname = substr($Initials, 2, 2);
                 $sConditions .= "((SupportMemberFirstname LIKE \"".$sFirstname[0]."%-".$sFirstname[1]."%\" AND SupportMemberLastname LIKE \"$sLastname%\") OR (SupportMemberFirstname LIKE \"".$sFirstname[0]."%-".$sFirstname[1]."%\" AND SupportMemberLastname LIKE \"".$sLastname[0]."%-".$sLastname[1]."%\"))";
                 break;
         }

         if ((isset($ArrayParams['SupportMemberActivated'])) && (count($ArrayParams['SupportMemberActivated']) > 0))
         {
             $sConditions .= " AND SupportMemberActivated IN ".constructSQLINString($ArrayParams['SupportMemberActivated']);
         }

         $DbResult = $DbConnection->query("SELECT SupportMemberID, SupportMemberLastname, SupportMemberFirstname, SupportMemberPhone,
                                          SupportMemberEmail, SupportMemberActivated, SupportMemberStateID FROM SupportMembers
                                          WHERE $sConditions");
         if (!DB::isError($DbResult))
         {
             // There are customers comments
             if ($DbResult->numRows() > 0)
             {
                 while ($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                 {
                     $ArraySupportersFound['SupportMemberID'][] = $Record['SupportMemberID'];
                     $ArraySupportersFound['SupportMemberLastname'][] = $Record['SupportMemberLastname'];
                     $ArraySupportersFound['SupportMemberFirstname'][] = $Record['SupportMemberFirstname'];
                     $ArraySupportersFound['SupportMemberPhone'][] = $Record['SupportMemberPhone'];
                     $ArraySupportersFound['SupportMemberEmail'][] = $Record['SupportMemberEmail'];
                     $ArraySupportersFound['SupportMemberActivated'][] = $Record['SupportMemberActivated'];
                     $ArraySupportersFound['SupportMemberStateID'][] = $Record['SupportMemberStateID'];
                 }
             }
         }

         return $ArraySupportersFound;
     }

     // Error
     return array();
 }


/**
 * Give the fieldnames of the SupportMembers table
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-02-03
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 *
 * @return Array of Strings     Fieldnames, an empty array otherwise
 */
 function getSupportMembersFieldnames($DbConnection)
 {
     $DbResult = $DbConnection->getAll("SELECT * FROM SupportMembers LIMIT 0,1", array(), DB_FETCHMODE_ASSOC);
     if (!DB::isError($DbResult))
     {
         return array_keys($DbResult[0]);
     }

     // ERROR
     return array();
 }


/**
 * Give the value of a field of a given supporter in the SupportMembers table
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-02-03
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $SupportMemberID      Integer      ID of the  supporter [1..n]
 * @param $Fieldname            String       Name of the field of which we want to get the value
 *
 * @return mixed                Value of the fieldname of the given media, -1 otherwise
 */
 function getSupportMemberFieldValue($DbConnection, $SupportMemberID, $Fieldname)
 {
     // Check if the fieldname given is a field of the SupportMembers table
     if (in_array($Fieldname, getSupportMembersFieldnames($DbConnection)))
     {
         $DbResult = $DbConnection->getOne("SELECT $Fieldname FROM SupportMembers WHERE SupportMemberID = $SupportMemberID");
         if (!DB::isError($DbResult))
         {
             return $DbResult;
         }
     }

     // ERROR
     return -1;
 }


/**
 * Give the list of supporters of each support member state, grouped by a give fieldname
 * of the table
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-03-19
 *
 * @param $DbConnection         DB object      Object of the opened database connection
 * @param $FieldName            String         The fieldname of the table used to group
 *                                             the supporters
 * @param $ArrayParams          Mixed Array    Optional search parameters
 *
 * @return Mixed array          For each FieldName, the ID ans the name of
 *                              the linked supporters, an empty array if error
 */
 function getSupportMembersGroupByFieldname($DbConnection, $FieldName = 'sms.SupportMemberStateName', $ArrayParams = array('SupportMemberActivated' => array(1)))
 {
     if (!empty($FieldName))
     {
         $sConditions = '';
         if ((isset($ArrayParams['SupportMemberActivated'])) && (count($ArrayParams['SupportMemberActivated']) > 0))
         {
             $sConditions .= " AND sm.SupportMemberActivated IN ".constructSQLINString($ArrayParams['SupportMemberActivated']);
         }

         $DbResult = $DbConnection->query("SELECT $FieldName, sm.SupportMemberID, sm.SupportMemberLastname, sm.SupportMemberFirstname
                                          FROM SupportMembers sm, SupportMembersStates sms WHERE sms.SupportMemberStateID > 1
                                          AND sms.SupportMemberStateID = sm.SupportMemberStateID $sConditions
                                          ORDER BY $FieldName, sm.SupportMemberLastname, sm.SupportMemberFirstname");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 // The result array
                 $ArraySupporters = array();
                 $ArrayFieldnames = simplifySQLSELECTSpecifiedFields(array($FieldName));
                 while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                 {
                     // The first key in the array is the FieldName used to group
                     $ArraySupporters[$Record[$ArrayFieldnames[0]]][$Record['SupportMemberID']] = $Record['SupportMemberLastname'].' '.$Record['SupportMemberFirstname'];
                 }

                 return $ArraySupporters;
             }
         }
     }

     // ERROR
     return array();
 }


/**
 * Get support members filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2017-10-03 : taken into account FamilyID as search criteria
 *
 * @since 2016-10-21
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the support members
 * @param $OrderBy                  String                 Criteria used to sort the support members. If < 0, DESC is used,
 *                                                         otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of support members per page to return [1..n]
 *
 * @return Array of String                                 List of support members filtered, an empty array otherwise
 */
 function dbSearchSupportMember($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find support members
     $Select = "SELECT sm.SupportMemberID, sm.SupportMemberLastname, sm.SupportMemberFirstname, sm.SupportMemberEmail,
                sm.SupportMemberPhone, sm.SupportMemberActivated, sms.SupportMemberStateID, sms.SupportMemberStateName,
                f.FamilyID, f.FamilyLastname, f.FamilyDesactivationDate";
     $From = "FROM SupportMembersStates sms, SupportMembers sm LEFT JOIN Families f ON (f.FamilyID = sm.FamilyID)";
     $Where = "WHERE sm.SupportMemberStateID = sms.SupportMemberStateID";
     $Having = "";

     $FromRegistrations = '';
     if (count($ArrayParams) >= 0)
     {
         // <<< SupportMemberID field >>>
         if ((array_key_exists("SupportMemberID", $ArrayParams)) && (!empty($ArrayParams["SupportMemberID"])))
         {
             $Where .= " AND sm.SupportMemberID = ".$ArrayParams["SupportMemberID"];
         }

         // <<< SupportMemberLastname field >>>
         if ((array_key_exists("SupportMemberLastname", $ArrayParams)) && (!empty($ArrayParams["SupportMemberLastname"])))
         {
             $Where .= " AND sm.SupportMemberLastname LIKE \"".$ArrayParams["SupportMemberLastname"]."\"";
         }

         // <<< SupportMemberEmail field >>>
         if ((array_key_exists("SupportMemberEmail", $ArrayParams)) && (!empty($ArrayParams["SupportMemberEmail"])))
         {
             $Where .= " AND sm.SupportMemberEmail LIKE \"".$ArrayParams["SupportMemberEmail"]."\"";
         }

         // <<< SupportMemberStateID field >>>
         if ((array_key_exists("SupportMemberStateID", $ArrayParams)) && (count($ArrayParams["SupportMemberStateID"]) > 0))
         {
             $Where .= " AND sms.SupportMemberStateID IN ".constructSQLINString($ArrayParams["SupportMemberStateID"]);
         }

         // <<< SupportMemberStateName field >>>
         if ((array_key_exists("SupportMemberStateName", $ArrayParams)) && (!empty($ArrayParams["SupportMemberStateName"])))
         {
             $Where .= " AND sms.SupportMemberStateName LIKE \"".$ArrayParams["SupportMemberStateName"]."\"";
         }

         // <<< SupportMemberActivated field >>>
         if ((isset($ArrayParams['SupportMemberActivated'])) && (count($ArrayParams['SupportMemberActivated']) > 0))
         {
             $Where .= " AND sm.SupportMemberActivated IN ".constructSQLINString($ArrayParams['SupportMemberActivated']);
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (count($ArrayParams["FamilyID"]) > 0))
         {
             $Where .= " AND sm.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
         }

         // <<< Option : get support member last connection date >>>
         $bLastConnection = FALSE;
         if (array_key_exists("SupportMemberLastConnection", $ArrayParams))
         {
             if ($ArrayParams["SupportMemberLastConnection"])
             {
                 // Last connection date of the support member
                 $Select .= ", LastConnections.MaxDate";
                 $From .= " LEFT JOIN (SELECT le.SupportMemberID, MAX(LogEventDate) AS MaxDate FROM LogEvents le
                                       WHERE le.LogEventItemType = \"".EVT_SYSTEM."\" AND le.LogEventService = \"".EVT_SERV_LOGIN."\"
                                       AND le.LogEventAction =\"".EVT_ACT_LOGIN."\" GROUP BY le.SupportMemberID) AS LastConnections
                            ON (LastConnections.SupportMemberID = sm.SupportMemberID)";

                 $bLastConnection = TRUE;
             }
         }
     }

     // We take into account the page and the number of support members per page
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY SupportMemberID $Having $StrOrderBy $Limit");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "SupportMemberID" => array(),
                                   "SupportMemberLastname" => array(),
                                   "SupportMemberFirstname" => array(),
                                   "SupportMemberEmail" => array(),
                                   "SupportMemberPhone" => array(),
                                   "SupportMemberActivated" => array(),
                                   "SupportMemberStateID" => array(),
                                   "SupportMemberStateName" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array(),
                                   "FamilyDesactivationDate" => array()
                                  );

             if ($bLastConnection)
             {
                 $ArrayRecords["SupportMemberLastConnection"] = array();
             }

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["SupportMemberID"][] = $Record["SupportMemberID"];
                 $ArrayRecords["SupportMemberLastname"][] = $Record["SupportMemberLastname"];
                 $ArrayRecords["SupportMemberFirstname"][] = $Record["SupportMemberFirstname"];
                 $ArrayRecords["SupportMemberEmail"][] = $Record["SupportMemberEmail"];
                 $ArrayRecords["SupportMemberPhone"][] = $Record["SupportMemberPhone"];
                 $ArrayRecords["SupportMemberActivated"][] = $Record["SupportMemberActivated"];
                 $ArrayRecords["SupportMemberStateID"][] = $Record["SupportMemberStateID"];
                 $ArrayRecords["SupportMemberStateName"][] = $Record["SupportMemberStateName"];
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
                 $ArrayRecords["FamilyDesactivationDate"][] = $Record["FamilyDesactivationDate"];

                 if ($bLastConnection)
                 {
                     $ArrayRecords["SupportMemberLastConnection"][] = $Record["MaxDate"];
                 }
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of support members filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2017-10-03 : taken into account FamilyID as search criteria
 *
 * @since 2016-10-21
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the support members
 *
 * @return Integer              Number of the support members found, 0 otherwise
 */
 function getNbdbSearchSupportMember($DbConnection, $ArrayParams)
 {
     // SQL request to find workgroups
     $Select = "SELECT sm.SupportMemberID";
     $From = "FROM SupportMembers sm, SupportMembersStates sms";
     $Where = "WHERE sm.SupportMemberStateID = sms.SupportMemberStateID";
     $Having = "";

     $FromRegistrations = '';
     if (count($ArrayParams) >= 0)
     {
         // <<< SupportMemberID field >>>
         if ((array_key_exists("SupportMemberID", $ArrayParams)) && (!empty($ArrayParams["SupportMemberID"])))
         {
             $Where .= " AND sm.SupportMemberID = ".$ArrayParams["SupportMemberID"];
         }

         // <<< SupportMemberLastname field >>>
         if ((array_key_exists("SupportMemberLastname", $ArrayParams)) && (!empty($ArrayParams["SupportMemberLastname"])))
         {
             $Where .= " AND sm.SupportMemberLastname LIKE \"".$ArrayParams["SupportMemberLastname"]."\"";
         }

         // <<< SupportMemberEmail field >>>
         if ((array_key_exists("SupportMemberEmail", $ArrayParams)) && (!empty($ArrayParams["SupportMemberEmail"])))
         {
             $Where .= " AND sm.SupportMemberEmail LIKE \"".$ArrayParams["SupportMemberEmail"]."\"";
         }

         // <<< SupportMemberStateID field >>>
         if ((array_key_exists("SupportMemberStateID", $ArrayParams)) && (count($ArrayParams["SupportMemberStateID"]) > 0))
         {
             $Where .= " AND sms.SupportMemberStateID IN ".constructSQLINString($ArrayParams["SupportMemberStateID"]);
         }

         // <<< SupportMemberStateName field >>>
         if ((array_key_exists("SupportMemberStateName", $ArrayParams)) && (!empty($ArrayParams["SupportMemberStateName"])))
         {
             $Where .= " AND sms.SupportMemberStateName LIKE \"".$ArrayParams["SupportMemberStateName"]."\"";
         }

         // <<< SupportMemberActivated field >>>
         if ((isset($ArrayParams['SupportMemberActivated'])) && (count($ArrayParams['SupportMemberActivated']) > 0))
         {
             $Where .= " AND sm.SupportMemberActivated IN ".constructSQLINString($ArrayParams['SupportMemberActivated']);
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (count($ArrayParams["FamilyID"]) > 0))
         {
             $Where .= " AND sm.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY SupportMemberID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }
?>