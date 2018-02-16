<?php
class Woocommerce_Product_Tabs_Product_Category_Walker extends Walker_Category{

  public function start_el(&$output, $term, $depth = 0, $args = array(), $id = 0 ){

    $args = wp_parse_args($args, array(
      'name'    => 'wpt_product_category',
      'checked' => array(),
    ) );

    extract($args);

    $checked_text = '';
    if (is_array($checked)) {
      $checked_text = checked(in_array($term->term_id, $checked), true, false );
    }

    ob_start(); ?>

    <li>
      <input type="checkbox" <?php echo $checked_text; ?> id="category-<?php print $term->term_id; ?>" name="<?php print $name; ?>[]" value="<?php print $term->term_id; ?>" />
      <label for="category-<?php print $term->term_id; ?>">
        <?php print esc_attr($term->name); ?>
      </label>

    <?php // closing LI is added inside end_el

    $output .= ob_get_clean();
  }
}
