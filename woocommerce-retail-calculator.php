<?php
/*----------------------------------------------------------------------------------------------------------------------
Plugin Name: WooCommerce Retail Calculator
Description: A plugin calculating retail prices and margins on product pages. Works with the WooCommerce Cost of Goods plugin.
Version: 1.1.0
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

		add_action( 'quick_edit_custom_box', array( $this, 'get_quickedit_post' ), 10, 2 );
		add_action( 'wp_ajax_render_wc_product_margin_quickedit', array( $this, 'render_quickedit' ), 10, 2 );
		add_action( 'wp_ajax_save_wc_product_margin', array( $this, 'save_margin' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_quickedit_scripts' ) );
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
		$dec = wc_get_price_decimal_separator();
		$tho = wc_get_price_thousand_separator();
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
				<td colspan="2" style="border-bottom:2px solid #eee !important;padding-top:7px;"></td>
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
		function rmCurFormat(v){
			var symbols = {'<?php echo $dec; ?>':'.','<?php echo $tho; ?>':','};
			return v.replace(/<?php echo ($dec == '.' ? '\\' : '') . $dec; ?>|<?php echo ($tho == '.' ? '\\' : '') . $tho; ?>/gi, function(matched){ return symbols[matched]; });
		}

		function addCurFormat(v){
			var symbols = {'.':'<?php echo $dec; ?>',',':'<?php echo $tho; ?>'};
			return v.replace(/\.|,/gi, function(matched){ return symbols[matched]; });
		}

		jQuery('document').ready(function($){
			$('#wc_ret_calc_save').click(function(){
				$('#ret_calc_inputs img,#retail_calc_inputs img').fadeIn();
				$.post(ajaxurl + '?action=save_wc_product_margin',{post_ID:<?php echo $post->ID; ?>,cost:$('#wc_ret_calc_cost').val(),retail:jQuery('#wc_ret_calc_retail').val()},function(){
					$('#ret_calc_inputs img,#retail_calc_inputs img').fadeOut();
					$('#_wc_cog_cost').val($('#wc_ret_calc_cost').val());
					$('#_regular_price,.inline-edit-product:visible input[name=_regular_price]').val($('#wc_ret_calc_retail').val());
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

			$('#wc_ret_calc_retail').bind('change keyup',function(){
				wcCalcMargin();
			});
			$('#wc_ret_calc_margin').bind('change keyup',function(){
				wcCalcRetail();
			});
			$('#wc_ret_calc_cost').bind('change keyup',function(){
				wcCalcRetail();
			});

			$('#wc_ret_calc_margin,#wc_ret_calc_retail,#wc_ret_calc_margin').blur(function(){
				$('#wc_ret_calc_cost').val(addCurFormat(parseFloat(rmCurFormat($('#wc_ret_calc_cost').val())).toFixed(2)));
				$('#wc_ret_calc_retail').val(addCurFormat(parseFloat(rmCurFormat($('#wc_ret_calc_retail').val())).toFixed(2)));
			});

			$('#_wc_cog_cost').change();
			$('#_regular_price').change();

			if(!$('#_wc_cog_cost').length){
				$('#wc_ret_calc_cost').val('<?php echo @array_pop(get_post_meta( $post->ID, '_wc_cog_cost' )); ?>');
				wcCalcMargin();

				/* Some quickedit bindings */
				$('.inline-edit-product:visible input[name=_regular_price]').bind('change keyup',function(){
					$('#wc_ret_calc_retail').val($(this).val() ? $(this).val() : 0);
					wcCalcMargin();
				});

				$('.inline-edit-product:visible input[name=_regular_price]').change();
			}

			$('#wc_ret_calc_margin').blur();

			function wcCalcMargin(){
				$('#wc_ret_calc_margin').val(addCurFormat((parseFloat(rmCurFormat($('#wc_ret_calc_retail').val())) / parseFloat(rmCurFormat($('#wc_ret_calc_cost').val()))).toFixed(3)));
			}
			function wcCalcRetail(){
				$('#wc_ret_calc_retail').val(addCurFormat((parseFloat(rmCurFormat($('#wc_ret_calc_cost').val())) * parseFloat(rmCurFormat($('#wc_ret_calc_margin').val()))).toFixed(2)));
			}
		});
		</script>
		<?php
	}

	/**
	 * Let's add a container for our quickedit module.
	 * Oof.
	 *
	 * @param string $column_name
	 * @param string $post_type
	 */
	public function get_quickedit_post( $column_name, $post_type ) {
		if ( $column_name != 'cost' || $post_type != 'product' ) return;
		?>
		<style type="text/css">
		#ret_calc_inputs a{float:right;}
		#ret_calc_inputs table{
		    padding: 20px;
		    width: 100%;
		    background: #fff;
		    border: 1px solid #eee;
		}
		</style>
		<fieldset class="inline-edit-col-left">
			<div id="ret_calc_inputs" class="inline-edit-col" style="margin-top:35px;"> </div>
		</fieldset>
		<?php
	}

	/**
	 * We'll add some JS to send us the row's post id in quickedit mode.
	 */
	public function add_quickedit_scripts( $hook ) {
		if ( $hook == 'edit.php' && @$_GET['post_type'] == 'product' ) wp_enqueue_script( 'wc_ret_calc_quickedit', plugins_url('scripts/admin_quickedit.js', __FILE__), false, null, true );
	}

	/**
	 * Drop some markup in our quickedit container.
	 */
	public function render_quickedit() {
		?>
		<h4><?php _e( 'Calculate Retail Price', 'woocommerce-retail-calculator' ); ?></h4>
		<?php
		$this->render_calc_meta_box( get_post( $_REQUEST['post_ID'] ) );
		die();
	}

	/**
	 * AJAX action for saving margin / retail updates.
	 */
	function save_margin() {
		global $wpdb;

		update_post_meta( $_REQUEST['post_ID'], '_wc_cog_cost', wc_format_decimal( stripslashes( $_REQUEST['cost'] ) ) );
		update_post_meta( $_REQUEST['post_ID'],'_regular_price', wc_format_decimal( $_REQUEST['retail'] ) );

		die();
	}
}
