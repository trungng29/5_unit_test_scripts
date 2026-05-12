<?php

/**
 * Sau PHPUnit: php vendor/bin/phpunit --log-junit junit.xml
 *            php tools/build-result-table.php junit.xml
 *
 * Sinh S2_S5_S6_BANG_KET_QUA_PHPUNIT.md: ID lấy từ tên method (TC_Sx_NN), sắp xếp S2→S5→S6 và số tăng dần.
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
        $attr = trim((string) $node['message']);
        $body = trim((string) $node);
        $chunks[] = $attr !== '' ? $attr : $body;
    }
    foreach ($tc->error as $node) {
        $attr = trim((string) $node['message']);
        $body = trim((string) $node);
        $chunks[] = $attr !== '' ? $attr : $body;
    }
    if ($chunks === []) {
        continue;
    }
    $text = trim(preg_replace('/\s+/', ' ', implode(' ', $chunks)));
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (function_exists('mb_strlen') && mb_strlen($text) > 480) {
        $text = mb_substr($text, 0, 477) . '...';
    } elseif (strlen($text) > 480) {
        $text = substr($text, 0, 477) . '...';
    }
    $failureTexts[$name] = $text;
}

/**
 * @return array{0:string,1:string,2:string,3:string,4:string,5:string,6:string,7:string}
 * [script, controller, apiMethod, objective, precondition, input, expected, notesStatic]
 */
function umbrellaInferSpecRow(string $phpMethod, string $junitClass): array
{
    $scriptMap = [
        'UmbrellaTests\\S2DoctorManagementTest' => 'S2DoctorManagementTest.php',
        'UmbrellaTests\\S5BookingManagementTest' => 'S5BookingManagementTest.php',
        'UmbrellaTests\\S6ServiceSpecialityRoomClinicTest' => 'S6ServiceSpecialityRoomClinicTest.php',
    ];
    $script = $scriptMap[$junitClass] ?? (preg_replace('/^.*\\\\/', '', $junitClass) . '.php');

    $ctl = '—';
    $api = '—';
    if (strpos($phpMethod, 'test_TC_S2_') === 0) {
        if (strpos($phpMethod, 'doctorsGetAll') !== false || strpos($phpMethod, 'doctorsSave') !== false) {
            $ctl = 'DoctorsController.php';
            $api = strpos($phpMethod, 'doctorsSave') !== false ? 'save()' : 'getAll()';
        } elseif (strpos($phpMethod, 'isDoctorBusy') !== false) {
            $ctl = 'isDoctorBusyController.php';
            $api = 'isDoctorBusy()';
        } else {
            $ctl = 'DoctorController.php';
            if (strpos($phpMethod, 'getById') !== false) {
                $api = 'getById()';
            } elseif (strpos($phpMethod, 'update') !== false) {
                $api = 'update()';
            } elseif (strpos($phpMethod, 'delete') !== false) {
                $api = 'delete()';
            } else {
                $api = 'getById()|update()|delete()';
            }
        }
    } elseif (strpos($phpMethod, 'bookingsSave') !== false || strpos($phpMethod, 'bookingsGetAll') !== false) {
        $ctl = 'BookingsController.php';
        $api = strpos($phpMethod, 'bookingsSave') !== false ? 'save()' : 'getAll()';
    } elseif (strpos($phpMethod, 'bookingGetById') !== false) {
        $ctl = 'BookingController.php';
        $api = 'getById()';
    } elseif (strpos($phpMethod, 'patientBookings') !== false) {
        $ctl = 'PatientBookingsController.php';
        $api = 'getAll()';
    } elseif (strpos($phpMethod, 'patientBooking') !== false) {
        $ctl = 'PatientBookingController.php';
        if (strpos($phpMethod, 'getById') !== false) {
            $api = 'getById()';
        } else {
            $api = 'delete()';
        }
    } elseif (strpos($phpMethod, 'specialities') !== false && strpos($phpMethod, 'speciality') === false) {
        $ctl = 'SpecialitiesController.php';
        $api = strpos($phpMethod, 'Save') !== false || strpos($phpMethod, 'save') !== false ? 'save()' : 'getAll()';
    } elseif (strpos($phpMethod, 'speciality') !== false) {
        $ctl = 'SpecialityController.php';
        if (strpos($phpMethod, 'GetById') !== false || strpos($phpMethod, 'valid') !== false && strpos($phpMethod, 'Get') !== false) {
            $api = 'getById()';
        } elseif (strpos($phpMethod, 'Update') !== false || strpos($phpMethod, 'update') !== false) {
            $api = 'update()';
        } elseif (strpos($phpMethod, 'Delete') !== false || strpos($phpMethod, 'delete') !== false) {
            $api = 'delete()';
        } else {
            $api = 'save()|getById()|update()|delete()';
        }
    } elseif (strpos($phpMethod, 'services') !== false && strpos($phpMethod, 'service') === false) {
        $ctl = 'ServicesController.php';
        $api = strpos($phpMethod, 'Save') !== false || strpos($phpMethod, 'save') !== false ? 'save()' : 'getAll()';
    } elseif (strpos($phpMethod, 'service') !== false) {
        $ctl = 'ServiceController.php';
        if (strpos($phpMethod, 'GetById') !== false || (strpos($phpMethod, 'ok') !== false && strpos($phpMethod, 'service') !== false)) {
            $api = 'getById()';
        } elseif (strpos($phpMethod, 'Update') !== false) {
            $api = 'update()';
        } elseif (strpos($phpMethod, 'Delete') !== false) {
            $api = 'delete()';
        } else {
            $api = 'getById()|update()|delete()';
        }
    } elseif (strpos($phpMethod, 'rooms') !== false) {
        $ctl = 'RoomsController.php';
        $api = 'save()';
    } elseif (strpos($phpMethod, 'room') !== false) {
        $ctl = 'RoomController.php';
        $api = strpos($phpMethod, 'delete') !== false || strpos($phpMethod, 'Delete') !== false ? 'delete()' : 'getById()';
    } elseif (strpos($phpMethod, 'clinics') !== false) {
        $ctl = 'ClinicsController.php';
        $api = 'save()';
    } elseif (strpos($phpMethod, 'clinic') !== false) {
        $ctl = 'ClinicController.php';
        $api = 'getById()';
    }

    $tail = preg_replace('/^test_TC_S[256]_\d+_/', '', $phpMethod);
    $tailWords = str_replace('_', ' ', $tail);
    $obj = ucfirst($tailWords) . '.';

    // Build specific precondition / input / expected from method name keywords
    $pre = 'DB SQLite :memory: seed baseline (admin id=1, member id=2, supporter id=7, patient id=1, speciality id=1, room id=1, service id=1, clinic id=1).';
    $inp = '';
    $exp = '';

    // --- Input inference ---
    if (strpos($phpMethod, 'missingRouteId') !== false || strpos($phpMethod, 'missingId') !== false) {
        $inp = strtoupper(str_replace(['save','getAll','getById','update','delete','isDoctorBusy'], ['POST','GET','GET','PUT','DELETE','GET'], $api)) . ' không có route id.';
    } elseif (strpos($phpMethod, 'missing') !== false) {
        // missing field
        preg_match('/missing([A-Z][a-zA-Z]+)/', $phpMethod, $mf);
        $field = isset($mf[1]) ? lcfirst($mf[1]) : 'field';
        $method = (strpos($api,'save') !== false) ? 'POST' : ((strpos($api,'update') !== false) ? 'PUT' : 'GET');
        $inp = $method . ' body thiếu trường ' . $field . '.';
    } elseif (strpos($phpMethod, 'invalid') !== false || strpos($phpMethod, 'Invalid') !== false) {
        preg_match('/[Ii]nvalid([A-Z][a-zA-Z]+)/', $phpMethod, $mf);
        $field = isset($mf[1]) ? lcfirst($mf[1]) : 'field';
        $method = (strpos($api,'save') !== false) ? 'POST' : ((strpos($api,'update') !== false) ? 'PUT' : 'GET');
        $inp = $method . ' với ' . $field . ' không hợp lệ.';
    } elseif (strpos($phpMethod, 'member') !== false && (strpos($phpMethod, 'Forbidden') !== false || strpos($phpMethod, 'forbidden') !== false || strpos($phpMethod, 'MustBeForbidden') !== false || strpos($phpMethod, 'notAdmin') !== false)) {
        $method = (strpos($api,'save') !== false) ? 'POST' : ((strpos($api,'update') !== false) ? 'PUT' : ((strpos($api,'delete') !== false) ? 'DELETE' : 'GET'));
        $inp = $method . ' AuthUser = member (role=member).';
        $pre = 'DB baseline; AuthUser = doctor role=member (id=2).';
    } elseif (strpos($phpMethod, 'admin') !== false && strpos($phpMethod, 'valid') !== false) {
        $method = (strpos($api,'save') !== false) ? 'POST' : ((strpos($api,'update') !== false) ? 'PUT' : 'GET');
        $inp = $method . ' hợp lệ; AuthUser = admin.';
    } elseif (strpos($phpMethod, 'duplicate') !== false) {
        $method = (strpos($api,'save') !== false) ? 'POST' : 'PUT';
        $inp = $method . ' với dữ liệu trùng lặp.';
    } elseif (strpos($phpMethod, 'empty') !== false || strpos($phpMethod, 'Empty') !== false) {
        $method = (strpos($api,'getAll') !== false) ? 'GET' : 'POST';
        $inp = $method . '; dữ liệu trống / danh sách rỗng.';
    } else {
        $method = (strpos($api,'save') !== false) ? 'POST' : ((strpos($api,'update') !== false) ? 'PUT' : ((strpos($api,'delete') !== false) ? 'DELETE' : 'GET'));
        $inp = $method . ' – xem chi tiết trong ' . $phpMethod . '.';
    }

    // --- Expected output inference ---
    if (strpos($phpMethod, 'returnsSuccess') !== false || strpos($phpMethod, '_ok') !== false || strpos($phpMethod, 'isAccepted') !== false || strpos($phpMethod, 'valid_') !== false) {
        $exp = 'result=1; thao tác thành công.';
    } elseif (strpos($phpMethod, 'returnsError') !== false || strpos($phpMethod, 'Forbidden') !== false || strpos($phpMethod, 'forbidden') !== false || strpos($phpMethod, 'blocked') !== false || strpos($phpMethod, 'Blocked') !== false || strpos($phpMethod, 'rejected') !== false || strpos($phpMethod, 'Rejected') !== false || strpos($phpMethod, 'MustBeForbidden') !== false) {
        $exp = 'result=0; msg mô tả lý do từ chối.';
    } elseif (strpos($phpMethod, 'returnsNotAvailable') !== false || strpos($phpMethod, 'unavailable') !== false) {
        $exp = 'result=0; msg chứa "not available".';
    } elseif (strpos($phpMethod, 'returnsMissing') !== false) {
        $exp = 'result=0; msg chứa tên trường bắt buộc còn thiếu.';
    } elseif (strpos($phpMethod, 'deactivates') !== false) {
        $exp = 'result=1; type="deactivated"; DB: active=0, record vẫn còn.';
    } elseif (strpos($phpMethod, 'hardDeletes') !== false) {
        $exp = 'result=1; type="delete"; DB: record bị xóa hoàn toàn.';
    } else {
        $exp = 'Xem assertion trong ' . $phpMethod . '.';
    }

    $notes = '';
    return [$script, $ctl, $api, $obj, $pre, $inp, $exp, $notes];
}

/** @var array<string, array{0:string,1:string,2:string,3:string,4:string,5:string,6:string,7:string}> */
$detailOverrides = [
    'test_TC_S2_33_getById_memberMustBeForbidden' => [
        'S2DoctorManagementTest.php',
        'DoctorController.php',
        'getById()',
        'Xác minh phân quyền truy cập chi tiết bác sĩ trên endpoint quản trị: tài khoản member phải bị từ chối khi gọi DoctorController::getById(), tránh rò rỉ thông tin bác sĩ.',
        'Đã đăng nhập với Doctor role=member (AuthUser id=2); route params.id trỏ tới doctor khác.',
        'GET, Route->params->id = 2, AuthUser = member doctor.',
        'Expected: HTTP JSON result = 0; msg thông báo không đủ quyền / từ chối truy cập; không trả payload chi tiết doctor cho member.',
        'Notes: Member xem chi tiết doctor bị lọt quyền. Lỗi nghiệp vụ API DoctorController::getById() không chặn role member, nên member vẫn xem được chi tiết doctor (đáng ra chỉ admin mới được xem endpoint quản trị). Bằng chứng test: kỳ vọng result=0 nhưng API trả result=1.',
    ],
    'test_TC_S5_32_patientBookingsGetAll_quantityShouldBeTotalBeforeLimit' => [
        'S5BookingManagementTest.php',
        'PatientBookingsController.php',
        'getAll()',
        'Xác minh logic phân trang booking của bệnh nhân: trường quantity phải là tổng số bản ghi trước LIMIT để frontend tính tổng trang chính xác.',
        'Patient id=1 có 3 booking trong DB; gọi getAll với length=1, start=0.',
        'GET _GET[length]=1, _GET[start]=0, AuthUser = patient.',
        'Expected: result=1; data có đúng 1 phần tử (page size); quantity = 3 (tổng bản ghi của patient).',
        'Notes: Sai semantics phân trang nếu quantity trả theo page size.',
    ],
    'test_TC_S5_33_patientBookingsGetAll_searchByBookingName_filtersCorrectly' => [
        'S5BookingManagementTest.php',
        'PatientBookingsController.php',
        'getAll()',
        'Xác minh tìm kiếm booking của bệnh nhân theo booking_name hoạt động đúng và không lỗi SQL/biến chưa khai báo.',
        'Patient có booking tên bắt đầu Nguyen và Le.',
        'GET search=Nguyen.',
        'Expected: result=1; data chỉ chứa booking khớp điều kiện (booking_name prefix Nguyen).',
        'Notes: Có nguy cơ lỗi truy vấn hoặc lọc sai.',
    ],
    'test_TC_S5_34_bookingsGetAll_memberMustBeForbidden' => [
        'S5BookingManagementTest.php',
        'BookingsController.php',
        'getAll()',
        'Xác minh phân quyền danh sách booking quản trị: role=member không được gọi BookingsController::getAll().',
        'AuthUser = doctor role member.',
        'GET BookingsController::getAll.',
        'Expected: result=0; msg không đủ quyền.',
        'Notes: Member có thể truy cập danh sách booking quản trị (lọt quyền) nếu API không chặn.',
    ],
    'test_TC_S5_35_duplicateSameSlot_rejected' => [
        'S5BookingManagementTest.php',
        'BookingsController.php',
        'save()',
        'Chống trùng lịch bác sĩ: cùng doctor_id + appointment_date + appointment_time không được tạo booking thứ hai.',
        'Đã có booking hợp lệ cho doctor_id=2 tại slot đã chọn.',
        'POST hai lần cùng payload (doctor_id, date, time).',
        'Expected: lần 1 result=1; lần 2 result=0.',
        'Notes: Double-booking bác sĩ nếu API không chặn.',
    ],
    'test_TC_S5_38_bookingGetById_memberDoctorForbidden' => [
        'S5BookingManagementTest.php',
        'BookingController.php',
        'getById()',
        'Member (doctor role) không được xem chi tiết booking qua endpoint quản trị BookingController::getById (chỉ admin/supporter).',
        'Booking id tồn tại; AuthUser = member doctor.',
        'GET route id = booking.',
        'Expected: result=0; msg chứa permission / không đủ quyền.',
        'Notes: Nếu API trả result=1 thì lọt quyền endpoint quản trị.',
    ],
    'test_TC_S6_42_servicesGetAll_memberMustBeForbidden' => [
        'S6ServiceSpecialityRoomClinicTest.php',
        'ServicesController.php',
        'getAll()',
        'Member không được gọi ServicesController::getAll() (endpoint quản trị danh sách dịch vụ).',
        'AuthUser = doctor role member.',
        'GET ServicesController::getAll.',
        'Expected: result=0; msg từ chối quyền.',
        'Notes: Role check có thể bị comment trong controller.',
    ],
    'test_TC_S6_43_specialitiesGetAll_memberMustBeForbidden' => [
        'S6ServiceSpecialityRoomClinicTest.php',
        'SpecialitiesController.php',
        'getAll()',
        'Member không được gọi SpecialitiesController::getAll() (endpoint quản trị danh sách chuyên khoa).',
        'AuthUser = doctor role member.',
        'GET SpecialitiesController::getAll.',
        'Expected: result=0; msg từ chối quyền.',
        'Notes: Role check có thể bị comment trong controller.',
    ],
];

$ordered = [];
foreach ($xml->xpath('//testcase') as $tc) {
    $phpMethod = (string) $tc['name'];
    $className = (string) ($tc['class'] ?? '');
    if ($className === '') {
        $className = str_replace('.', '\\', (string) $tc['classname']);
    }
    $ordered[] = [$phpMethod, $className, (string) $tc['file']];
}

$unknown = [];
$rows = [];
foreach ($ordered as [$phpMethod, $className, $filePath]) {
    if (!preg_match('/^test_(TC_S[256]_\d+)_/', $phpMethod, $mm)) {
        $unknown[] = $phpMethod;
        continue;
    }
    $caseId = $mm[1];
    $base = isset($detailOverrides[$phpMethod]) ? $detailOverrides[$phpMethod] : umbrellaInferSpecRow($phpMethod, $className);
    if ($filePath !== '') {
        $base[0] = basename($filePath);
    }
    $rows[] = array_merge([$caseId], $base, [$phpMethod]);
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

if ($unknown !== []) {
    fwrite(STDERR, "WARN: junit có testcase không khớp pattern test_TC_Sx_NN_: \n - " . implode("\n - ", $unknown) . "\n");
}

function mdCell(string $s): string
{
    return str_replace('|', '\\|', $s);
}

$out = "## Kết quả unit test S2, S5, S6 (PHP API)\n\n";
$out .= "| Test Case ID | File / Class | Function Name | Test Objective | Precondition | Input | Expected Output | Pass/Fail | Notes |\n";
$out .= "|---|---|---|---|---|---|---|---|---|\n";

foreach ($rows as $r) {
    [$id, $script, $ctl, $api, $obj, $pre, $inp, $exp, $notesStatic, $phpMethod] = $r;
    $pf = isset($failureTexts[$phpMethod]) ? '**Fail**' : 'Pass';
    $notesParts = [];
    if ($notesStatic !== '') {
        $notesParts[] = $notesStatic;
    }
    if (isset($failureTexts[$phpMethod])) {
        $notesParts[] = $failureTexts[$phpMethod];
    }
    $notesOut = $notesParts !== [] ? implode(' | ', $notesParts) : '—';

    $out .= sprintf(
        '| %s | %s | %s | %s | %s | %s | %s | %s | %s |' . "\n",
        $id,
        mdCell($ctl),
        mdCell($api),
        mdCell($obj),
        mdCell($pre),
        mdCell($inp),
        mdCell($exp),
        $pf,
        mdCell($notesOut)
    );
}

$out .= "\n> Sinh tự động: `tools/build-result-table.php` + `junit.xml`. ID lấy từ tiền tố `TC_Sx_NN` trong tên method PHPUnit.\n";
$out .= "\n> Chạy: `php vendor/bin/phpunit --log-junit junit.xml` rồi `php tools/build-result-table.php junit.xml`.\n";
$out .= "\n> **Mock DB:** SQLite `:memory:` (Pixie), không ghi production.\n";

$target = dirname(__DIR__) . '/S2_S5_S6_BANG_KET_QUA_PHPUNIT.md';
file_put_contents($target, $out);
echo "Wrote $target (" . count($rows) . " rows)\n";
