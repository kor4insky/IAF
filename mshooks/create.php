<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
$inputData = file_get_contents('php://input');
$update = json_decode($inputData,true);

file_put_contents("create.log.txt",date("d.m.Y H:i").PHP_EOL.$inputData, FILE_APPEND);

$order = sendGetNew($update['events'][0]['meta']['href']);

foreach($order->attributes as $a=>$attr) {
	if($attr->id=="87c70bc5-9a5b-11e7-7a6c-d2a900000c9d") {
		$dtype = $attr->value->name;
	}
}

sleep(1);

$positions = sendGetNew($order->positions->meta->href)->rows;

foreach($positions as $p=>$pos) {
	if($pos->assortment->meta->type=="service") {
		switch($dtype) {
			case "Доставка Boxberry (До ПВЗ)" : 
				$delivery = array(
					"meta" => array(
						"href" => "https://api.moysklad.ru/api/remap/1.2/entity/service/04dc70b3-1da3-11ef-0a80-1491000e0f26",
						"type" => "service",
						"mediaType" => "application/json",
					),
				);
				break;
			case "Доставка Boxberry (Курьер)" : 
				$delivery = array(
					"meta" => array(
						"href" => "https://api.moysklad.ru/api/remap/1.2/entity/service/122a782b-1da3-11ef-0a80-1491000e121a",
						"type" => "service",
						"mediaType" => "application/json",
					),
				);
				break;
			case "СДЭК (до склада)" : 
				$delivery = array(
					"meta" => array(
						"href" => "https://api.moysklad.ru/api/remap/1.2/entity/service/e55bdc32-25ba-11e7-7a69-9711000c4584",
						"type" => "service",
						"mediaType" => "application/json",
					),
				);
				break;
			case "СДЭК (до двери)" : 
				$delivery = array(
					"meta" => array(
						"href" => "https://api.moysklad.ru/api/remap/1.2/entity/service/f79dc183-88d8-11e6-7a31-d0fd0017098c",
						"type" => "service",
						"mediaType" => "application/json",
					),
				);
				break;
			case "СДЭК (Постамат)" : 
				$delivery = array(
					"meta" => array(
						"href" => "https://api.moysklad.ru/api/remap/1.2/entity/service/bbe2b6ce-f96f-11ec-0a80-0fc30013b947",
						"type" => "service",
						"mediaType" => "application/json",
					),
				);
				break;
			case "Почта России" : 
			case "Почта России (Доставка в отделение)" : 
				$delivery = array(
					"meta" => array(
						"href" => "https://api.moysklad.ru/api/remap/1.2/entity/service/dc6bfdfe-88d8-11e6-7a69-8f550011d820",
						"type" => "service",
						"mediaType" => "application/json",
					),
				);
				break;
		}
		$priceDelivery = $pos->price;
		$discount = $pos->discount;
		$fields = array(
			"quantity" => 1,
			"price" => $priceDelivery,
			"discount" => $discount,
			"assortment" => $delivery,
		);
		sendDelete($pos->meta->href); // удаляем стандартную доставку
		sendPost($order->positions->meta->href,$fields); //создаем новую
	}
}
?>