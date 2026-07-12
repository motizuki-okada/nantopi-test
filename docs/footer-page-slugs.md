# Footer Page Slugs

フッター左側の案内リンク3本は、テーマの fallback では以下の slug を基準に参照します。

| 表示名 | slug | 想定URL |
| --- | --- | --- |
| このサイトについて | `about` | `/about/` |
| 免責事項 | `disclaimer` | `/disclaimer/` |
| お問い合わせ | `contact` | `/contact/` |

## 運用メモ

- 定義元は `inc/gakuson-content-helpers.php` の `gakuson_get_footer_page_specs()`。
- `footer-nav` が WordPress 管理画面で割り当て済みなら、そのメニューが優先表示されます。
- `footer-nav` 未設定時は、テーマ fallback が上記 slug をもとにリンクを生成します。
- 対応する固定ページが存在する場合は `get_permalink()` を優先し、未作成なら `/<slug>/` の想定URLへリンクします。
