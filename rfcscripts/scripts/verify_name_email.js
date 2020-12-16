/* $Id: verify_name_email.js,v 1.1.1.1 2016/02/08 21:03:51 priyanka Exp $ */
/* Copyright The IETF Trust 2020 All Rights Reserved */
/* *****************************************************************
 * These methods perform data validation of the verifier name      *
 * and email entry form                                            *
 * ***************************************************************** */

var errString;

/**
 * Step through the fields of a reported erratum. Return false if anything
 * looks odd, else true.
 * Called by onsubmit event of errata_report form.
 */
function validateNameEmailForm(fields) {
   var errCount  = 0;
   errString = new String("Please correct these input errors:\n");

   if (fields.verifier_name.value.length <= 0) {
      errString = errString.concat("\tPlease provide a value for your name!\n");
      errCount++;
   }
   if (fields.verifier_email.value.length <= 0) {
      errString = errString.concat("\tPlease provide a value for your email address!\n");
      errCount++;
   } else if (!(isValidVerifierEmail(fields.verifier_email.value))) {
      errString = errString.concat("\tEmail address looks invalid. Please correct!\n");
      errCount++;
   }

   if (errCount > 0) {
      alert(errString);
      return false;             // Cancel the form submit
   }
   return true;                 // Let form go to server
}

/*
 * Simplistic validations of the form of an email address
 * TODO Merge with original in errata_report_edits.js.
 */
function isValidVerifierEmail(address) {
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
   
   return true;
}
