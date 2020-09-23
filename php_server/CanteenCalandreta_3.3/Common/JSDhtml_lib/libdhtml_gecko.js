<!-- hide script from old browsers
/*
DHTML Librairie Gecko (Version 1.0 - 01/09/2005)
*/
var vers=parseFloat(navigator.appVersion);if (vers<4.0) {alert("AVERTISSEMENT: Ce script requiert un navigateur de 4ieme génération.");}
var MXP=65535;var MYP=65535;var CMDS=new Array();var ptcom=0;var attente=0;var nbptcom=0;var pt=0;var pt2=0;var pt3=0;
var BUF=new Array();var com="";var cx1=0;var cy1=0;var cx2=0;var cy2=0;var nbi=0;var vitx=0.1;var vity=0.1;
var temp="";var nom="";var dx=0.1;var dy=0.1;var rx=0;var ry=0;var decaX=0;var decaY=0;coz=new Array();var zin=new Array();
var SobjX=new Array();var SobjY=new Array();var Nobj=new Array();var Dobj=new Array();var DDobj=new Array();var PtObj=0;
for (var i = 0; i < 360; i++) {zin[i]=Math.sin((2*Math.PI)*i/360);coz[i]=Math.cos((2*Math.PI)*i/360);}
hexa = new Array(0,1,2,3,4,5,6,7,8,9,"a","b","c","d","e","f");
function hex(i) {if (i < 0) {return "00";} else if (i > 255) {return "ff";} else{return "" + hexa[Math.floor(i/16)] + hexa[i%16];}}
function setbgColor(r, g, b) {var hr = hex(r); var hg = hex(g); var hb = hex(b);document.bgColor = "#"+hr+hg+hb;}
function CreerObj(nom,px,py,tx,ty,visible,zindex,contenu,special,dragdrop){if (visible==1) {visi="visible;"} else {visi="hidden;"};chaine='<div style="position:absolute;width:'+tx+'px;height:'+ty+'px;top:'+py+'px;left:'+px+'px;visibility:'+visi+'z-index:'+zindex+';" id="'+nom+'" '+special+'>'+contenu+'</div>';document.write(chaine);Nobj[PtObj]=nom;SobjX[PtObj]=tx;SobjY[PtObj]=ty;Dobj[PtObj]=0;DDobj[PtObj]=dragdrop;PtObj+=1;}
function CreerZone(nom,x1,y1,x2,y2){document.getElementById(nom).style.clip = "rect("+y1+","+x2+","+y2+","+x1+")";}
function ChangeIndex(nom,valeur) {document.getElementById(nom).style.zIndex=valeur;}
function CacherObj(nom){document.getElementById(nom).style.visibility = "hidden";}
function VoirObj(nom){document.getElementById(nom).style.visibility = "visible";}
function ModifierObj(nom,contenu){document.getElementById(nom).innerHTML=contenu;}
function PlacerObj(nom,px,py) {if (px!=-10000) {document.getElementById(nom).style.left = px + "px";} if (py!=-10000) {document.getElementById(nom).style.top = py + "px";};}
function ObjX(nom){var chaine=document.getElementById(nom).style.left;var value=parseInt(chaine.substring(0,chaine.length-2));return value;}
function ObjY(nom){var chaine=document.getElementById(nom).style.top;var value=parseInt(chaine.substring(0,chaine.length-2));return value;}
function Mouvement(evnt) {MXP = evnt.clientX;MYP = evnt.clientY;for ( j = 0 ; j < PtObj ; j++ ) {if ((Dobj[j]==1)){PlacerObj(Nobj[j],MXP-decaX,MYP-decaY);return false;}}}
function noClique() {for ( j = 0 ; j < PtObj ; j++ ) {Dobj[j]=0;}}
function Clique(evnt) {
        MXP = evnt.clientX;
        MYP = evnt.clientY;
        for ( j = 0 ; j < PtObj ; j++ ) {
                if (DDobj[j]){
                        if ((MXP>=ObjX(Nobj[j])) && (MXP<=ObjX(Nobj[j])+SobjX[j]) && (MYP>=ObjY(Nobj[j])) && (MYP<=ObjY(Nobj[j])+SobjY[j]))
                        {
                                Dobj[j]=1;decaX=MXP-ObjX(Nobj[j]);decaY=MYP-ObjY(Nobj[j]);
                        }
                        else {
                                Dobj[j]=0;
                        }
                }
        }
}
document.onmousemove = Mouvement;document.onmousedown = Clique;document.onmouseup = noClique;
function SourisX(){var value=MXP+window.pageXOffset;return value;}
function SourisY(){var value=MYP+window.pageYOffset;return value;}
function TailleX(){var value=window.innerWidth;return value;}
function TailleY(){var value=window.innerHeight;return value;}
function OffsetX(){var value=window.pageXOffset;return value;}
function OffsetY(){var value=window.pageYOffset;return value;}
function MoveObj(nom,x1,y1,x2,y2,vit){BUF[pt2]=1;pt2+=1;BUF[pt2]=nom;pt2+=1;if (x1!=-1){BUF[pt2]=x1;pt2+=1;}else{BUF[pt2]=ObjX("nom");pt2+=1;}if (y1!=-1){BUF[pt2]=y1;pt2+=1;}else{BUF[pt2]=ObjY("nom");pt2+=1;}BUF[pt2]=x2;pt2+=1;BUF[pt2]=y2;pt2+=1;BUF[pt2]=vit;pt2+=1;BUF[pt2]=x1;pt2+=1;BUF[pt2]=y1;pt2+=1;BUF[pt2]=vit;pt2+=1;pt+=1;}
function BounceObj(nom,x1,y1,y2,vit){BUF[pt2]=2;pt2+=1;BUF[pt2]=nom;pt2+=1;if (x1!=-1){BUF[pt2]=x1;pt2+=1;}else{BUF[pt2]=ObjX("nom");pt2+=1;}if (y1!=-1){BUF[pt2]=y1;pt2+=1;}else{BUF[pt2]=ObjY("nom");pt2+=1;}BUF[pt2]=y2;pt2+=1;BUF[pt2]=vit;pt2+=1;BUF[pt2]=y1;pt2+=1;BUF[pt2]=0;pt2+=1;BUF[pt2]=1;pt2+=1;pt+=1;}
function FallObj(nom,x1,y1,y2,vitx,vity){BUF[pt2]=8;pt2+=1;BUF[pt2]=nom;pt2+=1;BUF[pt2]=x1;pt2+=1;BUF[pt2]=y1;pt2+=1;BUF[pt2]=y2;pt2+=1;BUF[pt2]=vitx;pt2+=1;BUF[pt2]=vity;pt2+=1;BUF[pt2]=x1;pt2+=1;BUF[pt2]=y1;pt2+=1;BUF[pt2]=1;pt2+=1;pt+=1;}
function RotateObj(nom,px,py,rx,ry,debut,fin,vit){BUF[pt2]=3;pt2+=1;BUF[pt2]=nom;pt2+=1;if (px!=-1){BUF[pt2]=px;pt2+=1;}else{BUF[pt2]=ObjX("nom");pt2+=1;}if (py!=-1){BUF[pt2]=py;pt2+=1;}else{BUF[pt2]=ObjY("nom");pt2+=1;}BUF[pt2]=rx;pt2+=1;BUF[pt2]=ry;pt2+=1;BUF[pt2]=debut;pt2+=1;BUF[pt2]=fin;pt2+=1;BUF[pt2]=vit;pt2+=1;BUF[pt2]=debut;pt2+=1;BUF[pt2]=1;pt2+=1;pt+=1;}
function RevealObj(nom,x1,y1,x2,y2,cx1,cy1,cx2,cy2,rx,ry){BUF[pt2]=4;pt2+=1;BUF[pt2]=nom;pt2+=1;BUF[pt2]=x1;pt2+=1;BUF[pt2]=y1;pt2+=1;BUF[pt2]=x2;pt2+=1;BUF[pt2]=y2;pt2+=1;BUF[pt2]=cx1;pt2+=1;BUF[pt2]=cy1;pt2+=1;BUF[pt2]=cx2;pt2+=1;BUF[pt2]=cy2;pt2+=1;BUF[pt2]=rx;pt2+=1;BUF[pt2]=ry;pt2+=1;BUF[pt2]=x1;pt2+=1;BUF[pt2]=y1;pt2+=1;BUF[pt2]=x2;pt2+=1;BUF[pt2]=y2;pt2+=1;BUF[pt2]=rx;pt2+=1;BUF[pt2]=ry;pt2+=1;BUF[pt2]=1;pt2+=1;pt+=1;}
function FixObj(nom,x1,y1){BUF[pt2]=5;pt2+=1;BUF[pt2]=nom;pt2+=1;BUF[pt2]=x1;pt2+=1;BUF[pt2]=y1;pt2+=1;pt+=1;}
function PassObj(nom,x1,y1,x2,y2,vit){BUF[pt2]=6;pt2+=1;BUF[pt2]=nom;pt2+=1;if (x1!=-1){BUF[pt2]=x1;pt2+=1;}else{BUF[pt2]=ObjX("nom");pt2+=1;}if (y1!=-1){BUF[pt2]=y1;pt2+=1;}else{BUF[pt2]=ObjY("nom");pt2+=1;}BUF[pt2]=x2;pt2+=1;BUF[pt2]=y2;pt2+=1;BUF[pt2]=vit;pt2+=1;BUF[pt2]=x1;pt2+=1;BUF[pt2]=y1;pt2+=1;BUF[pt2]=vit;pt2+=1;pt+=1;}
function FadeBG(r1,g1,b1,r2,g2,b2,vit){BUF[pt2]=7;pt2+=1;BUF[pt2]=r1;pt2+=1;BUF[pt2]=g1;pt2+=1;BUF[pt2]=b1;pt2+=1;BUF[pt2]=r2;pt2+=1;BUF[pt2]=g2;pt2+=1;BUF[pt2]=b2;pt2+=1;BUF[pt2]=vit;pt2+=1;BUF[pt2]=vit;pt2+=1;pt+=1;}

function CreerStaticObj(nom,px,py,tx,ty,visible,zindex,contenu,special,dragdrop){if (visible==1) {visi="block;"} else {visi="none;"};chaine='<div style="width:'+tx+'px;height:'+ty+'px;top:'+py+'px;left:'+px+'px;display:'+visi+'z-index:'+zindex+';" id="'+nom+'" '+special+'>'+contenu+'</div>';document.write(chaine);Nobj[PtObj]=nom;SobjX[PtObj]=tx;SobjY[PtObj]=ty;Dobj[PtObj]=0;DDobj[PtObj]=dragdrop;PtObj+=1;}
function CacherStaticObj(nom){document.getElementById(nom).style.display = "none";}
function VoirStaticObj(nom){document.getElementById(nom).style.display = "block";}
function VisibiliteObj(nom){if (document.getElementById(nom).style.display == "none") {return false} else {return true};}

function AddCom(donnees,delai) {CMDS[nbptcom]=donnees;nbptcom+=1;CMDS[nbptcom]=delai;nbptcom+=1;}
function animator(temps){
 if (pt!=0) {
  pt3=0;
  for ( j = 0 ; j < pt ; j++ ) {
   com=BUF[pt3];pt3+=1;
   if (com==1) {
    if (BUF[pt3+8]) {
     nom=BUF[pt3];cx1=BUF[pt3+1];cy1=BUF[pt3+2];cx2=BUF[pt3+3];
     cy2=BUF[pt3+4];nbi=BUF[pt3+5];rx=BUF[pt3+6];ry=BUF[pt3+7];
     dx=(cx2-cx1)/nbi;dy=(cy2-cy1)/nbi;
     rx+=dx;ry+=dy;
     PlacerObj(nom,Math.round(rx),Math.round(ry));
     BUF[pt3+6]=rx;BUF[pt3+7]=ry;
     BUF[pt3+8]-=1;
    }
    pt3+=9;
   }
   if (com==2) {
    if (BUF[pt3+7]) {
     nom=BUF[pt3];cx1=BUF[pt3+1];cy1=BUF[pt3+2];cy2=BUF[pt3+3];
     nbi=BUF[pt3+4];ry=BUF[pt3+5];dy=BUF[pt3+6];
     ry+=dy;
     if (ry>cy2) {
      ry=cy2;dy=-(dy/1.75);if ((dy>=-2) && (dy<=2)) {BUF[pt3+7]=0;nbi=0;}
     }
         dy+=nbi;
         PlacerObj(nom,Math.round(cx1),Math.round(ry));
     BUF[pt3+6]=dy;BUF[pt3+5]=ry;
    }
    pt3+=8;
   }
   if (com==3) {
    if (BUF[pt3+9]) {
     nom=BUF[pt3];x1=BUF[pt3+1];y1=BUF[pt3+2];rx=BUF[pt3+3];ry=BUF[pt3+4];
     deb=BUF[pt3+5];fin=BUF[pt3+6];
     vit=BUF[pt3+7];cur=BUF[pt3+8];
     cur+=vit;
     if (cur>359) {cur=cur-360;}
     if (cur<0) {cur=360+cur;}
     if ((deb!=-1) && (fin!=-1)) {if (cur==fin) {BUF[pt3+9]=0;} }
     fx=x1+(rx*coz[cur]);fy=y1+(ry*zin[cur]);
     PlacerObj(nom,Math.round(fx),Math.round(fy));
     BUF[pt3+8]=cur;BUF[pt3+7]=vit;
    }
    pt3+=10;
   }
   if (com==4) {
    if (BUF[pt3+17]) {
     nom=BUF[pt3];
           x1=BUF[pt3+1];y1=BUF[pt3+2];x2=BUF[pt3+3];y2=BUF[pt3+4];
           x11=BUF[pt3+5];y11=BUF[pt3+6];x22=BUF[pt3+7];y22=BUF[pt3+8];
           rx=BUF[pt3+9];ry=BUF[pt3+10];
           cx1=BUF[pt3+11];cy1=BUF[pt3+12];cx2=BUF[pt3+13];cy2=BUF[pt3+14];
     dx=(x11-x1)/rx;dy=(y11-y1)/ry;cx1+=dx;cy1+=dy;
           dx=(x22-x2)/rx;dy=(y22-y2)/ry;cx2+=dx;cy2+=dy;
     CreerZone(nom,Math.round(cx1),Math.round(cy1),Math.round(cx2),Math.round(cy2));
     BUF[pt3+11]=cx1;BUF[pt3+12]=cy1;BUF[pt3+13]=cx2;BUF[pt3+14]=cy2;
           rx=BUF[pt3+15];ry=BUF[pt3+16];
           rx-=1;ry-=1;
           if (rx<=0) {rx=0;}
           if (ry<=0) {ry=0;}
           if ((rx==0) && (ry==0)) {BUF[pt3+17]=0;CreerZone(nom,x11,y11,x22,y22);}
     BUF[pt3+15]=rx;BUF[pt3+16]=ry;
    }
    pt3+=18;
   }
   if (com==5) {
     nom=BUF[pt3];x1=BUF[pt3+1];y1=BUF[pt3+2];
     PlacerObj(nom,Math.round(TailleX()*(x1/100)),Math.round(TailleY()*(y1/100)));
     pt3+=3;
         }
   if (com==6) {
    if (BUF[pt3+8]) {
     nom=BUF[pt3];cx1=BUF[pt3+1];cy1=BUF[pt3+2];cx2=BUF[pt3+3];
     cy2=BUF[pt3+4];nbi=BUF[pt3+5];rx=BUF[pt3+6];ry=BUF[pt3+7];
     dx=(cx2-cx1)/nbi;dy=(cy2-cy1)/nbi;
     rx=rx+dx;ry=ry+dy;
     PlacerObj(nom,Math.round(rx),Math.round(ry));
     BUF[pt3+6]=rx;BUF[pt3+7]=ry;
     BUF[pt3+8]-=1;
     if (BUF[pt3+8]==0) {
      BUF[pt3+6]=cx1;BUF[pt3+7]=cy1;BUF[pt3+8]=nbi;
     }
    }
    pt3+=9;
   }
   if (com==7) {
    if (BUF[pt3+7]>=0) {
     r1=BUF[pt3];g1=BUF[pt3+1];b1=BUF[pt3+2];
     r2=BUF[pt3+3];g2=BUF[pt3+4];b2=BUF[pt3+5];
     nbi=BUF[pt3+6];var ptstep=(nbi-BUF[pt3+7]);
     setbgColor(
      Math.floor(r1 * ((nbi-ptstep)/nbi) + r2 * (ptstep/nbi)),
      Math.floor(g1 * ((nbi-ptstep)/nbi) + g2 * (ptstep/nbi)),
      Math.floor(b1 * ((nbi-ptstep)/nbi) + b2 * (ptstep/nbi)));
     BUF[pt3+7]-=1;
    }
    pt3+=8;
   }
   if (com==8) {
    if (BUF[pt3+8]) {
     nom=BUF[pt3];cx1=BUF[pt3+1];cy1=BUF[pt3+2];cy2=BUF[pt3+3];
     vitx=BUF[pt3+4];vity=BUF[pt3+5];dx=BUF[pt3+6];dy=BUF[pt3+7];
     dx+=vitx;
     dy+=vity;vity+=1;
     if (dy>cy2) {
      dy=cy2;BUF[pt3+8]=0;
     }
           PlacerObj(nom,Math.round(dx),Math.round(dy));
     BUF[pt3+6]=dx;BUF[pt3+7]=dy;BUF[pt3+5]=vity;
    }
    pt3+=9;
   }
  }
 }
 if ((nbptcom!=0) && (ptcom<=nbptcom)) {
  if (attente==0) {
   comm=CMDS[ptcom];ptcom+=1;
   if (comm=="LOOP;") {
     ptcom=0;
   }
   else {
    eval(comm);
    attente=CMDS[ptcom];ptcom+=1;
   }
  }
  else {
   attente-=1;if (attente<0) {attente=0;}
  }
 }
 setTimeout("animator("+temps+")",temps)
}
// end hiding --->