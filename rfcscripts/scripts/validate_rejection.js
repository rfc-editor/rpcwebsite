/* Copyright The IETF Trust 2020 All Rights Reserved */
/* $Id: validate_rejection.js,v 1.1.1.1 2016/02/08 21:03:51 priyanka Exp $ */
/* *********************************************************************
 * These methods perform data validation of the RFC verify reject form *
 * ********************************************************************* */

var errString;

/**
 * Step through the fields of a reported erratum. Return false if anything
 * looks odd, else true.
 * Called by onsubmit event of errata_report form.
 */
function verifyRejection(fields) {
   var errCount  = 0;
   var errString  = "";
 
  if (fields.reason.value.length <= 3){
      errString = errString.concat("\tPlace the reason(s) for rejecting this errata report.\n");
      errCount++;
   }

   if (errCount > 0) {
      alert(errString);
      fields.reason.focus();
      return false;             // Cancel the form submit
   }

   return true;                 // Let form go to server


}
