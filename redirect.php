<?php
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Redirecting...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class='container'>
        <h2>Redirecting...</h2>
        <div class='loader'></div>
        <p>If you are not redirected automatically, please click the link below:</p>
        <p><a href='";

// Check which file was requested
$requested_uri = $_SERVER['REQUEST_URI'];

// Better path extraction logic
$redirect_path = '';

// Check if the URL contains common application folders
if (preg_match('~/(auth|patient|doctor|admin)/(.*)~', $requested_uri, $matches)) {
    $folder = $matches[1];
    $file = $matches[2];
    $redirect_path = "clinicdentalsystem/{$folder}/{$file}";
} else {
    // Default to index for other paths
    $redirect_path = "clinicdentalsystem/index.php";
}

echo $redirect_path;

echo "'>Click here</a></p>
    </div>
    <script>
        // Redirect after 2 seconds
        setTimeout(function() {
            window.location.href = '";

// Use the same redirect path for JavaScript
echo $redirect_path;

echo "';
        }, 2000);
    </script>
</body>
</html>";
?>
