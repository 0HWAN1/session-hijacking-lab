<?php
// 그룹웨어 DB 연결 (MySQLi). 단일 커넥션을 재사용한다.

function gw_db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $cfg  = require __DIR__ . '/config.php';
    $conn = @new mysqli($cfg['db_host'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_name']);

    if ($conn->connect_errno) {
        http_response_code(500);
        exit('DB 연결 실패: ' . $conn->connect_error
            . ' — scripts/03-setup-target-app.sh 를 먼저 실행했는지 확인하세요.');
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
