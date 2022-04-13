<?php

if (file_exists('config.php')) {
    include(__DIR__ . '/config.php');
}

$get_text = function($values) {
    $categories = [];
    $values['category'] = 'Taiwan Culture Memory Bank';
    if ($values['category']) {
        foreach (explode(';', $values['category']) as $c) {
            $categories[] = '[[Category:' . $c . ']]';
        }
    }
    $ret = sprintf("=={{int:filedesc}}==
{{Information
|description={{zh-tw|1=%s}}
|source=%s
|author=%s
|permission=
|other versions=
}}
{{Location|%f|%f}}

=={{int:license-header}}==
{{cc-by-3.0-tw}}

%s
", $values['description'], $values['mc_url'], $values['author'], $values['lat'], $values['lng'], implode("\n", $categories)
    );

    return $ret;
};

$lgname = getenv('LGNAME');
$lgpassword = getenv('LGPASSWORD');

$curl = curl_init();
curl_setopt($curl, CURLOPT_COOKIEFILE, '');

// get login token
curl_setopt($curl, CURLOPT_URL, 'https://commons.wikimedia.org/w/api.php?action=query&meta=tokens&type=login&format=json');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($curl);
$obj = json_decode($content);
$logintoken = $obj->query->tokens->logintoken;

// login
curl_setopt($curl, CURLOPT_URL, 'https://commons.wikimedia.org/w/api.php?action=login&format=json');
curl_Setopt($curl, CURLOPT_POSTFIELDS, sprintf("lgname=%s&lgpassword=%s&lgtoken=%s",
    urlencode($lgname),
    urlencode($lgpassword),
    urlencode($logintoken)
));
$content = curl_exec($curl);
$obj = json_decode($content);
if ($obj->login->result != 'Success') {
    print_r($obj);
    throw new Exception("login failed");
}


// get csrf token
curl_setopt($curl, CURLOPT_URL, 'https://commons.wikimedia.org/w/api.php?action=query&meta=tokens&format=json');
curl_setopt($curl, CURLOPT_POSTFIELDS, '');
$content = curl_exec($curl);
$obj = json_decode($content);
$csrf_token = $obj->query->tokens->csrftoken;

$check_online = function($name){
    $url = 'https://commons.wikimedia.org/w/api.php?action=query&titles=' . urlencode($name) . '&prop=imageinfo&iiprop=extmetadata&format=json';
    $obj = json_decode(file_get_contents($url));
    $result = get_object_vars($obj->query->pages);
    if (array_keys($result)[0] == -1) {
        return null;
    }
    return array_values($result);
};

$fp = fopen('output.csv', 'r');
$columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    $values = array_combine($columns, $rows);
    if ($ret = $check_online($values['common_id'])) {
        if (strpos($ret[0]->imageinfo[0]->extmetadata->Credit->value, 'https://memory.culture.tw/')) {
            error_log("skip {$values['common_id']}");
            continue;
        }
        throw new Exception("TODO, 檢查資料是否正確");
    }

    if (!file_exists('tmpfile') or !file_exists('tmpfile.url') or file_get_contents('tmpfile.url') != $values['img_url']) {
        file_put_contents('tmpfile', file_get_contents($values['img_url']));
        file_put_contents('tmpfile.url', $values['img_url']);
    }
    curl_setopt($curl, CURLOPT_URL, sprintf("https://commons.wikimedia.org/w/api.php?action=upload&filename=%s&format=json",
        urlencode(explode(':', $values['common_id'], 2)[1])
    ));
    $post = [
        'token' => $csrf_token,
        'file' => curl_file_create('tmpfile'),
        'text' => $get_text($values),
    ];
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    $content = curl_exec($curl);
    if (!$obj = json_decode($content)) {
        error_log("{$values['common_id']} failed");
        file_put_contents('log', json_encode([$values['common_id'], 'json error']) . "\n", FILE_APPEND);
        continue;
    }
    if ($obj->upload->Result == 'Success') {
        error_log("{$values['common_id']} ok");
        file_put_contents('log', json_encode([$values['common_id'], 'ok']) . "\n", FILE_APPEND);
        continue;
    }
    error_log("{$values['common_id']} failed");
    file_put_contents('log', json_encode([$values['common_id'], $content]) . "\n", FILE_APPEND);
}
