<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
	CModule::IncludeModule("iblock");
$inputData = file_get_contents('php://input');
$update = json_decode($inputData,true);

file_put_contents("update.log.txt",date("d.m.Y H:i").PHP_EOL.$inputData, FILE_APPEND);

include "TinkoffMerchantAPI.php";
$api = new TinkoffMerchantAPI(
	TINKOFF_TERMINAL_ID,
    TINKOFF_SECRET,
);

$orders = $update['events'];

foreach($orders as $ord) {

	$order = sendGetNew($ord['meta']['href']);

	$blank = false; //blank pochty
	foreach($order->attributes as $attr) {
		if($attr->id=="87c70bc5-9a5b-11e7-7a6c-d2a900000c9d" && $attr->value->name=="Почта России") {
			$blank = true;
		}
		if($attr->id=="87c70bc5-9a5b-11e7-7a6c-d2a900000c9d" && $attr->value->name=="Почта России (Доставка в отделение)") {
			$blank = true;
		}

		if($attr->id=="bfb58a65-9eee-11e8-9109-f8fc000f3663" && strlen($attr->value)>0) {
			$postdata = array(
				'attributes' => array(
					array(
						'meta' => array(
							'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/85f7ec7e-885d-11ee-0a80-032c00520bbf',
							'type' => 'attributemetadata',
							'mediaType' => 'application/json',
						),
						'value' => 'https://iafstudio.ru/print/cdek.php?track='.$attr->value
					),
				)
			);
			$upd = sendPut($ord['meta']['href'],$postdata);
		}

		if($blank==true) {
			$postdata = array(
				'attributes' => array(
					array(
						'meta' => array(
							'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/dee578fb-f8e8-11ed-0a80-0f70000206ed',
							'type' => 'attributemetadata',
							'mediaType' => 'application/json',
						),
						'value' => "https://iafstudio.ru/post/?oid=".$order->name,
					),
				)
			);
			$upd = sendPut($ord['meta']['href'],$postdata);
		}
	}

	$state = sendGetNew($order->state->meta->href)->id;

	$positions = sendGetNew($order->positions->meta->href)->rows;

	//obnuliaem dannye
	$email = $fio = $phone = $track = $d_type = $tracklink = "";

	if($state=="9ac54368-28f5-11e8-9109-f8fc00035749") { // Peredan v dostavku
		foreach($order->attributes as $attr) {
			switch($attr->id) {
				case "1c910c32-0b7d-11e8-7a6c-d2a900111fff" : $email = $attr->value; break;
				case "bfb591e5-9eee-11e8-9109-f8fc000f3667" : $fio = $attr->value; break;
				case "0025cf0a-acee-11e8-9107-50480001fdbd" : $phone = $attr->value; break;
				case "bfb58a65-9eee-11e8-9109-f8fc000f3663" : $track = $attr->value; break;
				case "87c70bc5-9a5b-11e7-7a6c-d2a900000c9d" : $d_type = $attr->value->name; break;
			}
		}
		if($d_type=="СДЭК (до двери)" || $d_type=="СДЭК (до склада)" || $d_type=="СДЭК (Постамат)") {
			$tracklink = "https://www.cdek.ru/track.html?order_id=".$track;
		}
		if($d_type=="Почта России" || $d_type=="Почта России (Доставка в отделение)" || $d_type=="Почта 1 класс") {
			$tracklink = "https://www.pochta.ru/tracking#".$track;
		}
		if($d_type=="Доставка Boxberry (До ПВЗ)" || $d_type=="Доставка Boxberry (Курьер)") {
			$tracklink = "https://boxberry.ru/tracking-page?id=".$track;
		}
		$arFields = array(
			"ORDER" => $order->name,
			"EMAIL" => $email,
			"FIO" => $fio,
			"TRACK" => $track,
			"TRACKLINK" => $tracklink,
		);
		CEvent::Send("MOYSKLAD_TRACKING", "s1", $arFields,"Y","");
		CEvent::CheckEvents();
		//Set status otgruzhen
		$postdataTracking = array(
			"state" => array(
				"meta" => array (
					"href" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/37196b9f-83dc-11e6-7a31-d0fd003ac994",
					"metadataHref" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata",
					"type" => "state",
					"mediaType" => "application/json"
				),
				"id" => "37196b9f-83dc-11e6-7a31-d0fd003ac994",
			),
		);
		$upd = sendPut("https://api.moysklad.ru/api/remap/1.2/entity/customerorder/".$order->id."/",$postdataTracking);
	
	}
	
	if($state=="6dc42246-84de-11e6-7a69-971100133dcc") { // Samovyvoz otpravit pismo
		foreach($order->attributes as $attr) {
			switch($attr->id) {
				case "1c910c32-0b7d-11e8-7a6c-d2a900111fff" : $email = $attr->value; break;
				case "bfb591e5-9eee-11e8-9109-f8fc000f3667" : $fio = $attr->value; break;
				case "0025cf0a-acee-11e8-9107-50480001fdbd" : $phone = $attr->value; break;
			}
		}
	
		$arFields = array(
			"ORDER" => $order->name,
			"EMAIL" => $email,
			"FIO" => $fio,
		);
	
		CEvent::Send("MOYSKLAD_PICKUP", "s1", $arFields,"Y","");
		CEvent::CheckEvents();
		//Set status samovyvoz
		$postdataPickup = array(
			"state" => array(
				"meta" => array (
					"href" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/84070655-0683-11eb-0a80-0995001ccf69",
					"metadataHref" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata",
					"type" => "state",
					"mediaType" => "application/json"
				),
				"id" => "84070655-0683-11eb-0a80-0995001ccf69",
			),
		);
		$upd = sendPut("https://api.moysklad.ru/api/remap/1.2/entity/customerorder/".$order->id."/",$postdataPickup);
	}

	//DOLYAME START
	if($state=="d45e8fb4-b6e3-11ed-0a80-01310002e7f9" || $state=="d45e9753-b6e3-11ed-0a80-01310002e7fa") {
	
		$delivprice = 0; $pvz = false; $address = ""; $pvzpoint = "";
	
		foreach($order->attributes as $attr) {
	
			if($attr->id=="ff839d36-b1e3-11e8-9ff4-31500008825d" || $attr->id=="bfb59047-9eee-11e8-9109-f8fc000f3666") {
					if(strpos($attr->value->name,"Беларусь")!==false || strpos($attr->value->name,"Казахс")!==false || strpos($attr->value->name,"Армения")!==false || strpos($attr->value->name,"Грузия")!==false) {
					$outside = true; 
				}
			}
	
			if($attr->id=="6d6c5d3f-b8da-11ed-0a80-04c000239469" && strlen($attr->value)>0) {
				die(); // die if tinkoff link is exist
			}
		}
	
		$summ = $order->sum;
	
		foreach($order->attributes as $attr) {
	
			switch($attr->id) {
				case "1c910c32-0b7d-11e8-7a6c-d2a900111fff" : $email = $attr->value; break;
				case "bfb591e5-9eee-11e8-9109-f8fc000f3667" : $fio = $attr->value; break;
				case "0025cf0a-acee-11e8-9107-50480001fdbd" : $phone = $attr->value; break;
			}
	
			if($attr->id=="ff839d36-b1e3-11e8-9ff4-31500008825d" && strlen($attr->value)>0) {
				$addr = htmlspecialchars_decode($attr->value->name);
			}
			if($attr->id=="bfb59047-9eee-11e8-9109-f8fc000f3666" && strlen($attr->value)>0) {
				$addr = htmlspecialchars_decode($attr->value);
			}
	
			if($attr->id=="65a270b5-4639-11ee-0a80-07c400069f3e" && $attr->value=="17") {
				$dolyami_items = [];
				foreach($positions as $n=>$item) {
					$skid_dolyami = $item->price/100*$item->discount;
					$skid = ($item->price*$item->discount)/100;
	
					$itemCard = sendGetNew($item->assortment->meta->href);
					if($item->assortment->meta->type=="service") {
						if($itemCard->id!=="e55bdc32-25ba-11e7-7a69-9711000c4584" 
							&& $itemCard->id=="f79dc183-88d8-11e6-7a31-d0fd0017098c" 
							&& $itemCard->id=="bbe2b6ce-f96f-11ec-0a80-0fc30013b947" 
							&& $itemCard->id=="72bb8071-532a-11ef-0a80-01e5005d29a2" 
							&& $itemCard->id=="04dc70b3-1da3-11ef-0a80-1491000e0f26" /*pvz-box*/ 
							&& $itemCard->id=="122a782b-1da3-11ef-0a80-1491000e121a" /*kur-box*/ 
						) {
							if($outside==false) {
								$summ-=$item->price+$skid;
								$skip = true;
							}else{
								$delivprice = "";
							}
						}
					} else {
						$skip = false;
					}
	
	
					if($skip==false) {
						$itemPrice = $item->price;
	
						$dolyami_items[] = [
							"name" => sendGetNew($item->assortment->meta->href)->name." - ".$item->quantity,
							"quantity" => 1,
							"price" => number_format(($item->price*$item->quantity)-($skid*$item->quantity),0,"","")/100,
							"receipt" => array(
								"tax" => "none",
								"payment_method" => "full_payment", 
								"payment_object" => "commodity",
								"measurement_unit" => sendGetNew(sendGetNew($item->assortment->meta->href)->uom->meta->href)->name,
							),
						];
	
					}
	
				}
	
				$dolami["order"] = [
					"id" => "IAF_".$order->name."_".time(),
					"amount" => $summ/100, //$order->sum/100,
					"items" => $dolyami_items,
				];
				$dolami['notification_url'] = "https://iafstudio.ru/mshooks/dolyami.php";
				$dolami['fail_url'] = "https://iafstudio.ru/";
				$dolami['success_url'] = "https://iafstudio.ru/";
	
				$dolami['fiscalization_settings'] = array(
					"type" => "enabled",
				);

				$dol = json_decode(Create_Dolyami(json_encode($dolami)),true);
	
				if($dol['status']=="new" && strlen($dol['link'])>0) {
					$postdata = array(
						'attributes' => array(
							array(
								'meta' => array(
									'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/6d6c5d3f-b8da-11ed-0a80-04c000239469',
									'type' => 'attributemetadata',
									'mediaType' => 'application/json',
								),
								'value' => $dol['link'],
							),
						)
					);
					$upd = sendPut($ord['meta']['href'],$postdata);
	
					$items = '<table class="items" style="width:100%;" border="0" cellpadding="1" cellspacing="1">';
					$items .= "<tr><td>№</td><td>Фото</td><td>Наименование товара</td><td>Кол-во</td><td>Ед. изм.</td><td>Цена</td><td>Сумма без скидки</td><td>Скидка</td><td>Сумма</td></tr>";
					foreach($positions as $n=>$item) {
						$itemCard = sendGetNew($item->assortment->meta->href);
						$ratio = sendGetNew($itemCard->uom->meta->href)->name;
						if($outside==false) {
							if(//$item->assortment->meta->type!=="service" 
								//$itemCard->id!=="dc6bfdfe-88d8-11e6-7a69-8f550011d820" //post
								$itemCard->id!=="e55bdc32-25ba-11e7-7a69-9711000c4584" 
								&& $itemCard->id!=="5944dd1c-4d71-11ef-0a80-048200cd036c" //cdek do sklada
								&& $itemCard->id!=="04dc70b3-1da3-11ef-0a80-1491000e0f26" /*pvz-box*/ 
								&& $itemCard->id!=="122a782b-1da3-11ef-0a80-1491000e121a" /*kur-box*/ 
								&& $itemCard->id!=="f79dc183-88d8-11e6-7a31-d0fd0017098c" /*cdek-dver*/
								//|| $itemCard->id=="bbe2b6ce-f96f-11ec-0a80-0fc30013b947" postamat
							) {
								$itemPrice = $item->price/100;
								$skid = $item->price/100*$item->discount*$item->quantity;
								$items .= "<tr><td>".($n+1)."</td>";
									$itemName = str_replace(["(1)","(2)","(3)","(4)","(5)","(6)"],["","","","",""],$itemCard->name);
									$bItem = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"NAME" => $itemName],false,[],["ID","NAME", "DETAIL_PICTURE"])->Fetch();
									if($bItem['DETAIL_PICTURE']>0) {
										$thumb = CFile::ResizeImageGet($bItem['DETAIL_PICTURE'],["width"=>100,"height"=>100],BX_RESIZE_IMAGE_EXACT);
									}
								$items .= "<td><img src=\"".$thumb['src']."\" class=\"img-fluid\"></td>";
								$items .= "<td>".$itemCard->name."</td><td>".$item->quantity."</td>";
								$items .= "<td>".$ratio."</td>";
								$items .= "<td>".($item->price/100)."</td>";
								$items .= "<td>".($item->price/100*$item->quantity)."</td>";
								$items .= "<td>".($item->price/100*$item->discount/100*$item->quantity)."</td>";
								$items .= "<td>".($item->price/100*$item->quantity-$skid/100)."</td></tr>";
							}else{
								$delivprice += $item->price; //plus delivery
								$delivname = $itemCard->name; //delivery name
							}
						} else {
							$itemPrice = $item->price/100;
							$skid = $item->price/100*$item->discount*$item->quantity;
							$items .= "<tr><td>".($n+1)."</td><td>".$itemCard->name."</td><td>".$item->quantity."</td>";
							$items .= "<td>".$ratio."</td>";
							$items .= "<td>".($item->price/100)."</td>";
							$items .= "<td>".($item->price/100*$item->quantity)."</td>";
							$items .= "<td>".($item->price/100*$item->discount/100*$item->quantity)."</td>";
							$items .= "<td>".($item->price/100*$item->quantity-$skid/100)."</td></tr>";
						}
						if($item->id == "07454daf-b75e-11ed-0a80-0bdb00120906" || $itemCard->id=="04dc70b3-1da3-11ef-0a80-1491000e0f26" /*pvz-box*/ ) {
							$pvz = true;
						}
					}
				
					$items .= '<tr><td colspan="8">К оплате: </td><td>'.number_format($summ/100,2).' RUB</td></tr>';
					$items .= "</table>";
	
					$arFields = array(
						"ID" => $order->name,
						"LINK" => $dol['link'],
						"SUMM" => $order->sum/100,
						"EMAIL" => $email,
						"ZAKAZ_DATE" => $order->moment,
						"FIO" => $fio,
						"PHONE" => $phone,
						"ITEMS" => $items,
						"ADDRESS" => $addr,
						"DELIVNAME" => $delivname,
						"DELIVPRICE" => $delivprice,
					);
	
					CEvent::Send("DOLYAME_LINK", "s1", $arFields,"Y","");
					CEvent::CheckEvents();
	
					$postdata = array(
						"state" => array(
							"meta" => array (
								"href" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/d45e9899-b6e3-11ed-0a80-01310002e7fb",
								"metadataHref" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata",
								"type" => "state",
								"mediaType" => "application/json"
							),
							"id" => "d45e9899-b6e3-11ed-0a80-01310002e7fb",
						),
					);
					$upd = sendPut("https://api.moysklad.ru/api/remap/1.2/entity/customerorder/".$order->id."/",$postdata);
	
				}else{
					$postdata = array(
						'attributes' => array(
							array(
								'meta' => array(
									'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/6d6c5d3f-b8da-11ed-0a80-04c000239469',
									'type' => 'attributemetadata',
									'mediaType' => 'application/json',
								),
								'value' => $dol['operationDetails'],
							),
						)
					);
					$upd = sendPut($ord['meta']['href'],$postdata);
				}

				die(); //IMPORTANT THING!
			}
		}
	}

	//DOLYAME END

	if($state=="d45e8fb4-b6e3-11ed-0a80-01310002e7f9" || $state=="d45e9753-b6e3-11ed-0a80-01310002e7fa") {

		$summ = $order->sum;
		$delivprice = 0; $pvz = false; $address = ""; $pvzpoint = "";
		$positions = sendGetNew($order->positions->meta->href)->rows;
		$outside = false; // priznak dostavki zarubezh
		foreach($order->attributes as $attr) {
			if($attr->id=="ff839d36-b1e3-11e8-9ff4-31500008825d" || $attr->id=="bfb59047-9eee-11e8-9109-f8fc000f3666") {
				if(strpos($attr->value->name,"Беларусь")!==false || strpos($attr->value->name,"Казахс")!==false || strpos($attr->value->name,"Армения")!==false || strpos($attr->value->name,"Грузия")!==false) {
					$outside = true; 
				}
			}
		}

		$skip = false; // skip dostavka
		$tovars = [];

		foreach($positions as $n=>$item) {
			$itemCard = sendGetNew($item->assortment->meta->href);
				$skid = ($item->price*$item->discount)/100;
			if($item->assortment->meta->type=="service") {
				if($itemCard->id=="e55bdc32-25ba-11e7-7a69-9711000c4584" || $itemCard->id=="f79dc183-88d8-11e6-7a31-d0fd0017098c" || $itemCard->id=="bbe2b6ce-f96f-11ec-0a80-0fc30013b947" || $itemCard->id=="04dc70b3-1da3-11ef-0a80-1491000e0f26" /*pvz-box*/ || $itemCard->id=="122a782b-1da3-11ef-0a80-1491000e121a" /*kur-box*/ ) {
					if($outside==false) {
						$summ-= $item->price-$skid;
						$skip = true;
						$delivtext = "<span>Обращаем ваше внимание, что стоимость доставки необходимо будет оплатить при получении заказа</span>";
					}else{
						$delivtext = ""; // for ruspost empty text
						$delivprice = "";
					}
				} else {
					$delivtext = ""; // for ruspost empty text
					$delivprice = "";
				}
			} else {
				$skip = false;
			}

			if($skip==false) {
				$itemPrice = $item->price;
				$skid = ($item->price*$item->discount)/100;
				$tovars[] = array(
					"Name" => $itemCard->name,
					"Price" => $itemPrice-$skid,
					"Quantity" => $item->quantity,
					"Amount" => number_format(($itemPrice*$item->quantity)-($skid*$item->quantity),0,"",""),
					'PaymentMethod' => 'full_prepayment',
					'PaymentObject' => 'commodity',
					'Tax' => 'none',
				);
			}

		}

		$items = '<table class="items" style="width:100%;" border="0" cellpadding="1" cellspacing="1">';
		$items .= "<tr><td>№</td><td>Фото</td><td>Наименование товара</td><td>Кол-во</td><td>Ед. изм.</td><td>Цена</td><td>Сумма без скидки</td><td>Скидка</td><td>Сумма</td></tr>";
		foreach($positions as $n=>$item) {
			$itemCard = sendGetNew($item->assortment->meta->href);
			$ratio = sendGetNew($itemCard->uom->meta->href)->name;
			if($outside==false) {
				if(//$item->assortment->meta->type!=="service" 
					//$itemCard->id!=="dc6bfdfe-88d8-11e6-7a69-8f550011d820" //post
					$itemCard->id!=="e55bdc32-25ba-11e7-7a69-9711000c4584" 
					&& $itemCard->id!=="5944dd1c-4d71-11ef-0a80-048200cd036c" //cdek do sklada
					&& $itemCard->id!=="04dc70b3-1da3-11ef-0a80-1491000e0f26" /*pvz-box*/ 
					&& $itemCard->id!=="122a782b-1da3-11ef-0a80-1491000e121a" /*kur-box*/ 
					&& $itemCard->id!=="f79dc183-88d8-11e6-7a31-d0fd0017098c" /*cdek-dver*/
					//|| $itemCard->id=="bbe2b6ce-f96f-11ec-0a80-0fc30013b947" postamat
				) {
					$itemPrice = $item->price/100;
					$skid = $item->price/100*$item->discount*$item->quantity;
					$items .= "<tr><td>".($n+1)."</td>";
						$itemName = str_replace(["(1)","(2)","(3)","(4)","(5)","(6)"],["","","","",""],$itemCard->name);
						$bItem = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"NAME" => $itemName],false,[],["ID","NAME", "DETAIL_PICTURE"])->Fetch();
						if($bItem['DETAIL_PICTURE']>0) {
							$thumb = CFile::ResizeImageGet($bItem['DETAIL_PICTURE'],["width"=>100,"height"=>100],BX_RESIZE_IMAGE_EXACT);
						}
					$items .= "<td><img src=\"".$thumb['src']."\" class=\"img-fluid\"></td>";
					$items .= "<td>".$itemCard->name."</td><td>".$item->quantity."</td>";
					$items .= "<td>".$ratio."</td>";
					$items .= "<td>".($item->price/100)."</td>";
					$items .= "<td>".($item->price/100*$item->quantity)."</td>";
					$items .= "<td>".($item->price/100*$item->discount/100*$item->quantity)."</td>";
					$items .= "<td>".($item->price/100*$item->quantity-$skid/100)."</td></tr>";
				}else{
					$delivprice += $item->price; //plus delivery
					$delivname = $itemCard->name; //delivery name
				}
			} else {
				$itemPrice = $item->price/100;
				$skid = $item->price/100*$item->discount*$item->quantity;
				$items .= "<tr><td>".($n+1)."</td><td>".$itemCard->name."</td><td>".$item->quantity."</td>";
				$items .= "<td>".$ratio."</td>";
				$items .= "<td>".($item->price/100)."</td>";
				$items .= "<td>".($item->price/100*$item->quantity)."</td>";
				$items .= "<td>".($item->price/100*$item->discount/100*$item->quantity)."</td>";
				$items .= "<td>".($item->price/100*$item->quantity-$skid/100)."</td></tr>";
			}
			if($item->id == "07454daf-b75e-11ed-0a80-0bdb00120906" || $itemCard->id=="04dc70b3-1da3-11ef-0a80-1491000e0f26" /*pvz-box*/ ) {
				$pvz = true;
			}
		}

		$items .= '<tr><td colspan="8">К оплате: </td><td>'.number_format($summ/100,2).' RUB</td></tr>';
		$items .= "</table>";
		$email = "";
		foreach($order->attributes as $attr) {
			if($attr->id=="6d6c5d3f-b8da-11ed-0a80-04c000239469" && strlen($attr->value)>0) {
				die(); // die if tinkoff link is exist
			}
			//other attr
			if($attr->id=="ff839d36-b1e3-11e8-9ff4-31500008825d" && strlen($attr->value)>0) {
				$addr = htmlspecialchars_decode($attr->value->name);
			}
			if($attr->id=="bfb59047-9eee-11e8-9109-f8fc000f3666" && strlen($attr->value)>0) {
				$addr = htmlspecialchars_decode($attr->value);
			}
			switch($attr->id) {
				case "1c910c32-0b7d-11e8-7a6c-d2a900111fff" : $email = $attr->value; break;
				case "bfb591e5-9eee-11e8-9109-f8fc000f3667" : $fio = $attr->value; break;
				case "0025cf0a-acee-11e8-9107-50480001fdbd" : $phone = $attr->value; break;
			}

		}

		$params = [
			'OrderId' => $order->name,
			'Amount'  => $summ,
			'DATA'    => [
				'Email'           => $email,
				'Connection_type' => 'example'
			],
			'Receipt' => [
				'Taxation' => 'usn_income',
				'Email' => $email,
				'Items' => $tovars,
			],
			'NotificationURL' => 'https://iafstudio.ru/mshooks/notify.php',
		];
		$api->init($params);
		$response = json_decode(htmlspecialchars_decode($api->response),true);

		if($response['Success']==1) {
			// ZAPIS SSILKI V MOY SKLAD
			$link = $response['PaymentURL'];
			$postdata = array(
				'attributes' => array(
					array(
						'meta' => array(
							'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/6d6c5d3f-b8da-11ed-0a80-04c000239469',
							'type' => 'attributemetadata',
							'mediaType' => 'application/json',
						),
						'value' => $link,
					),
				)
			);
			$upd = sendPut($ord['meta']['href'],$postdata);
			$arFields = array(
				"ID" => $order->name,
				"LINK" => $link,
				"SUMM" => ($summ/100),
				"EMAIL" => $email,
				"ZAKAZ_DATE" => $order->moment,
				"FIO" => $fio,
				"PHONE" => $phone,
				"ITEMS" => $items,
				"ADDRESS" => $addr,
				"DELIVNAME" => $delivname,
				"DELIVOPLATA" => $delivtext,
			);
			if(strlen($delivname)>3) {
				$arFields["DELIVPRICE"] = $delivprice/100;
			}
			//$bill = makeBill($order->id);
			CEvent::Send("TINKOFF_LINK", "s1", $arFields,"Y","",$bill);
			CEvent::CheckEvents();
			sleep(1);
			$postdata = array(
				"state" => array(
					"meta" => array (
						"href" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/d45e9899-b6e3-11ed-0a80-01310002e7fb",
						"metadataHref" => "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata",
						"type" => "state",
						"mediaType" => "application/json"
					),
					"id" => "d45e9899-b6e3-11ed-0a80-01310002e7fb",
				),
			);
			$upd = sendPut("https://api.moysklad.ru/api/remap/1.2/entity/customerorder/".$order->id."/",$postdata);
		}else{
			$postdata = array(
				'attributes' => array(
					array(
						'meta' => array(
							'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/6d6c5d3f-b8da-11ed-0a80-04c000239469',
							'type' => 'attributemetadata',
							'mediaType' => 'application/json',
						),
						'value' => $response['Message']." / ".$response['Details'],
					),
				)
			);
			$upd = sendPut($ord['meta']['href'],$postdata);
		}
	}
}
?>