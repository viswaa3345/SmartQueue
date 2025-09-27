<?php
$data = [
  "email" => "admin@example.com",
  "password" => "admin123"
];

$options = [
  "http" => [
    "header"  => "Content-type: application/json\r\n",
    "method"  => "POST",
    "content" => json_encode($data),
  ],
];

$context  = stream_context_create($options);
$result = file_get_contents("http://localhost/queue_app/api/login.php", false, $context);
echo $result;
?>
