<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
define("MY_HL_BLOCK_ID","9");
CModule::IncludeModule('highloadblock');

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

function getPVZList($token) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.cdek.ru/v2/deliverypoints',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	  CURLOPT_HTTPHEADER => array(
		'Authorization: Bearer '.$token,
	  ),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return json_decode($response,true);
}

$pvzlist = getPVZList($token);

//Функция получения экземпляра класса:
function GetEntityDataClass($HlBlockId) {
	if (empty($HlBlockId) || $HlBlockId < 1)
	{
		return false;
	}
	$hlblock = HLBT::getById($HlBlockId)->fetch();	
	$entity = HLBT::compileEntity($hlblock);
	$entity_data_class = $entity->getDataClass();
	return $entity_data_class;
}

$hlblock = HLBT::getById(MY_HL_BLOCK_ID)->fetch();
$entity = HLBT::compileEntity($hlblock);

$entity_data_class = GetEntityDataClass(MY_HL_BLOCK_ID);


echo count($pvzlist); //die();

foreach($pvzlist as $p=>$pvz) {
	//if($p<3) {
	$result = $entity_data_class::add(array(
		"UF_CITY" => $pvz['location']['city'],
		"UF_CITYCODE" => $pvz['location']['city_code'],
		"UF_REGION" => $pvz['location']['region_code'],
		"UF_POSTAL_CODE" => $pvz['location']['postal_code'],
		"UF_LON" => $pvz['location']['longitude'],
		"UF_LAT" => $pvz['location']['latitude'],
		"UF_ADDRESS" => $pvz['location']['address'],
		"UF_PVZ_CODE" => $pvz['code'],
		"UF_TYPE" => $pvz['type'],
		"UF_MIN_WEIGHT" => $pvz['weight_min'],
		"UF_MAX_WEIGHT" => $pvz['weight_max'],
	));
	//}
}

?>