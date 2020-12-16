/* Copyright The IETF Trust 2020 All Rights Reserved */
/* $Id: verify_login_form.js,v 1.1.1.1 2016/02/08 21:03:51 priyanka Exp $ */
/* *****************************************************************
 * These methods perform data validation of the verify login form  *
 * ***************************************************************** */

var errString;

/**
 * Step through the fields of a reported erratum. Return false if anything
 * looks odd, else true.
 * Called by onsubmit event of errata_report form.
 */
function validateLoginForm(fields) {
   var errCount  = 0;
   errString = new String("Please correct these input errors:\n");

   if (fields.login_name.value.length <= 0) {
      errString = errString.concat("\tPlease provide a value for the login name!\n");
      errCount++;
   }
   if (fields.password.value.length <= 0) {
      errString = errString.concat("\tPlease provide a value for the password field!\n");
      errCount++;
   }

   if (errCount > 0) {
      alert(errString);
      return false;             // Cancel the form submit
   }
   return true;                 // Let form go to server
}
