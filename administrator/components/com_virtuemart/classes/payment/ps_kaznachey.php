<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) 
  die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );


if(intval(@$_SERVER["SERVER_PORT"]) == 443)
{
	define ('SSL_URL', 'https://'.$_SERVER["SERVER_NAME"]);
}else{
	define ('SSL_URL', 'http://'.$_SERVER["SERVER_NAME"]);
}
		
class ps_kaznachey 
{

	var $classname = "ps_kaznachey";
	var $payment_code = "P2P";
	
function show_configuration() 
{
	global $VM_LANG;
	$db = new ps_DB();
	
	if(!RESULTPATH)
		define ('RESULTPATH', 'kaznachey_result.php');
	


	include_once(CLASSPATH ."payment/".$this->classname.".cfg.php"); // Read current Configuration
	
    ?>
<table>
		 
	<tr>
		<td><strong>Merchant ID</strong></td>
		<td><input type="text" name="MERCHANTGUID" class="inputbox" value="<? echo MERCHANTGUID ?>" /></td>
		<td>Идентификатор магазина в системе kaznachey.ua</td>
	</tr>
	<tr>
		<td><strong>Secret Key</strong></td>
		<td><input type="text" name="MERCHNATSECRETKEY" class="inputbox" value="<?  echo MERCHNATSECRETKEY ?>" /></td>
		<td>Секретный ключ магазина в системе kaznachey.ua</td>
	</tr>
	<tr>
		<td><strong>Путь к файлу возврата</strong></td>
		<td><label for="RESULTPATH"><?=SSL_URL.'/'?></label><input type="text" name="RESULTPATH" class="inputbox" value="<?  echo RESULTPATH ?>" /></td>
		<td>путь к kaznachey_result.php</td>
	</tr>

	<tr>
		<td><strong><?php echo $VM_LANG->_('PHPSHOP_ADMIN_CFG_PAYMENT_ORDERSTATUS_SUCC') ?></strong></td>
		<td>
			<select name="P2P_VERIFIED" class="inputbox" >
			<?php
				$q = "SELECT order_status_name,order_status_code FROM #__{vm}_order_status ORDER BY list_order";
				$db->query($q);
				$order_status_code = Array();
				$order_status_name = Array();
				
				while ($db->next_record()) 
				{
					$order_status_code[] = $db->f("order_status_code");
					$order_status_name[] = $db->f("order_status_name");
				}
				
				for ($i = 0; $i < sizeof($order_status_code); $i++) 
				{
					echo "<option value=\"" . $order_status_code[$i];
					if (P2P_VERIFIED == $order_status_code[$i]) 
						echo "\" selected=\"selected\">";
					else
						echo "\">";
						echo $order_status_name[$i] . "</option>\n";
				}?>
			</select>
		</td>
		<td><?php echo $VM_LANG->_('PHPSHOP_ADMIN_CFG_PAYMENT_ORDERSTATUS_SUCC_EXPLAIN') ?></td>
	</tr>	
	
	<tr>
		<td><strong><?php echo 'Выберите валюту' ?></strong></td>
		<td>
			<select name="KCURRENCY" class="inputbox" >
			<?php
				$available_currencies = array('EUR'=>'ЕВРО', 'RUB'=>'Рубль', 'UAH'=>'Гривня', 'USD'=>'Доллар');
				
				foreach($available_currencies as $cur_code=>$cur_name)
				{
					$sl = (KCURRENCY == $cur_code) ? ' selected="selected" ': '';
					echo "<option value='$cur_code' $sl>$cur_name</option>";
				}
			?>
			</select>
		</td>
		<td><?php echo $VM_LANG->_('PHPSHOP_ADMIN_CFG_PAYMENT_ORDERSTATUS_SUCC_EXPLAIN') ?></td>
	</tr>
	<tr>
		<td><strong><?php echo $VM_LANG->_('PHPSHOP_ADMIN_CFG_PAYMENT_ORDERSTATUS_FAIL') ?></strong></td>
		<td>
			<select name="P2P_INVALID" class="inputbox" >
			<?php
				for ($i = 0; $i < sizeof($order_status_code); $i++) 
				{
					echo "<option value=\"" . $order_status_code[$i];
					if (P2P_INVALID == $order_status_code[$i]) 
						echo "\" selected=\"selected\">";
					else
						echo "\">";
				  echo $order_status_name[$i] . "</option>\n";
			  }
		  ?>
			</select>
		</td>
		<td><?php echo $VM_LANG->_('PHPSHOP_ADMIN_CFG_PAYMENT_ORDERSTATUS_FAIL_EXPLAIN') ?></td>
	</tr>

</table>
<?php
}
    
function has_configuration() {
	// return false if there's no configuration
	return true;
}
   
  /**
	* Returns the "is_writeable" status of the configuration file
	* @param void
	* @returns boolean True when the configuration file is writeable, false when not
	*/
function configfile_writeable() 
{
	return is_writeable( CLASSPATH."payment/".$this->classname.".cfg.php" );
}
   
  /**
	* Returns the "is_readable" status of the configuration file
	* @param void
	* @returns boolean True when the configuration file is writeable, false when not
	*/
function configfile_readable() 
{
	return is_readable( CLASSPATH."payment/".$this->classname.".cfg.php" );
}
   
  /**
	* Writes the configuration file for this payment method
	* @param array An array of objects
	* @returns boolean True when writing was successful
	*/
function write_configuration( &$d ) 
{
	$my_config_array = array(
			"MERCHANTGUID"		=> $d['MERCHANTGUID'],
			"MERCHNATSECRETKEY"	=> $d['MERCHNATSECRETKEY'],
			"RESULTPATH"		=> $d['RESULTPATH'],
			"KCURRENCY" 		=> $d['KCURRENCY'],
			"P2P_VERIFIED" 		=> $d['P2P_VERIFIED'],
			"P2P_INVALID"		=> $d['P2P_INVALID'],
        );
	$config = "<?php\n";
	$config .= "if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); \n";

	foreach( $my_config_array as $key => $value ) 
	{
		$config .= "define ('$key', '$value');\n";
	}
	$config .= "?>";
  
	if ($fp = fopen(CLASSPATH ."payment/".$this->classname.".cfg.php", "w")) 
	{
		fputs($fp, $config, strlen($config));
		fclose ($fp);
		return true;
	}
	else
		return false;
}
   
  /**************************************************************************
  ** name: process_payment()
  ** returns: 
  ***************************************************************************/
function process_payment($order_number, $order_total, &$d) {
      return true;
    }
}

	
class kaznachey {
	public	$urlGetMerchantInfo = 'http://payment.kaznachey.net/api/PaymentInterface/CreatePayment';
	public	$urlGetClientMerchantInfo = 'http://payment.kaznachey.net/api/PaymentInterface/GetMerchatInformation';
	public	$merchantGuid = MERCHANTGUID;
	public	$merchnatSecretKey = MERCHNATSECRETKEY;
	var $classname = "ps_kaznachey";
	
   function __construct() {
		include_once(CLASSPATH ."payment/".$this->classname.".cfg.php"); // Read current Configuration
   }
   
	public function process_payment(){

	global  $db;
	$order_id = $db->f("order_id");
	$amount = $db->f("order_total");
	$order = $this->getOrderInfo($order_id);
	$success_url = SSL_URL.'/'.RESULTPATH.'?status=success';
	$result_url = SSL_URL.'/'.RESULTPATH.'?status=done';
	$i = 0;
	$amount2 = 0;
	$product_count =  0;
	
	$products_items = $this->getProducts($order_id);
  	if($products_items)
	{
		foreach ($products_items as $key=>$pr_item)
		{
			$product = $this->getProduct($pr_item->order_item_id);
			
			if($product_image = ps_product::image_tag($product[0]->product_full_image, '', 0))
			{
				if(preg_match('/src="([^"]*)"/', $product_image, $matches))
				{
					$products[$i]['ImageUrl'] = $matches[1];
				}
			}

			$products[$i]['ProductItemsNum'] = number_format($pr_item->product_quantity, 2, '.', '');
			$products[$i]['ProductName'] = $pr_item->order_item_name;
			$products[$i]['ProductPrice'] = number_format($pr_item->product_item_price, 2, '.', '');
			$products[$i]['ProductId'] = $pr_item->order_item_id;
			$amount2 += $pr_item->product_item_price;
			$product_count += $pr_item->product_quantity;
			$i++;
		}
	}
	
	if($amount != $amount2)
	{
		$tt = $amount - $amount2; 
		$products[$i]['ProductItemsNum'] = '1.00';
		$products[$i]['ProductName'] = 'Delivery or discount';
		$products[$i]['ProductPrice'] = number_format($tt, 2, '.', '');
		$products[$i]['ProductId'] = '00001'; 
		$pr_c = '1.00';
		$amount2  = number_format($amount2 + $tt, 2, '.', '');
	}
	
	$user_id = ($order->user_id < 1)?$order->user_id:1;

	$signature_u = md5(md5(
		$this->merchantGuid.
		$this->merchnatSecretKey.
		$order->order_total.
		$order_id
	));

	$phone = (isset($order->phone_2))?$order->phone_2:$order->phone_1;
	$address = (isset($order->address_type_name))?$order->address_type_name:$order->address_1;
	
    $paymentDetails = Array(
       "MerchantInternalPaymentId"=>"$order_id",
       "MerchantInternalUserId"=>$user_id,
       "EMail"=>$order->user_email,
       "PhoneNumber"=>$phone,
       "CustomMerchantInfo"=>"$signature_u",
       "StatusUrl"=>"$result_url",
       "ReturnUrl"=>"$success_url",
       "BuyerCountry"=>$order->country,
       "BuyerFirstname"=>$order->first_name,
       "BuyerPatronymic"=>$order->middle_name,
       "BuyerLastname"=>$order->last_name,
       "BuyerStreet"=>$address,
       "BuyerZone"=>"",
       "BuyerZip"=>$order->zip,
       "BuyerCity"=>$order->city,

       "DeliveryFirstname"=>$order->first_name,
       "DeliveryLastname"=>$order->last_name,
       "DeliveryZip"=>$order->zip, 
       "DeliveryCountry"=>$order->country,
       "DeliveryPatronymic"=>$order->middle_name,
       "DeliveryStreet"=>$address,
       "DeliveryCity"=>$order->city,
       "DeliveryZone"=>"",
    );

	$product_count = (@$pr_c) ? $product_count + $pr_c : $product_count;
	$product_count = number_format($product_count, 2, '.', '');	
	$amount2 = number_format($amount2, 2, '.', '');	

	$selectedPaySystemId = $_SESSION['cc_types'] ? $_SESSION['cc_types'] : "1" ;
	
	$signature = md5(
		$this->merchantGuid.
		"$amount2".
		"$product_count".
		$paymentDetails["MerchantInternalUserId"].
		$paymentDetails["MerchantInternalPaymentId"].
		$selectedPaySystemId.
		$this->merchnatSecretKey
	);

	$request = Array(
        "SelectedPaySystemId"=>$selectedPaySystemId,
        "Products"=>$products,
        "PaymentDetails"=>$paymentDetails,
        "Signature"=>$signature,
        "MerchantGuid"=>$this->merchantGuid,
		"Currency"=> KCURRENCY
    );
	$res = $this->sendRequestKaznachey($this->urlGetMerchantInfo, json_encode($request));
	$result = json_decode($res,true);

	unset($_SESSION['cc_types']);

	if($result['ErrorCode'] != 0)
	{
		return '<div class="error">Произошла ошибка. Попробуйте повторить позднее</div>';
	}
	
	echo(base64_decode($result["ExternalForm"]));
		
	}
	
		function getProducts($order_id)
		{
			$query = "SELECT *
				  FROM `#__{vm}_order_item` 
				  WHERE `order_id`='".$order_id."'";

			$db_ap = new ps_DB;
			$db_ap->setQuery($query);
			return $db_ap->loadObjectList();
		}		
		
		function getProduct($product_id)
		{
			$query = "SELECT *
				  FROM `#__{vm}_product` 
				  WHERE `product_id`='".$product_id."'";

			$db_ap = new ps_DB;
			$db_ap->setQuery($query);
			return $db_ap->loadObjectList();
		}
		
		function getOrderInfo($order_id)
		{
			$qv = "SELECT *
				  FROM `#__{vm}_orders` as o
				  left join `#__{vm}_order_user_info` as oi on o.order_id = oi.order_id 
				  WHERE o.order_id='".$order_id."'";

			$db_ap = new ps_DB;
			$db_ap->setQuery($qv);
			$result =  $db_ap->loadObjectList();

			return  $result[0];
		}
		
		function sendRequestKaznachey($url,$data)
		{
			$curl =curl_init();
			if (!$curl)
				return false;

			curl_setopt($curl, CURLOPT_URL,$url );
			curl_setopt($curl, CURLOPT_POST,true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, 
					array("Expect: ","Content-Type: application/json; charset=UTF-8",'Content-Length: ' 
						. strlen($data)));
			curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,True);
			$res =  curl_exec($curl);
			curl_close($curl);

			return $res;
		}

		function GetMerchnatInfo($id = false)
		{
			$requestMerchantInfo = Array(
				"MerchantGuid"=>MERCHANTGUID,
				"Signature"=>md5(MERCHANTGUID.MERCHNATSECRETKEY)
			);

			$resMerchantInfo = json_decode($this->sendRequestKaznachey($this->urlGetClientMerchantInfo , json_encode($requestMerchantInfo)),true); 
			if($id)
			{
				foreach ($resMerchantInfo["PaySystems"] as $key=>$paysystem)
				{
					if($paysystem['Id'] == $id)
					{
						return $paysystem;
					}
				}
			}else{
				return $resMerchantInfo;
			}
		}

		function GetTermToUse()
		{
			$requestMerchantInfo = Array(
				"MerchantGuid"=>MERCHANTGUID,
				"Signature"=>md5(MERCHANTGUID.MERCHNATSECRETKEY)
			);

			$resMerchantInfo = json_decode($this->sendRequestKaznachey($this->urlGetClientMerchantInfo , json_encode($requestMerchantInfo)),true); 

			return $resMerchantInfo["TermToUse"];

		}
		
		function getPaySystems()
		{
			$cc_types = $this->GetMerchnatInfo();
			if(@$cc_types["PaySystems"])
			{
				$box = '
<script type="text/javascript">
function addLoadEvent(func) {var oldonload = window.onload;if (typeof window.onload != "function") {window.onload = func;} else {window.onload = function() { if (oldonload) {oldonload();} func();}} }

addLoadEvent(function() {
	var kznd = document.getElementById("kznd"),
		kzndinner = kznd.innerHTML,
		kzndnext = kznd.nextSibling,
		kzndprev = kznd.previousSibling;
		kzndprev2 = kznd.previousSibling.previousSibling.previousSibling.previousSibling;
		kzndnext.outerHTML += "<div id=\"kzndn\" style=\"display:none\" >" + kzndinner + "</div>" ;
		kznd.outerHTML = "";
		delete kznd;
	var kzndn = document.getElementById("kzndn");
		kzndprev.onclick=function(){
			kzndn.style.display="block";
		}		
		kzndprev2.onclick=function(){
			kzndn.style.display="block";
		}
});
</script>
				<div id="kznd"><label for="cc_types">Выберите способ оплаты</label><select name="cc_types" id="cc_types" >';
				$term_url = $this->GetTermToUse();
					foreach ($cc_types["PaySystems"] as $paysystem)
					{
						$box .= "<option value='$paysystem[Id]'>$paysystem[PaySystemName]</option>";
					}
				$box .= '</select><br><input type="checkbox" checked="checked" value="1" name="cc_agreed" id="cc_agreed"><label for="cc_agreed"><a href="'.$term_url.'" target="_blank">Согласен с условиями использования</a></label>
				</div>';
				
				print $box;

			}
		}
}

function kaznachey() {
	$kaznachey = new kaznachey();
	$kaznachey->process_payment();
}

if($_REQUEST['page'] == 'checkout.index')
{
	if($_REQUEST['checkout_last_step'] == '3')
	{
		$_SESSION['cc_types'] = (int) $_REQUEST['cc_types'];
	}else{
		$kaznachey = new kaznachey();
		$kaznachey->getPaySystems();
	}
}