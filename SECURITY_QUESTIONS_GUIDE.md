# Security Questions Feature - Implementation Guide

## Overview

The BOFETODA system now includes a comprehensive password recovery mechanism using security questions. This feature allows both Officers (Admin portal) and Drivers (Driver portal) to reset their passwords without administrator intervention.

**Key Features:**
- Mandatory security question setup on first login
- Three-step password recovery flow
- Rate limiting to prevent brute-force attacks
- Case-insensitive answer matching
- Profile-based security question management

---

## Database Schema Changes

### Migration Required

Before using this feature, you must run the database migration to add the necessary columns to both `officers` and `drivers` tables.

**Migration File:** `add_security_questions.sql`

**What it adds:**
- `security_question_id` - ID of the chosen security question (1-5)
- `security_answer_hash` - Hashed security answer (bcrypt)
- `failed_recovery_attempts` - Counter for failed password recovery attempts
- `recovery_cooldown_until` - Timestamp when cooldown period expires

### How to Run the Migration

**Option 1: Using import_database.php (Browser-based)**
1. Navigate to `https://your-app-url.onrender.com/import_database.php`
2. Copy the contents of `add_security_questions.sql`
3. Paste into the text area and click "Import SQL"

**Option 2: Using Aiven Console**
1. Go to your Aiven console: https://console.aiven.io
2. Select your MySQL service (bofetoda-mysql)
3. Go to "Query Editor" tab
4. Select database: `bofetoda`
5. Copy and paste the contents of `add_security_questions.sql`
6. Click "Execute"

**Option 3: Using MySQL Command Line**
```bash
mysql -h bofetoda-mysql-asuncion-630b.j.aivencloud.com \
      -P 19831 \
      -u avnadmin \
      -p \
      --ssl-mode=REQUIRED \
      bofetoda < add_security_questions.sql
```

**Verification:**
After running the migration, verify the columns were added:
```sql
DESCRIBE officers;
DESCRIBE drivers;
```

You should see the four new columns in both tables.

---

## Security Questions

Users can choose from 5 pre-defined security questions:

1. **What is your mother's maiden name?**
2. **What city were you born in?**
3. **What is the name of your first pet?**
4. **What was your childhood nickname?**
5. **What is your favorite color?**

**Answer Handling:**
- Answers are case-insensitive (e.g., "Manila" matches "manila")
- Leading and trailing whitespace is removed
- Answers are hashed using bcrypt (same as passwords)
- Minimum answer length: 2 characters

---

## User Flows

### 1. First-Time Login (Mandatory Setup)

**For Officers (Admin Portal):**
1. User logs in with Officer ID and password
2. System checks if security question is set
3. If not set, redirects to `/Admin_BOFETODA/setup_security_question.php`
4. User selects a question and provides an answer
5. Confirmation page displays for 3 seconds
6. Automatically redirects to dashboard

**For Drivers (Driver Portal):**
1. User logs in with Driver ID and password
2. System checks if security question is set
3. If not set, redirects to `/Driver_BOFETODA/driver-setup-security.php`
4. User selects a question and provides an answer
5. Confirmation page displays for 3 seconds
6. Automatically redirects to home page

**Important:** Users CANNOT skip this step. The security question setup is mandatory before accessing the application.

---

### 2. Forgot Password Flow (3 Steps)

**Step 1: Enter ID**
- User clicks "Forgot Password?" link on login page
- Enters their Officer ID or Driver ID
- System validates:
  - ID exists in database
  - Account is active (not archived for drivers)
  - Security question is set up
  - No active cooldown period

**Step 2: Answer Security Question**
- System displays the user's chosen security question
- User enters their answer
- System verifies answer (case-insensitive)
- **If correct:** Proceeds to Step 3
- **If incorrect:** 
  - Increments failed attempt counter
  - Shows remaining attempts
  - After 3 attempts: 15-minute cooldown
  - After 5 attempts: 1-hour cooldown

**Step 3: Reset Password**
- User enters new password (minimum 8 characters)
- User confirms new password
- System validates passwords match
- Password is hashed and stored
- User is redirected to login page with success message

---

### 3. Update Security Question (Profile)

**For Officers:**
1. Click on profile avatar/name in sidebar
2. Click "Change Security Question" button
3. Modal opens with dropdown and answer input
4. Select new question and provide new answer
5. Click "Save Changes"
6. Success message displays

**For Drivers:**
1. Navigate to Profile page
2. Click "Security Question" button
3. Modal opens with dropdown and answer input
4. Select new question and provide new answer
5. Click "Save"
6. Success message displays

**Note:** No current password required to change security question (by design requirement).

---

## Rate Limiting & Security

### Cooldown Rules

**3 Failed Attempts:**
- Cooldown: 15 minutes
- User cannot attempt password recovery during this period
- Message: "Too many failed attempts. Please try again in X minutes."

**5 Failed Attempts:**
- Cooldown: 1 hour
- User cannot attempt password recovery during this period
- Message: "Too many failed attempts. Please try again in X hour(s)."

**Cooldown Reset:**
- Cooldown is automatically cleared when the time expires
- Failed attempt counter is reset to 0 when user answers correctly

### How Cooldown Works

1. Failed attempts are tracked in the database per user
2. Cooldown timestamp (`recovery_cooldown_until`) is set when threshold is reached
3. System checks cooldown before allowing password recovery
4. If cooldown is active, user sees time remaining
5. Once cooldown expires, system automatically clears it

---

## Security Considerations

### Password Hashing
- Security answers are hashed using PHP's `password_hash()` (bcrypt)
- Same security level as passwords
- Answers are normalized (lowercase + trimmed) before hashing

### Case-Insensitive Matching
- Answers like "Manila", "manila", "MANILA" are all treated as identical
- Implemented by normalizing input before hashing and verification
- User-friendly while maintaining security

### No Email Required
- System designed for environments without email infrastructure
- Security questions provide alternative identity verification
- Rate limiting prevents brute-force attacks

### Attack Prevention
- Failed attempt tracking prevents unlimited guessing
- Cooldown periods prevent automated attacks
- Answers are hashed (not reversible)
- Cooldown persists across browser sessions (database-backed)

---

## Administrator Tasks

### Manual Password Reset (If User Loses Access)

If a user cannot recover their password through security questions:

**For Officers:**
1. Log in as President or Secretary
2. Go to Driver List (if applicable)
3. Use admin functions to reset password

**For Drivers:**
1. Admin must reset password from Driver List
2. Provide temporary password to driver
3. Driver changes password on next login

### Manually Clear Cooldown

If a user is locked out due to cooldown and needs immediate access:

```sql
-- Clear cooldown for an officer
UPDATE officers 
SET failed_recovery_attempts = 0, 
    recovery_cooldown_until = NULL 
WHERE officer_id = 'OFF-20240001';

-- Clear cooldown for a driver
UPDATE drivers 
SET failed_recovery_attempts = 0, 
    recovery_cooldown_until = NULL 
WHERE driver_id = 'DRV-BG0001';
```

### Reset Security Question

If a user forgets their security answer, admin must reset it:

```sql
-- Reset security question for an officer
UPDATE officers 
SET security_question_id = NULL, 
    security_answer_hash = NULL 
WHERE officer_id = 'OFF-20240001';

-- Reset security question for a driver
UPDATE drivers 
SET security_question_id = NULL, 
    security_answer_hash = NULL 
WHERE driver_id = 'DRV-BG0001';
```

After resetting, the user will be prompted to set up their security question on next login.

---

## Files Modified/Created

### Admin Portal
- **Created:**
  - `Admin_BOFETODA/setup_security_question.php` - First-time setup page
  - `Admin_BOFETODA/forgot_password.php` - 3-step password recovery
  - `Admin_BOFETODA/update_security_question.php` - Update from profile

- **Modified:**
  - `Admin_BOFETODA/config.php` - Added security question functions and profile modal
  - `Admin_BOFETODA/login.php` - Added forgot password link and first-login check

### Driver Portal
- **Created:**
  - `Driver_BOFETODA/driver-setup-security.php` - First-time setup page
  - `Driver_BOFETODA/driver-forgot-password.php` - 3-step password recovery
  - `Driver_BOFETODA/driver-update-security.php` - Update from profile

- **Modified:**
  - `Driver_BOFETODA/driver-database.php` - Added security question functions
  - `Driver_BOFETODA/driver-login.php` - Added forgot password link and first-login check
  - `Driver_BOFETODA/driver-profile.php` - Added security question button and modal
  - `Driver_BOFETODA/driver-template.php` - Added forgot password link CSS

### Database
- **Created:**
  - `add_security_questions.sql` - Database migration file

---

## Testing Checklist

### Initial Setup Testing
- [ ] New officer logs in → redirected to security question setup
- [ ] Cannot skip security question setup page
- [ ] Security question saved successfully
- [ ] Confirmation page displays and redirects
- [ ] Second login goes straight to dashboard

### Forgot Password Testing
- [ ] "Forgot Password?" link visible on login page
- [ ] Step 1: Valid ID proceeds to step 2
- [ ] Step 1: Invalid ID shows error
- [ ] Step 1: ID without security question shows appropriate message
- [ ] Step 2: Correct answer proceeds to step 3
- [ ] Step 2: Incorrect answer shows remaining attempts
- [ ] Step 2: 3 wrong answers triggers 15-minute cooldown
- [ ] Step 2: 5 wrong answers triggers 1-hour cooldown
- [ ] Step 3: Password validation works (min 8 chars, match confirmation)
- [ ] Step 3: New password works on login page

### Rate Limiting Testing
- [ ] Cooldown message displays correct time remaining
- [ ] Cannot bypass cooldown by clearing cookies
- [ ] Cooldown automatically expires after time period
- [ ] Correct answer resets failed attempt counter
- [ ] Cooldown persists across different browsers

### Profile Update Testing
- [ ] Can change security question from profile
- [ ] New question/answer works for password recovery
- [ ] Success message displays correctly
- [ ] No current password required to change question

### Edge Cases
- [ ] Archived driver account cannot use forgot password
- [ ] Inactive officer account cannot use forgot password
- [ ] Case-insensitive answer matching works (e.g., "Manila" vs "manila")
- [ ] Whitespace in answers is trimmed
- [ ] Special characters in answers work correctly

---

## Troubleshooting

### Issue: User Cannot See Security Question Setup Page
**Solution:** Ensure database migration has been run and columns exist in the database.

### Issue: Cooldown Not Working
**Solution:** Verify `recovery_cooldown_until` column exists and has DATETIME type.

### Issue: Answers Not Matching
**Solution:** Check that answers are being normalized (lowercase + trim) before hashing and verification.

### Issue: "Forgot Password" Link Not Visible
**Solution:** Clear browser cache and verify CSS has been updated in login pages.

### Issue: Security Question Not Required on First Login
**Solution:** Check that `has_security_question()` or `driver_has_security_question()` function is being called after successful login.

---

## Future Enhancements

Potential improvements for future versions:

1. **Email Integration:** Add email-based password recovery as an alternative
2. **Multiple Security Questions:** Require users to answer 2-3 questions instead of one
3. **Account Recovery History:** Log all password recovery attempts
4. **Admin Dashboard:** View users who are in cooldown period
5. **Custom Questions:** Allow users to write their own security questions
6. **2FA Integration:** Add two-factor authentication alongside security questions

---

## Support

For technical support or questions about this feature:
- Review this documentation
- Check the database migration was run successfully
- Verify all modified files are deployed
- Test the flows in a staging environment first

**Deployment Checklist:**
1. ✅ Run database migration (`add_security_questions.sql`)
2. ✅ Deploy all modified PHP files
3. ✅ Test with sample accounts
4. ✅ Inform users about the new feature
5. ✅ Monitor for any issues in the first few days

---

**Last Updated:** October 22, 2026  
**Version:** 1.0  
**Author:** BOFETODA Development Team
