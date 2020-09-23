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
 * Interface module : XHTML Graphic components library used to export data of some web pages
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-10
 */


/**
 * Export a table in txt format
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-10
 *
 * @param $TabCaptions           Array of Strings         Captions of the table
 * @param $TabData               Array of Strings         Data to put in txt format
 * @param $Separator             Straing                  Separator used to separate columns
 * @param $ResultFilename        String                   Path Filename used to store the export result
 */
 function exportTableToTxtFile($TabCaptions, $TabData, $Separator = ";", $ResultFilename = "")
 {
     if ((count($TabCaptions) > 0) && ($Separator != ""))
     {
         // Put the captions
         $tmp = implode($Separator, $TabCaptions)."\n";

         // Put the data
         $ArrayKeys = array_keys($TabData); // To manage all arrays types
         for($i = 0 ; $i < count($TabData[$ArrayKeys[0]]) ; $i++)
         {
             // New record
             $ArrayTmp = array();

             // Get elements of a record
             for($j = 0 ; $j < count($TabData) ; $j++)
             {
                 // Process the current value
                 $CurrentValue = $TabData[$ArrayKeys[$j]][$i];
                 if (is_Null($CurrentValue))
                 {
                     $CurrentValue = "";
                 }
                 $ArrayTmp[] = invFormatText($CurrentValue, "CSV");
             }

             $tmp .= implode($Separator, $ArrayTmp)."\n";
         }

         // Return result
         if ($ResultFilename == "")
         {
             return $tmp;
         }
         else
         {
             // We store the result in a file
             saveToFile($ResultFilename, "wt", array($tmp));
         }
     }

     // ERROR
     return "";
 }
?>