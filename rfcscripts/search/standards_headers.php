<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
$lsi  = strrpos($_SERVER['PHP_SELF'], "/");
$sub  = substr($_SERVER['PHP_SELF'], 0,$lsi);
$base  = "http://";
if (isset($_SERVER['HTTPS'])) {
     $base  = "http://";
}
$base .= $_SERVER['HTTP_HOST'];
$base .= $sub;
?>

<?php
/*This function specifies the page header */
/*function page_header($title="RFC Search",
					 $script_extra1 = "NONE",
					 $script_extra2 = "NONE",
					 $base_extra=null,
					 $style_sheet="css/rfcsearch.css",
					 $on_load=null){
*/


function page_header($title="RFC Search",
					 $script_extra1 = "NONE",
					 $script_extra2 = "NONE",
					 $base_extra=null,
					 $style_sheet="/css/rfcsearch_new.css",
					 $on_load=null){
						 

						 
	 global $base;
     switch($base_extra) {
     case null:
          $anchor = $base;
          break;
     default:
          $anchor = $base . '/' . $base_extra;
          break;
     }
	 print <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>$title</title>
END;
    // if this page has a special script, load it.
    if ( strcmp($script_extra1,"NONE") != 0 ) {
      print "\n<script src=\"$script_extra1\" type=\"text/javascript\">\n</script>\n";
    }
	if ( strcmp($script_extra2,"NONE") != 0 ) {
      print "\n<script src=\"$script_extra2\" type=\"text/javascript\">\n</script>\n";
    }
	
    print "\n<link rel=\"stylesheet\" href=\"$style_sheet\" type=\"text/css\" />";

?>
 <script>
 /********************************************************************************/	
/*This function runs when rfc_search page is loaded								 */
/*********************************************************************************/	
function load(){
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

	}
/*********************************************************************************/	
/*This is a conditional function whcih runs when rfc_search_detail page is loaded*/
/*********************************************************************************/	
function load_condition(){

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
	}
}
	
</script>
<?php	
	print "\n</head>\n";

//If the page title is RFC Search then call load java function
/*	if ($title == "RFC Search"){
		$onload = "\"load()\"";
	}
//If the page title is RFC Search Detail then call load_condition java function	
	if ($title == "RFC Search Detail"){
		$onload = "\"load_condition()\"";
	}
	
*/		
  	if ($on_load != null) {
         print("<body onload=$on_load>");
    } else {
         print("<body>");
    }
	
}
/*
 * Write out the closing tags for an HTML page.
 */
function page_footer() {
/*	print<<<END
	</div>
  	<div class="footer">
	<p></P>
    <!-- end .footer --></div>
  	</body>
	</html>

END;
*/
?>
   </div>
  </div>
</div>
<?php
    print "<br>";
    print "<br>";
get_footer();
}


?>
