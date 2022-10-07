<?php

/**
 * Plugin Name: WooCommerce Réduction par packs
 * Description: Applique automatiquement des réductions dans le panier dès que des packs sont constitués
 * Plugin URI: https://github.com/studiomaiis/wp-plugin-woocommerce-coupon-packs
 * Version: 0.1
 * Requires at least: 5.6
 * Tested up to: 6.0
 * Requires PHP: 7.3
 * Author: Pierre BASSON
 * Author URI: https://www.studiomaiis.net
 * Text Domain: woocommerce-coupon-packs
 * Domain Path: /languages
 *
 * WC requires at least: 5.8
 * WC tested up to: 6.9.4
 *
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required minimums and constants
 */
define( 'WC_COUPON_PACKS_SERVER_URL', 'https://depot.studiomaiis.net/wordpress' );
define( 'WC_COUPON_PACKS_PREFIX', 'wc_coupon_packs' );
define( 'WC_COUPON_PACKS_PLUGIN_NAME', __( 'WooCommerce Réduction par packs', 'woocommerce-coupon-packs') );
define( 'WC_COUPON_PACKS_MAIN_FILE', __FILE__ );
define( 'WC_COUPON_PACKS_ABSPATH', __DIR__ . '/' );
define( 'WC_COUPON_PACKS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_COUPON_PACKS_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );


function woocommerce_coupon_packs() {


	static $plugin;


	if ( ! isset( $plugin ) ) {


		class WC_Coupon_Packs {


			/**
			 * The *Singleton* instance of this class
			 *
			 * @var Singleton
			 */
			private static $instance;


			/**
			 * Returns the *Singleton* instance of this class.
			 *
			 * @return Singleton The *Singleton* instance.
			 */
			public static function get_instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}
				return self::$instance;
			}


			/**
			 * The main Mercanet gateway instance. Use get_main_mercanet_gateway() to access it.
			 *
			 * @var null|WC_Mercanet_Payment_Gateway
			 */
			protected $gateway = null;


			/**
			 * Private clone method to prevent cloning of the instance of the
			 * *Singleton* instance.
			 *
			 * @return void
			 */
			public function __clone() {}


			/**
			 * Private unserialize method to prevent unserializing of the *Singleton*
			 * instance.
			 *
			 * @return void
			 */
			public function __wakeup() {}


			/**
			 * Protected constructor to prevent creating a new instance of the
			 * *Singleton* via the `new` operator from outside of this class.
			 */
			public function __construct() {
				add_action( 'admin_init', [ $this, 'install' ] );
				
				$this->init();
			}


			/**
			 * Init the plugin after plugins_loaded so environment variables are set.
			 */
			public function init() {
				require_once dirname( __FILE__ ) . '/includes/class-mercanet-updater.php';
				require_once dirname( __FILE__ ) . '/includes/acf-packs.php';
				
				$updater = new Packs\Updater( __FILE__, WC_COUPON_PACKS_SERVER_URL );
				$updater->initialize();

				add_filter( 'woocommerce_coupon_discount_types', [ $this, 'coupon_discount_types' ] );
				add_action( 'woocommerce_before_cart', [ $this, 'before_checkout_form' ] );
				add_action( 'woocommerce_before_checkout_form', [ $this, 'before_checkout_form' ] );
				add_filter( 'woocommerce_coupon_custom_discounts_array', [ $this, 'coupon_custom_discounts_array' ], 10, 2 );
				add_filter( 'woocommerce_coupon_message', [ $this, 'coupon_message' ], 10, 3 );
				add_filter( 'woocommerce_coupon_error', [ $this, 'coupon_message' ], 10, 3 );
				add_filter( 'woocommerce_cart_totals_coupon_html', [ $this, 'cart_totals_coupon_html' ], 10, 3 );
				add_filter( 'woocommerce_cart_totals_coupon_label', [ $this, 'cart_totals_coupon_label' ], 10, 2 );
			}


			public function update_plugin_version() {
				delete_option( WC_COUPON_PACKS_PREFIX . '_version' );

				if( ! function_exists('get_plugin_data') ){
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}
				$plugin_data = get_plugin_data( __FILE__ );

				update_option( WC_COUPON_PACKS_PREFIX . '_version', $plugin_data['Version'] );
			}


			public function install() {
				if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
					return;
				}
	
				$this->update_plugin_version();
			}


			public function coupon_discount_types( $discount_types ) {
				$discount_types['packs'] = "Packs";
				
				return $discount_types;
			}
			
			
			public function before_checkout_form() {
			
				if ( is_admin() and ! defined( 'DOING_AJAX' ) ) {
					return;
				}
				
				$posts = get_posts( array(
					'post_type' => 'shop_coupon',
					'posts_per_page' => 1,
					'meta_query' => array(
						array(
							'key' => 'discount_type',
							'value' => 'packs',
						),
						array(
							'key' => 'packs',
							'value' => '1',
						),
					),
				));
				
				if ( count( $posts ) == 1 ) {
					
					$coupon = reset( $posts );

					if ( WC()->cart->get_subtotal() > 0 ) {
						if ( ! in_array( $coupon->post_title, WC()->cart->get_applied_coupons() ) ) {
							WC()->cart->apply_coupon( $coupon->post_title );
						}
					} else {
						WC()->cart->remove_coupon( $coupon->post_title );
					}
				
				}
			}
			
			
			public function coupon_custom_discounts_array( $discounts, $coupon ) {
				
				$cart = WC()->cart;
			
				$reductions_packs = self::process_packs_discounts( $cart );
				
				if ( $reductions_packs[ 'montant' ] > 0 ) {
				
					$discounts[ array_key_first( $discounts ) ] = $reductions_packs[ 'montant' ] * 100;
					
				}
			
				return $discounts;
			}
			
			
			public function coupon_message( $msg, $msg_code, $coupon ) {
				if ( $coupon->get_discount_type() == 'packs' ) {
					$msg = '';
				}
				
				return $msg;
			}


			public function cart_totals_coupon_html( $coupon_html, $coupon, $discount_amount_html ) {
				
				if ( $coupon->get_discount_type() == 'packs' ) {
					$cart = WC()->cart;
					$reductions_packs = self::process_packs_discounts( $cart );
					if ( $reductions_packs[ 'montant' ] == 0 ) {
						$coupon_html = 'Aucun pour le moment';
					} else {
						$coupon_html = $discount_amount_html;
						if ( count( $reductions_packs[ 'detail' ] ) ) {
							$coupon_html .= '<br>' . join( '<br>', $reductions_packs[ 'detail' ] );
						}
					}
				}
				
				return $coupon_html;
				
			}
			
			
			public function cart_totals_coupon_label( $label, $coupon ) {
				if ( $coupon->get_discount_type() == 'packs' ) {
					$label = "Packs constitués";
				}
				
				return $label;
			}

			
			public function process_packs_discounts( $cart ) {
			
				$reductions_packs = array(
					'montant' => 0,
					'detail' => array(),
				);
				$quantites = $groupes = array();
				
				$posts = get_posts(array(
					'post_type' => 'shop_coupon',
					'posts_per_page' => 1,
					'meta_query' => array(
						array(
							'key' => 'discount_type',
							'value' => 'packs',
						),
						array(
							'key' => 'packs',
							'value' => '1',
						),
					),
				));
				
				if (count($posts) == 1) {
			
					if (count($cart->cart_contents)) {
						foreach($cart->cart_contents as $item) {
							$sku = $item['data']->get_sku();
							$quantite = $item['quantity'];
							if (!isset($quantites[$sku])) $quantites[$sku] = array('quantite' => $quantite, 'prix_unitaire' => intval($item['data']->get_price() * 100));
							else $quantites[$sku]['quantite']+= $quantite;
						}
					}
			
					$post_id = $posts[0]->ID;
					$tarifs = get_field('tarifs', $post_id);
					$i = 1;
					
					foreach ($tarifs as $tarif) {
						$groupes[$i] = array(
							'quantite' => 0,
						);
						$skus = explode(',', $tarif['skus']);
						foreach ($skus as $sku) {
							if (isset($quantites[$sku])) {
								$groupes[$i]['quantite']+= $quantites[$sku]['quantite'];
								$prix_unitaire = $quantites[$sku]['prix_unitaire'];
								if (isset($groupes[$i]['prix_unitaire'])) {
									$groupes[$i]['prix_unitaire'] = max($prix_unitaire, $groupes[$i]['prix_unitaire']);
								} else {
									$groupes[$i]['prix_unitaire'] = $prix_unitaire;
								}
							}
						}
						if ($groupes[$i]['quantite'] > 0) {
							$reduction = self::process_packs_discount($tarif, $groupes[$i]['prix_unitaire'], $groupes[$i]['quantite']);
							if ($reduction['montant'] > 0) {
								$reductions_packs['montant']+= $reduction['montant'];
								$reductions_packs['detail'][] = join(', ', $reduction['detail']);
							}
						}
						$i++;
					}
			
				}
			
				return $reductions_packs;
			}
			
			
			public function process_packs_discount( $tarif, $prix_unitaire, $quantite ) {
			
				$reduction = array(
					'montant' => 0,
					'detail' => array(),
				);
				$packs = array();
				
				foreach (array(12, 6, 3) as $nb_unites) {
					$x = intdiv($quantite, $nb_unites);
					if ($x > 0) {
						$reduction_1_pack = ($prix_unitaire / 100) * $nb_unites - $tarif['x'.$nb_unites];
						$reduction['montant']+= $x * $reduction_1_pack;
						$reduction['detail'][] = $x.' pack'.($x > 1 ? 's' : '').' de '.$nb_unites.' '.$tarif['titre'];
					}
					$reste = fmod($quantite, $nb_unites);
					if ($reste > 0) {
						$quantite = $reste;
					} else {
						break;
					}
				}
			
				return $reduction;
			}

		}


		$plugin = WC_Coupon_Packs::get_instance();


	}
	
	return $plugin;

}


add_action( 'plugins_loaded', 'woocommerce_coupon_packs_init' );
function woocommerce_coupon_packs_init() {
	load_plugin_textdomain( 'woocommerce-coupon-packs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_coupon_packs_missing_wc_notice' );
		return;
	}

	if ( ! class_exists( 'acf_pro' ) ) {
		add_action( 'admin_notices', 'woocommerce_coupon_packs_missing_acf_pro_notice' );
		return;
	}

	woocommerce_coupon_packs();
}


function woocommerce_coupon_packs_missing_wc_notice() {
	/* translators: 1. Plugin name 2. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( '%1$s requires WooCommerce to be installed and active. You can download %2$s here.', 'woocommerce-coupon-packs' ), WC_COUPON_PACKS_PLUGIN_NAME, '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}


function woocommerce_coupon_packs_missing_acf_pro_notice() {
	/* translators: 1. Plugin name */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( '%1$s requires ACF Pro to be installed and active.', 'woocommerce-coupon-packs' ), WC_COUPON_PACKS_PLUGIN_NAME ) . '</strong></p></div>';
}

