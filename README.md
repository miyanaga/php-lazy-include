PHPのincludeをわずかな変更でJavaScriptによる非同期処理に変更する試みです。

スクロール外のHTML断片を非同期に展開することでファーストビューの描画を高速化することを目的とします。

* `index.php` includee.phpを読み込むPHPファイルです。HTMLドキュメント全体を出力します。
* `includee.php` index.phpによって読み込まれるPHPファイルです。HTMLの断片を出力します。
* `lazyInclude.php` 非同期のincludeを実現するためのライブラリです。