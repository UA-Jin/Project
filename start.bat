@echo off

:: 设置字符编码为UTF-8
chcp 65001 > NUL 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo 警告: 无法设置字符编码为UTF-8，可能会导致中文显示乱码。
)

:: 配置日志文件
set LOG_FILE=startup.log
if exist %LOG_FILE% del %LOG_FILE%

echo 正在启动服务器监控探针系统... >> %LOG_FILE%
echo 正在启动服务器监控探针系统...

echo === 检查系统依赖 ===

:: 检查PHP是否安装
php -v > NUL 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo 错误: 未找到PHP。请安装PHP 8.0+并添加到系统PATH。 >> %LOG_FILE%
    echo 错误: 未找到PHP。请安装PHP 8.0+并添加到系统PATH。
    echo 下载地址: https://windows.php.net/download/ >> %LOG_FILE%
    echo 下载地址: https://windows.php.net/download/
    pause
    exit /b 1
) else (
    echo √ PHP已安装 >> %LOG_FILE%
    echo √ PHP已安装
)

:: 检查Composer是否安装
composer -v > NUL 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo 错误: 未找到Composer。请安装Composer并添加到系统PATH。 >> %LOG_FILE%
    echo 错误: 未找到Composer。请安装Composer并添加到系统PATH。
    echo 下载地址: https://getcomposer.org/download/ >> %LOG_FILE%
    echo 下载地址: https://getcomposer.org/download/
    pause
    exit /b 1
) else (
    echo √ Composer已安装 >> %LOG_FILE%
    echo √ Composer已安装
)

:: 检查Node.js是否安装
node -v > NUL 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo 错误: 未找到Node.js。请安装Node.js 16+并添加到系统PATH。 >> %LOG_FILE%
    echo 错误: 未找到Node.js。请安装Node.js 16+并添加到系统PATH。
    echo 下载地址: https://nodejs.org/en/download/ >> %LOG_FILE%
    echo 下载地址: https://nodejs.org/en/download/
    pause
    exit /b 1
) else (
    echo √ Node.js已安装 >> %LOG_FILE%
    echo √ Node.js已安装
)

:: 检查npm是否安装
npm -v > NUL 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo 错误: 未找到npm。 >> %LOG_FILE%
    echo 错误: 未找到npm。
    pause
    exit /b 1
) else (
    echo √ npm已安装 >> %LOG_FILE%
    echo √ npm已安装
)


echo === 安装项目依赖 === >> %LOG_FILE%

echo === 安装项目依赖 ===


echo 1. 安装后端依赖... >> %LOG_FILE%

echo 1. 安装后端依赖...
cd backend
composer install >> %LOG_FILE% 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo 后端依赖安装失败。详细信息请查看%LOG_FILE% >> %LOG_FILE%
    echo 后端依赖安装失败。详细信息请查看%LOG_FILE%
    pause
    exit /b 1
) else (
    echo √ 后端依赖安装成功 >> %LOG_FILE%
    echo √ 后端依赖安装成功
)

cd ..



echo 2. 安装前端依赖... >> %LOG_FILE%

echo 2. 安装前端依赖...
cd frontend
npm install >> %LOG_FILE% 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo 前端依赖安装失败。详细信息请查看%LOG_FILE% >> %LOG_FILE%
    echo 前端依赖安装失败。详细信息请查看%LOG_FILE%
    pause
    exit /b 1
) else (
    echo √ 前端依赖安装成功 >> %LOG_FILE%
    echo √ 前端依赖安装成功
)

cd ..


echo === 初始化数据库 === >> %LOG_FILE%

echo === 初始化数据库 ===



echo 3. 初始化数据库... >> %LOG_FILE%

echo 3. 初始化数据库...
:: 检查.env文件是否存在
if not exist .env (
    echo 错误: 未找到.env文件。请确保已创建.env文件并配置数据库连接。 >> %LOG_FILE%
    echo 错误: 未找到.env文件。请确保已创建.env文件并配置数据库连接。
    pause
    exit /b 1
) else (
    echo √ .env文件存在 >> %LOG_FILE%
    echo √ .env文件存在
)

php backend/migrations/001_create_tables.php >> %LOG_FILE% 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo 数据库初始化失败，请检查.env文件中的数据库配置。详细信息请查看%LOG_FILE% >> %LOG_FILE%
    echo 数据库初始化失败，请检查.env文件中的数据库配置。详细信息请查看%LOG_FILE%
    pause
    exit /b 1
) else (
    echo √ 数据库初始化成功 >> %LOG_FILE%
    echo √ 数据库初始化成功
)


echo === 启动服务 === >> %LOG_FILE%

echo === 启动服务 ===



echo 4. 启动后端服务器... >> %LOG_FILE%

echo 4. 启动后端服务器...
start cmd /k "echo 启动后端服务器... && cd backend && php -S localhost:8000 -t api >> %LOG_FILE% 2>&1"
if %ERRORLEVEL% NEQ 0 (
    echo 后端服务器启动失败。详细信息请查看%LOG_FILE% >> %LOG_FILE%
    echo 后端服务器启动失败。详细信息请查看%LOG_FILE%
)


echo 5. 启动前端开发服务器... >> %LOG_FILE%

echo 5. 启动前端开发服务器...
start cmd /k "echo 启动前端开发服务器... && cd frontend && npm run dev >> %LOG_FILE% 2>&1"
if %ERRORLEVEL% NEQ 0 (
    echo 前端开发服务器启动失败。详细信息请查看%LOG_FILE% >> %LOG_FILE%
    echo 前端开发服务器启动失败。详细信息请查看%LOG_FILE%
)

echo 系统已启动！请在浏览器中访问 http://localhost:3000 >> %LOG_FILE%

echo 系统已启动！请在浏览器中访问 http://localhost:3000

echo 启动完成。详细日志请查看%LOG_FILE% >> %LOG_FILE%

echo 启动完成。详细日志请查看%LOG_FILE%

pause