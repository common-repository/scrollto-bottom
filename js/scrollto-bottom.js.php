<?php
require( '../../../../wp-load.php' );
header('Content-Type: text/javascript');
?>
(function($) {
   var isTransitioned = true;
   var transparent = 0;
   var translucent = 0.3;
   var opaque = 1;

<?php if( $ScrollToBottom->options['enable_scroll_event'] ) : ?>
   var fade = function() {
   console.log($(document).scrollTop());
      if(isTransitioned) {
         isTransitioned = false;
         if(<?php print $ScrollToBottom->options['scroll_event_location']; ?> < $(document).height() - $(document).scrollTop()) {
            $("#gotobottom").show().fadeTo("slow", translucent, function() {
               isTransitioned = true;
            });
         } else {
            $("#gotobottom").fadeTo("slow", transparent, function() {
               isTransitioned = true;
               $(this).hide();
            });
         }
      }
   }
<?php endif; ?>

   $(function() {
      $("body").prepend('<a id="bottom"></a>\n<a href="#bottom" id="gotobottom" class="gotobottom">Bottom of page</a>');

<?php if( $ScrollToBottom->options['enable_scroll_event'] ) : ?>
      fade();
      $(document).scroll(fade);
<?php endif; ?>

<?php if( !$ScrollToBottom->options['enabled_scroll_event'] ) : ?>
      $("#gotobottom").fadeTo(0, translucent);
<?php endif; ?>

      $("#gotobottom").click(function() {
         $.scrollTo($(document).height(), <?php print $ScrollToBottom->options['scroll_speed']; ?>);
<?php if( $ScrollToBottom->options['enable_scroll_event'] ) : ?>
         $(this).fadeOut();
<?php endif; ?>
         return false;
      });

      $("#gotobottom").mouseover(function() {
         if(isTransitioned) {
            $(this).fadeTo("slow",opaque);
         }
      }).mouseout(function() {
         if(isTransitioned) {
            $(this).fadeTo("slow",translucent);
         }
      });
   });
})(jQuery);