-- Domain Randomizer Database Schema
-- Created: 2024-12-17
-- Author: Laurensius Jeffrey

CREATE DATABASE IF NOT EXISTS domainrandomizer;
USE domainrandomizer;

-- Set timezone to UTC+7
SET time_zone = '+07:00';

CREATE TABLE IF NOT EXISTS source_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS target_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS domain_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_domain_id INT NOT NULL,
    target_domain_id INT NOT NULL,
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (source_domain_id) REFERENCES source_domains(id),
    FOREIGN KEY (target_domain_id) REFERENCES target_domains(id)
);

CREATE TABLE IF NOT EXISTS redirects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_domain VARCHAR(255) NOT NULL,
    target_url VARCHAR(512) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO source_domains (domain) VALUES 
    ('domainA.com'),
    ('domainB.com');

INSERT INTO target_domains (domain) VALUES 
    ('TargetA.com'),
    ('TargetB.com'),
    ('TargetC.com'),
    ('TargetD.com');

-- Set up rules
INSERT INTO domain_rules (source_domain_id, target_domain_id)
SELECT 
    sd.id as source_domain_id,
    td.id as target_domain_id
FROM source_domains sd, target_domains td
WHERE 
    (sd.domain = 'domainA.com' AND td.domain IN ('TargetA.com', 'TargetB.com'))
    OR 
    (sd.domain = 'domainB.com' AND td.domain IN ('TargetC.com', 'TargetD.com'));
