<?php
/**
 * Файл содержит константы, используемые для настроек приложения по умолчанию
 */

/**
 * Константа устанавливает абсолютный путь к корневой директории проекта
 */
define("ROOTDIR", dirname(__DIR__));
/**
 * Константа для определения пути к настройкам по умолчанию
 */
define("SETTINGS_PATH", ROOTDIR."/config/config.ini");
define("STORAGE",'storage');
define ("FILES_ALLOWED_TYPES",['jpg','jpeg','png','pdf','doc','docx','txt']);
define ("ALLOWED_FILE_MIME_TYPES",['image/jpg','image/jpeg','image/png','application/pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/msword','text/plain']);
define('MAX_FILE_UPLOAD_SIZE', 100000000);
define('SANDBOX_STORE_TIME',3600);