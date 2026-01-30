
# 📚 My Book Collection - Backend (BookManage_Laravel)

## 概要
Laravel + Reactで作成した書籍管理アプリです。 
バックエンド（Laravel + MySQL）が書籍データを管理し、ReactフロントエンドにJSONを返します。

## 特徴
- 書籍CRUD APIの提供
- 楽天Books API連携で書籍情報を自動取得
- 手入力データとAPIデータのマージで精度向上
- 画像がない書籍はデフォルト画像を表示
- フロントで複数候補をカードUIで選択可能
- MySQLによる安定したデータ管理と検索

## 使用技術
- PHP: 8.2.12
- Laravel Framework: 10.10.2
- GuzzleHTTP: 7.9.0
- Laravel Sanctum: 3.3
- データベース: MariaDB 10.4.32 (MySQL互換)

### 工夫した点
- 楽天Books APIのデータを手入力で補完できる仕組みを実装
- 画像がない書籍はデフォルト画像を表示しUXを改善
- 複数候補選択UIで操作性向上

### 難しかった点 / 改善したい点
- 楽天APIとDBのフィールド名・形式の違いのマッピングが複雑
- バックエンドとフロントエンドでデータバリデーションを統一し堅牢化予定
- 画像管理やキャッシュ改善でパフォーマンス向上の余地あり