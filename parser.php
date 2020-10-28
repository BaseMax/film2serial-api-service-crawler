<?php
// Max Base
// film2serial-api-service-crawler

class Film2Serial {

	public function linkHome() {
		return "https://www.film2serial.ir/";
	}

	public function parsePage($page=1, $input=null) {
		if($input == null) {
			$input=file_get_contents($this->linkHome()."page/".$page);
		}
		preg_match_all('/<h3><span class=\"icon\"><\/span>(\s*|)<a href=\"(?<value>[^\"]+)\" rel=\"bookmark\"/i', $input, $links);
		if(isset($links["value"])) {
			$links=$links["value"];
			return $links;
		}
		return [];
	}

	public function parsePost($link, $input=null) {
		if($input == null) {
			$input=file_get_contents($link);
		}
		preg_match('/>(\s*|)(?<value>[^\<]+)<\/a><\/h1>(\s*|)<div class="leftbox">/si', $input, $title);
		if(isset($title["value"])) {
			$title=$title["value"];
		}
		else {
			return [];
		}
		preg_match('/<div class="rightbox">(\s*|)<ul>(\s*|)<li><span class="icons daste"><\/span>(\s*|)(?<value>.*?)<\/li>/si', $input, $categories);
		if(isset($categories["value"])) {
			$categories=$categories["value"];
			$categories=strip_tags($categories);
			$categories=explode(" , ", $categories);
		}
		else {
			$categories=[];
		}
		preg_match('/<\/div>(\s*|)<div class="contents">(\s*|)(?<value>.*?)<\/div>(\s*|)<\/div>(\s*|)<div class="entry">/si', $input, $context);
		if(isset($context["value"])) {
			$context=$context["value"];
		}
		preg_match('/<meta name="description" content="(?<value>[^\"]+)"/si', $input, $keyword);
		if(isset($keyword["value"])) {
			$keyword=$keyword["value"];
		}
		else {
			$keyword=strip_tags($context);
		}
		return [
			"title"=>$title,
			"context"=>$context,
			"categories"=>$categories,
			"keyword"=>$keyword,
		];
	}

	function countPage($input=null) {
		if($input == null) {
			$input=file_get_contents("https://www.film2serial.ir/");
		}
		preg_match('/<a class=\"last\" href=\"https:\/\/www.film2serial.ir\/page\/(?<value>[0-9]+)\"/i', $input, $lastPage);
		// print_r($lastPage);
		if(isset($lastPage["value"])) {
			$lastPage=(int)$lastPage["value"];
			return $lastPage;
		}
	}	
}
$service=new Film2Serial();
$input=file_get_contents($service->linkHome());
$page=$service->countPage($input);
print "Page: ".$page."\n";
for($i=1;$i<=$page;$i++) {
	$links=$service->parsePage($i, $input);
	print_r($links);
	foreach($links as $link) {
		$post=$service->parsePost($link);
		print_r($post);
	}
}
