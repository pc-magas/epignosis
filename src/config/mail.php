<?php

return [
    'host'=>$_ENV['SMTP_HOST']??'localhost',
    'port'=>$_ENV['SMTP_PORT']??25,
    'username'=>$_ENV['SMTP_USER']??'',
    'password'=>$_ENV['SMTP_PASS']??''
];