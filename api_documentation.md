# API Docs

## Base URL

Tất cả các API đều được đặt trong đường dẫn `/api`.

### Login

```
POST /login

Body:
{
    "email": "admin@example.com",
    "password": "password"
}

Response:
{
    "status": "success",
    "message": "Login successful",
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "role": "admin"
    }
}
```

### Logout

```
POST /logout

Headers:
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "message": "Logged out successfully"
}
```

### Get Current User

```
GET /user

Headers:
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "role": "admin",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
    }
}
```

### Register User (Admin only)

```
POST /register

Headers:
Authorization: Bearer {token}

Body:
{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "password",
    "password_confirmation": "password",
    "role": "student" // Options: "student", "manager", "admin"
}

Response:
{
    "status": "success",
    "message": "User registered successfully",
    "data": {
        "id": 5,
        "name": "New User",
        "email": "newuser@example.com",
        "role": "student",
        "created_at": "2023-07-01T00:00:00.000000Z",
        "updated_at": "2023-07-01T00:00:00.000000Z"
    }
}
```

## Term Management (Admin only)

### List All Terms

```
GET /terms

Headers:
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "name": "2024A",
            "start_date": "2024-01-01",
            "end_date": "2024-05-30",
            "roster_deadline": "2024-01-15",
            "grade_entry_date": "2024-06-14",
            "created_at": "2023-12-01T00:00:00.000000Z",
            "updated_at": "2023-12-01T00:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "2024B",
            "start_date": "2024-06-01",
            "end_date": "2024-10-30",
            "roster_deadline": "2024-06-15",
            "grade_entry_date": "2024-11-14",
            "created_at": "2023-12-01T00:00:00.000000Z",
            "updated_at": "2023-12-01T00:00:00.000000Z"
        }
    ]
}
```

### Get Single Term

```
GET /terms/{id}

Headers:
Authorization: Bearer {token}

URL Parameters:
id: The ID of the term

Response:
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "2024A",
        "start_date": "2024-01-01",
        "end_date": "2024-05-30",
        "roster_deadline": "2024-01-15",
        "grade_entry_date": "2024-06-14",
        "created_at": "2023-12-01T00:00:00.000000Z",
        "updated_at": "2023-12-01T00:00:00.000000Z",
        "courses": [
            {
                "id": 1,
                "code": "JUMP23",
                "subject_name": "Nhảy cao",
                "term_id": 1,
                "manager_id": 2,
                "created_at": "2023-12-15T00:00:00.000000Z",
                "updated_at": "2023-12-15T00:00:00.000000Z"
            }
        ]
    }
}
```

### Create Term

```
POST /terms

Headers:
Authorization: Bearer {token}

Body:
{
    "name": "2025A",
    "start_date": "2025-01-01",
    "end_date": "2025-05-30",
    "roster_deadline": "2025-01-15",
    "grade_entry_date": "2025-06-14"
}

Response:
{
    "status": "success",
    "message": "Term created successfully",
    "data": {
        "id": 3,
        "name": "2025A",
        "start_date": "2025-01-01",
        "end_date": "2025-05-30",
        "roster_deadline": "2025-01-15",
        "grade_entry_date": "2025-06-14",
        "created_at": "2023-12-20T00:00:00.000000Z",
        "updated_at": "2023-12-20T00:00:00.000000Z"
    }
}
```

### Update Term

```
PUT /terms/{id}

Headers:
Authorization: Bearer {token}

URL Parameters:
id: The ID of the term

Body:
{
    "name": "2025A",
    "start_date": "2025-01-10",
    "end_date": "2025-06-10",
    "roster_deadline": "2025-01-24",
    "grade_entry_date": "2025-06-25"
}

Response:
{
    "status": "success",
    "message": "Term updated successfully",
    "data": {
        "id": 3,
        "name": "2025A",
        "start_date": "2025-01-10",
        "end_date": "2025-06-10",
        "roster_deadline": "2025-01-24",
        "grade_entry_date": "2025-06-25",
        "created_at": "2023-12-20T00:00:00.000000Z",
        "updated_at": "2023-12-21T00:00:00.000000Z"
    }
}
```

### Delete Term

```
DELETE /terms/{id}

Headers:
Authorization: Bearer {token}

URL Parameters:
id: The ID of the term

Response:
{
    "status": "success",
    "message": "Term deleted successfully"
}
```

## Course Management (Admin only)

### List All Courses

```
GET /courses

Headers:
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "code": "JUMP23",
            "subject_name": "Nhảy cao",
            "term_id": 1,
            "manager_id": 2,
            "created_at": "2023-12-15T00:00:00.000000Z",
            "updated_at": "2023-12-15T00:00:00.000000Z",
            "term": {
                "id": 1,
                "name": "2024A",
                "start_date": "2024-01-01",
                "end_date": "2024-05-30"
            },
            "manager": {
                "id": 2,
                "name": "Manager User",
                "email": "manager@example.com"
            }
        },
        {
            "id": 2,
            "code": "RUN24",
            "subject_name": "Chạy nhanh",
            "term_id": 1,
            "manager_id": 2,
            "created_at": "2023-12-15T00:00:00.000000Z",
            "updated_at": "2023-12-15T00:00:00.000000Z",
            "term": {
                "id": 1,
                "name": "2024A",
                "start_date": "2024-01-01",
                "end_date": "2024-05-30"
            },
            "manager": {
                "id": 2,
                "name": "Manager User",
                "email": "manager@example.com"
            }
        }
    ]
}
```

### Get Single Course

```
GET /courses/{id}

Headers:
Authorization: Bearer {token}

URL Parameters:
id: The ID of the course

Response:
{
    "status": "success",
    "data": {
        "id": 1,
        "code": "JUMP23",
        "subject_name": "Nhảy cao",
        "term_id": 1,
        "manager_id": 2,
        "created_at": "2023-12-15T00:00:00.000000Z",
        "updated_at": "2023-12-15T00:00:00.000000Z",
        "term": {
            "id": 1,
            "name": "2024A",
            "start_date": "2024-01-01",
            "end_date": "2024-05-30",
            "roster_deadline": "2024-01-15",
            "grade_entry_date": "2024-06-14"
        },
        "manager": {
            "id": 2,
            "name": "Manager User",
            "email": "manager@example.com",
            "role": "manager"
        }
    }
}
```

### Create Course

```
POST /courses

Headers:
Authorization: Bearer {token}

Body:
{
    "code": "SWIM25",
    "subject_name": "Bơi lội",
    "term_id": 1,
    "manager_id": 2
}

Response:
{
    "status": "success",
    "message": "Course created successfully",
    "data": {
        "id": 3,
        "code": "SWIM25",
        "subject_name": "Bơi lội",
        "term_id": 1,
        "manager_id": 2,
        "created_at": "2023-12-20T00:00:00.000000Z",
        "updated_at": "2023-12-20T00:00:00.000000Z",
        "term": {
            "id": 1,
            "name": "2024A",
            "start_date": "2024-01-01",
            "end_date": "2024-05-30"
        },
        "manager": {
            "id": 2,
            "name": "Manager User",
            "email": "manager@example.com"
        }
    }
}
```

### Update Course

```
PUT /courses/{id}

Headers:
Authorization: Bearer {token}

URL Parameters:
id: The ID of the course

Body:
{
    "code": "SWIM26",
    "subject_name": "Bơi lội nâng cao",
    "term_id": 1,
    "manager_id": 2
}

Response:
{
    "status": "success",
    "message": "Course updated successfully",
    "data": {
        "id": 3,
        "code": "SWIM26",
        "subject_name": "Bơi lội nâng cao",
        "term_id": 1,
        "manager_id": 2,
        "created_at": "2023-12-20T00:00:00.000000Z",
        "updated_at": "2023-12-21T00:00:00.000000Z",
        "term": {
            "id": 1,
            "name": "2024A",
            "start_date": "2024-01-01",
            "end_date": "2024-05-30"
        },
        "manager": {
            "id": 2,
            "name": "Manager User",
            "email": "manager@example.com"
        }
    }
}
```

### Delete Course

```
DELETE /courses/{id}

Headers:
Authorization: Bearer {token}

URL Parameters:
id: The ID of the course

Response:
{
    "status": "success",
    "message": "Course deleted successfully"
}
```

### Get Course Students

```
GET /courses/{id}/students

Headers:
Authorization: Bearer {token}

URL Parameters:
id: The ID of the course

Response:
{
    "status": "success",
    "data": [
        {
            "id": 3,
            "name": "Student One",
            "email": "student1@example.com",
            "grade": 85.5,
            "status": "enrolled",
            "notes": "Performing well"
        },
        {
            "id": 4,
            "name": "Student Two",
            "email": "student2@example.com",
            "grade": null,
            "status": "enrolled",
            "notes": null
        }
    ]
}
```

### Enroll Student in Course

```
POST /courses/{id}/students

Headers:
Authorization: Bearer {token}

URL Parameters:
id: The ID of the course

Body:
{
    "user_id": 5
}

Response:
{
    "status": "success",
    "message": "Student enrolled successfully",
    "data": {
        "id": 10,
        "user_id": 5,
        "course_id": 1,
        "grade": null,
        "status": "enrolled",
        "notes": null,
        "created_at": "2023-12-22T00:00:00.000000Z",
        "updated_at": "2023-12-22T00:00:00.000000Z"
    }
}
```

### Update Student Grade

```
PUT /courses/{courseId}/students/{userId}/grade

Headers:
Authorization: Bearer {token}

URL Parameters:
courseId: The ID of the course
userId: The ID of the student

Body:
{
    "grade": 90.5,
    "status": "completed",
    "notes": "Excellent performance"
}

Response:
{
    "status": "success",
    "message": "Student grade updated successfully",
    "data": {
        "id": 10,
        "user_id": 5,
        "course_id": 1,
        "grade": 90.5,
        "status": "completed",
        "notes": "Excellent performance",
        "created_at": "2023-12-22T00:00:00.000000Z",
        "updated_at": "2023-12-23T00:00:00.000000Z"
    }
}
```

## Error Responses

### Authentication Error

```
{
    "status": "error",
    "message": "Unauthenticated"
}
```

### Validation Error

```
{
    "status": "error",
    "message": "Validation error",
    "errors": {
        "name": [
            "The name field is required."
        ],
        "start_date": [
            "The start date field is required."
        ]
    }
}
```

### Resource Not Found

```
{
    "status": "error",
    "message": "Resource not found"
}
```

### Business Logic Error

```
{
    "status": "error",
    "message": "Term dates overlap with an existing term"
}
```

### JWT Token Error

```
{
    "status": "error",
    "message": "Token has expired"
}
```

## Notes

1. Tất cả các endpoint ngoại trừ `/login` đều yêu cầu xác thực thông qua token JWT trong header `Authorization`.
2. Tất cả các endpoint quản lý học kỳ và khóa học chỉ dành cho admin
3. date format: `YYYY-MM-DD`.
4. term name format: `YYYY[A-Z]` (ví dụ: `2024A`).
5. term date validation:
   - end date > start date
   - roster deadline >= start date + 2 weeks
   - roster deadline < end date
   - grade entry date >= end date + 2 weeks
6. các học kỳ không được chồng ngày.
7. Không thể xóa khóa học nếu có sinh viên đã đăng ký. (có thể update sau)
8. Không thể xóa học kỳ nếu có khóa học liên quan. (có thể update sau)
9. chỉ có thể thêm sinh viên vào lớp trước thời gian hạn chót đăng ký của học kỳ.

## Quân tư trang và Phụ cấp

### Quân tư trang (Học viên)

#### Lấy danh sách quân tư trang của học viên

```
GET /api/student/equipment

Headers:
Authorization: Bearer {token}

Query Parameters:
year: Năm cần lấy dữ liệu (tùy chọn)

Response:
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "user_id": 5,
            "distribution_id": 1,
            "received": true,
            "received_at": "2024-04-10T08:30:00.000000Z",
            "notes": null,
            "created_at": "2024-04-01T00:00:00.000000Z",
            "updated_at": "2024-04-10T08:30:00.000000Z",
            "distribution": {
                "id": 1,
                "year": 2024,
                "equipment_type_id": 1,
                "quantity": 2,
                "created_at": "2024-03-15T00:00:00.000000Z",
                "updated_at": "2024-03-15T00:00:00.000000Z",
                "equipment_type": {
                    "id": 1,
                    "name": "Quần áo K03",
                    "description": "Quần áo kaki mùa hè",
                    "created_at": "2024-03-01T00:00:00.000000Z",
                    "updated_at": "2024-03-01T00:00:00.000000Z"
                }
            }
        },
        {
            "id": 2,
            "user_id": 5,
            "distribution_id": 2,
            "received": false,
            "received_at": null,
            "notes": null,
            "created_at": "2024-04-01T00:00:00.000000Z",
            "updated_at": "2024-04-01T00:00:00.000000Z",
            "distribution": {
                "id": 2,
                "year": 2024,
                "equipment_type_id": 2,
                "quantity": 2,
                "created_at": "2024-03-15T00:00:00.000000Z",
                "updated_at": "2024-03-15T00:00:00.000000Z",
                "equipment_type": {
                    "id": 2,
                    "name": "Giày vải",
                    "description": "Giày vải quân đội",
                    "created_at": "2024-03-01T00:00:00.000000Z",
                    "updated_at": "2024-03-01T00:00:00.000000Z"
                }
            }
        }
    ]
}
```

#### Cập nhật trạng thái nhận quân tư trang

```
PUT /api/student/equipment/{receiptId}

Headers:
Authorization: Bearer {token}

Body:
{
    "received": true,
    "notes": "Đã nhận đầy đủ"
}

Response:
{
    "status": "success",
    "data": {
        "id": 2,
        "user_id": 5,
        "distribution_id": 2,
        "received": true,
        "received_at": "2024-04-10T10:15:22.000000Z",
        "notes": "Đã nhận đầy đủ",
        "created_at": "2024-04-01T00:00:00.000000Z",
        "updated_at": "2024-04-10T10:15:22.000000Z"
    },
    "message": "Trạng thái nhận quân tư trang đã được cập nhật"
}
```

### Phụ cấp (Học viên)

#### Lấy danh sách phụ cấp của học viên

```
GET /api/student/allowances

Headers:
Authorization: Bearer {token}

Query Parameters:
month: Tháng cần lấy dữ liệu (tùy chọn)
year: Năm cần lấy dữ liệu (tùy chọn)

Response:
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "user_id": 5,
            "month": 7,
            "year": 2024,
            "amount": "2000000.00",
            "received": true,
            "received_at": "2024-07-03T08:45:00.000000Z",
            "notes": null,
            "created_at": "2024-07-01T00:00:00.000000Z",
            "updated_at": "2024-07-03T08:45:00.000000Z"
        },
        {
            "id": 2,
            "user_id": 5,
            "month": 8,
            "year": 2024,
            "amount": "2000000.00",
            "received": false,
            "received_at": null,
            "notes": null,
            "created_at": "2024-08-01T00:00:00.000000Z",
            "updated_at": "2024-08-01T00:00:00.000000Z"
        }
    ]
}
```

#### Cập nhật trạng thái nhận phụ cấp

```
PUT /api/student/allowances/{allowanceId}

Headers:
Authorization: Bearer {token}

Body:
{
    "received": true,
    "notes": "Đã nhận đầy đủ"
}

Response:
{
    "status": "success",
    "data": {
        "id": 2,
        "user_id": 5,
        "month": 8,
        "year": 2024,
        "amount": "2000000.00",
        "received": true,
        "received_at": "2024-08-05T09:30:15.000000Z",
        "notes": "Đã nhận đầy đủ",
        "created_at": "2024-08-01T00:00:00.000000Z",
        "updated_at": "2024-08-05T09:30:15.000000Z"
    },
    "message": "Trạng thái nhận phụ cấp đã được cập nhật"
}
```

### Quản lý quân tư trang (Admin)

#### Lấy danh sách loại quân tư trang

```
GET /api/admin/equipment/types

Headers:
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "name": "Quần áo K03",
            "description": "Quần áo kaki mùa hè",
            "created_at": "2024-03-01T00:00:00.000000Z",
            "updated_at": "2024-03-01T00:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "Giày vải",
            "description": "Giày vải quân đội",
            "created_at": "2024-03-01T00:00:00.000000Z",
            "updated_at": "2024-03-01T00:00:00.000000Z"
        }
    ]
}
```

#### Tạo loại quân tư trang mới

```
POST /api/admin/equipment/types

Headers:
Authorization: Bearer {token}

Body:
{
    "name": "Mũ kêpi",
    "description": "Mũ kêpi quân đội"
}

Response:
{
    "status": "success",
    "data": {
        "id": 3,
        "name": "Mũ kêpi",
        "description": "Mũ kêpi quân đội",
        "created_at": "2024-04-10T11:20:00.000000Z",
        "updated_at": "2024-04-10T11:20:00.000000Z"
    },
    "message": "Loại quân tư trang được tạo thành công"
}
```

#### Lấy danh sách phân phối quân tư trang

```
GET /api/admin/equipment/distributions

Headers:
Authorization: Bearer {token}

Query Parameters:
year: Năm cần lấy dữ liệu (tùy chọn)

Response:
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "year": 2024,
            "equipment_type_id": 1,
            "quantity": 2,
            "created_at": "2024-03-15T00:00:00.000000Z",
            "updated_at": "2024-03-15T00:00:00.000000Z",
            "equipment_type": {
                "id": 1,
                "name": "Quần áo K03",
                "description": "Quần áo kaki mùa hè",
                "created_at": "2024-03-01T00:00:00.000000Z",
                "updated_at": "2024-03-01T00:00:00.000000Z"
            }
        },
        {
            "id": 2,
            "year": 2024,
            "equipment_type_id": 2,
            "quantity": 2,
            "created_at": "2024-03-15T00:00:00.000000Z",
            "updated_at": "2024-03-15T00:00:00.000000Z",
            "equipment_type": {
                "id": 2,
                "name": "Giày vải",
                "description": "Giày vải quân đội",
                "created_at": "2024-03-01T00:00:00.000000Z",
                "updated_at": "2024-03-01T00:00:00.000000Z"
            }
        }
    ]
}
```

#### Tạo phân phối quân tư trang mới

```
POST /api/admin/equipment/distributions

Headers:
Authorization: Bearer {token}

Body:
{
    "year": 2024,
    "equipment_type_id": 3,
    "quantity": 1
}

Response:
{
    "status": "success",
    "data": {
        "id": 3,
        "year": 2024,
        "equipment_type_id": 3,
        "quantity": 1,
        "created_at": "2024-04-10T11:30:00.000000Z",
        "updated_at": "2024-04-10T11:30:00.000000Z"
    },
    "message": "Phân phối quân tư trang được tạo thành công"
}
```

#### Tạo biên nhận quân tư trang cho học viên

```
POST /api/admin/equipment/receipts

Headers:
Authorization: Bearer {token}

Body:
{
    "distribution_id": 3,
    "student_ids": [5, 6, 7, 8]
}

Response:
{
    "status": "success",
    "data": {
        "count": 4
    },
    "message": "Biên nhận quân tư trang được tạo thành công"
}
```

### Quản lý phụ cấp (Admin)

#### Lấy danh sách phụ cấp

```
GET /api/admin/allowances

Headers:
Authorization: Bearer {token}

Query Parameters:
month: Tháng cần lấy dữ liệu (tùy chọn)
year: Năm cần lấy dữ liệu (tùy chọn)

Response:
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "user_id": 5,
            "month": 7,
            "year": 2024,
            "amount": "2000000.00",
            "received": true,
            "received_at": "2024-07-03T08:45:00.000000Z",
            "notes": null,
            "created_at": "2024-07-01T00:00:00.000000Z",
            "updated_at": "2024-07-03T08:45:00.000000Z",
            "student": {
                "id": 5,
                "name": "Nguyễn Văn A",
                "email": "nguyenvana@example.com",
                "role": "student"
            }
        },
        {
            "id": 2,
            "user_id": 6,
            "month": 7,
            "year": 2024,
            "amount": "2000000.00",
            "received": false,
            "received_at": null,
            "notes": null,
            "created_at": "2024-07-01T00:00:00.000000Z",
            "updated_at": "2024-07-01T00:00:00.000000Z",
            "student": {
                "id": 6,
                "name": "Trần Văn B",
                "email": "tranvanb@example.com",
                "role": "student"
            }
        }
    ]
}
```

#### Tạo phụ cấp hàng loạt

```
POST /api/admin/allowances/bulk

Headers:
Authorization: Bearer {token}

Body:
{
    "student_ids": [5, 6, 7, 8],
    "month": 8,
    "year": 2024,
    "amount": 2000000
}

Response:
{
    "status": "success",
    "data": {
        "count": 4
    },
    "message": "Phụ cấp hàng tháng được tạo thành công"
}
```

#### Lấy danh sách học viên chưa nhận phụ cấp

```
GET /api/admin/allowances/pending

Headers:
Authorization: Bearer {token}

Body:
{
    "month": 7,
    "year": 2024
}

Response:
{
    "status": "success",
    "data": [
        {
            "student": {
                "id": 6,
                "name": "Trần Văn B",
                "email": "tranvanb@example.com",
                "role": "student",
                "student_class": {
                    "class": {
                        "id": 1,
                        "name": "40TS1",
                        "manager_id": 2
                    }
                }
            },
            "allowance": {
                "id": 2,
                "user_id": 6,
                "month": 7,
                "year": 2024,
                "amount": "2000000.00",
                "received": false,
                "received_at": null,
                "notes": null,
                "created_at": "2024-07-01T00:00:00.000000Z",
                "updated_at": "2024-07-01T00:00:00.000000Z"
            }
        }
    ],
    "message": "Danh sách học viên chưa nhận phụ cấp"
}
```