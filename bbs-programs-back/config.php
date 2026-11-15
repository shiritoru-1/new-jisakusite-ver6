<?php
declare(strict_types=1);

return [
    // 管理画面のパスワード（ハッシュ値）。README の手順で生成して貼り付けてください。
    'admin_password_hash' => '$2y$10$dyqbHKRkfY0J9sCNB8EXse3m7sO5YvwOZtM7yoDq2cql46ipkFJiy',

    // ID生成に使う秘密の文字列（長いランダムな英数字に変更）
    'id_secret' => 'lkjugfsdalfjbaldkfahiludsfvnajsauhvrinsdhglauihbnvfav8l4ywt89osgfvhirlwcy8uh9p38tp0ahw38',

    'site_title' => 'BBS',
    'site_description' => 'なんでも書き込みOK。個人情報は書かないでね。',

    // 画像の合計がこの容量を超えたら、古い画像から順に削除（3GB）
    'storage_limit_bytes' => 3 * 1024 * 1024 * 1024,

    // 1枚あたりの画像サイズ上限（20MB）
    'max_image_bytes' => 20 * 1024 * 1024,

    // 一般の連続投稿の間隔（秒）
    'post_interval_sec' => 5,

    'max_name_len' => 20,
    'max_body_len' => 1000,

    // 通常表示の件数（超えた分は「すべて見る」で表示）
    'posts_per_page' => 30,

    'timezone' => 'Asia/Tokyo',
];