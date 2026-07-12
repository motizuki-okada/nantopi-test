# 3/17メモ対応 チケット一覧

関連設計: [3-17-yuki-design.md](./3-17-yuki-design.md)

- 連携方式の正本は `WordPress custom REST endpoint + 静的側 direct fetch` とする。
- `WordPress -> Xserver 同期` は v1 チケットには含めず、将来の代替案として扱う。
- 実装の順番と完了条件はこの文書、詳細仕様は `3-17-yuki-design.md` を参照する。

## 実装順
1. TK-01 基盤整備
2. TK-02 WordPress標準出力ベース整理
3. TK-03 ヘッダー検索モーダル
4. TK-04 検索結果ページ
5. TK-05 トップカルーセル
6. TK-06 横展開
7. TK-07 下層デザイン統一
8. TK-08 REST endpoint / direct fetch
9. TK-09 総合確認と仕上げ
10. TK-10 記事いいね機能と likes 順ランキング

## TK-01 基盤整備
### 目的
- 後続実装の前提になる WordPress 側の関数整理、endpoint 用 helper、Sass 出力導線を整える。

### 対応内容
- 共通 helper の土台を追加する
  - featured 記事取得
  - カルーセル response 生成
  - タグクラウド整形
  - endpoint 用キャッシュ制御
- `package.json` の Sass watch 設定を `smacss/main/*.scss` 出力に合わせる

### Done Criteria
- 後続実装で使う helper の置き場が定まっている
- endpoint 実装で使う共通関数の責務が整理されている
- `smacss/main/*.css` の再生成導線が確認できる

### 依存
- なし

### 確認事項
- なし

## TK-02 WordPress標準出力ベース整理
### 目的
- 既存コードを大きく崩さずに横展開するため、WordPress 標準の出力 class とテンプレートタグを使う前提を整えつつ、context 別の最小分岐で差分を扱える状態にする。

### 対応内容
- 現行テンプレートで使える WordPress 標準出力を整理する
  - `body_class()`
  - `post_class()`
  - `wp_nav_menu()` の自動 class
  - `the_content()` の `wp-block-*`
  - `wp_tag_cloud()` の標準 class
- テンプレート内の `add_filter('wp_tag_cloud', ...)` 直書きをやめ、必要な差分は context 別の最小分岐で扱う
- `wp_tag_cloud()` 周りは専用 helper を積極追加する前提にせず、引数調整や markup 差分が必要な箇所だけ薄く整理する
- 記事カードは全面 partial 化せず、必要最小限の helper 化に留める
- 一覧見出し、タクソノミー表示、カード余白の共通ルールだけを整理する

### Done Criteria
- WordPress 標準出力を styling hook として使う前提が明文化・実装準備できている
- テンプレート内 function 宣言や template-local filter の増殖方針を止められている
- 共通 helper の追加を目的化せず、context 別の最小分岐で後続の横展開に必要な最小限の整理ができている

### 依存
- TK-01

### 確認事項
- サイドバー固有の順位表現は共通化対象にせず、既存のまま残す
- `wp_tag_cloud()` 周りは専用 2 helper 構成を前提にしない

## TK-03 ヘッダー検索モーダル
### 目的
- 既存のヘッダー検索 UI を、WordPress 標準検索フローに接続する。

### 対応内容
- `header.php` の検索 UI を `form role="search"` ベースへ修正する
- 可能な限り `get_search_form()` / `searchform.php` と整合する設計に寄せる
- 入力項目を追加する
  - キーワード
  - カテゴリ単一選択
  - タグ単一選択
- `js/script.js` の検索モーダル開閉処理を整理する
- `aria-expanded`、`aria-hidden`、フォーカス制御、typo を修正する

### Done Criteria
- PC/SP 両方で検索モーダルが自然に開閉する
- フォーム送信で `s`、`category_name`、`tag` が GET 送信される
- 既存のヘッダー構造を大きく変えずに WordPress 検索へ接続できている

### 依存
- TK-01

### 確認事項
- v1 のフィルタ UI は単一選択のままにする

## TK-04 検索結果ページ
### 目的
- WordPress の検索結果として、キーワード・カテゴリ・タグ条件を持つ結果ページを追加する。

### 対応内容
- `search.php` を追加する
- `pre_get_posts` でカテゴリ・タグ絞り込みを適用する
- 検索対象を `post` のみに制限する
- 結果ページに以下を実装する
  - 現在条件表示
  - 再検索導線
  - 結果一覧
  - 0件メッセージ
  - ページング
- 一覧表示は WordPress 標準出力と既存マークアップを活かして実装する

### Done Criteria
- キーワードのみ、カテゴリのみ、タグのみ、複合条件で正しい検索結果が出る
- URL を保持してリロードしても同じ結果になる
- 0件時の表示がある

### 依存
- TK-02
- TK-03

### 確認事項
- なし

## TK-05 トップカルーセル
### 目的
- `featured` タグ記事を使ったトップ訴求エリアを追加する。

### 対応内容
- `front-page.php` にカルーセルを追加する
- `featured` タグ付き記事を最大 5 件取得する
- 表示要素を実装する
  - アイキャッチ
  - タイトル
  - 説明文
  - カテゴリ
  - タグ（表示時は `featured` タグ自体を除外する）
  - CTA
- 0 件時は非表示、1 件時は静的ヒーロー、2 件以上でスライド有効にする
- 既存 jQuery で前後移動、ドット、現在位置表示を実装する

### Done Criteria
- `featured` が 0 件、1 件、複数件でそれぞれ正しく表示される
- SP でも操作できる
- 説明文は抜粋または本文トリムで表示される
- カルーセル内のタグ表示に `featured` 自体は出ない

### 依存
- TK-01

### 確認事項
- CTA 文言の最終確定

## TK-06 横展開
### 目的
- トップ基準のテーマ表現を、各テンプレートに実際に反映する。

### 対応内容
- 以下のテンプレートへテーマ表現を横展開する
  - `single.php`
  - `page.php`
  - `category.php`
  - `tag.php`
  - `search.php`
  - `sidebar.php`
- 横展開は、WordPress 標準 class と既存マークアップを活かして行う
- テンプレート固有の役割は維持し、HTML 構造の過剰な一元化はしない
- テンプレートごとの差分は以下を基準に扱う
  - `single.php`: 本文は維持、周辺 UI を寄せる
  - `page.php`: 本文は維持、周辺導線を寄せる
  - `category.php` / `tag.php` / `search.php`: 一覧見出しとカード、導線を寄せる
  - `sidebar.php`: 人気記事や広告導線のトーンだけ寄せる

### Done Criteria
- トップ以外の対象テンプレートにテーマ表現が反映されている
- 各テンプレートの役割と構造は保たれている
- 横展開のためだけの大規模なテンプレート統合が行われていない

### 依存
- TK-02
- TK-04
- TK-05

### 確認事項
- `page.php` は本文より周辺導線を優先して寄せる

## TK-07 下層デザイン統一
### 目的
- 横展開後の下層ページを、トップと同じトーンに整える。

### 対応内容
- カテゴリ、タグ、検索結果の見出し、カード、導線ルールを揃える
- 単記事のヘッダー、関連記事、ハッシュタグ、サイドバーをトップ基準に寄せる
- 必要な SCSS を以下へ反映する
  - `smacss/page/page-top.scss`
  - `smacss/page/page-post.scss`
  - `smacss/page/page-category.scss`
  - `smacss/page/page-fixed.scss`
  - 必要に応じて `smacss/module/*.scss`

### Done Criteria
- トップと下層で見出し、カード、タクソノミー表示、CTA のトーンが揃う
- 単記事本文の可読性が後退しない
- デザイン統一のためだけに class 名や HTML 構造を無理に統一していない

### 依存
- TK-06

### 確認事項
- 単記事本文スタイルは現状維持を優先する

## TK-08 REST endpoint / direct fetch
### 目的
- `nantopi-pick` 記事の軽量データを、WordPress custom REST endpoint として公開し、gakuson 側から direct fetch できるようにする。

### 対応内容
- `register_rest_route()` ベースの custom endpoint を実装する
- route 例:
  - `GET /wp-json/gakuson/v1/picks`
- `permission_callback` を公開読み取り前提で定義する
- `nantopi-pick` タグ付き記事の最新1件を返す helper を実装する
- endpoint レスポンス整形を実装する
  - `items[]`
  - `title`
  - `url`
  - `tags[]`
  - `image`
- 表示に不要な WordPress 標準フィールドは返さない
- `nantopi-pick` タグ自体は `tags[]` から除外する
- `isKk` は通常タグとして `tags[]` にそのまま含める
- `transient` などの短時間キャッシュを入れる
- `GAKUSON_PICKS_ALLOWED_ORIGINS` で allowlist 制御できるようにし、既定値は `https://gakuson.com` にする
- `GAKUSON_PICKS_CACHE_TTL` で TTL を上書きできるようにする
- browser からの cross-origin GET を前提に CORS を確認する
- v1 では push 同期、Bearer token、Xserver 側受け口 PHP は扱わない

### Done Criteria
- `nantopi-pick` 記事の状態が endpoint レスポンスへ反映される
- endpoint が想定 shape の JSON を返す
- browser からの direct fetch で CORS エラーが出ない
- gakuson 側が読み込める前提の公開 GET として成立している

### 依存
- TK-01
- TK-05

### 確認事項
- `GAKUSON_PICKS_CACHE_TTL` の本番値

## TK-09 総合確認と仕上げ
### 目的
- 個別実装をつなぎ、全体が WordPress テーマとして破綻なく動く状態に整える。

### 対応内容
- 手動テストを実施する
  - カルーセル 0/1/複数件
  - 検索条件別
  - PC/SP
  - REST endpoint 応答
  - direct fetch / CORS
- 不要な typo や軽微な不整合を整理する
- 必要なら設計書へ反映メモを追記する

### Done Criteria
- 主要導線の手動確認が一通り終わっている
- 致命的な表示崩れや PHP エラーがない
- 実装順に沿って完了確認できる

### 依存
- TK-01 から TK-08 まで

### 確認事項
- なし

## TK-10 記事いいね機能と likes 順ランキング
### 目的
- 記事末尾に共通いいね UI を追加し、カードにハート付き likes 表示を出したうえで、人気記事ランキングを likes 順へ切り替える。

### 対応内容
- `docs/3-17-yuki-tickets.md` と `AgentLog/TK-10.md` にチケット運用ログを追加する
- いいね総数を投稿メタ `gakuson_like_count` で管理する
- 1端末10回制限を cookie `gakuson_like_counts` で扱う
- `admin-ajax.php?action=gakuson_like_post` を使ったいいね加算処理を実装する
- 既存の `post_views_count` は維持しつつ、views の整数取得 helper を追加する
- ハート SVG を使った likes の共通 stats markup helper を追加する
- `single.php` の本文直後に、丸いハートボタン、likes 数、残り回数、結果メッセージを持つ共通 UI を追加する
- トップ新着、トップ人気、関連記事、category、tag、search、sidebar のカードへハート付き likes 表示を横展開する
- トップ人気記事とサイドバー人気記事の並び順を `gakuson_like_count` 降順へ切り替える
- `wp_localize_script()` と既存 jQuery を使って、like Ajax 成功時の件数更新と上限到達時の無効化を行う
- 必要な SCSS を更新し、`smacss/main/*.css` を再生成する

### Done Criteria
- `docs/3-17-yuki-tickets.md` に TK-10 が追加され、`AgentLog/TK-10.md` が作成されている
- 単記事で views が従来どおり増え、いいね UI が表示される
- 同一ブラウザで同一記事に10回までいいねでき、11回目は増えない
- トップ新着、トップ人気、関連記事、category、tag、search、sidebar の各カードにハート付き likes 数が表示される
- トップ人気記事とサイドバー人気記事が likes 順で表示される
- PC/SP で stats 表示と単記事リアクション UI が崩れない

### 依存
- TK-06
- TK-07
- TK-09

### 確認事項
- `1人10回` は `1ブラウザ / 1端末` とみなす
- cookie 削除や別ブラウザ利用で上限は実質リセットされる
- v1 はいいね解除なしの加算のみ
- views は計測と管理確認を維持し、フロント表示には出さない

## 補足
- 最小着手順は `TK-01 -> TK-02 -> TK-03 -> TK-04` です。
- フロント訴求を早く見たい場合は `TK-05` を `TK-04` と並行で進められます。
- 「整理」と「横展開」は別です。`TK-02` で WordPress 標準出力ベースへ寄せ、`TK-06` で各テンプレートへ反映します。
- 外部連携は `TK-08` で custom REST endpoint と direct fetch 前提を固めます。
