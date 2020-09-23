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
 * Common module : library of database functions used for the UploadedFiles table
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2019-11-20
 */


/**
 * Give the ID of an uploaded file thanks to its name
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-20
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $FileName             String       Name of the file searched
 *
 * @return Integer              ID of the uploaded file [1..n], 0 otherwise
 */
 function getUploadedFileID($DbConnection, $FileName)
 {
     $DbResult = $DbConnection->query("SELECT UploadedFileID FROM UploadedFiles WHERE UploadedFileName = \"$FileName\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["UploadedFileID"];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Check if an uploaded file exists in the UploadedFiles table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-20
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $UploadedFileID       Integer      ID of the uploaded file searched [1..n]
 *
 * @return Boolean              TRUE if the uploaded file exists, FALSE otherwise
 */
 function isExistingUploadedFile($DbConnection, $UploadedFileID)
 {
     $DbResult = $DbConnection->query("SELECT UploadedFileID FROM UploadedFiles WHERE UploadedFileID = $UploadedFileID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The uploaded file exists
             return TRUE;
         }
     }

     // The uploaded file doesn't exist
     return FALSE;
 }


/**
 * Add a file in the UploadedFiles table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-20
 *
 * @param $DbConnection         DB object      Object of the opened database connection
 * @param $Filename             String         Filename
 * @param $FileDate             DateTime       Upload date of the file (yyyy-mm-dd hh:mm:ss)
 * @param $ObjectType           Integer        Type of object linked to the uploaded file
 * @param $ObjectID             Integer        ID of the object (event...) linked to the uploaded file [1..n]
 * @param $Description          String         Description of the content of the file
 *
 * @return Integer              The primary key of the uploaded file, 0 otherwise
 */
 function dbAddUploadedFile($DbConnection, $Filename, $FileDate, $ObjectType, $ObjectID, $Description = '')
 {
     // The parameters are correct?
     if ((!empty($Filename)) && (preg_match("[\d\d\d\d-\d\d-\d\d]", $FileDate) != 0) && ($ObjectType > 0) && ($ObjectID > 0))
     {
         // Is the file the same as an other file?
         $DbResult = $DbConnection->query("SELECT UploadedFileID FROM UploadedFiles WHERE UploadedFileName = \"$Filename\" AND UploadedFileObjectType = $ObjectType");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // New file : it's added
                 // For the auto-incrementation functionality
                 $id = getNewPrimaryKey($DbConnection, "UploadedFiles", "UploadedFileID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO UploadedFiles SET UploadedFileID = $id, UploadedFileName = \"$Filename\",
                                                       UploadedFileDate = \"$FileDate\", UploadedFileDescription = \"$Description\",
                                                       UploadedFileObjectType = $ObjectType, ObjectID = $ObjectID");
                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // Old uploaded file : we return its ID
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record["UploadedFileID"];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update informations of an uploaded file in the UploadedFiles table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-20
 *
 * @param $DbConnection         DB object      Object of the opened database connection
 * @param $FileID               Integer        ID of the file
 * @param $Filename             String         Filename
 * @param $FileDate             DateTime       Upload date of the file
 * @param $Description          String         Description of the content of the file
 * @param $AowID                Integer        The file is linked to this ask of work ID
 *
 * @return Integer              The primary key of the uploaded file, 0 otherwise
 */
 function dbUpdateUploadedFile($DbConnection, $Filename, $FileDate, $ObjectType, $ObjectID, $Description = NULL)
 {
     // The paramters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($FileID < 1) || (!isInteger($FileID)))
     {
         // ERROR
         return 0;
     }

     if (!is_Null($Filename))
     {
         if ($Filename == "")
         {
             // ERROR
             return 0;
         }
         else
         {
             // The UploadedFileName field will be updated
             $ArrayParamsUpdate[] = "UploadedFileName = \"$Filename\"";
         }
     }

     if (!is_Null($FileDate))
     {
         if ($FileDate == "")
         {
             // ERROR
             return 0;
         }
         else
         {
             // The UploadedFileDate field will be updated
             $ArrayParamsUpdate[] = "UploadedFileDate = \"$FileDate\"";
         }
     }

     if (!is_Null($Description))
     {
         // The UploadedFileDescription field will be updated
         $ArrayParamsUpdate[] = "UploadedFileDescription = \"$Description\"";
     }

     if (!is_Null($UploadedFileObjectType))
     {
         if ($ObjectType < 1)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The UploadedFileObjectType field will be updated
             $ArrayParamsUpdate[] = "UploadedFileObjectType = $ObjectType";
         }
     }

     if (!is_Null($ObjectID))
     {
         if (($ObjectID < 0) || (!isInteger($ObjectID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The ObjectID field will be updated
             if ($AowID == 0)
             {
                 $ArrayParamsUpdate[] = "ObjectID = NULL";
             }
             else
             {
                 $ArrayParamsUpdate[] = "ObjectID = $ObjectID";
             }
         }
     }

     // Is the file the same as an other file?
     $DbResult = $DbConnection->query("SELECT UploadedFileID FROM UploadedFiles WHERE UploadedFileID <> $FileID AND UploadedFileName = \"$Filename\"
                                       AND UploadedFileObjectType = $ObjectType");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 0)
         {
             // The file is unique : we can update if there is at least 1 parameter
             if (count($ArrayParamsUpdate) > 0)
             {
                 $DbResult = $DbConnection->query("UPDATE UploadedFiles SET ".implode(", ", $ArrayParamsUpdate)." WHERE UploadedFileID = $FileID");
                 if (!DB::isError($DbResult))
                 {
                     // File updated
                     return $FileID;
                 }
             }
             else
             {
                 // The update isn't usefull
                 return $FileID;
             }
         }
     }

     // ERROR
     return 0;
}


/**
 * Return the list of the uploaded files linked to an object
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-20
 *
 * @param $DbConnection         DB object        Object of the opened database connection
 * @param $ObjectType           Integer          Type of the concerned object [1..n]
 * @param $ObjectID             Integer          ID of the concerned object[1..n]
 *
 * @return Array                Array of filenames, empty array otherwise
 */
 function getUploadedFilesOfObject($DbConnection, $ObjectType, $ObjectID)
 {
     if (($ObjectType > 0) && ($ObjectID > 0))
     {
         // Array which will contain the list of the files joined to the ask of work given in parameter
         $UploadedFiles = array("UploadedFileID" => array(),
                                "UploadedFileName" => array(),
                                "UploadedFileDate" => array(),
                                "UploadedFileDescription" => array()
                               );

         $DbResult = $DbConnection->query("SELECT uf.UploadedFileID, uf.UploadedFileName, uf.UploadedFileDate, uf.UploadedFileDescription
                                           FROM UploadedFiles uf
                                           WHERE uf.ObjectID = $ObjectID AND uf.UploadedFileObjectType = $ObjectType
                                           ORDER BY uf.UploadedFileDate, uf.UploadedFileName");
         if (!DB::isError($DbResult))
         {
             // Uploaded files found
             if ($DbResult->numRows() != 0)
             {
                 while ($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                 {
                     $UploadedFiles["UploadedFileID"][] = $Record["UploadedFileID"];
                     $UploadedFiles["UploadedFileName"][] = $Record["UploadedFileName"];
                     $UploadedFiles["UploadedFileDate"][] = $Record["UploadedFileDate"];
                     $UploadedFiles["UploadedFileDescription"][] = $Record["UploadedFileDescription"];
                 }
             }
         }

         // Return the list of the files linked to the object given in parameter
         return $UploadedFiles;
     }

     // ERROR
     return array();
 }


/**
 * Delete a file of an object in the UploadedFiles table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-20
 *
 * @param $DbConnection         DB object      Object of the opened database connection
 * @param $UploadedFileID       Integer        ID of the uploaded file to delete [1..n]
 *
 * @return Boolean              TRUE if the uploaded file is deleted, FALSE otherwise
 */
 function dbDeleteUploadedFile($DbConnection, $UploadedFileID)
 {
     // The parameters are correct?
     if ($UploadedFileID > 0)
     {
         // Delete the uploaded file
         $DbResult = $DbConnection->query("DELETE FROM UploadedFiles WHERE UploadedFileID = $UploadedFileID");
         if (!DB::isError($DbResult))
         {
             // Uploaded file deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }
?>