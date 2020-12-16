/* Copyright The IETF Trust 2020 All Rights Reserved */
/**************************************************************************/
/*This methods performs data validations for RFC serach forms		      */
/**************************************************************************/
var errString;

function validateRFCSearchForm(fields){
 var errCount  = 0;
 var letters = /^[A-Za-z0-9\-]+$/; 


// var quote_title = /^\"[0-9a-zA-Z\-]+$|^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s* \"$/;
 var quote_start = /^\"/;
 var quote_end = /\"$/;
 var quote_title =  /^[0-9a-zA-Z\-\/ ]+$|^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s*$/; 
  
 
// var title_letters =  /^[0-9a-zA-Z ]+$|^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s*$/; 
 var title_letters =  /^[0-9a-zA-Z\-\/ ]+$|^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s*$/; 
 
 var restricted = /^(SELECT|select|INSERT|insert|Delete|delete|UPDATE|update|PASSWORD|password|Password|passwd|LOGIN|login|Login|USER|user|User)$/;
 
 var author_letters = /^[A-Za-z\'.-]+$/; 
 var abstract_letters = /^[0-9a-zA-Z ]+$|^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s*$/;
 errString = new String("Please correct these input errors:\n");
 var and_letter = /\band/gi;
 var or_letter = /\bor/gi;

  /* if (fields.rfc.value.length > 0) {
      // Validate this value: must be all digits
      if (isNaN(fields.rfc.value)) {
         errString = errString.concat("\tThe RFC number may contain only digits.\n");
         errCount++;
      }
   }
  */
  
   if (fields.author.value.length > 0){
	   if (!(fields.author.value.match(author_letters))){
		  	 	errString = errString.concat("\t\The author name may contain only alphabetic characters\n");
			 	errCount++;
	   		  } 
   }

/*Title/Keyword check logic*/
   if (fields.title.value.length > 0){
	   if ((fields.title.value.match(quote_start)) &&(fields.title.value.match(quote_end))){// Strings within the  quotes
		   		 if (fields.title.value.match(restricted)){
					  	 	errString = errString.concat("\t\Restricted character or word used in the Title/Keyword\n");
							errCount++;
				 } else {

					   var str = fields.title.value.replace(/\"/g,"");
					   if (!(str.match(quote_title))){
						   errString = errString.concat("\t\The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase). A query can include \"and\", \"or\", or double quotes, but not in combination.\n");
							errCount++;		   
		   				}
				 }
	   } else if ((!(fields.title.value.match(quote_start)))||(!(fields.title.value.match(quote_end)))){// Strings without quotes
		   		 if (fields.title.value.match(restricted)){
					  	 	errString = errString.concat("\t\Restricted character or word used in the Title/Keyword\n");
					 errCount++;
				 }else {	
				   		 if (!(fields.title.value.match(title_letters))){
							   errString = errString.concat("\t\The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase). A query can include \"and\", \"or\", or double quotes, but not in combination.\n");
					 	     errCount++;
	   				     } 
						 if ((fields.title.value.search(and_letter) != -1) && (fields.title.value.search(or_letter) != -1)){
							 errString = "";
							   errString = errString.concat("\t\The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase). A query can include \"and\", \"or\", or double quotes, but not in combination.\n");
							 errCount++;
						 }
					   
				 }
	   	}
   }
	
/*******************/

/*Get how many times the \" (quotes) appear in the search straing for Title/Keyword only 2 allowed < 2 or > 2 not allowed*/

/*   if (fields.title.value.length > 0){

			var characterToCount = '"';
			var counter = 0;
			var myArray = fields.title.value.split('');
		
			for (i=0;i<myArray.length;i++)
			{
				if (myArray[i] == characterToCount)
				{
				counter++;
				}
			}
			//alert('The character ' + characterToCount + ' appears ' + counter + ' times in the sequence:\n' + fields.title.value);
		
			var lastchar = fields.title.value[fields.title.value.length - 1];		
			
			if (counter == 2){
			//Now check if it begins and ends with \"	
			   if ((fields.title.value.match(quote)) &&( lastchar == '"' )){
				alert("String is proper begining and ending with quotes");
				if (!(fields.title.value.match(quote_title))){
			  	 	errString = errString.concat("\t\The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase).\n");	
				}
			   }
			   else {
				 alert("Quotes are not at proper location ERROR");
 				 errString = errString.concat("\t\The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase).\n");	
			   }
			}else if (counter == 1) {
				 alert("Single quotes not allowed ERROR");	
				 errString = errString.concat("\t\The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase).\n");	
			}
			else if(counter == 0){
				 alert("Without quote output");
				 if (!(fields.title.value.match(title_letters))){
		  	 		errString = errString.concat("\t\The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase).\n");}
				 
			}else if (counter > 2){
				 alert("Single quotes not allowed in more ERROR");		
				 errString = errString.concat("\t\The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase).\n");
			}
		 	errCount++;	
   }

*/





/*   var lastchar = fields.title.value[fields.title.value.length - 1];		
   if (fields.title.value.length > 0){
	   if ((fields.title.value.match(quote)) &&( lastchar == '"' )){
		alert("It's there");
	   }
	   else {
		 alert("It's not there");
		 if (fields.title.value.match(quote)){
			 alert ("Need quotes at both ends!");
		 }
	   }
   }
*/

/*From Date- To Date logic check*/
	 
	if ((fields.from_month.value.length > 0) && (fields.from_year.value.length == 0)){
  		 errString = errString.concat("\t\Please select the Publication date From Year value\n");
		 errCount++;
	} else if ((fields.from_month.value.length == 0) && (fields.from_year.value.length > 0)){
		 if (fields.to_year.value.length == 0){
	   		 errString = errString.concat("\t\Please select the Publication date To Year value\n");
			 errCount++;			
		 } else if (fields.to_year.value.length > 0){
				    if ((fields.to_year.value) < (fields.from_year.value)){
					 	errString = errString.concat("\t\The From Year value should be lesser than the To Year value\n");
						 errCount++;
				}
		   }
	} else if ((fields.from_month.value.length > 0) && (fields.from_year.value.length > 0)){
		 if (fields.to_year.value.length == 0){
	   		 errString = errString.concat("\t\Please select the Publication date To Year value\n");
			 errCount++;			
		 }else if (fields.to_year.value.length > 0){
				    if ((fields.to_year.value) < (fields.from_year.value)){
					 	errString = errString.concat("\t\The From Year value should be lesser than the To Year value\n");
						 errCount++;
				}
		   }
	} else if ((fields.to_year.value.length > 0) && (fields.from_year.value.length == 0)){
		 errString = errString.concat("\t\Please select the Publication date From Year value\n");
		 errCount++;
		
	 }
	
/*WG Acronym check*/	
	if (fields.wg_acronym.value.length > 0){
	   if (!(fields.wg_acronym.value.match(letters))){
		  	 	errString = errString.concat("\t\The WG acronym name may contain only alphanumeric characters\n");
			 	errCount++;
	   		  } 
    }
/*Abstract box logic check*/

/*	if (fields.abstract_box.value.length > 0){
	   if (!(fields.abstract_box.value.match(abstract_letters))){
		  	 	errString = errString.concat("\t\The Abstract name may contain only alphanumeric characters\n");
			 	errCount++;
	   		  } 
    }
*/


/*Title/Keyword check logic*/
   if (fields.abstract_box.value.length > 0){
	   if ((fields.abstract_box.value.match(quote_start)) &&(fields.abstract_box.value.match(quote_end))){// Strings within the  quotes
		   		 if (fields.abstract_box.value.match(restricted)){
					  	 	errString = errString.concat("\t\Restricted character or word used in the Abstract Contains\n");
							errCount++;
				 } else {

					   var str = fields.abstract_box.value.replace(/\"/g,"");
					   if (!(str.match(quote_title))){
							    errString = errString.concat("\t\The Abstract Contains may contain only alphanumeric characters (and double quotes to indicate a search phrase). A query can include \"and\", \"or\", or double quotes, but not in combination.\n");
							errCount++;		   
		   				}
				 }
	   } else if ((!(fields.abstract_box.value.match(quote_start)))||(!(fields.abstract_box.value.match(quote_end)))){// Strings without quotes
		   		 if (fields.abstract_box.value.match(restricted)){
					  	 	errString = errString.concat("\t\Restricted character or word in the Abstract Contains\n");
					 errCount++;
				 }else {	
				   		 if (!(fields.abstract_box.value.match(title_letters))){
					  	 	 errString = errString.concat("\t\The Abstract Contains may contain only alphanumeric characters (and double quotes to indicate a search phrase). A query can include \"and\", \"or\", or double quotes, but not in combination.\n");
					 	errCount++;
	   				     }
						 if ((fields.abstract_box.value.search(and_letter) != -1) && (fields.abstract_box.value.search(or_letter) != -1)){
							 errString = "";
							 errString = errString.concat("\t\The Abstract Contains may contain only alphanumeric characters (and double quotes to indicate a search phrase). A query can include \"and\", \"or\", or double quotes, but not in combination.\n");
							 errCount++;
						 }
				 }
	   	}
   }


/****************************/	
   if (errCount > 0) {
      alert(errString);
      return false;             // Cancel the form submit
   }
   // Everything we checked validated. Now go through and remove unused
   // arguments so that the URL doesn't look to ugly.
 if (fields.rfc.value.length <= 0) {
      el = document.getElementById(fields.rfc.id);
      el.parentNode.removeChild(el); // remore "rfc"
   }

   if (fields.title.value.length <= 0) {
      el = document.getElementById(fields.title.id);
      el.parentNode.removeChild(el); // remove "title"
   }

   if (fields.author.value.length <= 0) {
      el = document.getElementById(fields.author.id);
      el.parentNode.removeChild(el); // remove "author"
   }

   if (fields.stream_name.value.length <= 0) {
      el = document.getElementById(fields.stream_name.id);
      el.parentNode.removeChild(el); // remove "stream_name"
   }

   if (fields.area_acronym.value.length <= 0) {
      el = document.getElementById(fields.area_acronym.id);
      el.parentNode.removeChild(el); // remove "area_acronym"
   }

   if (fields.wg_acronym.value.length <= 0) {
      el = document.getElementById(fields.wg_acronym.id);
      el.parentNode.removeChild(el); // remove "wg_acronym"
   }

   if (fields.std_trk.value.length <= 0) {
      el = document.getElementById(fields.std_trk.id);
      el.parentNode.removeChild(el); // remove "std_trk"
   }

   if (fields.from_month.value.length <= 0) {
      el = document.getElementById(fields.from_month.id);
      el.parentNode.removeChild(el); // remove "from_month"
   }

   if (fields.from_year.value.length <= 0) {
      el = document.getElementById(fields.from_year.id);
      el.parentNode.removeChild(el); // remove "from_year"
   }

   if (fields.to_month.value.length <= 0) {
      el = document.getElementById(fields.to_month.id);
      el.parentNode.removeChild(el); // remove "to_month"
   }

   if (fields.to_year.value.length <= 0) {
      el = document.getElementById(fields.to_year.id);
      el.parentNode.removeChild(el); // remove "to_year"
   }

   if (fields.abstract_box.value.length <= 0) {
      el = document.getElementById(fields.abstract_box.id);
      el.parentNode.removeChild(el); // remove "abstract_box"
   }

//Remove other selection of pubstatus if Any is selected
	if (document.getElementById("Any").checked == true){
	  el = document.getElementById(fields.std_trk.id);
      el.parentNode.removeChild(el); // remove "std_trk"
				
	}
   return true;    
   
}

/**************************************************************************/
/*This methods clears the values of search form when user submits reset   */
/**************************************************************************/
function clearAdvSearchForm(fields) {
   fields.rfc.value = "";
   fields.title.value = "";
   fields.author.value = "";
   fields.area_acronym.value = "";
   fields.wg_acronym.value = "";
   fields.stream_name.value = "";
   fields.from_month.value = "";
   fields.from_year.value = "";
   fields.to_month.value = "";
   fields.to_year.value = "";
   fields.abstract_box.value = "";
   

/* var boxes = document.getElementById('query').getElementsByTagName('input');

     for (var i = 0; i < boxes.length; i++) {
          if (boxes[i].type == 'checkbox'){
			  alert("We are here!");
			    boxes[i].checked = false;
          }
     }
	 
*/	 
/*Uncheck checkboxes other that any*/
		document.getElementById("st").checked = false;
	 	document.getElementById("bct").checked = false;
		document.getElementById("il").checked = false;
		document.getElementById("ex").checked = false;
		document.getElementById("hc").checked = false;
		document.getElementById("un").checked = false;
		document.getElementById("std_trk").checked = false;
		
/*Disable checkboxes other that any*/		
/*		document.getElementById("st").disabled = true;
		document.getElementById("bct").disabled = true;
		document.getElementById("il").disabled = true;
		document.getElementById("ex").disabled = true;
		document.getElementById("hc").disabled = true;
		document.getElementById("un").disabled = true;
		document.getElementById("std_trk").disabled = true;
*/		
/*Checked the status checkbox Any*/		
		document.getElementById("Any").checked = true
		
		
/*Uncheck the Abstarct and Keyword on option*/	
	document.getElementById("abstract").checked = false;
 	document.getElementById("keywords").checked = false;

	
/*The Publication date reset*/
	var pdate = document.getElementById("pub_date_type");
   	for (i = 0; i< pdate.options.length; i++)
	{
		pval = pdate.options[i].value;
		if (pdate.options[i].value == 'any')
		{
		// Item is found. Set its property and exit
			pdate.options[i].selected = true;
			break;
		}
	}
	
    document.getElementById('from_month').disabled = true;
	document.getElementById('from_year').disabled = true;
	document.getElementById('to_month').disabled = true;
	document.getElementById('to_year').disabled = true;

		
/*Bring focus back to rfc number*/		

   fields.rfc.focus();
   return true;
}


/**************************************************************************************/
/*Function : enableElement() disable/enables the Any option and unchecks and disables
			 the apporpriate  options	  											  */
/*			 Currently not in USE                                                     */			 
/**************************************************************************************/

function disableElement(){
	if (document.getElementById("Any").checked == true){
//Disabled the other status		
		document.getElementById("st").disabled = true;
		document.getElementById("bct").disabled = true;
		document.getElementById("il").disabled = true;
		document.getElementById("ex").disabled = true;
		document.getElementById("hc").disabled = true;
		document.getElementById("un").disabled = true;
		document.getElementById("std_trk").disabled = true;
	
//Unchecked the Other status		
		document.getElementById("st").checked = false;
		document.getElementById("bct").checked = false;
		document.getElementById("il").checked = false;
		document.getElementById("ex").checked = false;
		document.getElementById("hc").checked = false;
		document.getElementById("un").checked = false;
		document.getElementById("std_trk").checked = false;

	} else if (document.getElementById("Any").checked == false)
	{
//If other than Any status is selected then allow user to select other statuses	
		document.getElementById("st").disabled = false;
		document.getElementById("bct").disabled = false;
		document.getElementById("il").disabled = false;
		document.getElementById("ex").disabled = false;
		document.getElementById("hc").disabled = false;
		document.getElementById("un").disabled = false;
		document.getElementById("std_trk").disabled = false;
	}
		
}

/**************************************************************************************/
/*Function : disableAny() disables the Any option and checks the oppropriate option   */
/**************************************************************************************/

function disableAny(){
	
/*Make any unchecked if other that "Any" status is selected */	
				if (document.getElementById("st").checked == true){
					document.getElementById("Any").checked = false;
				}else if (document.getElementById("bct").checked == true){
					document.getElementById("Any").checked = false;
				}else if (document.getElementById("il").checked == true){
					document.getElementById("Any").checked = false;
				}else if (document.getElementById("ex").checked == true){
					document.getElementById("Any").checked = false;
				}else if (document.getElementById("hc").checked == true){
					document.getElementById("Any").checked = false;			
				}else if (document.getElementById("un").checked == true){
					document.getElementById("Any").checked = false;
				}else if (document.getElementById("std_trk").checked == true){
					document.getElementById("Any").checked = false;			
				}

}


/**************************************************************************************/
/*Function : enableAny() enables the Any option and unchecks the remaining options	  */
/**************************************************************************************/
function enableAny(){
	//Makes all the options unchecked after the Any is checked
	  document.getElementById("st").checked = false;
	  document.getElementById("bct").checked = false;
	  document.getElementById("il").checked = false;
	  document.getElementById("ex").checked = false;
	  document.getElementById("hc").checked = false;
	  document.getElementById("un").checked = false;
	  document.getElementById("std_trk").checked = false;

	}

/**************************************************************************************/
/*Function : selectPubDateType selects the publication date type
/**************************************************************************************/
function selectPubDateType(){
		
       if (document.getElementById("pub_date_type").value == "range"){
			document.getElementById('from_month').disabled = false;
			document.getElementById('from_year').disabled = false;
			document.getElementById('to_month').disabled = false;
			document.getElementById('to_year').disabled = false;
		}else{
			document.getElementById('from_month').disabled = true;
			document.getElementById('from_year').disabled = true;
			document.getElementById('to_month').disabled = true;
			document.getElementById('to_year').disabled = true;
		}
	
	}
