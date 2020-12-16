/* Copyright The IETF Trust 2020 All Rights Reserved */
/*
 * This function is used to get the exact value of a checkbox from
 * edit_lib.php script.
 */

function getXMLvalue(){
	if (document.getElementById("xml_file").checked == true){
		document.getElementById("xml_file").value = "1"	;
        }else{
		document.getElementById("xml_file").value = "0"	;

	}
	
}
