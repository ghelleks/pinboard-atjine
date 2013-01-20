<?php

/*
 * We get a syndicated feed from Tumblr (using tumblr-rss)
 * This extracts the post format from the list of categories.
 */
if ( ! function_exists( 'add_post_format_feedwordpress_syndicated_post' ) ) {
add_filter( 'syndicated_post' , 'add_post_format_feedwordpress_syndicated_post' ); 
function add_post_format_feedwordpress_syndicated_post ( $data ) {
	$feed = $data['tax_input']['syndication_feed'];
	$tag_ids = $data['tax_input']['post_tag'];
   $format = '';

   // maybe someday we'd check if this feed is tumblr or not.

	// look through the tags for a "format-*" so we can alter the post format accordingly
   foreach ($tag_ids as $i => $tag_id) {
		if (empty($format)) {
	      $tag = get_term_by('id', $tag_id, 'post_tag');

			//print_r($tag);

			switch ($tag->slug) {
				# aside, chat, gallery, link, image, quote, status, video
				case 'format-regular': $format = ''; break;
				case 'format-link': $format = 'link'; break;
				case 'format-quote'; $format = 'quote'; break;
				case 'format-photo': $format = 'image'; break;
				case 'format-conversation': $format = 'chat'; break;
				case 'format-video': $format = 'video'; break;
				case 'format-audio': $format = 'audio'; break;
				case 'format-answer': $format = 'aside'; break;
			}

	      // if we found a format, remove the format-* tag from the list of tags, we're done with it.
			if (! empty($format)) {
				unset($data['tax_input']['post_tag'][$i]);
				break; // ok, we're all done here.
			}
		}
   }
   
   if (empty($format) ) {
      // the format is "no format"
	   unset($data['tax_input']['post_format']);
	}
	else {
      // announce our post format
	   $data['tax_input']['post_format'] = 'post-format-' . $format;
	}

	print_r($data);
 
	return $data;
} }
 
/* fix possibly broken content that hides URLs from the oembed functions */
add_filter('the_content', 'pinboard_ensure_oembed', 1);
function pinboard_ensure_oembed($content) {
    return preg_replace('/^\s*<[^>]*>(http.*)<[^>]*>\s*$/im', '\1' . "\n", $content);
}

add_action('init', 'pinboard_atjine_register_scripts');
/*add_action('wp_enqueue_scripts', 'pinboard_atjine_print_scripts');  */

if ( !function_exists( 'pinboard_atjine_register_scripts' ) ) {
function pinboard_atjine_register_scripts() {
	wp_register_script('pinboard_twitter', 'http://platform.twitter.com/widgets.js', false, false, true);
} }

if ( !function_exists( 'pinboard_atjine_print_scripts' ) ) {
function pinboard_atjine_print_scripts() {
	wp_print_scripts('pinboard_twitter');
} }

/* make twitter oembed width more cooperative */
if ( !function_exists( 'twitter_html_cleanup' ) ) {
function twitter_html_cleanup($html, $url, $args) {
        if (false !== strpos($html, 'class="twitter-tweet"') ) {
		// make width match the theme
                $html = str_replace('width="550"','width="290"',$html);
		// remove redundant author info
                $html = str_replace('&mdash; Gunnar Hellekson (@ghelleks)', '',$html);
        }
        return $html;
} }
add_filter('embed_oembed_html','twitter_html_cleanup',10,3);

/* strip twitter scripts to make everything faster and less beautiful without changing the oembed class*/
if ( !function_exists( 'twitter_no_scripts' ) ) {
function twitter_no_scripts($html, $url, $args) {
/*  <blockquote class="twitter-tweet" width="550"><p>"Losers exist. Don’t hire them." ...or work for them, esp. those compelled to write a screed like this. Gross. <a href="http://t.co/tOGQCTd6" title="http://pocket.co/spHoC">pocket.co/spHoC</a></p>&mdash; Gunnar Hellekson (@ghelleks) <a href="https://twitter.com/ghelleks/status/284678493806149633" data-datetime="2012-12-28T15:13:36+00:00">December 28, 2012</a></blockquote><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>  */
	if (false !== strpos($html, 'class="twitter-tweet"') ) {
		$html = preg_replace('/<script[^>]*><\/script>/', '', $html); 
	}
	return $html;
} }
add_filter('embed_oembed_html','twitter_no_scripts',10,3);

/* Let YARPP build thumbnails */
define('YARPP_GENERATE_THUMBNAILS', true);

/*
FWP+: Strip excerpts from syndicated posts
*/

add_filter(
/*hook=*/ 'syndicated_item_excerpt',
/*function=*/ 'fwp_strip_excerpt',
/*order=*/ 10,
/*arguments=*/ 2
);

/**

 fwp_strip_excerpt: Strips the excerpt for syndicated posts.

 @param string $excerpt The current excerpt for the syndicated item.
 @param SyndicatedPost $post An object representing the syndicated post.
  The syndicated item data is contained in $post->item
  The syndication feed channel data is contained in $post->feed
  The subscription data is contained in $post->link
 @return string The new content to give the syndicated item.

**/



function fwp_strip_excerpt ($excerpt, $post) {
	// Strip it
	$excerpt = '';

	// Send it back
	return $excerpt;
} /* fwp_strip_excerpt() */

/*
 * Overridden because thumbs should go to the permalink, not the img itself
 */
function pinboard_post_image() {
        if( has_post_thumbnail() ) : ?>
                <figure>
                        <a href="<?php echo the_permalink() ?>" title="<?php the_title_attribute(); ?>" >
                                <?php the_post_thumbnail( ( pinboard_is_teaser() ? 'teaser-thumb' : 'image-thumb' ) ); ?>
                        </a>
                </figure>
        <?php else :
                // Retrieve the last image attached to the post
                $args = array(
                        'numberposts' => 1,
                        'post_type' => 'attachment',
                        'post_mime_type' => 'image',
                        'post_parent' => get_the_ID()
                );
                $attachments = get_posts( $args );
                if( count( $attachments ) ) {
                        $attachment = $attachments[0];
                        if( isset( $attachment ) && ! post_password_required() ) :
                                $image = wp_get_attachment_image_src( $attachment->ID, 'full' ); ?>
                                <figure>
                        		<a href="<?php echo the_permalink() ?>" title="<?php the_title_attribute(); ?>">
                                                <?php echo wp_get_attachment_image( $attachment->ID, 'image-thumb' ); ?>
                                        </a>
                                </figure>
                        <?php endif;
                } elseif( false !== pinboard_get_first_image() ) {
                        if( ! post_password_required() ) :
                                $image = pinboard_get_first_image();
                                if( false === $image[1] )
                                        $image[1] = 695;
                                if( false === $image[2] )
                                        $image[2] = 430;
                                $attachment = get_post( get_the_ID() ); ?>
                                <figure>
                        		<a href="<?php echo the_permalink() ?>" title="<?php the_title_attribute(); ?>">
                                                <img src="<?php echo $image[0]; ?>" alt="<?php the_title_attribute(); ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>" />
                                        </a>
                                </figure>
                        <?php endif;
                } else {
                        the_content();
                }
        endif;
}

/**
 * Slider should display title of the first item instead of hiding it
 *
 * @since Pinboard 1.0
 */
function pinboard_call_scripts() { ?>

<script async type="text/javascript">
	jQuery(document).ready(function($) {
		$('#access .menu > li > a').each(function() {
			var title = $(this).attr('title');
			if(typeof title !== 'undefined' && title !== false) {
				$(this).append('<br /> <span>'+title+'</span>');
				$(this).removeAttr('title');
			}
		});
		function pinboard_move_elements(container) {
			if( container.hasClass('onecol') ) {
				var thumb = $('.entry-thumbnail', container);
				if('undefined' !== typeof thumb)
					$('.entry-container', container).before(thumb);
				var video = $('.entry-attachment', container);
				if('undefined' !== typeof video)
					$('.entry-container', container).before(video);
				var gallery = $('.post-gallery', container);
				if('undefined' !== typeof gallery)
					$('.entry-container', container).before(gallery);
				var meta = $('.entry-meta', container);
				if('undefined' !== typeof meta)
					$('.entry-container', container).after(meta);
			}
		}
		function pinboard_restore_elements(container) {
			if( container.hasClass('onecol') ) {
				var thumb = $('.entry-thumbnail', container);
				if('undefined' !== typeof thumb)
					$('.entry-header', container).after(thumb);
				var video = $('.entry-attachment', container);
				if('undefined' !== typeof video)
					$('.entry-header', container).after(video);
				var gallery = $('.post-gallery', container);
				if('undefined' !== typeof gallery)
					$('.entry-header', container).after(gallery);
				var meta = $('.entry-meta', container);
				if('undefined' !== typeof meta)
					$('.entry-header', container).append(meta);
				else
					$('.entry-header', container).html(meta.html());
			}
		}
		if( ($(window).width() > 960) || ($(document).width() > 960) ) {
			// Viewport is greater than tablet: portrait
		} else {
			$('#content .post').each(function() {
				pinboard_move_elements($(this));
			});
		}
		$(window).resize(function() {
			if( ($(window).width() > 960) || ($(document).width() > 960) ) {
				<?php if( is_category( pinboard_get_option( 'portfolio_cat' ) ) || ( is_category() && cat_is_ancestor_of( pinboard_get_option( 'portfolio_cat' ), get_queried_object() ) ) ) : ?>
					$('#content .post').each(function() {
						pinboard_restore_elements($(this));
					});
				<?php else : ?>
					$('.page-template-template-full-width-php #content .post, .page-template-template-blog-full-width-php #content .post, .page-template-template-blog-four-col-php #content .post').each(function() {
						pinboard_restore_elements($(this));
					});
				<?php endif; ?>
			} else {
				$('#content .post').each(function() {
					pinboard_move_elements($(this));
				});
			}
			if( ($(window).width() > 760) || ($(document).width() > 760) ) {
				var maxh = 0;
				$('#access .menu > li > a').each(function() {
					if(parseInt($(this).css('height'))>maxh) {
						maxh = parseInt($(this).css('height'));
					}
				});
				$('#access .menu > li > a').css('height', maxh);
			} else {
				$('#access .menu > li > a').css('height', 'auto');
			}
		});
		if( ($(window).width() > 760) || ($(document).width() > 760) ) {
			var maxh = 0;
			$('#access .menu > li > a').each(function() {
				var title = $(this).attr('title');
				if(typeof title !== 'undefined' && title !== false) {
					$(this).append('<br /> <span>'+title+'</span>');
					$(this).removeAttr('title');
				}
				if(parseInt($(this).css('height'))>maxh) {
					maxh = parseInt($(this).css('height'));
				}
			});
			$('#access .menu > li > a').css('height', maxh);
			<?php if( pinboard_get_option( 'fancy_dropdowns' ) ) : ?>
				$('#access li').mouseenter(function() {
					$(this).children('ul').css('display', 'none').stop(true, true).fadeIn(250).css('display', 'block').children('ul').css('display', 'none');
				});
				$('#access li').mouseleave(function() {
					$(this).children('ul').stop(true, true).fadeOut(250).css('display', 'block');
				});
			<?php endif; ?>
		} else {
			$('#access li').each(function() {
				if($(this).children('ul').length)
					$(this).append('<span class="drop-down-toggle"><span class="drop-down-arrow"></span></span>');
			});
			$('.drop-down-toggle').click(function() {
				$(this).parent().children('ul').slideToggle(250);
			});
		}
		<?php if( ( is_home() && ! is_paged() ) || ( is_front_page() && ! is_home() ) || is_page_template( 'template-landing-page.php' ) ) : ?>
			$('#slider').flexslider({
				selector: '.slides > li',
				video: true,
				prevText: '&larr;',
				nextText: '&rarr;',
				pausePlay: true,
				pauseText: '||',
				playText: '>',
				before: function() {
					$('#slider .entry-title').hide();
				},

				after: function() {
					$('#slider .entry-title').fadeIn();
				}
			});
			$('#slider .entry-title').show();
		<?php endif; ?>
		<?php if( ! is_singular() || is_page_template( 'template-blog.php' ) || is_page_template( 'template-blog-full-width.php' ) || is_page_template( 'template-blog-four-col.php' ) || is_page_template( 'template-blog-left-sidebar.php' ) || is_page_template( 'template-blog-no-sidebars.php' ) || is_page_template( 'template-blog-no-sidebars.php' ) || is_page_template( 'template-portfolio.php' ) || is_page_template( 'template-portfolio-right-sidebar.php' ) || is_page_template( 'template-portfolio-four-col.php' ) || is_page_template( 'template-portfolio-left-sidebar.php' ) || is_page_template( 'template-portfolio-no-sidebars.php' ) ) : ?>
			var $content = $('.entries');
			$content.imagesLoaded(function() {
				$content.masonry({
					itemSelector : '.post',
					columnWidth : function( containerWidth ) {
                                               return containerWidth / 12;
					},
				});
			});
			<?php if( ( ! is_singular() && ! is_paged() ) || ( ( is_page_template( 'template-blog.php' ) || is_page_template( 'template-blog-full-width.php' ) || is_page_template( 'template-blog-four-col.php' ) || is_page_template( 'template-blog-left-sidebar.php' ) || is_page_template( 'template-blog-no-sidebars.php' ) || is_page_template( 'template-blog-no-sidebars.php' ) || is_page_template( 'template-portfolio.php' ) || is_page_template( 'template-portfolio-right-sidebar.php' ) || is_page_template( 'template-portfolio-four-col.php' ) || is_page_template( 'template-portfolio-left-sidebar.php' ) || is_page_template( 'template-portfolio-no-sidebars.php' ) ) && ! is_paged() ) ) : ?>
				<?php if( 'ajax' == pinboard_get_option( 'posts_nav' ) ) : ?>
					var nav_link = $('#posts-nav .nav-all a');
					if(!nav_link.length)
						var nav_link = $('#posts-nav .nav-next a');
					if(nav_link.length) {
						nav_link.addClass('ajax-load');
						nav_link.html('Load more posts');
						nav_link.click(function() {
							var href = $(this).attr('href');
							nav_link.html('<img src="<?php echo get_template_directory_uri(); ?>/images/loading.gif" style="float: none; vertical-align: middle;" /> Loading more posts &#8230;');
							$.get(href, function(data) {
								var helper = document.createElement('div');
								helper = $(helper);
								helper.html(data);
								var content = $('#content .entries', helper);
								$('.entries').append(content.html());
								var nav_url = $('#posts-nav .nav-next a', helper).attr('href');
								if(typeof nav_url !== 'undefined') {
									nav_link.attr('href', nav_url);
									nav_link.html('Load more posts');
								} else {
									$('#posts-nav').html('<span class="ajax-load">There are no more posts to display.</span>');
								}
							});
							return false;
						});
					}
				<?php elseif( 'infinite' == pinboard_get_option( 'posts_nav' ) ) : ?>
					$('#content .entries').infinitescroll({
						debug           : false,
						nextSelector    : "#posts-nav .nav-all a, #posts-nav .nav-next a",
						loadingImg      : ( window.devicePixelRatio > 1 ? "<?php echo get_template_directory_uri(); ?>/images/ajax-loading_2x.gif" : "<?php echo get_template_directory_uri(); ?>/images/ajax-loading.gif" ),
						loadingText     : "Loading more posts &#8230;",
						donetext        : "There are no more posts to display.",
						navSelector     : "#posts-nav",
						contentSelector : "#content .entries",
						itemSelector    : "#content .entries .post",
					}, function(entries){
						var $entries = $( entries ).css({ opacity: 0 });
						$entries.imagesLoaded(function(){
							$entries.animate({ opacity: 1 });
							$content.masonry( 'appended', $entries, true );
						});
						if( ($(window).width() > 960) || ($(document).width() > 960) ) {
							// Viewport is greater than tablet: portrait
						} else {
							$('#content .post').each(function() {
								pinboard_move_elements($(this));
							});
						}
						$('audio,video').mediaelementplayer({
							videoWidth: '100%',
							videoHeight: '100%',
							audioWidth: '100%',
							alwaysShowControls: true,
							features: ['playpause','progress','tracks','volume'],
							videoVolume: 'horizontal'
						});
						$(".entry-attachment, .entry-content").fitVids({ customSelector: "iframe, object, embed"});
						<?php if( pinboard_get_option( 'lightbox' ) ) : ?>
							jQuery('a.colorbox').colorbox({maxHeight:"90%",maxWidth:"90%"});
						<?php endif; ?>
					});
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
		$('audio,video').mediaelementplayer({
			videoWidth: '100%',
			videoHeight: '100%',
			audioWidth: '100%',
			alwaysShowControls: true,
			features: ['playpause','progress','tracks','volume'],
			videoVolume: 'horizontal'
		});
		$(".entry-attachment, .entry-content").fitVids({ customSelector: "iframe, object, embed"});
		<?php if( pinboard_get_option( 'lightbox' ) ) : ?>
			/* take up a bunch of the screen, and include a link to the full image page.
 				this only works if you've told the Pinboard theme to display "full images with lightbox"
			*/
			jQuery("a[rel*='lightbox'],a.colorbox").colorbox({
				maxHeight:"95%",
				maxWidth:"95%",
				transition:"elastic",
				speed:150,
				title: function () { return this.title.link(this.href.replace('/files/lg-gallery/','/photos/')+'/'); }
			});
		<?php endif; ?>
/* trying to get masonry to reflow after the twitter embed javascript loads */
/*
                jQuery('.entries').imagesLoaded(function() {
 			jQuery('.entries').masonry('reload');
			// jQuery('.entries').masonry //append( jQuery('.post') ).masonry( 'appended', $content, true ); 
		});
*/
	});
</script>
<?php
}


?>
