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
 * Common module : function used to sort a table displaying some informations
 *
 * @author STNA/7SQ
 * @version 3.0
 * @since 2004-01-23
 */


/**
 * Function used to sort a table
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-01-23
 *
 * @param Value   Integer       Column number of the "order by" field
 */
 function SortFct(Value)
 {
     if (document.forms[0].hidOrderByField.value == Value)
     {
         Value = -Value;
     }

     document.forms[0].hidOrderByField.value = Value;
     document.forms[0].submit();
 }


/**
 * Function used to sort several tables
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2008-09-26 : try to find the right number of form
 *
 * @since 2005-05-10
 *
 * @param Value   Integer       Column number of the "order by" field
 * @param Table   String        Name of the table
 */
 function MultiSortFct(Value, Table)
 {
     // We search the right number of form
     iNumForm = 0;
     bFound = false;
     while((bFound == false) && (iNumForm < document.forms.length))
     {
         if (document.forms[iNumForm].elements["hidOrderByField" + Table])
         {
             bFound = true;
         }
         else
         {
             iNumForm++;
         }
     }

     if (document.forms[iNumForm].elements["hidOrderByField" + Table].value == Value)
     {
         Value = -Value;
     }

     document.forms[iNumForm].elements["hidOrderByField" + Table].value = Value;
     document.forms[iNumForm].submit();
 }