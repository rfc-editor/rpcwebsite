/* Copyright The IETF Trust 2020 All Rights Reserved */
/* $Id: index_controls.js,v 2.2 2014/07/16 18:47:54 priyanka Exp $
 *
 * This function is used to submit one of the forms in the
 * index_controls.php script from a button element. Called with the
 * name of the action desired.
 */
function submitForm(formName) {
   if(formName == 'publishForm')
   {
      document.publishForm.flag.value="1";
      document.publishForm.submit();
   }
   else if (formName == 'emailForm')
   {
      document.publishForm.action = "sendEMail.php";
      document.publishForm.submit();
   }
   else if (formName == 'announcementForm')
   {
      document.publishForm.action = "makeAnnouncement.php";
      document.publishForm.submit();
   }
   else if (formName == 'auth48emailForm')
   {
      // For AUTH48, we want to select a different template depending
      // on the format of the RFC. Get the draft name from the other
      // form and use its value to set the value for our separate
      // AUTH48 form.
      document.AUTH48Form.action = "Auth48Email.php";
      document.AUTH48Form.draftName.value = document.publishForm.draftName.value;
      document.AUTH48Form.submit();
   }
   else if (formName == 'commitForm')
   {
      document.updateForm.action ="exportINDEX.php";
      document.updateForm.submit();
   }
   else if (formName == 'qcommitForm')
   {
      document.updateForm.action ="exportQueue.php";
      document.updateForm.submit();
   }
   else if (formName == 'publishDraftForm')
   {
      document.publishForm.action ="publishDraft.php";
      document.publishForm.submit();
   }
   else if (formName == 'postIndexDatatrackerForm')
   {
      document.publishForm.action ="postIndexDttracker.php";
      document.publishForm.submit();
   }
   else if (formName == 'searchPubForm')
   {

      var x= document.searchPubForm.rfcNum.value;
      
      if (isNaN(x)) 
      {
    	alert("Please enter input numbers");
	document.searchPubForm.rfcNum.value = "";
    	return false;
      }else {
      document.searchPubForm.action = "makeAnnouncement.php";
      document.searchPubForm.submit();
      }
   }	

}
