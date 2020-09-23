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
 * Interface module : XHTML Graphic primitives forms library used to create forms (input, select, textarea tags)
 *
 * @author STNA/7SQ
 * @version 3.5
 * @since 2003-12-26
 */


/**
 * Generate an opening form in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-03-21
 *
 * @param $Name          String            Name of the form
 * @param $Method        String            Method to submit the form (GET or POST)
 * @param $Action        String            URL of the page to submit the form
 * @param $Enctype       String            Type of data encoding
 * @param $OnSubmitFct   String            Name of the control function (with the '(' and ')' ) called to validate the content of the form
 *
 * @return String                          XHTML form tag
 */
 function generateOpenForm($Name, $Method, $Action, $Enctype = "", $OnSubmitFct = "")
 {
     $tmp = "<form name=\"$Name\" method=\"$Method\" action=\"$Action\"";

     if ($Enctype != "")
     {
         $tmp .= " enctype=\"$Enctype\"";
     }

     if ($OnSubmitFct != "")
     {
         $tmp .= " onSubmit=\"return $OnSubmitFct\"";
     }

     return "$tmp>\n";
 }


/**
 * Open a form in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2008-03-21 : use generateOpenForm to display the opening form
 *
 * @since 2003-12-26
 *
 * @param $Name          String            Name of the form
 * @param $Method        String            Method to submit the form (GET or POST)
 * @param $Action        String            URL of the page to submit the form
 * @param $Enctype       String            Type of data encoding
 * @param $OnSubmitFct   String            Name of the control function (with the '(' and ')' ) called to validate the content of the form
 */
 function openForm($Name, $Method, $Action, $Enctype = "", $OnSubmitFct = "")
 {
     echo generateOpenForm($Name, $Method, $Action, $Enctype, $OnSubmitFct);
 }


/**
 * Insert an input form field in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.2
 *     - 2004-04-16 : taken into account JavaScript code
 *     - 2007-03-28 : use the generateInputField() function
 *     - 2009-03-25 : allow to add a style
 *
 * @since 2003-12-26
 *
 * @param $Name          String            Name of the input field
 * @param $Type          String            Type on the input field
 * @param $Maxlength     String            Maxlength of the input field (in characters)
 * @param $Size          String            Size on the input field (in characters)
 * @param $Title         String            Tip text
 * @param $Value         String            Default value of the input field
 * @param $Readonly      Boolean           TRUE if the input field has to be in readonly mode, FALSE otherwise
 * @param $Checked       Boolean           TRUE if the input field is a checkbox and has to be checked, FALSE otherwise
 * @param $JavaScript    String            JavaScript code
 * @param $Style         String            CSS style
 */
 function insertInputField($Name, $Type, $Maxlength, $Size, $Title, $Value, $Readonly = FALSE, $Checked = FALSE, $JavaScript = '', $Style = '')
 {
     echo generateInputField($Name, $Type, $Maxlength, $Size, $Title, $Value, $Readonly, $Checked, $JavaScript, $Style);
 }


/**
 * Generate a string which contains an input form field in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.2
 *     - 2004-04-16 : taken into account JavaScript code
 *     - 2009-03-25 : allow to add a style
 *
 * @since 2004-03-17
 *
 * @param $Name          String            Name of the input field
 * @param $Type          String            Type on the input field
 * @param $Maxlength     String            Maxlength of the input field (in characters)
 * @param $Size          String            Size on the input field (in characters)
 * @param $Title         String            Tip text
 * @param $Value         String            Default value of the input field
 * @param $Readonly      Boolean           TRUE if the input field has to be in readonly mode, FALSE otherwise
 * @param $Checked       Boolean           TRUE if the input field is a checkbox and has to be checked, FALSE otherwise
 * @param $JavaScript    String            JavaScript code
 * @param $Style         String            CSS style
 *
 * @return String                          XHTML input tag
 */
 function generateInputField($Name, $Type, $Maxlength, $Size, $Title, $Value, $Readonly = FALSE, $Checked = FALSE, $JavaScript = '', $Style = '')
 {
     $tmp = '';
     if ($Name != '')
     {
         if (!empty($Style))
         {
             $Style .= ' ';
         }

         if ($Readonly)
         {
             $tmp = "<input class=\"".$Style."Readonly\" id=\"$Name\" name=\"$Name\" type=\"$Type\"";
         }
         else
         {
             $tmp = "<input class=\"$Style$Type\" id=\"$Name\" name=\"$Name\" type=\"$Type\"";
         }

         if ($Maxlength != '')
         {
             $tmp .= " maxlength=\"$Maxlength\"";
         }

         if ($Size != '')
         {
             $tmp .= " size=\"$Size\"";
         }

         if ($Title != '')
         {
             $tmp .= " title=\"$Title\"";
         }

         $tmp .= " value=\"$Value\"";

         if ($Readonly)
         {
             $tmp .= " readonly=\"readonly\"";
         }

         if ($Checked)
         {
             $tmp .= " checked=\"checked\"";
         }

         if ($JavaScript != '')
         {
             $tmp .= " $JavaScript";
         }

         $tmp .= " />";
     }

     return $tmp;
 }


/**
 * Insert a select form field in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.1
 *     - 2007-01-12 : new interface
 *     - 2007-05-25 : allow to use different styles for the items ($TabStyles)
 *     - 2010-12-08 : allow to use a style for the select field ($Style)
 *
 * @since 2004-01-03
 *
 * @param $Name                 String                Name of the select field
 * @param $TabValues            Array of Strings      List of values of the <option> of the select field.
 * @param $TabCaptions          Array of Strings      List of captions of the <option> of the select field.
 * @param $ItemSelected         Integer               Value of the selected item [1..n]
 * @param $OnChangeFct          String                Name of the control function (with the '(' and ')' )
 *                                                    called when the selected item changes
 * @param $TabStyles            Array of Strings      List of styles of the <option> to use
 * @param $Style                String                CSS style
 */
 function insertSelectField($Name, $TabValues, $TabCaptions, $ItemSelected = '', $OnChangeFct = '', $TabStyles = array(), $Style = '')
 {
     echo generateSelectField($Name, $TabValues, $TabCaptions, $ItemSelected, $OnChangeFct, $TabStyles, $Style);
 }


/**
 * Generate a select form field in the current row of the table of the web page, in the graphic interface
 * in XHTML
 *
 * @author STNA/7SQ
 * @version 2.1
 *     - 2007-01-12 : new interface
 *     - 2007-05-25 : allow to use different styles for the items ($TabStyles)
 *     - 2010-12-08 : allow to use a style for the select field ($Style)
 *
 * @since 2004-03-17
 *
 * @param $Name                 String                Name of the select field
 * @param $TabValues            Array of Strings      List of values of the <option> of the select field.
 * @param $TabCaptions          Array of Strings      List of captions of the <option> of the select field.
 * @param $ItemSelected         Integer               Value of the selected item [1..n]
 * @param $OnChangeFct          String                Name of the control function (with the '(' and ')' )
 *                                                    called when the selected item changes
 * @param $TabStyles            Array of Strings      List of styles of the <option> to use
 * @param $Style                String                CSS style
 *
 * @return String                                     XHTML select tag
 */
 function generateSelectField($Name, $TabValues, $TabCaptions, $ItemSelected = '', $OnChangeFct = '', $TabStyles = array(), $Style = '')
 {
     $tmp = '';
     $NbValues = count($TabValues);
     if (($Name != '') && ($NbValues > 0) && ($NbValues == count($TabCaptions)))
     {
         // Create the <select>
         $tmp =  "<select id=\"$Name\" name=\"$Name\"";

         if (!empty($Style))
         {
             // Style of the select field
             $tmp .= " style=\"$Style\"";
         }

         if (!empty($OnChangeFct))
         {
             $tmp .= " onChange=\"$OnChangeFct\"";
         }

         $tmp .= ">\n";

         // We check if we use styles for each <option>
         if ($NbValues == count($TabStyles))
         {
             // We use a style for each <option>
             // Create the list of <option>
             foreach($TabValues as $i => $CurrentValue)
             {
                 if ($TabStyles[$i] != '')
                 {
                     // There is a style to use
                     $tmp .= "\t<option value=\"$CurrentValue\" class=\"".$TabStyles[$i]."\"";
                 }
                 else
                 {
                     // No style for this item
                     $tmp .= "\t<option value=\"$CurrentValue\"";
                 }

                 // Select the item
                 if ($CurrentValue == $ItemSelected)
                 {
                     $tmp .= " selected=\"selected\"";
                 }

                 $tmp .= '>'.nullFormatText($TabCaptions[$i])."</option>\n";
             }
         }
         else
         {
             // No styles
             // Create the list of <option>
             foreach($TabValues as $i => $CurrentValue)
             {
                 $tmp .= "\t<option value=\"$CurrentValue\"";

                 // Select the item
                 if ($CurrentValue == $ItemSelected)
                 {
                     $tmp .= " selected=\"selected\"";
                 }

                 $tmp .= '>'.nullFormatText($TabCaptions[$i])."</option>\n";
             }
         }

         // Close the <select>
         $tmp .= "</select>\n";
     }

     return $tmp;
 }


/**
 * Insert an OptGroup select form field in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.2
 *     - 2010-10-20 : allow to use different styles for the items ($TabStyles)
 *     - 2010-12-08 : allow to use a style for the select field ($Style)
 *
 * @since 2007-03-28
 *
 * @param $Name                 String              Name of the select field
 * @param $TabData              Mixed array         List of values/captions of the <option> of the select field
 *                                                  for each opt group
 * @param $ItemSelected         Integer             Value of the selected item [1..n]
 * @param $OnChangeFct          String              Name of the control function (with the '(' and ')' ) called
 *                                                  when the selected item changes
 * @param $TabStyles            Array of Strings    List of styles of the <option> to use
 * @param $Style                String              CSS style
 *
 * @return String                                   XHTML optgroup select tag
 */
 function insertOptGroupSelectField($Name, $TabData, $ItemSelected = '', $OnChangeFct = '', $TabStyles = array(), $Style = '')
 {
     echo generateOptGroupSelectField($Name, $TabData, $ItemSelected, $OnChangeFct, $TabStyles, $Style);
 }


/**
 * Generate an OptGroup select form field in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.2
 *     - 2010-10-20 : allow to use different styles for the items ($TabStyles)
 *     - 2010-12-08 : allow to use a style for the select field ($Style)
 *
 * @since 2007-03-28
 *
 * @param $Name                 String              Name of the select field
 * @param $TabData              Mixed array         List of values/captions of the <option> of the select field
 *                                                  for each opt group
 * @param $ItemSelected         Integer             Value of the selected item [1..n]
 * @param $OnChangeFct          String              Name of the control function (with the '(' and ')' ) called
 *                                                  when the selected item changes
 * @param $TabStyles            Array of Strings    List of styles of the <option> to use
 * @param $Style                String              CSS style
 *
 * @return String                                   XHTML optgroup select tag
 */
 function generateOptGroupSelectField($Name, $TabData, $ItemSelected = '', $OnChangeFct = '', $TabStyles = array(), $Style = '')
 {
     $tmp = '';
     $NbData = count($TabData);
     if (($Name != '') && ($NbData > 0))
     {
         // Create the <select>
         $tmp =  "<select id=\"$Name\" name=\"$Name\"";

         if (!empty($Style))
         {
             // Style of the select field
             $tmp .= " style=\"$Style\"";
         }

         if (!empty($OnChangeFct))
         {
             $tmp .= " onChange=\"$OnChangeFct\"";
         }

         $tmp .= ">\n";

         // We count data for each key of the array
         $NbTotalData = 0;
         foreach($TabData as $k => $ArrayData)
         {
             $NbTotalData += count($ArrayData);
         }

         // We check if we use styles for each <option>
         if ($NbTotalData == count($TabStyles))
         {
             // We use a style for each <option>
             // Create the list of <optgroup>
             foreach($TabData as $OptGroupLabel => $CurrentArrayOptions)
             {
                 // We check if the opt group label is empty
                 if ((empty($OptGroupLabel)) || (empty($CurrentArrayOptions)))
                 {
                     // The opt group is empty : we display a normal <option>
                     // The value is the opt group label
                     // The caption is the current array options
                     $tmp .= "\t<option value=\"$OptGroupLabel\"";

                     if ($TabStyles[$OptGroupLabel] != '')
                     {
                         // There is a style to use
                         $tmp .= " class=\"".$TabStyles[$OptGroupLabel]."\"";
                     }

                     // Select the item
                     if ($OptGroupLabel == $ItemSelected)
                     {
                         $tmp .= " selected=\"selected\"";
                     }

                     $tmp .= ">".nullFormatText($CurrentArrayOptions)."</option>\n";
                 }
                 else
                 {
                     // The opt group label isn't empty
                     $tmp .= "\t<optgroup label=\"$OptGroupLabel\">\n";

                     // Create the list of <option>
                     foreach($CurrentArrayOptions as $CurrentValue => $CurrentCaption)
                     {
                         $tmp .= "\t\t<option value=\"$CurrentValue\"";

                         if ($TabStyles[$CurrentValue] != '')
                         {
                             // There is a style to use
                             $tmp .= " class=\"".$TabStyles[$CurrentValue]."\"";
                         }

                         // Select the item
                         if ($CurrentValue == $ItemSelected)
                         {
                             $tmp .= " selected=\"selected\"";
                         }

                         $tmp .= ">".nullFormatText($CurrentCaption)."</option>\n";
                     }

                     $tmp .= "\t</optgroup>\n";
                 }
             }
         }
         else
         {
             // No styles
             foreach($TabData as $OptGroupLabel => $CurrentArrayOptions)
             {
                 // We check if the opt group label is empty
                 if ((empty($OptGroupLabel)) || (empty($CurrentArrayOptions)))
                 {
                     // The opt group is empty : we display a normal <option>
                     // The value is the opt group label
                     // The caption is the current array options
                     $tmp .= "\t<option value=\"$OptGroupLabel\"";

                     // Select the item
                     if ($OptGroupLabel == $ItemSelected)
                     {
                         $tmp .= " selected=\"selected\"";
                     }

                     $tmp .= ">".nullFormatText($CurrentArrayOptions)."</option>\n";
                 }
                 else
                 {
                     // The opt group label isn't empty
                     $tmp .= "\t<optgroup label=\"$OptGroupLabel\">\n";

                     // Create the list of <option>
                     foreach($CurrentArrayOptions as $CurrentValue => $CurrentCaption)
                     {
                         $tmp .= "\t\t<option value=\"$CurrentValue\"";

                         // Select the item
                         if ($CurrentValue == $ItemSelected)
                         {
                             $tmp .= " selected=\"selected\"";
                         }

                         $tmp .= ">".nullFormatText($CurrentCaption)."</option>\n";
                     }

                     $tmp .= "\t</optgroup>\n";
                 }
             }
         }

         // Close the <select>
         $tmp .= "</select>\n";
     }

     return $tmp;
 }


/**
 * Insert a multiple select form field in the current row of the table of the web page, in the graphic
 * interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2007-01-12 : new interface and use the generateMultipleSelectField() function
 *
 * @since 2005-02-10
 *
 * @param $Name                 String                Name of the select field
 * @param $TabValues            Array of Strings      List of values of the <option> of the select field.
 * @param $TabCaptions          Array of Strings      List of captions of the <option> of the select field.
 * @param $Size                 Integer               Number of visible items [1..n]
 * @param $ArrayItemsSelected   Array of Integers     Values of the selected items [1..n]
 * @param $OnChangeFct          String                Name of the control function (with the '(' and ')' ) called when the selected item changes
 */
 function insertMultipleSelectField($Name, $TabValues, $TabCaptions, $Size = 2, $ArrayItemsSelected = array(), $OnChangeFct = '')
 {
     echo generateMultipleSelectField($Name, $TabValues, $TabCaptions, $Size, $ArrayItemsSelected, $OnChangeFct);
 }


/**
 * Generate a multiple select form field in the current row of the table of the web page, in the graphic
 * interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2007-01-12 : new interface
 *
 * @since 2005-02-10
 *
 * @param $Name                 String                Name of the select field
 * @param $TabValues            Array of Strings      List of values of the <option> of the select field.
 * @param $TabCaptions          Array of Strings      List of captions of the <option> of the select field.
 * @param $Size                 Integer               Number of visible items [1..n]
 * @param $ArrayItemsSelected   Array of Integers     Values of the selected items [1..n]
 * @param $OnChangeFct          String                Name of the control function (with the '(' and ')' ) called when the selected item changes
 *
 * @return String                                     XHTML select tag
 */
 function generateMultipleSelectField($Name, $TabValues, $TabCaptions, $Size = 2, $ArrayItemsSelected = array(), $OnChangeFct = '')
 {
     $tmp = '';
     $NbValues = count($TabValues);
     if (($Name != '') && ($NbValues > 0) && ($NbValues == count($TabCaptions)) && ($Size > 0))
     {
         // Create the <select>
         $tmp = "<select id=\"$Name\" name=\"$Name"."[]"."\" multiple=\"multiple\" size=\"$Size\"";

         if ($OnChangeFct != '')
         {
             $tmp .= " onChange=\"$OnChangeFct\"";
         }

         $tmp .= ">\n";

         // Create the list of <option>
         foreach($TabValues as $i => $CurrentValue)
         {
             $tmp .= "\t<option value=\"$CurrentValue\"";

             // Select the item
             if (in_array($CurrentValue, $ArrayItemsSelected))
             {
                 $tmp .= " selected=\"selected\"";
             }

             $tmp .= ">".nullFormatText($TabCaptions[$i])."</option>\n";
         }

         // Close the <select>
         $tmp .= "</select>\n";
     }

     return $tmp;
 }


/**
 * Insert an OptGroup multiple select form field in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-03-19
 *
 * @param $Name                 String             Name of the select field
 * @param $TabData              Mixed array        List of values/captions of the <option> of the select field
 *                                                 for each opt group
 * @param $ItemSelected         Integer            Value of the selected item [1..n]
 * @param $OnChangeFct          String             Name of the control function (with the '(' and ')' ) called
 *                                                 when the selected item changes
 */
 function insertOptGroupMultipleSelectField($Name, $TabData, $Size = 2, $ArrayItemsSelected = array(), $OnChangeFct = '')
 {
     echo generateOptGroupMultipleSelectField($Name, $TabData, $Size, $ArrayItemsSelected, $OnChangeFct);
 }


/**
 * Generate an OptGroup multiple select form field in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-03-19
 *
 * @param $Name                 String             Name of the select field
 * @param $TabData              Mixed array        List of values/captions of the <option> of the select field
 *                                                 for each opt group
 * @param $ArrayItemsSelected   Integer            Value of the selected item [1..n]
 * @param $OnChangeFct          String             Name of the control function (with the '(' and ')' ) called
 *                                                 when the selected item changes
 *
 * @return String                                  XHTML optgroup multiple select tag
 */
 function generateOptGroupMultipleSelectField($Name, $TabData, $Size = 2, $ArrayItemsSelected = array(), $OnChangeFct = '')
 {
     $tmp = '';
     $NbData = count($TabData);
     if (($Name != '') && ($NbData > 0) && ($Size > 0))
     {
         // Create the <select>
         $tmp = "<select id=\"$Name\" name=\"$Name"."[]"."\" multiple=\"multiple\" size=\"$Size\"";

         if ($OnChangeFct != '')
         {
             $tmp .= " onChange=\"$OnChangeFct\"";
         }

         $tmp .= ">\n";

         // Create the list of <optgroup>
         foreach($TabData as $OptGroupLabel => $CurrentArrayOptions)
         {
             // We check if the opt group label is empty
             if ((empty($OptGroupLabel)) || (empty($CurrentArrayOptions)))
             {
                 // The opt group is empty : we display a normal <option>
                 // The value is the opt group label
                 // The caption is the current array options
                 $tmp .= "\t<option value=\"$OptGroupLabel\"";

                 // Select the item
                 if (in_array($OptGroupLabel, $ArrayItemsSelected))
                 {
                     $tmp .= " selected=\"selected\"";
                 }

                 $tmp .= ">".nullFormatText($CurrentArrayOptions)."</option>\n";
             }
             else
             {
                 // The opt group label isn't empty
                 $tmp .= "\t<optgroup label=\"$OptGroupLabel\">\n";

                 // Create the list of <option>
                 foreach($CurrentArrayOptions as $CurrentValue => $CurrentCaption)
                 {
                     $tmp .= "\t\t<option value=\"$CurrentValue\"";

                     // Select the item
                     if (in_array($CurrentValue, $ArrayItemsSelected))
                     {
                         $tmp .= " selected=\"selected\"";
                     }

                     $tmp .= ">".nullFormatText($CurrentCaption)."</option>\n";
                 }

                 $tmp .= "\t</optgroup>\n";
             }
         }

         // Close the <select>
         $tmp .= "</select>\n";
     }

     return $tmp;
 }


/**
 * Insert a textarea form field in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.1
 *     - 2006-01-22 : do not taken into account the $Cols parameter when
 *                    $Rows parameter is > 5
 *     - 2007-03-28 : use the generateTextareaField() function
 *
 * @since 2003-01-09
 *
 * @param $Name          String            Name of the input field
 * @param $Rows          Integer           Number of rows
 * @param $Cols          Integer           Number of columns (in charachers)
 * @param $Title         String            Tip text
 * @param $Value         String            Default value of the input field
 */
 function insertTextareaField($Name, $Rows, $Cols, $Title, $Value)
 {
     echo generateTextareaField($Name, $Rows, $Cols, $Title, $Value);
 }


/**
 * Generate a textarea form field in the current row of the table of the web page, in the graphic interface
 * in XHTML
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2006-01-22 : do not taken into account the $Cols parameter when
 *                    $Rows parameter is > 5
 *
 * @since 2004-03-17
 *
 * @param $Name          String            Name of the input field
 * @param $Rows          Integer           Number of rows
 * @param $Cols          Integer           Number of columns (in charachers)
 * @param $Title         String            Tip text
 * @param $Value         String            Default value of the input field
 *
 * @return String                   XHTML textarea tag
 */
 function generateTextareaField($Name, $Rows, $Cols, $Title, $Value)
 {
     $tmp = '';
     if ($Name != '')
     {
         $tmp = "<textarea id=\"$Name\" name=\"$Name\" rows=\"$Rows\"";

         if ((integer)$Rows > 5) {
             // Big textarea : must be very large
             $tmp .= " class=\"BigTextarea\"";
         } else {
             // Tiny textarea : we use the $Cols parameter
             $tmp .= " cols=\"$Cols\"";
         }

         if ($Title != '')
         {
             $tmp .= " title=\"$Title\"";
         }

         $tmp .= ">$Value</textarea>" ;
     }

     return $tmp;
 }


/**
 * Generate the closing current form in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-03-21
 */
 function generateCloseForm()
 {
     return "</form>\n";
 }


/**
 * Close the current form in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2003-12-26
 */
 function closeForm()
 {
     echo "</form>\n";
 }
?>