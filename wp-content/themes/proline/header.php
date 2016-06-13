<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title>
<?php wp_title('&laquo;', true, 'right'); ?>
<?php bloginfo('name'); ?>
</title>
<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/reset.css" />
<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/text.css" />
<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/960.css" />
<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/slicknav.css" />
<link rel="stylesheet" type="text/css" href="<?php echo esc_url( home_url( '/' ) ); ?>../includes/templates/theme158/css/proline_new.css"/>
<link rel="stylesheet" type="text/css" href="<?php echo esc_url( home_url( '/' ) ); ?>../includes/templates/theme158/css/responsive.css"/>
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
<?php wp_head(); ?>
<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
      <script src="http://www.prolinerangehoods.com/includes/templates/theme158/js/script.js"></script>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-12993154-15', 'auto');
  ga('send', 'pageview');

</script>
</head>
<body <?php body_class(); ?>>
<div id="page" class="container_12">
<div class="header">
  <div class="main_w">
    <!--bof-header logo and navigation display-->
    <div class="header-links"> <a href="http://www.prolinerangehoods.com/">Home <span class="devider">|</span></a><a href="http://www.prolinerangehoods.com/1-801-973-3959/about-proline-range-hoods/contact-us/">Contact Us <span class="devider">|</span></a> <a href="http://www.prolinerangehoods.com/index.php?main_page=login&amp;zenid=nbu2b6ip7fk4mpm7g9c7lroau0">Log In<span class="devider">|</span></a> <a href="tel:877.901.5530" class="tollfree"><span>Toll Free:</span> 877.901.5530</a>

      <ul style="list-style-type:none; float:right" class="socialsection">
            <li>
                <a href="https://twitter.com/prolinehoods"><img src="<?php echo get_bloginfo('template_directory');?>/img/twitter.png"  alt="" /></a>
                <a href="https://www.youtube.com/user/prolinerangehoods"><img src="<?php echo get_bloginfo('template_directory');?>/img/youtube.png"></a>
                <a href="https://www.facebook.com/prolinerangehoods"><img src="<?php echo get_bloginfo('template_directory');?>/img/facebook.png"></a>
                <a href="http://www.pinterest.com/prolinehoods/"><img src="<?php echo get_bloginfo('template_directory');?>/img/pintrest.png"></a>
            </li>
      </ul>

    </div>
    <div class="logo"></div>
    <div class="clear"></div>
    <div class="grid_4"></div>
    <div class="logosection"><a href="/" title="ProLine Range Hood Home Page"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/newlogo1.png" alt="<?php bloginfo('name'); ?> - <?php bloginfo('description'); ?>" /></a></div>
    <div class="search_cart_wrap">
      <div class="navigation">
        <div class="curr">
          <?php get_search_form(); ?>
          <!--<a href="http://www.prolinerangehoods.ca" target="_blank"><img src="images/canadiansClickHere.gif" alt="Canada range hoods"></a>-->
        </div>
        <div class="clear"></div>
      </div>
      <div class="mycart"><a href="http://www.prolinerangehoods.com/index.php?main_page=shopping_cart&amp;zenid=nbu2b6ip7fk4mpm7g9c7lroau0"> 0 </a> <span class="cart_text"> &nbsp;&nbsp;&nbsp;YOUR CART $0.00</span> </div>
    </div>
    <div class="clear"></div>
    <div id="cssmenu" class="dropdown">
      
      <ul>
        <li><a href="http://www.prolinerangehoods.com/" data-title="Home">Home</a></li>
        <li><a href="http://www.prolinerangehoods.com/index.php?main_page=index&amp;cPath=32" data-title="Wall">Wall</a> </li>
        <li><a href="http://www.prolinerangehoods.com/index.php?main_page=index&amp;cPath=31" data-title="Island">Island</a> </li>
        <li><a href="http://www.prolinerangehoods.com/index.php?main_page=index&amp;cPath=33" data-title="Under cabinet">Under cabinet</a> </li>
        <li><a href="http://www.prolinerangehoods.com/index.php?main_page=index&amp;cPath=71" data-title="bbq and outdoor">bbq and outdoor</a> </li>
        <li><a href="http://www.prolinerangehoods.com/index.php?main_page=index&amp;cPath=30" data-title="Professional Series">Professional Series</a> </li>
        <li><a href="#http://www.prolinerangehoods.com/index.php?main_page=index&amp;cPath=88" data-title="artisan">artisan</a></li>
      </ul>
    </div>
  </div>
</div>
<div class="content_wrap">