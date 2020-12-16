<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
include("rfc_headers.php");
#include("rfc_search_lib.php");
include("rfc_lib_new.php");


$criteria = array();
page_header("RFC Search","js/jquery.js","js/validate_rfcsearch.js","","css/rfcsearch_wp.css","selectPubDateType()");



/*print<<<END
<script>
$(document).ready(function(){
  $("#adv").hide();	
  
  $("#Advanced").click(function(){
    $("#adv").toggle(400);
	return false;
  });
  
});
</script>
END;*/

create_adv_search_form($criteria);   
page_footer();

?>
