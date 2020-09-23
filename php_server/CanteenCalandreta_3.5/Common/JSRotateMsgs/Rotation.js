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
 *
 ***************************************************************************
 *
 * based on a script donwloaded at www.script-masters.com
 */


/**
 * Common module : function used to display in loop short messages
 *
 * @author STNA/7SQ
 * @version 3.1
 * @since 2005-01-27
 */


 var msgRotateSpeed = 4000;  // Delay between 2 displayed messages
 var textStr = new Array();  // Array which contains the messages
 var MsgID = 0;              // ID of the message to display
 var msgToDisplay = "";      // The message to display
 var iStyleIndexMsg = 0;     // The style to use to display the message


/**
 * Function used to init the array containing the messages to display.
 *
 * @author STNA/7SQ
 * @version 1.1
 *    - 2009-01-23 : allow to display messages with 2 different styles
 *
 * @since 2005-01-27
 *
 * @param ListMsgs       String       List of the messages to display, separated by a "#"
 */
 function initMsgs(ListMsgs)
 {
     // Initialization of the messages to display
     if (ListMsgs != "")
     {
         textStr = ListToArray(ListMsgs);

         // Define the display area
         if (textStr.length > 0)
         {
             if (document.layers)
             {
                 msgToDisplay = 'document.NS4message.document.NS4message2.document.write(textStr[MsgID++]);'+ 'document.NS4message.document.NS4message2.document.close()';
             }
             else if (document.getElementById)
             {
                 msgToDisplay = 'document.getElementById("message").innerHTML = "<span class=\'" + StyleToUse + "\'>" + textStr[MsgID++] + "</span>";';
             }
             else if (document.all)
             {
                 msgToDisplay = 'message.innerHTML = "<span class=\'" + StyleToUse + "\'>" + textStr[MsgID++] + "</span>";';
             }
         }

         // Display the messages
         msgRotate();
     }
 }


/**
 * Function used to display in loop the short messages.
 *
 * @author STNA/7SQ
 * @version 1.1
 *    - 2009-01-23 : allow to display messages with 2 different styles
 *
 * @since 2005-01-27
 *
 */
 function msgRotate()
 {
     // Select the right style to display the message
     ArrayStyles = new Array("EvenRotateMsg", "OddRotateMsg");
     iStyleIndexMsg = iStyleIndexMsg % 2;
     StyleToUse = ArrayStyles[iStyleIndexMsg];
     iStyleIndexMsg++;

     eval(msgToDisplay);

     if (MsgID == textStr.length) MsgID = 0;
     setTimeout("msgRotate()", msgRotateSpeed);
 }






