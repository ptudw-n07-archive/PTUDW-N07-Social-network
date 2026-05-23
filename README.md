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
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=db_archive
DB_USER=root
DB_PASSWORD=
UPLOADS_ROOT=storage/uploads
```

## Chạy local

Nếu máy có PHP CLI:

```bash
cp .env.example .env
php -S 0.0.0.0:8080 -t . Public/index.php
```

Mở trình duyệt tại:

```text
http://localhost:8080
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
- `db_archive.sql` là dữ liệu hỗ trợ/import thủ công, không phải phần bắt buộc của deploy flow
