<?php
require_once('../../../../wp-load.php');
header("Content-Type: text/css");
?>
#gotobottom {
   position:fixed;
   z-index:5000;
   <?php print $ScrollToBottom->options['location_y'] . ':' . $ScrollToBottom->options['location_y_amt']; ?>px;
   <?php print $ScrollToBottom->options['location_x'] . ':' . $ScrollToBottom->options['location_x_amt']; ?>px;
   background:url("<?php print STB_IMAGES_URL . '/' . $ScrollToBottom->options['image']; ?>") no-repeat top left;
   text-indent:-9999em;
   width:<?php print $ScrollToBottom->options['image_width']; ?>px;
   height:<?php print $ScrollToBottom->options['image_height']; ?>px;
   <?php if( $ScrollToBottom->options['enable_scroll_event'] ) : ?>
   display:none;
   <?php endif; ?>
}
#gotobottom:hover,
#gotobottom:active,
#gotobottom:focus {
   outline:0;
}