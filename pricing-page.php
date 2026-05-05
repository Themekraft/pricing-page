<?php
/**
 * Themekraft Pricing Page
 *
 * Renders a parameterized pricing page driven by the `tk_pricing_page_config` filter.
 * Each host plugin sells a single Freemius product (its bundle, or — when no bundle exists —
 * its premium plugin) across three site-license tiers: Personal (1) / Professional (5) /
 * Agency (unlimited). The host plugin provides the product credentials and the per-tier
 * card data via the filter.
 *
 * @package Themekraft\PricingPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tk_pricing_page_get_config' ) ) {
	/**
	 * Returns the page config, applying the `tk_pricing_page_config` filter and
	 * normalizing each tier against a default schema.
	 *
	 * @return array<string,mixed>
	 */
	function tk_pricing_page_get_config() {
		$defaults = array(
			'heading'    => '',
			'subheading' => '',
			'bundle'     => array(
				'product_id' => '',
				'plan_id'    => '',
				'public_key' => '',
				'name'       => '',
			),
			'tiers'      => array(),
		);

		$config = wp_parse_args( apply_filters( 'tk_pricing_page_config', $defaults ), $defaults );

		$config['bundle'] = wp_parse_args( (array) $config['bundle'], $defaults['bundle'] );

		$config['tiers'] = array_map(
			static function ( $tier ) {
				return wp_parse_args(
					$tier,
					array(
						'id'        => '',
						'name'      => '',
						'sites'     => '',
						'licenses'  => '1',
						'price'     => '',
						'currency'  => '$',
						'period'    => '/year',
						'highlight' => false,
						'bullets'   => array(),
						'cta_label' => __( 'Get Started', 'tk-pricing-page' ),
					)
				);
			},
			(array) $config['tiers']
		);

		return $config;
	}
}

if ( ! function_exists( 'tk_pricing_page_enqueue_assets' ) ) {
	/**
	 * Enqueue Freemius checkout + pricing-page assets on any admin screen
	 * whose id contains `bundle_screen` (the convention used across host plugins).
	 */
	function tk_pricing_page_enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || ! str_contains( $screen->id, 'bundle_screen' ) ) {
			return;
		}

		$base_path = plugin_dir_path( __FILE__ );
		$base_url  = plugin_dir_url( __FILE__ );
		$js_path   = $base_path . 'build/js/pricing-page.js';
		$css_path  = $base_path . 'build/css/pricing-page.css';

		wp_enqueue_script( 'freemius-checkout', 'https://checkout.freemius.com/js/v1/', array(), '1', true );
		wp_enqueue_script(
			'tk-pricing-page',
			$base_url . 'build/js/pricing-page.js',
			array( 'freemius-checkout' ),
			file_exists( $js_path ) ? filemtime( $js_path ) : false,
			true
		);
		wp_enqueue_style(
			'tk-pricing-page',
			$base_url . 'build/css/pricing-page.css',
			array(),
			file_exists( $css_path ) ? filemtime( $css_path ) : false
		);

		wp_localize_script( 'tk-pricing-page', 'tkPricingPageConfig', tk_pricing_page_get_config() );
	}
}
add_action( 'admin_enqueue_scripts', 'tk_pricing_page_enqueue_assets' );

if ( ! function_exists( 'tk_pricing_page_render' ) ) {
	/**
	 * Render the pricing page. Use as the callback for `add_submenu_page()` in each host plugin.
	 */
	function tk_pricing_page_render() {
		$config = tk_pricing_page_get_config();

		if ( empty( $config['tiers'] ) || empty( $config['bundle']['product_id'] ) ) {
			echo '<div class="notice notice-warning"><p>';
			esc_html_e( 'Pricing page is not fully configured. The host plugin must register the `tk_pricing_page_config` filter with `bundle` credentials and at least one `tiers` entry.', 'tk-pricing-page' );
			echo '</p></div>';
			return;
		}
		?>
		<div class="tk-pricing">
			<div class="tk-pricing__container">
				<header class="tk-pricing__header">
					<p class="tk-pricing__pre-heading"><?php esc_html_e( 'Pricing', 'tk-pricing-page' ); ?></p>
					<?php if ( '' !== $config['heading'] ) : ?>
						<h1 class="tk-pricing__heading"><?php echo esc_html( $config['heading'] ); ?></h1>
					<?php endif; ?>
					<?php if ( '' !== $config['subheading'] ) : ?>
						<p class="tk-pricing__description"><?php echo esc_html( $config['subheading'] ); ?></p>
					<?php endif; ?>
				</header>
				<div class="tk-pricing__boxes">
					<?php foreach ( $config['tiers'] as $tier ) : ?>
						<?php tk_pricing_page_render_tier( $tier ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'buddyforms_bundle_screen_content' ) ) {
	/**
	 * Backward-compat alias for host plugins still passing the old callback name to `add_submenu_page()`.
	 * Remove once every host plugin has migrated its menu callback to `tk_pricing_page_render`.
	 */
	function buddyforms_bundle_screen_content() {
		tk_pricing_page_render();
	}
}

if ( ! function_exists( 'tk_pricing_page_render_tier' ) ) {
	/**
	 * Render a single tier card.
	 *
	 * @param array<string,mixed> $tier Normalized tier data.
	 */
	function tk_pricing_page_render_tier( $tier ) {
		$box_class = 'tk-pricing__box' . ( ! empty( $tier['highlight'] ) ? ' tk-pricing__box--highlight' : '' );
		?>
		<div class="<?php echo esc_attr( $box_class ); ?>" data-tier-id="<?php echo esc_attr( $tier['id'] ); ?>" data-licenses="<?php echo esc_attr( $tier['licenses'] ); ?>">
			<header class="tk-pricing__box-header">
				<h2 class="tk-pricing__plan"><?php echo esc_html( $tier['name'] ); ?></h2>

				<p class="tk-pricing__price">
					<span class="tk-pricing__price-currency"><?php echo esc_html( $tier['currency'] ); ?></span><?php echo esc_html( $tier['price'] ); ?> <span class="tk-pricing__price-period"><?php echo esc_html( $tier['period'] ); ?></span>
				</p>

				<?php if ( '' !== $tier['sites'] ) : ?>
					<p class="tk-pricing__sites"><?php echo esc_html( $tier['sites'] ); ?></p>
				<?php endif; ?>

				<button class="tk-pricing__button" data-tier-id="<?php echo esc_attr( $tier['id'] ); ?>"><?php echo esc_html( $tier['cta_label'] ); ?></button>
			</header>

			<?php if ( ! empty( $tier['bullets'] ) ) : ?>
				<hr class="tk-pricing__divider" />
				<ul class="tk-pricing__features">
					<?php foreach ( $tier['bullets'] as $bullet ) : ?>
						<?php
						$label     = is_array( $bullet ) && isset( $bullet['label'] ) ? $bullet['label'] : (string) $bullet;
						$url       = is_array( $bullet ) && isset( $bullet['url'] ) ? $bullet['url'] : '';
						$highlight = is_array( $bullet ) && ! empty( $bullet['highlight'] );
						$li_class  = 'tk-pricing__feature' . ( $highlight ? ' tk-pricing__feature--highlight' : '' );
						?>
						<li class="<?php echo esc_attr( $li_class ); ?>">
							<?php if ( '' !== $url ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $label ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $label ); ?>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
