<?php
/*
 * Plugin Name:       custom export plugin
 * Description:       This is custom export plugin for woocommerece.
 * Version:           1.10.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Zain Hassan
 * Author URI:        https://bitcraftx.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       custom-export-plugin
 */


 add_action('admin_menu', 'wpdocs_unsub_add_pages');

function wpdocs_unsub_add_pages() {
     add_menu_page(
	 	__( 'Woo Export Orders', 'astra' ),
		__( 'Export Orders','astra' ),
		'manage_options',
		'export-orders',
		'woo_export_orders_page_callback',
		''
	);
}

function woo_export_orders_page_callback() 
{	


	if(isset($_POST['get_orders']))
	{	
		$data = array();
		$data[] = array(
			'Order ID',
			'Order Date',
			'Order Status',
			'Product ID',
			'Product Name',
			'User ID',
			'First Name',
			'Last Name',
			'User Email',
			'Price',
			'Custom Product Name & Description'
		);
		$quickbooks_settings_accounts = unserialize(get_option('quickbooks_settings_accounts'));

		$initial_date = $_POST['from_date'];
		$final_date = $_POST['to_date'];
		if($_POST['from_date'] == '' || $_POST['to_date'] == '')
		{
			echo "Please select date range!";
		}else
		{
			$args = array(
				'numberposts'    => '-1',
				'status'=> array( 'wc-completed','wc-refunded', 'wc-processing', 'wc-pending' ),
				'date_created'=> $initial_date .'...'. $final_date,
				'meta_compare' => 'BETWEEN'
			);
			$orders = wc_get_orders( $args );
			foreach ( $orders as $order ) 
			{
				$order_id = $order->get_id();
	
				$items = $order->get_items();
				$order_status  = $order->get_status(); 
				$user_id = get_post_meta( $order_id, '_customer_user', true );
				$customer = new WC_Customer( $user_id );
				$order_date = explode(" ", $order->order_date);
				$fname = $customer->data['first_name'];
				$lname = $customer->data['last_name'];
				$company = $customer->data['billing']['company'];
				$customer_email = $customer->get_email();				
				
				foreach($items as $item)
				{
					$customValueNameDesc = '';
					foreach ( $item->get_meta_data() as $meta_data ) 
					{
						if($meta_data->key=='_wccf_pf_customdetails')//custom field with wocommerce custom field 
						{//name customdetails in plugin field
						  $customValueNameDesc = $meta_data->value;
						  break;
						}
					}
					$woo_product = $item->get_product();
					$price = $order->get_item_total( $item );
					$qty = $item->get_quantity();
					$pname = $item->get_name();
					$pid = $item->get_product_id();
					// $woo_product = wc_get_product( $pid );
					if(is_null($woo_product) || false == $woo_product){
						continue;
					}

					$data[] = array(
						$order_id,
						$order_date['0'],
						$order_status,
						$pid,
						$pname,
						$user_id,
						$fname,
						$lname,
						$customer_email,
						$company,
						$price*$qty,
						$customValueNameDesc 
					);
				}
			}
			
			ob_clean();
    		ob_start();
			header('Content-Type: application/csv');
			header('Content-Disposition: attachment; filename=orders.csv');
			$output = fopen('php://output', 'w');
			foreach ( $data as $line ) {
				fputcsv($output, $line);
			}
			fclose($output);
			die();
		}
	}
	
	if(isset($_POST['export_products']))
	{	
		$products = array();
		$data[] = array(
			'Product Name',
			'Price'
		);
		// echo "Export Products";
		$args = array(
			'posts_per_page' => -1,
			'post_type' => 'product'
		);
		$products = get_posts($args);

		foreach($products as $product)
		{
			$woo_product = wc_get_product( $product->ID );
			$price = $woo_product->get_regular_price();
			$p_title = $product->post_title;

			$data[] = array(
				$p_title,
				$price
			);
		}
		// Generating CSV
		ob_clean();
		ob_start();
		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename=products.csv');
		$output = fopen('php://output', 'w');
		foreach ( $data as $line ) {
			fputcsv($output, $line);
		}
		fclose($output);
		die();
	}

	if(isset($_POST['export_users']))
	{	
		$data = array();
		$data[] = array(
			'First Name',
			'Last Name',
			'Email',
			'Company',
		);
		
		$args = array(
			'role'    => 'customer',
			'orderby' => 'user_nicename',
			'order'   => 'ASC'
		);
		$users = get_users( $args );
		foreach ( $users as $user ) 
		{
			$data[] = array(
				$user->first_name,
				$user->last_name,
				$user->user_email,
				$user->billing_company,
			);
		}

		// Generating CSV
		ob_clean();
		ob_start();
		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename=users.csv');
		$output = fopen('php://output', 'w');
		foreach ( $data as $line ) {
			fputcsv($output, $line);
		}
		fclose($output);
		die();
	}
	
	if($_GET['page'] == 'export-orders'){
		
		?>
			<style>
				.export-section {
					max-width: 420px;
				}
				.export-btn {
					width: 150px;
				}
			</style>
			<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
			
			<div class="export-section">
				<h3>Export Orders</h3>
				<form method="POST">
					<label><b>From:</b></label> <input type="text" class="datepicker" name="from_date">
					<label><b>To:</b></label> <input type="text" class="datepicker" name="to_date">
					<p><input class="export-btn" type="submit" name="get_orders" value="Export Orders"></p>
				</form>

				<hr>
				<form method="POST">
					<p>
						<input class="export-btn" type="submit" name="export_products" value="Export Products">
					</p>
				</form>
				<hr>
				<form method="POST">
					<p>
						<input class="export-btn" type="submit" name="export_users" value="Export Users">
					</p>
				</form>
			</div>

			<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
			<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
			<script>
				jQuery( function() {
					jQuery( ".datepicker" ).datepicker(
						{ dateFormat: 'yy-mm-dd' }
					);
				} );
  			</script>
		<?php
	}
}