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