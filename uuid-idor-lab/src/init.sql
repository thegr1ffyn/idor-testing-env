-- UUID-based IDOR Lab Database Schema
CREATE DATABASE IF NOT EXISTS reports_db;
USE reports_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    department VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reports table with UUID-based IDs
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    owner_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

-- Insert test users
INSERT INTO users (username, password, email, first_name, last_name, department) VALUES
('user_a', 'password123', 'user.a@company.com', 'Alice', 'Anderson', 'Finance'),
('user_b', 'password123', 'user.b@company.com', 'Bob', 'Brown', 'Operations');

-- Insert reports for User A (Alice Anderson - Finance Department)
INSERT INTO reports (uuid, title, content, owner_id) VALUES
('a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'Q1 Financial Analysis', 'CONFIDENTIAL REPORT - Prepared by Alice Anderson (Finance Department)\n\nDetailed financial analysis for Q1 2024. Revenue increased by 15% compared to previous quarter ($2.4M to $2.76M). Key metrics show positive growth in all sectors.\n\nSensitive Financial Data:\n- Cash Flow: $450,000 positive\n- Employee Costs: $1.2M\n- Profit Margin: 18.5%\n- Investment Portfolio: $5.2M\n\nThis report contains confidential financial information and should only be accessible to Alice Anderson and authorized Finance personnel.', 1),
('b2c3d4e5-f6g7-8901-bcde-f23456789012', 'Marketing Campaign Results', 'PRIVATE REPORT - Author: Alice Anderson (Finance Department)\n\nMarketing campaign financial analysis generated 2,500 new leads with a conversion rate of 12%. Social media engagement increased by 45%.\n\nBudget Breakdown:\n- Campaign Cost: $125,000\n- Cost per Lead: $50\n- Revenue Generated: $380,000\n- ROI: 204%\n\nNote: This financial analysis is proprietary to Alice Anderson and contains sensitive budget information.', 1),
('c3d4e5f6-g7h8-9012-cdef-345678901234', 'Employee Performance Review', 'CONFIDENTIAL HR REPORT - Prepared by Alice Anderson\n\nAnnual performance review summary for Finance team. Team productivity improved by 20%. Identified key areas for professional development.\n\nSalary Review Recommendations:\n- John Smith: Promotion + $15K salary increase\n- Jane Doe: Performance improvement plan\n- Mike Johnson: Bonus $8K\n\nThis document contains sensitive HR information accessible only to Alice Anderson.', 1),
('d4e5f6g7-h8i9-0123-defg-456789012345', 'Security Audit Report', 'RESTRICTED ACCESS - Author: Alice Anderson (Finance Security)\n\nComprehensive security audit completed. Found 3 medium-risk vulnerabilities. All issues have been addressed and patched.\n\nSecurity Issues Found:\n- Database password policy weak\n- Admin panel accessible without 2FA\n- Financial data encryption missing\n\nAccess Credentials Updated:\n- Database: fin_db_2024!SecurePass\n- Admin Panel: admin/Finance@2024\n\nThis security report is restricted to Alice Anderson only.', 1),
('e5f6g7h8-i9j0-1234-efgh-567890123456', 'Customer Satisfaction Survey', 'INTERNAL REPORT - Created by Alice Anderson\n\nCustomer satisfaction scores averaged 4.2/5. Positive feedback on product quality and customer service responsiveness.\n\nCustomer Feedback Analysis:\n- Top Complaints: Pricing (23%), Delivery times (18%)\n- Top Praise: Quality (45%), Support (32%)\n- NPS Score: 67\n\nCustomer Database: 12,450 active customers\nThis report contains proprietary customer data owned by Alice Anderson.', 1),
('f6g7h8i9-j0k1-2345-fghi-678901234567', 'Project Timeline Update', 'PROJECT STATUS - Managed by Alice Anderson (Finance Lead)\n\nProject Alpha is 75% complete and on schedule. Expected delivery date remains Q3 2024. No major blockers identified.\n\nBudget Status:\n- Total Budget: $890,000\n- Spent to Date: $667,500\n- Remaining: $222,500\n- Projected Overrun: $45,000\n\nThis project financial data is confidential to Alice Anderson and Finance team.', 1);

-- Insert reports for User B (Bob Brown - Operations Department)
INSERT INTO reports (uuid, title, content, owner_id) VALUES
('g7h8i9j0-k1l2-3456-ghij-789012345678', 'IT Infrastructure Assessment', 'CONFIDENTIAL REPORT - Prepared by Bob Brown (Operations Department)\n\nCurrent infrastructure can support 150% growth. Recommended upgrades include additional server capacity and network optimization.\n\nInfrastructure Details:\n- Server Costs: $45,000/month\n- Network Capacity: 10Gbps\n- Downtime Last Quarter: 0.02%\n- Security Incidents: 0\n\nAccess Credentials:\n- Root Server: ops_server/BobBrown@Ops2024\n- Network Admin: netadmin/Operations!2024\n\nThis technical report is restricted to Bob Brown and Operations team only.', 2),
('h8i9j0k1-l2m3-4567-hijk-890123456789', 'Budget Allocation Proposal', 'PRIVATE DOCUMENT - Author: Bob Brown (Operations Manager)\n\nProposed budget allocation for next fiscal year. 40% operations, 30% development, 20% marketing, 10% contingency.\n\nDepartment Budgets:\n- Operations: $2.4M (Bob Brown - Manager)\n- Development: $1.8M\n- Marketing: $1.2M\n- Contingency: $600M\n\nThis budget proposal contains sensitive financial planning information accessible only to Bob Brown.', 2),
('i9j0k1l2-m3n4-5678-ijkl-901234567890', 'Vendor Evaluation Report', 'RESTRICTED ACCESS - Created by Bob Brown\n\nEvaluated 5 potential vendors for cloud services. Vendor C offers best value proposition with 99.9% uptime guarantee.\n\nVendor Pricing (Confidential):\n- Vendor A: $125,000/year\n- Vendor B: $98,000/year\n- Vendor C: $87,000/year (SELECTED)\n\nContract Terms: 3 years, 15% discount for early payment\nThis vendor evaluation contains proprietary pricing information owned by Bob Brown.', 2),
('j0k1l2m3-n4o5-6789-jklm-012345678901', 'Training Program Effectiveness', 'INTERNAL ANALYSIS - Prepared by Bob Brown (Operations)\n\nNew employee training program reduced onboarding time by 30%. Employee satisfaction with training increased to 4.5/5.\n\nTraining Metrics:\n- Cost per Employee: $2,400\n- Time Reduction: 2 weeks to 1.4 weeks\n- Satisfaction Score: 4.5/5\n- Completion Rate: 94%\n\nEmployee Performance Data:\n- Best Performer: Sarah Wilson (95% score)\n- Needs Improvement: Mark Davis (67% score)\n\nThis training analysis is confidential to Bob Brown and HR department.', 2),
('k1l2m3n4-o5p6-7890-klmn-123456789012', 'Risk Assessment Analysis', 'CONFIDENTIAL RISK REPORT - Author: Bob Brown\n\nIdentified 12 potential business risks. High-priority risks include supply chain disruption and cybersecurity threats.\n\nRisk Matrix (Confidential):\n- Supply Chain Disruption: HIGH (Impact: $500K)\n- Cybersecurity Breach: MEDIUM (Impact: $200K)\n- Key Personnel Loss: MEDIUM (Impact: $150K)\n\nMitigation Strategies:\n- Insurance Coverage: $2M policy\n- Backup Suppliers: 3 alternatives identified\n- Security Investment: $75K allocated\n\nThis risk assessment is restricted to Bob Brown and executive team.', 2),
('l2m3n4o5-p6q7-8901-lmno-234567890123', 'Compliance Review Summary', 'COMPLIANCE REPORT - Prepared by Bob Brown (Operations)\n\nAnnual compliance review completed. All regulatory requirements met. Minor documentation updates required for GDPR compliance.\n\nCompliance Status:\n- ISO 27001: CERTIFIED\n- SOC 2: COMPLIANT\n- GDPR: 98% compliant (minor gaps)\n- HIPAA: NOT APPLICABLE\n\nPending Actions:\n- Update privacy policy (Due: 30 days)\n- Employee training refresh (Due: 60 days)\n- Data retention policy review (Due: 90 days)\n\nThis compliance report contains sensitive regulatory information accessible only to Bob Brown.', 2); 