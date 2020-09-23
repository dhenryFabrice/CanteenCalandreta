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


/******************************************************************************
    Composant de barre d'attente - jsWait
    Vincent Fiack - 18/03/2003
*******************************************************************************/


document.writeln('<div id=\"jsWaitMessage\" style=\"display: none; font-family: Verdana; font-size: 10px; text-align: center; padding: 3px; position: absolute; left: 30%; top: 250px; height: 20px; width: 300px; z-index:3\"></div>');
document.writeln('<div id=\"jsWaitArea\" style=\"display: none; text-align: left; position: absolute; left: 30%; top: 250px; height: 20px; width: 300px; border: 1px black solid; background: #CBCBFF;z-index:2\">');
document.writeln('<div id=\"jsWaitBlock\" style=\"display: none; position: relative; left: 0px; height: 20px; width: 50px; background: #CC6633;z-index:2\"></div>');
document.writeln('</div>');

jsWait_defaultInstance = null;

function showWait(message)
{
  jsWait_defaultInstance = new jsWait('jsWait_defaultInstance', message);
  jsWait_defaultInstance.show();
}

// -------------------------------------------
//        Définition du type jsWait
// -------------------------------------------

/**
 * Constructeur
 * @param name le nom du composant
 * @param message le message a afficher
 */
function jsWait(name, message)
{
  this.name = name;
  this.message = message;
  this.speed = 10;
  this.direction = 2;

  this.waiting = false;

  this.divMessage = document.getElementById("jsWaitMessage");
  this.divArea = document.getElementById("jsWaitArea");
  this.divBlock = document.getElementById("jsWaitBlock");
}


// -------------------------------------------
//        Méthodes publiques
// -------------------------------------------

jsWait.prototype.show = function()
{
  this.divMessage.innerHTML = this.message;
  this.divMessage.style.display = "block";
  this.divArea.style.display = "block";
  this.divBlock.style.display = "block";
  this.divBlock.style.left = "0px";
  this.waiting = true;

  this.loop();
}

jsWait.prototype.setMessage = function(message)
{
  this.message = message;
  this.divMessage.innerHTML = this.message;
}

jsWait.prototype.stop = function()
{
  this.waiting = false;
  this.divMessage.style.display = "none";
  this.divArea.style.display = "none";
  this.divBlock.style.display = "none";
}


// -------------------------------------------
//        Méthodes privées
// -------------------------------------------

jsWait.prototype.loop = function()
{
  myLeft = this.divBlock.style.left;
  myLeft = myLeft.substring(0, myLeft.length-2);
  intLeft = parseInt(myLeft);

  if(intLeft >= 250)
    this.direction = -2;
  if(intLeft <= 0)
    this.direction = 2;

  myLeft = "" + (intLeft+this.direction) + "px";
  this.divBlock.style.left = myLeft;

  if(this.waiting)
    setTimeout(this.name + ".loop()", this.speed);
}