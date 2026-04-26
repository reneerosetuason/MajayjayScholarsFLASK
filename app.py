from flask import Flask, render_template, request, jsonify, redirect, flash, url_for, session
import os
from werkzeug.utils import secure_filename
import mysql.connector
from datetime import datetime, timedelta
# ==================== EMAIL VERIFICATION SETUP ====================
import smtplib
import random
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.image import MIMEImage

app = Flask(__name__)
app.secret_key = "ren02"

# ================== FILE UPLOAD SETTINGS ==================
UPLOAD_FOLDER = 'static/uploads'
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'pdf'}

if not os.path.exists(UPLOAD_FOLDER):
    os.makedirs(UPLOAD_FOLDER)

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


# ================== DATABASE CONNECTION ==================
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="ren123",
    database="majayjay_scholars"
)

# ==================== EMAIL VERIFICATION SETUP ====================
# Verification storage
verification_store = {}

# Email credentials
SENDER_EMAIL = "majayjayscholars@gmail.com"
SENDER_APP_PASSWORD = "zsxp iqvn klmd xqfw"

# ==================== HELPER FUNCTIONS ====================

def generate_verification_key(email):
    """Generate unique key for storing verification data"""
    return f"verify_{email}"

def store_verification_code(email, code):
    """Store verification code server-side with expiry"""
    key = generate_verification_key(email)
    verification_store[key] = {
        'email': email,
        'code': str(code),
        'verified': False,
        'created_at': datetime.now(),
        'expires_at': datetime.now() + timedelta(minutes=10)
    }
    print(f"\n{'='*60}")
    print(f"[DEBUG] ✓ VERIFICATION CODE STORED FOR {email}")
    print(f"[DEBUG] Code: {code}")
    print(f"[DEBUG] Expires at: {verification_store[key]['expires_at']}")
    print(f"{'='*60}\n")

def get_verification_data(email):
    """Retrieve verification data"""
    key = generate_verification_key(email)
    data = verification_store.get(key)
    
    if data and datetime.now() > data['expires_at']:
        print(f"[DEBUG] Verification code for {email} has expired")
        del verification_store[key]
        return None
    
    return data

def verify_code_check(email, code):
    """Verify the code and mark as verified"""
    data = get_verification_data(email)
    
    if not data:
        print(f"[DEBUG] ❌ No verification data found for {email}")
        return False, "No verification code found. Please request a code first."
    
    stored_code = str(data['code']).strip()
    received_code = str(code).strip()
    
    print(f"\n[DEBUG] Code verification attempt:")
    print(f"  Email:    {email}")
    print(f"  Stored:   '{stored_code}'")
    print(f"  Received: '{received_code}'")
    print(f"  Match:    {stored_code == received_code}\n")
    
    if stored_code != received_code:
        print(f"[DEBUG] ❌ Code mismatch!")
        return False, "Incorrect verification code."
    
    # Mark as verified
    data['verified'] = True
    print(f"[DEBUG] ✓ Email {email} verified successfully")
    return True, "Email verified successfully."

def is_email_verified(email):
    """Check if email has been verified"""
    data = get_verification_data(email)
    return data and data['verified']

def cleanup_verification(email):
    """Clean up verification data after use"""
    key = generate_verification_key(email)
    if key in verification_store:
        del verification_store[key]
        print(f"[DEBUG] ✓ Cleaned up verification data for {email}")

def send_status_email(email, name, status):
    """Send application status notification email"""
    try:
        msg = MIMEMultipart('related')
        msg['From'] = SENDER_EMAIL
        msg['To'] = email
        
        if status == 'approved':
            msg['Subject'] = "🎉 Congratulations! Your Scholarship Application is Approved"
            gradient = "linear-gradient(135deg, #10b981 0%, #059669 100%)"
            status_icon = "✓"
            status_text = "APPROVED"
            message = f"Congratulations <strong>{name}</strong>! We are thrilled to inform you that your scholarship application has been <strong>approved</strong>. We are excited to welcome to the Majayjay Scholars family!"
            action_text = "You will receive further instructions via email regarding the next steps."
        else:
            msg['Subject'] = "Scholarship Application Status Update"
            gradient = "linear-gradient(135deg, #ef4444 0%, #dc2626 100%)"
            status_icon = "✕"
            status_text = "NOT APPROVED"
            message = f"Dear <strong>{name}</strong>, after careful review, we regret to inform you that your scholarship application has not been approved at this time."
            action_text = "We encourage you to reapply in the future. Keep striving for excellence!"
        
        alt = MIMEMultipart('alternative')
        msg.attach(alt)
        
        html = f"""
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
          </head>
          <body style="margin:0; padding:0; background:#f3f4f6; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <table width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6; padding:40px 20px;">
              <tr><td align="center">
                <table width="600" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:20px; overflow:hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                  
                  <!-- Header -->
                  <tr><td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding:50px 40px; text-align:center;">
                    <div style="background:rgba(255,255,255,0.2); width:80px; height:80px; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(10px);">
                      <span style="font-size:40px; color:#fff;">🎓</span>
                    </div>
                    <h1 style="margin:0; font-size:28px; font-weight:700; color:#fff; letter-spacing:-0.5px;">
                      Majayjay Scholars Program
                    </h1>
                  </td></tr>
                  
                  <!-- Status Badge -->
                  <tr><td style="padding:40px 40px 20px; text-align:center;">
                    <div style="background:{gradient}; color:#fff; padding:16px 32px; border-radius:50px; display:inline-block; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
                      <span style="font-size:18px; font-weight:700; letter-spacing:1px;">{status_icon} {status_text}</span>
                    </div>
                  </td></tr>
                  
                  <!-- Message -->
                  <tr><td style="padding:20px 50px; text-align:center;">
                    <p style="margin:0 0 20px; font-size:17px; color:#374151; line-height:1.7;">
                      {message}
                    </p>
                    <p style="margin:0; font-size:15px; color:#6b7280; line-height:1.6;">
                      {action_text}
                    </p>
                  </td></tr>
                  
                  <!-- Divider -->
                  <tr><td style="padding:30px 50px;">
                    <div style="height:1px; background:linear-gradient(90deg, transparent, #e5e7eb, transparent);"></div>
                  </td></tr>
                  
                  <!-- Footer -->
                  <tr><td style="padding:0 50px 40px; text-align:center;">
                    <p style="margin:0 0 10px; font-size:13px; color:#9ca3af;">
                      This is an automated notification from Majayjay Scholars Program
                    </p>
                    <p style="margin:0; font-size:12px; color:#d1d5db;">
                      © 2025 Majayjay Scholars. All rights reserved.
                    </p>
                  </td></tr>
                  
                </table>
              </td></tr>
            </table>
          </body>
        </html>
        """
        
        alt.attach(MIMEText(html, 'html'))
        
        with smtplib.SMTP_SSL('smtp.gmail.com', 465) as smtp:
            smtp.login(SENDER_EMAIL, SENDER_APP_PASSWORD)
            smtp.send_message(msg)
        print(f"[DEBUG] ✓ Status email sent to {email}")
        return True
    except Exception as e:
        print(f"[ERROR] Failed to send status email: {e}")
        return False


# ================== ROUTES ==================

@app.route('/')
def home():
    if 'user_id' in session:
        user_type = session.get('user_type', '').lower()
        if user_type == 'admin':
            return redirect(url_for('admin_dashboard'))
        elif user_type == 'mayor':
            return redirect(url_for('mayor_dashboard'))
        else:
            return redirect(url_for('student_dashboard'))
    return redirect(url_for('login'))


# ==================== SEND CODE ROUTE ====================
@app.route('/send-code', methods=['POST'])
def send_code():
    try:
        data = request.get_json()
        email = data.get('email')

        print(f"\n{'='*60}")
        print(f"[DEBUG] SEND CODE REQUEST")
        print(f"[DEBUG] Email: {email}")
        print(f"{'='*60}\n")

        if not email:
            print("[DEBUG] ❌ No email provided")
            return jsonify({'status': 'error', 'message': 'Email is required'}), 400

        # Generate 6-digit code
        code = f"{random.randint(100000, 999999)}"
        print(f"[DEBUG] Generated code: {code}")

        # Store server-side
        store_verification_code(email, code)

        # Create email
        msg = MIMEMultipart('related')
        msg['From'] = SENDER_EMAIL
        msg['To'] = email
        msg['Subject'] = "Your Verification Code - Majayjay Scholars Registration"

        # Alternative container
        alt = MIMEMultipart('alternative')
        msg.attach(alt)

        # HTML Email
        html = f"""
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
          </head>
          <body style="margin:0; padding:0; background:#f3f4f6; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <table width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6; padding:40px 20px;">
              <tr><td align="center">
                <table width="600" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:20px; overflow:hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                  
                  <!-- Header -->
                  <tr><td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding:50px 40px; text-align:center;">
                    <div style="background:rgba(255,255,255,0.2); width:80px; height:80px; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(10px);">
                      <span style="font-size:40px;">🔐</span>
                    </div>
                    <h1 style="margin:0; font-size:28px; font-weight:700; color:#fff; letter-spacing:-0.5px;">
                      Email Verification
                    </h1>
                    <p style="margin:10px 0 0; font-size:14px; color:rgba(255,255,255,0.9);">
                      Majayjay Scholars Program
                    </p>
                  </td></tr>
                  
                  <!-- Content -->
                  <tr><td style="padding:40px 50px; text-align:center;">
                    <p style="margin:0 0 30px; font-size:16px; color:#4a5568; line-height:1.6;">
                      Welcome! Please use the verification code below to complete your registration:
                    </p>
                    
                    <!-- Code Box -->
                    <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding:3px; border-radius:16px; display:inline-block; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.25);">
                      <div style="background:#ffffff; padding:24px 48px; border-radius:14px;">
                        <div style="font-size:36px; font-weight:700; color:#667eea; letter-spacing:8px; font-family: 'Courier New', monospace;">
                          {code}
                        </div>
                      </div>
                    </div>
                    
                    <p style="margin:30px 0 0; font-size:14px; color:#718096; line-height:1.6;">
                      This code will expire in <strong style="color:#667eea;">10 minutes</strong>
                    </p>
                  </td></tr>
                  
                  <!-- Info Box -->
                  <tr><td style="padding:0 50px 40px;">
                    <div style="background:#f7fafc; border-left:4px solid #667eea; padding:16px 20px; border-radius:8px;">
                      <p style="margin:0; font-size:13px; color:#4a5568; line-height:1.5;">
                        <strong style="color:#2d3748;">🛡️ Security Tip:</strong> Never share this code with anyone. Our team will never ask for your verification code.
                      </p>
                    </div>
                  </td></tr>
                  
                  <!-- Divider -->
                  <tr><td style="padding:0 50px;">
                    <div style="height:1px; background:linear-gradient(90deg, transparent, #e2e8f0, transparent);"></div>
                  </td></tr>
                  
                  <!-- Footer -->
                  <tr><td style="padding:30px 50px; text-align:center;">
                    <p style="margin:0 0 8px; font-size:13px; color:#a0aec0;">
                      If you didn't request this code, you can safely ignore this email.
                    </p>
                    <p style="margin:0; font-size:12px; color:#cbd5e0;">
                      © 2025 Majayjay Scholars Program. All rights reserved.
                    </p>
                  </td></tr>
                  
                </table>
              </td></tr>
            </table>
          </body>
        </html>
        """

        alt.attach(MIMEText(html, 'html'))

        # Send Email
        try:
            print(f"[DEBUG] Connecting to SMTP server...")
            with smtplib.SMTP_SSL('smtp.gmail.com', 465) as smtp:
                smtp.login(SENDER_EMAIL, SENDER_APP_PASSWORD)
                smtp.send_message(msg)
            print(f"[DEBUG] ✓ Email sent successfully to {email}")
        except Exception as e:
            print(f"[DEBUG] ❌ SMTP error: {e}")
            return jsonify({'status': 'error', 'message': 'Failed to send email. Please check the email address.'}), 500

        return jsonify({'status': 'success', 'message': 'Verification code sent to email'}), 200

    except Exception as e:
        print(f"[DEBUG] ❌ Send code error: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({'status': 'error', 'message': 'Failed to send code'}), 500


# ==================== VERIFY CODE ROUTE ====================
@app.route('/verify-code', methods=['POST'])
def verify_code_endpoint():
    try:
        data = request.get_json()
        email = data.get('email')
        code = data.get('code')

        print(f"\n[DEBUG] Verify attempt - Email: {email}, Code: {code}\n")

        if not email or not code:
            return jsonify({'status': 'failed', 'message': 'Email and code required'}), 400

        success, message = verify_code_check(email, code)
        
        if not success:
            return jsonify({'status': 'failed', 'message': message}), 400

        return jsonify({'status': 'success', 'message': 'Email verified successfully'}), 200

    except Exception as e:
        print(f"[DEBUG] ❌ Verify code error: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({'status': 'failed', 'message': 'Server error during verification'}), 500


# ---------- REGISTER ----------
@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        confirm = request.form.get('confirm_password')
        first_name = request.form.get('first_name')
        middle_name = request.form.get('middle_name')  # Optional field
        last_name = request.form.get('last_name')
        email_verified = request.form.get('email_verified')

        # Validate required fields (middle_name is optional)
        if not all([email, password, confirm, first_name, last_name]):
            flash("Please fill out all required fields.", "error")
            return redirect(url_for('register'))

        # Check if email is verified
        if email_verified != 'true' or not is_email_verified(email):
            flash('Please verify your email before registering', 'error')
            return redirect(url_for('register'))

        # Check password match
        if password != confirm:
            flash("Passwords do not match!", "error")
            return redirect(url_for('register'))

        cursor = db.cursor()

        try:
            # Hash the password before storing (recommended!)
            # If you have werkzeug.security: hashed_password = generate_password_hash(password)
            
            cursor.execute("""
                INSERT INTO users (email, password, first_name, middle_name, last_name, user_type)
                VALUES (%s, %s, %s, %s, %s, %s)
            """, (email, password, first_name, middle_name, last_name, 'student'))
            db.commit()
            
            # Clean up verification data after successful registration
            cleanup_verification(email)
            
            flash("Registration successful! You can now log in.", "success")
            return redirect(url_for('login'))
        except mysql.connector.IntegrityError:
            flash("Email already exists!", "error")
            return redirect(url_for('register'))
        except Exception as e:
            db.rollback()
            flash(f"Registration failed: {str(e)}", "error")
            return redirect(url_for('register'))
        finally:
            cursor.close()

    return render_template('register.html')


# ---------- LOGIN ----------
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']

        cursor = db.cursor(dictionary=True)
        cursor.execute("SELECT * FROM users WHERE email = %s", (email,))
        user = cursor.fetchone()
        cursor.close()

        if user and user['password'] == password:
            session['user_id'] = user['user_id']
            session['email'] = user['email']
            session['user_type'] = user['user_type']

            flash("Login successful!", "success")

            role = user['user_type'].strip().lower()
            if role == 'admin':
                return redirect(url_for('admin_dashboard'))
            elif role == 'mayor':
                return redirect(url_for('mayor_dashboard'))
            else:
                return redirect(url_for('student_dashboard'))
        else:
            flash("Invalid email or password.", "error")

    return render_template('login.html')


# ---------- MAYOR DASHBOARD ----------
@app.route('/mayor')
def mayor_dashboard():
    print(f"\n[DEBUG] Mayor dashboard accessed")
    print(f"[DEBUG] Session user_type: {repr(session.get('user_type'))}")
    print(f"[DEBUG] Session user_type (lower): {repr(session.get('user_type', '').lower())}")
    print(f"[DEBUG] Check result: {session.get('user_type', '').lower() != 'mayor'}\n")
    
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor(dictionary=True)
    
    # Get mayor's name
    cursor.execute("SELECT first_name, last_name FROM users WHERE user_id = %s", (session['user_id'],))
    mayor = cursor.fetchone()
    name = f"{mayor['first_name']} {mayor['last_name']}" if mayor else session.get('email')
    
    # Get all active applications (exclude archived)
    cursor.execute("""
        SELECT scholarship_type, status 
        FROM application 
        WHERE archived = FALSE OR archived IS NULL
    """)
    applications = cursor.fetchall()
    
    # Get all active renewals from renew table
    cursor.execute("""
        SELECT status 
        FROM renew 
        WHERE archived = FALSE OR archived IS NULL
    """)
    renewals = cursor.fetchall()
    
    # Get renewal status
    cursor.execute("SELECT is_open FROM renewal_settings WHERE id = 1")
    renewal_setting = cursor.fetchone()
    renewal_open = renewal_setting['is_open'] if renewal_setting else False
    
    cursor.close()
    
    # Filter new applications only
    new_apps = [a for a in applications if a['scholarship_type'] == 'new']
    
    return render_template('mayor/mayor_dashboard.html', 
                         name=name, 
                         new_applications=new_apps, 
                         renewals=renewals,
                         renewal_open=renewal_open)

#===================admin dashboard===================
@app.route('/admin')
def admin_dashboard():
    # Check if logged in and correct role
    if session.get('user_type', '').lower() == 'admin':
        
        cursor = db.cursor(dictionary=True)

        # Get all users with first_name, middle_name, last_name
        cursor.execute("SELECT user_id, first_name, middle_name, last_name, email, user_type FROM users")
        users = cursor.fetchall()

        # Get current admin's name
        cursor.execute("SELECT first_name, last_name FROM users WHERE user_id = %s", (session['user_id'],))
        current_admin = cursor.fetchone()
        cursor.close()

        # Build full name or fallback to email
        name = f"{current_admin['first_name']} {current_admin['last_name']}" if current_admin else session.get('email')

        return render_template('admin/admin_dashboard.html', users=users, name=name)

    # Access denied
    flash("Access denied!", "error")
    return redirect(url_for('login'))

# ---------- STUDENT DASHBOARD ----------
@app.route('/student')
def student_dashboard():
    if session.get('user_type', '').lower() == 'student':
        cursor = db.cursor(dictionary=True)
        
        # Fetch current student info
        cursor.execute("SELECT first_name FROM users WHERE user_id = %s", (session['user_id'],))
        current_student = cursor.fetchone()
        
        # Check if renewals are open
        cursor.execute("SELECT is_open FROM renewal_settings WHERE id = 1")
        renewal_setting = cursor.fetchone()
        renewal_open = renewal_setting['is_open'] if renewal_setting else False
        
        # Check if student has an approved application
        cursor.execute("""
            SELECT status FROM application 
            WHERE user_id = %s 
            ORDER BY submission_date DESC 
            LIMIT 1
        """, (session['user_id'],))
        app_status = cursor.fetchone()
        has_approved_application = app_status and app_status['status'] == 'approved'
        
        cursor.close()

        # Get student name or email as fallback
        first_name = current_student['first_name'] if current_student and current_student.get('first_name') else session.get('email', 'Student')
        
        return render_template('student/student_dashboard.html', 
                             first_name=first_name, 
                             renewal_open=renewal_open,
                             has_approved_application=has_approved_application)

    flash("Access denied!", "error")
    return redirect(url_for('login'))


# ---------- APPLY (NEW SCHOLARSHIP) ----------
@app.route('/apply', methods=['GET', 'POST'])
def apply():
    if session.get('user_type', '').lower() != 'student':
        flash("Access denied!", "error")
        return redirect(url_for('login'))

    # Get user information from users table
    cursor = db.cursor(dictionary=True)
    cursor.execute("""
        SELECT first_name, middle_name, last_name, email 
        FROM users 
        WHERE user_id = %s
    """, (session['user_id'],))
    user_info = cursor.fetchone()
    
    if not user_info:
        cursor.close()
        flash("User information not found!", "error")
        return redirect(url_for('login'))

    # CHECK IF USER ALREADY HAS AN APPLICATION
    cursor.execute("""
        SELECT COUNT(*) as count FROM application 
        WHERE user_id = %s AND (scholarship_type = 'new' OR scholarship_type IS NULL)
    """, (session['user_id'],))
    result = cursor.fetchone()
    
    # If user already applied, redirect them immediately
    if result and result['count'] > 0:
        cursor.close()
        flash("You have already submitted an application. You can only apply once.", "error")
        return redirect(url_for('student_dashboard'))
    
    cursor.close()

    if request.method == 'POST':
        # Get form data
        student_id = request.form.get('student_id')
        contact_number = request.form.get('contact_number')
        address = request.form.get('address')
        municipality = request.form.get('municipality')
        barangay = request.form.get('barangay')
        school_name = request.form.get('school_name')
        course = request.form.get('course')
        year_level = request.form.get('year_level')
        gwa = request.form.get('gwa')
        year_applied = request.form.get('year_applied')
        reason = request.form.get('reason')

        # Handle file uploads
        uploaded_files = {}
        for field in ['school_id', 'id_picture', 'birth_certificate', 'grades', 'cor']:
            file = request.files.get(field)
            if file and file.filename and allowed_file(file.filename):
                filename = secure_filename(file.filename)
                timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
                name, ext = os.path.splitext(filename)
                filename = f"{name}_{timestamp}{ext}"
                filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
                file.save(filepath)
                uploaded_files[field] = filename
            else:
                flash(f"Please upload a valid file for {field.replace('_', ' ').title()}", "error")
                return render_template('student/apply.html', user_info=user_info)

        cursor = db.cursor()
        try:
            # Check if user already has an application
            cursor.execute("""
                SELECT COUNT(*) as count FROM application 
                WHERE user_id = %s
            """, (session['user_id'],))
            check_result = cursor.fetchone()
            
            if check_result[0] > 0:
                cursor.close()
                flash("You have already submitted an application. You can only apply once.", "error")
                return redirect(url_for('student_dashboard'))
            
            # Get user's name from users table
            cursor.execute("SELECT first_name, middle_name, last_name FROM users WHERE user_id = %s", (session['user_id'],))
            user_data = cursor.fetchone()
            first_name = user_data[0] if user_data else ''
            middle_name = user_data[1] if user_data else ''
            last_name = user_data[2] if user_data else ''
            
            print(f"[DEBUG] User: {first_name} {middle_name} {last_name}")
            print(f"[DEBUG] Student ID: {student_id}")
            print(f"[DEBUG] Files uploaded: {list(uploaded_files.keys())}")
            
            # Insert application with all fields including name
            cursor.execute("""
                INSERT INTO application (
                    user_id, first_name, middle_name, last_name, student_id, 
                    contact_number, address, municipality, baranggay, school_name, 
                    course, year_level, gwa, year_applied, reason, 
                    school_id_path, id_picture_path, birth_certificate_path, grades_path, cor_path, scholarship_type
                ) VALUES (
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s
                )
            """, (
                session['user_id'], first_name, middle_name, last_name, student_id,
                contact_number, address, municipality, barangay, school_name,
                course, year_level, gwa, year_applied, reason,
                uploaded_files['school_id'], uploaded_files['id_picture'], 
                uploaded_files['birth_certificate'], uploaded_files['grades'], 
                uploaded_files['cor'], 'new'
            ))
            db.commit()
            print("[DEBUG] Application inserted successfully")
            
            flash("✅ Application submitted successfully!", "success")
            return redirect(url_for('student_dashboard'))
        except Exception as e:
            print(f"\n[ERROR] Application submission failed:")
            print(f"Error type: {type(e).__name__}")
            print(f"Error message: {str(e)}")
            import traceback
            traceback.print_exc()
            db.rollback()
            flash(f"❌ Error: {str(e)}", "error")
            return render_template('student/apply.html', user_info=user_info)
        finally:
            cursor.close()

    # Pass user_info to template
    return render_template('student/apply.html', user_info=user_info)

# ---------- RENEW SCHOLARSHIP ----------
@app.route('/renew', methods=['GET', 'POST'])
def renew():
    if session.get('user_type', '').lower() != 'student':
        flash("Access denied!", "error")
        return redirect(url_for('login'))

    cursor = db.cursor(dictionary=True)
    
    # Check if renewals are open
    cursor.execute("SELECT is_open FROM renewal_settings WHERE id = 1")
    renewal_setting = cursor.fetchone()
    
    if not renewal_setting or not renewal_setting['is_open']:
        cursor.close()
        flash("Renewal applications are currently closed. Please check back later.", "error")
        return redirect(url_for('student_dashboard'))
    
    # Check if user has an approved application
    cursor.execute("""
        SELECT status FROM application 
        WHERE user_id = %s 
        ORDER BY submission_date DESC 
        LIMIT 1
    """, (session['user_id'],))
    app_status = cursor.fetchone()
    
    if not app_status:
        cursor.close()
        flash("You must have an approved scholarship application before you can renew. Please apply first.", "error")
        return redirect(url_for('student_dashboard'))
    
    if app_status['status'] == 'rejected':
        cursor.close()
        flash("Your scholarship application was rejected. You cannot renew a rejected application. Please submit a new application instead.", "error")
        return redirect(url_for('student_dashboard'))
    
    if app_status['status'] == 'pending':
        cursor.close()
        flash("Your scholarship application is still pending. You can only renew after your application has been approved.", "error")
        return redirect(url_for('student_dashboard'))
    
    if app_status['status'] != 'approved':
        cursor.close()
        flash("Only students with approved scholarship applications can apply for renewal.", "error")
        return redirect(url_for('student_dashboard'))
    
    # Check if user already submitted a renewal
    cursor.execute("""
        SELECT COUNT(*) as count FROM renew WHERE user_id = %s
    """, (session['user_id'],))
    renewal_check = cursor.fetchone()
    
    if renewal_check and renewal_check['count'] > 0:
        cursor.close()
        flash("You have already submitted a renewal application.", "error")
        return redirect(url_for('student_dashboard'))
    
    # Fetch existing application data for autofill
    cursor.execute("""
        SELECT a.first_name, a.middle_name, a.last_name, a.address, 
               a.municipality, a.baranggay, a.application_id
        FROM application a
        WHERE a.user_id = %s AND a.status = 'approved'
        ORDER BY a.submission_date DESC
        LIMIT 1
    """, (session['user_id'],))
    app_data = cursor.fetchone()
    cursor.close()

    if request.method == 'POST':
        try:
            user_id = session.get('user_id')

            first_name = request.form.get('first_name')
            middle_name = request.form.get('middle_name')
            last_name = request.form.get('last_name')
            student_id = request.form.get('student_id')
            contact_number = request.form.get('contact_number')
            address = request.form.get('address')
            baranggay = request.form.get('baranggay')
            municipality = request.form.get('municipality')
            course = request.form.get('course')
            year_level = request.form.get('year_level')
            gwa = request.form.get('gwa')
            reason = request.form.get('reason')

            uploaded_files = {}
            for field in ['school_id', 'id_picture', 'birth_certificate', 'grades', 'cor']:
                file = request.files.get(field)
                if file and allowed_file(file.filename):
                    filename = secure_filename(file.filename)
                    filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
                    file.save(filepath)
                    uploaded_files[field] = filename
                else:
                    uploaded_files[field] = None

            cursor = db.cursor()
            cursor.execute("""
                INSERT INTO renew (
                    renewal_id, application_id, user_id, student_id, contact_number, 
                    address, baranggay, municipality,
                    course, year_level, gwa, reason,
                    school_id_path, id_picture_path, birth_certificate_path, grades_path, cor_path,
                    first_name, middle_name, last_name,
                    status, submission_date
                ) VALUES (NULL, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 'Pending', NOW())
            """, (
                request.form.get('application_id'), user_id, student_id, contact_number,
                address, baranggay, municipality,
                course, year_level, gwa, reason,
                uploaded_files['school_id'], uploaded_files['id_picture'],
                uploaded_files['birth_certificate'], uploaded_files['grades'], uploaded_files['cor'],
                first_name, middle_name, last_name
            ))

            db.commit()
            cursor.close()
            flash("✅ Renewal application submitted successfully!", "success")
            return redirect(url_for('student_dashboard'))

        except Exception as e:
            print("Error submitting renewal application:", e)
            import traceback
            traceback.print_exc()
            flash("❌ Error submitting renewal application. Please try again.", "error")
            return redirect(url_for('renew'))

    if not app_data:
        flash("No approved application found. Please ensure your application is approved before renewing.", "error")
        return redirect(url_for('student_dashboard'))
    
    return render_template('student/renew.html', app_data=app_data)


#==============application===============

@app.route('/my_applications')
def my_applications():
    if session.get('user_type', '').lower() != 'student':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor(dictionary=True)

    # Fetch regular applications
    cursor.execute("""
        SELECT 
            a.application_id,
            a.user_id,
            a.student_id,
            a.contact_number,
            a.address,
            a.municipality,
            a.baranggay,
            a.course,
            a.year_level,
            a.gwa,
            a.reason,
            a.scholarship_type,
            a.school_id_path,
            a.id_picture_path,
            a.birth_certificate_path,
            a.grades_path,
            a.cor_path,
            a.status,
            a.submission_date,
            a.updated_at,
            a.first_name,
            a.middle_name,
            a.last_name,
            'application' as type
        FROM application a
        WHERE a.user_id = %s
    """, (session['user_id'],))
    applications = cursor.fetchall()

    # Fetch renewal applications
    cursor.execute("""
        SELECT 
            r.renewal_id as application_id,
            r.user_id,
            r.student_id,
            r.contact_number,
            r.address,
            r.municipality,
            r.baranggay,
            r.course,
            r.year_level,
            r.gwa,
            r.reason,
            NULL as scholarship_type,
            r.school_id_path,
            r.id_picture_path,
            r.birth_certificate_path,
            r.grades_path,
            r.cor_path,
            r.status,
            r.submission_date,
            r.submission_date as updated_at,
            r.first_name,
            r.middle_name,
            r.last_name,
            'renewal' as type
        FROM renew r
        WHERE r.user_id = %s
    """, (session['user_id'],))
    renewals = cursor.fetchall()
    cursor.close()

    # Combine both lists and sort by submission_date
    all_applications = applications + renewals
    all_applications.sort(key=lambda x: x['submission_date'], reverse=True)

    return render_template('student/my_applications.html', applications=all_applications)

# ---------- EDIT APPLICATION ----------
@app.route('/edit_application/<int:app_id>', methods=['GET', 'POST'])
def edit_application(app_id):
    if session.get('user_type', '').lower() != 'student':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    app_type = request.args.get('type', 'application')
    cursor = db.cursor(dictionary=True)
    
    if request.method == 'POST':
        # Handle file uploads
        uploaded_files = {}
        for field in ['school_id', 'id_picture', 'birth_certificate', 'grades', 'cor']:
            file = request.files.get(field)
            if file and file.filename and allowed_file(file.filename):
                filename = secure_filename(file.filename)
                timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
                name, ext = os.path.splitext(filename)
                filename = f"{name}_{timestamp}{ext}"
                filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
                file.save(filepath)
                uploaded_files[field] = filename
        
        try:
            if app_type == 'renewal':
                # Update renewal
                update_parts = []
                params = []
                
                for key in ['student_id', 'contact_number', 'course', 'year_level', 'gwa', 'reason']:
                    if request.form.get(key):
                        update_parts.append(f"{key} = %s")
                        params.append(request.form.get(key))
                
                for field, filename in uploaded_files.items():
                    update_parts.append(f"{field} = %s")
                    params.append(filename)
                
                params.append(app_id)
                cursor.execute(f"UPDATE renew SET {', '.join(update_parts)} WHERE renewal_id = %s", tuple(params))
            else:
                # Update application
                update_parts = []
                params = []
                
                for key in ['student_id', 'contact_number', 'course', 'year_level', 'gwa', 'reason']:
                    if request.form.get(key):
                        update_parts.append(f"{key} = %s")
                        params.append(request.form.get(key))
                
                for field, filename in uploaded_files.items():
                    update_parts.append(f"{field} = %s")
                    params.append(filename)
                
                params.append(app_id)
                cursor.execute(f"UPDATE application SET {', '.join(update_parts)} WHERE application_id = %s", tuple(params))
            
            db.commit()
            flash("✅ Application updated successfully!", "success")
            return redirect(url_for('my_applications'))
        except Exception as e:
            print(f"Error updating application: {e}")
            db.rollback()
            flash("❌ Error updating application.", "error")
        finally:
            cursor.close()
    
    # GET request - fetch application data
    if app_type == 'renewal':
        cursor.execute("SELECT * FROM renew WHERE renewal_id = %s AND user_id = %s", (app_id, session['user_id']))
    else:
        cursor.execute("SELECT * FROM application WHERE application_id = %s AND user_id = %s", (app_id, session['user_id']))
    
    app_data = cursor.fetchone()
    cursor.close()
    
    if not app_data:
        flash("Application not found.", "error")
        return redirect(url_for('my_applications'))
    
    if app_data['status'].lower() != 'pending':
        flash("You can only edit pending applications.", "error")
        return redirect(url_for('my_applications'))
    
    return render_template('student/edit_application.html', app=app_data, app_type=app_type)

#================mayor_records==================
@app.route('/mayor/records')
def mayor_records():
    # Only mayors are allowed
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))

    show_archived = request.args.get('archived', 'false').lower() == 'true'
    section = request.args.get('section', 'applications')
    cursor = db.cursor(dictionary=True)

    # Fetch regular applications
    if show_archived:
        where_clause = "WHERE a.archived = TRUE"
    else:
        where_clause = "WHERE a.archived = FALSE OR a.archived IS NULL"

    cursor.execute(f"""
        SELECT 
            a.application_id,
            a.user_id,
            a.student_id,
            a.contact_number,
            a.address,
            a.municipality,
            a.baranggay,
            a.school_name,
            a.course,
            a.year_level,
            a.gwa,
            a.year_applied,
            a.reason,
            a.scholarship_type,
            a.school_id_path,
            a.id_picture_path,
            a.birth_certificate_path,
            a.grades_path,
            a.cor_path,
            a.status,
            a.archived,
            a.submission_date,
            a.updated_at,
            a.first_name,
            a.middle_name,
            a.last_name
        FROM application a
        {where_clause}
        ORDER BY a.submission_date DESC
    """)
    applications = cursor.fetchall()

    # Fetch renewal applications based on archived status
    if show_archived:
        renewal_where = "WHERE r.archived = TRUE"
    else:
        renewal_where = "WHERE r.archived = FALSE OR r.archived IS NULL"
    
    cursor.execute(f"""
        SELECT 
            r.renewal_id,
            r.user_id,
            r.student_id,
            r.contact_number,
            r.address,
            r.municipality,
            r.baranggay,
            r.course,
            r.year_level,
            r.gwa,
            r.reason,
            r.school_id_path,
            r.id_picture_path,
            r.birth_certificate_path,
            r.grades_path,
            r.cor_path,
            r.status,
            r.submission_date,
            r.first_name,
            r.middle_name,
            r.last_name,
            r.archived
        FROM renew r
        {renewal_where}
        ORDER BY r.submission_date DESC
    """)
    renewals = cursor.fetchall()
    
    # Get archived count
    cursor.execute("SELECT COUNT(*) as count FROM application WHERE archived = TRUE")
    archived_apps = cursor.fetchone()['count']
    cursor.execute("SELECT COUNT(*) as count FROM renew WHERE archived = TRUE")
    archived_renewals = cursor.fetchone()['count']
    archived_count = archived_apps + archived_renewals
    
    cursor.close()

    return render_template('mayor/mayor_records.html', 
                         applications=applications, 
                         renewals=renewals, 
                         show_archived=show_archived, 
                         section=section,
                         archived_count=archived_count)

#================approve renewal==================
@app.route('/mayor/approve_renewal/<int:renewal_id>', methods=['POST'])
def approve_renewal(renewal_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor(dictionary=True)
    try:
        # Get applicant email and name
        cursor.execute("""
            SELECT u.email, u.first_name, u.last_name
            FROM renew r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.renewal_id = %s
        """, (renewal_id,))
        applicant = cursor.fetchone()
        
        # Update status
        cursor.execute("UPDATE renew SET status = 'approved' WHERE renewal_id = %s", (renewal_id,))
        db.commit()
        
        # Send email notification
        if applicant:
            name = f"{applicant['first_name']} {applicant['last_name']}"
            send_status_email(applicant['email'], name, 'approved')
        
        flash("✅ Renewal approved successfully!", "success")
    except Exception as e:
        print(f"Error approving renewal: {e}")
        db.rollback()
        flash("❌ Error approving renewal.", "error")
    finally:
        cursor.close()
    
    return redirect(url_for('mayor_records', section='renewals'))

#================reject renewal==================
@app.route('/mayor/reject_renewal/<int:renewal_id>', methods=['POST'])
def reject_renewal(renewal_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor(dictionary=True)
    try:
        # Get applicant email and name
        cursor.execute("""
            SELECT u.email, u.first_name, u.last_name
            FROM renew r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.renewal_id = %s
        """, (renewal_id,))
        applicant = cursor.fetchone()
        
        # Update status
        cursor.execute("UPDATE renew SET status = 'rejected' WHERE renewal_id = %s", (renewal_id,))
        db.commit()
        
        # Send email notification
        if applicant:
            name = f"{applicant['first_name']} {applicant['last_name']}"
            send_status_email(applicant['email'], name, 'rejected')
        
        flash("ℹ️ Renewal rejected.", "info")
    except Exception as e:
        print(f"Error rejecting renewal: {e}")
        db.rollback()
        flash("❌ Error rejecting renewal.", "error")
    finally:
        cursor.close()
    
    return redirect(url_for('mayor_records', section='renewals'))

#================archive application==================
@app.route('/mayor/archive/<int:application_id>', methods=['POST'])
def archive_application(application_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor()
    try:
        cursor.execute("""
            UPDATE application 
            SET archived = TRUE 
            WHERE application_id = %s
        """, (application_id,))
        db.commit()
        flash("Application archived successfully!", "success")
    except Exception as e:
        print(f"Error archiving application: {e}")
        db.rollback()
        flash("Error archiving application.", "error")
    finally:
        cursor.close()
    
    return redirect(url_for('mayor_records'))

#================archive renewal==================
@app.route('/mayor/archive_renewal/<int:renewal_id>', methods=['POST'])
def archive_renewal(renewal_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor()
    try:
        cursor.execute("""
            UPDATE renew 
            SET archived = TRUE 
            WHERE renewal_id = %s
        """, (renewal_id,))
        db.commit()
        flash("Renewal archived successfully!", "success")
    except Exception as e:
        print(f"Error archiving renewal: {e}")
        db.rollback()
        flash("Error archiving renewal.", "error")
    finally:
        cursor.close()
    
    return redirect(url_for('mayor_records', section='renewals'))

#================unarchive application==================
@app.route('/mayor/unarchive/<int:application_id>', methods=['POST'])
def unarchive_application(application_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor()
    try:
        cursor.execute("""
            UPDATE application 
            SET archived = FALSE 
            WHERE application_id = %s
        """, (application_id,))
        db.commit()
        flash("Application restored successfully!", "success")
    except Exception as e:
        print(f"Error unarchiving application: {e}")
        db.rollback()
        flash("Error restoring application.", "error")
    finally:
        cursor.close()
    
    return redirect(url_for('mayor_records', archived='true'))

#================unarchive renewal==================
@app.route('/mayor/unarchive_renewal/<int:renewal_id>', methods=['POST'])
def unarchive_renewal(renewal_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor()
    try:
        cursor.execute("""
            UPDATE renew 
            SET archived = FALSE 
            WHERE renewal_id = %s
        """, (renewal_id,))
        db.commit()
        flash("Renewal restored successfully!", "success")
    except Exception as e:
        print(f"Error unarchiving renewal: {e}")
        db.rollback()
        flash("Error restoring renewal.", "error")
    finally:
        cursor.close()
    
    return redirect(url_for('mayor_records', archived='true'))

#================approve application==================
@app.route('/mayor/approve/<int:application_id>', methods=['POST'])
def approve_application(application_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor(dictionary=True)
    try:
        # Get applicant email and name
        cursor.execute("""
            SELECT u.email, u.first_name, u.last_name
            FROM application a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.application_id = %s
        """, (application_id,))
        applicant = cursor.fetchone()
        
        # Update status
        cursor.execute("""
            UPDATE application 
            SET status = 'approved' 
            WHERE application_id = %s
        """, (application_id,))
        db.commit()
        
        # Send email notification
        if applicant:
            name = f"{applicant['first_name']} {applicant['last_name']}"
            send_status_email(applicant['email'], name, 'approved')
        
        flash("Application approved successfully!", "success")
    except Exception as e:
        print(f"Error approving application: {e}")
        db.rollback()
        flash("Error approving application.", "error")
    finally:
        cursor.close()
    
    return redirect(url_for('mayor_records'))

#================reject application==================
@app.route('/mayor/reject/<int:application_id>', methods=['POST'])
def reject_application(application_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor(dictionary=True)
    try:
        # Get applicant email and name
        cursor.execute("""
            SELECT u.email, u.first_name, u.last_name
            FROM application a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.application_id = %s
        """, (application_id,))
        applicant = cursor.fetchone()
        
        # Update status
        cursor.execute("""
            UPDATE application 
            SET status = 'rejected' 
            WHERE application_id = %s
        """, (application_id,))
        db.commit()
        
        # Send email notification
        if applicant:
            name = f"{applicant['first_name']} {applicant['last_name']}"
            send_status_email(applicant['email'], name, 'rejected')
        
        flash("Application rejected.", "info")
    except Exception as e:
        print(f"Error rejecting application: {e}")
        db.rollback()
        flash("Error rejecting application.", "error")
    finally:
        cursor.close()
    
    return redirect(url_for('mayor_records'))
#================toggle renewal==================
@app.route('/mayor/toggle_renewal', methods=['POST'])
def toggle_renewal():
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    cursor = db.cursor(dictionary=True)
    try:
        # Check if renewal_settings exists
        cursor.execute("SELECT is_open FROM renewal_settings WHERE id = 1")
        result = cursor.fetchone()
        
        if result:
            # Toggle the current state
            new_state = not result['is_open']
            cursor.execute("UPDATE renewal_settings SET is_open = %s WHERE id = 1", (new_state,))
        else:
            # Create initial record if it doesn't exist
            cursor.execute("INSERT INTO renewal_settings (id, is_open) VALUES (1, TRUE)")
        
        db.commit()
        flash("Renewal status updated successfully!", "success")
    except Exception as e:
        print(f"Error toggling renewal: {e}")
        db.rollback()
        flash("Error updating renewal status.", "error")
    finally:
        cursor.close()
    
    return redirect(url_for('mayor_dashboard'))

#==============mayor scholars++++++++++++
@app.route('/mayor/scholars')
def mayor_scholars():
    if session.get('user_type') != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))

    cursor = db.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            a.application_id,
            a.user_id,
            a.student_id,
            a.address,
            a.municipality,
            a.baranggay,
            a.school_name,
            a.course,
            a.year_level,
            a.gwa,
            a.year_applied,
            a.reason,
            a.school_id_path,
            a.id_picture_path,
            a.birth_certificate_path,
            a.grades_path,
            a.status,
            a.submission_date,
            a.scholarship_type,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email
        FROM application a
        INNER JOIN users u ON a.user_id = u.user_id
        WHERE a.status = 'approved' AND (a.archived = FALSE OR a.archived IS NULL)
        ORDER BY a.submission_date DESC
    """)
    scholars = cursor.fetchall()
    cursor.close()

    return render_template('mayor/mayor_scholars.html', scholars=scholars)


#=============add admin===============
@app.route("/admin/add_admin", methods=["GET", "POST"])
def admin_add_admin():
    if session.get("user_type") != "admin":
        flash("Access denied!", "error")
        return redirect(url_for("login"))

    if request.method == "POST":
        first_name = request.form.get("first_name")
        middle_name = request.form.get("middle_name")
        last_name = request.form.get("last_name")
        email = request.form.get("email")
        password = request.form.get("password")
        
        name = f"{first_name} {middle_name} {last_name}".strip()

        cursor = db.cursor(dictionary=True)

        # Check if email already exists
        cursor.execute("SELECT * FROM users WHERE email = %s", (email,))
        existing = cursor.fetchone()

        if existing:
            cursor.close()
            return render_template(
                "admin/admin_add_admin.html",
                message="Email already exists!",
                success=False
            )

        # Insert new admin
        cursor.execute("""
            INSERT INTO users (name, email, password, user_type)
            VALUES (%s, %s, %s, 'admin')
        """, (name, email, password))

        db.commit()
        cursor.close()

        return render_template(
            "admin/admin_add_admin.html",
            message="Admin successfully added!",
            success=True
        )

    return render_template("admin/admin_add_admin.html")

#=============add mayor===============
@app.route("/admin/add_mayor", methods=["GET", "POST"])
def admin_add_mayor():
    if session.get("user_type") != "admin":
        flash("Access denied!", "error")
        return redirect(url_for("login"))

    if request.method == "POST":
        first_name = request.form.get("first_name")
        middle_name = request.form.get("middle_name")
        last_name = request.form.get("last_name")
        email = request.form.get("email")
        password = request.form.get("password")
        
        name = f"{first_name} {middle_name} {last_name}".strip()

        cursor = db.cursor(dictionary=True)

        # Check if email already exists
        cursor.execute("SELECT * FROM users WHERE email = %s", (email,))
        existing = cursor.fetchone()

        if existing:
            cursor.close()
            return render_template(
                "admin/admin_add_mayor.html",
                message="Email already exists!",
                success=False
            )

        # Insert new mayor
        cursor.execute("""
            INSERT INTO users (name, email, password, user_type)
            VALUES (%s, %s, %s, 'mayor')
        """, (name, email, password))

        db.commit()
        cursor.close()

        return render_template(
            "admin/admin_add_mayor.html",
            message="Mayor successfully added!",
            success=True
        )

    return render_template("admin/admin_add_mayor.html")


# ---------- LOGOUT ----------
@app.route('/logout')
def logout():
    session.clear()
    flash("Logged out successfully.", "info")
    return redirect(url_for('login'))


# ================== MAIN ==================
if __name__ == '__main__':
    app.run(debug=True)