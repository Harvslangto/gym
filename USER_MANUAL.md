# Trizen Fitness Hub - Gym Management System User Manual

Welcome to the **Trizen Fitness Hub Management System**. This secure, web-based application is designed to help gym administrators manage members, track payments, and monitor business performance efficiently.

---

## 1. Installation & Setup

### Prerequisites
- A web server (e.g., Apache via XAMPP).
- PHP 7.4 or higher.
- MySQL Database.

### First-Time Setup
1.  **Database Configuration**:
    - Open `db.php` in a text editor.
    - Update the database connection settings if you are using a live server. For local XAMPP, the default is usually:
      ```php
      $conn = new mysqli("localhost", "root", "", "gym_db");
      ```
    - **Security Note**: For production environments, create a dedicated database user with a strong password and update `db.php`.

2.  **Database Initialization**:
    - The system automatically creates the necessary database tables (`admin`, `members`, `payments`, `membership_types`, `activity_logs`, `login_attempts`) upon the first run.

3.  **Default Credentials**:
    - **Username**: `admin`
    - **Password**: `trizen2026`
    - *Note: You should change this password immediately after logging in.*

---

## 2. Getting Started

### Logging In
1.  Navigate to the application URL (e.g., `http://localhost/gym/`).
2.  Enter your username and password.
3.  **Security Feature**: After 5 failed attempts, the system will lock login for 15 minutes.

### Dark/Light Mode
- Click the **Sun/Moon icon** in the bottom-right corner of the screen to toggle between Dark Mode and Light Mode. Your preference is saved automatically.

---

## 3. Dashboard Overview

The **Dashboard** provides a snapshot of your gym's performance:
- **Key Metrics**: View Total Members, Active Members, Expired Members, and Total Income at a glance.
- **Daily Breakdown**: See who paid today. You can filter this list by Membership Type, Year, Month, and Day.
- **Monthly Breakdown**: A table showing member counts and revenue for each month of the selected year.
- **Yearly Overview**: A high-level view of performance across different years.

---

## 4. Managing Members

### Adding a New Member
1.  Click **+ Add Member** on the Member List page.
2.  Fill in the required details:
    - **Personal Info**: Name, Contact (must be 11 digits starting with 09), Address, Birth Date, Sex.
    - **Membership**: Select a Type (e.g., Regular, Student) and Duration (1-60 months).
    - **Photo**: Upload a profile picture. You can crop the image directly in the browser before saving.
3.  Click **Save Member**. The system calculates the total amount automatically.

### Viewing Member Details
1.  Click on a member's row in the **Member List** or **Dashboard**.
2.  The profile shows:
    - Membership Status (Active/Expired).
    - Days Remaining.
    - Personal Information.
    - Payment History.

### Editing a Member
1.  Go to the member's profile and click **Edit**.
2.  Update information as needed.
3.  You can upload a new photo or re-crop the existing one.
4.  Click **Update Member** to save changes.

### Renewing Membership
1.  Go to the member's profile and click **Renew**.
2.  Select the new Membership Type and Duration.
3.  The system calculates the new End Date and Amount to be paid.
4.  Click **Confirm Renewal**. A new payment record is added to their history.

### Deleting a Member
1.  Go to the member's profile and click **Delete**.
2.  Confirm the action in the modal window.
3.  *Warning: This permanently removes the member and all their payment records.*

---

## 5. Settings & Administration

Access the **Settings** page from the Dashboard or navigation menu.

### Managing Membership Prices
1.  View the list of current membership types (e.g., Regular, Walk-in).
2.  Enter a new price in the input field next to the type.
3.  Click **Update**. Confirm the change in the popup window.

### Changing Admin Password
1.  Scroll to the **Change Password** section.
2.  Enter your current password.
3.  Enter and confirm your new password (must be at least 6 characters).
4.  Click **Update Password**.

---

## 6. Activity Logs

The system tracks important actions for security and auditing.
- Navigate to **Logs** from the Dashboard.
- View a chronological list of actions (e.g., "Login", "Add Member", "Update Settings").
- Click **Clear Logs** to wipe the history (requires confirmation).

---

*&copy; 2026 Trizen Fitness Hub Management System*