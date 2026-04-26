-- Drop tables in correct order
DROP TABLE IF EXISTS renew CASCADE;
DROP TABLE IF EXISTS application CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Create users table
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20),
    password VARCHAR(255),
    user_type VARCHAR(20) CHECK (user_type IN ('student', 'admin', 'mayor')) DEFAULT 'student',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Create application table (user_id changed to INT to match users.user_id)
CREATE TABLE application (
    application_id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    student_id VARCHAR(100) UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    contact_number VARCHAR(50),
    address VARCHAR(500),
    municipality VARCHAR(50),
    baranggay VARCHAR(45),
    school_name VARCHAR(255),
    course VARCHAR(255),
    year_level VARCHAR(50),
    gwa DECIMAL(3,2),
    year_applied INT NOT NULL,
    reason TEXT,
    scholarship_type VARCHAR(45),
    school_id_path VARCHAR(255),
    id_picture_path VARCHAR(255),
    birth_certificate_path VARCHAR(255),
    grades_path VARCHAR(255),
    cor_path VARCHAR(255),
    status VARCHAR(20) CHECK (status IN ('pending', 'approved', 'rejected', 'renewal')) DEFAULT 'pending' NOT NULL,
    archived BOOLEAN DEFAULT FALSE,
    submission_date TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);


CREATE TABLE renewal_settings (
  id INT PRIMARY KEY DEFAULT 1,
  is_open BOOLEAN DEFAULT FALSE,
  updated_at TIMESTAMP DEFAULT NOW()
);

INSERT INTO renewal_settings (id, is_open) VALUES (1, FALSE);


-- Enable RLS
ALTER TABLE "public"."users" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."application" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."renew" ENABLE ROW LEVEL SECURITY;

-- Users policies (allow authenticated users to insert/read/update)
CREATE POLICY "Enable insert for authenticated" ON "public"."users"
FOR INSERT TO authenticated WITH CHECK (true);

CREATE POLICY "Enable read for authenticated" ON "public"."users"
FOR SELECT TO authenticated USING (true);

CREATE POLICY "Enable update for authenticated" ON "public"."users"
FOR UPDATE TO authenticated USING (true) WITH CHECK (true);

-- Application policies
CREATE POLICY "Enable all for application" ON "public"."application"
FOR ALL TO authenticated USING (true) WITH CHECK (true);

-- Renew policies
CREATE POLICY "Enable all for renew" ON "public"."renew"
FOR ALL TO authenticated USING (true) WITH CHECK (true);

-- Drop existing policy
DROP POLICY IF EXISTS "Enable insert for authenticated" ON "public"."users";

-- Create new policy allowing both anon and authenticated to insert
CREATE POLICY "Enable insert for registration" ON "public"."users"
FOR INSERT WITH CHECK (true);

-- Insert 150 users
INSERT INTO users (email, first_name, middle_name, last_name, contact_number, password, user_type) VALUES
('user1@gmail.com', 'John', 'Michael', 'Smith', '09171234567', 'asdf', 'student'),
('user2@gmail.com', 'Maria', 'Elena', 'Garcia', '09181234567', 'asdf', 'student'),
('user3@gmail.com', 'Jose', 'Antonio', 'Rodriguez', '09191234567', 'asdf', 'student'),
('user4@gmail.com', 'Ana', 'Sofia', 'Martinez', '09201234567', 'asdf', 'student'),
('user5@gmail.com', 'Carlos', 'Miguel', 'Lopez', '09211234567', 'asdf', 'student'),
('user6@gmail.com', 'Elena', 'Rose', 'Hernandez', '09221234567', 'asdf', 'student'),
('user7@gmail.com', 'David', 'James', 'Gonzalez', '09231234567', 'asdf', 'student'),
('user8@gmail.com', 'Sofia', 'Grace', 'Perez', '09241234567', 'asdf', 'student'),
('user9@gmail.com', 'Miguel', 'Luis', 'Sanchez', '09251234567', 'asdf', 'student'),
('user10@gmail.com', 'Isabella', 'Marie', 'Ramirez', '09261234567', 'asdf', 'student'),
('user11@gmail.com', 'Diego', 'Rafael', 'Torres', '09271234567', 'asdf', 'student'),
('user12@gmail.com', 'Camila', 'Victoria', 'Flores', '09281234567', 'asdf', 'student'),
('user13@gmail.com', 'Gabriel', 'Daniel', 'Rivera', '09291234567', 'asdf', 'student'),
('user14@gmail.com', 'Valentina', 'Luna', 'Gomez', '09301234567', 'asdf', 'student'),
('user15@gmail.com', 'Sebastian', 'Mateo', 'Diaz', '09311234567', 'asdf', 'student'),
('user16@gmail.com', 'Lucia', 'Carmen', 'Cruz', '09321234567', 'asdf', 'student'),
('user17@gmail.com', 'Mateo', 'Andres', 'Reyes', '09331234567', 'asdf', 'student'),
('user18@gmail.com', 'Emma', 'Sophia', 'Morales', '09341234567', 'asdf', 'student'),
('user19@gmail.com', 'Lucas', 'Gabriel', 'Jimenez', '09351234567', 'asdf', 'student'),
('user20@gmail.com', 'Mia', 'Isabella', 'Ruiz', '09361234567', 'asdf', 'student'),
('user21@gmail.com', 'Daniel', 'Alejandro', 'Mendoza', '09371234567', 'asdf', 'student'),
('user22@gmail.com', 'Olivia', 'Natalia', 'Castro', '09381234567', 'asdf', 'student'),
('user23@gmail.com', 'Adrian', 'Fernando', 'Ortiz', '09391234567', 'asdf', 'student'),
('user24@gmail.com', 'Ava', 'Gabriela', 'Romero', '09401234567', 'asdf', 'student'),
('user25@gmail.com', 'Santiago', 'Nicolas', 'Alvarez', '09411234567', 'asdf', 'student'),
('user26@gmail.com', 'Charlotte', 'Diana', 'Navarro', '09421234567', 'asdf', 'student'),
('user27@gmail.com', 'Nicolas', 'Eduardo', 'Gutierrez', '09431234567', 'asdf', 'student'),
('user28@gmail.com', 'Amelia', 'Patricia', 'Ramos', '09441234567', 'asdf', 'student'),
('user29@gmail.com', 'Leonardo', 'Ricardo', 'Vasquez', '09451234567', 'asdf', 'student'),
('user30@gmail.com', 'Harper', 'Eliza', 'Castillo', '09461234567', 'asdf', 'student'),
('user31@gmail.com', 'Alejandro', 'Xavier', 'Herrera', '09471234567', 'asdf', 'student'),
('user32@gmail.com', 'Evelyn', 'Jade', 'Medina', '09481234567', 'asdf', 'student'),
('user33@gmail.com', 'Samuel', 'Isaac', 'Aguilar', '09491234567', 'asdf', 'student'),
('user34@gmail.com', 'Abigail', 'Ruby', 'Vargas', '09501234567', 'asdf', 'student'),
('user35@gmail.com', 'Benjamin', 'Oliver', 'Cortez', '09511234567', 'asdf', 'student'),
('user36@gmail.com', 'Emily', 'Claire', 'Silva', '09521234567', 'asdf', 'student'),
('user37@gmail.com', 'Elijah', 'Noah', 'Fuentes', '09531234567', 'asdf', 'student'),
('user38@gmail.com', 'Elizabeth', 'Anne', 'Mendez', '09541234567', 'asdf', 'student'),
('user39@gmail.com', 'Matias', 'Julian', 'Santiago', '09551234567', 'asdf', 'student'),
('user40@gmail.com', 'Victoria', 'Grace', 'Delgado', '09561234567', 'asdf', 'student'),
('user41@gmail.com', 'Joshua', 'Caleb', 'Moreno', '09571234567', 'asdf', 'student'),
('user42@gmail.com', 'Madison', 'Faith', 'Guzman', '09581234567', 'asdf', 'student'),
('user43@gmail.com', 'Christopher', 'Ryan', 'Rojas', '09591234567', 'asdf', 'student'),
('user44@gmail.com', 'Chloe', 'Alexandra', 'Nunez', '09601234567', 'asdf', 'student'),
('user45@gmail.com', 'Andrew', 'Thomas', 'Rios', '09611234567', 'asdf', 'student'),
('user46@gmail.com', 'Grace', 'Lily', 'Salazar', '09621234567', 'asdf', 'student'),
('user47@gmail.com', 'Nathan', 'Aaron', 'Fernandez', '09631234567', 'asdf', 'student'),
('user48@gmail.com', 'Zoey', 'Hannah', 'Pena', '09641234567', 'asdf', 'student'),
('user49@gmail.com', 'Isaac', 'Jordan', 'Campos', '09651234567', 'asdf', 'student'),
('user50@gmail.com', 'Lily', 'Violet', 'Soto', '09661234567', 'asdf', 'student'),
('user51@gmail.com', 'Ryan', 'Connor', 'Vega', '09671234567', 'asdf', 'student'),
('user52@gmail.com', 'Penelope', 'Rose', 'Duran', '09681234567', 'asdf', 'student'),
('user53@gmail.com', 'Aaron', 'Jacob', 'Guerrero', '09691234567', 'asdf', 'student'),
('user54@gmail.com', 'Aria', 'Scarlett', 'Pacheco', '09701234567', 'asdf', 'student'),
('user55@gmail.com', 'Christian', 'Luke', 'Cabrera', '09711234567', 'asdf', 'student'),
('user56@gmail.com', 'Nora', 'Stella', 'Mendoza', '09721234567', 'asdf', 'student'),
('user57@gmail.com', 'Jonathan', 'Ethan', 'Valencia', '09731234567', 'asdf', 'student'),
('user58@gmail.com', 'Layla', 'Aurora', 'Padilla', '09741234567', 'asdf', 'student'),
('user59@gmail.com', 'Liam', 'Mason', 'Cuevas', '09751234567', 'asdf', 'student'),
('user60@gmail.com', 'Hazel', 'Ivy', 'Mejia', '09761234567', 'asdf', 'student'),
('user61@gmail.com', 'Tyler', 'Blake', 'Zavala', '09771234567', 'asdf', 'student'),
('user62@gmail.com', 'Ellie', 'Maya', 'Montoya', '09781234567', 'asdf', 'student'),
('user63@gmail.com', 'Hunter', 'Owen', 'Rosales', '09791234567', 'asdf', 'student'),
('user64@gmail.com', 'Bella', 'Zoe', 'Nunez', '09801234567', 'asdf', 'student'),
('user65@gmail.com', 'Jack', 'Hudson', 'Carrillo', '09811234567', 'asdf', 'student'),
('user66@gmail.com', 'Aubrey', 'Leah', 'Molina', '09821234567', 'asdf', 'student'),
('user67@gmail.com', 'Wyatt', 'Eli', 'Villalobos', '09831234567', 'asdf', 'student'),
('user68@gmail.com', 'Addison', 'Nora', 'Cardenas', '09841234567', 'asdf', 'student'),
('user69@gmail.com', 'Dylan', 'Carter', 'Benitez', '09851234567', 'asdf', 'student'),
('user70@gmail.com', 'Savannah', 'Bella', 'Espinoza', '09861234567', 'asdf', 'student'),
('user71@gmail.com', 'Austin', 'Gavin', 'Figueroa', '09871234567', 'asdf', 'student'),
('user72@gmail.com', 'Brooklyn', 'Avery', 'Ochoa', '09881234567', 'asdf', 'student'),
('user73@gmail.com', 'Zachary', 'Ian', 'Sandoval', '09891234567', 'asdf', 'student'),
('user74@gmail.com', 'Claire', 'Madeline', 'Parra', '09901234567', 'asdf', 'student'),
('user75@gmail.com', 'Landon', 'Colton', 'Zamora', '09911234567', 'asdf', 'student'),
('user76@gmail.com', 'Skylar', 'Autumn', 'Barajas', '09921234567', 'asdf', 'student'),
('user77@gmail.com', 'Jordan', 'Chase', 'Corona', '09931234567', 'asdf', 'student'),
('user78@gmail.com', 'Lucy', 'Eva', 'Gallegos', '09941234567', 'asdf', 'student'),
('user79@gmail.com', 'Jason', 'Cole', 'Beltran', '09951234567', 'asdf', 'student'),
('user80@gmail.com', 'Anna', 'Norah', 'Luna', '09961234567', 'asdf', 'student'),
('user81@gmail.com', 'Kevin', 'Evan', 'Marquez', '09971234567', 'asdf', 'student'),
('user82@gmail.com', 'Caroline', 'Reagan', 'Dominguez', '09981234567', 'asdf', 'student'),
('user83@gmail.com', 'Brandon', 'Sean', 'Nieves', '09991234567', 'asdf', 'student'),
('user84@gmail.com', 'Natalie', 'Lillian', 'Contreras', '09101234567', 'asdf', 'student'),
('user85@gmail.com', 'Justin', 'Jaden', 'Escobar', '09111234567', 'asdf', 'student'),
('user86@gmail.com', 'Sarah', 'Katherine', 'Cordova', '09121234567', 'asdf', 'student'),
('user87@gmail.com', 'Robert', 'Wesley', 'Velasquez', '09131234567', 'asdf', 'student'),
('user88@gmail.com', 'Allison', 'Paige', 'Serrano', '09141234567', 'asdf', 'student'),
('user89@gmail.com', 'Austin', 'Parker', 'Cervantes', '09151234567', 'asdf', 'student'),
('user90@gmail.com', 'Samantha', 'Nicole', 'Montes', '09161234567', 'asdf', 'student'),
('user91@gmail.com', 'Thomas', 'Maxwell', 'Ibarra', '09162234567', 'asdf', 'student'),
('user92@gmail.com', 'Ashley', 'Morgan', 'Gallardo', '09163234567', 'asdf', 'student'),
('user93@gmail.com', 'Cameron', 'Brayden', 'Quintana', '09164234567', 'asdf', 'student'),
('user94@gmail.com', 'Taylor', 'Payton', 'Cordero', '09165234567', 'asdf', 'student'),
('user95@gmail.com', 'Logan', 'Riley', 'Salas', '09166234567', 'asdf', 'student'),
('user96@gmail.com', 'Hannah', 'Brooke', 'Maldonado', '09167234567', 'asdf', 'student'),
('user97@gmail.com', 'Caleb', 'Aiden', 'Acevedo', '09168234567', 'asdf', 'student'),
('user98@gmail.com', 'Alexis', 'Audrey', 'Lugo', '09169234567', 'asdf', 'student'),
('user99@gmail.com', 'Mason', 'Landon', 'Calderon', '09170234567', 'asdf', 'student'),
('user100@gmail.com', 'Sophia', 'Ella', 'Osorio', '09172234567', 'asdf', 'student'),
('user101@gmail.com', 'Tristan', 'Preston', 'Camacho', '09173234567', 'asdf', 'student'),
('user102@gmail.com', 'Jessica', 'Amanda', 'Ayala', '09174234567', 'asdf', 'student'),
('user103@gmail.com', 'Hunter', 'Bryce', 'Macias', '09175234567', 'asdf', 'student'),
('user104@gmail.com', 'Rachel', 'Jennifer', 'Valdez', '09176234567', 'asdf', 'student'),
('user105@gmail.com', 'Isaiah', 'Jeremiah', 'Rivas', '09177234567', 'asdf', 'student'),
('user106@gmail.com', 'Lauren', 'Julia', 'Ponce', '09178234567', 'asdf', 'student'),
('user107@gmail.com', 'Blake', 'Tanner', 'Villa', '09179234567', 'asdf', 'student'),
('user108@gmail.com', 'Melissa', 'Danielle', 'Marquez', '09180234567', 'asdf', 'student'),
('user109@gmail.com', 'Hayden', 'Brody', 'Avila', '09182234567', 'asdf', 'student'),
('user110@gmail.com', 'Kimberly', 'Michelle', 'Suarez', '09183234567', 'asdf', 'student'),
('user111@gmail.com', 'Ashton', 'Trevor', 'Rangel', '09184234567', 'asdf', 'student'),
('user112@gmail.com', 'Stephanie', 'Kayla', 'Arroyo', '09185234567', 'asdf', 'student'),
('user113@gmail.com', 'Evan', 'Miles', 'Jaramillo', '09186234567', 'asdf', 'student'),
('user114@gmail.com', 'Andrea', 'Brittany', 'Palacios', '09187234567', 'asdf', 'student'),
('user115@gmail.com', 'Xavier', 'Dominic', 'Cisneros', '09188234567', 'asdf', 'student'),
('user116@gmail.com', 'Nicole', 'Vanessa', 'Alvarado', '09189234567', 'asdf', 'student'),
('user117@gmail.com', 'Jaxon', 'Easton', 'Guerrero', '09190234567', 'asdf', 'student'),
('user118@gmail.com', 'Megan', 'Katelyn', 'Marin', '09192234567', 'asdf', 'student'),
('user119@gmail.com', 'Carson', 'Nolan', 'Osorio', '09193234567', 'asdf', 'student'),
('user120@gmail.com', 'Amy', 'Christina', 'Prado', '09194234567', 'asdf', 'student'),
('user121@gmail.com', 'Bryson', 'Bentley', 'Mora', '09195234567', 'asdf', 'student'),
('user122@gmail.com', 'Rebecca', 'Laura', 'Valenzuela', '09196234567', 'asdf', 'student'),
('user123@gmail.com', 'Peyton', 'Declan', 'Bonilla', '09197234567', 'asdf', 'student'),
('user124@gmail.com', 'Courtney', 'Kelsey', 'Solano', '09198234567', 'asdf', 'student'),
('user125@gmail.com', 'Jace', 'Griffin', 'Barraza', '09199234567', 'asdf', 'student'),
('user126@gmail.com', 'Heather', 'Erica', 'Gallegos', '09201234568', 'asdf', 'student'),
('user127@gmail.com', 'Braxton', 'Tucker', 'Murillo', '09202234567', 'asdf', 'student'),
('user128@gmail.com', 'Jasmine', 'Sierra', 'Barrera', '09203234567', 'asdf', 'student'),
('user129@gmail.com', 'Greyson', 'Sawyer', 'Ochoa', '09204234567', 'asdf', 'student'),
('user130@gmail.com', 'Alexandria', 'Jacqueline', 'Esquivel', '09205234567', 'asdf', 'student'),
('user131@gmail.com', 'Kingston', 'Ryker', 'Villarreal', '09206234567', 'asdf', 'student'),
('user132@gmail.com', 'Angela', 'Destiny', 'Ventura', '09207234567', 'asdf', 'student'),
('user133@gmail.com', 'Micah', 'Silas', 'Arellano', '09208234567', 'asdf', 'student'),
('user134@gmail.com', 'Katherine', 'Teresa', 'Caballero', '09209234567', 'asdf', 'student'),
('user135@gmail.com', 'Lincoln', 'Harrison', 'Armenta', '09210234567', 'asdf', 'student'),
('user136@gmail.com', 'Brianna', 'Monica', 'Santana', '09212234567', 'asdf', 'student'),
('user137@gmail.com', 'Brantley', 'Beckham', 'Esparza', '09213234567', 'asdf', 'student'),
('user138@gmail.com', 'Gabrielle', 'Kathryn', 'Vera', '09214234567', 'asdf', 'student'),
('user139@gmail.com', 'Maverick', 'Finnegan', 'Cano', '09215234567', 'asdf', 'student'),
('user140@gmail.com', 'Jade', 'Ruby', 'Alfaro', '09216234567', 'asdf', 'student'),
('user141@gmail.com', 'Kai', 'Rowan', 'Rosado', '09217234567', 'asdf', 'student'),
('user142@gmail.com', 'Kylie', 'Sophia', 'Blanco', '09218234567', 'asdf', 'student'),
('user143@gmail.com', 'Emmett', 'Nash', 'Acosta', '09219234567', 'asdf', 'student'),
('user144@gmail.com', 'Paige', 'Cassidy', 'Delacruz', '09220234567', 'asdf', 'student'),
('user145@gmail.com', 'Corbin', 'Knox', 'Salgado', '09222234567', 'asdf', 'student'),
('user146@gmail.com', 'Isabelle', 'Eden', 'Zarate', '09223234567', 'asdf', 'student'),
('user147@gmail.com', 'Rylan', 'Jasper', 'Peralta', '09224234567', 'asdf', 'student'),
('user148@gmail.com', 'Gianna', 'Eliana', 'Trejo', '09225234567', 'asdf', 'student'),
('user149@gmail.com', 'Archer', 'Holden', 'Aguirre', '09226234567', 'asdf', 'student'),
('user150@gmail.com', 'Clara', 'Vivian', 'Mercado', '09227234567', 'asdf', 'student');

-- Generate 150 applications for users 9 to 157
INSERT INTO application (
    user_id,
    student_id,
    first_name,
    middle_name,
    last_name,
    contact_number,
    address,
    municipality,
    baranggay,
    school_name,
    course,
    year_level,
    gwa,
    year_applied,
    reason,
    scholarship_type,
    school_id,
    id_picture,
    birth_certificate,
    grades,
    cor,
    status
)
SELECT 
    u.user_id,
    'STU-' || u.user_id,
    u.first_name,
    u.middle_name,
    u.last_name,
    u.contact_number,
    'Sample Address ' || u.user_id,
    'SampleTown',
    'Barangay ' || (u.user_id % 20),
    'Sample University',
    'BS Information Technology',
    '2nd Year',
    ROUND((RANDOM() * (1.75 - 1.25) + 1.25)::numeric, 2),
    2025,
    'Sample scholarship application reason.',
    'new',
    'SCH-' || u.user_id,
    'id_pic_' || u.user_id || '.jpg',
    'birth_cert_' || u.user_id || '.pdf',
    'grades_' || u.user_id || '.pdf',
    'cor_' || u.user_id || '.pdf',
    'pending'
FROM users u
WHERE u.user_id BETWEEN 9 AND 157;


ALTER TABLE application 
  RENAME COLUMN school_id TO school_id_path;

ALTER TABLE application 
  RENAME COLUMN id_picture TO id_picture_path;

ALTER TABLE application 
  RENAME COLUMN birth_certificate TO birth_certificate_path;

ALTER TABLE application 
  RENAME COLUMN grades TO grades_path;

ALTER TABLE application 
  RENAME COLUMN cor TO cor_path;

-- Rename columns in renew table
ALTER TABLE renew 
  RENAME COLUMN school_id TO school_id_path;

ALTER TABLE renew 
  RENAME COLUMN id_picture TO id_picture_path;

ALTER TABLE renew 
  RENAME COLUMN birth_certificate TO birth_certificate_path;

ALTER TABLE renew 
  RENAME COLUMN grades TO grades_path;

ALTER TABLE renew 
  RENAME COLUMN cor TO cor_path;

SELECT application_id, school_id_path, id_picture_path, birth_certificate_path, grades_path 
FROM application 
ORDER BY submission_date DESC 
LIMIT 5;

UPDATE application 
SET 
  school_id_path = 'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || student_id || '/school_id.jpg',
  id_picture_path = 'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || student_id || '/id_picture.jpg',
  birth_certificate_path = 'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || student_id || '/birth_cert.jpg',
  grades_path = 'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || student_id || '/grades.jpg'
WHERE school_id_path IS NULL OR school_id_path = '';

UPDATE renew 
SET 
  school_id_path = 'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || student_id || '/school_id.jpg',
  id_picture_path = 'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || student_id || '/id_picture.jpg',
  birth_certificate_path = 'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || student_id || '/birth_cert.jpg',
  grades_path = 'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || student_id || '/grades.jpg',
  cor_path = 'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || student_id || '/cor.jpg'
WHERE school_id_path IS NULL OR school_id_path = '';

DROP POLICY IF EXISTS "Public Access" ON storage.objects;

CREATE POLICY "Public Access"
ON storage.objects FOR ALL
TO public
USING (bucket_id = 'scholarship_bucket')
WITH CHECK (bucket_id = 'scholarship_bucket');

ALTER TABLE application DROP CONSTRAINT application_student_id_key;

DELETE FROM renew;
ALTER SEQUENCE renew_renewal_id_seq RESTART WITH 1;


-- Recreate renew table
CREATE TABLE renew (
    renewal_id SERIAL PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT,
    student_id VARCHAR(100),
    first_name VARCHAR(50),
    middle_name VARCHAR(50),
    last_name VARCHAR(50),
    contact_number VARCHAR(50),
    address VARCHAR(500),
    municipality VARCHAR(50),
    baranggay VARCHAR(45),
    course VARCHAR(255),
    year_level VARCHAR(50),
    gwa DECIMAL(3,2),
    reason TEXT,
    school_id_path VARCHAR(255),
    id_picture_path VARCHAR(255),
    birth_certificate_path VARCHAR(255),
    grades_path VARCHAR(255),
    cor_path VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Pending',
    archived BOOLEAN DEFAULT FALSE,
    submission_date TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (application_id) REFERENCES application(application_id) ON DELETE CASCADE
);

-- Enable RLS
ALTER TABLE "public"."renew" ENABLE ROW LEVEL SECURITY;

-- Create policy
CREATE POLICY "Enable all for renew" ON "public"."renew"
FOR ALL TO authenticated USING (true) WITH CHECK (true);

-- Insert renewals
INSERT INTO renew (
    application_id,
    user_id,
    student_id,
    first_name,
    middle_name,
    last_name,
    contact_number,
    address,
    municipality,
    baranggay,
    course,
    year_level,
    gwa,
    reason,
    school_id_path,
    id_picture_path,
    birth_certificate_path,
    grades_path,
    cor_path,
    status
)
SELECT
    a.application_id,
    a.user_id,
    a.student_id,
    a.first_name,
    a.middle_name,
    a.last_name,
    a.contact_number,
    a.address,
    a.municipality,
    a.baranggay,
    a.course,
    '3rd Year',
    ROUND((RANDOM() * (1.75 - 1.25) + 1.25)::numeric, 2),
    'Sample renewal justification.',
    'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || a.student_id || '/school_id.jpg',
    'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || a.student_id || '/id_picture.jpg',
    'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || a.student_id || '/birth_cert.jpg',
    'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || a.student_id || '/grades.jpg',
    'https://your-project.supabase.co/storage/v1/object/public/scholarship_bucket/' || a.student_id || '/cor.jpg',
    'Pending'
FROM application a
WHERE a.user_id BETWEEN 9 AND 157;
