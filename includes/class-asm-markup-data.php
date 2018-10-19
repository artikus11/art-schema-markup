<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class ASM_Markup
 *
 * @author Artem Abramovich
 * @since  2.1.0
 */
class ASM_Markup {
	
	public $image;
	
	public $logo;
	
	public $point;
	
	/**
	 * ASM_Markup constructor.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		
		$options = get_option( 'asm_option_name' );
		
		$this->image = $options['image_default'];
		$this->logo  = $options['image_logo'];
		$this->point = $options['select_point'];
		
		add_action( 'wp_head', array( $this, 'out_header_markup' ), 10 );
		add_action( 'wp_footer', array( $this, 'out_footer_markup' ), 100 );
		add_action( 'the_content', array( $this, 'out_after_before_content_markup' ), 1 );
	}
	
	/**
	 * Output markup in the <head> tag
	 *
	 * @since 2.1.0
	 *
	 */
	public function out_header_markup() {
		
		if ( 'header' == $this->point ) {
			echo $this->markup_schema();
		}
	}
	
	/**
	 * Получение полной разметки
	 *
	 *
	 */
	public function markup_schema() {
		
		$posts_schema = '<!-- This markup start  --><script type="application/ld+json">';
		$posts_schema .= $this->get_markup_data();
		$posts_schema .= '</script><!-- This markup end  --> ';
		
		if ( is_front_page() && ! is_singular( array( 'post', 'page' ) ) ) {
			return false;
		}
		
		return $posts_schema;
	}
	
	/**
	 * Collect data and get json string
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function get_markup_data() {
		
		$post = get_post();
		
		$canonical_link = get_the_permalink( $post->ID );
		
		$post_type_markup = is_single() ? 'BlogPosting' : 'Article';
		
		$post_image = $this->get_images( $post );
		
		$post_markup = array(
			'@context'         => 'https://schema.org',
			'@type'            => $post_type_markup,
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => $canonical_link,
			),
			'url'              => $canonical_link,
			'headline'         => $post->post_title,
			'image'            => array(
				'@type'  => 'ImageObject',
				'url'    => $post_image[0],
				'width'  => $post_image[1],
				'height' => $post_image[2],
			),
			'datePublished'    => $post->post_date,
			'dateModified'     => $post->post_modified,
			'articleSection'   => $this->get_category(),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'logo'  => array(
					'@type'  => 'ImageObject',
					'url'    => $this->logo,
					'width'  => 600,
					'height' => 60,
				),
			),
			'author'           => array(
				'@type' => 'Person',
				'name'  => $this->get_author_data( $post ),
				'url'   => get_author_posts_url( $post->post_author ),
				'image' => array(
					'@type'  => 'ImageObject',
					'url'    => get_avatar_url( $post, "size=24&default=wavatar" ),
					'width'  => 24,
					'height' => 24,
				),
			),
			'comment'          => $this->get_comments( $post ),
		);
		
		return json_encode( $post_markup, JSON_UNESCAPED_UNICODE );
	}
	
	/**
	 * Reception of images.
	 * First of all we get a thumbnail, if not, then the first image from the article,
	 * otherwise the default value
	 *
	 * @since 2.1.0
	 *
	 * @param        $post
	 * @param string $size
	 *
	 * @return array|null
	 */
	public function get_images( $post, $size = 'full' ) {
		
		$image_array = null;
		
		if ( $images_first = has_post_thumbnail( $post->ID ) ) {
			
			$image_array = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $size );
			
		} else {
			
			$image_array = $this->no_thumbnail_post( $post );
			
		}
		
		return $image_array;
	}
	
	/**
	 * Getting the first image from the become
	 *
	 * @since 2.1.0
	 *
	 * @param $post
	 *
	 * @return array
	 */
	public function no_thumbnail_post( $post ) {
		
		preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches );
		
		if ( $matches[0] ) {
			return array( $matches [1][0], '1280', '720' );
		} else {
			return array( $this->image, '1280', '720' );
		}
	}
	
	
	/**
	 * Getting all article categories
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function get_category() {
		
		$categories = get_the_category();
		
		$categories_count = $categories ? count( $categories ) : 0;
		
		$categories_list = '';
		
		if ( $categories_count > 1 ) {
			foreach ( $categories as $category ) {
				$categories_list .= $category->name . ', ';
			}
		} else {
			foreach ( $categories as $category ) {
				$categories_list .= $category->name;
			}
		}
		
		return $categories_list;
	}
	
	
	/**
	 * Getting the author of the article
	 *
	 * @since 2.1.0
	 *
	 * @param $post
	 *
	 * @return mixed
	 */
	public function get_author_data( $post ) {
		
		$author_data = get_userdata( $post->post_author );
		
		return $author_data->data->display_name;
	}
	
	/**
	 * Getting the first 20 article comments
	 *
	 * @since 2.1.0
	 *
	 * @param $post
	 *
	 * @return array
	 */
	public function get_comments( $post ) {
		
		$comment_arr   = array();
		$post_comments = get_comments( array(
			'post_id' => $post->ID,
			'status'  => 'approve',
			'type'    => 'comment',
			'number'  => 20,
		) );
		if ( false != $post_comments ) {
			foreach ( $post_comments as $comment ) {
				$comment_arr[] = array(
					'@type'       => 'Comment',
					'dateCreated' => $comment->comment_date,
					'author'      => array(
						'@type' => 'Person',
						'name'  => $comment->comment_author,
						'url'   => $comment->comment_author_url ? $comment->comment_author_url : 'https//:',
					),
					'description' => strip_tags( $comment->comment_content ),
				);
			}
		}
		
		return $comment_arr;
	}
	
	/**
	 * Display markup before closing </body>
	 *
	 * @since 2.1.0
	 *
	 */
	public function out_footer_markup() {
		
		if ( 'footer' == $this->point ) {
			echo $this->markup_schema();
		}
	}
	
	/**
	 * Display markup before and after content
	 *
	 * @since 2.1.0
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function out_after_before_content_markup( $content ) {
		
		switch ( $this->point ) {
			case 'before_post':
				return $this->markup_schema() . $content;
				break;
			case 'after_post':
				return $content . $this->markup_schema();
				break;
		}
		
		return $content;
	}
	
}