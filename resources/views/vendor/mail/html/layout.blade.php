<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
@php
    $settings = app(\Artwork\Modules\GeneralSettings\Models\GeneralSettings::class);
@endphp
<head>
    <title>
    </title>
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--<![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <style type="text/css">
        #outlook a {
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        p {
            display: block;
            margin: 13px 0;
        }

        /* Benachrichtigungs-Mails (emails.simple-mail, emails.notifications) */
        .notification,
        .email-content {
            padding: 10px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .notification {
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .email-content {
            margin-bottom: 2rem;
        }

        .email-greeting {
            margin-top: 3rem;
            margin-bottom: 2rem;
            font-size: 16px;
            font-weight: 500;
        }

        .notification-group {
            margin-bottom: 1rem;
        }

        .email-content h1 {
            color: #27233C;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .email-content h1 span {
            font-size: 12px;
            background-color: #EBEBE8;
            border-radius: 5px;
            min-width: 20px;
            min-height: 20px;
            color: #A7A6B1;
            text-align: center;
            margin-left: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
        }

        .notification h2,
        .email-content h2 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 0.2rem;
            color: #27233C;
        }

        .notification-content {
            margin-bottom: 1rem;
        }

        .notification-text,
        .notification-text p,
        .notification-description {
            font-size: 12px;
            font-weight: 500;
        }

        .notification-description {
            margin: 0.2rem 0;
        }

        .notification-link {
            display: inline-block;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            font-size: 12px;
            text-decoration: none;
            color: #3017AD;
        }

        .notification-link-secondary {
            margin-top: 0.25rem;
        }

        .notification-link-footer {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
        }

    </style>
    <!--[if mso]>
    <noscript>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    </noscript>
    <![endif]-->
    <!--[if lte mso 11]>
    <style type="text/css">
    .mj-outlook-group-fix { width:100% !important; }
    </style>
    <![endif]-->
    {{-- Keine externen Font-Abrufe (Datenschutz, Sicherheits-Audit 21.09.2026): System-Font-Stack. --}}
    <style type="text/css">
        @media only screen and (min-width:480px) {
            .mj-column-per-50 {
                width: 50% !important;
                max-width: 50%;
            }

            .mj-column-per-100 {
                width: 100% !important;
                max-width: 100%;
            }

            .mj-column-per-25 {
                width: 25% !important;
                max-width: 25%;
            }
        }

    </style>
    <style media="screen and (min-width:480px)">
        .moz-text-html .mj-column-per-50 {
            width: 50% !important;
            max-width: 50%;
        }

        .moz-text-html .mj-column-per-100 {
            width: 100% !important;
            max-width: 100%;
        }

        .moz-text-html .mj-column-per-25 {
            width: 25% !important;
            max-width: 25%;
        }

    </style>
    <style type="text/css">
        @media only screen and (max-width:480px) {
            table.mj-full-width-mobile {
                width: 100% !important;
            }

            td.mj-full-width-mobile {
                width: auto !important;
            }
        }

    </style>
    <style type="text/css">
    </style>
</head>

<body style="word-spacing:normal;">
<div style="">
    <!-- HEADER -->
    <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" role="presentation" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
    <div style="margin:0px auto;max-width:600px;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:0 25px;text-align:center;">
                    <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:middle;width:275px;" ><![endif]-->
                    <div class="mj-column-per-50 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:middle;padding:0;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
                                        <tbody>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                                    <tbody>
                                                    <tr>
                                                        <td style="width:200px;">
                                                            @if($settings->big_logo_path)
                                                                <img height="auto" src="{{ asset('storage/' . $settings->big_logo_path) }}" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                                            @elseif($settings->small_logo_path)
                                                                <img height="auto" src="{{ asset('storage/' . $settings->small_logo_path) }}" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                                            @else
                                                                <img height="auto" src="{{ asset('Svgs/Logos/artwork_logo_big.svg') }}" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!--[if mso | IE]></td></tr></table><![endif]-->
    <!-- CONTENT -->
    <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" role="presentation" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
    <div style="margin:0px auto;max-width:600px;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
                {{$slot}}
            </tbody>
        </table>
    </div>
    <!--[if mso | IE]></td></tr></table><![endif]-->
    <!-- FOOTER -->
    <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" role="presentation" style="width:600px;" width="600" bgcolor="#27233C" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
    <div style="background:#27233C;background-color:#27233C;margin:0px auto;max-width:600px;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#27233C;background-color:#27233C;width:100%;">
            <tbody>
                <tr>
                    <td align="left" style="font-size:0px;padding:25px 50px;word-break:break-word;">
                        <a href="{{$settings->impressum_link}}" style="font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;font-size:14px;line-height:1;text-align:left;color:#A7A6B1;">
                            Impressum
                        </a>
                        <a href="{{ $settings->privacy_link}}" style="font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;font-size:14px;line-height:1;text-align:left;color:#A7A6B1; margin-left: 3em">
                            Datenschutz
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!--[if mso | IE]></td></tr></table><![endif]-->
</div>
</body>

</html>
