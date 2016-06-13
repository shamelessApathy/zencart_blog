<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */

get_header(); ?>
	<?php get_sidebar(); ?>
	<div id="content" class="grid_9">

	<?php if (have_posts()) : ?>

		<h2 class="pagetitle">Search Results</h2>

		<div class="navigation">
			<div class="alignright"><?php next_posts_link('Next &raquo;') ?></div>
			<div class="alignleft"><?php previous_posts_link('&laquo; Previous') ?></div>
			<div class="cls"></div>
		</div>


		<?php while (have_posts()) : the_post(); ?>

			<div <?php post_class() ?>>
				<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
				<small><?php the_time('l, F jS, Y') ?></small>
				<?php the_excerpt(); ?>
				<p class="postmetadata"><a class="url" href="<?php echo $post->url ?>"><?php echo substr($post->url, 0, 64); ?></a> | <?php the_tags('Tags: ', ', ', '<br />'); ?> Posted in <?php the_category(', ') ?> | <?php edit_post_link('Edit', '', ' | '); ?>  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></p>
			</div>

		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignright"><?php next_posts_link('Next &raquo;') ?></div>
			<div class="alignleft"><?php previous_posts_link('&laquo; Previous') ?></div>
			<div class="cls"></div>
		</div>

	<?php else : ?>

		<h2 class="center">No posts found. Try a different search?</h2>
		<?php get_search_form(); ?>

	<?php endif; ?>

	</div>

<?php get_footer(); ?>
