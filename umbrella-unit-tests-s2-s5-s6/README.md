# Unit test S2 / S5 / S6 (Umbrella API)

Thư mục độc lập dưới `www`, dùng **PHPUnit 9** và **SQLite `:memory:`** (Pixie giống project) làm **mock database** — không đụng MySQL production, không cần rollback (mỗi test tạo lại schema).

## Chạy test

```bash
cd umbrella-unit-tests-s2-s5-s6
composer install
php vendor/bin/phpunit
```

## Chạy từng suite + coverage report

> Coverage cần bật **Xdebug** hoặc **PCOV** trong PHP CLI. Hướng dẫn bật Xdebug (XAMPP/Laragon, `php.ini`, kiểm tra): xem [COVERAGE_XDEBUG.md](COVERAGE_XDEBUG.md).

`phpunit.xml` chỉ whitelist **code API**; để báo cáo HTML/text **gồm luôn file test** đang chạy (không kéo các file `*Test.php` khác vào báo cáo 0%), thêm **`--coverage-filter`** trỏ đúng file đó.

- S2:

```bash
php vendor/bin/phpunit tests/S2DoctorManagementTest.php --coverage-html coverage/S2 --coverage-text --coverage-filter tests/S2DoctorManagementTest.php
```

- S5:

```bash
php vendor/bin/phpunit tests/S5BookingManagementTest.php --coverage-html coverage/S5 --coverage-text --coverage-filter tests/S5BookingManagementTest.php
```

- S6:

```bash
php vendor/bin/phpunit tests/S6ServiceSpecialityRoomClinicTest.php --coverage-html coverage/S6 --coverage-text --coverage-filter tests/S6ServiceSpecialityRoomClinicTest.php
```

Mở report: `coverage/S2/index.html` (tương tự S5/S6) → trong cây file tìm `tests/S2DoctorManagementTest.php` (hoặc tương ứng S5/S6).

JUnit + bảng markdown (đủ cột như `unit_test_analysis.md`):

```bash
php vendor/bin/phpunit --log-junit junit.xml
php tools/build-result-table.php junit.xml
```

File kết quả: `S2_S5_S6_BANG_KET_QUA_PHPUNIT.md`.

## Redesign expected theo nghiệp vụ

Tài liệu hướng dẫn thiết kế expected output theo **nghiệp vụ/ngữ nghĩa** (không bám theo source code):
`S2_S5_S6_EXPECTED_REDESIGN.md`.

## Kỹ thuật

- `bootstrap.php`: trỏ `ROOTPATH`/`APPPATH` tới API, nạp autoload + helpers, khởi tạo Pixie SQLite.
- `tests/Support/*Harness`: override `jsonecho()` → ném `JsonEchoExit` để mô phỏng `exit` của framework.
- Reflection gọi các method `private` trong controller (đúng logic production).

Phần **Android** của monorepo: không có trong package này; chỉ test PHP web API.
