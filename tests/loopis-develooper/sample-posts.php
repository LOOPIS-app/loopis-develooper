<?php
/** Run from loopis-develooper with: php tests/loopis-develooper/sample-posts.php */

require dirname(__DIR__, 2) . '/includes/samples/content/posts.php';

$posts = array();
foreach (get_sample_posts() as $post) {
    $posts[$post['post_name']] = $post;
    foreach ($post['post_meta'] as $key => $value) {
        if ($value === '' || $value === array() || $value === array('date' => '', 'time' => '')) {
            throw new RuntimeException('Empty sample post metadata: ' . $post['post_name'] . ' / ' . $key);
        }
    }
}

if ($posts['kanguru']['post_meta']['extend_date'] !== array('date' => '-7 days', 'time' => '07:00:00')
    || $posts['trastol']['post_meta']['participants'] !== array('fred-fetcher', 'rebecca-raffle', 'monica-manager')
    || $posts['trastol']['post_meta']['reminder_fetch'] !== '1'
    || $posts['pinnstol']['post_meta']['forward_date'] !== array('date' => '', 'time' => '00:04:00')
    || $posts['pinnstol']['post_meta']['forward_post'] !== 'pinnstol_2'
    || $posts['pinnstol_2']['post_meta']['previous_post'] !== 'pinnstol') {
    throw new RuntimeException('Non-empty sample metadata must be preserved.');
}

echo "Sample post metadata tests passed.\n";
