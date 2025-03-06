## Yêu cầu hệ thống
- PHP 8.4.4
- Composer CLI

## Các bước cài đặt

### 1.Clone dự án

```bash
git clone https://github.com/hieuleminh03/backend-mili-sys
cd backend-mili-sys
```

### 2. Cài đặt các dependency PHP

```bash
composer install
```

### 3. Cấu hình môi trường
```bash
cp .env.example .env
php artisan key:generate
```
### 4. Cấu hình database
**Note: hiện tại đang sử dụng sqlite, không cần config db ngoài**
- Mở file `.env` và cập nhật các thông tin database:

### 5. Chạy migration và seeder cho DB
```bash
php artisan migrate
php artisan db:seed
```
### 6. Khởi chạy dự án
```bash
php artisan serve
```
