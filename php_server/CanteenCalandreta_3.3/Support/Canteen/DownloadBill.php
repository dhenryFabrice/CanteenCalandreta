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
 * Support module : allow a supporter to download a bill in PDF file. The supporter must be logged
 * to download the bill.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-03-16
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS',
                                      'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                      'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                      'CONF_CANTEEN_PRICES',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

 // To take into account the crypted and no-crypted bill ID
 // Crypted ID
 if (!empty($_GET["Cr"]))
 {
     $CryptedID = (string)strip_tags($_GET["Cr"]);
 }
 else
 {
     $CryptedID = '';
 }

 // No-crypted ID
 if (!empty($_GET["Id"]))
 {
     $Id = (string)strip_tags($_GET["Id"]);
 }
 else
 {
     $Id = '';
 }

 // The ID and the md5 crypted ID must be equal
 if ((md5($Id) == $CryptedID) && ($Id > 0))
 {
      // The supporter must be allowed to view the bill
      $AccessRules = $CONF_ACCESS_APPL_PAGES[FCT_BILL];
      $cUserAccess = FCT_ACT_NO_RIGHTS;

      // Creation mode
      if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
      {
          $cUserAccess = FCT_ACT_CREATE;
      }
      elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
      {
          // Update mode
          $cUserAccess = FCT_ACT_UPDATE;
      }
      elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
      {
          // Read mode
          $cUserAccess = FCT_ACT_READ_ONLY;
      }

      if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
      {
          $RecordBill = getTableRecordInfos($DbCon, "Bills", $Id);
          if (!empty($RecordBill))
          {
              // Generate the bill in HTML/CSS, then we convert the HTML/CSS file to PDF
              $Month = date('m', strtotime($RecordBill['BillForDate']));
              $Year = date('Y', strtotime($RecordBill['BillForDate']));
              $FileSuffix = formatFilename($CONF_PLANNING_MONTHS[$Month - 1].$Year);
              $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_FILENAME."$FileSuffix-$Id.html";

              @unlink($HTMLFilename);
              printDetailsBillForm($DbCon, $Id, "", $HTMLFilename);
              if (file_exists($HTMLFilename))
              {
                  $PDFFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_FILENAME."$FileSuffix-$Id.pdf";

                  // Generate the PDF
                  @unlink($PDFFilename);
                  if (html2pdf($HTMLFilename, $PDFFilename, 'portrait', MONTHLY_BILL_DOCTYPE))
                  {
                      // Delete the HTML file
                      unlink($HTMLFilename);

                      // Create link to download the PDF containing all bills of the month
                      if (file_exists($PDFFilename))
                      {
                          // Force the download of the PDF file
                          $PDFsize = filesize($PDFFilename);
                          $PDFTmpFilename = basename($PDFFilename);
                          header("Content-Type: application/octet-stream");
                          header("Content-Length: $PDFsize");
                          header("Content-disposition: attachment; filename=$PDFTmpFilename");
                          header("Pragma: no-cache;");
                          header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
                          header("Expires: 0");
                          readfile($PDFFilename);
                      }
                  }
              }
          }
          else
          {
              openFrame($LANG_ERROR);
              displayStyledText($LANG_ERROR_NOT_VIEW_BILL, 'ErrorMsg');
              closeFrame();
          }
      }
      else
      {
          openFrame($LANG_ERROR);
          displayStyledText($LANG_ERROR_NOT_VIEW_BILL, 'ErrorMsg');
          closeFrame();
      }
 }
 else
 {
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_NOT_VIEW_BILL, 'ErrorMsg');
     closeFrame();
 }

 // Release the connection to the database
 dbDisconnection($DbCon);
?>