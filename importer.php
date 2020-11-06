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

require "src/Film2Serial.php";

$service=new Film2Serial();

/*
Testing
$post=$service->parsePost("https://www.film2serial.ir/1399/08/10/%d8%af%d8%a7%d9%86%d9%84%d9%88%d8%af-%d9%81%d8%b5%d9%84-%d8%a7%d9%88%d9%84-%d8%b3%d8%b1%db%8c%d8%a7%d9%84-dracula-2020-%d8%a8%d8%a7-%d8%af%d9%88%d8%a8%d9%84%d9%87-%d9%81%d8%a7%d8%b1%d8%b3%db%8c.html");
print_r($post);
exit();
*/

$input=get($service->linkHome())[0];
$page=$service->countPage($input);
print "Page: ".$page."\n";

$i=$page;
for(;$i>=1;$i--) {
    if($i == 1) {
        $links=$service->parsePage($i, $input);
    }
    else {
        $links=$service->parsePage($i);
    }
    foreach($links as $link) {
        $post=$service->parsePost($link);
        print '.';
    }
    print '#';
}
print "\nDone.";
