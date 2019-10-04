<?php
/**
 * Add Products Widget
 *
 * @package Add-Products-Widget/Widget
 *
 * @author  Moxa Jogani
 * @since 1.0
 */

/**
 * Creating the widget class
 * 
 * @since 1.0
 */

class ap_widget extends WP_Widget {

	/**
	 * Default Constructor
	 *
	 * @since 1.0
	 */
	public function __construct() {
		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			$this->widget_description = __("Allow customers to add products to the cart.", 'add_products_domain');
			$this->widget_id          = 'ap_widget';
			$this->widget_name        = __('Add Products', 'add_products_domain');
			$this->settings           = array(
				'title'  => array(
					'type'  => 'text',
					'std'   => __('Add Products', 'add_products_domain'),
					'label' => __('Title', 'add_products_domain'),
				),
				'number' => array(
					'type'  => 'number',
					'step'  => 1,
					'min'   => 1,
					'max'   => '',
					'std'   => 5,
					'label' => __('Number of products to show', 'add_products_domain'),
				),
				'show' => array(
					'type'  => 'select',
					'std'   => '',
					'label' => __('Show', 'add_products_domain'),
					'options' => array(
						''         => __('All products', 'add_products_domain'),
						'featured' => __('Featured products', 'add_products_domain'),
						'onsale'   => __('On-sale products', 'add_products_domain'),
					),
				),
				'orderby' => array(
					'type'  => 'select',
					'std'   => 'date',
					'label' => __('Order by', 'add_products_domain'),
					'options' => array(
						'date'   => __('Date', 'add_products_domain'),
						'price'  => __('Price', 'add_products_domain'),
						'rand'   => __('Random', 'add_products_domain'),
						'sales'  => __('Sales', 'add_products_domain'),
					),
				),
				'order' => array(
					'type'  => 'select',
					'std'   => 'desc',
					'label' => _x('Order', 'Sorting order', 'add_products_domain'),
					'options' => array(
						'asc'  => __('ASC', 'add_products_domain'),
						'desc' => __('DESC', 'add_products_domain'),
					),
				)
			);
			parent::__construct('ap_widget', $this->widget_name, $this->settings);
		}
	}
 	
 	/**
 	 * Query the products and return them.
 	 * @param  array $args
 	 * @param  array $instance
 	 * @return WP_Query
 	 */
	public function get_products( $args, $instance ) {
		$number                      = ! empty( $instance['number'] ) ? absint( $instance['number'] )           : $this->settings['number']['std'];
		$show                        = ! empty( $instance['show'] ) ? sanitize_title( $instance['show'] )       : $this->settings['show']['std'];
		$orderby                     = ! empty( $instance['orderby'] ) ? sanitize_title( $instance['orderby'] ) : $this->settings['orderby']['std'];
		$order                       = ! empty( $instance['order'] ) ? sanitize_title( $instance['order'] )     : $this->settings['order']['std'];
		$product_visibility_term_ids = wc_get_product_visibility_term_ids();

		$query_args = array(
			'posts_per_page' => $number,
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'no_found_rows'  => 1,
			'order'          => $order,
			'meta_query'     => array(),
			'tax_query'      => array(
				'relation' => 'AND',
			),
		);

		if ('yes' === get_option('woocommerce_hide_out_of_stock_items')) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_term_ids['outofstock'],
					'operator' => 'NOT IN',
				),
			);
		}

		switch ($show) {
			case 'featured' :
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_term_ids['featured'],
				);
				break;
			case 'onsale' :
				$product_ids_on_sale    = wc_get_product_ids_on_sale();
				$product_ids_on_sale[]  = 0;
				$query_args['post__in'] = $product_ids_on_sale;
				break;
		}
		
		switch ($orderby) {
			case 'price' :
				$query_args['meta_key'] = '_price';
				$query_args['orderby']  = 'meta_value_num';
				break;
			case 'rand' :
				$query_args['orderby']  = 'rand';
				break;
			case 'sales' :
				$query_args['meta_key'] = 'total_sales';
				$query_args['orderby']  = 'meta_value_num';
				break;
			default :
				$query_args['orderby']  = 'date';
		}

		return new WP_Query(apply_filters('woocommerce_products_widget_query_args', $query_args));
	}

	// Creating widget front-end
	public function widget($args, $instance) {
		
			 
		// This is where you run the code and display the output
		ob_start();
		if (($products = $this->get_products($args, $instance)) && $products->have_posts()) {
			$title = apply_filters('widget_title', $instance['title']);
		
			// before and after widget arguments are defined by themes
			echo $args['before_widget'];

			if ( ! empty($title))
				echo $args['before_title'] . $title . $args['after_title'];

			echo wp_kses_post(apply_filters('ap_before_widget_product_list', '<ul class="product_list_widget">'));

			$template_args = array(
				'widget_id'   => $args['widget_id'],
				'show_rating' => true,
			);

			while ($products->have_posts()) {
				$products->the_post();
				//wc_get_template( 'content-widget-product.php', $template_args );

				 wc_get_template( 
					'ap-widget-content.php', 
					$template_args, 
					'add-product-widget', 
					plugin_dir_path( __FILE__ ) . '/templates/' );
			}

			echo wp_kses_post( apply_filters( 'ap_after_widget_product_list', '</ul>' ) );
			echo $args[ 'after_widget' ];	
		}

		wp_reset_postdata();
		echo ob_get_clean();
	}
		         
		// Widget Backend 
	public function form($instance) {
		if (empty($this->settings)) {
			return;
		}

		foreach ($this->settings as $key => $setting) {
			$class = isset($setting['class']) ? $setting['class'] : '';
			$value = isset($instance[$key]) ? $instance[$key] : $setting['std'];
			switch ($setting['type']) {
				case 'text' :
					?>
					<p>
						<label for="<?php echo $this->get_field_id($key); ?>"><?php echo $setting['label']; ?></label>
						<input class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>" name="<?php echo $this->get_field_name($key); ?>" type="text" value="<?php echo esc_attr($value); ?>" />
					</p>
					<?php
				break;

				case 'number' :
					?>
					<p>
						<label for="<?php echo $this->get_field_id($key); ?>"><?php echo $setting['label']; ?></label>
						<input class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>" name="<?php echo $this->get_field_name($key); ?>" type="number" step="<?php echo esc_attr($setting['step']); ?>" min="<?php echo esc_attr($setting['min']); ?>" max="<?php echo esc_attr($setting['max']); ?>" value="<?php echo esc_attr($value); ?>" />
					</p>
					<?php
				break;

				case 'select' :
					?>
					<p>
						<label for="<?php echo $this->get_field_id($key); ?>"><?php echo $setting['label']; ?></label>
						<select class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>" name="<?php echo $this->get_field_name($key); ?>">
							<?php foreach ($setting['options'] as $option_key => $option_value) : ?>
								<option value="<?php echo esc_attr($option_key); ?>" <?php selected($option_key, $value); ?>><?php echo esc_html($option_value); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<?php
				break;

				// Default: run an action
				default :
					do_action('woocommerce_widget_field_' . $setting['type'], $key, $value, $setting, $instance);
				break;
			}
		}
	}
     
	// Updating widget replacing old instances with new
	public function update($new_instance, $old_instance) {
		$instance = $old_instance;

		if (empty($this->settings)) {
			return $instance;
		}

		// Loop settings and get values to save.
		foreach ($this->settings as $key => $setting) {
			if ( ! isset($setting['type'])) {
				continue;
			}

			// Format the value based on settings type.
			switch ($setting['type']) {
				case 'number' :
					$instance[$key] = absint($new_instance[$key]);

					if (isset($setting['min']) && '' !== $setting['min']) {
						$instance[$key] = max($instance[$key], $setting['min']);
					}

					if (isset($setting['max']) && '' !== $setting['max']) {
						$instance[$key] = min($instance[$key], $setting['max']);
					}
				break;
				default:
					$instance[$key] = sanitize_text_field($new_instance[$key]);
				break;
			}

			/**
			 * Sanitize the value of a setting.
			 */
			$instance[$key] = apply_filters('woocommerce_widget_settings_sanitize_option', $instance[$key], $new_instance, $key, $setting);
		}
		return $instance;
	}
} // Class wpb_widget ends here