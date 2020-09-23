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
 * Interface module : XHTML Graphic components library used to display high level informations
 *
 * @author Christophe Javouhey
 * @version 2.3
 * @since 2012-01-10
 */


/**
 * Display the execution script time in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2003-12-27
 *
 * @param $Style    String      @param $Style    String      Name of the style which will be use to display the execution script time
 */
 function displayExecutionScriptTime($Style)
 {
     displayStyledText($GLOBALS["LANG_EXECUTION_TIME_MSG"].bcsub($GLOBALS["END_TIME"], $GLOBALS["START_TIME"], 6)." ".$GLOBALS["LANG_SECONDS"].".", $Style);
 }


/**
 * Generate the content of a table in the current web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.2
 *     - 2012-01-06 : allow to define styles for some columns
 *     - 2014-02-25 : allow to define an anchor on the table
 *
 * @since 2011-08-19
 *
 * @param $TabCaptions        Array of Strings        Captions of the columns of the table
 * @param $TabSorts           Array of Strings        Contains the criterion to sort the table. If a string is empty, the column can't be used to sort
 * @param $SortFct            String                  Javscript Function called to sort the table
 * @param $TabData            Array of Strings        Contains the data to display
 * @param $StyleTable         String                  Style used to display the table
 * @param $StyleCaptions      String                  Style used to display the captions of the table
 * @param $StyleRows          String                  Style used to display the data in the table
 * @param $TableName          String                  Name of the table used for a multi-sort of tables
 * @param $TabGroupBy         Mixed Array             To group by rows of a given column
 * @param $SortedColumn       Integer                 Num of the column sorted (> 0 or < 0)
 * @param $StylesCols         Mixed Array             Styles for somes columns
 * @param $ID                 String                  $ID of the table
 *
 * @return String             String with the styled table
 */
 function generateStyledTable($TabCaptions, $TabSorts, $SortFct, $TabData, $StyleTable = '', $StyleCaptions = '', $StyleRows = '', $TableName = '', $TabGroupBy = array(), $SortedColumn = NULL, $StylesCols = array(), $ID = '')
 {
     $sTmp = '';
     if ((count($TabCaptions) > 0) && (count($TabCaptions) == count($TabSorts)))
     {
         // Open the table
         $sTmp = "<div class=\"table";

         if ($StyleTable != '') {
             $sTmp .= " $StyleTable";
         }
         $sTmp .= "\">\n";

         $sTmp .= "\t<table";

         if (!empty($ID)) {
             $sTmp .= " id=\"$ID\"";
         }

         $sTmp .= " class=\"$StyleTable\" cellspacing=\"0\" summary=\"\">\n";
         $sTmp .= "\t<thead>\n";
         $sTmp .= "\t\t<tr>\n\t\t\t";

         // Display the captions
         foreach($TabCaptions as $i => $CurrentCaption)
         {
             if ($TabSorts[$i] == '')
             {
                 $sTmp .= "<th class=\"$StyleCaptions\">$CurrentCaption</th>";
             }
             else
             {
                 // This column is sortable
                 $sTmp .= "<th class=\"$StyleCaptions\"><a href=\"javascript:$SortFct($TabSorts[$i]";
                 if ($TableName != '')
                 {
                     $sTmp .= ",'$TableName'";
                 }

                 $sTmp .= ")\" title=\"".$GLOBALS["LANG_SORT_INSTRUCTIONS"]."\">$CurrentCaption</a>";

                 // Check if this column is the criteria to sort the data
                 if (!empty($SortedColumn))
                 {
                     // We search the position of the SortedColumn in the $TabSort because, for the Asks of work tables,
                     // num of caption column <> SortedColumn (all columns aren't displayed!)
                     $iPos = array_search(abs($SortedColumn), $TabSorts);
                     if (($iPos !== FALSE) && ($iPos == $i))
                     {
                         // This column is sorted
                         if ($SortedColumn > 0)
                         {
                             $sTmp .= ' '.generateStyledPicture($GLOBALS['CONF_SORT_TABLE_ASC']);
                         }
                         else
                         {
                             $sTmp .= ' '.generateStyledPicture($GLOBALS['CONF_SORT_TABLE_DESC']);
                         }
                     }
                 }

                 $sTmp .= "</th>";
             }
         }
         $sTmp .= "\n\t\t</tr>\n";
         $sTmp .= "\t</thead>\n";

         // Display data
         $sTmp .= "\t<tbody>\n";

         if (empty($TabGroupBy))
         {
             for($i = 0 ; $i < count($TabData[0]) ; $i++)
             {
                 if ($i % 2)
                 {
                     $ParityStyle = 'even';
                 }
                 else
                 {
                     $ParityStyle = 'odd';
                 }

                 $sTmp .= "\t\t<tr>\n\t\t\t";

                 for($j = 0 ; $j < count($TabData) ; $j++)
                 {
                     $sCurrentColStyle = "$StyleRows $ParityStyle";
                     if (isset($StylesCols[$j]))
                     {
                         $sCurrentColStyle .= ' '.$StylesCols[$j];
                     }

                     $sTmp .= "<td class=\"$sCurrentColStyle\">".$TabData[$j][$i]."</td>";
                 }

                 $sTmp .= "\n\t\t</tr>\n";
             }
         }
         else
         {
             $ArrayCurrentGroupBy = array();
             $ArrayKeysGroupBy = array_keys($TabGroupBy);
             for($i = 0 ; $i < count($TabData[0]) ; $i++)
             {
                 if ($i % 2)
                 {
                     $ParityStyle = 'even';
                 }
                 else
                 {
                     $ParityStyle = 'odd';
                 }

                 if (empty($ArrayCurrentGroupBy))
                 {
                     $ArrayCurrentGroupBy = array_shift($TabGroupBy[$ArrayKeysGroupBy[0]]);
                     $NbGroupBy = count($ArrayCurrentGroupBy);
                 }

                 $sTmp .= "\t\t<tr>\n\t\t\t";

                 for($j = 0 ; $j < count($TabData) ; $j++)
                 {
                     $sCurrentColStyle = "$StyleRows $ParityStyle";
                     if (isset($StylesCols[$j]))
                     {
                         $sCurrentColStyle .= ' '.$StylesCols[$j];
                     }

                     // Check if the rows of this column must be grouped
                     if ($j == $ArrayKeysGroupBy[0])
                     {
                         // Yes, group by
                         if ($NbGroupBy == count($ArrayCurrentGroupBy))
                         {
                             $sTmp .= "<td class=\"$sCurrentColStyle\" rowspan=\"$NbGroupBy\">".$TabData[$j][$i]."</td>";
                         }

                         array_shift($ArrayCurrentGroupBy);
                     }
                     else
                     {
                         // No group by
                         $sTmp .= "<td class=\"$sCurrentColStyle\">".$TabData[$j][$i]."</td>";
                     }
                 }

                 $sTmp .= "\n\t\t</tr>\n";
             }
         }

         $sTmp .= "\t</tbody>\n";

         // Close the table
         $sTmp .= "\t</table>\n";
         $sTmp .= "</div>\n";
     }

     return $sTmp;
 }


/**
 * Display a table in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 3.5
 *     - 2005-05-10 : Taken into account of the name of the table for a multi-sort of tables
 *     - 2007-01-12 : new interface
 *     - 2008-04-03 : display with different style odd and even lines of the table
 *     - 2010-11-03 : allow to group by rows of a given column and display a mark to show the sort
 *                    in the caption of the column used as criteria to sort result
 *     - 2011-08-19 : use the generateStyledTable() function
 *     - 2012-01-06 : allow to define styles for some columns
 *     - 2014-02-25 : allow to define an anchor on the table
 *
 * @since 2004-01-18
 *
 * @param $TabCaptions        Array of Strings        Captions of the columns of the table
 * @param $TabSorts           Array of Strings        Contains the criterion to sort the table. If a string is empty, the column can't be used to sort
 * @param $SortFct            String                  Javscript Function called to sort the table
 * @param $TabData            Array of Strings        Contains the data to display
 * @param $StyleTable         String                  Style used to display the table
 * @param $StyleCaptions      String                  Style used to display the captions of the table
 * @param $StyleRows          String                  Style used to display the data in the table
 * @param $TableName          String                  Name of the table used for a multi-sort of tables
 * @param $TabGroupBy         Mixed Array             To group by rows of a given column
 * @param $SortedColumn       Integer                 Num of the column sorted (> 0 or < 0)
 * @param $StylesCols         Mixed Array             Styles for somes columns
 * @param $ID                 String                  $ID of the table
 */
 function displayStyledTable($TabCaptions, $TabSorts, $SortFct, $TabData, $StyleTable = '', $StyleCaptions = '', $StyleRows = '', $TableName = '', $TabGroupBy = array(), $SortedColumn = NULL, $StylesCols = array(), $ID = '')
 {
     echo generateStyledTable($TabCaptions, $TabSorts, $SortFct, $TabData, $StyleTable, $StyleCaptions, $StyleRows, $TableName,
                              $TabGroupBy, $SortedColumn, $StylesCols, $ID);
 }


/**
 * Display the links "previous" and "next" in the current row of the table of the web page, in the
 * graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 3.1
 *     - 2004-05-14 : display links to access to other pages than the previous and the next page
 *     - 2005-05-10 : Taken into account of the extension for the page "Pg"
 *     - 2006-02-28 : define undeclared variables
 *     - 2007-01-12 : new interface
 *     - 2009-03-10 : patch a bug of wrong url on page numbers
 *
 * @since 2004-01-24
 *
 * @param $PreviousCaption         String        Caption of the previous link
 * @param $PreviousLink            String        URL of the previous link
 * @param $NextCaption             String        Caption of the next link
 * @param $NextLink                String        URL of the next link
 * @param $Style                   String        Style of the both links
 * @param $Currentpage             Integer       Current page displayed [1..n]
 * @param $NbPages                 Integer       Number of pages found [1..n]
 * @param $PgExt                   String        Extension put after the word "Pg"
 */
 function displayPreviousNext($PreviousCaption, $PreviousLink, $NextCaption, $NextLink, $Style = "", $CurrentPage = 1, $NbPages = 5, $PgExt = "")
 {
     $tmp = "<div class=\"prevnext\">\n";
     if ($Style == '')
     {
         $tmp .= "<table summary=\"\">\n<tr>\n\t";
     }
     else
     {
         $tmp .= "<table class=\"$Style\" summary=\"\">\n<tr>\n\t" ;
     }

     // We generate the previous link
     if ($PreviousLink == '')
     {
         $tmp .= "<td class=\"prev\">$PreviousCaption</td>";
         $sStartPreviousLink = '';
     }
     else
     {
         $tmp .= "<td class=\"prev\"><a href=\"$PreviousLink\">$PreviousCaption</a></td>";

         // We get the base of the url of the previous link
         $iPosStartPreviousLink = strpos($PreviousLink, "Pg$PgExt=");
         $sStartPreviousLink = substr($PreviousLink, 0, $iPosStartPreviousLink);

         // We get the paramters of the url of the previous link
         $iPosEndPreviousLink = strpos($PreviousLink, "&", $iPosStartPreviousLink);
         if ($iPosEndPreviousLink === FALSE)
         {
             // No other parameter found in the url
             $sEndPreviousLink = "";
         }
         else
         {
             $sEndPreviousLink = substr($PreviousLink, $iPosEndPreviousLink);
         }
     }

     // We generate the next link
     if ($NextLink == '')
     {
         $tmpNext = "<td class=\"next\">$NextCaption</td>";
         $sStartNextLink = '';
         $sEndNextLink = '';
     }
     else
     {
         $tmpNext = "<td class=\"next\"><a href=\"$NextLink\">$NextCaption</a></td>";

         // We get the base of the url of the next link
         $iPosStartNextLink = strpos($NextLink, "Pg$PgExt=");
         $sStartNextLink = substr($NextLink, 0, $iPosStartNextLink);

         // We get the parameters of the url of the next link
         $iPosEndNextLink = strpos($NextLink, "&", $iPosStartNextLink);
         if ($iPosEndNextLink === FALSE)
         {
             // No other parameter found in the url
             $sEndNextLink = "";
         }
         else
         {
             $sEndNextLink = substr($NextLink, $iPosEndNextLink);
         }
     }

     // If the both base links are equal, we can use this base link as URL for other links on the pages
     if ((strcasecmp($sStartPreviousLink, $sStartNextLink) == 0) || ($sStartPreviousLink == '') || ($sStartNextLink == ''))
     {
         // We keep the not empty string base link
         if ($sStartPreviousLink == '')
         {
             $BaseLink = $sStartNextLink;
             $EndLink = $sEndNextLink;
         }
         else
         {
             $BaseLink = $sStartPreviousLink;
             $EndLink = $sEndPreviousLink;
         }

         // We compute the number of links on the pages to display
         $No = $CurrentPage / $GLOBALS["CONF_TABLE_LINKS_PAGES"];
         $NoPage = floor($No);
         if ($No - $NoPage <= 0.5)
         {
             $StartIndex = max(1, ($NoPage * $GLOBALS["CONF_TABLE_LINKS_PAGES"]) - 1);
         }
         else
         {
             $StartIndex = $CurrentPage - 1;
         }

         $EndIndex = $StartIndex + $GLOBALS["CONF_TABLE_LINKS_PAGES"] - 1;

         if ($EndIndex > $NbPages)
         {
             $EndIndex = $NbPages;
             $StartIndex = max(1, $EndIndex -  $GLOBALS["CONF_TABLE_LINKS_PAGES"] + 1);
         }

         // We check if we must add '...' before the start index to show there are some
         // other pages before
         $sBeforeStartIndex = '';
         if ($StartIndex > 1)
         {
             $sBeforeStartIndex = '...';
         }

         // We check if we must add '...' after the end index to show there are some
         // other pages after
         $sAfterEndIndex = '';
         if ($EndIndex < $NbPages)
         {
             $sAfterEndIndex = '...';
         }

         // We generate the links on the pages
         $tmpListLinks = "<td class=\"page_n\">$sBeforeStartIndex";
         for($i = $StartIndex ; $i <= $EndIndex ; $i++)
         {
             if ($i == $CurrentPage)
             {
                 // We don't generate a link on the current page
                 $tmpListLinks .= "&nbsp;<strong>$i</strong>";
             }
             else
             {
                 // We generate a link for the other links
                 $Link = $BaseLink."Pg$PgExt=$i".$EndLink;
                 $tmpListLinks .= "&nbsp;<a href=\"$Link\" title=\"\">$i</a>";
             }
         }
         $tmpListLinks .= "$sAfterEndIndex</td>";
     }

     // We display the result
     echo $tmp.$tmpListLinks.$tmpNext."\n</tr>\n</table>\n</div>\n";
 }


/**
 * Display a communication area in the current row of the table of the web page, in the graphic
 * interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2005-01-27
 *
 */
 function displayCommunicationArea()
 {
     if (getBrowserName() == "NS4")
     {
         // Layer tag because Netscape 4 detected
         echo "<ilayer id=\"NS4message\" height=\"25\" width=\"100%\"><layer id=\"NS4message2\" height=\"25\" width=\"100%\"></layer></ilayer>";
     }
     else
     {
         // Div tag because IE, OPERA or NS >= 6 detected
         echo "<div id=\"message\" class=\"CommunicationArea\"></div>";
     }
 }


/**
 * Generate visual indicators about a family in the current web page, in
 * the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 2.2
 *     - 2012-07-12 : taken into account the FamilySpecialAnnualContribution field to compute
 *                    the number of powers
 *     - 2013-04-17 : taken into account the "FamilyCoopContribution" parameter
 *     - 2013-08-30 : taken into account the new way to compute school start and end dates
 *     - 2013-10-10 : display an icon for monthly contribution modes
 *
 * @since 2012-03-30
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $FamilyID             Integer               ID of the family [1..n]
 * @param $Mode                 Const                 Define which visual indicators will be generated
 *
 * @return String                                     Visual indicators if the family exists, an empty
 *                                                    string otherwise
 */
 function generateFamilyVisualIndicators($DbConnection, $FamilyID, $Mode = TABLE, $ArrayParams = array())
 {
     if ($FamilyID > 0)
     {
         $tmp = "";

         switch($Mode)
         {
             case TABLE:
                 // Visual indicators displayed in a table of families
                 // Check if the family has paid it's annual contribution
                 if ((array_key_exists("PbAnnualContributionPayments", $ArrayParams)) && (!empty($ArrayParams["PbAnnualContributionPayments"])))
                 {
                     // No payment for the school year
                     $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["PbAnnualContributionPayments"]);
                     $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["PbAnnualContributionPayments"]);

                     $When = "HAVING CASE NbMembers";
                     foreach($GLOBALS['CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS'][$ArrayParams["PbAnnualContributionPayments"]] as $Nb => $Price)
                     {
                         $When .= " WHEN $Nb THEN TotalAmount >= $Price";
                     }

                     $When .= " END";

                     $Where = " AND f.FamilyID NOT IN (SELECT tf.FamilyID FROM (
                                SELECT ff.FamilyID, (ff.FamilyNbMembers + ff.FamilyNbPoweredMembers - ff.FamilySpecialAnnualContribution) AS NbMembers,
                                SUM(p.PaymentAmount) AS TotalAmount FROM Families ff, Payments p WHERE ff.FamilyID = p.FamilyID
                                AND p.PaymentType = 0 AND p.PaymentDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                                GROUP BY p.FamilyID $When) AS tf)";

                     $DbResult = $DbConnection->query("SELECT f.FamilyID FROM Families f WHERE f.FamilyID = $FamilyID $Where");
                     if (!DB::isError($DbResult))
                     {
                         if ($DbResult->numRows() > 0)
                         {
                             $tmp .= generateStyledPicture($GLOBALS["CONF_ANNUAL_CONTRIBUTION_NOT_PAID_ICON"], $GLOBALS['LANG_PB_ANNUAL_CONTRIBUTION_PAYMENTS'], "");
                         }
                     }
                 }

                 // Display an icon in relation with the monthly contribution mode of the family
                 if ((array_key_exists("FamilyMonthlyContributionMode", $ArrayParams)) && ($ArrayParams["FamilyMonthlyContributionMode"] >= 0))
                 {
                     // We get the monthly contribution mode for the given date
                     $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["FamilyMonthlyContributionMode"]);
                     $RecordHistoFamily = getHistoFamilyForDate($DbConnection, $FamilyID, $SchoolYearEndDate);

                     // We check if an icon is defined for this mode
                     if (isset($GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES_ICONS'][$RecordHistoFamily["HistoFamilyMonthlyContributionMode"]]))
                     {
                         // Get the label
                         $sLabel = $GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES'][$RecordHistoFamily["HistoFamilyMonthlyContributionMode"]].'.';
                         $tmp .= generateStyledPicture($GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES_ICONS'][$RecordHistoFamily["HistoFamilyMonthlyContributionMode"]],
                                                       $sLabel, "");
                     }
                 }

                 // Check if the family has a good cooperation indicator
                 if ((array_key_exists("FamilyCoopContribution", $ArrayParams)) && (!empty($ArrayParams["FamilyCoopContribution"])))
                 {
                     // Concerned school year
                     $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["FamilyCoopContribution"]);
                     $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["FamilyCoopContribution"]);

                     $Select = '';
                     $From = '';
                     $Where = '';
                     foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS'] as $c => $NbMinCoop)
                     {
                         // We keep only valided registrations
                         $Select .= ", Tev$c.NB$c";
                         $From .= ", (SELECT er$c.FamilyID, COUNT(er$c.EventRegistrationID) AS NB$c
                                      FROM Events e$c, EventTypes et$c, EventRegistrations er$c WHERE e$c.EventTypeID = et$c.EventTypeID
                                      AND et$c.EventTypeCategory = $c AND e$c.EventID = er$c.EventID AND er$c.FamilyID = $FamilyID
                                      AND e$c.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                                      AND er$c.EventRegistrationValided = 1
                                      GROUP BY er$c.FamilyID HAVING NB$c >= $NbMinCoop) AS Tev$c";
                         $Where .= " AND f.FamilyID = Tev$c.FamilyID";
                     }

                     $DbResult = $DbConnection->query("SELECT f.FamilyID $Select FROM Families f $From WHERE f.FamilyID = $FamilyID $Where");

                     if (!DB::isError($DbResult))
                     {
                         if ($DbResult->numRows() > 0)
                         {
                             $sNbCoops = "";
                             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                             {
                                 foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS'] as $c => $NbMinCoop)
                                 {
                                     if (!empty($sNbCoops))
                                     {
                                         $sNbCoops .= " / ";
                                     }

                                     $sNbCoops .= $Record["NB$c"];
                                 }
                             }

                             // Good cooperation for events
                             $tmp .= generateStyledPicture($GLOBALS["CONF_EVENT_COOPERATION_OK_ICON"], $GLOBALS['LANG_GOOD_EVENT_COOPERATION']." ($sNbCoops).", "");
                         }
                         else
                         {
                             // Too low cooperation for events
                             $tmp .= generateStyledPicture($GLOBALS["CONF_EVENT_COOPERATION_NOK_ICON"], $GLOBALS['LANG_TOO_LOW_EVENT_COOPERATION'], "");
                         }
                     }
                 }
                 break;

             case DETAILS:
                 // Visual indicators displayed in the details of the given family
                 // Check if the family has paid it's annual contribution
                 if ((array_key_exists("PbAnnualContributionPayments", $ArrayParams)) && (!empty($ArrayParams["PbAnnualContributionPayments"])))
                 {
                     // No payment for the school year
                     $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["PbAnnualContributionPayments"]);
                     $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["PbAnnualContributionPayments"]);

                     $When = "HAVING CASE NbMembers";
                     foreach($GLOBALS['CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS'][$ArrayParams["PbAnnualContributionPayments"]] as $Nb => $Price)
                     {
                         $When .= " WHEN $Nb THEN TotalAmount >= $Price";
                     }

                     $When .= " END";

                     $Where = " AND f.FamilyID NOT IN (SELECT tf.FamilyID FROM (
                                SELECT ff.FamilyID, (ff.FamilyNbMembers + ff.FamilyNbPoweredMembers - ff.FamilySpecialAnnualContribution) AS NbMembers,
                                SUM(p.PaymentAmount) AS TotalAmount FROM Families ff, Payments p WHERE ff.FamilyID = p.FamilyID
                                AND p.PaymentType = 0 AND p.PaymentDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                                GROUP BY p.FamilyID $When) AS tf)";

                     $DbResult = $DbConnection->query("SELECT f.FamilyID FROM Families f WHERE f.FamilyID = $FamilyID $Where");
                     if (!DB::isError($DbResult))
                     {
                         if ($DbResult->numRows() > 0)
                         {
                             $tmp .= generateStyledPicture($GLOBALS["CONF_ANNUAL_CONTRIBUTION_NOT_PAID_ICON"], $GLOBALS['LANG_PB_ANNUAL_CONTRIBUTION_PAYMENTS'], "");
                         }
                     }
                 }

                 // Check if the family has a good cooperation indicator
                 if ((array_key_exists("FamilyCoopContribution", $ArrayParams)) && (!empty($ArrayParams["FamilyCoopContribution"])))
                 {
                     // Concerned school year
                     $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["FamilyCoopContribution"]);
                     $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["FamilyCoopContribution"]);

                     $Select = '';
                     $From = '';
                     $Where = '';
                     foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS'] as $c => $NbMinCoop)
                     {
                         // We keep only valided registrations
                         $Select .= ", Tev$c.NB$c";
                         $From .= ", (SELECT er$c.FamilyID, COUNT(er$c.EventRegistrationID) AS NB$c
                                      FROM Events e$c, EventTypes et$c, EventRegistrations er$c WHERE e$c.EventTypeID = et$c.EventTypeID
                                      AND et$c.EventTypeCategory = $c AND e$c.EventID = er$c.EventID AND er$c.FamilyID = $FamilyID
                                      AND e$c.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                                      AND er$c.EventRegistrationValided = 1
                                      GROUP BY er$c.FamilyID HAVING NB$c >= $NbMinCoop) AS Tev$c";
                         $Where .= " AND f.FamilyID = Tev$c.FamilyID";
                     }

                     $DbResult = $DbConnection->query("SELECT f.FamilyID $Select FROM Families f $From WHERE f.FamilyID = $FamilyID $Where");

                     if (!DB::isError($DbResult))
                     {
                         if ($DbResult->numRows() > 0)
                         {
                             $sNbCoops = "";
                             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                             {
                                 foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS'] as $c => $NbMinCoop)
                                 {
                                     if (!empty($sNbCoops))
                                     {
                                         $sNbCoops .= " / ";
                                     }

                                     $sNbCoops .= $Record["NB$c"];
                                 }
                             }

                             // Good cooperation for events
                             $tmp .= generateStyledPicture($GLOBALS["CONF_EVENT_COOPERATION_OK_ICON"], $GLOBALS['LANG_GOOD_EVENT_COOPERATION']." ($sNbCoops).", "");
                         }
                         else
                         {
                             // Too low cooperation for events
                             $tmp .= generateStyledPicture($GLOBALS["CONF_EVENT_COOPERATION_NOK_ICON"], $GLOBALS['LANG_TOO_LOW_EVENT_COOPERATION'], "");
                         }
                     }
                 }
                 break;
         }

         return $tmp;
     }

     return "";
 }


/**
 * Generate legends of the visual indicators about tables of asks of work in the current
 * row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-09-23
 *
 * @param $ArrayIcons    Mixed array       Contains icons and text about icons
 * @param $Mode          Const             Mode to display the legends (ICON or CSS_STYLE)
 *
 * @return String                          Legends of the visual indicators of the tables of
 *                                         asks of work or else, empty string otherwise
 */
 function generateLegendsOfVisualIndicators($ArrayIcons, $Mode = ICON)
 {
     if (count($ArrayIcons) > 0)
     {
         switch($Mode)
         {
             case CSS_STYLE:
                 // Display the legends with CSS styles
                 $Legend = "<ul class=\"TableLegendCSS_Style\">";
                 foreach($ArrayIcons as $i => $CurrentLegend)
                 {
                     // $CurrentLegent = array(CSS style, text)
                     $Legend .= "<li>".generateStyledText($CurrentLegend[1], $CurrentLegend[0])."</li>";
                 }
                 $Legend .= "</ul>";
                 break;

             case ICON:
             default:
                 // Display the legends with icons
                 $Legend = "<ul class=\"TableLegend\">";
                 foreach($ArrayIcons as $i => $CurrentLegend)
                 {
                     // $CurrentLegent = array(picture, text)
                     $Legend .= "<li>".generateStyledPicture($CurrentLegend[0], '', '')." ".$CurrentLegend[1]."</li>";
                 }
                 $Legend .= "</ul>";
                 break;
         }

         return $Legend;
     }

     // Error
     return '';
 }


/**
 * Display a confirmation (yes/no) form in the current row of the table of the web page, in the graphic interface
 * in XHTML
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2007-01-23 : new interface
 *
 * @since 2005-11-16
 *
 * @param $Title                String                Title of the form
 * @param $Msg                  String                Message which contains the question to answer
 * @param $ProcessFormPage      String                URL of the page which will process the form
 */
 function displayConfirmationForm($Title, $Msg, $ProcessFormPage)
 {
     if ((isSet($_SESSION["SupportMemberID"])) || (isSet($_SESSION["CustomerID"])))
     {
         // Open a form
         openForm("FormConfirm", "post", $ProcessFormPage, "", "");

         // Display the table (frame) where the form will take place
         openFrame($Title);
         displayStyledText($Msg, "ConfirmationMsg");
         closeFrame();

         // Display the buttons
         echo "<table class=\"validation\">\n<tr>\n\t<td>";
         insertInputField("bSubmitYes", "submit", "", "", $GLOBALS["LANG_YES"], $GLOBALS["LANG_YES"]);
         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
         insertInputField("bSubmitNo", "submit", "", "", $GLOBALS["LANG_NO"], $GLOBALS["LANG_NO"]);
         echo "</td>\n</tr>\n</table>\n";

         closeForm();
     }
     else
     {
         // ERROR : the supporter/customer isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Generate a progress visual indicator
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2011-07-21 : add a title to display info on the progress info
 *
 * @since 2010-12-02
 *
 * @param $Progress           Float             % progress [0..100]
 * @param $InitialLoad        Float             Estimated load to done when starting (>= 0)
 * @param $LoadToDone         Float             Load still to done (>= 0)
 * @param $LoadDone           Float             Load already done (>=0)
 *
 * @return String             Visual indicator of the progress, an empty string otherwise
 */
 function generateProgressVisualIndicator($Progress, $InitialLoad = NULL, $LoadToDone = NULL, $LoadDone = NULL, $Title = '')
 {
     $sIndicator = '';
     if ((is_null($Progress)) && (!is_null($InitialLoad)) && (!is_null($LoadToDone)) && (!is_null($LoadDone)))
     {
         // Progress not known : we compute it
         $MaxLoad = max($InitialLoad, $LoadToDone + $LoadDone);

         // Progress between 0 and 100
         $Progress = round(($LoadToDone / $MaxLoad) * 100, 2);
     }

     // Check if there is a title
     $sTitleInfo = '';
     if (!empty($Title))
     {
         $sTitleInfo = " title=\"$Title\"";
     }

     if (($Progress >= 0) && ($Progress < 34))
     {
         // % done [0 ; 33%]
         $sIndicator = "<p class=\"UOProgress0_33\"$sTitleInfo>$Progress%</p>";
     }
     elseif (($Progress > 33 ) && ($Progress < 67))
     {
         // % done [34 ; 66%]
         $sIndicator = "<p class=\"UOProgress34_66\"$sTitleInfo>$Progress%</p>";
     }
     elseif (($Progress > 66) && ($Progress < 100))
     {
         // % done [67 ; 99%]
         $sIndicator = "<p class=\"UOProgress67_99\"$sTitleInfo>$Progress%</p>";
     }
     elseif ($Progress >= 100)
     {
         // 100% done
         if ($InitialLoad < $LoadDone)
         {
             // > 100% done (overload)
             $sIndicator = "<p class=\"UOProgressOver100\"$sTitleInfo>$Progress%</p>";
         }
         else
         {
             $sIndicator = "<p class=\"UOProgress100\"$sTitleInfo>$Progress%</p>";
         }
     }

     return $sIndicator;
 }
?>