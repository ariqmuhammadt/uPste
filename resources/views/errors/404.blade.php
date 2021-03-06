<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Page Not Found</title>
    <link rel="icon" type="image/png" href="{{ url('assets/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ elixir('assets/css/error.css') }}">
    <link href="//fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
</head>
<body>
<div class="container">
    <div class="content">
        <p>{{ $exception->getMessage() ?: 'Page Not Found' }}</p>
    </div>
</div>
</body>
</html>
