-- 사내 그룹웨어(모의) 스키마 — 격리 실습 전용
-- users: 로그인 계정 / guestbook: 저장형 XSS 대상 게시판

CREATE TABLE IF NOT EXISTS users (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50)  NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

-- 실습 계정 admin / password (일부러 단순한 값)
INSERT IGNORE INTO users (username, password) VALUES ('admin', 'password');

CREATE TABLE IF NOT EXISTS guestbook (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  message    TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
