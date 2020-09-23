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
 * JS plugin Textarea module : add some functions to each textarea of the
 * current web page
 *
 * @author STNA/7SQ
 * @version 3.4
 * @since 2008-02-07
 */


/**
 * Function used to init this plugin
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2010-03-18 : taken nto account the "NoToolbar" mode
 *
 * @since 2008-02-07
 *
 * @param ImagePath    String    Path of the images used by the plugin
 * @param Mode         String    Running mode of the plugin
 */
 function initTextareaPlugin(ImagePath, Mode)
 {
     // We get all textarea of the current page
     var ArrayTextarea = document.getElementsByTagName('textarea');

     // for each, we add some new functions
     for(var i = 0; i < ArrayTextarea.length; i++) {
         var objParent = ArrayTextarea[i].parentNode;

         if (Mode == 'Writer') {
             // Writer is a JS of Astres for reporting (Com module)
             // We change some styles of the DIV
             var objDivTmp = document.createElement('div');
             objDivTmp.id = objParent.id;

             // We get the content of the textarea
             var TextValue = '';
             if (window.attachEvent) {
                 TextValue = ArrayTextarea[i].value;   // IE
             } else {
                 TextValue = ArrayTextarea[i].textContent;   // FF
             }

             // Hide the parent DIV of the textarea if it's empty
             if (TextValue == '') {
                 objDivTmp.style.display = 'none';
             }
             else {
                 objDivTmp.style.display = 'block';
             }

             objDivTmp.innerHTML = objParent.innerHTML;
             objParent.parentNode.replaceChild(objDivTmp, objParent);
             objParent = objDivTmp;
         }

         if(window.attachEvent) {
             ArrayTextarea[i].attachEvent("onfocus", TextareaPluginFocus);  // IE
             ArrayTextarea[i].attachEvent("onblur", TextareaPluginBlur);
         } else {
             ArrayTextarea[i].addEventListener("focus", TextareaPluginFocus, false);  // FF
             ArrayTextarea[i].addEventListener("blur", TextareaPluginBlur, false);
         }

         var objToolBar = document.createElement("ul");
         objToolBar.className = "JSTextareaPlugin";

         var ArrayObjNewFcts = new Array();

         // New function to grow the height of the textarea
         var objNewFct = document.createElement("li");
         objNewFct.innerHTML = '<img src="' + ImagePath + 'picto_police_plus.gif" title="+" alt="+" />';
         if(window.attachEvent) {
             // IE
             if (Mode == 'Writer') {
                 objNewFct.attachEvent("onclick", TextareaPluginClickPlusWriter);
             } else {
                 objNewFct.attachEvent("onclick", TextareaPluginClickPlus);
             }
         } else {
             // FF
             if (Mode == 'Writer') {
                 objNewFct.addEventListener("click", TextareaPluginClickPlusWriter, false);
             } else {
                 objNewFct.addEventListener("click", TextareaPluginClickPlus, false);
             }
         }
         ArrayObjNewFcts.push(objNewFct);

         // New function to grow the height of the textarea
         objNewFct = document.createElement("li");
         objNewFct.innerHTML = '<img src="' + ImagePath + 'picto_police_moins.gif" title="-" alt="-" />';
         if(window.attachEvent) {
             // IE
             if (Mode == 'Writer') {
                 objNewFct.attachEvent("onclick", TextareaPluginClickMoinsWriter);
             } else {
                 objNewFct.attachEvent("onclick", TextareaPluginClickMoins);
             }
         } else {
             // FF
             if (Mode == 'Writer') {
                 objNewFct.addEventListener("click", TextareaPluginClickMoinsWriter, false);
             } else {
                 objNewFct.addEventListener("click", TextareaPluginClickMoins, false);
             }
         }
         ArrayObjNewFcts.push(objNewFct);

         for(var j = 0; j < ArrayObjNewFcts.length; j++) {
             ArrayObjNewFcts[j].className = "JSTextareaPlugin";
             objToolBar.appendChild(ArrayObjNewFcts[j]);
         }

         switch(Mode)
         {
             case 'NoToolbar':
                 // No toolbar
                 ArrayTextarea[i].className = 'JSTextareaNoToolbarPlugin';
                 break;

             default:
                 objParent.insertBefore(objToolBar, objParent.firstChild);
                 break;
         }
     }
 }


 function TextareaPluginClickPlus(evt)
 {
     var obj = evt.target || evt.srcElement;

     var objTextArea = obj.parentNode.parentNode.parentNode.getElementsByTagName('textarea')[0];
     objTextArea.focus();
     objTextArea.rows += 2;
 }

 function TextareaPluginClickMoins(evt)
 {
     var obj = evt.target || evt.srcElement;

     var objTextArea = obj.parentNode.parentNode.parentNode.getElementsByTagName('textarea')[0];
     objTextArea.focus();

     if (objTextArea.rows > 4) {
         objTextArea.rows -= 2;
     }
 }


 function TextareaPluginClickPlusWriter(evt)
 {
     var obj = evt.target || evt.srcElement;

     var objTextArea = obj.parentNode.parentNode.parentNode.getElementsByTagName('textarea')[0];
     objTextArea.focus();
     objTextArea.rows += 4;
     objTextArea.cols += 6;
 }


 function TextareaPluginClickMoinsWriter(evt)
 {
     var obj = evt.target || evt.srcElement;

     var objTextArea = obj.parentNode.parentNode.parentNode.getElementsByTagName('textarea')[0];
     objTextArea.focus();

     if (objTextArea.rows > 10) {
         objTextArea.rows -= 4;
         objTextArea.cols -= 6;
     }
 }


 function TextareaPluginFocus(evt)
 {
     var obj = evt.target || evt.srcElement;
     obj.style.background = '#ebebff';
 }


 function TextareaPluginBlur(evt)
 {
     var obj = evt.target || evt.srcElement;
     obj.style.background = '#fff';
 }
