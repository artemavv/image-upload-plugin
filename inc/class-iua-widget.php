<?php

class Iua_Product_Page_Widget extends WP_Widget {

  
  public function __construct() {
    
    $widget_options = array( 
      'classname' => 'iua_product_page_widget',
      'description' => 'This is a Widget to upload images',
    );
    
    parent::__construct( 'iua_product_page_widget', 'Image Upload Widget', $widget_options );
  }
  
  public function widget( $args, $instance ) {

    $title = apply_filters( 'widget_title', $instance[ 'title' ] );
    $button_name = $instance[ 'button_name' ];
    $prompt_length = $instance[ 'prompt_length' ];
    
    $product_name = get_the_title();
    $user_prompt = ''; // TODO save user prompt and display it on the next page load
    
    echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title']; ?>

    <form>
      <h4>Product name: <?php echo $product_name; ?></h4>
      <?php if ( $prompt_length > 0 ): ?>
        <div style="padding-top:10px;">
          <label for="iua-user-prompt">Enter your prompt (max <?php echo $prompt_length; ?> characters)</label><br>
          <input type="text" style="width:100%;" id="iua-user-prompt" name="user_prompt" value="<?php echo $user_prompt; ?>"/>
        </div>
      <?php endif; ?>
      <div style="padding-top:10px;">
        <label for="iua-user-image">Upload your image:</label>
        <input type="file" id="iua-client-image" name="user_image"/>
      </div>
      <div style="padding-top:10px;">
        <input type="button" class="iua-submit" style="width:100%;" value="<?php echo $button_name; ?>">
      </div>
    </form>
    
    <?php echo $args['after_widget'];
  }
  
  public function form( $instance ) {

  $title = ! empty( $instance['title'] ) ? $instance['title'] : ''; 
  $button_name = ! empty( $instance['button_name'] ) ? $instance['button_name'] : 'Upload'; 
  $prompt_length = ! empty( $instance['prompt_length'] ) ? $instance['prompt_length'] : 0; 
  
  ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
      <input type="text" 
             id="<?php echo $this->get_field_id( 'title' ); ?>" 
             name="<?php echo $this->get_field_name( 'title' ); ?>" 
             value="<?php echo esc_attr( $title ); ?>" />
      <label for="<?php echo $this->get_field_id( 'button_name' ); ?>">Button name:</label>
      <input type="text" 
             id="<?php echo $this->get_field_id( 'button_name' ); ?>" 
             name="<?php echo $this->get_field_name( 'button_name' ); ?>" 
             value="<?php echo esc_attr( $button_name ); ?>" />
      <label for="<?php echo $this->get_field_id( 'prompt_length' ); ?>">Allowed Prompt Length:</label>
      <input type="number" 
             id="<?php echo $this->get_field_id( 'prompt_length' ); ?>" 
             name="<?php echo $this->get_field_name( 'prompt_length' ); ?>" 
             value="<?php echo esc_attr( $prompt_length ); ?>" />
      <small>Enter 0 to hide prompt field in widget</small>
    </p>
    
    <?php 

  }
  
  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
    $instance[ 'button_name' ] = strip_tags( $new_instance[ 'button_name' ] );
    $instance[ 'prompt_length' ] = abs(intval( $new_instance[ 'prompt_length' ] ) );
    return $instance;
  }
}