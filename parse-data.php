<?php

$output = fopen('output.csv', 'w');
$columns = [ 'mc_id', 'mc_url', 'common_id', 'caption', 'description', 'date', 'author', 'category', 'lat', 'lng', 'img_url' ];
fputcsv($output, $columns);
foreach (glob(__DIR__ . "/list/*") as $f) {
    $obj = json_decode(file_get_contents($f));
    $f = basename($f);
    error_log($f);
    $title = $obj->Title;

    $mc_url = "https://memory.culture.tw/Home/Detail?Id={$obj->Id}&IndexCode={$obj->IndexCode}";

    if ($obj->IndexCode == 'Culture_Media') { // 跳過影片
        continue;
    }

    // 如果有 AllImages 就用 AllImages，不然用 ImageUrl，只選一個（因為 ImageUrl 可能是縮圖）
    $images = explode(' / ', $obj->ImageUrl);
    if ($obj->CustomColumns->AllImages) {
        $images = explode(' / ', $obj->CustomColumns->AllImages);
    }
    $images = array_unique($images);

    $names = [];
    foreach ($images as $imgurl) {
        $name = basename($imgurl);
        if (count($images) == 1) {
            $terms = explode('.', $name);

            $target = "{$title}" . '.' . $terms[count($terms) - 1];
        } else {
            $target = "{$title}-{$name}";
        }
        if (in_array($target, $names)) {
            throw new Exception('dupplicate' . $target);
        }
        $names[] = $target;
        error_log("{$imgurl} => {$f} {$target}");
        //$columns = [ 'mc_id', 'mc_url', 'common_id', 'caption', 'description', 'date', 'author', 'category', 'lat', 'lng', 'img_url' ];
        $values = [];
        $values['mc_id'] = $obj->Id;
        $values['mc_url'] = $mc_url;
        $values['common_id'] = 'File:' . $target;
        $values['caption'] = $obj->Title;
        $values['description'] = trim($obj->CustomColumns->Description);
        if ('' == trim($obj->CustomColumns->dates) ){ 
            $values['date'] = '';
        } else if (!preg_match('#\d#u', $obj->CustomColumns->dates)) {
            $values['date'] = '';
        } else if (preg_match('#(出版日期|創作時間|拍攝時間|發表日期|採訪時間|入藏日期|入庫日期) / (\d+)(/\d+)?(/\d+)?#u', $obj->CustomColumns->dates, $matches)) {
            $values['date'] = $matches[2];
            if ($matches[3]) {
                $values['date'] .= '-' . ltrim($matches[3], '/');
            }
            if ($matches[4]) {
                $values['date'] .= '-' . ltrim($matches[4], '/');
            }
            
        } else {
            throw new Exception($obj->CustomColumns->dates);
        }
        $values['img_url'] = $imgurl;
        $values['lng'] = $obj->CustomColumns->Keyword_Longitude;
        $values['lat'] = $obj->CustomColumns->Keyword_Latitude;
        $values['category'] = [];
        foreach (explode(' / ', $obj->CustomColumns->Keyword_Subject_1) as $c) {
            switch ($c) {
            case '空間、地域與遷徙':
                $values['category'][] = 'Geography;Migration';
                break;
                case '民俗與宗教';
                $values['category'][] = 'Folklore;Religion';
                break;
            case '藝術與人文':
                $values['category'][] = 'Art;Humanities';
                break;
            case '社會與政治':
                $values['category'][] = 'Politics;Society';
                break;
            case '生物、生態與環境':
                $values['category'][] = 'Biology;Ecology;Environment';
                break;
            case '產業與經濟':
                $values['category'][] = 'Economics;Industries';
                break;
            case '族群與語言':
                $values['category'][] = 'Ethnic groups;Language';
                break;
            case '人物與團體':
                $values['category'][] = 'People;Group';
                break;
            }
        }
        $values['category'] = implode(':', $values['category']);
        if ($obj->IndexCode == 'Culture_Organization' || $obj->IndexCode == 'Culture_People') {
            $values['author'] = sprintf("創作者：%s。貢獻者：%s", $obj->CustomColumns->Creator, $obj->CustomColumns->createOrgName);
        } elseif ($obj->IndexCode == 'Culture_Place') {
            $values['author'] = sprintf("創作者：%s。貢獻者：%s", $obj->CustomColumns->writer, $obj->CustomColumns->createOrgName);
        } elseif ($obj->IndexCode == 'Culture_Object') {
            $values['author'] = sprintf("創作者：%s。貢獻者：%s", $obj->CustomColumns->Creator, $obj->CustomColumns->Creator);
        } elseif ($obj->IndexCode == 'Culture_Event') {
            $values['author'] = sprintf("創作者：%s。貢獻者：%s", $obj->CustomColumns->contributor, $obj->CustomColumns->contributors);
        } else {
            throw new Exception($mc_url . ' ' . $obj->IndexCode);
        }
        fputcsv($output, array_map(function($k) use ($values) { return $values[$k]; }, $columns));
    }
}
