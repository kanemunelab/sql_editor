<?php
session_start();
?>
<html>
<head>
<meta charset="UTF-8">
<title>SQLエディタ</title>
<style type="text/css">
h1,h2 {margin:0px;}
th {background-color:#ddf;}
.dblist td {background-color:#fffff0;}
.result td {background-color:#f8fff8;}
.history-item {
  margin:5px 0; 
  padding:8px; 
  border:1px solid #ddd; 
  background-color:#efe; 
  cursor:pointer; 
  border-radius:3px;
  transition: background-color 0.2s;
}
.history-item:hover {
  background-color:#f0f8ff;
}
</style>
</head>
<body>

<script>
function updateHistoryDisplay(historyArray) {
  var historyContainer = document.getElementById('history-container');
  if (historyArray && historyArray.length > 0) {
    var html = '履歴（最近の3件）';
    html += '<div style="border:1px solid #ccc; background-color:#f9f9f9; padding:1px; margin:1px auto; width:100%; max-width:600px; font-size:70%;">';
    
    for (var i = 0; i < historyArray.length; i++) {
      var escapedSql = historyArray[i].replace(/'/g, "\\'").replace(/"/g, '\\"');
      html += '<div class="history-item" onclick="document.getElementById(\'sql_input\').value = \'' + escapedSql + '\'; return false;">';
      html += '<strong>' + (i + 1) + ':</strong> ' + historyArray[i];
      html += '</div>';
    }
    
    html += '</div>';
    historyContainer.innerHTML = html;
  } else {
    historyContainer.innerHTML = '';
  }
}

function clearHistory() {
  var historyContainer = document.getElementById('history-container');
  historyContainer.innerHTML = '';
}
</script>

<h1 align=center>SQLエディタ</h1>

<table><tr><td>

<table style="font-size:80%;">
<tr><td>・用意されたデータベースを使い、SQLを実習できます。</td></tr>
<tr><td>
・<a href="https://saccess.eplang.jp" target="_blank">sAccess</a>のデータを使用しています。</td></tr>
<tr><td>・「use コンビニ」やログインは不要です。</td></tr>
<tr><td>・「select * from 商品データ」のように検索してください。</td></tr>
<tr><td>・insert/update/deleteも他に影響なく使用できます。</td></tr>
</table>

</td><td>

<table class="dblist" border=1 style="font-size:70%;">
<tr><th>データベース</th><th>テーブル</th></tr>
<tr><td>コンビニ</td><td>商品データ、売上データ</td></tr>
<tr><td>レンタル</td><td>貸出データ、顧客データ、商品データ</td></tr>
<tr><td>生徒名簿</td><td>生徒データ、選択科目データ、クラブデータ、生徒成績データ</td></tr>
<tr><td>図書館</td><td>図書データ、著者データ、分類データ、貸出データ、生徒データ</td></tr>
</table>

</td><td>

<!-- SQL履歴表示エリア（JavaScript更新用） -->
<div id="history-container" style="text-align:left; margin-top:20px;">
  <!-- 履歴はJavaScriptで動的に更新される -->
</div>

</td></tr></table>

<hr>

<form method="POST" name="sql_form" id="sql_form" action="<?php print($_SERVER['PHP_SELF']) ?>" style="text-align:center;">
<h2>SQLを実行しよう！</h2>
<input type="radio" name="db_select" value="convini" <?php if (!isset($_POST['db_select']) || (isset($_POST['db_select']) && $_POST['db_select'] == "convini")) echo 'checked'; ?>>コンビニ
<input type="radio" name="db_select" value="rental" <?php if (isset($_POST['db_select']) && $_POST['db_select'] == "rental") echo 'checked'; ?>>レンタル
<input type="radio" name="db_select" value="student" <?php if (isset($_POST['db_select']) && $_POST['db_select'] == "student") echo 'checked'; ?>>生徒名簿
<input type="radio" name="db_select" value="library" <?php if (isset($_POST['db_select']) && $_POST['db_select'] == "library") echo 'checked'; ?>>図書館<br>
<input type="text" name="sql_input" id="sql_input" size=60 style="font-size:24px;background-color:#e8f0ff;" placeholder="SQL文を入力してください。" value="<?php echo $_POST['sql_input']; ?>">
<input type="submit" name="btn1" value="送信" style="background-color:#ccffcc;"><br><br>
<input type="submit" name="rollback_btn" value="データベース初期化" style="background-color:#ffcccc;">
</form>

<?php
// データベースファイルのディレクトリ設定
$DB_DIRECTORY = '/var/www/klab_data/kanemune/sql_editor';

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // データベース選択が変更された場合は履歴をクリア
    $db_changed = false;
    if (isset($_POST['db_select']) && isset($_SESSION['current_db']) && $_POST['db_select'] != $_SESSION['current_db']) {
        $_SESSION['sql_history'] = array();
        $db_changed = true;
    }
    $_SESSION['current_db'] = $_POST['db_select'];
    
    if (isset($_POST['rollback_btn'])) {
        rollback_transaction();
        // ロールバック後は履歴をクリア
        print('<script>clearHistory();</script>');
    } else {
        exec_sql();
    }
    
    // データベース変更時のみ履歴クリア（SQL実行なしの場合）
    if ($db_changed && !isset($_POST['btn1'])) {
        print('<script>clearHistory();</script>');
    }
}

function exec_sql(){
  global $DB_DIRECTORY;
  
  $sql_text = $_POST['sql_input'];
  $db_filename = $_POST['db_select'] . '.sqlite3';
  $original_db_path = $DB_DIRECTORY . '/' . $db_filename;
  
  // セッションキーを作成（データベース名ごとに管理）
  $session_key = 'transaction_started_' . $_POST['db_select'];
  $temp_db_key = 'temp_db_path_' . $_POST['db_select'];
  
  // 初回実行時のみ一時データベースを作成
  if (!isset($_SESSION[$session_key]) || !$_SESSION[$session_key]) {
    // 一時ファイル名を生成
    $temp_db_path = sys_get_temp_dir() . '/sql_editor_' . $_POST['db_select'] . '_' . session_id() . '.sqlite3';
    
    // 元のデータベースを一時ファイルにコピー
    if (!copy($original_db_path, $temp_db_path)) {
      print("<p align=center style='color:red;'>データベースファイルのコピーに失敗しました</p>");
      return;
    }
    
    $_SESSION[$temp_db_key] = $temp_db_path;
    $_SESSION[$session_key] = true;
    print("<p align=center style='color:blue;'>トランザクションを開始しました（一時データベースを作成）</p>");
  }
  
  // 一時データベースに接続
  $temp_db_path = $_SESSION[$temp_db_key];
  $db = new SQLite3($temp_db_path);
  
  // SQL履歴を先に保存（実行前に）
  if (!isset($_SESSION['sql_history'])) {
    $_SESSION['sql_history'] = array();
  }
  
  // 最新3件まで保持
  array_unshift($_SESSION['sql_history'], $sql_text);
  if (count($_SESSION['sql_history']) > 3) {
    array_pop($_SESSION['sql_history']);
  }
  
  $result = $db->query($sql_text);
  if (!$result) {
    print_error($db->lastErrorMsg(), $sql_text);
    // エラー時は履歴から削除
    array_shift($_SESSION['sql_history']);
    // エラー時は一時ファイルを削除してセッションをクリア
    $db->close();
    if (file_exists($temp_db_path)) {
      unlink($temp_db_path);
    }
    unset($_SESSION[$session_key]);
    unset($_SESSION[$temp_db_key]);
    return;
  }
  
  // SQL文の種類を判定
  $sql_type = strtoupper(trim(preg_replace('/\s+/', ' ', $sql_text)));
  $is_select = strpos($sql_type, 'SELECT') === 0;
  
  $cnt = 0;
  if ($is_select) {
    // SELECT文の場合のみ結果をカウント
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      $cnt++;
    }
    // 結果を再取得（表示用）
    $result = $db->query($sql_text);
  } else {
    // INSERT/UPDATE/DELETE文の場合は影響を受けた行数を取得
    $cnt = $db->changes();
  }
  
  print("<p align=center>全");
  print($cnt);
  if ($is_select) {
    print("件</p>");
  } else {
    print("件が影響を受けました</p>");
  }
  
  if ($cnt == 0) {
    if (preg_match('/％/', $sql_text)) {
      print('<br>(日本語の「％」を使っていないかを確認してください)');
    }
  }

  // 結果を表示（SELECT文の場合のみ）
  if ($is_select && $cnt > 0) {
    $print_head = TRUE;
    print('<table class="result" border=1 align="center">');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      if ($print_head) {
        print('<tr>');
        foreach (array_keys($row) as $f){
          print('<th>');
          print($f);
          print('</th>');
        }
        print('</tr>');
        $print_head = FALSE;
      }
      print('<tr>');
      foreach ($row as $f){
        print('<td>');
        print($f);
        print('</td>');
      }
      print('</tr>');
    }
    print('</table>');
  }
  
  // データベース接続を閉じる（一時ファイルは保持）
  $db->close();
  
  // トランザクション継続中であることを通知
  //print("<p align=center style='color:green;'>トランザクション継続中（変更は一時データベースに保存されています）</p>");
  //print("<p align=center><small>※ 元のデータベースファイルには影響しません</small></p>");
  
  // JavaScript経由で履歴を更新
  print('<script>');
  if (isset($_SESSION['sql_history']) && count($_SESSION['sql_history']) > 0) {
    print('updateHistoryDisplay(' . json_encode($_SESSION['sql_history'], JSON_HEX_QUOT | JSON_HEX_APOS) . ');');
  } else {
    print('updateHistoryDisplay([]);');
  }
  print('</script>');
  
  //print('<hr><small style="text-align:right;">大阪電気通信大学 兼宗研究室</small>');
  print('<hr><small style="text-align:right;">kanemune lab, Osaka Electro-Communication University.</small>');
}

function rollback_transaction(){
    $db_select = $_POST['db_select'];
    $session_key = 'transaction_started_' . $db_select;
    $temp_db_key = 'temp_db_path_' . $db_select;
    
    if (isset($_SESSION[$temp_db_key]) && isset($_SESSION[$session_key]) && $_SESSION[$session_key]) {
        $temp_db_path = $_SESSION[$temp_db_key];
        
        // 一時ファイルを削除
        if (file_exists($temp_db_path)) {
            unlink($temp_db_path);
        }
        
        unset($_SESSION[$session_key]);
        unset($_SESSION[$temp_db_key]);
        print("<p align=center style='color:red;'>データベースが初期化されました</p>");
        //print("<p align=center style='color:red;'>トランザクションをロールバックしました</p>");
        //print("<p align=center><small>一時データベースを削除し、すべての変更が破棄されました</small></p>");
    } else {
        print("<p align=center style='color:orange;'>アクティブなトランザクションがありません</p>");
    }
}

function print_error($msg, $sql){
  print($msg);

  $pos = strpos($msg, 'no such table');
  if ($pos !== false) {
    print('<br>(その名前のテーブルが存在しません)');
  }

  $pos = strpos($msg, 'no such column');
  if ($pos !== false) {
    print('<br>(その名前の列が存在しません)');
  }

  $pos = strpos($msg, 'not an error');
  if ($pos !== false) {
    print('<br>(SQL文を入力してください)');
  }

  $pos = strpos($msg, 'unrecognized token');
  if ($pos !== false) {
    print('<br>(両端のクォート(\'や")を確認してください)');
  }

  $pos = strpos($msg, 'near');
  if ($pos !== false) {
    print('<br>(表示された語や、前後の語のスペルなどを確認してください)');
  }

  if (preg_match('/　/', $sql)) {
    print('<br>(日本語の空白を使っていないかを確認してください)');
  }

  if (preg_match('/＊/', $sql)) {
    print('<br>(日本語の「＊」を使っていないかを確認してください)');
  }

  //if (preg_match('/'/|"/', $sql)) {
  //  print('<br>(日本語の「'」「"」を使っていないかを確認してください)');
  //}

  if (preg_match('/（|）/', $sql)) {
    print('<br>(日本語の「（」「）」を使っていないかを確認してください)');
  }
}

?>

</body>
</html>
