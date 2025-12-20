# ROKAPASS 서버 설정 가이드

## 서버 사양 (권장)
- OS: Ubuntu 22.04 LTS
- CPU: 2코어 이상
- RAM: 4GB 이상
- SSD: 40GB 이상

---

## 1. 서버 초기 설정

### 1.1 시스템 업데이트
```bash
sudo apt update && sudo apt upgrade -y
```

### 1.2 기본 패키지 설치
```bash
sudo apt install -y curl wget git unzip software-properties-common
```

### 1.3 시간대 설정
```bash
sudo timedatectl set-timezone Asia/Seoul
```

### 1.4 Swap 메모리 설정 (RAM 4GB 미만 시)
```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## 2. 필수 소프트웨어 설치

### 2.1 PHP 8.2 설치
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl \
    php8.2-xml php8.2-bcmath php8.2-intl php8.2-readline \
    php8.2-opcache php8.2-redis
```

### 2.2 Composer 설치
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2.3 MariaDB 설치
```bash
sudo apt install -y mariadb-server mariadb-client

# MariaDB 서비스 시작 및 활성화
sudo systemctl start mariadb
sudo systemctl enable mariadb

# MariaDB 보안 설정
sudo mysql_secure_installation
```

### 2.4 MariaDB 데이터베이스 생성
```bash
sudo mariadb -u root -p

# MariaDB 콘솔에서 실행
CREATE DATABASE rokapass CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rokapass'@'localhost' IDENTIFIED BY '강력한_비밀번호_입력';
GRANT ALL PRIVILEGES ON rokapass.* TO 'rokapass'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2.5 Nginx 설치
```bash
sudo apt install -y nginx
sudo systemctl enable nginx
```

### 2.6 Node.js 20.x 설치
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2.7 Redis 설치 (캐시/세션용, 선택사항)
```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
```

---

## 3. 프로젝트 배포

### 3.1 프로젝트 디렉토리 생성
```bash
sudo mkdir -p /var/www/rokapass
sudo chown -R $USER:www-data /var/www/rokapass
```

### 3.2 Git에서 프로젝트 클론 (또는 파일 업로드)
```bash
cd /var/www/rokapass
git clone https://github.com/your-repo/rokapass.git .
# 또는 SFTP로 파일 업로드
```

### 3.3 Backend 설정
```bash
cd /var/www/rokapass/backend

# Composer 의존성 설치
composer install --optimize-autoloader --no-dev

# 환경설정 파일 복사 및 수정
cp .env.example .env
nano .env
```

### 3.4 .env 파일 수정 (Backend)
```env
APP_NAME=ROKAPASS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rokapass
DB_USERNAME=rokapass
DB_PASSWORD=강력한_비밀번호_입력

SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=.your-domain.com
```

### 3.5 Laravel 설정 마무리
```bash
cd /var/www/rokapass/backend

# 앱 키 생성
php artisan key:generate

# 캐시 최적화
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 마이그레이션 실행
php artisan migrate --force

# 초기 데이터 시딩
php artisan db:seed --force

# 스토리지 링크
php artisan storage:link

# 권한 설정
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 3.6 Frontend 빌드
```bash
cd /var/www/rokapass/frontend

# 의존성 설치
npm install

# 환경설정
cp .env.example .env
nano .env
```

### 3.7 .env 파일 수정 (Frontend)
```env
VITE_API_URL=https://your-domain.com/api
```

### 3.8 Frontend 빌드
```bash
npm run build
```

---

## 4. Nginx 설정

### 4.1 Nginx 설정 파일 생성
```bash
sudo nano /etc/nginx/sites-available/rokapass
```

### 4.2 Nginx 설정 내용
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;

    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    # SSL 인증서 (Certbot 설치 후 자동 설정됨)
    # ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # Frontend (Vue.js)
    root /var/www/rokapass/frontend/dist;
    index index.html;

    # Gzip 압축
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Frontend 라우팅 (SPA)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API 요청을 Laravel로 프록시
    location /api {
        alias /var/www/rokapass/backend/public;
        try_files $uri $uri/ @backend;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }

    @backend {
        rewrite ^/api/(.*)$ /api/index.php?$1 last;
    }

    # Laravel API
    location ~ ^/api {
        root /var/www/rokapass/backend/public;

        # PHP 처리
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/rokapass/backend/public/index.php;
            include fastcgi_params;
        }

        try_files $uri /index.php?$query_string;
    }

    # Sanctum CSRF 쿠키
    location /sanctum {
        root /var/www/rokapass/backend/public;
        try_files $uri /index.php?$query_string;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/rokapass/backend/public/index.php;
            include fastcgi_params;
        }
    }

    # 정적 파일 캐싱
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 로그
    access_log /var/log/nginx/rokapass.access.log;
    error_log /var/log/nginx/rokapass.error.log;
}
```

### 4.3 간단한 대안 설정 (API 서브도메인 사용)
```nginx
# api.your-domain.com용 설정
server {
    listen 80;
    server_name api.your-domain.com;
    root /var/www/rokapass/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

# your-domain.com용 설정 (Frontend)
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/rokapass/frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

### 4.4 설정 활성화
```bash
sudo ln -s /etc/nginx/sites-available/rokapass /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## 5. SSL 인증서 설치 (Let's Encrypt)

```bash
# Certbot 설치
sudo apt install -y certbot python3-certbot-nginx

# SSL 인증서 발급
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 자동 갱신 테스트
sudo certbot renew --dry-run
```

---

## 6. 방화벽 설정 (UFW)

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

---

## 7. 크론 작업 설정

### 7.1 Laravel 스케줄러 등록
```bash
sudo crontab -e
```

다음 줄 추가:
```cron
* * * * * cd /var/www/rokapass/backend && php artisan schedule:run >> /dev/null 2>&1
```

### 7.2 자동 데이터 파기 스케줄러 (Laravel에서 설정)
`backend/app/Console/Kernel.php`에 이미 설정되어 있음

---

## 8. 보안 추가 설정

### 8.1 Fail2ban 설치 (SSH 브루트포스 방지)
```bash
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
```

### 8.2 SSH 보안 강화
```bash
sudo nano /etc/ssh/sshd_config
```

변경 사항:
```
PermitRootLogin no
PasswordAuthentication no  # SSH 키 설정 후
MaxAuthTries 3
```

### 8.3 PHP 보안 설정
```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

변경 사항:
```ini
expose_php = Off
display_errors = Off
log_errors = On
```

---

## 9. 서버 모니터링 (선택사항)

### 9.1 htop 설치
```bash
sudo apt install -y htop
```

### 9.2 Supervisor 설치 (Laravel Queue용)
```bash
sudo apt install -y supervisor

sudo nano /etc/supervisor/conf.d/rokapass-worker.conf
```

```ini
[program:rokapass-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/rokapass/backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/rokapass/backend/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start rokapass-worker:*
```

---

## 10. 배포 후 확인사항

### 10.1 체크리스트
- [ ] https://your-domain.com 접속 확인
- [ ] 관리자 로그인 테스트 (admin@rokapass.kr / password)
- [ ] 비밀번호 변경!
- [ ] 예비군 문진표 플로우 테스트
- [ ] QR 코드 생성 테스트
- [ ] 키오스크 페이지 테스트
- [ ] 엑셀 내보내기 테스트

### 10.2 초기 비밀번호 변경
관리자 페이지 접속 후 반드시 비밀번호를 변경하세요!

---

## 문제 해결

### 로그 확인
```bash
# Nginx 로그
sudo tail -f /var/log/nginx/rokapass.error.log

# Laravel 로그
tail -f /var/www/rokapass/backend/storage/logs/laravel.log

# PHP-FPM 로그
sudo tail -f /var/log/php8.2-fpm.log
```

### 권한 문제
```bash
sudo chown -R www-data:www-data /var/www/rokapass/backend/storage
sudo chmod -R 775 /var/www/rokapass/backend/storage
```

### 캐시 클리어
```bash
cd /var/www/rokapass/backend
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```
