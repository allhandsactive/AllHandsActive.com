<?php
include('include/membersearch.inc.php');
get_header(); ?>

		<div id="container">
			<div id="content" role="main">

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  				<h1 class="entry-title"><?php the_title(); ?></h1>
					<div class="entry-content">
						<?php
						  the_content();
						  //Check if there is a search request.
						  if($_REQUEST['ms']) {
						    echo getSearchResults();
						    echo '<hr />';
						  }
						  
						  echo getSearchForm();
						?>
						
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'aha2010' ), 'after' => '</div>' ) ); ?>
						<?php edit_post_link( __( 'Edit', 'aha2010' ), '<span class="edit-link">', '</span>' ); ?>
					</div><!-- .entry-content -->
				</div><!-- #post-## -->

				<?php comments_template( '', true ); ?>

<?php endwhile; ?>

			</div><!-- #content -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
