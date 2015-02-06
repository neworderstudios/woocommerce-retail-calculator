<?php
/*----------------------------------------------------------------------------------------------------------------------
Plugin Name: WooCommerce Retail Calculator
Description: A plugin calculating retail prices and margins on product pages. Works with the WooCommerce Cost of Goods plugin.
Version: 1.0
Author: New Order Studios
Author URI: http://neworderstudios.com/
----------------------------------------------------------------------------------------------------------------------*/

if ( is_admin() ) {
    new wcRetailCalc();
}

class wcRetailCalc {
	protected $c;

	public function __construct() {
		load_plugin_textdomain( 'woocommerce-retail-calculator', false, basename( dirname(__FILE__) ) . '/i18n' );

		add_action( 'wp_ajax_save_wc_product_margin', array( $this, 'save_margin' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
	}

	/**
	 * Let's add the calculator metabox.
	 */
	public function add_metabox( $post_type ) {
		$this->c = get_woocommerce_currency_symbol();

		if ( $post_type == 'product' ) {

			add_meta_box(
				'retail_calc_inputs'
				,__( 'Calculate Retail Price', 'woocommerce-retail-calculator' )
				,array( $this, 'render_calc_meta_box' )
				,$post_type
				,'side'
				,'core'
			);
		
		}
	}

	/**
	 * Let's render the calculator box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_calc_meta_box( $post ) {
		?>
		<table cellpadding="0" cellspacing="0">
			<tr>
				<td width="50%" align="right"><?php echo __( 'Cost of goods', 'woocommerce-retail-calculator' ); ?>: &nbsp;<?php echo $this->c; ?></td>
				<td width="50%" align="right"><input type="text" style="width:98%;" id="wc_ret_calc_cost" value="0" /></td>
			</tr>
			<tr>
				<td width="50%" align="right" style="padding-top:7px;"><?php echo __( 'Margin', 'woocommerce-retail-calculator' ); ?>: &nbsp;</td>
				<td width="50%" align="right" style="padding-top:7px;"><input type="text" style="width:98%;" id="wc_ret_calc_margin" value="0" /></td>
			</tr>
			<tr>
				<td colspan="2" style="border-bottom:2px solid #eee;padding-top:7px;"></td>
			</tr>
			<tr>
				<td width="50%" align="right" style="padding-top:7px;"><?php echo __( 'Retail price', 'woocommerce-retail-calculator' ); ?>: &nbsp;<?php echo $this->c; ?></td>
				<td width="50%" align="right" style="padding-top:7px;"><input type="text" style="width:98%;" id="wc_ret_calc_retail" value="0" /></td>
			</tr>
		</table>

		<div style="float:right;padding-top:10px;">
			<img src="images/loading.gif" style="display:none;padding-top:12px;" />&nbsp;
			<a class="button button-primary button-large" href="#" id="wc_ret_calc_save"><?php echo __( 'Save', 'woocommerce-retail-calculator' ); ?></a>
		</div>

		<div style="clear:both;"></div>

		<script type="text/javascript">
		jQuery('document').ready(function($){
			$('#wc_ret_calc_save').click(function(){
				$('#retail_calc_inputs img').fadeIn();
				$.post(ajaxurl + '?action=save_wc_product_margin',{post_ID:<?php echo $post->ID; ?>,cost:$('#wc_ret_calc_cost').val(),retail:jQuery('#wc_ret_calc_retail').val()},function(){
					$('#retail_calc_inputs img').fadeOut();
					$('#_wc_cog_cost').val($('#wc_ret_calc_cost').val());
					$('#_regular_price').val($('#wc_ret_calc_retail').val());
				});
				return false;
			});

			$('#_wc_cog_cost').bind('change keyup',function(){
				$('#wc_ret_calc_cost').val($(this).val() ? $(this).val() : 0);
				wcCalcRetail();
			});
			$('#_regular_price').bind('change keyup',function(){
				$('#wc_ret_calc_retail').val($(this).val() ? $(this).val() : 0);
				wcCalcMargin();
			});
			$('#_wc_cog_cost').change();
			$('#_regular_price').change();

			$('#wc_ret_calc_retail').bind('change keyup',function(){
				wcCalcMargin();
			});
			$('#wc_ret_calc_margin').bind('change keyup',function(){
				wcCalcRetail();
			});
			$('#wc_ret_calc_cost').bind('change keyup',function(){
				wcCalcRetail();
			});

			function wcCalcMargin(){
				$('#wc_ret_calc_margin').val(parseFloat($('#wc_ret_calc_retail').val()) - parseFloat($('#wc_ret_calc_cost').val()));
			}
			function wcCalcRetail(){
				$('#wc_ret_calc_retail').val(parseFloat($('#wc_ret_calc_cost').val()) + parseFloat($('#wc_ret_calc_margin').val()));
			}
		});
		</script>
		<?php
	}

	/**
	 * AJAX action for saving margin / retail updates.
	 */
	function save_margin() {
		global $wpdb;

		update_post_meta( $_REQUEST['post_ID'], '_wc_cog_cost', stripslashes( $_REQUEST['cost'] ) );
		update_post_meta( $_REQUEST['post_ID'],'_regular_price', $_REQUEST['retail'] );

		die();
	}
}
