-- =====================================================================
-- TaniRaya ERP — Database Schema + Seed Data
-- Jalankan dari cmd (bukan lewat Laravel migration):
--   1. mysql -u root -p -e "CREATE DATABASE taniraya_erp"
--   2. mysql -u root -p taniraya_erp < taniraya_erp.sql
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- roles
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (id, name, slug, description, created_at, updated_at) VALUES
(1, 'Admin', 'admin', 'Akses penuh ke semua menu', NOW(), NOW()),
(2, 'Approver', 'approver', 'Menyetujui / menolak pre-order', NOW(), NOW()),
(3, 'Staf Gudang', 'staf-gudang', 'Kelola stok & pemakaian', NOW(), NOW()),
(4, 'Staf Pembelian', 'staf-pembelian', 'Input transaksi pembelian', NOW(), NOW()),
(5, 'Staf Lapangan', 'staf-lapangan', 'Input pemakaian sparepart', NOW(), NOW());

-- ---------------------------------------------------------------------
-- permissions
-- ---------------------------------------------------------------------
CREATE TABLE permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(150) NOT NULL UNIQUE,
  module VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO permissions (id, name, slug, module) VALUES
(1,  'Lihat pre-order',            'preorder.view',    'preorder'),
(2,  'Ajukan pre-order',           'preorder.create',  'preorder'),
(3,  'Setujui/tolak pre-order',    'preorder.approve', 'preorder'),
(4,  'Lihat pemakaian',            'usage.view',        'usage'),
(5,  'Catat pemakaian',            'usage.create',      'usage'),
(6,  'Lihat pembelian',            'purchase.view',     'purchase'),
(7,  'Catat pembelian',            'purchase.create',   'purchase'),
(8,  'Lihat stok',                 'stock.view',        'stock'),
(9,  'Penyesuaian stok',           'stock.adjust',      'stock'),
(10, 'Lihat master data',          'master.view',       'master'),
(11, 'Tambah/edit master data',    'master.manage',     'master'),
(12, 'Hapus master data',          'master.delete',     'master'),
(13, 'Lihat user',                 'user.view',         'user'),
(14, 'Tambah/edit user',           'user.manage',       'user'),
(15, 'Kelola role & permission',   'role.manage',       'user');

-- ---------------------------------------------------------------------
-- permission_role (pivot)
-- ---------------------------------------------------------------------
CREATE TABLE permission_role (
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin (role 1) = semua permission
INSERT INTO permission_role (role_id, permission_id) SELECT 1, id FROM permissions;

-- Approver (role 2)
INSERT INTO permission_role (role_id, permission_id) VALUES
(2,1),(2,3),(2,4),(2,6),(2,8),(2,10);

-- Staf Gudang (role 3)
INSERT INTO permission_role (role_id, permission_id) VALUES
(3,1),(3,4),(3,5),(3,8),(3,9),(3,10);

-- Staf Pembelian (role 4)
INSERT INTO permission_role (role_id, permission_id) VALUES
(4,1),(4,6),(4,7),(4,8),(4,10);

-- Staf Lapangan (role 5)
INSERT INTO permission_role (role_id, permission_id) VALUES
(5,4),(5,5);

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  phone VARCHAR(30) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  remember_token VARCHAR(100) NULL,
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password default untuk SEMUA user di bawah ini: "password" (wajib diganti setelah login pertama)
INSERT INTO users (id, name, email, password, role_id, phone, is_active, created_at, updated_at) VALUES
(1, 'Dedi Kurniawan', 'dedi.k@taniraya.co.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '0811-1000-0001', 1, NOW(), NOW()),
(2, 'Rina Hartati',   'rina.h@taniraya.co.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '0811-1000-0002', 1, NOW(), NOW()),
(3, 'Budi Santoso',   'budi.s@taniraya.co.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '0811-1000-0003', 1, NOW(), NOW()),
(4, 'Sari Wulandari',  'sari.w@taniraya.co.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '0811-1000-0004', 1, NOW(), NOW()),
(5, 'Agus Prasetyo',   'agus.p@taniraya.co.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, '0811-1000-0005', 0, NOW(), NOW());

-- ---------------------------------------------------------------------
-- sparepart_categories
-- ---------------------------------------------------------------------
CREATE TABLE sparepart_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sparepart_categories (id, name, created_at, updated_at) VALUES
(1,'Filter',NOW(),NOW()),(2,'Belt',NOW(),NOW()),(3,'Pelumas',NOW(),NOW()),
(4,'Rem',NOW(),NOW()),(5,'Selang',NOW(),NOW()),(6,'Kelistrikan',NOW(),NOW()),(7,'Lainnya',NOW(),NOW());

-- ---------------------------------------------------------------------
-- units (satuan)
-- ---------------------------------------------------------------------
CREATE TABLE units (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO units (id, name) VALUES
(1,'Pcs'),(2,'Set'),(3,'Drum'),(4,'Liter'),(5,'Meter');

-- ---------------------------------------------------------------------
-- suppliers
-- ---------------------------------------------------------------------
CREATE TABLE suppliers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact_person VARCHAR(150) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  address TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO suppliers (id, name, contact_person, phone, is_active, created_at, updated_at) VALUES
(1,'CV Sumber Sparepart','Pak Herman','0812-3456-7890',1,NOW(),NOW()),
(2,'Toko Jaya Motor','Ibu Lestari','0813-2345-6789',1,NOW(),NOW()),
(3,'PT Lubrindo Sejahtera','Pak Andri','0812-9988-7766',1,NOW(),NOW()),
(4,'UD Mitra Teknik','Pak Joko','0857-1122-3344',0,NOW(),NOW());

-- ---------------------------------------------------------------------
-- fleets (master armada)
-- ---------------------------------------------------------------------
CREATE TABLE fleets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  type VARCHAR(50) NOT NULL,
  status ENUM('aktif','maintenance','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO fleets (id, code, name, type, status, created_at, updated_at) VALUES
(1,'FR-D8','Truk Angkut','Truk','aktif',NOW(),NOW()),
(2,'TR-12','Traktor','Traktor','aktif',NOW(),NOW()),
(3,'DT-03','Dump Truck','Dump Truck','maintenance',NOW(),NOW()),
(4,'EX-02','Excavator Mini','Excavator','aktif',NOW(),NOW());

-- ---------------------------------------------------------------------
-- spareparts (master sparepart)
-- ---------------------------------------------------------------------
CREATE TABLE spareparts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  dimension VARCHAR(100) NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  min_stock INT NOT NULL DEFAULT 0,
  current_stock INT NOT NULL DEFAULT 0,
  standard_price DECIMAL(15,2) NULL,
  main_supplier_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (category_id) REFERENCES sparepart_categories(id),
  FOREIGN KEY (unit_id) REFERENCES units(id),
  FOREIGN KEY (main_supplier_id) REFERENCES suppliers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO spareparts (id, code, name, category_id, dimension, unit_id, min_stock, current_stock, standard_price, main_supplier_id, created_at, updated_at) VALUES
(1,'SP-0012','Filter Oli Mesin',1,'Ø8cm x 12cm',1,10,6,90000,1,NOW(),NOW()),
(2,'SP-0024','Van Belt Alternator',2,'1150 mm',1,8,22,150000,2,NOW(),NOW()),
(3,'SP-0031','Oli Hidrolik 20L',3,'Drum 20L',3,5,11,1200000,3,NOW(),NOW()),
(4,'SP-0045','Kampas Rem',4,'22cm x 9cm',2,6,3,300000,1,NOW(),NOW()),
(5,'SP-0052','Selang Hidrolik 1"',5,'1" x 3m',1,5,14,220000,1,NOW(),NOW());

-- ---------------------------------------------------------------------
-- preorders
-- ---------------------------------------------------------------------
CREATE TABLE preorders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  sparepart_id BIGINT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  fleet_id BIGINT UNSIGNED NULL,
  needed_date DATE NULL,
  reason TEXT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  requested_by BIGINT UNSIGNED NOT NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at TIMESTAMP NULL,
  approval_notes VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (sparepart_id) REFERENCES spareparts(id),
  FOREIGN KEY (unit_id) REFERENCES units(id),
  FOREIGN KEY (fleet_id) REFERENCES fleets(id),
  FOREIGN KEY (requested_by) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO preorders (id, code, sparepart_id, quantity, unit_id, fleet_id, needed_date, reason, status, requested_by, created_at, updated_at) VALUES
(1,'PO-0001',1,5,1,1,CURDATE(),'stok habis',3,'pending',3,NOW(),NOW()),
(2,'PO-0002',2,10,1,2,CURDATE(),'komponen aus',4,'pending',3,NOW(),NOW());

-- ---------------------------------------------------------------------
-- purchases (pembelian)
-- ---------------------------------------------------------------------
CREATE TABLE purchases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(50) NULL,
  preorder_id BIGINT UNSIGNED NULL,
  sparepart_id BIGINT UNSIGNED NOT NULL,
  supplier_id BIGINT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(15,2) NOT NULL,
  total_price DECIMAL(15,2) NOT NULL,
  purchase_date DATE NOT NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (preorder_id) REFERENCES preorders(id),
  FOREIGN KEY (sparepart_id) REFERENCES spareparts(id),
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- usages (pemakaian)
-- ---------------------------------------------------------------------
CREATE TABLE usages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sparepart_id BIGINT UNSIGNED NOT NULL,
  fleet_id BIGINT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  usage_date DATE NOT NULL,
  notes VARCHAR(255) NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (sparepart_id) REFERENCES spareparts(id),
  FOREIGN KEY (fleet_id) REFERENCES fleets(id),
  FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- stock_adjustments (update stok / stok opname)
-- ---------------------------------------------------------------------
CREATE TABLE stock_adjustments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sparepart_id BIGINT UNSIGNED NOT NULL,
  type ENUM('opname','damaged_lost','return_to_supplier','other') NOT NULL DEFAULT 'opname',
  system_qty_before INT NOT NULL,
  physical_qty INT NOT NULL,
  difference INT NOT NULL,
  reason TEXT NOT NULL,
  adjusted_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (sparepart_id) REFERENCES spareparts(id),
  FOREIGN KEY (adjusted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- stock_movements (ledger stok)
-- ---------------------------------------------------------------------
CREATE TABLE stock_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sparepart_id BIGINT UNSIGNED NOT NULL,
  type ENUM('purchase','usage','adjustment') NOT NULL,
  reference_type VARCHAR(50) NOT NULL,
  reference_id BIGINT UNSIGNED NOT NULL,
  quantity_change INT NOT NULL,
  balance_after INT NOT NULL,
  created_at TIMESTAMP NULL,
  FOREIGN KEY (sparepart_id) REFERENCES spareparts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
