<?php
/*
 * @Name: film2serial-api-service-crawler
 * @Date: 2020-10-27, 2020-11-06
 * @Version: 0.2
 * @Repository: https://github.com/BaseMax/film2serial-api-service-crawler
 */

echo "The time is " . date("h:i:sa")."\n"; // We need to know execute time in log emails...
ini_set('max_execution_time', 0);
set_time_limit(0);

require "NetPHP.php";
require "phpedb.php";
// use \Datetime;

$db=new database();
$db->connect("localhost", "movie_db", 'df*gdf*gd*ff*gd*ff*gd*fg*dfg*');
$db->db="movie_db";
$db->create_database($db->db, false);

class Film2Serial {

	// Why as a function? Because maybe i want to set a interface and extends...
	public function linkHome() {
		return "https://www.film2serial.ir/";
	}

	public function parsePage($page=1, $input=null) {
		if($input == null) {
    	   // print $this->linkHome()."page/" . $page."\n";
			$input=get($this->linkHome()."page/" . $page)[0];
		}
		preg_match_all('/<h3><span class=\"icon\"><\/span>(\s*|)<a href=\"(?<value>[^\"]+)\" rel=\"bookmark\"/i', $input, $links);
		if(isset($links["value"])) {
			$links=$links["value"];
			return $links;
		}
		return [];
	}
    
    function alt($text) {
        return trim(html_entity_decode(preg_replace('/([\-]{2,})/i', '-', preg_replace('/([\s\t\n]+)/i', '-', trim(strip_tags($text))))));
    }
    
	public function parsePost($link, $input=null) {
		global $db;
		if($input == null) {
			$input=get($link)[0];
		}
		preg_match('/>(\s*|)(?<value>[^\<]+)<\/a><\/h1>(\s*|)<div class="leftbox">/si', $input, $title);
		if(isset($title["value"])) {
			$title=$title["value"];
		}
		else {
			return [];
		}
// 		if($db->count("sld_post", ["title"=>$title]) >= 1) {
		if($db->count("sld_post", ["link"=>$link]) >= 1) {
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
			$shortContext=$splitContext[0];
		}
		else {
		    $shortContext=$context;
		}

		preg_match('/<h2><span class="icon tags"><\/span> برچسب ها<\/h2>(\s*|)<\/div>(\s*|)<div class="contact">(\s*|)<h3>(?<value>.*?)<\/h3>(\s*|)<\/div>(\s*|)<\/div>/is', $input, $tag);
        // print_r($tag);
		if(isset($tag["value"])) {
			$tag=$tag["value"];
			preg_match_all('/rel="tag">(?<value>[^\<]+)<\/a>/si', $input, $tags);
            // print_r($tags);
			if(isset($tags["value"])) {
    			$tags=$tags["value"];
    			$tags=array_unique($tags);
    			$tags=array_values($tags);
			}
			else {
			    $tags=[];
			}
		}
		else {
		    $tag="";
		    $tags=[];
		}
// 		print_r($tags);
        $categoryFilter=[
          "انیمیشن"=>"1",
          "ایرانی"=>"2",
          "سریال"=>"3",
          "فیلم"=>"4",
          "اکشن"=>"5",
          "جنایی"=>"6",
          "درام"=>"7",
          "دوبله"=>"8",
          "ترسناک"=>"9",
          "هیجان انگیز"=>"10",
          "خارجی"=>"11",
          "زبان اصلی"=>"12",
          "کمدی"=>"13",
          "مستند"=>"14",
          "دوبله فارسی"=>"15",
          "خانوادگی"=>"16",
          "فانتزی"=>"17",
          "بیوگرافی"=>"18",
          "جنگی"=>"19",
          "رازآلود"=>"20",
          "تاریخی"=>"21",
          "موزیک"=>"22",
          "ورزشی"=>"23",
          "ماجرایی"=>"24",
          "علمی تخیلی"=>"25",
          "وسترن"=>"26",
          "حیات وحش"=>"27",
          "کلاسیک"=>"28",
          "قدیمی دوبله"=>"29",
          "موزیکال"=>"30",
        ];
        $now = new DateTime();
        $data=[
            "link"=>$link,
		    "date"=>$now->format('Y-m-d H:i:s'),
		    "xfields"=>"",
			"autor"=>"admin",
			"title"=>$title,
// 			"alt_name"=>preg_replace('/([\s\t\n]+)/i', '', preg_replace('/([-]{2,})/i', '-', $title)),
// 			"alt_name"=>preg_replace('/([\-]{2,})/i', '-', preg_replace('/([\s\t\n]+)/i', '', $title)),
			"alt_name"=>$this->alt($title),
			"short_story"=>$shortContext == null ? "" : $shortContext,
			"descr"=>trim(preg_replace('/([\s\t\n]+)/i', ' ', mb_substr(strip_tags($context), 0, 299, "utf-8"))),
// 			"descr"=>substr_replace(strip_tags($context), "...", 299),
			"full_story"=>$context == null ? "" : $context,
			"category"=>"",
			"keywords"=>$keyword,
			"approve"=>1,
		];
		$clauses=["title"=>$title];
		if($db->count("sld_post", $clauses) == 0) {
    		$postID=$db->insert("sld_post", $data);
		}
		else {
		    $post=$db->select("sld_post", $clauses, "", "id");
		    $db->update("sld_post", $clauses, $data);
		    $postID=$post["id"];
		}
		$_vals=[
		    "eid"=>null,
		    "news_id"=>$postID,
		    "news_read"=>0,
		    "allow_rate"=>1,
		    "rating"=>0,
		    "vote_num"=>0,
		    "votes"=>0,
		    "view_edit"=>0,
		    "disable_index"=>0,
		    "related_ids"=>'',
		    "access"=>'',
		    "editdate"=>0,
		    "editor"=>'',
		    "reason"=>'',
		    "user_id"=>1,
		    "disable_search"=>0,
		    "need_pass"=>0,
		    "allow_rss"=>1,
		    "allow_rss_turbo"=>1,
		    "allow_rss_dzen"=>1,
		];
		$clauses=["news_id"=>$postID];
		if($db->count("sld_post_extras", $clauses) == 0) {
	        $db->insert("sld_post_extras", $_vals);
		}

        // category
// 		print_r($categories);
// 		$_cats=[];
		foreach($categories as $i=>$category) {
		    $category=trim($category);
		  //  if(isset($categoryFilter[$category])) {
    // 	        $_cats[]=$categoryFilter[$category];
		  //  }
			$clauses=["name"=>$category];
			if($db->count("sld_category", $clauses) == 0) {
			    $clauses["keywords"]="";
			    $clauses["fulldescr"]="";
			    $clauses["alt_name"]=trim($this->alt($clauses["name"]));
				$newID=$db->insert("sld_category", $clauses);
			}
			else {
				$find=$db->select("sld_category", $clauses, "", "id");
				if($find == null || $find == []) {
					unset($categories[$i]);
					continue;
				}
				else {
				    $newID=$find["id"];
				}
			}
			$db->delete("sld_post_extras_cats", ["news_id"=>$postID]);
	        $extcatID=$db->insert("sld_post_extras_cats", ["news_id"=>$postID, "cat_id"=>$newID]);
	       // $_cats[]=$extcatID;
			$_cats[]=$newID;
		}
// 		print "--------\n";
// 		print_r($_cats);
		if($_cats !=[]) {
		    $_cats=implode(",", $_cats);
    		$db->update("sld_post", ["id"=>$postID], ["category"=>$_cats]);
		}
		else {
    		$db->update("sld_post", ["id"=>$postID], ["category"=>""]);
		}
        
        // print_r($tags);
	    $clauses=["news_id"=>$postID];
	    $db->delete("sld_tags", $clauses);
		foreach($tags as $item) {
		    $clauses=["news_id"=>$postID, "tag"=>$item];
		  //  print_r($clauses);
		    if($db->count("sld_tags", $clauses) == 0) {
    		    $db->insert("sld_tags", $clauses);
		    }
		}
		if($tags != []) {
		    $stringTags=implode(",", $tags);
		    print $postID."\n";
		    $db->update("sld_post", ["id"=>$postID], ["tags"=>$stringTags]);
		}

		return $data;
	}

	function countPage($input=null) {
		if($input == null) {
			$input=get("https://www.film2serial.ir/")[0];
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

// Testing
// $post=$service->parsePost("https://www.film2serial.ir/1399/08/10/%d8%af%d8%a7%d9%86%d9%84%d9%88%d8%af-%d9%81%d8%b5%d9%84-%d8%a7%d9%88%d9%84-%d8%b3%d8%b1%db%8c%d8%a7%d9%84-dracula-2020-%d8%a8%d8%a7-%d8%af%d9%88%d8%a8%d9%84%d9%87-%d9%81%d8%a7%d8%b1%d8%b3%db%8c.html");
// exit();

$input=get($service->linkHome())[0];
$page=$service->countPage($input);
print "Page: ".$page."\n";
//$page=10;
//$page=1;
// $i=1;
// $i=$page/2;
$i=$page;
// $i=$page-30;
// $i=35;
// $i=180;
// $i=$page-150;
// $i=$page-220;
// $i=$page-250;
// $i=$page-440;
// $i=300;
// $i=400;
// for(;$i<=$page;$i++) {
for(;$i>=1;$i--) {
    if($i == 1) {
    	$links=$service->parsePage($i, $input);
    }
    else {
    	$links=$service->parsePage($i);
    }
// 	print_r($links);
	foreach($links as $link) {
		$post=$service->parsePost($link);
// 		print_r($post);
        print '.';
	}
	print '#';
}
print "\nDone.";
/*
INSERT INTO `sld_category` (`id`, `parentid`, `posi`, `name`, `alt_name`, `icon`, `skin`, `descr`, `keywords`, `news_sort`, `news_msort`, `news_number`, `short_tpl`, `full_tpl`, `metatitle`, `show_sub`, `allow_rss`, `fulldescr`, `disable_search`, `disable_main`, `disable_rating`, `disable_comments`, `enable_dzen`, `enable_turbo`, `active`, `rating_type`) VALUES (NULL, '0', '1', 'خبرها', 'news', '', '', '', '', '', '', '0', '', '', '', '0', '1', '', '0', '0', '0', '0', '1', '1', '1', '-1')

INSERT INTO `sld_post` (`id`, `autor`, `date`, `short_story`, `full_story`, `xfields`, `title`, `descr`, `keywords`, `category`, `alt_name`, `comm_num`, `allow_comm`, `allow_main`, `approve`, `fixed`, `allow_br`, `symbol`, `tags`, `metatitle`) VALUES (NULL, 'admin-shop', '2020-10-28 23:51:40', '<h2><b>دانلود انیمیشن آخرین داستان با کیفیت عالی 1080p Full HD</b></h2><b>آخرین داستان محصول 1399 به کارگردانی اشکان رهگذر با لینک مستقیم</b><br><b><a href=\\\"https://www.film2serial.ir/category/%D9%81%DB%8C%D9%84%D9%85/%D8%A7%DB%8C%D8%B1%D8%A7%D9%86%DB%8C\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">فیلم ایرانی </a></b><b>با کیفیت عالی HD</b><br><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan.jpg\\\" alt=\\\"\\\" width=\\\"500\\\" height=\\\"750\\\" srcset=\\\"https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan.jpg 500w, https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan-200x300.jpg 200w\\\" sizes=\\\"(max-width: 500px) 100vw, 500px\\\" class=\\\"fr-fic fr-dii\\\"><br>~~~~~~~~~~~~~~~~~~~~~~~~~~~<br>نام انیمیشن:آخرین داستان<br>کیفیت : WEB-DL<br>موضوع : انیمیشن، درام، هیجان انگیز<br>فرمت : MP4<br>مدت زمان: 101 دقیقه<br>سال انتشار : 1399<br>~~~~~~~~<br>کارگردان :اشکان رهگذر<br>~~~~~~~~<br>گویندگان :پرویز پرستویی، لیلا حاتمی، حسن پورشیرازی، اشکان خطیبی، بیتا فرهی، حامد بهداد، شقایق فراهانی، ملیکا شریفی نیا، زهیر یاری، باران کوثری، بانیپال شومون، فرخ نعمتی و…<br><br><br>~~~~~~~~<br>خلاصه داستان : در دورانی که سایه اهریمن بر سرزمین‌ها سیطره یافته، جمشید کی با سپاهی متشکل از متحدانش و با تکیه بر فرّ خود در مقابل سپاهیان اهریمن صف آرایی می‌کند، به خواست یزدان، جمشید بر اهریمنیان پیروز می شود. جمشید بر تخت تکیه زده و مغرور از پیروزی، خود را دارای فراست ایزدی می‌داند. وی متحدان را برای اشغال سرزمین‌های دیگر و شکار اهریمن فرا می‌خواند اما یزدان از وی روی برگردانده و فرّ و فراست جمشید را از وی می‌ستاند. جمشید تنها، گرفتار طمع و جنونی سیری ناپذیر می‌شود. وی دخترش شهرزاد را تنها می‌گذارد و…', '<h2><b>دانلود انیمیشن آخرین داستان با کیفیت عالی 1080p Full HD</b></h2><b>آخرین داستان محصول 1399 به کارگردانی اشکان رهگذر با لینک مستقیم</b><br><b><a href=\\\"https://www.film2serial.ir/category/%D9%81%DB%8C%D9%84%D9%85/%D8%A7%DB%8C%D8%B1%D8%A7%D9%86%DB%8C\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">فیلم ایرانی </a></b><b>با کیفیت عالی HD</b><br><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan.jpg\\\" alt=\\\"\\\" width=\\\"500\\\" height=\\\"750\\\" srcset=\\\"https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan.jpg 500w, https://www.film2serial.ir/wp-content/uploads/2020/10/Akharin-Dastan-200x300.jpg 200w\\\" sizes=\\\"(max-width: 500px) 100vw, 500px\\\" class=\\\"fr-fic fr-dii\\\"><br>~~~~~~~~~~~~~~~~~~~~~~~~~~~<br>نام انیمیشن:آخرین داستان<br>کیفیت : WEB-DL<br>موضوع : انیمیشن، درام، هیجان انگیز<br>فرمت : MP4<br>مدت زمان: 101 دقیقه<br>سال انتشار : 1399<br>~~~~~~~~<br>کارگردان :اشکان رهگذر<br>~~~~~~~~<br>گویندگان :پرویز پرستویی، لیلا حاتمی، حسن پورشیرازی، اشکان خطیبی، بیتا فرهی، حامد بهداد، شقایق فراهانی، ملیکا شریفی نیا، زهیر یاری، باران کوثری، بانیپال شومون، فرخ نعمتی و…<br><br><br>~~~~~~~~<br>خلاصه داستان : در دورانی که سایه اهریمن بر سرزمین‌ها سیطره یافته، جمشید کی با سپاهی متشکل از متحدانش و با تکیه بر فرّ خود در مقابل سپاهیان اهریمن صف آرایی می‌کند، به خواست یزدان، جمشید بر اهریمنیان پیروز می شود. جمشید بر تخت تکیه زده و مغرور از پیروزی، خود را دارای فراست ایزدی می‌داند. وی متحدان را برای اشغال سرزمین‌های دیگر و شکار اهریمن فرا می‌خواند اما یزدان از وی روی برگردانده و فرّ و فراست جمشید را از وی می‌ستاند. جمشید تنها، گرفتار طمع و جنونی سیری ناپذیر می‌شود. وی دخترش شهرزاد را تنها می‌گذارد و…<br><br><br> <br>==============================================<br><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"><b><a href=\\\"https://traffic.upera.tv/2736197-0-BLURAY.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 1080p BluRay</a>                            قیمت : 7000 تومان </b><br> <br><b><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"><a href=\\\"https://traffic.upera.tv/2736197-0-HQ_1080.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 1080p HQ</a>                                  قیمت : 6000 تومان </b><br> <br><b> <img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"><a href=\\\"https://traffic.upera.tv/2736197-0-1080.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 1080p</a>                                       قیمت : 5500 تومان    </b><br> <br><b> <img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"><a href=\\\"https://traffic.upera.tv/2736197-0-720.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 720p</a>                                        قیمت : 5200 تومان                </b><br> <br><b><img src=\\\"https://www.film2serial.ir/wp-content/uploads/2014/12/download.gif\\\" alt=\\\"download\\\" width=\\\"24\\\" height=\\\"24\\\" class=\\\"fr-fic fr-dii\\\"> <a href=\\\"https://traffic.upera.tv/2736197-0-480.mp4?ref=bIz6\\\" rel=\\\"noopener noreferrer external\\\" target=\\\"_blank\\\">دانلود با لینک مستقیم کیفیت 480p</a>                                        قیمت : 5100 تومان </b><br> <br> <br><b>درباره انیمیشن:</b><br>آخرین داستان (The Last Fiction 2018) اولین انیمیشن بلند سینمایی به نویسندگی و کارگردانی اشکان رهگذر است که از سال ۱۳۸۷ تا ۱۳۹۶ در استودیو هورخش تولید شده‌است. داستان این فیلم برداشتی آزاد از روایت ضحاک در شاهنامه اثر حکیم ابوالقاسم فردوسی است. آخرین داستان به عنوان اولین نماینده سینمای انیمیشن ایران در کنار ۳۴۴ فیلم واجد شرایط به بخش رقابتی بهترین فیلم نود و دومین دوره جوایز سینمایی اسکار راه یافت. ', '', 'دانلود انیمیشن آخرین داستان با کیفیت HD', 'دانلود انیمیشن آخرین داستان با کیفیت عالی 1080p Full HDآخرین داستان محصول 1399 به کارگردانی اشکان رهگذر با لینک مستقیم فیلم ایرانی با کیفیت عالی HD ~~~~~~~~~~~~~~~~~~~~~~~~~~~ نام انیمیشن:آخرین داستان کیفیت : WEB-DL موضوع : انیمیشن، درام، هیجان انگیز فرمت : MP4 مدت زمان: 101 دقیقه سال انتشار :', 'داستان, کیفیت, جمشید, دانلود, اشکان, انیمیشن, مستقیم, اهریمن, آخرین, رهگذر, تومان, 1080p, فراست, کارگردانی, پیروز, دارای, پیروزی،, مغرور, خواست, اهریمنیان', '1', 'دانلود-انیمیشن-آخرین-داستان-با-کیفیت-HD', '0', '1', '1', '1', '0', '0', '', '', '')

*/
