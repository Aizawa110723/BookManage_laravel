
# 📚 My Book Collection　- Backend（Bookmanage_Laravel）

## 概要
Laravel + Reactで作成した書籍管理アプリです。 
Laravel + MySQL（バックエンド）

- 書籍CRUD API
- 楽天Books APIとの連携
- ReactフロントエンドへのJSONレスポンス提供
- MySQLによるデータ管理

## 使用技術
- PHP: 8.1.12
- LaravelFramework: 10.10.2
- GuzzleHTTP: 7.9.0
- LaravelSanctum: 3.3
- LaravelTinker: 2.8
- Symfony/Translation: 6.4.13
- Symfony/VarDumper: 6.4.18
- vlucas/phpdotenv: 5.6.1

### 工夫した点
- 楽天BooksAPIのデータを手入力で補完できる仕組み・登録精度を向上
- 画像が無い書籍にはデフォルト画像を自動表示・UX改善
- フロント側で候補が複数ある場合にカードUIで選択できる

### 難しかった点 / 改善したい点
- 楽天APIとDBのフィールド名・形式の違いをマッピングするのがやや複雑
- 次回はバックエンドとフロントエンドでデータバリデーションを統一して、さらに堅牢にしたい
- 画像の管理やキャッシュ周りを改善するとパフォーマンスが向上しそう