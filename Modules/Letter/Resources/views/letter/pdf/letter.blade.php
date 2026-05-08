<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@lang($pageTitle)</title>
    <style>
        /* Force A4 size in DomPDF */
        @page {
            size: 210mm 297mm;
            margin: 0;
        }

        html {
            margin: 0;
            padding: 0;
            width: 210mm;
        }

        body {
            margin: 0;
            padding: 0;
            width: 210mm;
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
            box-sizing: border-box;
            width: 210mm;
            min-height: 297mm;
            padding-top: {{ $letter->top }}px;
            padding-bottom: {{ $letter->bottom }}px;
            padding-left: {{ $letter->left }}px;
            padding-right: {{ $letter->right }}px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Prevent images/tables from breaking across pages */
        img {
            max-width: 100%;
            page-break-inside: avoid;
        }
        table {
            page-break-inside: avoid;
            width: 100%;
        }
        p, h1, h2, h3, h4, h5, h6 {
            orphans: 3;
            widows: 3;
        }
    </style>

</head>
<body>
    <div class="text-wrap ql-editor letter-content">
        {!! $description !!}
    </div>
</body>
</html>
