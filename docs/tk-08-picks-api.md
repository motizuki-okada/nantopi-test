# TK-08 Picks API

## 概要
- `nantopi-pick` タグ付き投稿の最新1件を返す公開 API。
- 利用先は `gakuson.com` 側の cross-origin `fetch()` を前提とする。
- `iframe` ではなく、軽量 JSON を返して表示側で描画する方式。

## Endpoint
- Method: `GET`
- URL: `/wp-json/gakuson/v1/picks`
- Auth: なし

## CORS
- 許可 origin の既定値:
  - `https://gakuson.com`
  - `https://gakuson.xsrv.jp`
  - `http://127.0.0.1:5500`
- `wp-config.php` で `GAKUSON_PICKS_ALLOWED_ORIGINS` を定義すると、その値を優先する
- 許可 origin のときだけ `Access-Control-Allow-Origin` を返す
- 返すヘッダ
  - `Access-Control-Allow-Origin: (request Origin と同じ値)`
  - `Access-Control-Allow-Methods: GET, OPTIONS`
  - `Vary: Origin`

## 抽出ルール
- 対象 post type: `post`
- 対象 status: `publish`
- 対象タグ: `nantopi-pick`
- 並び順: `date DESC`
- 返却件数: 1件

## Response
```json
{
  "items": [
    {
      "title": "記事タイトル",
      "tags": ["isKk", "campus-life"],
      "image": "https://example.com/uploads/pick.jpg",
      "url": "https://example.com/post-slug/"
    }
  ]
}
```

## Field Rules
- `items`
  - 常に配列
  - 対象記事が無い場合は空配列
- `title`
  - HTML を除いた投稿タイトル
- `tags`
  - 投稿タグ名の配列
  - 制御タグ `nantopi-pick` は除外
  - `isKk` は通常タグとして含める
- `image`
  - アイキャッチ画像 URL
  - 未設定時は空文字
- `url`
  - 投稿 permalink

## Empty Response
```json
{
  "items": []
}
```

## Cache
- `transient` を使用
- cache key: `gakuson_picks_payload_v1`
- TTL の既定値: `300` 秒
- `wp-config.php` で `GAKUSON_PICKS_CACHE_TTL` を定義すると、その値を優先する
- 次のタイミングで cache を無効化する
  - 投稿保存
  - タグ / カテゴリ変更
  - 投稿削除

## wp-config.php 例
```php
define('GAKUSON_PICKS_ALLOWED_ORIGINS', array(
    'https://gakuson.com',
  'https://gakuson.xsrv.jp',
  'http://127.0.0.1:5500',
));

define('GAKUSON_PICKS_CACHE_TTL', 300);
```

## 動作確認例
```bash
curl -H "Origin: https://gakuson.com" \
  https://example.com/wp-json/gakuson/v1/picks
```

```bash
curl -H "Origin: https://evil.example" \
  https://example.com/wp-json/gakuson/v1/picks -I
```

## 関連
- `docs/3-17-yuki-design.md`
- `docs/3-17-yuki-tickets.md`
- `docs/3-17-gakuson-integration-design.md`
