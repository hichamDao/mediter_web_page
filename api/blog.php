<?php
require __DIR__ . '/bootstrap.php';
$m = $_SERVER['REQUEST_METHOD']; $id = (int)($_GET['id'] ?? 0);

function slugify(string $t): string {
    $t = strtr(mb_strtolower($t), ['à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','œ'=>'oe','æ'=>'ae']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-') ?: 'article';
}
function unique_slug(string $base, int $exceptId = 0): string {
    $slug = $base; $i = 1;
    while (true) {
        $s = db()->prepare('SELECT 1 FROM blog_posts WHERE slug = ? AND id <> ?'); $s->execute([$slug, $exceptId]);
        if (!$s->fetch()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}
function fields(array $b): array {
    return [str($b, 'title', 190, true), str($b, 'category', 80), str($b, 'excerpt', 300), trim((string)($b['content'] ?? '')) ?: fail('Le contenu est obligatoire.'),
            str($b, 'image', 190), empty($b['published']) ? 0 : 1];
}
if ($m === 'GET') {
    $all = isset($_GET['all']); if ($all) require_admin();
    $where = $all ? '' : ' AND published = 1';
    if (isset($_GET['slug'])) {
        $s = db()->prepare("SELECT id, slug, title, category, excerpt, content, image, published, created_at FROM blog_posts WHERE slug = ?$where"); $s->execute([$_GET['slug']]);
        $p = $s->fetch(); $p ? out($p) : fail('Article introuvable.', 404);
    }
    out(['posts' => db()->query('SELECT id, slug, title, category, excerpt, image, published, created_at FROM blog_posts WHERE 1=1' . $where . ' ORDER BY created_at DESC, id DESC')->fetchAll()]);
}
require_admin();
if ($m === 'POST') {
    [$t, $c, $e, $ct, $im, $pub] = fields(body());
    $slug = unique_slug(slugify($t));
    db()->prepare('INSERT INTO blog_posts (slug,title,category,excerpt,content,image,published,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)')
        ->execute([$slug, $t, $c, $e, $ct, $im, $pub, now(), now()]);
    out(['id' => (int)db()->lastInsertId(), 'slug' => $slug], 201);
}
if (!$id) fail('id requis.');
if ($m === 'PUT') {
    [$t, $c, $e, $ct, $im, $pub] = fields(body());
    db()->prepare('UPDATE blog_posts SET title=?,category=?,excerpt=?,content=?,image=?,published=?,updated_at=? WHERE id=?')->execute([$t, $c, $e, $ct, $im, $pub, now(), $id]);
    out(['ok' => true]);
}
if ($m === 'DELETE') { db()->prepare('DELETE FROM blog_posts WHERE id = ?')->execute([$id]); out(['ok' => true]); }
fail('Méthode non autorisée.', 405);
