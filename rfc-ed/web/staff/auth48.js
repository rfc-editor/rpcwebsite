/* Copyright The IETF Trust 2020 All Rights Reserved */
/*
 * $Id: auth48.js,v 2.1 2011/01/21 20:34:15 priyanka Exp $
 * Routines to support the AUTH48 tracking page.
 */

/*
 * Set the file of the input element to the current date
 */
function setAppDate(inputId) {
   var element = document.getElementById(inputId);
   var now = new Date();
   var year = now.getFullYear();
   var day = now.getDate();
   var month = now.getMonth() + 1;
   element.value = year + "-" + month + "-" + day;
}

/*
 * Clear the text of the input element
 */
function clearAppDate(inputId) {
   var element = document.getElementById(inputId);
   element.value = "";
}

/*
 * Function iterates over the approval date fields of the form and validates
 * the input.
 */
function validateForm(form) {
   var count = form.name_count.value;
   for (var i = 0; i < count; i++) {
      element = document.getElementById("app_date_" + i);
      if (!isValidDate(element.value)) {
         alert("The date value of " + element.value + " is invalid. Please correct.");
         return false;
      }
      name_element = document.getElementById("name_" + i);

      if ((name_element.value.length > 60)) {
         alert("The name you have entered is too long. Please shorten it.");
         return false;
      }
   }

   new_approver_element = form.new_approver;
   if ((new_approver_element.value.length > 60)){
	 alert("The name you have entered is too long. Please shorten it.");
         return false;
   }

   return true;
}

/*
 * Check that the entered date has a reasonable form. Having an empty value is
 * a legitimate value for this application.
 * Resonable dates are of the form YYYY-MM-DD.
 * NOTE: Does not check for inappropriate dates for a month, e.g. Feb 30 
 * or Sep 31.
 */
function isValidDate(dateString) {
   if (dateString == null || dateString.length == 0) return true;
   var parsedDate = dateString.split("-",3);
   var year = parseInt(parsedDate[0]);
   var month = parseInt(parsedDate[1]);
   var day = parseInt(parsedDate[2]);
   if (isNaN(year) || year < 1970) return false;
   if (isNaN(month) || month < 0 || month > 12) return false;
   if (isNaN(day) || day < 0 || day > 31) return false;
   return true;
}
