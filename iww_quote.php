<?php
/*
Plugin Name: iWantWorkwear Quote
Plugin URI:
Description: Allows for free bulk quotes and customization quotes
Version: 0.1
Author: Gregory Bastianelli
Author URI: http://d.iwantworkwear.com
*/
add_action( 'wp_enqueue_scripts', 'iww_quote_scripts', 100);
function iww_quote_scripts(){
	wp_enqueue_script('quote_js', plugins_url( 'iww_quote.js', __FILE__ ), array( 'jquery' ), rand(0,199), true);
}

add_action( 'iww_quote_tab', 'bulk_quote_html', 1 );

function bulk_quote_html(){
	global $product;
	$url = site_url() . '/large-quantity/?id=' . $product->get_id();
	?>
	<h3>Bulk Quote</h3>
	<p>Submit a request for bulk discount rates. Use this for large quantity orders that exceed the item quantity price breaks. Quotes are typically processed within 1 business day.</p>
	<a id="bulk-quote-link" href="<?php echo $url; ?>" role="button" class="btn btn-primary">Free Bulk Quote</a>
	<?php
}

add_action( 'woocommerce_product_options_general_product_data', 'iww_add_can_customize' );

// add checkbox to product data - general tab
function iww_add_can_customize(){
  global $woocommerce, $post;
  echo '<div class="options_group">';
  $checked = get_post_meta( get_the_ID(), 'iww_can_customize', true );
  // Checkbox
  woocommerce_wp_checkbox(
    array(
    	'id'            => 'iww_can_customize',
    	'wrapper_class' => '',
    	'label'         => __('Customizable', 'woocommerce' ),
    	'description'   => __( 'Can it be customized?', 'woocommerce' ),
      'cbvalue'       => 1,
    )
  );
  echo '</div>';
}

// Save Fields
add_action( 'woocommerce_process_product_meta', 'iww_add_can_customize_save' );
function iww_add_can_customize_save( $post_id ){
  $woocommerce_checkbox = isset( $_POST['iww_can_customize'] ) ? 1 : 0;
	update_post_meta( $post_id, 'iww_can_customize', $woocommerce_checkbox );
}

function bulk_quote_form(){
	echo '<h1>Bulk Quantity Quote</h1>';
	$id = $_GET['id'];
	$product = wc_get_product( $id );
	if( $product ){
		$type = $product->get_type();
		if( $type == 'variable' ){
			$children = $product->get_children();
		}
		?>
		<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
		<div class="card">
			<div class="card-header">
				<b>Step 1: Item Options</b>
			</div>
			<div class="card-body">
				<table>
					<tr>
						<td width="50%" style="max-height: 100px;"><?php echo $product->get_image(); ?></td>
						<td><?php echo 'Item #: ' . $product->get_sku(); ?></td>
					</tr>
				</table>
				<?php echo ( isset( $children ) ) ? '<p>How many of each option would you like?</p>' : ''; ?>
				<table id="bulk_quote_table">
					<?php ( isset( $children ) ) ? iww_var_form( $children ) : iww_simple_form( $product ); ?>
				</table>
				<h3>Total: <span id="quote_total"></span></h3>
			</div>
		</div>
		<br>
		<div class="card">
			<div class="card-header">
				<b>Step 2: Contact Information</b>
			</div>
			<div class="card-body">
				<div class="card-title">Contact Information</div>
					<div class="form-group">
						<label>Name:</label>
						<input type="text" class="form-control" name="name">
					</div>
					<div class="form-group">
						<label>Email:</label>
						<input type="text" class="form-control" name="email" required>
						<small>Used only for further communications</small>
					</div>
					<div class="form-group">
						<label>Company:</label>
						<input type="text" class="form-control" name="company">
					</div>
					<div class="form-group">
						<label>Street Address:</label>
						<input type="text" class="form-control" name="street">
					</div>
					<div class="form-group">
						<label>Zip:</label>
						<input type="text" class="form-control" name="zip">
						<small>Used for shipping estimate.</small>
					</div>
					<input type="hidden" name="action" value="send_bulk_quote">
					<button type="submit" class="btn btn-iww">Submit</button>
				</div>
			</div>
		</form>
		<?php
	}
}
add_shortcode( 'bulkquote', 'bulk_quote_form');

function iww_var_form( $children ){
	if( !empty( $children ) ){
		foreach( $children as $id ){
			$product = wc_get_product( $id );
			$atts = wc_get_formatted_variation( $product->get_variation_attributes(), true, false, true );
			?>
			<tr>
				<td width="25%"><input type="tel" min=0 name="ids[<?php echo $id; ?>]" /></td>
				<td><?php echo $atts; ?></td>
				<td><span class="price">$<?php echo $product->get_price(); ?></span></td>
			</tr>
			<?php
		}
	}
}

function iww_simple_form( $product ){
	echo '<p>How many would you like?</p>';
	?>
	<tr>
		<td>
			<label>Quantity: </label>
			<input style="width: 25%" type="tel" min=0 name="ids[<?php echo $product->get_id(); ?>]" />
		</td>
	</tr>
	<?php
}



function send_bulk_quote(){
	$quote = array();
	var_dump($_POST);
	foreach( $_POST['ids'] as $id => $qty ){
		if( !empty( $qty ) ){
			array_push( $quote, array( $id => $qty ) );
		}
	}

	$headers = array('Reply-To: '. $_POST['email']);

	if( !empty( $quote ) ){
		$message .= $_POST['name'] . ' would like a quote of the following: <br><br>';
		foreach( $quote as $item ){
			foreach( $item as $id => $qty ){
				$product = wc_get_product( $id );
				$message .= 'QTY: '. $qty .' - SKU: ' . $product->get_sku() . '<br>';
			}
		}

		$message .= '<br>Company: ' . $_POST['company'] . '<br>';
		$message .= 'Ship to: ' . $_POST['street'] . '<br>';
		$message .= 'Zip: ' . $_POST['zip'] . '<br><br>';
		$message .= $headers[0];
	}

	echo $message;
	// if( !empty( $message ) ) wp_mail( 'gregbast1994.com', 'Request for quote', $message, $headers );
	wp_mail( $_POST['email'], 'We got your quote request!', 'We got your email, expect a response same or next business day! <br> Thank you!<br><br> Here is a copy of your quote request for your reference:<br><br>' . $message );
	wp_redirect( '/' );
	exit;
}

add_action( 'admin_post_nopriv_send_bulk_quote', 'send_bulk_quote' );
add_action( 'admin_post_send_bulk_quote', 'send_bulk_quote' );