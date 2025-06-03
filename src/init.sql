-- DocManager Database Schema
CREATE DATABASE IF NOT EXISTS docmanager;
USE docmanager;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'manager', 'employee') DEFAULT 'employee',
    department VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Documents table
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    owner_id INT NOT NULL,
    department VARCHAR(50),
    is_confidential BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Reports table
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    author_id INT NOT NULL,
    department VARCHAR(50),
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    content TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id)
);

-- User profiles table
CREATE TABLE user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    bio TEXT,
    profile_picture VARCHAR(255),
    salary DECIMAL(10,2),
    emergency_contact VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample users
INSERT INTO users (username, password, email, first_name, last_name, role, department) VALUES
('john.doe', 'password123', 'john.doe@company.com', 'John', 'Doe', 'admin', 'IT'),
('jane.smith', 'password123', 'jane.smith@company.com', 'Jane', 'Smith', 'manager', 'HR'),
('bob.wilson', 'password123', 'bob.wilson@company.com', 'Bob', 'Wilson', 'employee', 'Finance'),
('alice.brown', 'password123', 'alice.brown@company.com', 'Alice', 'Brown', 'employee', 'Marketing'),
('charlie.davis', 'password123', 'charlie.davis@company.com', 'Charlie', 'Davis', 'employee', 'IT');

-- Insert sample documents
INSERT INTO documents (title, filename, file_path, owner_id, department, is_confidential) VALUES
('Budget Report 2024', 'budget_2024.pdf', '/uploads/budget_2024.pdf', 3, 'Finance', TRUE),
('Marketing Strategy', 'marketing_strategy.docx', '/uploads/marketing_strategy.docx', 4, 'Marketing', FALSE),
('IT Security Policy', 'security_policy.pdf', '/uploads/security_policy.pdf', 1, 'IT', TRUE),
('Employee Handbook', 'handbook.pdf', '/uploads/handbook.pdf', 2, 'HR', FALSE),
('Personal Notes', 'notes.txt', '/uploads/notes.txt', 5, 'IT', TRUE);

-- Insert sample orders
INSERT INTO orders (user_id, order_number, total_amount, status) VALUES
(1, 'ORD-2024-001', 150.50, 'completed'),
(2, 'ORD-2024-002', 89.99, 'processing'),
(3, 'ORD-2024-003', 245.00, 'pending'),
(4, 'ORD-2024-004', 67.25, 'completed'),
(5, 'ORD-2024-005', 189.75, 'pending');

-- Insert sample reports
INSERT INTO reports (title, content, author_id, department, is_public) VALUES
('Q1 Financial Summary', 'Detailed financial analysis for Q1...', 3, 'Finance', FALSE),
('HR Metrics Report', 'Employee satisfaction and retention metrics...', 2, 'HR', TRUE),
('IT Infrastructure Report', 'Current state of IT systems and recommendations...', 1, 'IT', FALSE),
('Marketing Campaign Results', 'Analysis of recent marketing campaigns...', 4, 'Marketing', TRUE),
('Internal Audit Report', 'Confidential audit findings...', 1, 'IT', FALSE);

-- Insert sample messages
INSERT INTO messages (sender_id, recipient_id, subject, content) VALUES
(1, 2, 'Meeting Tomorrow', 'Don\'t forget about our meeting tomorrow at 2 PM.'),
(2, 3, 'Budget Approval', 'Please review and approve the budget proposal.'),
(3, 4, 'Financial Data Request', 'I need the Q4 financial data for the presentation.'),
(4, 5, 'Project Update', 'Here\'s the latest update on the marketing project.'),
(5, 1, 'Server Issue', 'We\'re experiencing some server performance issues.');

-- Insert sample user profiles
INSERT INTO user_profiles (user_id, phone, address, bio, salary, emergency_contact) VALUES
(1, '555-0101', '123 Main St, City, State', 'IT Administrator with 10 years experience', 85000.00, 'Mary Doe - 555-0102'),
(2, '555-0201', '456 Oak Ave, City, State', 'HR Manager specializing in employee relations', 75000.00, 'Tom Smith - 555-0202'),
(3, '555-0301', '789 Pine Rd, City, State', 'Financial analyst with CPA certification', 65000.00, 'Lisa Wilson - 555-0302'),
(4, '555-0401', '321 Elm St, City, State', 'Marketing specialist with digital focus', 60000.00, 'Mike Brown - 555-0402'),
(5, '555-0501', '654 Maple Dr, City, State', 'Junior IT support technician', 45000.00, 'Sarah Davis - 555-0502'); 