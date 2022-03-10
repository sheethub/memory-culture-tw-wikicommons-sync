<?php

// curl 'https://memory.culture.tw/Home/Result?Filter_Keyword_Rights_2=CC%20BY&Filter_Keyword_Des_Rights_2=CC0&SearchMode=Precise&LanguageMode=Transfer&PageSize=2000&OrderColumn=imilarity' > list-2000.html
$content = file_get_contents('list-2000.html');
preg_match_all('#/Home/Detail\?Id=([^&]+)&IndexCode=([^&]+)#', $content, $matches);
$url = 'https://memory.culture.tw/Home/GetWebGenieAPIData';
$type_columns = [
    'Culture_Place' => '{"Columns":["Description","Keyword_Rights_2","Keyword_Des_Rights_2","Keyword_Longitude","Keyword_Latitude","Original_Url","Original_Identifier","Keyword_Subject_1","alias","Keywords","historicalDates","LastUpdateTime","timelines","place","address","longitude","latitude","phone","Event","relationPeople","open","free","openTime","openTimeDescription","capacity","traffic","subjection","tag","situation","historicalDataBrief","historicalData","reference","writer","createOrgName","source","Keyword_Rights_1","AllImages"],"Id":"%d","IndexCode":"Culture_Place"}',
    'Culture_Object' => '{"Columns":["Description","Keyword_Rights_2","Keyword_Des_Rights_2","Keyword_Longitude","Keyword_Latitude","Original_Url","Original_Identifier","Keyword_Subject_1","alias","alternativeHeadline","Keywords","dates","LastUpdateTime","Creator","publisher","mediaType","language","timelines","city","address","lng","lat","contributors","contributor","size","admin","award","character","contentLocation","acquireWay","acquireSource","storage","version","isbn","onlineAuthLink","stuff","url","createOrgName","citation","Keyword_Rights_1","AllImages"],"Id":"%d","IndexCode":"Culture_Object"}',
    'Culture_Event' => '{"Columns":["Description","Keyword_Rights_2","Keyword_Des_Rights_2","Keyword_Longitude","Keyword_Latitude","Original_Url","Original_Identifier","Keyword_Subject_1","alias","Keywords","startDate","endDate","LastUpdateTime","timelines","place","city","address","lng","lat","sameAs","organizer","contributors","contributor","eventUrl","performers","lengthOfTime","createOrgName","dataSource","workFeatured","Keyword_Rights_1","AllImages"],"Id":"%d","IndexCode":"Culture_Event"}',
    'Culture_People' => '{"Columns":["Description","Keyword_Rights_2","Keyword_Des_Rights_2","Keyword_Longitude","Keyword_Latitude","Original_Url","Original_Identifier","Keyword_Subject_1","alias","Keywords","bornStr","deathStr","LastUpdateTime","Creator","language","dataSource","timelines","bornPlace","bornAddress","bornCity","deathPlace","deathAddress","deathCity","sameAs","onlineAuthLink","job","organization","expertise","worksFor","gender","citizenship","bornLng","bornLat","deathLng","deathLat","createOrgName","url","Keyword_Rights_1","AllImages"],"Id":"%d","IndexCode":"Culture_People"}',
    'Culture_Organization' => '{"Columns":["Description","Keyword_Rights_2","Keyword_Des_Rights_2","Keyword_Longitude","Keyword_Latitude","Original_Url","Original_Identifier","Keyword_Subject_1","alternateName","Keywords","foundingDate","dissolutionDate","LastUpdateTime","Creator","timelines","foundingLoactionName","foundingLocationAddress","registerLocationAddress","contactLocationAddress","sameAs","foundingLocationLongitude","foundingLocationLatitude","registerLocationLongitude","registerLocationLatitude","contactLocationLongitude","contactLocationLatitude","taxId","founder","alumni","contactPhone","contactEmail","createOrgName","dataSource","Keyword_Rights_1","AllImages"],"Id":"%d","IndexCode":"Culture_Organization"}',
];

foreach ($matches[2] as $idx => $type) {
    $id = $matches[1][$idx];
    $target = __DIR__ . "/list/{$id}.json";
    if ($type == 'Culture_Media') {
        continue;
    }
    if (!array_key_exists($type, $type_columns)) {
        throw new Exception("{$type} {$id}");
    }

    $data = json_encode([
        'API_name' => 'FetchDetail',
        'q' => (sprintf($type_columns[$type], $id)),
    ]);

    while (true) {
        if (file_exists($target)) {
            if (!$obj = json_decode(file_get_contents($target))) {
                unlink($target);
            }
        }
        if (!file_exists($target)) {
            $cmd = sprintf("curl -H 'Content-Type: application/json; charset=utf-8' -XPOST --data %s %s", escapeshellarg($data), $url);
            error_log($cmd);
            $content = `$cmd`;
            system($cmd);
            if (!$obj = json_decode($content)) {
                error_log('error, retry');
                sleep(30);
                continue;
            }
            if ($obj->Error) {
                error_log('error, retry ' . json_encode($obj, JSON_UNESCAPED_UNICODE));
                sleep(30);
                continue;
            }
            file_put_contents($target, $obj->JsonData);
        }
        break;
    }
}
