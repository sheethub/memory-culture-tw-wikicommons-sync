# memory-culture-tw-wikicommons-sync

將 [文化記憶庫](https://memory.culture.tw/) 內 CC 授權的素材上傳至 [Wikimedia Commons](https://commons.wikimedia.org/wiki/Main_Page) 的程式

程式說明
========
- crawl-mc-list.php
  - 將前 2000 筆搜尋結果的網頁存至 list-2000.html ，並抓取資料存至 list/{id}.json
- parse-data.php
  - 將 list/{id}.json 內的資料轉換成 Wikimedia Commons 需要的格式，存至 output.csv

程式碼授權
=========
BSD License
