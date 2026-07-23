CREATE DATABASE IF NOT EXISTS impact_hope;
USE impact_hope;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- University applications table
CREATE TABLE IF NOT EXISTS university (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(150),
    marks INT,
    national_id VARCHAR(50),
    asylum_cert VARCHAR(100),
    origin_country VARCHAR(100),
    province VARCHAR(100),
    district VARCHAR(100),
    sector VARCHAR(100),
    cell VARCHAR(100),
    village VARCHAR(100),
    parent_phone VARCHAR(20),
    parent_names VARCHAR(200),
    email VARCHAR(100),
    father_name VARCHAR(150),
    mother_name VARCHAR(150),
    alevel_subject VARCHAR(150),
    camp VARCHAR(100),
    result_slip VARCHAR(255),
    proof_photocopy VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- A-Level applications table
CREATE TABLE IF NOT EXISTS alevel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(150),
    marks INT,
    national_id VARCHAR(50),
    asylum_cert VARCHAR(100),
    origin_country VARCHAR(100),
    province VARCHAR(100),
    district VARCHAR(100),
    sector VARCHAR(100),
    cell VARCHAR(100),
    village VARCHAR(100),
    parent_phone VARCHAR(20),
    parent_names VARCHAR(200),
    email VARCHAR(100),
    father_name VARCHAR(150),
    mother_name VARCHAR(150),
    camp VARCHAR(100),
    result_slip VARCHAR(255),
    proof_photocopy VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Summer training table
CREATE TABLE IF NOT EXISTS summer_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(150),
    previous_level VARCHAR(50), -- S5 / Level 4
    next_level VARCHAR(50),     -- S6 / Level 5
    school_name VARCHAR(150),
    subject_studied VARCHAR(150),
    parent_phone VARCHAR(20),
    gender VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Non-university / Short courses table
CREATE TABLE IF NOT EXISTS short_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(150),
    national_id VARCHAR(50),
    course_studied VARCHAR(150),
    school_attended VARCHAR(150),
    course_desired VARCHAR(150),
    camp VARCHAR(100),
    origin_country VARCHAR(100),
    province VARCHAR(100),
    district VARCHAR(100),
    sector VARCHAR(100),
    cell VARCHAR(100),
    village VARCHAR(100),
    diploma_upload VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System Logs
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);