from flask import Flask, render_template, request, jsonify, redirect, flash, url_for, session
import os
from werkzeug.utils import secure_filename
from datetime import datetime, timedelta
from supabase import create_client, Client
from dotenv import load_dotenv
import smtplib
import random
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

# Load environment variables
load_dotenv()

app = Flask(__name__)
app.secret_key = os.getenv('SECRET_KEY', 'ren02')

# ================== FILE UPLOAD SETTINGS ==================
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'pdf'}
SUPABASE_BUCKET = 'scholarship_bucket'

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def upload_to_supabase(file, student_id, field_name):
    """Upload file to Supabase Storage and return the public URL"""
    try:
        ext = file.filename.rsplit('.', 1)[1].lower()
        file_path = f"{student_id}/{field_name}.{ext}"
        file_bytes = file.read()
        
        supabase.storage.from_(SUPABASE_BUCKET).upload(
            path=file_path,
            file=file_bytes,
            file_options={"content-type": file.content_type, "upsert": "true"}
        )
        
        public_url = supabase.storage.from_(SUPABASE_BUCKET).get_public_url(file_path)
        return public_url
    except Exception as e:
        print(f"[ERROR] Upload to Supabase failed: {e}")
        raise

# ================== SUPABASE CONNECTION ==================
supabase_url = os.getenv('SUPABASE_URL')
supabase_key = os.getenv('SUPABASE_KEY')
supabase: Client = create_client(supabase_url, supabase_key)

# ==================== EMAIL VERIFICATION SETUP ====================
verification_store = {}
SENDER_EMAIL = os.getenv('SENDER_EMAIL')
SENDER_APP_PASSWORD = os.getenv('SENDER_APP_PASSWORD')

# ==================== HELPER FUNCTIONS ====================

def generate_verification_key(email):
    return f"verify_{email}"

def store_verification_code(email, code):
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
    key = generate_verification_key(email)
    data = verification_store.get(key)
    
    if data and datetime.now() > data['expires_at']:
        print(f"[DEBUG] Verification code for {email} has expired")
        del verification_store[key]
        return None
    
    return data

def verify_code_check(email, code):
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
    
    data['verified'] = True
    print(f"[DEBUG] ✓ Email {email} verified successfully")
    return True, "Email verified successfully."

def is_email_verified(email):
    data = get_verification_data(email)
    return data and data['verified']

def cleanup_verification(email):
    key = generate_verification_key(email)
    if key in verification_store:
        del verification_store[key]
        print(f"[DEBUG] ✓ Cleaned up verification data for {email}")

def send_status_email(email, name, status):
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
                  <tr><td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding:50px 40px; text-align:center;">
                    <div style="background:rgba(255,255,255,0.2); width:80px; height:80px; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(10px);">
                      <span style="font-size:40px; color:#fff;">🎓</span>
                    </div>
                    <h1 style="margin:0; font-size:28px; font-weight:700; color:#fff; letter-spacing:-0.5px;">
                      Majayjay Scholars Program
                    </h1>
                  </td></tr>
                  <tr><td style="padding:40px 40px 20px; text-align:center;">
                    <div style="background:{gradient}; color:#fff; padding:16px 32px; border-radius:50px; display:inline-block; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
                      <span style="font-size:18px; font-weight:700; letter-spacing:1px;">{status_icon} {status_text}</span>
                    </div>
                  </td></tr>
                  <tr><td style="padding:20px 50px; text-align:center;">
                    <p style="margin:0 0 20px; font-size:17px; color:#374151; line-height:1.7;">
                      {message}
                    </p>
                    <p style="margin:0; font-size:15px; color:#6b7280; line-height:1.6;">
                      {action_text}
                    </p>
                  </td></tr>
                  <tr><td style="padding:30px 50px;">
                    <div style="height:1px; background:linear-gradient(90deg, transparent, #e5e7eb, transparent);"></div>
                  </td></tr>
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

        # Check if email already exists
        existing = supabase.table('users').select('email').eq('email', email).execute()
        if existing.data:
            print(f"[DEBUG] ❌ Email already registered: {email}")
            return jsonify({'status': 'error', 'message': 'Email already registered. Please use a different email.'}), 400

        code = f"{random.randint(100000, 999999)}"
        print(f"[DEBUG] Generated code: {code}")

        store_verification_code(email, code)

        msg = MIMEMultipart('related')
        msg['From'] = SENDER_EMAIL
        msg['To'] = email
        msg['Subject'] = "Your Verification Code - Majayjay Scholars Registration"

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
                  <tr><td style="padding:40px 50px; text-align:center;">
                    <p style="margin:0 0 30px; font-size:16px; color:#4a5568; line-height:1.6;">
                      Welcome! Please use the verification code below to complete your registration:
                    </p>
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
                  <tr><td style="padding:0 50px 40px;">
                    <div style="background:#f7fafc; border-left:4px solid #667eea; padding:16px 20px; border-radius:8px;">
                      <p style="margin:0; font-size:13px; color:#4a5568; line-height:1.5;">
                        <strong style="color:#2d3748;">🛡️ Security Tip:</strong> Never share this code with anyone. Our team will never ask for your verification code.
                      </p>
                    </div>
                  </td></tr>
                  <tr><td style="padding:0 50px;">
                    <div style="height:1px; background:linear-gradient(90deg, transparent, #e2e8f0, transparent);"></div>
                  </td></tr>
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

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        confirm = request.form.get('confirm_password')
        first_name = request.form.get('first_name')
        middle_name = request.form.get('middle_name')
        last_name = request.form.get('last_name')
        email_verified = request.form.get('email_verified')

        print(f"\n[DEBUG] Registration attempt:")
        print(f"  Email: {email}")
        print(f"  Password: {repr(password)}")
        print(f"  Confirm: {repr(confirm)}")
        print(f"  First name: {first_name}")
        print(f"  Last name: {last_name}")
        print(f"  Email verified: {email_verified}")

        if not all([email, password, confirm, first_name, last_name]):
            flash("Please fill out all required fields.", "error")
            return redirect(url_for('register'))

        if email_verified != 'true' or not is_email_verified(email):
            flash('Please verify your email before registering', 'error')
            return redirect(url_for('register'))

        if password != confirm:
            flash("Passwords do not match!", "error")
            return redirect(url_for('register'))

        if len(password) < 6:
            flash("Password must be at least 6 characters long.", "error")
            return redirect(url_for('register'))

        try:
            existing = supabase.table('users').select('email').eq('email', email).execute()
            if existing.data:
                flash("Email already registered. Please use a different email.", "error")
                return redirect(url_for('register'))
            
            response = supabase.table('users').insert({
                'email': email,
                'password': password,
                'first_name': first_name,
                'middle_name': middle_name,
                'last_name': last_name,
                'user_type': 'student'
            }).execute()
            
            cleanup_verification(email)
            flash("Registration successful! You can now log in.", "success")
            return redirect(url_for('login'))
        except Exception as e:
            flash(f"Registration failed: {str(e)}", "error")
            return redirect(url_for('register'))

    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']

        try:
            print(f"\n[DEBUG] Login attempt - Email: {email}")
            response = supabase.table('users').select('*').eq('email', email).execute()
            print(f"[DEBUG] Users found: {len(response.data) if response.data else 0}")
            
            if response.data and len(response.data) > 0:
                user = response.data[0]
                print(f"[DEBUG] User found - ID: {user['user_id']}, Type: {user['user_type']}")
                print(f"[DEBUG] Stored password: {repr(user['password'])}")
                print(f"[DEBUG] Entered password: {repr(password)}")
                print(f"[DEBUG] Passwords match: {user['password'] == password}")
                
                if user['password'] == password:
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
                    print(f"[DEBUG] Password mismatch!")
            else:
                print(f"[DEBUG] No user found with email: {email}")
            
            flash("Invalid email or password.", "error")
        except Exception as e:
            print(f"[ERROR] Login error: {e}")
            import traceback
            traceback.print_exc()
            flash("Login failed. Please try again.", "error")

    return render_template('login.html')

@app.route('/mayor')
def mayor_dashboard():
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        mayor_response = supabase.table('users').select('first_name, last_name').eq('user_id', session['user_id']).execute()
        mayor = mayor_response.data[0] if mayor_response.data else None
        name = f"{mayor['first_name']} {mayor['last_name']}" if mayor else session.get('email')
        
        apps_response = supabase.table('application').select('status').or_('archived.is.null,archived.eq.false').execute()
        new_apps = apps_response.data if apps_response.data else []
        
        renewals_response = supabase.table('renew').select('status').or_('archived.is.null,archived.eq.false').execute()
        renewals = renewals_response.data if renewals_response.data else []
        
        # Get renewal status
        settings_response = supabase.table('renewal_settings').select('is_open').eq('id', 1).execute()
        renewal_open = settings_response.data[0]['is_open'] if settings_response.data else False
        
        return render_template('mayor/mayor_dashboard.html', name=name, new_applications=new_apps, renewals=renewals, renewal_open=renewal_open)
    except Exception as e:
        print(f"[ERROR] Mayor dashboard error: {e}")
        flash("Error loading dashboard", "error")
        return redirect(url_for('login'))

@app.route('/admin')
def admin_dashboard():
    if session.get('user_type', '').lower() != 'admin':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        users_response = supabase.table('users').select('user_id, first_name, middle_name, last_name, email, user_type').execute()
        users = users_response.data if users_response.data else []
        
        admin_response = supabase.table('users').select('first_name, last_name').eq('user_id', session['user_id']).execute()
        current_admin = admin_response.data[0] if admin_response.data else None
        
        name = f"{current_admin['first_name']} {current_admin['last_name']}" if current_admin else session.get('email')
        
        return render_template('admin/admin_dashboard.html', users=users, name=name)
    except Exception as e:
        print(f"[ERROR] Admin dashboard error: {e}")
        flash("Error loading dashboard", "error")
        return redirect(url_for('login'))

@app.route('/student')
def student_dashboard():
    if session.get('user_type', '').lower() != 'student':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        student_response = supabase.table('users').select('first_name').eq('user_id', session['user_id']).execute()
        current_student = student_response.data[0] if student_response.data else None
        
        first_name = current_student['first_name'] if current_student and current_student.get('first_name') else session.get('email', 'Student')
        
        return render_template('student/student_dashboard.html', first_name=first_name)
    except Exception as e:
        print(f"[ERROR] Student dashboard error: {e}")
        flash("Error loading dashboard", "error")
        return redirect(url_for('login'))

@app.route('/apply', methods=['GET', 'POST'])
def apply():
    if session.get('user_type', '').lower() != 'student':
        flash("Access denied!", "error")
        return redirect(url_for('login'))

    try:
        user_response = supabase.table('users').select('first_name, middle_name, last_name, email').eq('user_id', session['user_id']).execute()
        
        if not user_response.data:
            flash("User information not found!", "error")
            return redirect(url_for('login'))
        
        user_info = user_response.data[0]
        
        check_response = supabase.table('application').select('application_id').eq('user_id', session['user_id']).or_('scholarship_type.eq.new,scholarship_type.is.null').execute()
        
        if check_response.data and len(check_response.data) > 0:
            flash("You have already submitted an application. You can only apply once.", "error")
            return redirect(url_for('student_dashboard'))
        
        if request.method == 'POST':
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

            uploaded_files = {}
            for field in ['school_id', 'id_picture', 'birth_certificate', 'grades', 'cor']:
                file = request.files.get(field)
                if file and file.filename and allowed_file(file.filename):
                    try:
                        public_url = upload_to_supabase(file, student_id, field)
                        uploaded_files[f"{field}_path"] = public_url
                    except Exception as e:
                        flash(f"Error uploading {field.replace('_', ' ').title()}: {str(e)}", "error")
                        return render_template('student/apply.html', user_info=user_info)
                else:
                    flash(f"Please upload a valid file for {field.replace('_', ' ').title()}", "error")
                    return render_template('student/apply.html', user_info=user_info)

            try:
                supabase.table('application').insert({
                    'user_id': session['user_id'],
                    'first_name': user_info['first_name'],
                    'middle_name': user_info['middle_name'],
                    'last_name': user_info['last_name'],
                    'student_id': student_id,
                    'contact_number': contact_number,
                    'address': address,
                    'municipality': municipality,
                    'baranggay': barangay,
                    'school_name': school_name,
                    'course': course,
                    'year_level': year_level,
                    'gwa': float(gwa),
                    'year_applied': int(year_applied),
                    'reason': reason,
                    'school_id_path': uploaded_files['school_id_path'],
                    'id_picture_path': uploaded_files['id_picture_path'],
                    'birth_certificate_path': uploaded_files['birth_certificate_path'],
                    'grades_path': uploaded_files['grades_path'],
                    'cor_path': uploaded_files['cor_path'],
                    'scholarship_type': 'new'
                }).execute()
                
                flash("✅ Application submitted successfully!", "success")
                return redirect(url_for('student_dashboard'))
            except Exception as e:
                print(f"[ERROR] Application submission failed: {e}")
                flash(f"❌ Error: {str(e)}", "error")
                return render_template('student/apply.html', user_info=user_info)
        
        return render_template('student/apply.html', user_info=user_info)
    except Exception as e:
        print(f"[ERROR] Apply route error: {e}")
        flash("Error loading application form", "error")
        return redirect(url_for('student_dashboard'))

@app.route('/renew', methods=['GET', 'POST'])
def renew():
    if session.get('user_type', '').lower() != 'student':
        flash("Access denied!", "error")
        return redirect(url_for('login'))

    try:
        # Check if renewals are open
        settings_response = supabase.table('renewal_settings').select('is_open').eq('id', 1).execute()
        renewal_open = settings_response.data[0]['is_open'] if settings_response.data else False
        
        if not renewal_open:
            flash("Renewal applications are currently closed. Please check back later.", "error")
            return redirect(url_for('student_dashboard'))
        
        # Check if student already has a renewal application
        renewal_check = supabase.table('renew').select('renewal_id').eq('user_id', session['user_id']).execute()
        
        if renewal_check.data and len(renewal_check.data) > 0:
            flash("You have already submitted a renewal application.", "error")
            return redirect(url_for('student_dashboard'))
        
        # Check if student has an APPROVED application
        app_status = supabase.table('application').select('status, first_name, middle_name, last_name, address, municipality, baranggay, application_id').eq('user_id', session['user_id']).order('submission_date', desc=True).limit(1).execute()
        
        if not app_status.data:
            flash("No previous application found. Please apply first.", "error")
            return redirect(url_for('student_dashboard'))
        
        app_data = app_status.data[0]
        
        # Only allow renewal if application is approved
        if app_data['status'] != 'approved':
            flash("You can only renew an approved scholarship. Your application must be approved first.", "error")
            return redirect(url_for('student_dashboard'))
        
        if request.method == 'POST':
            student_id = request.form.get('student_id')
            contact_number = request.form.get('contact_number')
            address = request.form.get('address')
            baranggay = request.form.get('baranggay')
            municipality = request.form.get('municipality')
            course = request.form.get('course')
            year_level = request.form.get('year_level')
            gwa = request.form.get('gwa')
            reason = request.form.get('reason')
            first_name = request.form.get('first_name')
            middle_name = request.form.get('middle_name')
            last_name = request.form.get('last_name')

            uploaded_files = {}
            for field in ['school_id', 'id_picture', 'birth_certificate', 'grades', 'cor']:
                file = request.files.get(field)
                if file and file.filename and allowed_file(file.filename):
                    try:
                        public_url = upload_to_supabase(file, student_id, field)
                        uploaded_files[f"{field}_path"] = public_url
                    except Exception as e:
                        flash(f"Error uploading {field.replace('_', ' ').title()}: {str(e)}", "error")
                        return redirect(url_for('renew'))
                else:
                    flash(f"Please upload a valid file for {field.replace('_', ' ').title()}", "error")
                    return redirect(url_for('renew'))

            try:
                supabase.table('renew').insert({
                    'application_id': request.form.get('application_id'),
                    'user_id': session['user_id'],
                    'student_id': student_id,
                    'contact_number': contact_number,
                    'address': address,
                    'baranggay': baranggay,
                    'municipality': municipality,
                    'course': course,
                    'year_level': year_level,
                    'gwa': float(gwa),
                    'reason': reason,
                    'school_id_path': uploaded_files['school_id_path'],
                    'id_picture_path': uploaded_files['id_picture_path'],
                    'birth_certificate_path': uploaded_files['birth_certificate_path'],
                    'grades_path': uploaded_files['grades_path'],
                    'cor_path': uploaded_files['cor_path'],
                    'first_name': first_name,
                    'middle_name': middle_name,
                    'last_name': last_name,
                    'status': 'Pending'
                }).execute()

                flash("✅ Renewal application submitted successfully!", "success")
                return redirect(url_for('student_dashboard'))
            except Exception as e:
                print(f"[ERROR] Renewal submission error: {e}")
                flash("❌ Error submitting renewal application. Please try again.", "error")
                return redirect(url_for('renew'))
        
        return render_template('student/renew.html', app_data=app_data)
    except Exception as e:
        print(f"[ERROR] Renew route error: {e}")
        flash("Error loading renewal form", "error")
        return redirect(url_for('student_dashboard'))

@app.route('/my_applications')
def my_applications():
    if session.get('user_type', '').lower() != 'student':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        apps_response = supabase.table('application').select('*').eq('user_id', session['user_id']).execute()
        applications = apps_response.data if apps_response.data else []
        
        for app in applications:
            app['type'] = 'application'
            # Convert submission_date string to datetime
            if app.get('submission_date') and isinstance(app['submission_date'], str):
                try:
                    app['submission_date'] = datetime.fromisoformat(app['submission_date'].replace('Z', '+00:00'))
                except:
                    app['submission_date'] = datetime.now()
        
        renewals_response = supabase.table('renew').select('*').eq('user_id', session['user_id']).execute()
        renewals = renewals_response.data if renewals_response.data else []
        
        for renewal in renewals:
            renewal['application_id'] = renewal['renewal_id']
            renewal['type'] = 'renewal'
            # Convert submission_date string to datetime
            if renewal.get('submission_date') and isinstance(renewal['submission_date'], str):
                try:
                    renewal['submission_date'] = datetime.fromisoformat(renewal['submission_date'].replace('Z', '+00:00'))
                except:
                    renewal['submission_date'] = datetime.now()
            renewal['updated_at'] = renewal['submission_date']
        
        all_applications = applications + renewals
        all_applications.sort(key=lambda x: x['submission_date'], reverse=True)
        
        return render_template('student/my_applications.html', applications=all_applications)
    except Exception as e:
        print(f"[ERROR] My applications error: {e}")
        import traceback
        traceback.print_exc()
        flash("Error loading applications", "error")
        return redirect(url_for('student_dashboard'))

@app.route('/edit_application/<int:app_id>', methods=['GET', 'POST'])
def edit_application(app_id):
    if session.get('user_type', '').lower() != 'student':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    app_type = request.args.get('type', 'application')
    
    try:
        if request.method == 'POST':
            if app_type == 'renewal':
                existing_response = supabase.table('renew').select('student_id').eq('renewal_id', app_id).execute()
            else:
                existing_response = supabase.table('application').select('student_id').eq('application_id', app_id).execute()
            
            existing_student_id = existing_response.data[0]['student_id'] if existing_response.data else None
            
            uploaded_files = {}
            for field in ['school_id', 'id_picture', 'birth_certificate', 'grades', 'cor']:
                file = request.files.get(field)
                if file and file.filename and allowed_file(file.filename) and existing_student_id:
                    try:
                        public_url = upload_to_supabase(file, existing_student_id, field)
                        uploaded_files[f"{field}_path"] = public_url
                    except Exception as e:
                        flash(f"Error uploading {field.replace('_', ' ').title()}: {str(e)}", "error")
                        return redirect(url_for('edit_application', app_id=app_id, type=app_type))
            
            update_data = {}
            for key in ['student_id', 'contact_number', 'course', 'year_level', 'gwa', 'reason']:
                if request.form.get(key):
                    update_data[key] = request.form.get(key)
            
            update_data.update(uploaded_files)
            
            if app_type == 'renewal':
                supabase.table('renew').update(update_data).eq('renewal_id', app_id).execute()
            else:
                supabase.table('application').update(update_data).eq('application_id', app_id).execute()
            
            flash("✅ Application updated successfully!", "success")
            return redirect(url_for('my_applications'))
        
        if app_type == 'renewal':
            app_response = supabase.table('renew').select('*').eq('renewal_id', app_id).eq('user_id', session['user_id']).execute()
        else:
            app_response = supabase.table('application').select('*').eq('application_id', app_id).eq('user_id', session['user_id']).execute()
        
        app_data = app_response.data[0] if app_response.data else None
        
        if not app_data:
            flash("Application not found.", "error")
            return redirect(url_for('my_applications'))
        
        if app_data['status'].lower() != 'pending':
            flash("You can only edit pending applications.", "error")
            return redirect(url_for('my_applications'))
        
        return render_template('student/edit_application.html', app=app_data, app_type=app_type)
    except Exception as e:
        print(f"[ERROR] Edit application error: {e}")
        flash("Error editing application", "error")
        return redirect(url_for('my_applications'))

# @app.route('/edit_profile', methods=['GET', 'POST'])
# def edit_profile():
#     if 'user_id' not in session:
#         flash("Please log in first.", "error")
#         return redirect(url_for('login'))
    
#     try:
#         if request.method == 'POST':
#             first_name = request.form.get('first_name')
#             middle_name = request.form.get('middle_name')
#             last_name = request.form.get('last_name')
#             password = request.form.get('password', '').strip()
            
#             # Build update dictionary
#             update_data = {
#                 'first_name': first_name,
#                 'middle_name': middle_name,
#                 'last_name': last_name
#             }
            
#             # Add password to update if provided
#             if password:
#                 update_data['password'] = password
            
#             # Execute update
#             supabase.table('users').update(update_data).eq('user_id', session['user_id']).execute()
            
#             flash("Profile updated successfully!", "success")
#             return redirect(url_for('edit_profile'))
        
#         user_response = supabase.table('users').select('first_name, middle_name, last_name, email').eq('user_id', session['user_id']).execute()
#         user = user_response.data[0] if user_response.data else None
        
#         if not user:
#             flash("User not found.", "error")
#             return redirect(url_for('login'))
        
#         return render_template('edit_profile.html', user=user)
#     except Exception as e:
#         print(f"[ERROR] Edit profile error: {e}")
#         flash("Error updating profile.", "error")
#         return redirect(url_for('edit_profile'))

@app.route('/logout')
def logout():
    session.clear()
    flash("You have been logged out.", "success")
    return redirect(url_for('login'))

@app.route('/mayor/records')
def mayor_records():
    print(f"[DEBUG] mayor_records route accessed")
    print(f"[DEBUG] Session user_type: {session.get('user_type')}")
    print(f"[DEBUG] Session data: {dict(session)}")
    
    if session.get('user_type') != 'mayor':
        print(f"[DEBUG] Access denied - user_type is not mayor")
        flash("Access denied!", "error")
        return redirect(url_for('login'))

    show_archived = request.args.get('archived', 'false').lower() == 'true'
    print(f"[DEBUG] show_archived: {show_archived}")
    
    try:
        if show_archived:
            apps_response = supabase.table('application').select('*').eq('archived', True).order('submission_date', desc=True).execute()
            renewals_response = supabase.table('renew').select('*').eq('archived', True).order('submission_date', desc=True).execute()
        else:
            apps_response = supabase.table('application').select('*').or_('archived.is.null,archived.eq.false').order('submission_date', desc=True).execute()
            renewals_response = supabase.table('renew').select('*').or_('archived.is.null,archived.eq.false').order('submission_date', desc=True).execute()
        
        applications = apps_response.data if apps_response.data else []
        renewals = renewals_response.data if renewals_response.data else []
        
        # Convert submission_date strings to datetime objects
        for app in applications:
            if app.get('submission_date') and isinstance(app['submission_date'], str):
                try:
                    app['submission_date'] = datetime.fromisoformat(app['submission_date'].replace('Z', '+00:00'))
                except:
                    app['submission_date'] = None
        
        for renewal in renewals:
            if renewal.get('submission_date') and isinstance(renewal['submission_date'], str):
                try:
                    renewal['submission_date'] = datetime.fromisoformat(renewal['submission_date'].replace('Z', '+00:00'))
                except:
                    renewal['submission_date'] = None
        
        print(f"[DEBUG] Found {len(applications)} applications and {len(renewals)} renewals")
        
        section = request.args.get('section', 'applications')
        print(f"[DEBUG] Rendering template with section: {section}")
        print(f"[DEBUG] Template variables - applications: {len(applications)}, renewals: {len(renewals)}, show_archived: {show_archived}, section: {section}")
        
        try:
            return render_template('mayor/mayor_records.html', applications=applications, renewals=renewals, show_archived=show_archived, section=section)
        except Exception as template_error:
            print(f"[ERROR] Template rendering error: {template_error}")
            import traceback
            traceback.print_exc()
            flash(f"Template error: {str(template_error)}", "error")
            return redirect(url_for('mayor_dashboard'))
    except Exception as e:
        print(f"[ERROR] Mayor records error: {e}")
        import traceback
        traceback.print_exc()
        flash("Error loading records", "error")
        return redirect(url_for('mayor_dashboard'))

@app.route('/mayor/approve/<int:application_id>', methods=['POST'])
def approve_application(application_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        app_response = supabase.table('application').select('user_id').eq('application_id', application_id).execute()
        if app_response.data:
            user_id = app_response.data[0]['user_id']
            user_response = supabase.table('users').select('email, first_name, last_name').eq('user_id', user_id).execute()
            
            supabase.table('application').update({'status': 'approved'}).eq('application_id', application_id).execute()
            
            if user_response.data:
                applicant = user_response.data[0]
                name = f"{applicant['first_name']} {applicant['last_name']}"
                send_status_email(applicant['email'], name, 'approved')
            
            flash("Application approved successfully!", "success")
    except Exception as e:
        print(f"[ERROR] Approve application error: {e}")
        flash("Error approving application.", "error")
    
    return redirect(url_for('mayor_records'))

@app.route('/mayor/reject/<int:application_id>', methods=['POST'])
def reject_application(application_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        app_response = supabase.table('application').select('user_id').eq('application_id', application_id).execute()
        if app_response.data:
            user_id = app_response.data[0]['user_id']
            user_response = supabase.table('users').select('email, first_name, last_name').eq('user_id', user_id).execute()
            
            supabase.table('application').update({'status': 'rejected'}).eq('application_id', application_id).execute()
            
            if user_response.data:
                applicant = user_response.data[0]
                name = f"{applicant['first_name']} {applicant['last_name']}"
                send_status_email(applicant['email'], name, 'rejected')
            
            flash("Application rejected.", "info")
    except Exception as e:
        print(f"[ERROR] Reject application error: {e}")
        flash("Error rejecting application.", "error")
    
    return redirect(url_for('mayor_records'))

@app.route('/mayor/approve_renewal/<int:renewal_id>', methods=['POST'])
def approve_renewal(renewal_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        renewal_response = supabase.table('renew').select('user_id').eq('renewal_id', renewal_id).execute()
        if renewal_response.data:
            user_id = renewal_response.data[0]['user_id']
            user_response = supabase.table('users').select('email, first_name, last_name').eq('user_id', user_id).execute()
            
            supabase.table('renew').update({'status': 'approved'}).eq('renewal_id', renewal_id).execute()
            
            if user_response.data:
                applicant = user_response.data[0]
                name = f"{applicant['first_name']} {applicant['last_name']}"
                send_status_email(applicant['email'], name, 'approved')
            
            flash("✅ Renewal approved successfully!", "success")
    except Exception as e:
        print(f"[ERROR] Approve renewal error: {e}")
        flash("❌ Error approving renewal.", "error")
    
    return redirect(url_for('mayor_records', section='renewals'))

@app.route('/mayor/reject_renewal/<int:renewal_id>', methods=['POST'])
def reject_renewal(renewal_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        renewal_response = supabase.table('renew').select('user_id').eq('renewal_id', renewal_id).execute()
        if renewal_response.data:
            user_id = renewal_response.data[0]['user_id']
            user_response = supabase.table('users').select('email, first_name, last_name').eq('user_id', user_id).execute()
            
            supabase.table('renew').update({'status': 'rejected'}).eq('renewal_id', renewal_id).execute()
            
            if user_response.data:
                applicant = user_response.data[0]
                name = f"{applicant['first_name']} {applicant['last_name']}"
                send_status_email(applicant['email'], name, 'rejected')
            
            flash("ℹ️ Renewal rejected.", "info")
    except Exception as e:
        print(f"[ERROR] Reject renewal error: {e}")
        flash("❌ Error rejecting renewal.", "error")
    
    return redirect(url_for('mayor_records', section='renewals'))

@app.route('/mayor/archive/<int:application_id>', methods=['POST'])
def archive_application(application_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        supabase.table('application').update({'archived': True}).eq('application_id', application_id).execute()
        flash("Application archived successfully!", "success")
    except Exception as e:
        print(f"[ERROR] Archive application error: {e}")
        flash("Error archiving application.", "error")
    
    return redirect(url_for('mayor_records'))

@app.route('/mayor/archive_renewal/<int:renewal_id>', methods=['POST'])
def archive_renewal(renewal_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        supabase.table('renew').update({'archived': True}).eq('renewal_id', renewal_id).execute()
        flash("Renewal archived successfully!", "success")
    except Exception as e:
        print(f"[ERROR] Archive renewal error: {e}")
        flash("Error archiving renewal.", "error")
    
    return redirect(url_for('mayor_records', section='renewals'))

@app.route('/mayor/unarchive/<int:application_id>', methods=['POST'])
def unarchive_application(application_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        supabase.table('application').update({'archived': False}).eq('application_id', application_id).execute()
        flash("Application restored successfully!", "success")
    except Exception as e:
        print(f"[ERROR] Unarchive application error: {e}")
        flash("Error restoring application.", "error")
    
    return redirect(url_for('mayor_records', archived='true'))

@app.route('/mayor/unarchive_renewal/<int:renewal_id>', methods=['POST'])
def unarchive_renewal(renewal_id):
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        supabase.table('renew').update({'archived': False}).eq('renewal_id', renewal_id).execute()
        flash("Renewal restored successfully!", "success")
    except Exception as e:
        print(f"[ERROR] Unarchive renewal error: {e}")
        flash("Error restoring renewal.", "error")
    
    return redirect(url_for('mayor_records', archived='true'))

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

        try:
            existing = supabase.table('users').select('*').eq('email', email).execute()
            
            if existing.data:
                return render_template("admin/admin_add_admin.html", message="Email already exists!", success=False)
            
            supabase.table('users').insert({
                'first_name': first_name,
                'middle_name': middle_name,
                'last_name': last_name,
                'email': email,
                'password': password,
                'user_type': 'admin'
            }).execute()
            
            return render_template("admin/admin_add_admin.html", message="Admin successfully added!", success=True)
        except Exception as e:
            print(f"[ERROR] Add admin error: {e}")
            return render_template("admin/admin_add_admin.html", message=f"Error: {str(e)}", success=False)

    return render_template("admin/admin_add_admin.html")

# @app.route("/admin/add_mayor", methods=["GET", "POST"])
# def admin_add_mayor():
#     if session.get("user_type") != "admin":
#         flash("Access denied!", "error")
#         return redirect(url_for("login"))

#     if request.method == "POST":
#         first_name = request.form.get("first_name")
#         middle_name = request.form.get("middle_name")
#         last_name = request.form.get("last_name")
#         email = request.form.get("email")
#         password = request.form.get("password")

#         try:
#             existing = supabase.table('users').select('*').eq('email', email).execute()
            
#             if existing.data:
#                 return render_template("admin/admin_add_mayor.html", message="Email already exists!", success=False)
            
#             supabase.table('users').insert({
#                 'first_name': first_name,
#                 'middle_name': middle_name,
#                 'last_name': last_name,
#                 'email': email,
#                 'password': password,
#                 'user_type': 'mayor'
#             }).execute()
            
#             return render_template("admin/admin_add_mayor.html", message="Mayor successfully added!", success=True)
#         except Exception as e:
#             print(f"[ERROR] Add mayor error: {e}")
#             return render_template("admin/admin_add_mayor.html", message=f"Error: {str(e)}", success=False)

#     return render_template("admin/admin_add_mayor.html")

@app.route('/mayor/toggle_renewal', methods=['POST'])
def toggle_renewal():
    if session.get('user_type', '').lower() != 'mayor':
        flash("Access denied!", "error")
        return redirect(url_for('login'))
    
    try:
        settings_response = supabase.table('renewal_settings').select('is_open').eq('id', 1).execute()
        current_value = settings_response.data[0]['is_open'] if settings_response.data else False
        new_value = not current_value
        
        supabase.table('renewal_settings').update({'is_open': new_value, 'updated_at': datetime.now().isoformat()}).eq('id', 1).execute()
        
        status = "opened" if new_value else "closed"
        flash(f"Renewal applications have been {status}.", "success")
    except Exception as e:
        print(f"[ERROR] Toggle renewal error: {e}")
        flash("Error updating renewal status.", "error")
    
    return redirect(url_for('mayor_dashboard'))

if __name__ == '__main__':
    app.run(debug=True)
