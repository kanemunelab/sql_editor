# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## プロジェクト概要

このプロジェクトは、日本語対応のWebベースSQLエディタです。学習用途で設計されており、4つのサンプルデータベース（コンビニ、レンタル、生徒名簿、図書館）を使用してSQL文の実行と学習ができます。

## 主要ファイル構造

- `index.php` - メインのSQLエディタ（Webインターフェース）
- `convini.sqlite3`, `rental.sqlite3`, `student.sqlite3`, `library.sqlite3` - 4つのメインデータベース
- `data/*/mk*.sh` - 各データベースの再構築スクリプト

## データベース再構築コマンド

各データベースを再構築する場合：

```bash
# コンビニデータベース
cd data/convini && ./mkconvini.sh

# レンタルデータベース
cd data/rental && ./mkrental.sh

# 生徒名簿データベース
cd data/student && ./mkstudent.sh

# 図書館データベース
cd data/library && ./mklibrary.sh
```

## データベース構造確認

```bash
# テーブル一覧確認
sqlite3 [database_name].sqlite3 ".tables"

# テーブル構造確認
sqlite3 [database_name].sqlite3 ".schema [table_name]"

# サンプルデータ確認
sqlite3 [database_name].sqlite3 "select * from [table_name] limit 5;"
```

## アーキテクチャ

### トランザクション管理システム
- 元のデータベースファイルは変更されず、セッションごとに一時データベースを作成
- `exec_sql()` 関数内でセッション管理とトランザクション制御を実装
- ロールバック機能により一時データベースを削除して変更を破棄

### エラーハンドリング
- 日本語特有の入力エラー（全角文字、全角記号）を検出 (`index.php:227-241`)
- SQLエラーメッセージを日本語で補足説明 (`index.php:202-225`)

### データベース選択システム
- ラジオボタンによる4つのデータベース選択
- セッション単位でのデータベース状態管理

## 重要な注意点

- データベースファイルパスは `$DB_DIRECTORY` 変数で管理（本番環境では要変更）
- 日本語テーブル名・カラム名を使用
- セッション管理によるマルチユーザー対応

## サンプルデータベース

1. **コンビニ**: 商品データ、売上データ
2. **レンタル**: 貸出データ、顧客データ、商品データ
3. **生徒名簿**: 生徒データ、選択科目データ、クラブデータ、生徒成績データ
4. **図書館**: 図書データ、著者データ、分類データ、貸出データ、生徒データ