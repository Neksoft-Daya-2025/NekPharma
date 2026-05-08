<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@lang($pageTitle)</title>
    <link type="text/css" rel="stylesheet" media="all" href="{{ asset('css/main.css') }}">
    <style>
        html, body {
            padding: 0;
            margin: 0;
            @php
                $letterSetting = \Modules\Letter\Entities\LetterSetting::where('company_id', company()->id)->first();
                if ($letterSetting && $letterSetting->background_image) {
                    $bgImageUrl = asset_url_local_s3('letter-background/' . $letterSetting->background_image);
                    echo 'background-image: url(' . $bgImageUrl . ');';
                    echo 'background-size: cover;';
                    echo 'background-position: center;';
                    echo 'background-repeat: no-repeat;';
                    echo 'background-attachment: fixed;';
                }
            @endphp
        }
        .letter-content {
            @php
                if ($letterSetting && $letterSetting->background_image) {
                    echo 'background-color: rgba(255, 255, 255, 0.95);';
                } else {
                    echo 'background-color: #fff;';
                }
            @endphp
            padding-top: {{ $letter->top }}px;
            padding-bottom: {{ $letter->bottom }}px;
            padding-left: {{ $letter->left }}px;
            padding-right: {{ $letter->right }}px;
        }
    </style>

</head>
<body>
    <div class="text-wrap ql-editor letter-content">
        {!! $description !!}
    </div>
</body>
</html>
