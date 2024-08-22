<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
$orderID = $_GET['oid'];

$order = sendGetNew("https://api.moysklad.ru/api/remap/1.2/entity/customerorder?filter=name=".$orderID)->rows[0];
$positions = sendGetNew($order->positions->meta->href)->rows;

$totalWeight = $totalHeight = 0;

foreach($positions as $pos) {

	//pp($pos);

	$itemCard = sendGetNew($pos->assortment->meta->href);
	if($pos->assortment->meta->type=="service") {
		$delivprice = $pos->price/100;
	}else{
		$itemW = $itemH = $itemL = 0;
		if($pos->assortment->meta->type=="product") {
			$weight = $itemCard->weight;
			if(strpos($itemCard->pathName,"Аксессуары")!==false || strpos($itemCard->pathName,"Фурнитур")!==false) {
				$itemW = 1;
				$itemL = 1;
				$itemH = 1;
			}else{
				$itemW = 20;
				$itemL = 20;
				$itemH = 5;
			}
		}
		if($pos->assortment->meta->type=="variant") {
			$prod = $itemCard->product->meta->href;
			$pr = sendGetNew($prod);
			$weight = $pr->weight;
			if(strpos($pr->pathName,"Аксессуар")!==false || strpos($pr->pathName,"ФУРНИТУ")!==false) {
				$itemW = 1;
				$itemL = 1;
				$itemH = 1;
			}else{
				$itemW = 20;
				$itemL = 20;
				$itemH = 5;
			}
		}
		$items[] = array(
			"name" => $itemCard->name,
			"ware_key" => 1,
			"payment" => [
				"value" => 0,
			],
			"cost" => 0,
			"value" => 0,
			"weight" => $weight,
			"amount" => 1,
		);
		$totalWeight += $weight*$pos->quantity;
		$totalWidth = 20;
		$totalLength = 20;
		$totalHeight += $itemH;
	}
}

foreach($order->attributes as $attr) {
	if($attr->name=="Тариф СДЕК") {
		switch($attr->value->name) {
			case "2. склад-склад" :
				$tarif = 136; break;
			case "3. Постамат" :
				$tarif = 368; break;
			case "4. склад-дверь" :
				$tarif = 137; break;
		}
	}
	if($attr->name=="ФИО получателя") {
		$fio = $attr->value;
	}
	if($attr->name=="Телефон получателя") {
		$phone = $attr->value;
	}
	if($attr->name=="Полный адрес клиента") {
		$addr = $attr->value;
	}
}

//pp($order);

$data = array (
  'type' => 1,
  'number' => $order->name,
  'tariff_code' => $tarif,
  'threshold' => 0,
  'sum' => $delivprice,
  'recipient' => 
  array (
    'name' => $fio,
    'phones' => 
    array (
      0 => 
      array (
        'number' => $phone,
      ),
    ),
  ),
  'from_location' => 
	  array (
		'address' => ' г.Москва 115419 ул. Новоостаповская 6б',
	  ),
  'packages' => 
  array (
    0 => 
    array (
      'items' => $items,
      'number' => $order->name,
      'weight' => $totalWeight,
      'length' => $totalLength,
      'width' => $totalWidth,
      'height' => $totalHeight,
    ),
  ),
);

if($tarif==137) {
	$data['to_location'] = array (
		'address' => $addr,
	);
}
if($tarif==136 || $tarif==368) {
	$pvzList = preg_match('/#[A-Z]+[0-9]+/', $addr, $pvz);
	$data['delivery_point'] = mb_substr(str_replace("#","",$pvz[0]),1);
}

pp($data);

function getSDEKToken() {

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://api.cdek.ru/v2/oauth/token?client_id='.SDEK_CLIENT.'&client_secret='.SDEK_SECRET.'&grant_type=client_credentials',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_HTTPHEADER => array(
		'Authorization: Bearer {token}'
		),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return json_decode($response,true)['access_token'];
}

$token = getSDEKToken();

$cdek = createOrder($token,$data);

$track = orderInfo($cdek['entity']['uuid'],$token)['entity']['cdek_number'];

//pp($track);
echo $track;

function createOrder($token,$data) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.cdek.ru/v2/orders',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_POSTFIELDS => json_encode($data),
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_HTTPHEADER => array(
		'Authorization: Bearer '.$token,
		'Content-Type: application/json',
	  ),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return json_decode($response,true);
}

function orderInfo($uid,$token) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.cdek.ru/v2/orders/'.$uid.'/',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	  CURLOPT_HTTPHEADER => array(
		'Authorization: Bearer '.$token,
		'Content-Type: application/json',
	  ),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return json_decode($response,true);
}

?>