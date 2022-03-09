<?php

// curl 'https://memory.culture.tw/Home/Result?Filter_Keyword_Rights_2=CC%20BY&Filter_Keyword_Des_Rights_2=CC0&SearchMode=Precise&LanguageMode=Transfer&PageSize=2000&OrderColumn=imilarity' > list-2000.html
$content = file_get_contents('list-2000.html');
preg_match_all('#/Home/Detail\?Id=([^&]+)&IndexCode=([^&]+)#', $content, $matches);
$url = 'https://memory.culture.tw/Home/GetWebGenieAPIData';
foreach ($matches[2] as $idx => $type) {
    $id = $matches[1][$idx];
    $target = __DIR__ . "/list/{$id}.json";
    $data = sprintf('{"API_name":"FetchDetail","q":"{\"Columns\":[\"Description\",\"Keyword_Rights_2\",\"Keyword_Des_Rights_2\",\"Keyword_Longitude\",\"Keyword_Latitude\",\"Original_Url\",\"Original_Identifier\",\"Keyword_Subject_1\",\"alias\",\"alternativeHeadline\",\"Keywords\",\"dates\",\"LastUpdateTime\",\"Creator\",\"publisher\",\"mediaType\",\"language\",\"timelines\",\"city\",\"address\",\"lng\",\"lat\",\"contributors\",\"contributor\",\"size\",\"admin\",\"award\",\"character\",\"contentLocation\",\"acquireWay\",\"acquireSource\",\"storage\",\"version\",\"isbn\",\"onlineAuthLink\",\"stuff\",\"url\",\"createOrgName\",\"citation\",\"Keyword_Rights_1\",\"AllImages\"],\"Id\":\"%d\",\"IndexCode\":\"%s\"}"}', $id, $type);

    while (true) {
        if (file_exists($target)) {
            if (!$obj = json_decode(file_get_contents($target))) {
                unlink($target);
            }
            if ($obj and $obj->Error) {
                unlink($target);
            }
        }
        if (!file_exists($target)) {
            $cmd = sprintf("curl -H 'Content-Type: application/json; charset=utf-8' -XPOST --data %s %s > %s", escapeshellarg($data), $url, $target);
            error_log($cmd);
            system($cmd);
            if (!$obj = json_decode(file_get_contents($target))) {
                error_log('error, retry');
                sleep(30);
                continue;
            }
            if ($obj->Error) {
                error_log('error, retry ' . json_encode($obj, JSON_UNESCAPED_UNICODE));
                sleep(30);
                continue;
            }
        }
        break;
    }
}
