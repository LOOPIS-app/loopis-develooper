<?php
/** Run from loopis-develooper with: php tests/loopis-webmaster/webp-metadata.php */

define('ABSPATH', __DIR__ . '/');
class WP_Error {
    public function __construct($code, $message) {}
}
require dirname(__DIR__, 3) . '/loopis-content/functions/loopis_webp_metadata.php';

function metadata_test_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function metadata_test_chunk($type, $data) {
    return $type . pack('V', strlen($data)) . $data . (strlen($data) % 2 ? "\0" : '');
}

$file = tempnam(sys_get_temp_dir(), 'loopis-webp-test-');
if (!$file) {
    throw new RuntimeException('Could not create fixture.');
}
try {
    $image = imagecreatetruecolor(10, 12);
    imagewebp($image, $file);
    imagedestroy($image);
    $original = file_get_contents($file);
    $vp8x = chr(0x2c) . "\0\0\0" . "\x09\0\0" . "\x0b\0\0";
    $body = 'WEBP' . metadata_test_chunk('VP8X', $vp8x) . metadata_test_chunk('ICCP', 'color-profile')
        . substr($original, 12) . metadata_test_chunk('EXIF', 'private-gps') . metadata_test_chunk('XMP ', 'private-xmp');
    file_put_contents($file, 'RIFF' . pack('V', strlen($body)) . $body);
    chmod($file, 0640);
    metadata_test_assert(loopis_media_strip_webp_metadata($file) === true, 'Metadata removal failed.');
    $result = file_get_contents($file);
    metadata_test_assert(unpack('V', substr($result, 4, 4))[1] === strlen($result) - 8, 'RIFF length must match output.');
    metadata_test_assert(strpos($result, 'private-gps') === false && strpos($result, 'private-xmp') === false, 'EXIF and XMP must be removed.');
    metadata_test_assert(strpos($result, 'color-profile') !== false && ord($result[20]) === 0x20, 'ICC profile must be preserved and metadata flags cleared.');
    $dimensions = getimagesize($file);
    metadata_test_assert($dimensions && $dimensions[0] === 10 && $dimensions[1] === 12 && $dimensions['mime'] === 'image/webp', 'Output must remain a valid image with unchanged dimensions.');
    clearstatcache(true, $file);
    metadata_test_assert((fileperms($file) & 0777) === 0640, 'File permissions must remain unchanged.');
    metadata_test_assert(loopis_media_strip_webp_metadata($file) === true, 'Metadata removal must be idempotent.');

    $invalid = 'RIFF' . pack('V', 16) . 'WEBP' . 'EXIF' . pack('V', 100);
    file_put_contents($file, $invalid);
    metadata_test_assert(loopis_media_strip_webp_metadata($file) instanceof WP_Error, 'Malformed WebP chunks must fail.');
    metadata_test_assert(file_get_contents($file) === $invalid, 'Failure must not replace the input.');
    echo "WebP metadata tests passed.\n";
} finally {
    unlink($file);
}
