<?php
/*******************************************************************************************/
/* Copyright The IETF Trust 2020 All Rights Reserved                                       */
/* The Script is used when certtain page is not found with the server request.             */
/*                                                                                         */
/* NOvember 2022 : Initial script is placed - PN                                           */
/*******************************************************************************************/
header( "HTTP/1.1 404 Not Found" );
get_header();

$tech_head_ad_code = of_get_option( 'header_ad_code', '' );
if ( !empty( $tech_head_ad_code ) ) {
    ?>
    <div class="aligncenter">
        <?php
        $tech_header_ad_code = stripslashes( of_get_option( 'header_ad_code', '' ) );
        echo do_shortcode( $tech_header_ad_code );
        ?>
    </div>
    <?php
    $tech_ii++;
}
if ( of_get_option( 'home_sidebar', '1' ) == "1" )
    tech_show_sidebar( "l" );
?>

<div id="content" class="<?php
if ( of_get_option( 'home_sidebar', '1' ) == "1" ) {
    echo "narrow";
} else {
    echo "wide";
}
?>column">



<!--Original Code commented begin-->
    <!--h2 class="aligncenter"--><?php /*_e( 'Error 404 - Not Found', 'techozoic' ) */?><!--/h2-->
<!--h3--><?php /*_e( 'Browse Archives', 'techozoic' )*/?><!--/h3-->
<!--Original Code commented end-->



<h1 class="post_title">404 - Not Found</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">


          <?php
          print "<p>Sorry, the page you requested cannot be found.</p>";
          print "<p>Please go to our <a href='/sitemap/'>Site Map</a> to find what you're looking for or send email to <a href=\"mailto:rfc-editor@rfc-editor.org\">rfc-editor@rfc-editor.org</a></p>";

          ?>
    <ul>
<?php wp_get_archives( 'type=monthly' ); ?>
    </ul>

   </div>
   </div>
</div>

<?php
if ( of_get_option( 'home_sidebar', '1' ) == "1" )
    tech_show_sidebar( "r" );
get_footer();
date_default_timezone_set('America/Los_Angeles');
$date1 = date('Y-M-d H:i:s', time());
global $wp;
#$current_url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
#$logstring=$date1." ".$current_url."\n";
#$handle=fopen('/a/logs/404log.txt','a+');
#fwrite($handle,$logstring);
#fclose($handle);

?>
