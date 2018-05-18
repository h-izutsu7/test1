<?php

$baseUrl = 'https://no1s.biz/';

$urlList = getTitle(crawling($baseUrl));

ob_start(function($buf){ return mb_convert_encoding($buf, 'SJIS', 'UTF-8'); });
foreach($urlList as $title => $link) {
    echo $link . ' ' . $title;
    echo "\n";
}


/*
 * 指定したURL内のHTMLを取得する
 */
function getLinkHtml($url)
{
    // 開始
    $ch = curl_init();

    // オプション
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);

    $html =  mb_convert_encoding(curl_exec($ch), 'UTF-8', 'ASCII,JIS,UTF-8,CP51932,SJIS-win');

    return $html;
}

/*
 * HTML内のURLを取得する
 */
function htmlSearchLink ($url)
{
    $urlList = preg_match_all("|<a href=\"(.*?)\".*?>(.*?)</a>|mis", getLinkHtml($url), $matches) ? $matches[1] : '';

    if ($urlList) {
        foreach ($urlList as $link) {
            if (strpos($link, 'https://no1s.biz/') !== false) {
                if ($link != 'https://no1s.biz//') {
                    $result[] = $link;
                }
            }
        }
    } else {
        return '';
    }

    return array_unique($result);
}

/*
 * 全てのURLを取得する
 * TODO ループのしすぎで重いので要改善
 */
function crawling ($url)
{
    $urlList = htmlSearchLink($url);

    $forLoopUrlList = [];
    for ($i=1; $i<=100; $i++) {

        $forLoopUrlList[$i] =  [];
        if ($i == 1) {
            $forLoopUrlList[$i - 1] = $urlList;
        }
        // URLリストをループ
        foreach ($forLoopUrlList[$i - 1] as $key => $url) {

            if (strpos($url, 'https://no1s.biz/') !== false) {
                if ($url != 'https://no1s.biz//') {
                    // URLリストが空でない場合
                    $searchUrlList = htmlSearchLink($url);
                    if ($searchUrlList) {

                        // 取得したURLリストと現在のURLリストの差分を取得
                        $result = array_diff($searchUrlList, $urlList);

                        // 差分がある場合
                        if ($result) {
                            $forLoopUrlList[$i] = array_unique(array_merge($forLoopUrlList[$i], $result));
                        }
                    }
                }
            }
        }

        $urlList = array_unique(array_merge($urlList, $forLoopUrlList[$i]));

        // 途中で終了
        if ($i == 2) {
            return $urlList;
        }

        // クローリング完了したとき
        if (! $forLoopUrlList[$i]) {
            return $urlList;
        }
    }
}

/*
 * ページのタイトルを取得する
 */
function getTitle ($urls)
{
    $urlList = [];
    foreach ($urls as $url) {
        $title = preg_match('@<title>([^<]++)</title>@i', getLinkHtml($url), $m) ? $m[1] : '';
        $urlList[$title] = $url;
    }

    return $urlList;
}
?>
