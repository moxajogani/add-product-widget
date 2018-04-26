<?php

/**
 * The template for displaying add product widget entries.
 *
 * @author  Moxa Jogani
 * @package Add-Products-Widget/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
?>
<li>
	<?php do_action( 'ap_widget_product_item_start', $args ); ?>

	<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
		<?php echo $product->get_image(); ?>
		<span class="product-title"><?php echo $product->get_name(); ?></span>
	</a>

	<?php if ( ! empty( $show_rating ) ) :
		echo wc_get_rating_html( $product->get_average_rating() );
	endif;

	echo $product->get_price_html();
	
	?>
	<div style="width:130px;"> 
		<?php
		woocommerce_template_loop_add_to_cart();
		?>
	</div>
	<?php
	do_action( 'ap_widget_product_item_end', $args ); ?>
</li>