<?php
// Max Base
// film2serial-api-service-crawler
// https://github.com/BaseMax/film2serial-api-service-crawler

ini_set('max_execution_time', 0);
set_time_limit(0);
require "src/phpedb.php";

$db=new database();
$db->connect("localhost", "movie_site", "****");
$db->db="movie_site";
$db->create_database($db->db, false);

class Film2Serial {

	// Why as a function? Because maybe i want to set a interface and extends...
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
		global $db;
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
		$splitContext=preg_split('/<p([^\>]+|)><span id="more-([0-9]+)"><\/span><\/p>/i', $context);
		// print_r($splitContext);
		if(is_array($splitContext) and count($splitContext) >= 2) {
			$context=$splitContext[0];
			$moreContext=$splitContext[1];
		}
		foreach($categories as $i=>$category) {
			$clauses=["name"=>$category];
			if($db->count("sld_category", $clauses) == 0) {
			    $clauses["keywords"]="";
			    $clauses["fulldescr"]="";
				$newID=$db->insert("sld_category", $clauses);
				$categories[$i]=$newID;
			}
			else {
				$find=$db->select("sld_category", $clauses, "", "id");
				if($find == null || $find == []) {
					unset($categories[$i]);
				}
				else {
					$categories[$i]=$find["id"];
				}
			}
		}
		if($categories != []) {
			$categories=implode(",", $categories);
		}
		else {
			$categories="";
		}
		$count=$db->count("sld_post", ["title"=>$title]);
		var_dump($count);
		if($count == 0) {
    		return [
    		    "xfields"=>"",
    			"autor"=>"admin-shop",
    			"title"=>$title,
    			"alt_name"=>str_replace("--", "-", str_replace(" ", "-", $title)),
    			"short_story"=>$context,
    			"descr"=>substr_replace(($context), "...", 299),
    			"full_story"=>$moreContext,
    			"category"=>$categories,
    			"keywords"=>$keyword,
    		];
		}
		return [];
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
//$page=10;
for($i=1;$i<=$page;$i++) {
	if($i == 1) {
		$links=$service->parsePage($i, $input);
	}
	else {
		$links=$service->parsePage($i);
	}
	print_r($links);
	foreach($links as $link) {
		$post=$service->parsePost($link);
		print_r($post);
		if($post !== []) {
			$db->insert("sld_post", $post);
		}
	}
}
/*
INSERT INTO `sld_category` (`id`, `parentid`, `posi`, `name`, `alt_name`, `icon`, `skin`, `descr`, `keywords`, `news_sort`, `news_msort`, `news_number`, `short_tpl`, `full_tpl`, `metatitle`, `show_sub`, `allow_rss`, `fulldescr`, `disable_search`, `disable_main`, `disable_rating`, `disable_comments`, `enable_dzen`, `enable_turbo`, `active`, `rating_type`) VALUES (NULL, '0', '1', 'خبرها', 'news', '', '', '', '', '', '', '0', '', '', '', '0', '1', '', '0', '0', '0', '0', '1', '1', '1', '-1')

INSERT INTO `sld_post` (`id`, `autor`, `date`, `short_story`, `full_story`, `xfields`, `title`, `descr`, `keywords`, `category`, `alt_name`, `comm_num`, `allow_comm`, `allow_main`, `approve`, `fixed`, `allow_br`, `symbol`, `tags`, `metatitle`) VALUES (NULL, 'admin-shop', '2020-10-28 23:51:40', '<h2><b>دانلود انیمیشن آخرین داستان با کیفیت عالی 1080p Full HD</b></h2><b>آخرین داستان محصول 1399 به کارگردانی اشکان رهگذر با لینک مستقیم</b><br><b><a href=\\\"https://www.film2serial.ir/category/%D9%81%DB%8C%D9%84%D9%85/%D8%A7%DB%8C%D8%B1%D8%A7%D9%86%DB%8C\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">فیلم ایرانی </a></b><b>با کیفیت عالی HD</b><br><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan.jpg\\\" alt=\\\"\\\" width=\\\"500\\\" height=\\\"750\\\" srcset=\\\"https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan.jpg 500w, https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan-200x300.jpg 200w\\\" sizes=\\\"(max-width: 500px) 100vw, 500px\\\" class=\\\"fr-fic fr-dii\\\"><br>~~~~~~~~~~~~~~~~~~~~~~~~~~~<br>نام انیمیشن:آخرین داستان<br>کیفیت : WEB-DL<br>موضوع : انیمیشن، درام، هیجان انگیز<br>فرمت : MP4<br>مدت زمان: 101 دقیقه<br>سال انتشار : 1399<br>~~~~~~~~<br>کارگردان :اشکان رهگذر<br>~~~~~~~~<br>گویندگان :پرویز پرستویی، لیلا حاتمی، حسن پورشیرازی، اشکان خطیبی، بیتا فرهی، حامد بهداد، شقایق فراهانی، ملیکا شریفی نیا، زهیر یاری، باران کوثری، بانیپال شومون، فرخ نعمتی و…<br><br><br>~~~~~~~~<br>خلاصه داستان : در دورانی که سایه اهریمن بر سرزمین‌ها سیطره یافته، جمشید کی با سپاهی متشکل از متحدانش و با تکیه بر فرّ خود در مقابل سپاهیان اهریمن صف آرایی می‌کند، به خواست یزدان، جمشید بر اهریمنیان پیروز می شود. جمشید بر تخت تکیه زده و مغرور از پیروزی، خود را دارای فراست ایزدی می‌داند. وی متحدان را برای اشغال سرزمین‌های دیگر و شکار اهریمن فرا می‌خواند اما یزدان از وی روی برگردانده و فرّ و فراست جمشید را از وی می‌ستاند. جمشید تنها، گرفتار طمع و جنونی سیری ناپذیر می‌شود. وی دخترش شهرزاد را تنها می‌گذارد و…', '<h2><b>دانلود انیمیشن آخرین داستان با کیفیت عالی 1080p Full HD</b></h2><b>آخرین داستان محصول 1399 به کارگردانی اشکان رهگذر با لینک مستقیم</b><br><b><a href=\\\"https://www.film2serial.ir/category/%D9%81%DB%8C%D9%84%D9%85/%D8%A7%DB%8C%D8%B1%D8%A7%D9%86%DB%8C\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">فیلم ایرانی </a></b><b>با کیفیت عالی HD</b><br><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan.jpg\\\" alt=\\\"\\\" width=\\\"500\\\" height=\\\"750\\\" srcset=\\\"https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan.jpg 500w, https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan-200x300.jpg 200w\\\" sizes=\\\"(max-width: 500px) 100vw, 500px\\\" class=\\\"fr-fic fr-dii\\\"><br>~~~~~~~~~~~~~~~~~~~~~~~~~~~<br>نام انیمیشن:آخرین داستان<br>کیفیت : WEB-DL<br>موضوع : انیمیشن، درام، هیجان انگیز<br>فرمت : MP4<br>مدت زمان: 101 دقیقه<br>سال انتشار : 1399<br>~~~~~~~~<br>کارگردان :اشکان رهگذر<br>~~~~~~~~<br>گویندگان :پرویز پرستویی، لیلا حاتمی، حسن پورشیرازی، اشکان خطیبی، بیتا فرهی، حامد بهداد، شقایق فراهانی، ملیکا شریفی نیا، زهیر یاری، باران کوثری، بانیپال شومون، فرخ نعمتی و…<br><br><br>~~~~~~~~<br>خلاصه داستان : در دورانی که سایه اهریمن بر سرزمین‌ها سیطره یافته، جمشید کی با سپاهی متشکل از متحدانش و با تکیه بر فرّ خود در مقابل سپاهیان اهریمن صف آرایی می‌کند، به خواست یزدان، جمشید بر اهریمنیان پیروز می شود. جمشید بر تخت تکیه زده و مغرور از پیروزی، خود را دارای فراست ایزدی می‌داند. وی متحدان را برای اشغال سرزمین‌های دیگر و شکار اهریمن فرا می‌خواند اما یزدان از وی روی برگردانده و فرّ و فراست جمشید را از وی می‌ستاند. جمشید تنها، گرفتار طمع و جنونی سیری ناپذیر می‌شود. وی دخترش شهرزاد را تنها می‌گذارد و…<br><br><br> <br>==============================================<br><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"><b><a href=\\\"https://traffic.upera.tv/2736197-0-BLURAY.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 1080p BluRay</a>                            قیمت : 7000 تومان </b><br> <br><b><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"><a href=\\\"https://traffic.upera.tv/2736197-0-HQ_1080.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 1080p HQ</a>                                  قیمت : 6000 تومان </b><br> <br><b> <img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"><a href=\\\"https://traffic.upera.tv/2736197-0-1080.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 1080p</a>                                       قیمت : 5500 تومان    </b><br> <br><b> <img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"><a href=\\\"https://traffic.upera.tv/2736197-0-720.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 720p</a>                                        قیمت : 5200 تومان                </b><br> <br><b><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"> <a href=\\\"https://traffic.upera.tv/2736197-0-480.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 480p</a>                                        قیمت : 5100 تومان </b><br> <br> <br><b>درباره انیمیشن:</b><br>آخرین داستان (The Last Fiction 2018) اولین انیمیشن بلند سینمایی به نویسندگی و کارگردانی اشکان رهگذر است که از سال ۱۳۸۷ تا ۱۳۹۶ در استودیو هورخش تولید شده‌است. داستان این فیلم برداشتی آزاد از روایت ضحاک در شاهنامه اثر حکیم ابوالقاسم فردوسی است. آخرین داستان به عنوان اولین نماینده سینمای انیمیشن ایران در کنار ۳۴۴ فیلم واجد شرایط به بخش رقابتی بهترین فیلم نود و دومین دوره جوایز سینمایی اسکار راه یافت. ', '', 'دانلود انیمیشن آخرین داستان با کیفیت HD', 'دانلود انیمیشن آخرین داستان با کیفیت عالی 1080p Full HDآخرین داستان محصول 1399 به کارگردانی اشکان رهگذر با لینک مستقیم فیلم ایرانی با کیفیت عالی HD ~~~~~~~~~~~~~~~~~~~~~~~~~~~ نام انیمیشن:آخرین داستان کیفیت : WEB-DL موضوع : انیمیشن، درام، هیجان انگیز فرمت : MP4 مدت زمان: 101 دقیقه سال انتشار :', 'داستان, کیفیت, جمشید, دانلود, اشکان, انیمیشن, مستقیم, اهریمن, آخرین, رهگذر, تومان, 1080p, فراست, کارگردانی, پیروز, دارای, پیروزی،, مغرور, خواست, اهریمنیان', '1', 'دانلود-انیمیشن-آخرین-داستان-با-کیفیت-HD', '0', '1', '1', '1', '0', '0', '', '', '')
*/
