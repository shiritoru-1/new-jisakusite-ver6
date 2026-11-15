<?php
declare(strict_types=1);

$GLOBALS['config'] = require __DIR__ . '/config.php';
date_default_timezone_set($GLOBALS['config']['timezone']);

define('BBS_DATA_DIR', __DIR__ . '/data');
define('BBS_DATA_FILE', BBS_DATA_DIR . '/posts.json');
define('BBS_COUNTER_FILE', BBS_DATA_DIR . '/counter.txt');
define('BBS_UPLOAD_DIR', __DIR__ . '/uploads');

class BbsException extends RuntimeException {}

function cfg(string $key)
{
    return $GLOBALS['config'][$key];
}

function ensure_dirs(): void
{
    if (!is_dir(BBS_DATA_DIR)) {
        mkdir(BBS_DATA_DIR, 0755, true);
    }
    if (!is_dir(BBS_UPLOAD_DIR)) {
        mkdir(BBS_UPLOAD_DIR, 0755, true);
    }
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// 本文を安全に表示する。URLはリンク、>>番号は返信リンクにする
function render_body(string $text): string
{
    $html = h($text);
    $html = preg_replace_callback(
        '~https?://[^\s<]+~u',
        fn ($m) => '<a href="' . $m[0] . '" target="_blank" rel="noopener noreferrer">' . $m[0] . '</a>',
        $html
    );
    $html = preg_replace_callback(
        '~&gt;&gt;(\d+)~',
        fn ($m) => '<a class="reply" href="#p' . $m[1] . '">&gt;&gt;' . $m[1] . '</a>',
        $html
    );
    return nl2br($html);
}

function with_lock(callable $fn)
{
    ensure_dirs();
    $fp = fopen(BBS_DATA_DIR . '/.lock', 'c');
    flock($fp, LOCK_EX);
    try {
        return $fn();
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function load_posts(): array
{
    if (!is_file(BBS_DATA_FILE)) {
        return [];
    }
    $posts = json_decode((string)file_get_contents(BBS_DATA_FILE), true);
    if (!is_array($posts)) {
        return [];
    }
    foreach ($posts as $i => $p) {
        if (!isset($p['no'])) {
            $posts[$i]['no'] = $i + 1;
        }
    }
    return array_values($posts);
}

function save_posts(array $posts): void
{
    ensure_dirs();
    $tmp = BBS_DATA_FILE . '.tmp';
    file_put_contents($tmp, json_encode(array_values($posts), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    rename($tmp, BBS_DATA_FILE);
}

// 投稿番号は削除しても振り直さない（ロック内で呼ぶこと）
function next_no(array $posts): int
{
    $n = is_file(BBS_COUNTER_FILE) ? (int)trim((string)file_get_contents(BBS_COUNTER_FILE)) : 0;
    foreach ($posts as $p) {
        $n = max($n, (int)$p['no']);
    }
    $n++;
    file_put_contents(BBS_COUNTER_FILE, (string)$n);
    return $n;
}

function image_storage_bytes(array $posts): int
{
    $total = 0;
    foreach ($posts as $p) {
        if (!empty($p['image'])) {
            $file = BBS_UPLOAD_DIR . '/' . $p['image'];
            if (is_file($file)) {
                $total += filesize($file);
            }
        }
    }
    return $total;
}

// 合計が上限を超えている間、古い投稿の画像から順に削除（投稿文は残す）
function prune_old_images(array &$posts): int
{
    $total = image_storage_bytes($posts);
    $removed = 0;
    foreach ($posts as $i => $p) {
        if ($total <= cfg('storage_limit_bytes')) {
            break;
        }
        if (empty($p['image'])) {
            continue;
        }
        $file = BBS_UPLOAD_DIR . '/' . $p['image'];
        if (is_file($file)) {
            $total -= filesize($file);
            unlink($file);
        }
        $posts[$i]['image'] = null;
        $posts[$i]['image_removed'] = true;
        $removed++;
    }
    return $removed;
}

function store_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new BbsException('画像のアップロードに失敗しました（サイズが大きすぎる可能性があります）');
    }
    if ($file['size'] > cfg('max_image_bytes')) {
        throw new BbsException('画像は20MB以下にしてください');
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new BbsException('画像ファイルではありません');
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    if (!isset($types[$info['mime']])) {
        throw new BbsException('対応していない形式です（JPEG・PNG・GIFのみ）');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $types[$info['mime']];
    ensure_dirs();
    if (!move_uploaded_file($file['tmp_name'], BBS_UPLOAD_DIR . '/' . $name)) {
        throw new BbsException('画像の保存に失敗しました');
    }
    return $name;
}

// 投稿を追加する（一般・管理者共通）
function add_post(string $name, string $body, ?array $file, bool $isAdmin, ?string $authorId = null): void
{
    $name = trim($name) === '' ? '名無しさん' : trim($name);
    $body = trim($body);

    if (mb_strlen($name) > cfg('max_name_len')) {
        throw new BbsException('名前が長すぎます');
    }
    if ($body === '') {
        throw new BbsException('本文を入力してください');
    }
    if (mb_strlen($body) > cfg('max_body_len')) {
        throw new BbsException('本文が長すぎます');
    }

    $image = null;
    if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $image = store_image($file);
    }

    with_lock(function () use ($name, $body, $image, $isAdmin, $authorId) {
        $posts = load_posts();
        $posts[] = [
            'id' => bin2hex(random_bytes(8)),
            'no' => next_no($posts),
            'name' => $name,
            'body' => $body,
            'image' => $image,
            'image_removed' => false,
            'is_admin' => $isAdmin,
            'author_id' => $authorId,
            'created' => date('Y-m-d H:i'),
        ];
        prune_old_images($posts);
        save_posts($posts);
    });
}

function delete_post_by_id(string $id): bool
{
    return with_lock(function () use ($id) {
        $posts = load_posts();
        foreach ($posts as $i => $p) {
            if ($p['id'] === $id) {
                if (!empty($p['image'])) {
                    $file = BBS_UPLOAD_DIR . '/' . $p['image'];
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                array_splice($posts, $i, 1);
                save_posts($posts);
                return true;
            }
        }
        return false;
    });
}

function format_bytes(int $bytes): string
{
    if ($bytes >= 1024 ** 3) {
        return round($bytes / 1024 ** 3, 2) . ' GB';
    }
    if ($bytes >= 1024 ** 2) {
        return round($bytes / 1024 ** 2, 1) . ' MB';
    }
    return round($bytes / 1024, 1) . ' KB';
}