<?php

// includeされたときに関数を重複定義しないようにする。
if (!function_exists('startLazyInclude')) {
  // Lazy Includeの開始処理。
  // グローバル変数を汚染しないように処理を関数内に記述し、このファイルの末尾でそれを呼び出す。
  // 戻り値がtrueの場合はその場でincludeをreturnで終了するように用いる。
  function startLazyInclude() {
    // 呼び出し元により処理を分岐。
    $backtrace = debug_backtrace();
    if (count($backtrace) == 2) {
      // backtraceの長さが2の場合は、Lazy Includeされるファイルがブラウザから直接呼び出されていると判断する。
      // 0: このライブラリファイル
      // 1: 0をincludeしたLazy IncludeされるPHPファイル
      // Lazy Includeされる本体をバッファリングし、
      // endLazyInclude関数でそれを取得してJavaScriptを出力する。
      ob_start();
      // 本体の評価を中断しないのでfalseを返す。
      return false;
    } else if (count($backtrace) > 2) {
      // backtraceの長さが2より長い場合はLazy Includeを呼び出す側で利用されていると判断する。
      // 0: このライブラリファイル
      // 1: 0をincludeしたLazy IncludeされるPHPファイル
      // 2: 1をincludeしたLazy IncludeするPHPファイル
      // HTML断片を展開する目印となるdivと、非同期でそこにLazy Includeの内容を展開するJavaScriptを出力する。
      $document_root = $_SERVER['DOCUMENT_ROOT'];
      $includee_uri = $backtrace[1]['file'];

      // Lazy Includeの呼び出される側がDocumentRootの配下にない場合はURLで直接参照できないため例外を発生して中断。
      if (strpos($includee_uri, $document_root) !== 0) {
        throw new Exception("$includee_uri is not under document root");
      }

      // DocumentRootからのURLパスに変換し、format=jsonp&id=(一意のID)をURLパラメータとする。
      $includee_uri = substr_replace($includee_uri, '', 0, strlen($document_root));
      $includee_uri = str_replace(DIRECTORY_SEPARATOR, '/', $includee_uri); // 念のためDSを/に
      // idはLazy Includeで呼び出されるファイルのEtagのようなものが使い回しが効いてよいかもしれない
      $id = 'lazy-include-' . md5(microtime(true) + rand());
      $query_string = http_build_query(array('id' => $id));

      // DOMContentLoadedイベントでLazy Includeされる側のJavaScriptコードをJSONPするスクリプトを出力する。
      // scrollイベントなどでもよい。
    ?>
    <div class="lazy-include" id="<?php echo $id ?>"></div>
    <script>
    window.addEventListener('DOMContentLoaded', function once() {
      window.removeEventListener('DOMContentLoaded', once);
      var script = document.createElement('script');
      script.setAttribute('src', <?php echo json_encode("$includee_uri?$query_string") ?>);
      document.body.appendChild(script);
    });
    </script>
    <?php
      // Lazy Includeする側では本体のHTMLは評価しない。
      return true;
    } else {
      // backtraceが2未満の場合は想定しにくい(このPHPファイルがブラウザから直接呼ばれたケース)。
      // 特に処理をせずファイルの実行を終了する。
      return true;
    }
  }

  // Lazy Includeの終了処理
  // Lazy Includeされる側のPHPファイルは末尾で必ずこの関数を呼び出すこと。
  function endLazyInclude() {
    // backtraceの長さが1の場合は、Lazy IncludeされるPHPファイルがブラウザから直接リクエストされている。
    // 0: Lazy Includeされる側からのendLazyIncludeの呼び出し
    // そのため、この内容を指定のIDのセレクタ内に展開するJavaScriptを返す。
    // Lazy Includeを呼び出す側はJSONPとしてそのスクリプトを読み込み、画面に表示する。
    $backtrace = debug_backtrace();
    if (count($backtrace) == 1) {
      $content = ob_get_clean();
      // JSONPとして内容を展開する場合はクエリ文字列にformat=jsonp、id=任意のIDを含むこととする。
      if ($_GET['id']) {
        header('Content-Type: text/javascript');
?>
  document.getElementById(<?php echo json_encode($_GET['id']) ?>).innerHTML = <?php echo json_encode($content) ?>;
<?php
      } else {
        // クエリ文字列の要件を満たさない場合はLazy IncludeされるHTMLの断片をそのまま出力する(デバッグ用)。
        echo $content;
      }
    }
  }
}

// includeと同時にstartLazyInclude関数を実行する。
// 結果をincludeした側にそのままreturnで返す。
return startLazyInclude();
