# Contribution Repos - API Documentation

## Phase 1: Authentication & Member Management

### Base URL
```
http://localhost:3000/api (development)
https://api.contributions.wak.link (production)
```

### Health Check

#### GET /health
Check API health and database connectivity.

**Response (200)**
```json
{
  "status": "healthy",
  "timestamp": "2024-07-29T14:30:00Z",
  "database": "connected"
}
```

**Response (503 - Unhealthy)**
```json
{
  "status": "unhealthy",
  "timestamp": "2024-07-29T14:30:00Z",
  "database": "disconnected",
  "error": "connect ECONNREFUSED"
}
```

---

## Authentication Endpoints

### 1. Register

#### POST /auth/register
Create a new member account.

**Request Body**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+254712345678",
  "idNumber": "12345678"
}
```

**Required Fields**
- `email` (string, valid email)
- `password` (string, min 8 characters)
- `firstName` (string, not empty)
- `lastName` (string, not empty)

**Optional Fields**
- `phone` (string, valid phone)
- `idNumber` (string)

**Response (201)**
```json
{
  "message": "Member registered successfully",
  "member": {
    "id": 1,
    "email": "john@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "role": "member"
  }
}
```

**Response (400 - Validation Error)**
```json
{
  "message": "Validation error",
  "errors": [
    {
      "field": "email",
      "message": "Invalid email format"
    }
  ]
}
```

**Response (400 - Duplicate Email)**
```json
{
  "message": "Email already registered",
  "field": "email"
}
```

---

### 2. Login

#### POST /auth/login
Authenticate member and get access token.

**Request Body**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

**Response (200)**
```json
{
  "message": "Login successful",
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "member": {
    "id": 1,
    "email": "john@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "role": "member"
  }
}
```

**Response (401)**
```json
{
  "message": "Invalid credentials"
}
```

**Response (401 - Inactive Account)**
```json
{
  "message": "Account is inactive"
}
```

**Note:** Refresh token is set as httpOnly cookie automatically.

---

### 3. Refresh Token

#### POST /auth/refresh
Get a new access token using refresh token.

**Request Body**
```json
{
  "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (200)**
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (401)**
```json
{
  "message": "Invalid token or inactive member"
}
```

---

### 4. Change Password

#### POST /auth/change-password
Update member password.

**Authorization Required:** `Bearer {accessToken}`

**Request Body**
```json
{
  "oldPassword": "SecurePass123",
  "newPassword": "NewSecurePass456"
}
```

**Required Fields**
- `oldPassword` (string)
- `newPassword` (string, min 8 chars, different from old)

**Response (200)**
```json
{
  "message": "Password changed successfully"
}
```

**Response (400 - Wrong Old Password)**
```json
{
  "message": "Current password is incorrect"
}
```

**Response (400 - Same as Old)**
```json
{
  "message": "New password must be different from current password"
}
```

---

### 5. Logout

#### POST /auth/logout
Clear authentication session.

**Authorization Required:** `Bearer {accessToken}`

**Response (200)**
```json
{
  "message": "Logged out successfully"
}
```

---

## Member Endpoints

### 1. Get Profile

#### GET /members/profile
Get authenticated member's profile.

**Authorization Required:** `Bearer {accessToken}`

**Response (200)**
```json
{
  "member": {
    "id": 1,
    "email": "john@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+254712345678",
    "idNumber": "12345678",
    "role": "member",
    "isActive": true,
    "lastLogin": "2024-07-29T14:30:00Z",
    "createdAt": "2024-07-25T10:00:00Z",
    "updatedAt": "2024-07-29T14:30:00Z"
  }
}
```

**Response (401)**
```json
{
  "message": "Invalid or expired token"
}
```

---

### 2. Update Profile

#### PUT /members/profile
Update authenticated member's profile information.

**Authorization Required:** `Bearer {accessToken}`

**Request Body**
```json
{
  "firstName": "Jonathan",
  "lastName": "Doe",
  "phone": "+254712345679",
  "idNumber": "12345679"
}
```

**Allowed Fields**
- `firstName` (string, optional)
- `lastName` (string, optional)
- `phone` (string, optional)
- `idNumber` (string, optional)

**Response (200)**
```json
{
  "message": "Profile updated successfully",
  "member": {
    "id": 1,
    "email": "john@example.com",
    "firstName": "Jonathan",
    "lastName": "Doe",
    "phone": "+254712345679",
    "idNumber": "12345679",
    "role": "member",
    "isActive": true,
    "createdAt": "2024-07-25T10:00:00Z",
    "updatedAt": "2024-07-29T14:40:00Z"
  }
}
```

**Response (400 - Invalid Phone)**
```json
{
  "errors": [
    {
      "field": "phone",
      "message": "Invalid phone number"
    }
  ]
}
```

---

### 3. Get Member by ID

#### GET /members/:id
Get specific member's profile (with admin access).

**Authorization Required:** `Bearer {accessToken}`

**Parameters**
- `id` (integer, path parameter) - Member ID

**Response (200)**
```json
{
  "member": {
    "id": 2,
    "email": "jane@example.com",
    "firstName": "Jane",
    "lastName": "Smith",
    "phone": "+254712345680",
    "role": "member",
    "createdAt": "2024-07-25T11:00:00Z"
  }
}
```

**Response (404)**
```json
{
  "message": "Member not found"
}
```

---

### 4. Get All Members (Admin Only)

#### GET /members
List all members with pagination.

**Authorization Required:** `Bearer {accessToken}`
**Role Required:** `admin` or `super_admin`

**Query Parameters**
- `limit` (integer, optional, default: 10) - Items per page
- `offset` (integer, optional, default: 0) - Pagination offset

**Response (200)**
```json
{
  "total": 45,
  "members": [
    {
      "id": 1,
      "email": "john@example.com",
      "firstName": "John",
      "lastName": "Doe",
      "role": "member",
      "isActive": true,
      "createdAt": "2024-07-25T10:00:00Z"
    },
    {
      "id": 2,
      "email": "jane@example.com",
      "firstName": "Jane",
      "lastName": "Smith",
      "role": "member",
      "isActive": true,
      "createdAt": "2024-07-25T11:00:00Z"
    }
  ]
}
```

**Response (403 - Insufficient Permissions)**
```json
{
  "message": "Insufficient permissions"
}
```

---

## Authentication Headers

All protected endpoints require:
```
Authorization: Bearer {accessToken}
```

**Example:**
```bash
curl -X GET http://localhost:3000/api/members/profile \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

---

## Error Responses

### 400 Bad Request
```json
{
  "message": "Validation error",
  "errors": [...]
}
```

### 401 Unauthorized
```json
{
  "message": "Invalid or expired token"
}
```

### 403 Forbidden
```json
{
  "message": "Insufficient permissions"
}
```

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

### 500 Internal Server Error
```json
{
  "message": "Internal server error"
}
```

---

## Token Details

### Access Token
- **Expiry:** 15 minutes
- **Usage:** Sent in `Authorization: Bearer` header
- **Payload:**
  ```json
  {
    "id": 1,
    "email": "john@example.com",
    "role": "member",
    "iat": 1690365000,
    "exp": 1690365900
  }
  ```

### Refresh Token
- **Expiry:** 7 days
- **Storage:** httpOnly cookie (secure)
- **Usage:** Sent in request body to `/auth/refresh`
- **Payload:**
  ```json
  {
    "id": 1,
    "iat": 1690365000,
    "exp": 1691057400
  }
  ```

---

## Rate Limiting (Future)

Future phases will implement rate limiting:
- 100 requests per minute per IP
- 1000 requests per hour per authenticated user

---

## CORS Configuration

**Allowed Origins:**
- Development: `http://localhost:5173`
- Production: `https://contributions.wak.link`

**Allowed Methods:** GET, POST, PUT, DELETE, OPTIONS

**Allowed Headers:** Content-Type, Authorization

**Credentials:** Allowed

---

## Testing with cURL

### Register
```bash
curl -X POST http://localhost:3000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "SecurePass123",
    "firstName": "John",
    "lastName": "Doe"
  }'
```

### Login
```bash
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "SecurePass123"
  }'
```

### Get Profile
```bash
curl -X GET http://localhost:3000/api/members/profile \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

---

## Upcoming Phase 2 Endpoints

- `POST /contributions` - Create contribution
- `GET /contributions` - List contributions
- `POST /payments/initiate` - Initiate M-Pesa payment
- `POST /webhooks/mpesa` - M-Pesa payment callback

---

## Support

For API issues or questions, contact: api-support@contributions.wak.link
