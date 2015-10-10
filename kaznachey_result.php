<?php
ini_set("display_errors","0");

global $mosConfig_absolute_path, $mosConfig_live_site, $mosConfig_lang, $database,
$mosConfig_mailfrom, $mosConfig_fromname;

    /*** access Joomla's configuration file ***/
    $my_path = dirname(__FILE__);
    
    if( file_exists($my_path."/../../../configuration.php")) {
        $absolute_path = dirname( $my_path."/../../../configuration.php" );
        require_once($my_path."/../../../configuration.php");
    }
    elseif( file_exists($my_path."/../../configuration.php")){
        $absolute_path = dirname( $my_path."/../../configuration.php" );
        require_once($my_path."/../../configuration.php");
    }
    elseif( file_exists($my_path."/../configuration.php")){
        $absolute_path = dirname( $my_path."/../configuration.php" );
        require_once($my_path."/../configuration.php");
    }
    elseif( file_exists($my_path."/configuration.php")){
        $absolute_path = dirname( $my_path."/configuration.php" );
        require_once( $my_path."/configuration.php" );
    }
    else {
        die( "Joomla Configuration File not found!" );
    }
    
    $absolute_path = realpath( $absolute_path );
    
    // Set up the appropriate CMS framework
    if( class_exists( 'jconfig' ) ) {
		define( '_JEXEC', 1 );
		define( 'JPATH_BASE', $absolute_path );
		define( 'DS', DIRECTORY_SEPARATOR );
		
		// Load the framework
		require_once ( JPATH_BASE . DS . 'includes' . DS . 'defines.php' );
		require_once ( JPATH_BASE . DS . 'includes' . DS . 'framework.php' );

		// create the mainframe object
		$mainframe = & JFactory::getApplication( 'site' );
		
		// Initialize the framework
		$mainframe->initialise();
		
		// load system plugin group
		JPluginHelper::importPlugin( 'system' );
		
		// trigger the onBeforeStart events
		$mainframe->triggerEvent( 'onBeforeStart' );
		$lang =& JFactory::getLanguage();
		$mosConfig_lang = $GLOBALS['mosConfig_lang'] = strtolower( $lang->getBackwardLang() );
		// Adjust the live site path
		$mosConfig_live_site = str_replace('/administrator/components/com_virtuemart', '', JURI::base());
		$mosConfig_absolute_path = JPATH_BASE;
    } else {
    	define('_VALID_MOS', '1');
    	require_once($mosConfig_absolute_path. '/includes/joomla.php');
    	require_once($mosConfig_absolute_path. '/includes/database.php');
    	$database = new database( $mosConfig_host, $mosConfig_user, $mosConfig_password, $mosConfig_db, $mosConfig_dbprefix );
    	$mainframe = new mosMainFrame($database, 'com_virtuemart', $mosConfig_absolute_path );
    }

    // load Joomla Language File
    if (file_exists( $mosConfig_absolute_path. '/language/'.$mosConfig_lang.'.php' )) {
        require_once( $mosConfig_absolute_path. '/language/'.$mosConfig_lang.'.php' );
    }
    elseif (file_exists( $mosConfig_absolute_path. '/language/english.php' )) {
        require_once( $mosConfig_absolute_path. '/language/english.php' );
    }
/*** END of Joomla config ***/


/*** VirtueMart part ***/
	global $database;        
    require_once($mosConfig_absolute_path.'/administrator/components/com_virtuemart/virtuemart.cfg.php');
    include_once( ADMINPATH.'/compat.joomla1.5.php' );
    require_once( ADMINPATH. 'global.php' );
    require_once( CLASSPATH. 'ps_main.php' );
    require_once( CLASSPATH. 'ps_database.php' );
    require_once( CLASSPATH. 'ps_order.php' );

    $vmLogIdentifier = "kaznachey.php";
    require_once(CLASSPATH."Log/LogInit.php");
    require_once( CLASSPATH. 'payment/ps_kaznachey.cfg.php' );
    $sess = new ps_session();                        
    
/*** END VirtueMart part ***/



function SetOrderStatus ($order_id = false, $status) {
		if ($order_id)
		{
			$d['order_id'] = $order_id;
			$d['notify_customer'] = "N";
			$d['order_status'] = $status;                    
			$ps_order= new ps_order;
			$ps_order->order_status_update($d);
		}		
		die;
}

function redirectTo($link, $msg = false){
	$app = JFactory::getApplication();
	$msg = 'Спасибо за заказ';
	$app->redirect($link, $msg); 
}

function redirectHome($link, $msg){
	redirectTo('/'); 
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

switch ($_GET['status'])
{
	case 'done':
		$HTTP_RAW_POST_DATA = @$HTTP_RAW_POST_DATA ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');

		$hrpd = json_decode($HTTP_RAW_POST_DATA);
		$order_id = intval($hrpd->MerchantInternalPaymentId); 
		if(!$order_id){redirectHome();}
		
		if(@$hrpd->MerchantInternalPaymentId)
		{
			if($hrpd->ErrorCode == 0)
			{
				SetOrderStatus($order_id,P2P_VERIFIED);
			}else{
				SetOrderStatus($order_id,P2P_INVALID);
			}
		}
		
		redirectHome();
		
	break;		
	
	case 'success':
		redirectHome();
	break;		
	
	case 'fail':
		redirectTo('/', 'Ошибка оплаты, попробуйте еще раз');
	break;
}

?>