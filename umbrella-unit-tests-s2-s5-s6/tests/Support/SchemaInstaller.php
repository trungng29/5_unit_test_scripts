<?php

declare(strict_types=1);

namespace UmbrellaTests\Support;

use Pixie\Connection;

final class SchemaInstaller
{
    public static function install(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec('PRAGMA foreign_keys = OFF;');

        $tables = [
            'tn_notifications',
            'tn_booking',
            'tn_appointments',
            'tn_doctor_and_service',
            'tn_doctors',
            'tn_patients',
            'tn_services',
            'tn_specialities',
            'tn_rooms',
            'tn_clinics',
        ];
        foreach ($tables as $t) {
            $pdo->exec('DROP TABLE IF EXISTS ' . $t);
        }

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_specialities (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT,
  image TEXT DEFAULT 'default_avatar.jpg'
);
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_rooms (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  location TEXT NOT NULL
);
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_doctors (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL,
  phone TEXT NOT NULL,
  password TEXT,
  name TEXT NOT NULL,
  description TEXT,
  price INTEGER NOT NULL DEFAULT 100000,
  role TEXT NOT NULL,
  active INTEGER NOT NULL DEFAULT 1,
  avatar TEXT DEFAULT '',
  create_at TEXT,
  update_at TEXT,
  speciality_id INTEGER NOT NULL DEFAULT 1,
  room_id INTEGER NOT NULL DEFAULT 1,
  recovery_token TEXT DEFAULT ''
);
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_patients (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT,
  phone TEXT NOT NULL,
  password TEXT,
  name TEXT,
  gender INTEGER DEFAULT 0,
  birthday TEXT,
  address TEXT,
  avatar TEXT,
  create_at TEXT,
  update_at TEXT
);
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_services (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  image TEXT DEFAULT 'default_avatar.jpg',
  description TEXT
);
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_booking (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  doctor_id INTEGER NOT NULL DEFAULT 0,
  patient_id INTEGER NOT NULL,
  service_id INTEGER NOT NULL,
  booking_name TEXT,
  booking_phone TEXT,
  name TEXT,
  gender INTEGER DEFAULT 0,
  birthday TEXT,
  address TEXT,
  reason TEXT,
  appointment_date TEXT NOT NULL,
  appointment_time TEXT NOT NULL,
  status TEXT NOT NULL,
  create_at TEXT,
  update_at TEXT
);
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_appointments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  booking_id INTEGER DEFAULT 0,
  doctor_id INTEGER NOT NULL,
  patient_id INTEGER,
  patient_name TEXT,
  patient_birthday TEXT,
  patient_reason TEXT,
  patient_phone TEXT,
  numerical_order INTEGER DEFAULT 0,
  position INTEGER DEFAULT 0,
  date TEXT,
  appointment_time TEXT,
  status TEXT,
  create_at TEXT,
  update_at TEXT
);
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_notifications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  message TEXT,
  record_id INTEGER,
  record_type TEXT,
  is_read INTEGER DEFAULT 0,
  patient_id INTEGER,
  create_at TEXT,
  update_at TEXT
);
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_doctor_and_service (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  doctor_id INTEGER NOT NULL,
  service_id INTEGER NOT NULL
);
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE tn_clinics (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  address TEXT NOT NULL
);
SQL);

        $pdo->exec('PRAGMA foreign_keys = ON;');
    }

    public static function truncateData(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec('PRAGMA foreign_keys = OFF;');
        foreach ([
            'tn_notifications',
            'tn_booking',
            'tn_appointments',
            'tn_doctor_and_service',
            'tn_doctors',
            'tn_patients',
            'tn_services',
            'tn_specialities',
            'tn_rooms',
            'tn_clinics',
        ] as $t) {
            $pdo->exec('DELETE FROM ' . $t);
        }
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }
}
