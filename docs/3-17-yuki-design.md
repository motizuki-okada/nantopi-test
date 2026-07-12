# 3/17メモ対応 実装設計

## 前提環境
- 連携元は `nantopi-theme` を適用した WordPress サイト
- `nantopi` 側のホスティングはロリポップ
- `nantopi` 側が記事データの正本であり、`featured` 記事を保持し custom REST endpoint を公開する
- 連携先は `gakuson` 側サイト
- `gakuson` 側のホスティングは Xserver
- `gakuson` 側は静的 HTML / CSS / JS を前提とし、WordPress endpoint を direct fetch して描画する
- この文書は `gakusonTheme-2-24` 側のテーマ実装と連携前提を整理するためのもの

## この文書の役割
- `docs/3-17-yuki-tickets.md` は実装順、目的、Done Criteria を管理する文書です。
- この文書は、各チケットを実装するときに参照する詳細仕様です。
- 連携方式を採用した理由や比較経緯は `docs/3-17-gakuson-integration-design.md` を参照します。

## v1 決定事項
- カルーセル対象は通常タグ `featured`
- カルーセル取得件数は最大 5 件
- 連携方式は `WordPress custom REST endpoint + gakuson 側 direct fetch`
- 検索入口はヘッダーモーダル
- 検索パラメータは `s`、`category_name`、`tag`
- 検索対象は投稿 `post` のみ
- v1 のカテゴリ / タグ選択は単一選択
- 既存 jQuery、既存 SCSS 構成、WordPress 標準機能を優先し、新規ライブラリは追加しない

## 共通実装ルール
- classic theme の既存テンプレート構造は維持する
- テンプレート階層に沿って `front-page.php`、`single.php`、`category.php`、`tag.php`、`page.php`、`search.php` を使い分ける
- テンプレート内で新しい PHP 関数、`add_action()`、`add_filter()` を定義しない
- 共通化は helper、`searchform.php`、小さな partial の範囲に留める
- HTML 構造の大規模統一や、テンプレートの過剰な一元化はしない
- WordPress 標準出力を styling hook として優先利用する
  - `body_class()`
  - `post_class()`
  - `get_search_form()` / `searchform.php`
  - `wp_nav_menu()` の自動 class
  - `the_content()` の `wp-block-*`
  - `wp_tag_cloud()` の標準 class
- 出力は原則 escape する
- SCSS は `smacss/` の適切なレイヤーを編集し、`smacss/main/*.css` を再生成する

## チケット別詳細
### TK-01 基盤整備
- 先に helper の責務だけ整理する
  - featured 記事取得
  - カルーセル response 生成
  - タグクラウド整形
  - endpoint 用キャッシュ制御
- Sass watch は `smacss/main/*.scss` 出力に合わせる

### TK-02 WordPress標準出力ベース整理
- テンプレート内 `add_filter('wp_tag_cloud', ...)` はやめて共通関数へ移す
- 記事カードは全面 partial 化せず、必要ならデータ取得や繰り返し部分だけ helper 化する
- 見出し、カード余白、タクソノミー表示、区切り線はトップ基準で揃える
- `wp_nav_menu()` の HTML は大きく変えず、自動 class を活かす

### TK-03 / TK-04 検索
- ヘッダー検索は `form role="search"` ベースに寄せる
- 実装は可能な限り `get_search_form()` / `searchform.php` と整合させる
- 送信パラメータは以下
  - `s`
  - `category_name`
  - `tag`
- 検索結果は `search.php` で描画する
- カテゴリ / タグ絞り込みは `pre_get_posts` で適用する
- 検索結果ページに必要な要素
  - 現在条件表示
  - 再検索フォーム
  - 結果一覧
  - 0件メッセージ
  - ページング
- 既存のヘッダー UI は維持しつつ、中身だけ WordPress 標準検索へ接続する
- モーダルでは以下を正しく扱う
  - `aria-expanded`
  - `aria-hidden`
  - フォーカス制御

### TK-05 トップカルーセル
- `front-page.php` に追加する
- 表示条件
  - 0 件: 非表示
  - 1 件: 静的ヒーロー表示
  - 2 件以上: スライド有効
- 表示要素
  - アイキャッチ
  - タイトル
  - 短い説明文
  - カテゴリ
  - タグ（表示時は `featured` タグ自体を除外する）
  - CTA
- 説明文ルール
  - 手動抜粋があればそれを使う
  - なければ本文先頭をトリムして使う
- 操作 UI は既存 jQuery で実装する
  - 前後移動
  - ドット
  - 現在位置表示

### TK-06 / TK-07 横展開・下層デザイン統一
- 目的は「テンプレート統合」ではなく「トップ基準の見た目と導線の横展開」
- 対象テンプレート
  - `single.php`
  - `page.php`
  - `category.php`
  - `tag.php`
  - `search.php`
  - `sidebar.php`
- テンプレートごとの扱い
  - `single.php`
    - 本文可読性は維持
    - ヘッダー、関連記事、ハッシュタグ、サイドバーをトップ基準へ寄せる
  - `page.php`
    - 本文構造は維持
    - 関連記事、ハッシュタグ、周辺導線をトップ基準へ寄せる
  - `category.php` / `tag.php` / `search.php`
    - 一覧見出し、記事カード、導線をトップ基準へ寄せる
    - 各テンプレートは独立したまま維持する
  - `sidebar.php`
    - 人気記事や広告導線のトーンを揃える
    - 順位表現などの固有要素は残す
- デザイン統一の定義
  - 同じ class 名に無理に揃えることではない
  - 同じ見た目のルールを各テンプレートへ適用すること

### TK-08 REST endpoint / direct fetch
- `register_rest_route()` ベースの custom REST endpoint を実装する
- route
  - `GET /wp-json/gakuson/v1/picks`
- endpoint は public GET 前提とする
- `permission_callback` は公開読み取り前提で定義する
- レスポンスは外部確認用の最小 JSON に絞る
- 抽出タグは専用 slug `nantopi-pick` を使う
- `isKk` は通常タグ運用とし、JSON には `tags[]` の一要素としてそのまま含める

#### Response shape
- `items[]`

#### item fields
- `title`
- `url`
- `tags[]`
- `image`

#### 実装条件
- `nantopi-pick` 対象の抽出ルールは 1 箇所に閉じ込める
- 表示に不要な WordPress 標準フィールドは返さない
- `transient` などで短時間キャッシュできるようにする
- browser からの cross-origin GET を前提に CORS を確認する
- CORS は `GAKUSON_PICKS_ALLOWED_ORIGINS` を優先して allowlist 制御し、既定値は `https://gakuson.com` にする
- cache TTL は `GAKUSON_PICKS_CACHE_TTL` で上書き可能にする
- gakuson 側はページ本体描画後に非同期ロードする
- loading と fallback message を用意する

## 追加インターフェース
- 追加テンプレート
  - `search.php`
- 追加 GET パラメータ
  - `s`
  - `category_name`
  - `tag`
- 追加 REST endpoint
  - `GET /wp-json/gakuson/v1/picks`

## テスト観点
- `featured` が 0 件、1 件、複数件で表示が崩れない
- キーワードのみ、カテゴリのみ、タグのみ、複合条件、0件、ページングありで検索結果が正しい
- PC/SP で検索モーダルの開閉、送信、フォーカス移動、ARIA 状態が正しい
- 単記事、固定ページ、カテゴリ、タグ、検索結果で見出し・カード・配色がトップ基準に揃う
- REST endpoint が想定 shape の JSON を返す
- browser からの direct fetch で CORS エラーが出ない
- endpoint 失敗時に gakuson 側で fallback 表示へ切り替えられる

## 要相談事項
- `GAKUSON_PICKS_CACHE_TTL` の本番値
- サムネイル未設定時の fallback
- CTA 文言の最終確定
