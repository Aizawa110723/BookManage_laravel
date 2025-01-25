<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Models\Book;
use Illuminate\Support\Facades\Http;

class BookSeeder extends Seeder
{
    public function run()
    {
        // 日本語ジャンルの配列
        $genres = [
            "文学・評論",
            "自伝・伝記",
            "ノンフィクション",
            "ファンタジー・SF",
            "ミステリー・推理",
            "教育・学習",
            "ビジネス・経済",
            "歴史・社会",
            "芸能・エンターテインメント",
            "アート・建築・デザイン",
            "人文・思想・宗教",
            "科学・テクノロジー・プログラミング",
            "健康・ライフスタイル",
            "旅行・ガイド",
            "料理・グルメ"
        ];

        // 検索する書籍のタイトルと著者
        $queries = [
            '深夜特急 沢木耕太郎',
            '料理の鉄人 高田明',
            'コンビニ人間 村田沙耶香',
            'アンドロイドは電気羊の夢を見るか？ フィリップ・K・ディック',
            '嫌われる勇気 岸見一郎, 古賀史健',
            '時をかける少女 筒井康隆',
            'ハリー・ポッターと賢者の石 J.K.ローリング',
            'グレート・ギャツビー F・スコット・フィッツジェラルド',
            '告白 湊かなえ',
            'アインシュタインの相対性理論 小林登',
            'ブラックホールの天才科学者 スティーヴン・ホーキング',
            '地球の未来を変えるための科学 藤井聡',
            '世界を変えた20世紀の科学者たち',
            '生命とは何か？ 岡田靖',
            '未来の食べ物 村上陽一郎'
        ];

        // 書籍ごとにジャンルを設定するための簡易なマッピング（この部分をカスタマイズ）
        $genreMapping = [
            '深夜特急' => '旅行・ガイド',
            '料理の鉄人' => '料理・グルメ',
            'コンビニ人間' => '文学・評論',
            'アンドロイドは電気羊の夢を見るか？' => 'ファンタジー・SF',
            '嫌われる勇気' => '自己啓発',
            '時をかける少女' => '文学・評論',
            'ハリー・ポッターと賢者の石' => 'ファンタジー・SF',
            'グレート・ギャツビー' => '文学・評論',
            '告白' => 'ミステリー・推理',
            'アインシュタインの相対性理論' => '科学・テクノロジー・プログラミング',
            'ブラックホールの天才科学者' => '科学・テクノロジー・プログラミング',
            '地球の未来を変えるための科学' => '科学・テクノロジー・プログラミング',
            '世界を変えた20世紀の科学者たち' => '科学・テクノロジー・プログラミング',
            '生命とは何か？' => '科学・テクノロジー・プログラミング',
            '未来の食べ物' => 'ビジネス・経済'
        ];

        foreach ($queries as $query) {
            Log::info("Requesting Google Books API for query: $query");

            // GoogleBooks APIのURLにクエリをセット
            $url = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($query);

            // APIにリクエストを送信
            $response = Http::get($url);

            // APIレスポンスをチェック
            if ($response->successful()) {
                $booksData = $response->json();
                Log::info("Google Books API Response", $booksData);

                // 取得した書籍データの件数を確認
                Log::info("Fetched books count for query '$query': " . count($booksData['items'] ?? []));

                // 最大5件取得
                $items = array_slice($booksData['items'] ?? [], 0, 5);

                foreach ($items as $item) {
                    // 書籍の情報を取得
                    $title = $item['volumeInfo']['title'] ?? 'No Title';
                    $authors = implode(', ', $item['volumeInfo']['authors'] ?? []);
                    $publisher = $item['volumeInfo']['publisher'] ?? 'No Publisher';
                    $description = $item['volumeInfo']['description'] ?? 'No Description';
                    $imageUrl = $item['volumeInfo']['imageLinks']['thumbnail'] ?? null;
                    $imagePath = null;

                    // 書籍のタイトルから著者名を除外
                    $titleWithoutAuthor = preg_replace('/\s?[^\x01-\x7E]+$/u', '', $title); // 著者名を取り除く正規表現

                    // 画像URLがあれば画像をダウンロードして保存
                    if ($imageUrl) {
                        $imagePath = app('App\Services\ImageService')->downloadAndStoreImage($imageUrl);
                    }

                    // 出版日処理
                    $publishedDate = $item['volumeInfo']['publishedDate'] ?? null;
                    $year = null;
                    $formattedPublishedDate = null;
                    if ($publishedDate && strlen($publishedDate) === 4) {
                        $year = $publishedDate; // 年のみの場合
                    } elseif ($publishedDate) {
                        $formattedPublishedDate = \Carbon\Carbon::parse($publishedDate)->format('Y-m-d');
                    }

                    // ジャンルをマッピング
                    $genre = $genreMapping[$titleWithoutAuthor] ?? '未指定'; // 修正したタイトルでマッピング

                    // ここで重複を防ぐために、既に同じタイトルと著者の組み合わせが存在する場合には新規挿入を行わない
                    Book::firstOrCreate(
                        ['title' => $title, 'authors' => $authors], // 重複条件
                        [
                            'title' => $title,
                            'authors' => $authors,
                            'publisher' => $publisher,
                            'description' => $description,
                            'year' => $year,
                            'published_date' => $formattedPublishedDate ?? null,
                            'google_books_url' => $item['volumeInfo']['infoLink'] ?? 'No GoogleBooks URL',
                            'image_path' => $imagePath ?? 'No Image',
                            'image_url' => $imageUrl ?? 'No Image URL',
                            'genre' => $genre // マッピングされたジャンルを保存
                        ]
                    );
                }
            } else {
                Log::error("Failed to fetch books for query: $query");
            }
        }
    }
}
