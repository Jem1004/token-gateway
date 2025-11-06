# Implementation Plan - Token Gate Application

## Overview
This implementation plan breaks down the Token Gate application development into discrete, manageable coding tasks. Each task builds incrementally on previous work, ensuring a systematic approach to implementation.

## Task List

- [x] 1. Create database schema and configuration files
  - Create SQL schema file with active_token table definition
  - Create config.php with database connection credentials and exam URL configuration
  - Implement getDbConnection() function with error handling and UTF-8 charset
  - _Requirements: 5.1, 5.2, 5.3, 6.3_

- [x] 2. Implement token rotation functionality
  - Create rotate_token.php with generateToken() function that produces 8-character alphanumeric uppercase tokens
  - Implement database UPDATE logic using prepared statements to replace current_token
  - Add error handling for database connection failures
  - Add success/failure status output for debugging
  - _Requirements: 4.2, 4.3, 6.1_

- [x] 3. Build token validation backend
  - Create validate.php to handle POST requests from student portal
  - Implement input sanitization for token parameter using htmlspecialchars and trim
  - Add database query logic using prepared statements to fetch current_token
  - Implement token comparison logic (case-sensitive)
  - Add server-side redirect to exam URL on successful validation using header("Location: ...")
  - Add redirect to index.php?error=1 on validation failure
  - Ensure no output before header redirect and immediate exit after redirect
  - _Requirements: 1.2, 1.3, 1.5, 2.2, 2.3, 5.4, 6.1, 6.4_

- [x] 4. Create student portal interface
  - Create index.php with HTML form structure (POST method to validate.php)
  - Add single text input field for token with required attribute
  - Add submit button with appropriate label
  - Implement error parameter detection ($_GET['error']) and display logic
  - Add error message display ("Token tidak valid") when error parameter present
  - Link style.css for styling
  - _Requirements: 1.1, 1.4, 7.1, 7.2_

- [x] 5. Implement admin panel with authentication
  - Create admin.php with session management (session_start())
  - Implement hardcoded credential validation (username and password constants)
  - Create login form with username and password fields
  - Add POST handler for login action with credential verification
  - Implement session variable setting on successful authentication
  - Add authentication check to protect admin features
  - Implement logout functionality to destroy session
  - _Requirements: 3.1, 3.4, 6.2_

- [x] 6. Add admin token management features
  - Implement database query to fetch and display current active token
  - Create "Buat Token Baru (Manual)" button in admin interface
  - Add POST handler to trigger rotate_token.php logic manually
  - Implement page refresh or token display update after manual rotation
  - Add success/error message display for rotation operations
  - _Requirements: 3.2, 3.3, 4.4_

- [x] 7. Create CSS styling
  - Create style.css with styles for student portal form (centered layout, clear inputs)
  - Add error message styling (red color, visible placement)
  - Add admin panel styling (professional layout, clear sections)
  - Implement button styles with hover states
  - Add responsive design considerations for mobile devices
  - _Requirements: 7.1, 7.3, 7.4_

- [x] 8. Add security hardening
  - Review all user input points and ensure htmlspecialchars is applied
  - Verify all database queries use prepared statements
  - Add input validation for empty/missing parameters
  - Ensure no sensitive information in error messages
  - Verify exam URL is never exposed in client-side code
  - _Requirements: 2.4, 6.1, 6.2, 6.4_

- [x] 9. Create database initialization script
  - Create database.sql with CREATE DATABASE statement
  - Add CREATE TABLE statement for active_token
  - Add INSERT statement for initial token value
  - Add comments for setup instructions
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 10. Add documentation and deployment guide
  - Create README with setup instructions
  - Document cron job configuration examples
  - Add database setup steps
  - Document admin credentials and how to change them
  - Add security recommendations for production deployment
  - _Requirements: All_

## Implementation Notes

### Execution Order
Tasks should be executed in numerical order as each builds upon previous work:
1. Database and configuration foundation
2. Core token rotation logic
3. Validation backend
4. Student-facing interface
5. Admin authentication
6. Admin token management
7. Visual styling
8. Security review
9. Database initialization helper
10. Documentation

### Testing Approach
- After completing task 3, test token validation flow manually
- After completing task 4, test complete student authentication flow
- After completing task 6, test admin panel token management
- After completing task 8, perform security testing with invalid inputs

### Key Integration Points
- Task 2 (rotate_token.php) is called by Task 6 (admin panel)
- Task 3 (validate.php) receives data from Task 4 (index.php)
- Task 1 (config.php) is used by Tasks 2, 3, 5, and 6
- Task 7 (style.css) is linked by Tasks 4 and 5

### Security Priorities
- All database operations must use prepared statements (Tasks 2, 3, 5, 6)
- All user inputs must be sanitized (Tasks 3, 4, 5, 6)
- Server-side redirect only for exam URL (Task 3)
- No exam URL exposure in client code (Task 4)

### Complete Implementation
All tasks are required for a comprehensive implementation including database setup and documentation.
