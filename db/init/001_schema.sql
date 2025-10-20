CREATE TABLE IF NOT EXISTS employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company VARCHAR(120) NOT NULL,
  name    VARCHAR(120) NOT NULL,
  email   VARCHAR(200) NOT NULL,
  salary  INT NOT NULL DEFAULT 0
);

INSERT INTO employees(company,name,email,salary) VALUES
('ACME','John Doe','john@acme.com',50000),
('ACME','Jane Doe','jane@acme.com',55000),
('Stark','Tony Stark','tony@stark.com',100000);
