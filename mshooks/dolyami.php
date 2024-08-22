<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
$inputData = file_get_contents('php://input');
$update = json_decode($inputData,true);
$orderID = explode("_",$update['id'])[1];

$query = sendGetNew('https://api.moysklad.ru/api/remap/1.2/entity/customerorder?filter=name='.$orderID);
$orderhref = $query->rows[0]->meta->href;
$order = sendGetNew($orderhref);

//file_get_contents("http://api.telegram.org/bot6323581019:AAEXKQ52C4MRB0WyPcuJkRsNElQ9D3LSbPE/sendMessage?chat_id=2565367&parse_mode=HTML&text=".json_encode($update,JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE));

if($update['status']=="completed") {

	$postdata = array(
		"state" => array(
			"meta" => array (
				"href" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/6dc42044-84de-11e6-7a69-971100133dcb",
				"metadataHref" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata",
				"type" => "state",
				"mediaType" => "application/json"
			),
		  	"id" => "6dc42044-84de-11e6-7a69-971100133dcb",
		),
	);
	$upd = sendPut("https://api.moysklad.ru/api/remap/1.2/entity/customerorder/".$query->rows[0]->id."/",$postdata);
	$text = urlencode("Заказ №".$orderID." ( ".$update['amount']." RUB ) - ОПЛАЧЕН ДОЛЯМИ");
	//send chatbot
	file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendMessage?chat_id=-746668184&text=".$text);

	//CREATE INCOMING PAYMENT 

	$org = json_decode(json_encode($order->organization),true);
	$agent = json_decode(json_encode($order->agent),true);

	$postdata = array(
		"organization" => $org,
		"agent" => $agent,
		"sum" => $update['amount']*100,
		"printed" => false,
		"state" => array(
			"meta" => array(
				"href" => "https://api.moysklad.ru/api/remap/1.2/entity/paymentin/metadata/states/6892c08c-78c0-11ed-0a80-0680004c585d",
				"metadataHref" => "https://api.moysklad.ru/api/remap/1.2/entity/paymentin/metadata",
				"type" => "state",
				"mediaType" => "application/json",
			),
		),
		"operations" => array(
			array(
				"meta" => $order->meta,
				"linkedSum" => $update['amount']*100,
			),
		),
	);
	$pay = sendPost("https://api.moysklad.ru/api/remap/1.2/entity/paymentin",$postdata);
	// END CREATE

	//SEND MAIL TO CLIENT	
	foreach($order->attributes as $a=>$attr) {
		if($attr->id=="87c70bc5-9a5b-11e7-7a6c-d2a900000c9d") {
			$type = $attr->value->name;
		}
		if($attr->id=="1c910c32-0b7d-11e8-7a6c-d2a900111fff") {
			$email = $attr->value;
		}
	}
	
	if($type=="Самовывоз" || $type=="Самовывоз из офиса (г. Москва)") {
		$arEventFields = array(
			"ID" => $orderID, //$update['id'],
			"EMAIL" => $email,
		);
		CEvent::Send("TINKOFF_PAYED_PICKUP", "s1", $arEventFields);
		CEvent::CheckEvents();
		//SAMOVIVOZ EVENT
	}
	
	if($type=="Ждун") {
		$arEventFields = array(
			"ID" => $orderID, //$update['id'],
			"EMAIL" => $email,
		);
		CEvent::Send("TINKOFF_PAYED_WAIT", "s1", $arEventFields);
		CEvent::CheckEvents();
		//ZHDUN EVENT
	}
	
	if($type=="СДЭК (до двери)" || $type=="СДЭК (до склада)" || $type=="СДЭК (Постамат)" || $type=="Почта России" || $type=="Почта России (Доставка в отделение)") {
		$arEventFields = array(
			"ID" => $orderID, //$update['id'],
			"EMAIL" => $email,
		);
		CEvent::Send("TINKOFF_PAYED_SDEK", "s1", $arEventFields);
		CEvent::CheckEvents();
		//SDEK EVENT
	}

	echo "OK";

}elseif($update['status']=="rejected" || $update['status']=="canceled") {
	$postdata = array(
		"state" => array(
			"meta" => array (
				"href" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/ceb6a038-57c3-11ee-0a80-030a0014f7d6",
				"metadataHref" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata",
				"type" => "state",
				"mediaType" => "application/json"
			),
		  	"id" => "ceb6a038-57c3-11ee-0a80-030a0014f7d6",
		),
	);
	$upd = sendPut("https://api.moysklad.ru/api/remap/1.2/entity/customerorder/".$query->rows[0]->id."/",$postdata);
	$text = urlencode("❗️ Заказ №".$orderID." ( ".$update['amount']." RUB ) - ОШИБКА ОПЛАТЫ");
	//send chatbot
	file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendMessage?chat_id=-746668184&text=".$text);
}
?>