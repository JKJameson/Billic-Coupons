<?php
class Coupons {
	public $settings = array(
		'admin_menu_category' => 'Ordering',
		'admin_menu_name' => 'Coupons',
		'admin_menu_icon' => '<i class="icon-ticket"></i>',
		'description' => 'Configure promotional codes for new orders.',
	);
	function admin_area() {
		global $billic, $db;
		
		if (isset($_GET['Name'])) {
			$coupon = $db->q('SELECT * FROM `coupons` WHERE `name` = ?', urldecode($_GET['Name']));
			$coupon = $coupon[0];
			if (empty($coupon)) {
				err('Coupon does not exist');
			}

			$billic->set_title('Admin/Coupon '.safe($billingcycle['name']));
			echo '<h1><i class="icon-ticket"></i> Coupon '.safe($billingcycle['name']).'</h1>';

			if (isset($_POST['update'])) {
				if (empty($_POST['name'])) {
				    $billic->error('Name can not be empty', 'name');
				} else
				if (strlen($_POST['name'])>20) {
				    $billic->error('Name must be less than 20 characters', 'name');
				} else {
				    $name_check = $db->q('SELECT COUNT(*) FROM `coupons` WHERE `name` = ?', $_POST['name']);
				    if ($name_check[0]['COUNT(*)']>1) {
						$billic->error('Name is already in use by a different coupon', 'name');
				    }
				}
				
				//var_dump($_POST); exit;
				
				if (empty($billic->errors)) {
					$data = array(
						'setup' => $_POST['setup'],
						'setup_type' => $_POST['setup_type'],
						'recurring' => $_POST['recurring'],
						'recurring_type' => $_POST['recurring_type'],
						'recurring_cycles' => $_POST['recurring_cycles'],
						'billingcycles' => $_POST['billingcycles'],
						'user_limit' => $_POST['user_limit'],
						'services_limit' => $_POST['services_limit'],
						'registered_date_start' => strtotime($_POST['registered_date_start']),
						'registered_date_end' => strtotime($_POST['registered_date_end'])+86399,
					);
                	$db->q('UPDATE `coupons` SET `name` = ?, `data` = ?, `plans` = ? WHERE `name` = ?', $_POST['name'], json_encode($data), implode('|', $_POST['plans']), urldecode($_GET['Name']));
                    $billic->redirect('/Admin/Coupons/Name/'.urlencode($_POST['name']).'/');
				}
			}
			
			$billic->show_errors();
			
			$data = json_decode($coupon['data'], true);
			if (!is_array($data)) {
				$data = array(
					'setup' => '0.00',
					'setup_type' => 'percent',
					'recurring' => '0.00',
					'recurring_type' => 'percent',
					'recurring_cycles' => '0',
					'billingcycles' => array(),
					'user_limit' => 1,
					'services_limit' => 0,
					'registered_date_start' => mktime(0, 0, 0, 1, 1, date('Y')),
					'registered_date_end' => mktime(0, 0, 0, 1, 1, date('Y')+1)-1,
				);
			}
			$coupon['plans'] = explode('|', $coupon['plans']);
			
			echo '<form method="POST" class="form-inline"><table class="table table-striped"><tr><th colspan="2">Coupon Settings</th></td></tr>';
			echo '<tr><td width="200">Name</td><td><input type="text" class="form-control" name="name" value="'.$coupon['name'].'"></td></tr>';
			echo '<tr><td>Setup Discount</td><td><div class="form-group"><input type="text" class="form-control" name="setup" value="'.(isset($_POST['setup'])?safe($_POST['setup']):safe($data['setup'])).'" style="width: 75px"><select name="setup_type" class="form-control"><option value="percent"'.($data['setup_type']=='percent'?' selected':'').'>percent</option><option value="fixed"'.($data['setup_type']=='fixed'?' selected':'').'>'.get_config('billic_currency_code').'</option></select><label>&nbsp;once off&nbsp;</label></div></td></tr>';
			echo '<tr><td>Recurring Discount</td><td><div class="form-group"><input type="text" class="form-control" name="recurring" value="'.(isset($_POST['recurring'])?safe($_POST['recurring']):safe($data['recurring'])).'" style="width: 75px"><select name="recurring_type" class="form-control"><option value="percent"'.($data['recurring_type']=='percent'?' selected':'').'>percent</option><option value="fixed"'.($data['recurring_type']=='fixed'?' selected':'').'>'.get_config('billic_currency_code').'</option></select><label>&nbsp;for the first month and then&nbsp;</label><input type="text" class="form-control" name="recurring_cycles" value="'.(isset($_POST['recurring_cycles'])?safe($_POST['recurring_cycles']):safe($data['recurring_cycles'])).'" style="width: 50px"><label>&nbsp;billing cycles after the first month.</label></div></td></tr>';
			echo '<tr><td>Billing Cycles</td><td><select name="billingcycles[]" id="coupon_billingcycles" multiple="multiple">';
			$billingcycles = $db->q('SELECT `name` FROM `billingcycles` ORDER BY `name`');
			foreach($billingcycles as $billingcycle) {
				$name = $billingcycle['name'];
				echo '<option value="'.safe($name).'"'.(in_array($name, $data['billingcycles'])?' selected':'').'>'.safe($name).'</option>';
			}
			echo '</select></td></tr>';
			echo '<tr><td>Plans</td><td><select name="plans[]" id="coupon_plans" multiple="multiple">';
			$plans = $db->q('SELECT `name` FROM `plans` ORDER BY `name`');
			foreach($plans as $plan) {
				$name = $plan['name'];
				echo '<option value="'.safe($name).'"'.(in_array($name, $coupon['plans'])?' selected':'').'>'.safe($name).'</option>';
			}
			echo '</select></td></tr>';
			echo '<tr><td>&nbsp;</td><td><label>Limit the use&nbsp;</label><input type="text" class="form-control" name="user_limit" value="'.(isset($_POST['user_limit'])?safe($_POST['user_limit']):safe($data['user_limit'])).'" style="width: 50px"><label>&nbsp;times per user.</label></div></td></tr>';
			echo '<tr><td>&nbsp;</td><td><label>Limit to users with no more than&nbsp;</label><input type="text" class="form-control" name="services_limit" value="'.(isset($_POST['services_limit'])?safe($_POST['services_limit']):safe($data['services_limit'])).'" style="width: 50px"><label>&nbsp;active services.</label></div></td></tr>';
			echo '<tr><td>&nbsp;</td><td><label>Limit to users registered from&nbsp;</label><input type="text" class="form-control" name="registered_date_start" id="registered_date_start" value="'.date('Y-m-d', $data['registered_date_start']).'" class="form-control" style="width: 100px"><label>&nbsp;to&nbsp;</label><input type="text" class="form-control" name="registered_date_end" id="registered_date_end" value="'.date('Y-m-d', $data['registered_date_end']).'" class="form-control" style="width: 100px"></div></td></tr>';
			echo '</td></tr><tr><td colspan="4" align="center"><input type="submit" class="btn btn-success" name="update" value="Update &raquo;"></td></tr></table></form>';
			
			echo '<link type="text/css" rel="stylesheet" href="/Modules/Core/bootstrap/bootstrap-multiselect.min.css">';
			echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/css/bootstrap-datepicker.min.css">';
			echo '<script>addLoadEvent(function() {
			$(\'#coupon_billingcycles\').multiselect({
            enableFiltering: true,
			enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: true,
            maxHeight: 400,
        });
		$(\'#coupon_plans\').multiselect({
            enableFiltering: true,
			enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: true,
            maxHeight: 400,
        });
		$.getScript( "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/js/bootstrap-datepicker.min.js", function( data, textStatus, jqxhr ) { $( "#registered_date_start" ).datepicker({ format: "yyyy-mm-dd" }); $( "#registered_date_end" ).datepicker({ format: "yyyy-mm-dd" }); });
			});</script>';
			$billic->add_script('/Modules/Core/bootstrap/bootstrap-multiselect.min.js');
			return;
		}
		
		if (isset($_GET['New'])) {
			$title = 'New Coupon';
			$billic->set_title($title);
			echo '<h1>'.$title.'</h1>';

            $billic->module('FormBuilder');
			$form = array(
				'name' => array(
					'label' => 'Name',
					'type' => 'text',
					'required' => true, 
					'default' => '',
				),
			);
			if (isset($_POST['Continue'])) {
                $billic->modules['FormBuilder']->check_everything(array(
                    'form' => $form,
                ));
				if (strlen($_POST['name'])>20) {
				    $billic->error('Name must be less than 20 characters', 'name');
				}
				if (empty($billic->errors)) {
					$db->insert('coupons', array(
						'name' => $_POST['name'],
					));
					$billic->redirect('/Admin/Coupons/Name/'.urlencode($_POST['name']).'/');
				}
			}
			$billic->show_errors();
            $billic->modules['FormBuilder']->output(array(
                'form' => $form,
                'button' => 'Continue',
            ));
			return;
		}
		
		if (isset($_GET['Delete'])) {
				$db->q('DELETE FROM `coupons` WHERE `name` = ?', urldecode($_GET['Delete']));
				$billic->status = 'deleted';
		}
		
		$total = $db->q('SELECT COUNT(*) FROM `coupons`');
		$total = $total[0]['COUNT(*)'];
		$pagination = $billic->pagination(array(
            'total' => $total,
        ));
		echo $pagination['menu'];
		$coupons = $db->q('SELECT * FROM `coupons` ORDER BY `name` ASC LIMIT '.$pagination['start'].','.$pagination['limit']);

		$billic->set_title('Admin/Coupons');
		echo '<h1><i class="icon-ticket"></i> Coupons</h1>';
		echo '<a href="New/" class="btn btn-success"><i class="icon-plus"></i> New Coupon</a>';
		$billic->show_errors();
		echo '<div style="float: right;padding-right: 40px;">Showing ' . $pagination['start_text'] . ' to ' . $pagination['end_text'] . ' of ' . $total . ' Coupons</div>';
		echo '<table class="table table-striped"><tr><th>Name</th><th>Actions</th></tr>';
		if (empty($coupons)) {
			echo '<tr><td colspan="20">No Coupons matching filter.</td></tr>';
		}
		foreach($coupons as $coupon) {
			echo '<tr><td><a href="/Admin/Coupons/Name/'.urlencode($coupon['name']).'/">'.safe($coupon['name']).'</a></td><td>';
			echo '<a href="/Admin/Coupons/Name/'.urlencode($coupon['name']).'/"><i class="icon-edit-write"></i></a>';
			echo '&nbsp;<a href="/Admin/Coupons/Delete/'.urlencode($coupon['name']).'/" title="Delete" onClick="return confirm(\'Are you sure you want to delete?\');"><i class="icon-remove red"></i></a>';
			echo '</td></tr>';
		}
		echo '</table>';
	}
}
