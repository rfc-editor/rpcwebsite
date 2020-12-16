/* Copyright The IETF Trust 2020 All Rights Reserved */
/* $Id: errata_report_edits.js,v 1.3 2019/09/10 16:52:31 priyanka Exp $ */
var errString;
var noVerifyMsg = 
   "Warning: you are posting this record without changing its Status to " + 
   "\"Verified\".\nClick \"OK\" to continue; else, click \"Cancel\".";

function validateDataEntryFields(report) {
   var errCount  = 0;
   errString = new String("Please correct these input errors:\n");

   errCount += validateRfcId(report.rfcnum.value);
   errCount += validateName(report.submitter_name.value, "Reported by");
   errCount += validateEmail(report.submitter_email.value, "Reporter Email");
   errCount += validateDate(report.submit_date.value, "Reported Date");

   // Records may be entered as verified  without verifier data, but if that data
   // is entered and the status is not set to verified, complain.
   var verified = (report.errata_status_code.value.search("Reported") == 0) ? false : true;
   // Now see if there's verifier data without the status being changed.
   if (!verified && (report.verifier_name.value.length > 0  ||
                     report.verifier_email.value.length > 0 ||
                     report.posted_date.value.length > 0)) {
      errCount++;
      errString = errString.concat("\tYou have entered verifier data without setting the status to \"Verified\"!\n");
   }
   if (verified && report.verifier_name.value.length > 0 )  {
      errCount += validateName(report.verifier_name.value, "Verified by");
   }
   if (verified && report.verifier_email.value.length > 0 )  {
      errCount += validateEmail(report.verifier_email.value, "Verifier Email");
   }
   if (verified && report.verifier_email.value.length > 0 )  {
      errCount += validateDate(report.posted_date.value, "Verify Date");
   }

   errCount += validateTypeSelect(report.errata_type_code.value);
   errCount += validateSection(report.section.value);

   if (errCount > 0) {
      alert(errString);
      return false;             // Cancel the form submit
   }
   return true;                 // Let form go to server
}

function validateRfcId(value) {
   if (value.length <= 0 || isNaN(value)) {
      errString = errString.concat("\tPlease provide the digits of the RFC document ID!\n");
      return 1;
   }
   return 0;
}
function validateDate(dateString, field) {
   if (dateString.length <= 0) {
      errString = errString.concat("\tPlease provide a " + field + " (YYYY-MM-DD)\n");
      return 1;
   }
   if (!(isValidDate(dateString))) {
      errString = errString.concat("\tInvalid format for " + field + " (YYYY-MM-DD)\n");
      return 1;
   }
   return 0;
}

/*
 * Simplistic check of a date string. Expects strings of format YYYY-MM-DD.
 * Splits the string into parts and tries to convert each subpart into an
 * interger. The results are compared to acceptable ranges for each. Note
 * no attempt is made to trap nonsense dates like 2007-02-30.
 */
function isValidDate(dateString) {
   var parsedDate = dateString.split("-",3);
   var year = parseInt(parsedDate[0]);
   var month = parseInt(parsedDate[1]);
   var day = parseInt(parsedDate[2]);
   if (isNaN(year) || year < 1970) return false;
   if (isNaN(month) || month < 0 || month > 12) return false;
   if (isNaN(day) || day < 0 || day > 31) return false;
   return true;
}

/* *****************************************************************
 * These methods perform data validation of the errata verify form *
 * ***************************************************************** */

function validateVerifyFields(report) {
   var errCount = 0;

   errString = new String("Please correct these input errors:\n");

   errCount += validateOriginal(report.orig_text.value);
   errCount += validateCorrected(report.correct_text.value);
   errCount += validateSection(report.section.value);
   errCount += validateFormat(report);
   
   if (errCount > 0) {
      alert(errString);
      return false;             // Cancel the form submit
   }

   var doPreview = report.submit[0].checked;
   var doPost    = report.submit[1].checked;

   if (doPost && report.errata_status_code.value == "Reported") {
      return confirm(noVerifyMsg);
   }
   return true;                 // Let form go to server
}

/* *****************************************************************
 * These methods perform data validation of the errata report form *
 * ***************************************************************** */


/**
 * Step through the fields of a reported erratum. Return false if anything
 * looks odd, else true.
 * Called by onsubmit event of errata_report form.
 */
function validateReportFields (report) {
   var errCount  = 0;
   errString = new String("Please correct these input errors:\n");

   errCount += validateName(report.submitter_name.value);
   errCount += validateEmail(report.submitter_email.value);
   errCount += validateTypeSelect(report.errata_type_code.value);
   errCount += validateSection(report.section.value);
   errCount += validateOriginal(report.orig_text.value);
   errCount += validateCorrected(report.correct_text.value);
   errCount += validateFormat(report);

   if (errCount > 0) {
      alert(errString);
      return false;             // Cancel the form submit
   }
   return true;                 // Let form go to server
}

function validateFormat(report){
    
  var checkboxes = report.elements['format[]'];
  var checkCount = 0;
  
  for (var i=0, len=checkboxes.length; i<len; i++) {
    if (checkboxes[i].checked){
        checkCount++;
    }
  }
  
  if (checkCount == 0){
    errString = errString.concat("\tPlease select Format for this report!\n");
    return 1;
  } 
   return 0;
 
}


function validateName(value, field) {
   if (value.length <= 0) {
      field = field || "your name for this report";
      errString = errString.concat("\tPlease provide " + field + "\n");
      return 1;
   }
   return 0;
}

function validateEmail(value, field) {

   if (value.length <= 0) {
      field = field || "your email address for this report!";
      errString = errString.concat("\tPlease provide " + field + "\n");
      return 1;
   }
   if (!(isValidEmail(value))) {
      field = field || "Email";
      errString = errString.concat("\t" + field + " address looks invalid. Please correct!\n");
      return 1;
   }
   return 0;
}

function validateTypeSelect(value) {
   if (value == "Select One") {
      errString = errString.concat("\tPlease select a Type for this report!\n");
      return 1;
   }
   return 0;
}

function validateSection(value) {
   if (value.length <= 0) {
      errString = errString.concat("\tPlease provide the Section with the error!\n");
      return 1;
   }
   return 0;
}

// April 2008: Per Bob Braden, no check needs to be made for existance of
//             either original or corrected text fields.
function validateOriginal(value) {

//TODO
//   return 0;
/*
   if (value.length <= 0) {
      errString = errString.concat("\tPlease provide the Original Text!\n");
      return 1;
   }
   return 0; 
*/
//TODO

   var arr = value.split("\n");
   for(var i = 0;i < arr.length; i++){
	  if(arr[i].length > 72) {
//             errString = errString.concat("\tPlease insert returns into the Original Text. (We recommend copying & pasting from the RFC.)\n");
             errString = errString.concat("\tPlease insert returns into the Original Text so the maximum line length is 72 characters. (We recommend copying & pasting from the RFC.)\n");

	     return 1;	
	  }
   }	
   return 0;
}

function validateCorrected(value) {

//TODO
//   return 0;
/*
   if (value.length <= 0) {
      errString = errString.concat("\tPlease provide a Corrected Text!\n");
      return 1;
   }
   return 0;
*/
//TODO

   var arr = value.split("\n");
   for(var i = 0;i < arr.length; i++){
	  if(arr[i].length > 72) {
	     errString = errString.concat("\tPlease insert returns into the Corrected Text so the maximum line length is 72 characters.\n");
	     return 1;	
	  }
   }	
   return 0;

}
/*
 * Simplistic validations of the form of an email address
 */
function isValidEmail(address) {
   // more-than-one @ character
   var firstAt = address.indexOf("@");
   var secondAt = address.lastIndexOf("@");
   if ( firstAt == -1 || firstAt != secondAt )  return false;

   // @ character first or last
   var arrayOfStrings = address.split("@");
   if (arrayOfStrings[0].length <= 0 || arrayOfStrings[1].length <= 0) {
      return false;
   }

   // domain doesn't have at least 2 parts
   var arrayOfDomains = arrayOfStrings[1].split(".");
   if (arrayOfDomains.length < 2) {
      return false;
   }


  //Temp blocked email address
   var rejectemail = "dryan1008@aol.com";
   if (address == rejectemail){
        return false;
   }

   return true;
}
/*
 * Flag a submit input as the one used.
 */
function setChecked(submit) {
   submit.checked = true;
   return true;
}

/* *****************************************************************
 * These methods service the mouse rollover and mouse click events *
 * ***************************************************************** */

var rollBox;

/**
 * Regular Expression object; tThe coordinates in the style are assumed to
 * be pixels, e.g. "300px."
 */ 
var pixelPattern = /px$/;

/**
 * Extract the innerHTML from the "text" element assoicated with the
 * element argument and set the rollBox to this value. Then set the
 * rollBox visible.
 * @param element The HTML element used to display the text.
 */
function showText(element) {
   if (rollBox == null) {
      // Also initialize the rollover box for DHTML text
      rollBox = document.getElementById("helpbox");
   }
   var helpFor = element.name + "_help";
   var helpElement = document.getElementById(helpFor);
//   var xOffset = pixelValue(element.x) + 275;
//   var yOffset = pixelValue(element.y) + 10;
//   rollBox.style.left= xOffset + "px";
//   rollBox.style.top = yOffset + "px";
   rollBox.style.left= "475px";
   rollBox.style.top = "225px";
   rollBox.innerHTML = helpElement.innerHTML;
   rollBox.style.visibility="visible";
}
/**
 * Set the rollBox invisible. (onmouseout event).
 */
function hideText() {
   rollBox.style.visibility = "hidden";
}

/**
 * Extract the numerical value assoicated with the pixel units, e.g.
 * for "100px" return 100.
 * @param {Object} coordinate String value indicating pixels on the screen.
 */
function pixelValue(coordinate) {
   if (typeof coordinate == "string") {
      var value;
      var units = coordinate.search(pixelPattern);
      switch (units) {
          case -1:
              value = parseInt(coordinate,10);
              break;
          default:
              value = parseInt(coordinate.substring(0,units),10);
              break;
      }
      return value;
   } else if (typeof coordinate == "number") {
       return coordinate;
   }
   throw new Error("pixelValue: unexpected type for pixel value: " + coordinate);
}

/**
* Select all the formats in one click
*/

function pubFormatSelect() {
   if (document.getElementById("all").checked == true){
//Disabled the other status             
                document.getElementById("text").checked = true;
                document.getElementById("pdf").checked = true;
                document.getElementById("html").checked = true;

   }else if ( document.getElementById("all").checked == false){
              document.getElementById("text").checked = false;
                document.getElementById("pdf").checked = false;
                document.getElementById("html").checked = false;

   }
}
