【設置手順】

1. このbbsフォルダの中身（config.php, lib.php, index.php, admin.php, .htaccess と data・uploads フォルダ）をサーバーにアップロードします。
   古いデータ（data/posts.json）を消したい場合は、削除してからアップロードしてください。

2. 管理画面のパスワードを設定します。
   次の1行だけのファイル hash.php を作り、サーバーの bbs フォルダに置いて、
   https://あなたのドメイン/bbs/hash.php をブラウザで開きます。
     <?php echo password_hash('ここに新しいパスワード', PASSWORD_DEFAULT);
   表示された「$2y$10$...」の文字列を config.php の admin_password_hash に貼り付けてアップロードし直します。
   その後、サーバー上の hash.php は必ず削除してください。

3. data フォルダと uploads フォルダの権限は 755 にします。

4. 既存のHTMLページに次のコードを貼り付けます。
   <iframe src="bbs/index.php" width="100%" height="700" frameborder="0"></iframe>

5. 確認
   ・一般投稿（連続投稿は5秒あけると投稿できる）
   ・画像付き投稿（投稿後すぐ表示される）
   ・>>番号 の返信リンクを押すと、その投稿の位置へ移動する
   ・admin.php からログインし、管理者投稿・削除・容量チェックができる

【注意】
・.htaccess の php_value で500エラーになる場合は、その2行を削除してください。
・サイトの名前や説明は config.php の site_title / site_description で変更できます。
・容量の上限は config.php の storage_limit_bytes（現在3GB）で変更できます。
