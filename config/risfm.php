<?php

return [
    'mission_reminders' => [
        'before_due_days' => 2,
        'escalation_after_days' => 3,
        'schedule_time' => '07:00',
        'mail_enabled' => false,
    ],
    'backups' => [
        'disk' => 'local',
        'directory' => 'backups/database',
        'retention_days' => 14,
        'schedule_time' => '02:00',
        'mysqldump_binary' => 'mysqldump',
        'mysql_binary' => 'mysql',
        'process_timeout' => 900,
        'confirmation_phrase' => 'RESTAURER OIPI',
    ],
    'attachments' => [
        'disk' => 'local',
        'directory' => 'attachments/forms',
        'max_size_kb' => 10240,
        'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
        'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png'],
    ],
    'registry_import' => [
        'disk' => 'local',
        'directory' => 'imports/registry',
        'max_file_size_kb' => 20480,
        'max_rows' => 100000,
        'chunk_size' => 1000,
        'xlsx' => [
            'max_entries' => 1024,
            'max_directory_bytes' => 1048576,
            'max_uncompressed_bytes' => 268435456,
            'max_part_bytes' => 134217728,
            'max_sheets' => 64,
            'max_columns' => 64,
            'max_cell_bytes' => 4096,
            'max_shared_strings' => 1000000,
        ],
    ],
];
