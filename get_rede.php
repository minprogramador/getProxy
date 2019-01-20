<?php

require('Curl.php');

$keyapi  = 'VYZK892qeodPDML7fU6BFAjGtQuh4HWc';
$url_api = "http://falcon.proxyrotator.com:51337/?apiKey={$keyapi}&country=br&port=3128";

if(count($argv) > 1) {
	foreach($argv as $arv){
		if(stristr($arv, 'u=')){
			$url = str_replace('u=', '', $arv);
		}
		if(stristr($arv, 's=')){
			$strurl = str_replace('s=', '', $arv);			
		}
		if(stristr($arv, 't=')){
			$timeoutproxy = str_replace('t=', '', $arv);			
		}
	}
}else{
	die('--> exemplo de uso:'.PHP_EOL.PHP_EOL.'php get_rede.php u="https://google.." s="google" t=3'.PHP_EOL.PHP_EOL);
}

if(!$url) {
	die('insira uma url valida! ex: php get_rede.php u="https://google.." s="google" t=3' . PHP_EOL);
}

if(!$strurl) {
	die('insira a string da url! ex: php get_rede.php u="https://google.." s="google" t=3' . PHP_EOL);
}

if(!$timeoutproxy) {
	$timeoutproxy = 5;
}

$max_get      = 5;
$count_get    = 0;
$total_stress = 10;
$max_chance   = 10;
$max_chancei  = 0;
$resultado    = [];
$resultok     = [];

while(true) {

	if($max_chancei >= $max_chance) {
		if(isset($infosokk)){
			$infosokk['url'] = $url;
			$infosokk['strurl'] = $strurl;
			print_r($infosokk);
		}else{
			echo 'nada encontrado, tente denovo';
		}
		break;
	}

	if($count_get >= 5) {
		echo "tentou pegar proxy 5x, e nao conseguiu, stop.";
		break;
	}

	$Curl = new Curl();
	$Curl->setTimeout(5);
	$Curl->add($url_api);
	$respr = $Curl->run();
	$respr = $respr['body'][0];
	$respr = json_decode($respr);

	if($respr->proxy and stristr($respr->proxy, ':')){
		$count_get    = 0;
		$proxy        = $respr->proxy;
		$pr_count_cod = 0;
		$pr_count_on  = 0;
		$pr_list_time = [];
		$pr_media = 0;

		$Curlin = new Curl();
		$useragent = $respr->randomUserAgent;
		$Curlin->setTimeout($timeoutproxy);

		for ($i = 1; $i <= $total_stress; $i++) {
			$Curlin->add($url, null, null, $proxy);
		}
		$respr = $Curlin->run();

		foreach($respr['body'] as $key => $value) {
			$inf       = $respr['info'][$key];
			$timeinf   = $inf['total_time'];
			$pr_media  = $pr_media + $timeinf;
			$pr_list_time[] = $timeinf;
			$statusinf = $inf['http_code'];
			$res       = $value;

			if($statusinf == '200'){
				$pr_count_cod++;
			}

			if(strlen($res) > 10)
			{
				if (stripos($res,$strurl) !== false) {
					$resultado[] = [
						'proxy'    => $proxy,
						'timeout'  => $timeinf,
						'httpcode' => $statusinf,
						'status'   => 'on'
					];
					$pr_count_on++;
				}
			}
			else
			{
				$resultado[] = [
					'proxy'    => $proxy,
					'timeout'  => $timeinf,
					'httpcode' => $statusinf,
					'status'   => 'off'
				];
			}
		}

		$pr_media_ok = array_sum($pr_list_time)/count($pr_list_time); 
		$pr_media_ok = round($pr_media_ok, 2);
		$pr_media_ok = round($pr_media_ok);

		if($pr_media_ok < 5) {

			if($pr_count_on === $total_stress) {
				$infosokk = [
					'count_test' => $pr_count_on,
					'total_test' => $total_stress,
					'timeout' => $pr_media_ok,
					'proxy'   => $proxy
				];
				$max_chancei = $max_chance+10;
			}

		}else{
			echo "\n--> proxy: $proxy - timeout media: $pr_media_ok  - count_test: $pr_count_on de $total_stress ~ passando para o proximo...\n";
		}
	} else {
		$count_get++;
		continue;
	}

	$max_chancei++;
}
