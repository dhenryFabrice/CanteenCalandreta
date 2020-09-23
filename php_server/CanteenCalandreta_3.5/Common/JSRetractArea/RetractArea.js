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
 * Retractible area : cross-browers retractible area (visible or not)
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2006-01-26
 */


 var RetractArea_layers = new Array();


/**
 * Constructor of the retractible area
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2006-01-26
 *
 * @param $Name           String     Name of the retractible area
 * @param $Caption        String     text displayed before the picture used to
 *                                   display/hide the retractible area
 * @param $Width          Integer    Width (in pixels) of the retractible area
 * @param $Height         Integer    Height (in pixels) of the retractible area
 * @param $ContentArea    String     Content of the retractible area
 * @param $ImagePath      String     Path and name of the picture used to display/hide
 *                                   the retractible area
 * @param $Tip            String     Tip displayed on the picture
 * @param $bDisplayed     Integer    0->area not displayed, 1-> area displayed
 */
 function RetractArea(Name, Caption, Width, Height, ContentArea, ImagePath, Tip, bDisplayed)
 {
     // Attributes
     this.objName          = Name;
     this.AreaCaption      = Caption;
     this.AreaWidth        = Width;
     this.AreaHeight       = Height;
     this.Content          = ContentArea;
     this.Path             = ImagePath;
     this.TootTip          = Tip;
     this.isDisplayed      = bDisplayed;

     // Methods
     this.displayArea      = displayRetractArea;

     var htmlCode = '';

     // Display the button to display or hide the retractible area
     htmlCode += Caption + ' <a class="retractArealink" href="javascript:' + this.objName + '.displayArea()"><img src="' + this.Path + '" title="' + this.TootTip +'"/></a><br />';
     document.write(htmlCode);

     if (ns4)
     {
         CreerStaticObj(this.objName, 1, 1, this.AreaWidth, this.AreaHeight, this.isDisplayed, 2, this.Content, 'class="RetractArea"', 0);
     }
     else
     {
         CreerStaticObj(this.objName, 1, 1, this.AreaWidth, this.AreaHeight, this.isDisplayed, 2, this.Content, 'class="RetractArea"', 0);
     }

     // We add this retractible area in the array
     RetractArea_layers[RetractArea_layers.length] = this;
 }


/**
 * Display or hide a retractible area
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2006-01-26
 */
 function displayRetractArea()
 {
     if (VisibiliteObj(this.objName))
     {
         CacherStaticObj(this.objName);
     }
     else
     {
         VoirStaticObj(this.objName);
     }
 }

