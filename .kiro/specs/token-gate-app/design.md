# Design Document - Token Gate Application

## Overview

Token Gate adalah aplikasi web berbasis PHP native yang mengimplementasikan sistem autentikasi berbasis token untuk mengontrol akses siswa ke URL ujian. Aplikasi ini menggunakan arsitektur sederhana dengan pemisahan concern antara presentasi, logika bisnis, dan data access layer.

### Technology Stack
- **Backend**: PHP 7.4+ (Native, tanpa framework)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (minimal)
- **Web Server**: Apache/Nginx dengan PHP support

## Architecture

### High-Level Architecture

```
┌─────────────┐
│   Student   │
└──────┬──────┘
       │
       ▼
┌─────────────────┐      ┌──────────────┐
│  index.php      │─────▶│ validate.php │
│  (Student       │      │  (Token      │
│   Portal)       │◀─────│  Validator)  │
└─────────────────┘      └──────┬───────┘
                                │
                                ▼
                         ┌──────────────┐
                         │   MySQL DB   │
                         │ active_token │
                         └──────┬───────┘
                                │
       ┌────────────────────────┼────────────────────┐
       │                        │                    │
       ▼                        ▼                    ▼
┌─────────────┐         ┌──────────────┐    ┌──────────────┐
│ admin.php   │         │rotate_token  │    │  config.php  │
│ (Admin      │────────▶│    .php      │    │ (Database &  │
│  Panel)     │         │ (Token       │    │  Config)     │
└─────────────┘         │  Rotator)    │    └──────────────┘
                        └──────────────┘
```

### Request Flow

#### Student Authentication Flow
1. Student accesses `index.php`
2. Student submits token via POST to `validate.php`
3. `validate.php` queries database for current active token
4. If valid: Server-side redirect to exam URL
5. If invalid: Redirect back to `index.php?error=1`

#### Admin Token Management Flow
1. Admin accesses `admin.php`
2. Admin authenticates with hardcoded credentials
3. Admin views current active token
4. Admin triggers manual token rotation
5. `rotate_token.php` generates new token and updates database

#### Automated Token Rotation Flow
1. Cron job triggers `rotate_token.php` at scheduled interval
2. Script generates cryptographically secure random token
3. Database updated with new token
4. Old token becomes invalid immediately

## Components and Interfaces

### 1. config.php (Configuration Component)

**Purpose**: Centralized configuration for database connection and exam URL

**Interface**:
```php
// Database Configuration
$db_host = 'localhost';
$db_name = 'token_gate_db';
$db_user = 'root';
$db_pass = '';

// Exam URL Configuration
$exam_url = 'http://www.xxxxx.sch.id';

// Database Connection Function
function getDbConnection(): mysqli
```

**Security Considerations**:
- File should be placed outside web root if possible
- Use environment variables in production
- No direct output to prevent information disclosure

### 2. index.php (Student Portal Component)

**Purpose**: Entry point for students to submit access token

**Interface**:
- GET parameter: `error` (optional, indicates validation failure)
- POST form target: `validate.php`
- Form fields: `token` (text input)

**Functionality**:
- Display token input form
- Show error message if `$_GET['error']` is set
- Include `style.css` for styling
- Minimal JavaScript for error message display (no redirect logic)

**HTML Structure**:
```html
<form method="POST" action="validate.php">
    <input type="text" name="token" required>
    <button type="submit">Submit</button>
</form>
```

### 3. validate.php (Token Validator Component)

**Purpose**: Backend validation logic for token authentication

**Interface**:
- Input: `$_POST['token']`
- Output: HTTP redirect (Location header)

**Algorithm**:
```
1. Check if POST request with token parameter
2. Sanitize input token
3. Connect to database
4. Query current_token from active_token table
5. Compare submitted token with database token (case-sensitive)
6. If match:
   - Send HTTP 302 redirect to exam URL
   - Exit script
7. If no match:
   - Redirect to index.php?error=1
   - Exit script
```

**Security Measures**:
- Use prepared statements for database queries
- Sanitize all inputs with `trim()` and validation
- No output before header redirect
- Immediate script termination after redirect

### 4. admin.php (Admin Panel Component)

**Purpose**: Administrative interface for token management

**Interface**:
- Session-based authentication
- POST actions: `login`, `rotate_token`
- Display: Current active token

**Authentication Flow**:
```
1. Check if session exists
2. If not authenticated:
   - Display login form
   - Validate credentials against hardcoded values
   - Set session variable on success
3. If authenticated:
   - Display current token from database
   - Show "Generate New Token" button
   - Handle token rotation request
```

**Hardcoded Credentials**:
```php
$admin_username = 'admin';
$admin_password = 'admin123'; // Should be hashed in production
```

**Features**:
- Session management with `session_start()`
- Logout functionality
- Manual token rotation trigger
- Display current active token
- CSRF protection (optional but recommended)

### 5. rotate_token.php (Token Rotator Component)

**Purpose**: Generate and update access token

**Interface**:
- Can be called via HTTP or CLI
- No parameters required
- Returns: Success/failure status

**Token Generation Algorithm**:
```php
function generateToken($length = 8): string {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $token;
}
```

**Database Update Logic**:
```
1. Generate new 8-character alphanumeric token (uppercase)
2. Connect to database
3. UPDATE active_token SET current_token = ? WHERE id = 1
4. Verify update success
5. Return status
```

**Execution Methods**:
- Direct HTTP access (for manual trigger from admin panel)
- Cron job execution (for automated rotation)
- CLI execution: `php rotate_token.php`

### 6. style.css (Styling Component)

**Purpose**: Visual presentation for student and admin interfaces

**Design Principles**:
- Clean, minimal design
- Responsive layout
- Clear visual hierarchy
- Accessible form elements

**Key Styles**:
- Centered form layout
- Clear input fields with proper sizing
- Visible error messages (red color)
- Professional admin panel layout
- Button styling with hover states

## Data Models

### Database Schema

#### Table: active_token

```sql
CREATE TABLE active_token (
    id INT PRIMARY KEY AUTO_INCREMENT,
    current_token VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Columns**:
- `id`: Primary key, always value 1 (single row table)
- `current_token`: The currently valid access token
- `created_at`: Timestamp of initial record creation
- `updated_at`: Timestamp of last token update

**Initial Data**:
```sql
INSERT INTO active_token (id, current_token) VALUES (1, 'INITIAL00');
```

**Indexes**:
- Primary key on `id` (automatic)
- No additional indexes needed (single row table)

### Database Connection Pattern

```php
function getDbConnection(): mysqli {
    global $db_host, $db_name, $db_user, $db_pass;
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}
```

## Error Handling

### Student Portal Error Handling

**Error Types**:
1. Invalid token
2. Expired token (same as invalid in this simple implementation)
3. Database connection failure
4. Missing token parameter

**Error Display**:
- URL parameter: `index.php?error=1`
- JavaScript-based message display (non-intrusive)
- Generic error message: "Token tidak valid"
- No specific error details to prevent information disclosure

### Admin Panel Error Handling

**Error Types**:
1. Invalid login credentials
2. Database connection failure
3. Token rotation failure
4. Session timeout

**Error Display**:
- Inline error messages
- Specific error descriptions for admin
- Logging capability for debugging

### Validator Error Handling

**Error Scenarios**:
1. Missing POST data → Redirect to index.php
2. Database connection failure → Display generic error
3. Token mismatch → Redirect to index.php?error=1
4. Empty token → Redirect to index.php?error=1

**Error Response**:
- Always use HTTP redirects (no error pages)
- No detailed error information in production
- Log errors server-side for debugging

## Security Considerations

### Input Validation and Sanitization

**All User Inputs**:
```php
$token = htmlspecialchars(trim($_POST['token'] ?? ''), ENT_QUOTES, 'UTF-8');
```

**SQL Injection Prevention**:
```php
$stmt = $conn->prepare("SELECT current_token FROM active_token WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();
```

### Authentication Security

**Admin Panel**:
- Session-based authentication
- Hardcoded credentials (acceptable for simple use case)
- Session timeout after inactivity
- Logout functionality

**Token Validation**:
- Case-sensitive comparison
- No timing attack vulnerability (simple string comparison acceptable)
- Server-side validation only

### URL Protection

**Exam URL Security**:
- Stored in backend configuration file
- Never exposed in HTML/JavaScript
- Only accessed via server-side redirect
- No client-side redirect mechanisms

**Redirect Implementation**:
```php
header("Location: " . $exam_url);
exit();
```

### Database Security

**Connection Security**:
- Use prepared statements exclusively
- Parameterized queries for all user input
- Proper error handling without information disclosure
- Connection credentials in separate config file

**Token Storage**:
- Plain text storage (acceptable for this use case)
- Single active token model
- Automatic rotation capability

## Testing Strategy

### Unit Testing Approach

**Components to Test**:
1. Token generation function
2. Database connection function
3. Token validation logic
4. Admin authentication logic

**Test Cases**:

**Token Generation**:
- Verify token length (8 characters)
- Verify character set (A-Z, 0-9)
- Verify uniqueness across multiple generations

**Token Validation**:
- Valid token → successful redirect
- Invalid token → error redirect
- Empty token → error redirect
- SQL injection attempts → safe handling

**Admin Authentication**:
- Correct credentials → access granted
- Incorrect credentials → access denied
- Session persistence → maintained across requests

### Integration Testing

**End-to-End Flows**:

1. **Student Access Flow**:
   - Access index.php
   - Submit valid token
   - Verify redirect to exam URL
   - Verify exam URL not exposed in browser history

2. **Invalid Token Flow**:
   - Access index.php
   - Submit invalid token
   - Verify redirect back to index.php
   - Verify error message displayed

3. **Admin Token Management**:
   - Login to admin panel
   - View current token
   - Generate new token
   - Verify token updated in database
   - Verify old token no longer works

4. **Automated Rotation**:
   - Trigger rotate_token.php
   - Verify new token in database
   - Verify old token invalidated

### Manual Testing Checklist

- [ ] Student can access index.php
- [ ] Valid token redirects to exam URL
- [ ] Invalid token shows error message
- [ ] Exam URL not visible in page source
- [ ] Admin login works with correct credentials
- [ ] Admin can view current token
- [ ] Manual token rotation works
- [ ] New token immediately active
- [ ] Old token immediately invalid
- [ ] Cron job execution successful
- [ ] Database connection errors handled gracefully
- [ ] SQL injection attempts blocked
- [ ] XSS attempts sanitized

## Deployment Considerations

### Server Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (optional, for clean URLs)

### File Structure

```
/token-gate/
├── index.php
├── validate.php
├── admin.php
├── rotate_token.php
├── config.php
├── style.css
└── database.sql
```

### Cron Job Configuration

**Example Cron Entry** (rotate every 10 minutes):
```bash
*/10 * * * * /usr/bin/php /path/to/token-gate/rotate_token.php
```

**Alternative Intervals**:
- Every 5 minutes: `*/5 * * * *`
- Every 30 minutes: `*/30 * * * *`
- Every hour: `0 * * * *`

### Database Setup

1. Create database: `CREATE DATABASE token_gate_db;`
2. Import schema from `database.sql`
3. Verify initial token inserted
4. Configure database credentials in `config.php`

### Security Hardening

**Production Recommendations**:
- Move `config.php` outside web root
- Use environment variables for sensitive data
- Implement HTTPS (SSL/TLS)
- Add rate limiting for login attempts
- Implement CSRF protection for admin panel
- Use password hashing for admin credentials
- Enable PHP error logging (disable display_errors)
- Set proper file permissions (644 for PHP files, 600 for config.php)

## Performance Considerations

### Database Optimization

- Single row table requires no indexing beyond primary key
- Connection pooling not necessary for simple use case
- Query performance negligible (single row SELECT)

### Caching Strategy

- No caching required (token must be current)
- Database query on every validation (acceptable overhead)
- Session caching for admin authentication

### Scalability

**Current Design Limitations**:
- Single active token for all students
- No support for multiple exam sessions
- No token expiration tracking

**Future Enhancements** (if needed):
- Multiple active tokens with expiration
- Token usage tracking
- Student-specific tokens
- Token usage analytics
