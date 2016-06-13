<?php
/*
Template Name: support
*/
?>


<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */

get_header(); ?>

	<div id="content" class="grid_9 rightsection_inner supportpage">

		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
		<div class="post" id="post-<?php the_ID(); ?>">
		<h2><?php the_title(); ?></h2>
			<div class="entry">
				<?php the_content('<p class="serif">Read the rest of this page &raquo;</p>'); ?>

				<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>

			</div>
		</div>
		<?php endwhile; endif; ?>
	<?php edit_post_link('Edit this entry.', '<p>', '</p>'); ?>
    
    
    <div class="whichhood_to_get">
  <h2>Still not sure about which hood to buy?</h2>
  <h3>Check out our helpful range hood FAQ articles</h3>
  <a style="font-family: arial, helvetica, sans-serif;" href="http://www.prolinerangehoods.com/1-801-973-3959/support/">by clicking here.</a> </div>
  
  <div class="satisfaction">
  <h2>OUR 100% SATISFACTION GUARANTEE</h2>
  <h3><a href="http://www.prolinerangehoods.com/1-801-973-3959/about-proline-range-hoods/why-buy-from-us/">Click for details</a></h3>
</div>

    
	</div>
	<?php get_sidebar(); ?>
<?php get_footer(); ?>
