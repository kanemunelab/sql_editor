<html>
<head>
<meta charset="UTF-8">
<title>SQLエディタ</title>
<style type="text/css">
h1,h2 {margin:0px;}
th {background-color:#ddf;}
.dblist td {background-color:#fffff0;}
.result td {background-color:#f8fff8;}
</style>
</head>
<body>

<h1>SQLエディタ</h1>
<!-- ul>
<li><a href="https://saccess.eplang.jp" target="_blank">sAccess</a>のデータベースをSQLで検索できます。
<li>「use コンビニ」やログインは不要です。
<li>「select * from 商品データ」のように検索してください。
</ul -->

<table>
<tr><td>
<table>
<tr><td>・<a href="https://saccess.eplang.jp" target="_blank">sAccess</a>のデータベースをSQLで検索できます。</td></tr>
<tr><td>・「use コンビニ」やログインは不要です。</td></tr>
<tr><td>・「select * from 商品データ」のように検索してください。</td></tr>
</table>
</td><td>
<table class="dblist" border=1 style="font-size:70%;">
<tr><th>データベース</th><th>テーブル</th></tr>
<tr><td>コンビニ</td><td>商品データ、売上データ</td></tr>
<tr><td>レンタル</td><td>貸出データ、顧客データ、商品データ</td></tr>
<tr><td>生徒名簿</td><td>生徒データ、選択科目データ、クラブデータ、生徒成績データ</td></tr>
<tr><td>図書館</td><td>図書データ、著者データ、分類データ、貸出データ、生徒データ</td></tr>
</table>
</td></tr>
</table>

<hr>

<form method="POST" name="sql_form" id="sql_form" action="<?php print($_SERVER['PHP_SELF']) ?>" style="text-align:center;">
<h2>SQL実行</h2>
<input type="radio" name="db_select" value="convini" <?php if (!isset($_POST['db_select']) || (isset($_POST['db_select']) && $_POST['db_select'] == "convini")) echo 'checked'; ?>>コンビニ
<input type="radio" name="db_select" value="rental" <?php if (isset($_POST['db_select']) && $_POST['db_select'] == "rental") echo 'checked'; ?>>レンタル
<input type="radio" name="db_select" value="student" <?php if (isset($_POST['db_select']) && $_POST['db_select'] == "student") echo 'checked'; ?>>生徒名簿
<input type="radio" name="db_select" value="library" <?php if (isset($_POST['db_select']) && $_POST['db_select'] == "library") echo 'checked'; ?>>図書館<br>
<input type="text" name="sql_input" id="sql_input" size=60 style="font-size:24px;background-color:#e8f0ff;" placeholder="SQL文を入力してください。" value="<?php echo $_POST['sql_input']; ?>"><br><br>
<input type="submit" name="btn1" value="送信">
</form>

<?php

if($_SERVER["REQUEST_METHOD"] == "POST"){
    exec_sql();
}

function exec_sql(){
  $sql_text = $_POST['sql_input'];
  $db = $_POST['db_select'] . '.sqlite3';

  $db = new SQLite3($db);
  $result = $db->query($sql_text);
  if (!$result) {
    print_error($db->lastErrorMsg(), $sql_text);
    return;
  }
  $cnt = 0;
  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $cnt++;
  }
  print("<p align=center>全");
  print($cnt);
  print("件</p>");
  if ($cnt == 0) {
    if (preg_match('/％/', $sql_text)) {
      print('<br>(日本語の「％」を使っていないかを確認してください)');
    }
  }

  $print_head = TRUE;
  print('<table class="result" border=1 align="center"');
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
  print('<hr><small style="text-align:right;">kanemune lab, Osaka Electro-Communication University.</small>');
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

  if (preg_match('/’|”/', $sql)) {
    print('<br>(日本語の「’」「”」を使っていないかを確認してください)');
  }

  if (preg_match('/（|）/', $sql)) {
    print('<br>(日本語の「（」「）」を使っていないかを確認してください)');
  }
}

?>
</body>
</html>
