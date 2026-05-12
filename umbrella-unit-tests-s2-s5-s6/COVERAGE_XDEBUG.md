# Coverage dung theo tung module S2 / S5 / S6

Coverage co gia tri QA phai do tren business code that, khong do tren file test.

File test khong duoc dua vao coverage.

## Cau hinh hien tai

Project da tach rieng:

- `phpunit-s2.xml`
- `phpunit-s5.xml`
- `phpunit-s6.xml`

`phpunit.xml` goc chi de chay test chung, khong whitelist coverage nua.

## Danh sach file duoc do coverage

### S2

- `api/app/controllers/DoctorController.php`
- `api/app/controllers/DoctorsController.php`
- `api/app/controllers/isDoctorBusyController.php`
- `api/app/helpers/common.helper.php`
- `api/app/helpers/email.helper.php`
- `api/app/core/Controller.php`

Ly do:

- `DoctorController.php`, `DoctorsController.php`, `isDoctorBusyController.php` la controller ma test S2 goi truc tiep
- `common.helper.php` chua validation/business rule nhu `isVietnameseName`, `isNumber`
- `email.helper.php` lien quan flow tao doctor moi trong `DoctorsController::save()`
- `Controller.php` la base controller business dung chung

### S5

- `api/app/controllers/BookingsController.php`
- `api/app/controllers/BookingController.php`
- `api/app/controllers/PatientBookingsController.php`
- `api/app/controllers/PatientBookingController.php`
- `api/app/helpers/common.helper.php`
- `api/app/core/Controller.php`

Ly do:

- Day la 4 controller booking ma test S5 cover truc tiep
- `common.helper.php` chua rule validate gio kham, ten, phone, birthday, address

### S6

- `api/app/controllers/SpecialitiesController.php`
- `api/app/controllers/SpecialityController.php`
- `api/app/controllers/ServicesController.php`
- `api/app/controllers/ServiceController.php`
- `api/app/controllers/RoomsController.php`
- `api/app/controllers/RoomController.php`
- `api/app/controllers/ClinicsController.php`
- `api/app/controllers/ClinicController.php`
- `api/app/helpers/common.helper.php`
- `api/app/core/Controller.php`

Ly do:

- Day la toan bo controller CRUD/list thuoc module S6 ma test dang goi truc tiep
- `common.helper.php` chua validation ten clinic, address va cac business rule dung chung

## Chay coverage

Chay trong thu muc:

```powershell
cd c:\laragon\www\umbrella-unit-tests-s2-s5-s6
```

### S2

```powershell
php vendor\bin\phpunit -c phpunit-s2.xml --coverage-html coverage\S2 --coverage-text
```

### S5

```powershell
php vendor\bin\phpunit -c phpunit-s5.xml --coverage-html coverage\S5 --coverage-text
```

### S6

```powershell
php vendor\bin\phpunit -c phpunit-s6.xml --coverage-html coverage\S6 --coverage-text
```

## Ket qua mong doi

Bao cao S2 se khong con liet ke:

- `AppointmentController.php 0%`
- `LoginController.php 0%`
- `DrugController.php 0%`

Ma chi hien cac file lien quan module S2.

Tuong tu cho S5 va S6.

## Kiem tra Xdebug

```powershell
php -m | findstr /I xdebug
php -r "var_dump(extension_loaded('xdebug'));"
```

Neu chua co Xdebug, kiem tra `php --ini` va bat `zend_extension` trong dung `php.ini` cua PHP CLI.
