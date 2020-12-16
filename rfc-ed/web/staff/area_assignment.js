/* Copyright The IETF Trust 2020 All Rights Reserved */
/*Script Name : area_assignment.js
/*              Script performs the validations for area_assignment.php

/*
 * Function iterates over the approval date fields of the form and validates
 * the input.
 */
function validateForm(form) {
   rfcNum = form.rfcNum;
   if ((rfcNum.value.length > 0)){
         if (isNaN(rfcNum.value)) {
	 alert("The RFC number may contain only digits");
         return false;
         }
   }

   return true;
}
