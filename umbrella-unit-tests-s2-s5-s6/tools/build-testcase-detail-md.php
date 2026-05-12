<?php

declare(strict_types=1);

/**
 * Sinh bảng Markdown đặc tả testcase (S2, S5, S6) + cột Pass/Fail từ junit.
 *
 *   php vendor/bin/phpunit --log-junit junit.xml
 *   php tools/build-testcase-detail-md.php junit.xml
 *
 * Đầu ra: S2_S5_S6_BANG_TESTCASE_CHI_TIET.md
 */
$junit = $argv[1] ?? (__DIR__ . '/../junit.xml');
if (!is_readable($junit)) {
    fwrite(STDERR, "Missing junit: $junit\n");
    exit(1);
}

$xml = simplexml_load_file($junit);
if ($xml === false) {
    fwrite(STDERR, "Invalid XML: $junit\n");
    exit(1);
}

/** @var array<string, string> */
$failureTexts = [];
foreach ($xml->xpath('//testcase') as $tc) {
    $name = (string) $tc['name'];
    $chunks = [];
    foreach ($tc->failure as $node) {
        $chunks[] = trim((string) $node);
    }
    foreach ($tc->error as $node) {
        $chunks[] = trim((string) $node);
    }
    if ($chunks === []) {
        continue;
    }
    $raw = trim(implode("\n", $chunks));
    $failureTexts[$name] = $raw;
}

/** @var array<string, array{0:string,1:string,2:string,3:string,4:string,5:string}> */
$spec = require __DIR__ . '/testcase-spec-rows.php';

function mdCell(string $s): string
{
    return str_replace('|', '\\|', str_replace(["\r\n", "\n", "\r"], ' ', $s));
}

/**
 * Ghi chú khi Fail: ưu tiên dòng BUG/lỗi nghiệp vụ trong output PHPUnit, thêm bằng chứng assert.
 */
function failNotesFor(string $phpMethod, string $fileClass, string $fn, string $raw): string
{
    $lines = preg_split('/\R/', $raw) ?: [];
    $biz = [];
    foreach ($lines as $ln) {
        $t = trim($ln);
        if ($t === '' || (strpos($t, 'C:\\') === 0) || strpos($t, 'phpvfscomposer://') !== false) {
            continue;
        }
        if (strpos($t, '::test_') !== false && strpos($t, 'UmbrellaTests\\') !== false) {
            continue;
        }
        if (stripos($t, 'Failed asserting') === 0) {
            break;
        }
        if (preg_match('/^(BUG NGHIỆP VỤ|Lỗi nghiệp vụ|Nghiệp vụ)/iu', $t) || strpos($t, 'BUG NGHIỆP VỤ') !== false) {
            $biz[] = $t;
        } elseif ($biz === [] && preg_match('/[\p{L}]{4,}/u', $t)) {
            // Dòng mô tả từ assert message (vd. Search phải chạy được...) trước "Failed asserting"
            $biz[] = $t;
        }
    }
    if ($biz === []) {
        if (strpos($phpMethod, 'memberMustBeForbidden') !== false || strpos($phpMethod, 'memberDoctorForbidden') !== false) {
            $biz[] = 'Lỗi nghiệp vụ phân quyền: role member vẫn truy cập được endpoint/dữ liệu quản trị (đáng ra phải result=0 và từ chối).';
        } elseif (strpos($phpMethod, 'memberForbidden') !== false || strpos($phpMethod, '_member_forbidden') !== false) {
            $biz[] = 'Lỗi nghiệp vụ phân quyền: member vẫn thao tác được endpoint chỉ dành cho admin/supporter.';
        }
    }
    $evidence = '';
    if (preg_match('/Failed asserting that (.+?) is identical to (.+?)\.\s*$/m', $raw, $m)) {
        $actual = trim($m[1]);
        $expected = trim($m[2]);
        $evidence = "Bằng chứng: test kỳ vọng giá trị {$expected} nhưng nhận được {$actual} (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ).";
    } elseif (preg_match('/Failed asserting that (.+)$/m', $raw, $m)) {
        $evidence = 'Bằng chứng: ' . trim($m[1]) . '.';
    }

    $fallback = "Lỗi nghiệp vụ / kết quả API không khớp kịch bản kiểm thử trên {$fileClass} {$fn}.";
    $head = $biz !== [] ? implode(' ', $biz) : $fallback;

    $parts = array_filter([$head, $evidence]);
    $out = implode(' ', $parts);
    $out = preg_replace('/\s+/', ' ', $out) ?? $out;
    if (function_exists('mb_strlen') && mb_strlen($out) > 900) {
        return mb_substr($out, 0, 897) . '...';
    }
    if (strlen($out) > 900) {
        return substr($out, 0, 897) . '...';
    }

    return $out;
}

$ordered = [];
foreach ($xml->xpath('//testcase') as $tc) {
    $phpMethod = (string) $tc['name'];
    $className = (string) ($tc['class'] ?? '');
    if ($className === '') {
        $className = str_replace('.', '\\', (string) $tc['classname']);
    }
    $ordered[] = [$phpMethod, $className, (string) $tc['file']];
}

$rows = [];
$unknown = [];
foreach ($ordered as [$phpMethod, $className, $filePath]) {
    if (!preg_match('/^test_(TC_S[256]_\d+)_/', $phpMethod, $mm)) {
        $unknown[] = $phpMethod;
        continue;
    }
    $caseId = $mm[1];
    if (!isset($spec[$phpMethod])) {
        $unknown[] = $phpMethod . ' (missing spec)';
        continue;
    }
    [$fileClass, $fn, $objective, $pre, $input, $expected] = $spec[$phpMethod];
    $fail = isset($failureTexts[$phpMethod]);
    $pf = $fail ? 'Fail' : 'Pass';
    $notes = '';
    if ($fail) {
        $notes = failNotesFor($phpMethod, $fileClass, $fn, $failureTexts[$phpMethod]);
    }
    $rows[] = [$caseId, $fileClass, $fn, $objective, $pre, $input, $expected, $pf, $notes, $phpMethod];
}

usort($rows, static function (array $a, array $b): int {
    preg_match('/^TC_(S[256])_(\d+)$/', $a[0], $ma);
    preg_match('/^TC_(S[256])_(\d+)$/', $b[0], $mb);
    $sa = $ma[1] ?? 'S9';
    $sb = $mb[1] ?? 'S9';
    if ($sa !== $sb) {
        return strcmp($sa, $sb);
    }
    $na = isset($ma[2]) ? (int) $ma[2] : 9999;
    $nb = isset($mb[2]) ? (int) $mb[2] : 9999;
    if ($na !== $nb) {
        return $na <=> $nb;
    }
    return strcmp($a[9] ?? '', $b[9] ?? '');
});

$out = "# Bảng đặc tả test case S2, S5, S6\n\n";
$out .= "Cột **Pass/Fail** và **Notes** (khi Fail) lấy từ `junit.xml` sau khi chạy PHPUnit.\n\n";
$out .= "```bash\nphp vendor/bin/phpunit --log-junit junit.xml\nphp tools/build-testcase-detail-md.php junit.xml\n```\n\n";
$out .= "| Test Case ID | File/Class | Function Name | Test Objective | Precondition | Input | Expected Output | Pass/Fail | Notes |\n";
$out .= "|---|---|---|---|---|---|---|---|---|\n";

foreach ($rows as $r) {
    [$id, $fileClass, $fn, $objective, $pre, $input, $expected, $pf, $notes] = $r;
    $out .= sprintf(
        '| %s | %s | %s | %s | %s | %s | %s | %s | %s |' . "\n",
        $id,
        mdCell($fileClass),
        mdCell($fn),
        mdCell($objective),
        mdCell($pre),
        mdCell($input),
        mdCell($expected),
        $pf,
        $notes !== '' ? mdCell($notes) : ''
    );
}

if ($unknown !== []) {
    fwrite(STDERR, "WARN: bỏ qua hoặc thiếu spec:\n - " . implode("\n - ", $unknown) . "\n");
}

$target = dirname(__DIR__) . '/S2_S5_S6_BANG_TESTCASE_CHI_TIET.md';
file_put_contents($target, $out);
echo "Wrote $target (" . count($rows) . " rows)\n";
