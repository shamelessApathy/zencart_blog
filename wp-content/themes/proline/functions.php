<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */

automatic_feed_links();

if ( function_exists('register_sidebar') ) {
	register_sidebar(array(
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<h2 class="widgettitle">',
		'after_title' => '</h2>',
	));
}

 /*<script src="<?php get_template_directory_uri() . '../../../includes/templates/theme158/js/cat_script.js';?>"></script>
        
        <script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
        <script src="<?php get_template_directory_uri() . '../../../includes/templates/theme158/js/bslider-script.js';?>" type="text/javascript"></script>
   <script src="<?php get_template_directory_uri() . '../../../includes/templates/theme158/js/script.js';?>"></script>
*/
   wp_enqueue_script( 'script', get_template_directory_uri() . '/js/cat_script.js', array ( 'jquery' ), 1.1, true);
   wp_enqueue_style('style', get_template_directory_uri() .  '/css/font-awesome-4.6.3/css/font-awesome.css', false, 1.1, all);
?>