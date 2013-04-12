<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>> <!--<![endif]-->
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
  
  <title><?php colabs_title(); ?></title>
  <meta name="description" content="">

  <?php colabs_meta(); colabs_meta_head(); ?>
   <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>" type="text/css" media="screen" />
  <?php colabs_head();
  
  global $site_title,$site_url;
  
  $site_title = get_bloginfo( 'name' );
  $site_url = home_url( '/' );
  $site_description = get_bloginfo( 'description' );
  
  ?>
  
  <?php if(get_option('photogram_disable_mobile')=='false'){?>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <?php }?>
  <?php wp_head();?>

<link rel="stylesheet" type="text/css" href="wp-includes/css/geo.css" media="screen, projection, tv">

<script language="javascript" type="text/javascript" src="wp-includes/js/jquery/jquery2.js"></script>
<script language="javascript" type="text/javascript" src="wp-includes/js/jquery/jquery.cookie.js"></script>

</head>
<body <?php body_class(); ?>>
<header class="header container"> 
  <div class="row">
    <div class="branding">
      <h1 class="logo">
	    <a href="<?php echo $site_url;?>">
			<?php
			if (get_option('photogram_logotitle')=='logo'){
				echo '<img src="' . get_option('photogram_logo') . '" alt="' . $site_title . '" />';
			}else {
				echo $site_title;
			} // End IF Statement
			?>
  		</a>
	  </h1>
    </div><!-- .branding -->
    
    <button class="collapse-toggle collapsed">
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
    <div class="nav-collapse">
      <?php wp_nav_menu( array( 'theme_location' => 'primary', 'container_class' => 'topnav', 'container' => 'nav', 'menu_class' => '', 'fallback_cb'=>'colabs_fallback_menu') );?><!-- .topnav -->
    </div>
    
  </div>
</header>

<?php if(is_home()){ 
query_posts(array('post__in' => get_option('sticky_posts'), 'ignore_sticky_posts' => 1));
if (have_posts()) :
?>
<section class="featured-slider container slider-collapse">
  <div class='row'>
  <div class="slider-container">
    <?php 

		while (have_posts()) : the_post(); if(colabs_image('link=img&return=true')==null) continue;
		$image = colabs_image('width=55&height=55&return=true');
		$pattern = '/src="([^"]*)"/';
		preg_match($pattern, $image, $matches);
		$src = $matches[1];
		unset($matches);
		echo'
			<div class="slider-slide" data-thumbnail="'.$src.'">
			  <div class="slider-image">
				'.colabs_image('width=500&height=370&return=true').'
			  </div>
			<!-- <div class="slider-text">
				<h2 class="slider-title"><a href="'.get_permalink().'">'.get_the_title().'</a></h2>
				<p>'.home_excerpt().'</p>
			  </div> -->
			</div><!-- .slider-slide -->
		';
		endwhile;
		wp_reset_query();
    ?>
	
  </div><!-- .slider-container -->
  </div>
  
  <div class="slider-dir-nav row">
    <a href="#" class="prev"></a>
    <a href="#" class="next"></a>
  </div>
  <div class="slider-nav row"></div>
</section> <!-- .featured-slider -->
<?php 
endif;
} 
?>
<div class="main-container container"> 
  <div class="row">