<?//require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
$inputData = file_get_contents('php://input');
$update = json_decode($inputData,true);

file_put_contents("create.log.txt",date("d.m.Y H:i").PHP_EOL.$inputData, FILE_APPEND);

$order = sendGetNewAPI($update['events'][0]['meta']['href']);

foreach($order->attributes as $a=>$attr) {
	if($attr->id=="87c70bc5-9a5b-11e7-7a6c-d2a900000c9d") {
		$dtype = $attr->value->name;
	}
}

sleep(1);

$positions = sendGetNewAPI($order->positions->meta->href)->rows;

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

//} // END ONLY JEKA

function sendGet($url) {
    $username = MS_LOGIN;
    $password = MS_PASS;
    $authCode = base64_encode($username . ':' . $password);
    $opts = array(
        'http' => array(
        'method' => 'GET',
        'header' => 'Auth:'.PHP_EOL.'Authorization: Basic ' . $authCode,
        )
    );
    $context = stream_context_create($opts);
    return json_decode(file_get_contents($url,false,$context));
}

function sendGetNewAPI($url) {

    $username = MS_LOGIN;
    $password = MS_PASS;
    $authCode = base64_encode($username . ':' . $password);

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_ACCEPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$authCode,'Content-Type: application/json'));
	$html = curl_exec($ch);
	curl_close($ch);
	return json_decode($html);

}

function sendPut($url,$postdata) {
    $username = MS_LOGIN;
    $password = MS_PASS;
    $authCode = base64_encode($username . ':' . $password);
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
	  	CURLOPT_TIMEOUT => 0,
	  	CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_ACCEPT_ENCODING => "gzip",
	  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  	CURLOPT_CUSTOMREQUEST => 'PUT',
	  	CURLOPT_POSTFIELDS =>json_encode($postdata),
		CURLOPT_HTTPHEADER => array(
			'Authorization: Basic '.$authCode,
			'Content-Type: application/json'
		),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

function sendPost($url,$postdata) {
    $username = MS_LOGIN;
    $password = MS_PASS;
    $authCode = base64_encode($username . ':' . $password);
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
	  	CURLOPT_TIMEOUT => 0,
	  	CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_ACCEPT_ENCODING => "gzip",
	  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  	CURLOPT_CUSTOMREQUEST => 'POST',
	  	CURLOPT_POSTFIELDS =>json_encode($postdata),
		CURLOPT_HTTPHEADER => array(
			'Authorization: Basic '.$authCode,
			'Content-Type: application/json'
		),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

function sendDelete($url) {
    $username = MS_LOGIN;
    $password = MS_PASS;
    $authCode = base64_encode($username . ':' . $password);
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
	  	CURLOPT_TIMEOUT => 0,
	  	CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_ACCEPT_ENCODING => "gzip",
	  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  	CURLOPT_CUSTOMREQUEST => 'DELETE',
		CURLOPT_HTTPHEADER => array(
			'Authorization: Basic '.$authCode,
			'Content-Type: application/json'
		),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

?>