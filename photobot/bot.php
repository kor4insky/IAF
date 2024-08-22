<?php
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once "vendor/autoload.php";
CModule::IncludeModule("sale");
use Bitrix;
use TelegramBot\Api\Types;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

try {
    $bot = new \TelegramBot\Api\Client(BOT_IAFCMS);

	// обработка инлайнов
		$bot->inlineQuery(function ($inlineQuery) use ($bot) {
			mb_internal_encoding("UTF-8");
			$qid = $inlineQuery->getId();
			$text = $inlineQuery->getQuery();
			if(strlen($text)>=3) {

				if(strpos($text,"link_")!==false) {
					$tovId = str_replace("link_","",explode(" ",$text)[0]);
					$fullquery = explode(" ",$text); unset($fullquery[0]);
					$query = implode(" ",$fullquery);
					$items = CIBlockElement::GetList(["NAME"=>"ASC"],["IBLOCK_ID"=>17,"ACTIVE"=>"Y","NAME"=>"%".join("%", explode(" ", $query))."%"],false,["nPageSize"=>20],["ID","NAME","PREVIEW_TEXT","PREVIEW_PICTURE","CATALOG_QUANTITY","CATALOG_PRICE_3"]);
					$k = 0;
					while($item = $items->Fetch()) {

						if($item['CATALOG_QUANTITY']<=0) {
							$ostatok = "⚠️ Остаток: ".$item['CATALOG_QUANTITY'].PHP_EOL."💰 Цена: ".$item['CATALOG_PRICE_3'];
						}else{
							$ostatok = "🧮 Остаток: ".$item['CATALOG_QUANTITY'].PHP_EOL."💰 Цена: ".$item['CATALOG_PRICE_3'];
						}

						if($item['PREVIEW_PICTURE']>0) {
							$img = CFile::ResizeImageGet($item['PREVIEW_PICTURE'],["width"=>99,"height"=>99],BX_RESIZE_IMAGE_EXACT,false);
							$base[$k] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/link " . $item['ID']."_".$tovId, "HTML");
							$msg[$k] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($k, $item['NAME'], $ostatok, "https://iafstudio.ru".$img['src']."?t=".time(),99,99);
							$msg[$k]->setInputMessageContent($base[$k]);
						}else{
							$base[$k] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/link " . $item['ID']."_".$tovId, "HTML");
							$msg[$k] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($k, $item['NAME'], $ostatok); //, "https://iafstudio.ru".$img['src'],100,100);
							$msg[$k]->setInputMessageContent($base[$k]);
						}
						$k++;
					}

				} elseif(strpos($text,"linkremove_")!==false) {
					$tovId = str_replace("linkremove_","",explode(" ",$text)[0]);
					$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","PROPERTY_EXPANDABLES"])->Fetch();
					foreach($item['PROPERTY_EXPANDABLES_VALUE'] as $l=>$link) {
						$exp = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$link],false,[],["ID","NAME","PREVIEW_PICTURE","PREVIEW_TEXT","CATALOG_QUANTITY","CATALOG_PRICE_3"])->Fetch();
						if($exp['CATALOG_QUANTITY']<=0) {
							$ostatok = "⚠️ Остаток: ".$exp['CATALOG_QUANTITY'].PHP_EOL."💰 Цена: ".$exp['CATALOG_PRICE_3'];
						}else{
							$ostatok = "🧮 Остаток: ".$exp['CATALOG_QUANTITY'].PHP_EOL."💰 Цена: ".$exp['CATALOG_PRICE_3'];
						}
						if($exp['PREVIEW_PICTURE']>0) {
							$img = CFile::ResizeImageGet($exp['PREVIEW_PICTURE'],["width"=>99,"height"=>99],BX_RESIZE_IMAGE_EXACT,false);
							$base[$l] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/unlink ".$item['ID']."_".$exp['ID'], "HTML");
							$msg[$l] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($l, $exp['NAME'], $ostatok, "https://iafstudio.ru".$img['src'],100,100);
							$msg[$l]->setInputMessageContent($base[$l]);
						}else{
							$base[$l] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/unlink ".$item['ID']."_".$exp['ID'], "HTML");
							$msg[$l] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($l, $exp['NAME'], $ostatok); //, "https://iafstudio.ru".$img['src'],100,100);
							$msg[$l]->setInputMessageContent($base[$l]);
						}
					}
				} elseif(strpos($text,"linklook_")!==false) {
					$tovId = str_replace("linklook_","",explode(" ",$text)[0]);
					$fullquery = explode(" ",$text); unset($fullquery[0]);
					$query = implode(" ",$fullquery);
					$items = CIBlockElement::GetList(["NAME"=>"ASC"],["IBLOCK_ID"=>29,"ACTIVE"=>"Y","NAME"=>"%".join("%", explode(" ", $query))."%"],false,["nPageSize"=>20],["ID","NAME","PREVIEW_TEXT","PREVIEW_PICTURE"]);
					$k = 0;
					while($item = $items->Fetch()) {

						if($item['PREVIEW_PICTURE']>0) {
							$img = CFile::ResizeImageGet($item['PREVIEW_PICTURE'],["width"=>99,"height"=>99],BX_RESIZE_IMAGE_EXACT,false);
							$base[$k] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/looklink " . $item['ID']."_".$tovId, "HTML");
							$msg[$k] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($k, $item['NAME'], $item['NAME'], "https://iafstudio.ru".$img['src']."?t=".time(),99,99);
							$msg[$k]->setInputMessageContent($base[$k]);
						}else{
							$base[$k] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/looklink " . $item['ID']."_".$tovId, "HTML");
							$msg[$k] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($k, $item['NAME'], $item['NAME']);
							$msg[$k]->setInputMessageContent($base[$k]);
						}

						$k++;
					}

				} elseif(strpos($text,"deletelook_")!==false) {

					$tovId = str_replace("deletelook_","",explode(" ",$text)[0]);

					$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","PROPERTY_LOOKS"])->Fetch();
					$k = 0;
					foreach($item['PROPERTY_LOOKS_VALUE'] as $k=>$link) {
						$exp = CIBlockElement::GetList([],["IBLOCK_ID"=>29,"ID"=>$link],false,[],["ID","NAME","PREVIEW_PICTURE"])->Fetch();
						if($exp['PREVIEW_PICTURE']>0) {
							$img = CFile::ResizeImageGet($exp['PREVIEW_PICTURE'],["width"=>99,"height"=>99],BX_RESIZE_IMAGE_EXACT,false);
							$base[$k] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/unlinklook ".$item['ID']."_".$exp['ID'], "HTML");
							$msg[$k] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($k, trim($exp['NAME']), trim($exp['NAME']), "https://iafstudio.ru".$img['src'],100,100);
							$msg[$k]->setInputMessageContent($base[$k]);
						}else{
							$base[$k] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/unlinklook ".$item['ID']."_".$exp['ID'], "HTML");
							$msg[$k] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($k, trim($exp['NAME']), trim($exp['NAME']));
							$msg[$k]->setInputMessageContent($base[$k]);
						}
						$k++;
					}
				} else {
					$items = CIBlockElement::GetList(["NAME"=>"ASC"],["IBLOCK_ID"=>17,"ACTIVE"=>"Y","NAME"=>"%".join("%", explode(" ", $text))."%"],false,["nPageSize"=>20],["ID","NAME","PREVIEW_PICTURE","CATALOG_QUANTITY","CATALOG_PRICE_3"]);
					$k = 0;
					while($item = $items->Fetch()) {

						if($item['CATALOG_QUANTITY']<=0) {
							$ostatok = "⚠️ Остаток: ".$item['CATALOG_QUANTITY'].PHP_EOL."💰 Цена: ".$item['CATALOG_PRICE_3'];
						}else{
							$ostatok = "🧮 Остаток: ".$item['CATALOG_QUANTITY'].PHP_EOL."💰 Цена: ".$item['CATALOG_PRICE_3'];
						}

						if($item['PREVIEW_PICTURE']>0) {
							$img = CFile::ResizeImageGet($item['PREVIEW_PICTURE'],["width"=>99,"height"=>99],BX_RESIZE_IMAGE_EXACT,false);
							$base[$k] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/select " . $item['ID'], "HTML");
							$msg[$k] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($k, $item['NAME'], $ostatok, "https://iafstudio.ru".$img['src']."?t=".time(),99,99);
							$msg[$k]->setInputMessageContent($base[$k]);
						}else{
							$base[$k] = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text("/select " . $item['ID'], "HTML");
							$msg[$k] = new \TelegramBot\Api\Types\Inline\QueryResult\Article($k, $item['NAME'], $ostatok); //, "https://iafstudio.ru".$img['src'],100,100);
							$msg[$k]->setInputMessageContent($base[$k]);
						}
						$k++;
					}
				}
			}
			// отправка
			try{
				foreach ($msg as $answer)
				{
					$resarr[] = $answer;
				}
				$result = $bot->answerInlineQuery($qid,$resarr,101,false);
			}catch(Exception $e){
				file_put_contents("rdata.txt",print_r($e,true));
			}
		});

		// Обработка кнопок у сообщений
        $bot->on(function($update) use ($bot, $callback_loc, $find_command){

            $upd = json_decode(file_get_contents('php://input'));

			$text = $upd->callback_query->message->text;
            $callback = $update->getCallbackQuery();
            $message = $callback->getMessage();
        	$chatId = $message->getChat()->getId();
        	$data = $callback->getData();
        	$callbackId = $callback->getId();
            $callback_date = $upd->callback_query->message->date;
            $messId = $upd->callback_query->message->message_id;

            if(number_format((time()-$callback_date)/60/60/24,2)<=1.9)
            {
                $bot->deleteMessage($chatId,$messId);
            }

			if($data=="get_id") {
				$bot->sendMessage($chatId,"ID: ".$chatId);
				$bot->answerCallbackQuery($callback->getId(),"");
                $bot->answerCallbackQuery($chatId,"");
			}

			if($data=="close")
			{
				$bot->deleteMessage($chatId,$messId);
				$bot->answerCallbackQuery($callback->getId(),"");
                $bot->answerCallbackQuery($chatId,"");
			}

			//$bot->sendMessage($chatId,$data);

			if(strpos($data,"mainphoto_")!==false) {
				$tovId = str_replace("mainphoto_","",$data);
				$save = saveCurStep($chatId,"changemain_".$tovId);
				$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PROPERTY_VIDEOFILE"])->Fetch();
				$kb_back = new InlineKeyboardMarkup([
					[
						['text'=> "◀️ Назад", "callback_data"=>"view_".$tovId],
					]
				]);
				$bot->sendMessage($chatId,"Отправьте главное фото для товара <b>".$item['NAME']."</b>","HTML",null,false,$kb_back);
				$bot->answerCallbackQuery($callback->getId(),"");
                $bot->answerCallbackQuery($chatId,"");
			}

			if(strpos($data,"addgallery_")!==false) {
				$tovId = str_replace("addgallery_","",$data);
				$save = saveCurStep($chatId,"addphoto_".$tovId);
				$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME"])->Fetch();
				$kb_back = new InlineKeyboardMarkup(
				[
					[
						['text'=> "◀️ Назад", "callback_data"=>"view_".$tovId],
					]
				]);
				$bot->sendMessage($chatId,"📸 Отправьте фото (не файлом) для товара <b>".$item['NAME']."</b>".PHP_EOL."<b>ОНИ ДОБАВЯТСЯ К ТЕКУЩИМ</b>","HTML",null,false,$kb_back);
				$bot->answerCallbackQuery($callback->getId(),"");
                $bot->answerCallbackQuery($chatId,"");
			}

			if(strpos($data,"addvideo_")!==false) {
				$bot->sendChatAction($chatId,"upload_video");
				$tovId = str_replace("addvideo_","",$data);
				$save = saveCurStep($chatId,"addvideo_".$tovId);
				$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PROPERTY_VIDEOFILE"])->Fetch();

				if($item['PROPERTY_VIDEOFILE_VALUE'][0]>0) {
					$link = "🎞️ Текущее видео для <b>".$item['NAME']."</b>: ".PHP_EOL.PHP_EOL;
				}else{
					$link = "🤷‍♀️ Пока еще нет видео".PHP_EOL.PHP_EOL;
				}

				foreach($item['PROPERTY_VIDEOFILE_VALUE'] as $v=>$video) {
					$link .= '❇️ <a href="https://iafstudio.ru'.CFile::GetFileArray($item['PROPERTY_VIDEOFILE_VALUE'][0])['SRC'].'">Открыть</a>'.PHP_EOL;
				}

				$save = saveCurStep($chatId,"addvideo_".$tovId);

				$kb_back = new InlineKeyboardMarkup(
				[
					[
						['text'=> "◀️ Назад", "callback_data"=>"view_".$tovId],
					]
				]);
				$bot->sendMessage($chatId,$link.PHP_EOL."🎞️ Отправьте видео (не файлом) для товара <b>".$item['NAME']."</b>","HTML",null,false,$kb_back);
				$bot->answerCallbackQuery($callback->getId(),"");
                $bot->answerCallbackQuery($chatId,"");
			}

			if(strpos($data,"delgallery_")!==false) {
				$tovId = str_replace("delgallery_","",$data);
				$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PROPERTY_MORE_PHOTO"])->Fetch();
				if(count($item['PROPERTY_MORE_PHOTO_VALUE'])>0) {
					$media = new \TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia();
					foreach($item['PROPERTY_MORE_PHOTO_VALUE'] as $p=>$ph) {
						if($p<=8) { //only 10 photos
							$imgurl = CFile::ResizeImageGet($ph,["width"=>400,"height"=>400],BX_RESIZE_IMAGE_EXACT);
							$media->addItem(new TelegramBot\Api\Types\InputMedia\InputMediaPhoto("https://iafstudio.ru".$imgurl['src']));
						}
					}
					$bot->sendMediaGroup($chatId,$media);
					foreach($item['PROPERTY_MORE_PHOTO_VALUE'] as $p=>$ph) {
						if($p<=8) {
							$kb[][] = ["text" => $p+1, "callback_data" => "deletephoto_".$tovId."_".$ph];
						}
					}
					$kb[][] = ["text" => "◀️ Назад", "callback_data" => "view_".$tovId];
					$kb_delete = new InlineKeyboardMarkup($kb);
					$bot->sendMessage($chatId,"🌠 Какую фотографию удаляем?","HTML",null,false,$kb_delete);
				}else{
					$kb_back = new InlineKeyboardMarkup([
						[
							['text'=> "◀️ Назад", "callback_data"=>"view_".$tovId],
						]
					]);
					$bot->sendMessage($chatId,"🤷‍♀️ У товара нет доп фото. Вернитесь назад и загрузите :)","HTML",null,false,$kb_back);
				}
				$bot->answerCallbackQuery($callback->getId(),"");
				$bot->answerCallbackQuery($chatId,"");
			}

			if(strpos($data,"download_")!==false) {
				$tovId = str_replace("download_","",$data);
				$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","DETAIL_PICTURE","PROPERTY_MORE_PHOTO", "PROPERTY_VIDEOFILE"])->Fetch();
				$links = "";
				if($item['DETAIL_PICTURE']>0) {
					$downPhoto = CFile::GetFileArray($item['DETAIL_PICTURE'])['SRC'];
					$links.= "0. <a href='https://iafstudio.ru".$downPhoto."'>photo_0</a>".PHP_EOL;
				}
				if(count($item['PROPERTY_MORE_PHOTO_VALUE'])>0) {
					foreach($item['PROPERTY_MORE_PHOTO_VALUE'] as $p=>$ph) {
						$downPhoto = CFile::GetFileArray($ph)['SRC'];
						$links .= ($p+1).". <a href='https://iafstudio.ru".$downPhoto."'>photo_".($p+1)."</a>".PHP_EOL;
					}
				}
				if(count($item['PROPERTY_VIDEOFILE_VALUE'])>0) {
					foreach($item['PROPERTY_VIDEOFILE_VALUE'] as $p=>$ph) {
						$downPhoto = CFile::GetFileArray($ph)['SRC'];
                        $links .= ($p+1).". <a href='https://iafstudio.ru".$downPhoto."'>video_".($p+1)."</a>".PHP_EOL;
					}
				}
				$kb_close = new InlineKeyboardMarkup([
					[
						["text" => "Назад", "callback_data" => "view_".$tovId],
						['text'=> "Закрыть", "callback_data"=>"close"],
					]
				]);
				$bot->sendMessage($chatId,$links,"HTML",null,false,$kb_close);
				$bot->answerCallbackQuery($callback->getId(),"");
				$bot->answerCallbackQuery($chatId,"");
			}

            if(strpos($data,"label_")!==false) {
                $tovId = str_replace("label_","",$data);
                $item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PROPERTY_HIT"])->Fetch();
                $values = [];
                $hitsarr = CIBlockProperty::GetPropertyEnum(333,["sort"=>"asc"],[]);
                while($sticker = $hitsarr->Fetch()) {
                    $values[] = $sticker;
                }
                $output = [];
                foreach($values as $sticker) {
                    $output[][] = [
                        "text" => in_array($sticker['VALUE'],$item['PROPERTY_HIT_VALUE']) ? "☑️ ".$sticker['VALUE'] : "🔲 " . $sticker['VALUE'],
                        "callback_data"=> in_array($sticker['VALUE'],$item['PROPERTY_HIT_VALUE']) ? "delsticker_".$tovId."_".$sticker['ID'] : "setsticker_".$tovId."_".$sticker['ID'],
                    ];
                }
                $output[][] = ["text" => "◀️ Назад", "callback_data" => "view_".$tovId];
                $kb_sticker = new InlineKeyboardMarkup($output);
                $bot->sendMessage($chatId,"Нажмите на один из вариантов чтобы установить / удалить стикер","HTML",null,false,$kb_sticker);
                $bot->answerCallbackQuery($callback->getId(),"");
                $bot->answerCallbackQuery($chatId,"");
            }

            if(strpos($data,"setsticker_")!==false) {
                $params = explode("_",$data);
                $tovId = $params[1];
                $stickerId = $params[2];

                $itemStickers = CIBlockElement::GetProperty(17, $tovId, [], ['CODE' => 'HIT']);

                while ($sticker = $itemStickers->Fetch()) {
                    $sticker_arr["HIT"][] = $sticker['VALUE'];
                }
                $sticker_arr["HIT"][] = $stickerId;

                CIBlockElement::SetPropertyValuesEx($tovId,17,$sticker_arr);


                $item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PROPERTY_HIT"])->Fetch();
                $values = [];
                $hitsarr = CIBlockProperty::GetPropertyEnum(333,["sort"=>"asc"],[]);
                while($sticker = $hitsarr->Fetch()) {
                    $values[] = $sticker;
                }
                $output = [];
                foreach($values as $sticker) {
                    $output[][] = [
                        "text" => in_array($sticker['VALUE'],$item['PROPERTY_HIT_VALUE']) ? "☑️ ".$sticker['VALUE'] : "🔲 " . $sticker['VALUE'],
                        "callback_data"=> in_array($sticker['VALUE'],$item['PROPERTY_HIT_VALUE']) ? "delsticker_".$tovId."_".$sticker['ID'] : "setsticker_".$tovId."_".$sticker['ID'],
                    ];
                }
                $output[][] = ["text" => "◀️ Назад", "callback_data" => "view_".$tovId];
                $kb_sticker = new InlineKeyboardMarkup($output);

                $bot->sendMessage($chatId,"✅ ГОТОВО! Вы можете продолжить выбирать один из вариантов чтобы установить / удалить стикер","HTML",null,false,$kb_sticker);
                $bot->answerCallbackQuery($callback->getId(),"");
                $bot->answerCallbackQuery($chatId,"");
            }

            if(strpos($data,"delsticker_")!==false) {
                $params = explode("_",$data);
                $tovId = $params[1];
                $stickerId = $params[2];

                $itemStickers = CIBlockElement::GetProperty(17, $tovId, [], ['CODE' => 'HIT']);

                while ($sticker = $itemStickers->Fetch()) {
                    $sticker_arr["HIT"][] = $sticker['VALUE'];
                }
                $newarr = array_diff($sticker_arr["HIT"], [$stickerId]);

                if(count($newarr)>0) {
                    CIBlockElement::SetPropertyValuesEx($tovId, 17, ["HIT" => $newarr]);
                }else{
                    CIBlockElement::SetPropertyValuesEx($tovId, 17, ["HIT" => false]);
                }

                $staticHtmlCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
                $staticHtmlCache->deleteAll();

                //get values
                $item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PROPERTY_HIT"])->Fetch();
                $values = [];
                $hitsarr = CIBlockProperty::GetPropertyEnum(333,["sort"=>"asc"],[]);
                while($sticker = $hitsarr->Fetch()) {
                    $values[] = $sticker;
                }
                $output = [];
                foreach($values as $sticker) {
                    $output[][] = [
                        "text" => in_array($sticker['VALUE'],$item['PROPERTY_HIT_VALUE']) ? "☑️ ".$sticker['VALUE'] : "🔲 " . $sticker['VALUE'],
                        "callback_data"=> in_array($sticker['VALUE'],$item['PROPERTY_HIT_VALUE']) ? "delsticker_".$tovId."_".$sticker['ID'] : "setsticker_".$tovId."_".$sticker['ID'],
                    ];
                }
                $output[][] = ["text" => "◀️ Назад", "callback_data" => "view_".$tovId];
                $kb_sticker = new InlineKeyboardMarkup($output);

                $bot->sendMessage($chatId,"✅ ГОТОВО! Вы можете продолжить выбирать один из вариантов чтобы установить / удалить стикер","HTML",null,false,$kb_sticker);
                $bot->answerCallbackQuery($callback->getId(),"");
                $bot->answerCallbackQuery($chatId,"");
            }

			if(strpos($data,"delvideo_")!==false) {
				$tovId = str_replace("delvideo_","",$data);
				$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PROPERTY_VIDEOFILE"])->Fetch();
				if(count($item['PROPERTY_VIDEOFILE_VALUE'])>0) {
					if($item['PROPERTY_VIDEOFILE_VALUE'][0]>0) {
						$link = "🎞️ Текущее видео для <b>".$item['NAME']."</b>: ".PHP_EOL.PHP_EOL;
					}	
					foreach($item['PROPERTY_VIDEOFILE_VALUE'] as $v=>$video) {
						$link .= ($v+1).'. <a href="https://iafstudio.ru'.CFile::GetFileArray($item['PROPERTY_VIDEOFILE_VALUE'][0])['SRC'].'">Открыть</a>'.PHP_EOL;
						$kb[][] = ["text" => $p+1, "callback_data" => "deletevideo_".$tovId."_".$video];
					}
					$kb[][] = ["text" => "◀️ Назад", "callback_data" => "view_".$tovId];
					$kb_delete = new InlineKeyboardMarkup($kb);
					$bot->sendMessage($chatId,$link."🎞️ Какое видео удаляем?","HTML",null,false,$kb_delete);
				}else{
					$kb_back = new InlineKeyboardMarkup([
						[
							['text'=> "◀️ Назад", "callback_data"=>"view_".$tovId],
						]
					]);
					$bot->sendMessage($chatId,"🎥🤷‍♂️ У товара нет видео. Вернитесь назад и загрузите :)","HTML",null,false,$kb_back);
				}
				$bot->answerCallbackQuery($callback->getId(),"");
				$bot->answerCallbackQuery($chatId,"");
			}

			if(strpos($data,"deletephoto_")!==false) {
				$params = explode("_",$data);
				$tovId = $params[1];
				$photoId = $params[2];
				$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PROPERTY_MORE_PHOTO"])->Fetch();

				// ID элемента инфоблока и ID файла, который нужно удалить
				$elementId = $tovId;
				$fileId = $photoId;
				// Получаем список файлов множественного свойства элемента инфоблока
				$dbProperties = CIBlockElement::GetProperty(CIBlock::GetByID(17)->Fetch()['ID'], $elementId);
				while ($arProperty = $dbProperties->Fetch()) {
					if ($arProperty['CODE'] == "MORE_PHOTO" && $arProperty['VALUE'] == $fileId) {
						CIBlockElement::SetPropertyValueCode(
							$elementId,
							"MORE_PHOTO",
							[$arProperty['PROPERTY_VALUE_ID'] => ['del' => 'Y']] // ID значения свойства
						);
					}
				}
				CEventLog::Add(array("SEVERITY" => "INFO","AUDIT_TYPE_ID" => "CHATBOT_PHOTO_DELETED","MODULE_ID" => "main","ITEM_ID" => $chatId,"DESCRIPTION" => $chatId." Удалил(а) фото у товара ".$item['ID']." через чат-бот "));
				$kb_back = new InlineKeyboardMarkup(
				[
					[
						['text'=> "◀️ Назад", "callback_data"=>"view_".$tovId],
					]
				]);
				CIBlock::clearIblockTagCache(17);
				$bot->sendMessage($chatId,"💨 Фото ".$photoId." удалено","HTML",null,false,$kb_back);
			}

			if(strpos($data,"deletevideo_")!==false) {
				$params = explode("_",$data);
				$tovId = $params[1];
				$videoId = $params[2];
				$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PROPERTY_VIDEOFILE"])->Fetch();

				// ID элемента инфоблока и ID файла, который нужно удалить
				$elementId = $tovId;
				$fileId = $videoId;
				// Получаем список файлов множественного свойства элемента инфоблока
				$dbProperties = CIBlockElement::GetProperty(CIBlock::GetByID(17)->Fetch()['ID'], $elementId);
				while ($arProperty = $dbProperties->Fetch()) {
					if ($arProperty['CODE'] == "VIDEOFILE" && $arProperty['VALUE'] == $fileId) {
						CIBlockElement::SetPropertyValueCode(
							$elementId,
							"VIDEOFILE",
							[$arProperty['PROPERTY_VALUE_ID'] => ['del' => 'Y']] // ID значения свойства
						);
					}
				}
				CEventLog::Add(array("SEVERITY" => "INFO","AUDIT_TYPE_ID" => "CHATBOT_VIDEO_DELETED","MODULE_ID" => "main","ITEM_ID" => $chatId,"DESCRIPTION" => $chatId." Удалил(а) видео у товара ".$item['ID']."через чат-бот"));
				$kb_back = new InlineKeyboardMarkup(
				[
					[
						['text'=> "◀️ Назад", "callback_data"=>"view_".$tovId],
					]
				]);
				CIBlock::clearIblockTagCache(17);
				$bot->sendMessage($chatId,"💨 Видео ".$videoId." удалено","HTML",null,false,$kb_back);
			}

			if(strpos($data,"view_")!==false) {
				$tovId = str_replace("view_","",$data);
				saveCurStep($chatId,"");
				if($tovId>0) {
					$tovar = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PREVIEW_PICTURE"])->Fetch();
					if($tovar['PREVIEW_PICTURE']>0) {
						$img = CFile::ResizeImageGet($tovar['PREVIEW_PICTURE'],['width'=>400,'height'=>600],BX_RESIZE_IMAGE_EXACT);
					}else{
						$img['src'] = "/photobot/nophoto.png";
					}
						$pic = new CURLFile("/home/bitrix/www".$img['src']);
						$kb_item = new InlineKeyboardMarkup(
						[
							[
								['text'=> "🔍 Искать на сайте", "url"=>"https://iafstudio.ru/catalog/?q=".urlencode($tovar['NAME'])],
								['text' => '🔳 Печать QR', 'url' => 'https://iafstudio.ru/qr/?id='.$tovar['ID']],
							],
							[
								['text' => '🏞️ Изменить фото', "callback_data" => "mainphoto_".$tovar['ID']],
								['text' => '⤵️ Скачать все фото', "callback_data" => "download_".$tovar['ID']],
							],
							[
								['text' => '➕ Фото', 'callback_data' => 'addgallery_'.$tovar['ID']],
								['text' => '✖️ Удалить фото', 'callback_data' => 'delgallery_'.$tovar['ID']],
							],
							[
								['text' => '➕ Видео', 'callback_data' => 'addvideo_'.$tovar['ID']],
								['text' => '♨️ Удалить видео', 'callback_data' => 'delvideo_'.$tovar['ID']],
							],
							[
								['text' => '🧬 Привязать товар', 'switch_inline_query_current_chat' => 'link_'.$tovar['ID']],
								['text' => '🪚 Отвязать товар', 'switch_inline_query_current_chat' => 'linkremove_'.$tovar['ID']." ".time()],
							],
							[
								['text' => '👗 Привязать образ', 'switch_inline_query_current_chat' => 'linklook_'.$tovar['ID']." "],
								['text' => '🫥 Отвязать образ', 'switch_inline_query_current_chat' => 'deletelook_'.$tovar['ID']." ".time()],
							],
							[
                                ['text' => '❇️ Метки товара', 'callback_data' => 'label_'.$tovar['ID']],
                            ],
							[
								['text' => '❌ Закрыть', 'callback_data' => 'close'],
							],
						]);
						$bot->sendPhoto($chatId,$pic,$tovar['NAME'],null,$kb_item,false,"HTML");
						$bot->answerCallbackQuery($callback->getId(),"");
						$bot->answerCallbackQuery($chatId,"");
				}
			}

        },
        function($update){
        	$callback = $update->getCallbackQuery();
        	if (is_null($callback) || !strlen($callback->getData()))
        	{return false;}else{return true;}
        });

	$bot->command('id', function ($message) use ($bot) {
		$chatId = $message->getChat()->getId();
		$bot->sendMessage($chatId,"ID: ".$chatId,"HTML",false,null,$key_orders);
		die();
	});

     // говорильник
    $bot->on(function($update) use ($bot){

        $message = $update->getMessage();
        $text = $message->getText();
        $chatId = $message->getChat()->getId();
        $upd = json_decode(file_get_contents('php://input'));
		$messId = $upd->message->message_id;
		$curstep = loadCurStep($chatId);
		$file_id = $upd->message->photo[count($upd->message->photo)-1]->file_id;
		$filename = $upd->message->photo[count($upd->message->photo)-1]->file_unique_id.".jpg";
        $bot->sendChatAction($chatId,"typing");

		if($chatId == 2565367 || $chatId==882376974 /* JULIA */ || $chatId==535092381 || $chatId==242801253 /* MILA */ || $chatId==531557452 /* MASHA */ || $chatId==285047411 /* MILA 2*/ || $chatId==776137025 /* DMITRY */ || $chatId==1043286842) {
		}else{

			$kb_id = new InlineKeyboardMarkup(
			[
				[
					['text'=> "Получить свой ID", 'callback_data' => "get_id"],
				]
			]);

			$bot->sendMessage($chatId,"🚫 Access denied","HTML",null,false,$kb_id);
			die();
		}

		if($text=="/start") {
			$kb_close = new InlineKeyboardMarkup(
			[
				[
					['text'=> "🔍 Выбрать товар", "switch_inline_query_current_chat"=>""],
				]
			]);
			$bot->sendMessage($chatId,"👋 Здравствуйте, выберите товар для работы с ним","HTML",null,false,$kb_close);
			$bot->pinChatMessage($chatId,$messId+1,true);
			die();
		}

		if($text=="/stop") {
			saveCurStep($chatId,"");
			$bot->sendMessage($chatId,"⛔️ Операция отмененена");
		}

		if(strpos($curstep,"addphoto_")!==false && strlen($file_id)>0) {
			$tovId = str_replace("addphoto_","",$curstep);
			$bot->sendChatAction($chatId,"upload_photo");
			$file = $bot->downloadFile($file_id);
			file_put_contents($filename,$file);
			$file = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . "/photobot/".$filename);
			$item = CIblockElement::GetList([], ['IBLOCK_ID' => 17, 'ID' => $tovId], false, false, ['ID'])->Fetch();
			$values = ['n0' => ['VALUE' => $file, 'DESCRIPTION' => '']];
			$result2 = CIBlockElement::GetProperty(17, $item['ID'], [], ['CODE' => 'MORE_PHOTO']);
			while ($photo = $result2->Fetch()) {
				$values[$photo['PROPERTY_VALUE_ID']] = ['VALUE' => [
					'name' => '',
					'type' => '',
					'tmp_name' => '',
					'error' => 4,
					'size' => 0,
					'description' => ''
				], 'DESCRIPTION' => ''];
			}
			CIBlockElement::SetPropertyValuesEx($item['ID'], 17, ['MORE_PHOTO' => $values]);
			unlink($filename);
			CEventLog::Add(array("SEVERITY" => "INFO","AUDIT_TYPE_ID" => "CHATBOT_PHOTO_UPLOAD","MODULE_ID" => "main","ITEM_ID" => $chatId,"DESCRIPTION" => $chatId." добавил(а) фото для товара ".$item['ID']." через чат-бот"));
			$bot->deleteMessage($chatId,$messId);
			$kb_back = new InlineKeyboardMarkup([
				[
					['text'=> "◀️ Назад", "callback_data"=>"view_".$tovId],
				]
			]);
				CIBlock::clearIblockTagCache(17);
			$bot->sendMessage($chatId,"✅ Фото загружено, загрузите еще, или вернитесь назад","HTML",null,false,$kb_back);
		}

		if(strpos($curstep,"addvideo_")!==false && count($upd->message->video)>0) {
			$tovId = str_replace("addvideo_","",$curstep);
			$bot->sendChatAction($chatId,"upload_video");

			$file_id = $upd->message->video->file_id;
			$filename = $upd->message->video->file_unique_id.".mp4";

			$file = $bot->downloadFile($file_id);
			file_put_contents($filename,$file);
			$file = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . "/photobot/".$filename);
			$item = CIblockElement::GetList([], ['IBLOCK_ID' => 17, 'ID' => $tovId], false, false, ['ID'])->Fetch();
			$values = ['n0' => ['VALUE' => $file, 'DESCRIPTION' => '']];
			$result2 = CIBlockElement::GetProperty(17, $item['ID'], [], ['CODE' => 'VIDEOFILE']);
			while ($photo = $result2->Fetch()) {
				$values[$photo['PROPERTY_VALUE_ID']] = ['VALUE' => [
					'name' => '',
					'type' => '',
					'tmp_name' => '',
					'error' => 4,
					'size' => 0,
					'description' => ''
				], 'DESCRIPTION' => ''];
			}
			CIBlockElement::SetPropertyValuesEx($item['ID'], 17, ['VIDEOFILE' => $values]);
			unlink($filename);
			$bot->deleteMessage($chatId,$messId);
			CEventLog::Add(array("SEVERITY" => "INFO","AUDIT_TYPE_ID" => "CHATBOT_VIDEO_UPLOAD","MODULE_ID" => "main","ITEM_ID" => $chatId,"DESCRIPTION" => $chatId." добавил(а) видео для товара ".$item['ID']." через чат-бот"));
			$kb_back = new InlineKeyboardMarkup([
				[
					['text'=> "◀️ Назад", "callback_data"=>"view_".$tovId],
				]
			]);
				CIBlock::clearIblockTagCache(17);
			$bot->sendMessage($chatId,"Видео загружено, нажмите -> /stop -> чтобы завершить добавление или вернитесь назад","HTML",null,false,$kb_back);
		}

		if(strpos($curstep,"changemain_")!==false && strlen($file_id)>0) {
			$bot->sendChatAction($chatId,"upload_photo");
			$tovId = str_replace("changemain_","",$curstep);
			//$bot->sendMessage($chatId,json_encode($upd));
			$file = $bot->downloadFile($file_id);
			file_put_contents($filename,$file);
			$newfile = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/photobot/".$filename);
			$arFields = array(
				"PREVIEW_PICTURE" => $newfile,
				"DETAIL_PICTURE" => $newfile,
			);
			$el = new CIBlockElement;
			if($el->Update($tovId,$arFields)) {
				$bot->deleteMessage($chatId,$messId);
				$kb_close = new InlineKeyboardMarkup(
				[
					[
						['text' => '◀️ Назад', 'callback_data' => 'view_'.$tovId],
					],
					[
						['text'=> "❌ Закрыть", "callback_data"=>"close"],
					]
				]);
				unlink($filename);
				$bot->deleteMessage($chatId,$messId);
				$bot->sendMessage($chatId,"✅ Готово!","HTML",null,false,$kb_close);
				saveCurStep($chatId,"");
			}else{
				$bot->sendMessage($chatId,$el->LAST_ERROR);
			}
		}

		if(strpos($text,"/unlink ")!==false) {
			$params = str_replace("/unlink ","",$text);
			$args = explode("_",$params);
			$bot->deleteMessage($chatId,$messId);
			$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17, "ID" => $args[0]],false,[],["ID","NAME","PROPERTY_EXPANDABLES"])->Fetch();
			$linkitemID = $args[1];
			$linkItem = CIBlockElement::GetList([],["IBLOCK_ID"=>17, "ID" => $linkitemID],false,[],["ID","NAME"])->Fetch();
			foreach($item['PROPERTY_EXPANDABLES_VALUE'] as $e=>$exp) {
				if($exp!=$linkitemID) {
					$linked[]["VALUE"] = $exp;
				}
			}
			CIBlockElement::SetPropertyValuesEx($item['ID'], 17, ["EXPANDABLES" => $linked]);
			CEventLog::Add(array("SEVERITY" => "INFO","AUDIT_TYPE_ID" => "CHATBOT_UNLINK_DONE","MODULE_ID" => "main","ITEM_ID" => $chatId,"DESCRIPTION" => $chatId." отвязал(а) товар ".$exp." от товара ".$item['ID']."через чат-бот"));
			$kb_more = new InlineKeyboardMarkup([
			[
				['text'=> "➕ Привязать еще", "switch_inline_query_current_chat"=>"link_".$args[0]],
				['text'=> "➖ Отвязать", "switch_inline_query_current_chat"=>"linkremove_".$args[0]." ".time()],
			],
			[
				['text' => "◀️ Назад", "callback_data" => "view_".$args[0]],
				['text' => "❌ Закрыть", "callback_data" => "close"],
			],
			]);
			$bot->sendMessage($chatId,"Товар <b>".$linkItem['NAME']."</b> Отвязан от товара <b>".$item['NAME']."</b>","HTML",null,false,$kb_more);
		}

		if(strpos($text,"/link ")!==false) {
			$params = str_replace("/link ","",$text);
			$args = explode("_",$params);
			$bot->deleteMessage($chatId,$messId);
			$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17, "ID" => $args[1]],false,[],["ID","NAME","PROPERTY_EXPANDABLES"])->Fetch();
			$linkitemID = $args[0];
			$linkItem = CIBlockElement::GetList([],["IBLOCK_ID"=>17, "ID" => $linkitemID],false,[],["ID","NAME"])->Fetch();
			$linked = $item['PROPERTY_EXPANDABLES_VALUE'];
			$linked[] = $linkitemID;
			CIBlockElement::SetPropertyValuesEx($item['ID'], 17, ["EXPANDABLES" => $linked]);
			CEventLog::Add(array("SEVERITY" => "INFO","AUDIT_TYPE_ID" => "CHATBOT_LINK_DONE","MODULE_ID" => "main","ITEM_ID" => $chatId,"DESCRIPTION" => $chatId." привязал(а) товар ".$linkitemID." к товару ".$item['ID']." через чат-бот"));
			$kb_more = new InlineKeyboardMarkup([
			[
				['text'=> "➕ Привязать еще", "switch_inline_query_current_chat"=>"link_".$args[1]],
				['text'=> "➖ Отвязать", "switch_inline_query_current_chat"=>"linkremove_".$args[1]." ".time()],
			],
			[
				['text' => "◀️ Назад", "callback_data" => "view_".$args[1]],
				['text' => "❌ Закрыть", "callback_data" => "close"],
			],
			]);
			$bot->sendMessage($chatId,"✅ Товар <b>".$linkItem['NAME']."</b> привязан к товару <b>".$item['NAME']."</b>","HTML",null,false,$kb_more);
		}

		if(strpos($text,"/looklink ")!==false) {
			$params = str_replace("/looklink ","",$text);
			$args = explode("_",$params);
			$bot->deleteMessage($chatId,$messId);
			$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17, "ID" => $args[1]],false,[],["ID","NAME","PROPERTY_LOOKS"])->Fetch();
			$linkitemID = $args[0];
			$linkItem = CIBlockElement::GetList([],["IBLOCK_ID"=>29, "ID" => $linkitemID],false,[],["ID","NAME","PROPERTY_LINK_GOODS"])->Fetch();
			$linked = $item['PROPERTY_LOOKS_VALUE'];
			$linked[] = $linkitemID;
			CIBlockElement::SetPropertyValuesEx($item['ID'], 17, ["LOOKS" => $linked]);

			$lookgoods = $linkItem['PROPERTY_LINK_GOODS_VALUE'];
			$lookgoods[] = $args[1];
			CIBlockElement::SetPropertyValuesEx($linkItem['ID'], 29, ["PROPERTY_LINK_GOODS" => $lookgoods]);

			CEventLog::Add(array("SEVERITY" => "INFO","AUDIT_TYPE_ID" => "CHATBOT_LINK_LOOKS_DONE","MODULE_ID" => "main","ITEM_ID" => $chatId,"DESCRIPTION" => $chatId." привязал(а) образ ".$linkitemID." к товару ".$item['ID']." через чат-бот"));
			$kb_more = new InlineKeyboardMarkup([
			[
				['text'=> "➕ Привязать еще", "switch_inline_query_current_chat"=>"linklook_".$args[1]." "],
				['text'=> "➖ Отвязать", "switch_inline_query_current_chat"=>"deletelook_".$args[1]." ".time()],
			],
			[
				['text' => "◀️ Назад", "callback_data" => "view_".$args[1]],
				['text' => "❌ Закрыть", "callback_data" => "close"],
			],
			]);
			$bot->sendMessage($chatId,"✅ Образ <b>".$linkItem['NAME']."</b> привязан к товару <b>".$item['NAME']."</b>","HTML",null,false,$kb_more);
		}

		if(strpos($text,"/unlinklook ")!==false) {
			$params = str_replace("/unlinklook ","",$text);
			$args = explode("_",$params);
			$bot->deleteMessage($chatId,$messId);
			$item = CIBlockElement::GetList([],["IBLOCK_ID"=>17, "ID" => $args[0]],false,[],["ID","NAME","PROPERTY_LOOKS"])->Fetch();
			$linkitemID = $args[1];
			$linkItem = CIBlockElement::GetList([],["IBLOCK_ID"=>17, "ID" => $linkitemID],false,[],["ID","NAME"])->Fetch();
			foreach($item['PROPERTY_LOOKS_VALUE'] as $e=>$exp) {
				if($exp!=$linkitemID) {
					$linked[]["VALUE"] = $exp;
				}
			}
			CIBlockElement::SetPropertyValuesEx($item['ID'], 17, ["LOOKS" => $linked]);
			CEventLog::Add(array("SEVERITY" => "INFO","AUDIT_TYPE_ID" => "CHATBOT_UNLINK_LOOK_DONE","MODULE_ID" => "main","ITEM_ID" => $chatId,"DESCRIPTION" => $chatId." отвязал(а) образ ".$exp." от товара ".$item['ID']."через чат-бот"));
			$kb_more = new InlineKeyboardMarkup([
			[
				['text'=> "➕ Привязать еще", "switch_inline_query_current_chat"=>"linklook_".$args[0]],
				['text'=> "➖ Отвязать", "switch_inline_query_current_chat"=>"deletelook_".$args[0]],
			],
			[
				['text' => "◀️ Назад", "callback_data" => "view_".$args[0]],
				['text' => "❌ Закрыть", "callback_data" => "close"],
			],
			]);
			$bot->sendMessage($chatId,"Товар <b>".$linkItem['NAME']."</b> Отвязан от образа <b>".$item['NAME']."</b>","HTML",null,false,$kb_more);
		}

		if(strpos($text,"/select ")!==false) {
			$tovId = str_replace("/select ","",$text);
			$bot->deleteMessage($chatId,$messId);
			if($tovId>0) {
				$tovar = CIBlockElement::GetList([],["IBLOCK_ID"=>17,"ID"=>$tovId],false,[],["ID","NAME","PREVIEW_PICTURE"])->Fetch();
				if($tovar['PREVIEW_PICTURE']>0) {
					$img = CFile::ResizeImageGet($tovar['PREVIEW_PICTURE'],['width'=>400,'height'=>600],BX_RESIZE_IMAGE_EXACT);
				}else{
					$img['src'] = "/photobot/nophoto.png";
				}
					$pic = new CURLFile("/home/bitrix/www".$img['src']);
					$kb_item = new InlineKeyboardMarkup([
					[
						['text'=> "🔍 Искать на сайте", "url"=>"https://iafstudio.ru/catalog/?q=".urlencode($tovar['NAME'])],
						['text' => '🔳 Печать QR', 'url' => 'https://iafstudio.ru/qr/?id='.$tovar['ID']],
					],
					[
						['text' => '🏞️ Изменить фото', "callback_data" => "mainphoto_".$tovar['ID']],
						['text' => '⤵️ Скачать все фото', "callback_data" => "download_".$tovar['ID']],
					],
					[
						['text' => '➕ Фото', 'callback_data' => 'addgallery_'.$tovar['ID']],
						['text' => '✖️ Удалить фото', 'callback_data' => 'delgallery_'.$tovar['ID']],
					],
					[
						['text' => '➕ Видео', 'callback_data' => 'addvideo_'.$tovar['ID']],
						['text' => '♨️ Удалить видео', 'callback_data' => 'delvideo_'.$tovar['ID']],
					],
					[
						['text' => '🧬 Привязать товар', 'switch_inline_query_current_chat' => 'link_'.$tovar['ID']],
						['text' => '🪚 Отвязать товар', 'switch_inline_query_current_chat' => 'linkremove_'.$tovar['ID']." ".time()],
					],
					[
						['text' => '👗 Привязать образ', 'switch_inline_query_current_chat' => 'linklook_'.$tovar['ID']." "],
						['text' => '🫥 Отвязать образ', 'switch_inline_query_current_chat' => 'deletelook_'.$tovar['ID']." ".time()],
					],
                    [
                        ['text' => '❇️ Метки товара', 'callback_data' => 'label_'.$tovar['ID']],
                    ],
					[
						['text' => '❌ Закрыть', 'callback_data' => 'close'],
					],
				]);
				$bot->sendPhoto($chatId,$pic,$tovar['NAME'],null,$kb_item,false,"HTML");
			}
		}

    },

        function($message) use ($bot){
            return true;
        });

    $bot->run();
    
} catch (\TelegramBot\Api\Exception $e) {
    $e->getMessage();
}

function saveCurStep($chatId,$stepname) {
	global $DB;
	$DB->Query("INSERT INTO iaf_chatbot (CHATID,CURRENT_STEP) VALUES ('".$chatId."','".$stepname."') ON DUPLICATE KEY UPDATE CURRENT_STEP='".$stepname."'");
	return true;
}

function loadCurStep($chatId) {
	global $DB;
	$curstep = $DB->Query("SELECT * FROM iaf_chatbot WHERE CHATID=".$chatId)->Fetch()['CURRENT_STEP'];
	if($curstep == NULL) {
		$curstep = "";
	}
	return $curstep;
}
?>