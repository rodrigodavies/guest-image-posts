<?php
/*
  Template Name: Guest Image Post page
*/
?>
<?php get_header(); ?>

    <div class="main-content-wrapper row">
      <div class="main-content column col12">
        <article class="entry-post">
		<?php if(have_posts()): while(have_posts()): the_post(); ?>	          
          <div class="entry-content row">            
            <div class="entry-text">
              <h3 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>              
              <?php the_content(); ?>

			  <?php wp_link_pages();?>
            </div>
          </div>
		  <?php
		  endwhile; endif;
		  ?>
				
        </article><!-- .entry-post -->
      </div><!-- .main-content -->
      
</div>

<script type="text/javascript">
            
    var latitude;
    var longitude;
    var accuracy;
    
    function loadLocation() {
    
        if(navigator.geolocation) {
            document.getElementById("status").innerHTML = "Your browser supports geolocation - locating you...";
            document.getElementById("status").style.color = "#1ABC3C";
            
            if($.cookie("posLat")) {
                latitude = $.cookie("posLat");
                longitude = $.cookie("posLon");
                accuracy = $.cookie("posAccuracy");
                document.getElementById("status").innerHTML = "We saved your location from a previous visit. <a id=\"clear_cookies\" href=\" javascript:clear_cookies();\" style=\"cursor:pointer;\">Click here to clear it (you'll need to refresh your browser to detect a new location).</a>";
                updateDisplay();
                
            } else {
                navigator.geolocation.getCurrentPosition(
                                    success_handler, 
                                    error_handler, 
                                    {timeout:10000});
            }
        }
    }

    function success_handler(position) {
        latitude = position.coords.latitude;
        longitude = position.coords.longitude;
        accuracy = position.coords.accuracy;
        
        if (!latitude || !longitude) {
            document.getElementById("status").innerHTML = "HTML5 Geolocation supported, but location data is currently unavailable.";
            return;
        }
        
        updateDisplay();
        
        $.cookie("posLat", latitude);
        $.cookie("posLon", longitude);
        $.cookie("posAccuracy", accuracy);
      
    }
    
    function updateDisplay() {
        //var gmapdata = '<iframe width="425" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;ie=UTF8&amp;hq=&amp;ll=' + latitude + ',' + longitude + '&amp;output=embed"></iframe>';
        var gmapdata = '<img src="http://maps.google.com/maps/api/staticmap?center=' + latitude + ',' + longitude + '&zoom=16&size=350x250&sensor=true" />';
                
        document.getElementById("placeholder").innerHTML = gmapdata;
        document.getElementById("latitude").innerHTML = latitude;
        document.getElementById("longitude").innerHTML = longitude;
        document.getElementById("accuracy").innerHTML = accuracy;
    }
    
    
    function error_handler(error) {
        var locationError = '';
        
        switch(error.code){
        case 0:
            locationError = "There was an error while retrieving your location: " + error.message;
            break;
        case 1:
            locationError = "The user prevented this page from retrieving a location.";
            break;
        case 2:
            locationError = "The browser was unable to determine your location: " + error.message;
            break;
        case 3:
            locationError = "The browser timed out before retrieving the location.";
            break;
        }

        document.getElementById("status").innerHTML = locationError;
        document.getElementById("status").style.color = "#D03C02";
    }
    
    function clear_cookies() {
        $.cookie('posLat', null);
        document.getElementById("status").innerHTML = "Cookies cleared.";
    }

    loadLocation();
    
</script>
<?php get_footer(); ?>