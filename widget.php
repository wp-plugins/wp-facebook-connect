<?php

//hook widget
add_action('widgets_init', create_function('', 'return register_widget("FB_Connect_Widget");'));

class FB_Connect_Widget extends WP_Widget {
    /** constructor */
    function FB_Connect_Widget() {
        parent::WP_Widget(false, $name = 'Facebook Connect');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {	
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $size = $instance['size'];
        $login_text = $instance['login_text'];
        $logout_text = $instance['logout_text'];
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
                  <div style="text-align:center; margin-bottom:10px;"><?php do_shortcode("[fb_login size='$size' login_text='$login_text' logout_text='$logout_text']"); ?></div>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
	$instance['size'] = strip_tags($new_instance['size']);
	$instance['login_text'] = strip_tags($new_instance['login_text']);
	$instance['logout_text'] = strip_tags($new_instance['logout_text']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        $title = esc_attr($instance['title']);
        $size = esc_attr($instance['size']);
        $login_text = esc_attr($instance['login_text']);
        $logout_text = esc_attr($instance['logout_text']);
        ?>
            <p>
            	<label for="<?php echo $this->get_field_id('title'); ?>">
            		<?php _e('Widget title:'); ?>
            		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
            	</label>
            </p>
            <p>
            	<label for="<?php echo $this->get_field_id('login_text'); ?>">
            		<?php _e('Login button text:'); ?>
            		<input class="widefat" id="<?php echo $this->get_field_id('login_text'); ?>" name="<?php echo $this->get_field_name('login_text'); ?>" type="text" value="<?php echo $login_text; ?>" />
            	</label>
            </p>
            <p>
            	<label for="<?php echo $this->get_field_id('logout_text'); ?>">
            		<?php _e('Logout button text:'); ?>
            		<input class="widefat" id="<?php echo $this->get_field_id('logout_text'); ?>" name="<?php echo $this->get_field_name('logout_text'); ?>" type="text" value="<?php echo $logout_text; ?>" />
            	</label>
            </p>
            <p>
            	<label for="<?php echo $this->get_field_id('size'); ?>">
            		<?php _e('Size:'); ?>
            		<select id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" >
         				<?php $options = array(__('small'), __('medium'), __('large'), __('xlarge')); ?>
         				<?php foreach( (array)$options as $option ){ ?>
         				<?php
         				if( $option != $size )
         					$selected='';
         				else
         					$selected = 'selected="selected"';
         				  ?>
         				<option value="<?php echo $option; ?>" <?php echo $selected; ?> ><?php echo $option; ?></option>
         				<?php } ?>
         		   	</select>
         		</label>
            </p>
        <?php 
    }
}
?>