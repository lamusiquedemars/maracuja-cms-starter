<?php

return [
    'temporary_file_upload' => [
        'rules' => ['required', 'file', 'max:25600'],
        'max_upload_time' => 10,
    ],
];
