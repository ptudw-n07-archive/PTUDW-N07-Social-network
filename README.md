# ARCHIVE

ARCHIVE là đồ án môn học chủ đề mạng xã hội, lấy cảm hứng từ Threads của Meta nhưng rút gọn phạm vi để phù hợp demo, báo cáo và chấm điểm môn học.

## Tech stack thực tế

- PHP thuần theo mô hình MVC thư mục (`App/`, `Config/`, `Public/`)
- PDO + MySQL
- Bootstrap cho giao diện
- AJAX + JSON cho các tương tác chính

## Cấu trúc chính

- `Public/index.php`: entrypoint chính
- `Public/health.php`: health endpoint cho Railway
- `App/Controllers/*`: controller xử lý request
- `App/Models/*`: truy cập dữ liệu
- `App/Views/*`: giao diện
- `Config/Database.php`: config runtime, env helpers, DB connection
- `run-app.sh`: startup script cho Railway
- `railway.json`: config deploy Railway

## Biến môi trường cần có

Xem file `.env.example`.

Tối thiểu cần set:

- `APP_URL`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `UPLOADS_ROOT`

Ví dụ local:

```env
APP_URL=http://localhost:8080
DB_HOST=100.76.147.122
DB_PORT=3306
DB_NAME=db_archive
DB_USER=root
DB_PASSWORD=
UPLOADS_ROOT=Public/uploads
```

> ⚠️ `UPLOADS_ROOT` phải là `Public/uploads` để ảnh upload hiển thị được.
> Nếu để `storage/uploads`, ảnh sẽ lưu ở `storage/uploads/posts/` nhưng code tìm trong `Public/uploads/posts/` → ảnh không hiện.

> 💡 **Local dev**: Khi chạy localhost, tài khoản đăng ký mới được **tự động kích hoạt** — không cần email xác thực.
> Trên Railway (production), vẫn gửi email kích hoạt bình thường.

## Yêu cầu

- **PHP 8.0+** (có CLI)
- **MySQL 8.0+**
- **Composer** (dùng `composer.phar` kèm trong repo)

## Chạy local

### 1. Cài dependencies

```bash
php composer.phar install
```

### 2. Tạo database

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS db_archive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root db_archive < db_archive_final.sql
```

> Nếu chưa có MySQL, cài qua Homebrew: `brew install mysql && brew services start mysql`

### 3. Cấu hình env

```bash
cp .env.example .env
```

Sửa file `.env` với thông tin phù hợp (xem mục **Biến môi trường** bên trên).

### 4. Chạy server

```bash
php -S 0.0.0.0:8080 -t . Public/index.php
```

### 5. Mở trình duyệt

```text
http://localhost:8080
```

### (Tuỳ chọn) Đăng nhập bằng Google

1. Vào [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Tạo OAuth Client ID loại **Web application**
3. Thêm `http://localhost:8080/App/Controllers/GoogleLoginController.php` vào **Authorized redirect URIs**
4. Copy `Client ID` và `Client Secret` vào `.env`:

```env
GOOGLE_LOGIN_CLIENT_ID=your-client-id
GOOGLE_LOGIN_CLIENT_SECRET=your-client-secret
GOOGLE_LOGIN_REDIRECT_URI=http://localhost:8080/App/Controllers/GoogleLoginController.php
```

## Trial deploy lên Railway

Repo hiện đã được chuẩn bị để deploy thử Railway bằng Railpack.

### 1. Tạo service từ repo này

- Push code lên GitHub/GitLab
- Tạo Railway project/service mới từ repo

### 2. Set biến môi trường trên Railway

Set ít nhất:

```env
APP_URL=https://<your-railway-domain>
DB_HOST=<your-db-host>
DB_PORT=3306
DB_NAME=<your-db-name>
DB_USER=<your-db-user>
DB_PASSWORD=<your-db-password>
UPLOADS_ROOT=/data/uploads
```

> Nếu chưa gắn volume, có thể tạm dùng `UPLOADS_ROOT=storage/uploads` để thử deploy, nhưng file upload sẽ không bền vững qua các lần redeploy.

### 3. Gắn volume nếu muốn giữ ảnh upload

Khuyến nghị mount Railway volume sao cho:

```text
/data/uploads
```

và set:

```env
UPLOADS_ROOT=/data/uploads
```

Runtime script sẽ tự tạo symlink:

```text
Public/uploads -> UPLOADS_ROOT
```

### 4. Health check

Railway đang được cấu hình health check tại:

```text
/Public/health.php
```

### 5. Start command

Repo dùng:

```text
sh ./run-app.sh
```

được khai báo trong `railway.json`.

## Ghi chú

- `Public/uploads` không commit vì được tạo runtime bằng symlink
- `storage/uploads/*` có `.gitkeep` để giữ cấu trúc thư mục
- `*.sql` không push lên git, cần giữ file để người khác import database
- Google Login cần OAuth Client ID riêng từ Google Cloud Console
