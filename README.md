
# 📚 My Book Collection　- Backend（Bookmanage_Laravel）

## 概要
Laravel + Reactで作成した書籍管理アプリです。 
Laravel + MySQL（バックエンド）で書籍データを管理し、ReactフロントエンドにJSONを返します。

## 特徴
- 書籍CRUD APIを提供
- 楽天Books APIとの連携で書籍情報を自動取得
- APIデータと手入力データをマージして登録精度を向上
- 画像なし書籍にはデフォルト画像を自動表示
- フロント側で候補が複数ある場合にカードUIで選択可能
- MySQLで安定したデータ管理と検索を実現

## 使用技術
- PHP: 8.1.12
- LaravelFramework: 10.10.2
- GuzzleHTTP: 7.9.0
- LaravelSanctum: 3.3
- LaravelTinker: 2.8

### 工夫した点
- 楽天BooksAPIのデータを手入力で補完できる仕組みを実装
- 画像が無い書籍にはデフォルト画像を自動表示・UX改善
- 複数候補選択UIでUXを向上

### 難しかった点 / 改善したい点
- 楽天APIとDBのフィールド名・形式の違いをマッピングするのが複雑
- 次回はバックエンドとフロントエンドでデータバリデーションを統一してして堅牢化
- 画像管理やキャッシュ周りの改善でパフォーマンス向上の余地あり