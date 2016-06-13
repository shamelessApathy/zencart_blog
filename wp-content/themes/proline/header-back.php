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

		<title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>

		<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/reset.css" />
		<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/text.css" />
		<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/960.css" />
		<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
		<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

		<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>

		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
		<div id="page" class="container_12">
			<div id="header">
				<div class="grid_4"><a href="/" title="ProLine Range Hood Home Page"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/proline_logo.gif" alt="<?php bloginfo('name'); ?> - <?php bloginfo('description'); ?>" /></a></div>
				<div class="grid_8">
					<ul>
						<li><a href="/index.php?main_page=login">Log In</a></li>
						<li><a href="/1-801-973-3959/about-proline-range-hoods/contact-us/">Contact Us</a></li>
						<li><a href="/">Home</a></li>
						<li class="cls"></li>
					</ul>
					<?php get_search_form(); ?></div>
				
				<div class="clear"></div>
			</div>
			<div class="content_wrap">