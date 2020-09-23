<?php
/* Copyright (C) 2007  STNA/7SQ (IVDS)
 *
 * This file is part of ASTRES.
 *
 * ASTRES is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ASTRES is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ASTRES; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Interface module : XHTML Graphic primitives hyperlinks library used to create hyperlinks
 *
 * @author STNA/7SQ
 * @version 3.4
 * @since 2004-01-22
 */


/**
 * Display a text with a style and a hyperlink, in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.1
 *     - 2004-05-17 : taken into account the $Title and the $Target values
 *     - 2007-01-12 : new interface
 *
 * @since 2004-01-22
 *
 * @param $Text     String      Text to display with a link
 * @param $Link     String      URL of the link
 * @param $Style    String      Name of the style which will be use to display the text with a link
 * @param $Title    String      Tip of the hyperlink
 * @param $Target   String      _blank if the hyperlink must open a new window
 */
 function displayStyledLinkText($Text, $Link, $Style = '', $Title = '', $Target = '')
 {
     if ($Style == '')
     {
         echo "<a href=\"$Link\" title=\"$Title\"";
     }
     else
     {
         echo "<a class=\"$Style\" href=\"$Link\" title=\"$Title\"";
     }

     if ($Target != '') {
         echo " target=\"$Target\"";
     }

     echo ">$Text</a>";
 }


/**
 * Generate a text with a style and a hyperlink, in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2007-01-19 : new interface
 *
 * @since 2004-08-17
 *
 * @param $Text     String      Text to display with a link
 * @param $Link     String      URL of the link
 * @param $Style    String      Name of the style which will be use to display the text with a link
 * @param $Title    String      Tip of the hyperlink
 * @param $Target   String      _blank if the hyperlink must open a new window
 *
 * @return String               XHTML <a> tag
 */
 function generateStyledLinkText($Text, $Link, $Style = '', $Title = '', $Target = '')
 {
     $tmp = "<a href=\"$Link\" title=\"$Title\"";

     if ($Style != '') {
         $tmp .= " class=\"$Style\"";
     }

     if ($Target != '') {
         $tmp .= " target=\"$Target\"";
     }

     return $tmp.">$Text</a>";
 }


/**
 * Generate a crypted hyperlink with a style, on a label, in the current row of the table of the
 * web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.1
 *     - 2007-01-12 : new interface
 *     - 2010-05-12 : allow more parameters or an anchor in the url
 *
 * @since 2004-04-06
 *
 * @param $Label         String      Text used to display the hyperlink
 * @param $ID            String      Reference used in the md5 function
 * @param $Link          String      URL of the hyperlink
 * @param $Title         String      Tip of the hyperlink
 * @param $Style         String      Name of the style which will be use to display the hyperlink
 * @param $Target        String      _blank if the hyperlink must open a new window
 * @param $MoreParams    String      Other parameters or an anchor for the url
 *
 * @return String        Hyperlink generated, an empty string otherwise
 */
 function generateCryptedHyperlink($Label, $ID, $Link, $Title = '', $Style = '', $Target = '', $MoreParams = '')
 {
     if ($Link != '')
     {
         $tmp = "<a href=\"$Link?Cr=".md5($ID)."&amp;Id=$ID$MoreParams\" title=\"$Title\"";

         if ($Style != '') {
             $tmp .= " class=\"$Style\"";
         }

         if ($Target != '') {
             $tmp .= " target=\"$Target\"";
         }

         return $tmp.">$Label</a>";
     }

     // ERROR
     return '';
 }


/**
 * Generate a hyperlink with a style, on a ask of work reference, in the current row of the table of the
 * web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.1
 *     - 2007-01-12 : new interface
 *     - 2010-05-12 : allow more parameters or an anchor in the url
 *
 * @since 2004-04-05
 *
 * @param $Label         String      Text used to display the hyperlink
 * @param $AowReference  String      Reference of the ask of work (AowID or AowRef)
 * @param $Link          String      URL of the hyperlink
 * @param $Title         String      Tip of the hyperlink
 * @param $Style         String      Name of the style which will be use to display the hyperlink
 * @param $Target        String      _blank if the hyperlink must open a new window
 * @param $MoreParams    String      Other parameters or an anchor for the url
 *
 * @return String        Hyperlink generated, an empty string otherwise
 */
 function generateAowIDHyperlink($Label, $AowReference, $Link, $Title = "", $Style = "", $Target = "", $MoreParams = "")
 {
     if ($Link != '')
     {
         $tmp = "<a href=\"$Link?Cr=".md5($AowReference)."&amp;Id=$AowReference$MoreParams\" title=\"$Title\"";

         if ($Style != '') {
             $tmp .= " class=\"$Style\"";
         }

         if ($Target != '') {
             $tmp .= " target=\"$Target\"";
         }

         return $tmp.">$Label</a>";
     }

     // ERROR
     return '';
 }


/**
 * Generate a hyperlink with a style, on a picture, in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2007-01-16 : new interface
 *
 * @since 2004-04-17
 *
 * @param $Pic           String      Path of the picture
 * @param $Link          String      URL of the hyperlink
 * @param $Title         String      Tip of the hyperlink
 * @param $Style         String      Name of the style which will be use to display the picture
 * @param $Target        String      _blank if the hyperlink must open a new window
 *
 * @return String                    Hyperlink on a styled picture generated, an empty string otherwise
 */
 function generateStyledPictureHyperlink($Pic, $Link, $Title = '', $Style = '', $Target = '')
 {
      $tmp = '';
      if ($Pic != '')
      {
          $tmp = "<a href=\"$Link\" title=\"$Title\"";

          if ($Target != '') {
              $tmp .= " target=\"$Target\"";
          }

          $tmp .= "><img src=\"$Pic\"";

          if ($Style != '')
          {
              $tmp .= " class=\"$Style\"";
          }

          $tmp .= " title=\"$Title\" alt=\"\" /></a>";
      }

      return $tmp;
 }
?>