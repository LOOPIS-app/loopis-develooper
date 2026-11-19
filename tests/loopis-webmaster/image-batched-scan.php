<?php
/** Standalone regression tests for resumable month scans. */
require __DIR__ . '/image-test-helpers.php';

function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function selected($value, $current) { if ($value === $current) echo 'selected="selected"'; }
function wp_nonce_field($action) { echo '<input type="hidden" name="_wpnonce" value="test">'; }

class Batched_Scan_Test_DB extends Migration_Test_DB {
    public $queues = array('app_loopis_image_conversion' => array(), 'app_loopis_image_cleanup' => array());
    public $args = array();
    public $registrations = array();
    public $extra_registrations = array();
    public $content = array();
    public $fail_lookup = false;
    public $fail_insert = false;
    public function esc_like($value) { return addcslashes($value, '_%\\'); }
    public function prepare($sql, ...$args) {
        $this->args = count($args) === 1 && is_array($args[0]) ? $args[0] : $args;
        return $sql;
    }
    public function get_col($sql) {
        $this->last_error = $this->fail_lookup ? 'Simulated lookup failure' : '';
        if ($this->fail_lookup) return null;
        if (strpos($sql, 'SELECT DISTINCT p.ID') !== false) {
            return array_keys(array_filter($this->registrations, function($relative) { return in_array($relative, $this->args, true); }));
        }
        if (strpos($sql, 'SELECT target_path') !== false) {
            $prefixes = array_map(function($arg) { return stripcslashes(rtrim($arg, '%')); }, $this->args);
            return array_values(array_map(function($item) { return $item['target_path']; }, array_filter($this->queues['app_loopis_image_conversion'], function($item) use ($prefixes) {
                return in_array($item['status'], array('pending', 'skipped'), true)
                    && (strpos($item['target_path'], $prefixes[0]) === 0 || strpos($item['target_path'], $prefixes[1]) === 0);
            })));
        }
        throw new RuntimeException('Unexpected column query: ' . $sql);
    }
    public function get_var($sql) {
        if (strpos($sql, 'SELECT 1 FROM (') !== false) {
            foreach ($this->content as $content) {
                foreach ($this->args as $arg) {
                    if (strpos($arg, '(^|[^0-9])') === 0 ? preg_match('/' . $arg . '/', $content) : stripos($content, stripcslashes(trim($arg, '%'))) !== false) return '1';
                }
            }
            return null;
        }
        if (strpos($sql, "p.post_type = 'attachment'") !== false) {
            foreach ($this->registrations + $this->extra_registrations as $id => $file) {
                if ($id !== $this->args[0] && stripos($file, stripcslashes(trim($this->args[1], '%'))) !== false) return (string) $id;
            }
            return null;
        }
        if (preg_match("/FROM (app_loopis_image_(?:conversion|cleanup)) WHERE status = 'processing'/", $sql, $match)) {
            return count(array_filter($this->queues[$match[1]], function($item) { return $item['status'] === 'processing'; }));
        }
        return parent::get_var($sql);
    }
    public function query($sql) {
        if (preg_match('/^DELETE FROM (app_loopis_image_(?:conversion|cleanup))$/', $sql, $match)) {
            $this->queues[$match[1]] = array();
            return 1;
        }
        if (preg_match("/UPDATE (app_loopis_image_(?:conversion|cleanup)) SET status = 'superseded'/", $sql, $match)) {
            foreach ($this->queues[$match[1]] as &$item) {
                if ($match[1] === 'app_loopis_image_conversion' || in_array($item['status'], array('review', 'pending', 'skipped'), true)) $item['status'] = 'superseded';
            }
            return 1;
        }
        throw new RuntimeException('Unexpected query: ' . $sql);
    }
    public function insert($table, $data, $format) {
        if ($this->fail_insert) {
            $this->last_error = 'Simulated insert failure';
            return false;
        }
        $data['id'] = ++$this->insert_id;
        $this->queues[$table][] = $data;
        return 1;
    }
}

try {
    migration_test_assert(loopis_image_tools_scan_shared_summary(0) === '' && strpos(loopis_image_tools_scan_shared_summary(1), '1 files with multiple attachment registrations left untouched') !== false, 'Shared-file summaries must be omitted only when no files are affected.');
    $base = $fixture . '/uploads';
    mkdir($base . '/2024/01', 0777, true);
    mkdir($base . '/2024/02', 0777, true);
    mkdir($base . '/2025/01', 0777, true);
    migration_test_assert(symlink($base, $fixture . '/upload-alias'), 'Could not create an uploads-root symlink fixture.');
    $upload_alias = $fixture . '/upload-alias';
    $GLOBALS['migration_test_dir'] = $upload_alias;
    $wpdb = new Batched_Scan_Test_DB();
    for ($i = 0; $i < 205; $i++) file_put_contents($base . '/2024/01/' . sprintf('%03d.jpg', $i), 'image');
    file_put_contents($base . '/2024/02/other.jpg', 'other');
    file_put_contents($base . '/2025/01/outside.jpg', 'outside');
    foreach (array(201 => '000.jpg', 202 => '150.jpg') as $id => $name) {
        $wpdb->registrations[$id] = $id === 202 ? $upload_alias . '/2024/01/' . $name : '2024/01/' . $name;
        $GLOBALS['migration_test_files'][$id] = $upload_alias . '/2024/01/' . $name;
        $GLOBALS['migration_test_posts'][$id] = (object) array('post_type' => 'attachment', 'post_parent' => 20, 'post_mime_type' => 'image/jpeg');
    }
    $wpdb->registrations[203] = '2024/01/003.jpg';
    $wpdb->registrations[204] = '2024/01/003.jpg';
    $GLOBALS['migration_test_posts'][20] = (object) array('post_type' => 'event', 'post_title' => 'Summer Fair');
    $state = loopis_image_tools_scan_start('conversion', '2024');
    migration_test_assert(!is_wp_error($state) && get_option('loopis_image_conversion_scan_ready') === '0', 'Starting must disable conversion processing.');
    migration_test_assert(is_wp_error(loopis_image_tools_scan_start('cleanup', '2024')), 'A second tool must not start during a scan.');
    $id = $state['id'];
    $state = loopis_image_tools_scan_batch('conversion', $id);
    migration_test_assert($state['files'] === 100 && $state['month'] === 1, 'The first batch must process at most 100 files from one month.');
    unset($state['selected_month'], $state['pending_attachments'], $state['pending_source_files'], $state['skipped_attachments']);
    update_option(loopis_image_tools_scan_option('conversion'), $state);
    migration_test_assert(is_wp_error(loopis_image_conversion_process_next()), 'A partial scan must not be processed.');
    $state = loopis_image_tools_scan_batch('conversion', $id);
    migration_test_assert($state['files'] === 200 && count($wpdb->queues['app_loopis_image_conversion']) === 2, 'The second batch must continue without duplicates.');
    $targets = array_column($wpdb->queues['app_loopis_image_conversion'], 'target_path');
    migration_test_assert(basename($targets[0]) === 'summer-fair.webp' && basename($targets[1]) === 'summer-fair-2.webp', 'Filename reservations must survive batch boundaries.');
    $state = loopis_image_tools_scan_batch('conversion', $id);
    migration_test_assert($state['files'] === 205 && $state['month'] === 2, 'A batch must not cross into the next month.');
    while ($state['status'] !== 'complete') $state = loopis_image_tools_scan_batch('conversion', $id);
    migration_test_assert($state['files'] === 206 && $state['shared'] === 1 && get_option('loopis_image_conversion_scan_ready') === '1', 'A completed scan must include only the selected year, report shared registrations and enable processing.');
    migration_test_assert(!isset($state['pending_source_files']), 'A resumed older scan must not invent partial attachment file totals.');
    ob_start();
    loopis_image_tools_render_scan('conversion');
    $html = ob_get_clean();
    migration_test_assert(strpos($html, 'id="loopis-image-scan-progress" aria-live="polite" class="loopis-image-scan-complete"') !== false, 'A completed scan must render the green status class after reload.');
    $conversion_logs = loopis_image_conversion_recent_activity(loopis_image_conversion_log_file());
    migration_test_assert(strpos($conversion_logs[0]->message, '1 files with multiple attachment registrations left untouched') !== false, 'A conversion scan must log affected shared files.');
    migration_test_assert(is_wp_error(loopis_image_tools_check_item_scope('conversion', array($base . '/2025/01/outside.jpg'))), 'Processing must reject files outside the selected year.');
    $wpdb->content = array('A post from another year references 201');
    $wpdb->extra_registrations[999] = 'Backup metadata for 100.jpg from a different year';
    $state = loopis_image_tools_scan_start('cleanup', '2024');
    ob_start();
    loopis_image_tools_render_scan('cleanup');
    $html = ob_get_clean();
    migration_test_assert(strpos($html, 'id="loopis-image-scan-progress" aria-live="polite" class=') === false, 'An unfinished scan must not render the completed status class.');
    $id = $state['id'];
    while ($state['status'] !== 'complete') $state = loopis_image_tools_scan_batch('cleanup', $id);
    $cleanup = $wpdb->queues['app_loopis_image_cleanup'];
    migration_test_assert($state['files'] === 206 && $state['shared'] === 1 && count($cleanup) === 203, 'Cleanup must check whole-site references and scan only the selected year.');
    $cleanup_logs = loopis_image_conversion_recent_activity(loopis_image_conversion_log_file('cleanup'));
    migration_test_assert(strpos($cleanup_logs[0]->message, '1 files with multiple attachment registrations left untouched') !== false, 'A cleanup scan must log affected shared files.');
    migration_test_assert(!in_array(201, array_column($cleanup, 'attachment_id'), true), 'A referenced attachment must not be a cleanup candidate.');
    migration_test_assert(!in_array($base . '/2024/01/100.jpg', array_column($cleanup, 'source_path'), true), 'Files registered by an attachment in another year must remain protected.');
    foreach (array('conversion', 'cleanup') as $tool) {
        $before = get_option(loopis_image_tools_scan_option($tool));
        foreach (array('00', '13', '../01', array('01'), '03') as $invalid_month) {
            migration_test_assert(is_wp_error(loopis_image_tools_scan_start($tool, '2024', $invalid_month)), 'Invalid or missing month folders must not start a scan.');
            migration_test_assert(get_option(loopis_image_tools_scan_option($tool)) === $before && get_option('loopis_image_' . $tool . '_scan_ready') === '1', 'A rejected month must not modify the existing scan or queue readiness.');
        }
        $selected = loopis_image_tools_scan_start($tool, '2024', '01');
        migration_test_assert(!is_wp_error($selected) && $selected['selected_month'] === '01', 'The selected month must be persisted.');
        $selected_id = $selected['id'];
        $selected = loopis_image_tools_scan_batch($tool, $selected_id);
        migration_test_assert($selected['files'] === 100 && $selected['status'] === 'active', 'A selected month must continue across multiple batches.');
        while ($selected['status'] !== 'complete') $selected = loopis_image_tools_scan_batch($tool, $selected_id);
        migration_test_assert($selected['files'] === 205 && $selected['month'] === 2, 'The selected-month scan must finish without visiting the following month.');
        if ($tool === 'conversion') {
            migration_test_assert($selected['pending_attachments'] === 2 && $selected['pending_source_files'] === 2 && $selected['skipped_attachments'] === 0, 'Attachment file counts must persist and accumulate across batches.');
        }
        migration_test_assert(!is_wp_error(loopis_image_tools_check_item_scope($tool, array($base . '/2024/01/000.jpg'))) && is_wp_error(loopis_image_tools_check_item_scope($tool, array($base . '/2024/02/other.jpg'))), 'Processing must reject files in a different month of the same year.');
        $february = loopis_image_tools_scan_start($tool, '2024', '02');
        $february = loopis_image_tools_scan_batch($tool, $february['id']);
        migration_test_assert(!is_wp_error($february) && $february['status'] === 'complete' && $february['files'] === 1, 'Scanning a later month must finish after that month alone.');
        $logs = loopis_image_conversion_recent_activity(loopis_image_conversion_log_file($tool));
        migration_test_assert(strpos($logs[0]->message, 'multiple attachment registrations') === false, 'A zero shared-file count must not appear in the scan activity.');
    }
    mkdir($base . '/2024/12');
    $december = loopis_image_tools_scan_start('cleanup', '2024', '12');
    $december = loopis_image_tools_scan_batch('cleanup', $december['id']);
    migration_test_assert(!is_wp_error($december) && $december['status'] === 'complete' && $december['files'] === 0, 'An empty December must complete successfully.');
    rmdir($base . '/2024/12');
    mkdir($base . '/2024/11');
    $missing = loopis_image_tools_scan_start('cleanup', '2024', '11');
    rmdir($base . '/2024/11');
    $missing = loopis_image_tools_scan_batch('cleanup', $missing['id']);
    migration_test_assert(is_wp_error($missing) && get_option('loopis_image_cleanup_scan_ready') === '0', 'A month removed during a scan must leave the queue disabled.');
    $state = loopis_image_tools_scan_start('cleanup', '2024', '02');
    $state = loopis_image_tools_scan_batch('cleanup', $state['id']);
    migration_test_assert(!is_wp_error($state) && $state['status'] === 'complete', 'A failed selected-month scan must be replaceable.');
    $state = loopis_image_tools_scan_start('conversion', '2024');
    $old_id = $state['id'];
    $GLOBALS['migration_test_always_change_filename'] = true;
    $name_failure = loopis_image_tools_scan_batch('conversion', $old_id);
    unset($GLOBALS['migration_test_always_change_filename']);
    migration_test_assert(is_wp_error($name_failure) && $name_failure->get_error_code() === 'target_name_unavailable' && strpos($name_failure->get_error_message(), 'Attachment #201') !== false && get_option('loopis_image_conversion_scan_ready') === '0', 'A filename planning failure must identify the attachment and keep the queue disabled.');
    $state = loopis_image_tools_scan_start('conversion', '2024');
    $old_id = $state['id'];
    $wpdb->fail_lookup = true;
    $failure = loopis_image_tools_scan_batch('conversion', $old_id);
    $wpdb->fail_lookup = false;
    $wpdb->last_error = '';
    migration_test_assert(is_wp_error($failure) && get_option('loopis_image_conversion_scan_ready') === '0', 'A database failure must leave the scan disabled.');
    migration_test_assert(is_wp_error(loopis_image_tools_scan_batch('conversion', $old_id)), 'An interrupted batch must not be replayed blindly.');
    $replacement = loopis_image_tools_scan_start('conversion', '2024');
    migration_test_assert(!is_wp_error($replacement) && is_wp_error(loopis_image_tools_scan_batch('conversion', $old_id)), 'A stale request must not resume a replacement scan.');
    $wpdb->fail_insert = true;
    $failure = loopis_image_tools_scan_batch('conversion', $replacement['id']);
    $wpdb->fail_insert = false;
    $wpdb->last_error = '';
    migration_test_assert(is_wp_error($failure) && is_wp_error(loopis_image_tools_scan_batch('conversion', $replacement['id'])) && get_option('loopis_image_conversion_scan_ready') === '0', 'A failed queue insert must disable the scan and prevent replay.');
    $reset = loopis_image_tools_reset('conversion', 'clear');
    migration_test_assert(!is_wp_error($reset) && is_wp_error(loopis_image_tools_scan_batch('conversion', $replacement['id'])) && get_option('loopis_image_conversion_scan_ready') === '0', 'Reset must disable the scan and prevent stale batches from resuming.');
    foreach (array('main-a.jpg', 'main-b.jpg', 'skipped.jpg', 'thumb-a.jpg', 'thumb-b.jpg', 'original-b.jpg') as $name) {
        file_put_contents($base . '/2025/01/' . $name, 'fixture');
    }
    foreach (array(501 => 'main-a.jpg', 502 => 'main-b.jpg', 503 => 'skipped.jpg') as $id => $name) {
        $wpdb->registrations[$id] = '2025/01/' . $name;
        $GLOBALS['migration_test_files'][$id] = $upload_alias . '/2025/01/' . $name;
        $GLOBALS['migration_test_posts'][$id] = (object) array('post_type' => 'attachment', 'post_parent' => $id === 503 ? 0 : 20, 'post_mime_type' => 'image/jpeg');
    }
    $GLOBALS['migration_test_metadata'][501] = array('sizes' => array('thumbnail' => array('file' => 'thumb-a.jpg'), 'missing' => array('file' => 'missing.jpg')));
    $GLOBALS['migration_test_metadata'][502] = array('original_image' => 'original-b.jpg', 'sizes' => array('thumbnail' => array('file' => 'thumb-b.jpg')));
    $state = loopis_image_tools_scan_start('conversion', '2025', '01');
    $state = loopis_image_tools_scan_batch('conversion', $state['id']);
    migration_test_assert($state['status'] === 'complete' && $state['files'] === 7 && $state['candidates'] === 3 && $state['pending_attachments'] === 2 && $state['pending_source_files'] === 5 && $state['skipped_attachments'] === 1, 'File totals must count existing full-size, original and thumbnail files for pending attachments, not skipped attachments or unrelated files.');
    $logs = loopis_image_conversion_recent_activity(loopis_image_conversion_log_file());
    migration_test_assert(strpos($logs[0]->message, '2 pending attachments (5 existing source and associated files); 1 skipped attachments') !== false, 'Conversion activity must report the actual pending attachment and file totals.');
    ob_start();
    loopis_image_tools_render_scan('conversion');
    $html = ob_get_clean();
    migration_test_assert(strpos($html, '2 pending attachments (5 existing source and associated files); 1 skipped attachments') !== false, 'Completed conversion scan output must show pending and file totals.');
    unset($state['pending_attachments'], $state['pending_source_files'], $state['skipped_attachments']);
    update_option(loopis_image_tools_scan_option('conversion'), $state);
    ob_start();
    loopis_image_tools_render_scan('conversion');
    $html = ob_get_clean();
    migration_test_assert(strpos($html, 'attachment file totals unavailable for this scan') !== false && get_option('loopis_image_conversion_scan_ready') === '1', 'A completed older scan must remain usable without fabricating totals.');
    echo "Batched image scan tests passed.\n";
} finally {
    migration_test_remove_fixture($fixture);
}
