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
 * PHP plugin autocompletion module : add the autocompletion function for the recipients and in copy
 * fields of the "send message" form
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-06-20 : taken into account $CONF_CHARSET
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2016-03-02
 */


 // Include Config.php because of the name of the session
 require '../../GUI/GraphicInterface.php';

 session_start();

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 // Get the value
 if (isset($_POST['debut'])) {
     $sEnteredValue = $_POST['debut'];

     // We detect the charset : it must be ISO-8859-1
     if (mb_detect_encoding($sEnteredValue, 'UTF-8') == 'UTF-8')
     {
         $sEnteredValue = utf8_decode($sEnteredValue);
     }

     $sEnteredValue = strip_tags(strtolower(trim($sEnteredValue)));
     $sEnteredValue = "%$sEnteredValue%";
 } else {
     $sEnteredValue = "%";
 }

 // Get the name of the form field in which we have entered the value
 $sFormFieldName = '';
 if (isset($_POST['FormFieldName'])) {
    $sFormFieldName = strip_tags(trim($_POST['FormFieldName']));
 }

 $ArrayFoundValues = array();
 if (!empty($sFormFieldName))
 {
     $ArrayParams = array(
                          "SchoolYear" => array(getSchoolYear(date('Y-m-d'))),
                          "SupportMemberActivated" => array(1),
                          "Name" => $sEnteredValue
                         );

     $ArrayRecipients = dbSearchMessageRecipients($DbCon, $ArrayParams, "rName", 1, 0);

     if (isset($ArrayRecipients['rName']))
     {
         foreach($ArrayRecipients['rName'] as $n => $rName)
         {
             $sNameToDisplay = $rName." (".$ArrayRecipients['rStateName'][$n].")";
             if (!in_array($sNameToDisplay, array_values($ArrayFoundValues)))
             {
                 $ArrayFoundValues[$ArrayRecipients['rID'][$n]] = $sNameToDisplay;
             }
         }
     }
 }

 // Release the connection to the database
 dbDisconnection($DbCon);

 // We send the response to the browser
 header('Content-type: text/html; charset='.strtolower($CONF_CHARSET));
 echo '<ul class="FormFieldAutoCompletionList">';

 foreach($ArrayFoundValues as $id => $CurrentName)
 {
     echo "<li id=\"$id\">$CurrentName</li>";
 }

 echo '</ul>';
?>
