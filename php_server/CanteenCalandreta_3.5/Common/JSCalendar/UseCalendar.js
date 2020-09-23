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


 // Calendar callback. When a date is clicked on the calendar
 // this function is called so you can do as you want with it
 function calendarCallback(day, month, year, InputName)
 {
     // According the $CONF_DATE_DISPLAY_FORMAT, the day must be coded on 2 digits
     date = "";
     if (day < 10)
     {
         date = '0';
     }

     date = date + day + '/';

     // According the $CONF_DATE_DISPLAY_FORMAT, the month must be coded on 2 digits
     if (month < 10)
     {
         date = date + '0';
     }

     date = date + month + '/' + year;

     if (InputName == null) {
         document.forms[0].desactivationDate.value = date;
     } else {
         var objInput = document.getElementById(InputName);
         if (objInput) {
             objInput.value = date;

             var event = new Event('ondatechange');
             objInput.dispatchEvent(event);
         }
     }
 }