// dhtml_lib loader V1.1 (ajout du support FireFox)
// detection du navigateur et chargement de la bonne librairie
// sinon un message d'erreur est envoyé
var libdhtml_path = location.protocol + "//" + location.hostname + "/" + "CanteenCalandreta/Common/JSDhtml_lib/";   // Full path of the dhtml lib

var agent = navigator.userAgent.toLowerCase();
var major = parseInt(navigator.appVersion);
var minor = parseFloat(navigator.appVersion);
var ie = (agent.indexOf("msie") != -1);
var ns = ((agent.indexOf('mozilla')!=-1) && (agent.indexOf('spoofer')==-1) && (agent.indexOf('compatible') == -1));
var ns4 = (ns && (major >= 4 && major<5));
var ns6 = (ns && (major >= 5));
var ie5 = (ie && (major >= 4));
var gecko = (agent.indexOf('gecko') != -1);

if (ie5) {document.write("<script language='javascript' src='" + libdhtml_path + "libdhtml_ie.js'></script>");}
else if (gecko) {document.write("<script language='javascript' src='" + libdhtml_path + "libdhtml_gecko.js'></script>");}
else if (ns6) {document.write("<script language='javascript' src='" + libdhtml_path + "libdhtml_ns6.js'></script>");}
else if (ns4) {document.write("<script language='javascript' src='" + libdhtml_path + "libdhtml_ns4.js'></script>");}
else {alert("Votre navigateur n'est pas compatible avec la DHTML lib V2.3 (IE4.x+/NS4.x+)")}