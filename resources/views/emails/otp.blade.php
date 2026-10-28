<!DOCTYPE html>
<html lang="id" translate="no" class="notranslate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="google" content="notranslate">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Kode Verifikasi - {{ config('app.name', 'HRIS SaaS') }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }

        body, table, td, p, a, div, span {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        @media (prefers-color-scheme: dark) {
            .email-bg {
                background-color: #0b1120 !important;
            }
            .card-bg {
                background-color: #1e293b !important;
                border-color: #334155 !important;
            }
            .text-brand {
                color: #34d399 !important;
            }
            .border-sep {
                border-color: #334155 !important;
            }
            .text-title {
                color: #f8fafc !important;
            }
            .text-main {
                color: #cbd5e1 !important;
            }
            .otp-box {
                background-color: #0f172a !important;
                border-color: #334155 !important;
            }
            .otp-label {
                color: #94a3b8 !important;
            }
            .otp-number {
                color: #34d399 !important;
            }
            .otp-sub {
                color: #94a3b8 !important;
            }
            .alert-bg {
                background-color: #291e0a !important;
                border-color: #d97706 !important;
            }
            .alert-msg {
                color: #fde68a !important;
            }
            .footer-copy {
                color: #64748b !important;
                border-color: #334155 !important;
            }
        }

        @media only screen and (max-width: 600px) {
            .card-wrapper {
                padding: 24px 18px !important;
            }
            .otp-number {
                font-size: 32px !important;
                letter-spacing: 8px !important;
            }
        }
    </style>
</head>
<body class="email-bg notranslate" translate="no" style="margin: 0; padding: 0; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    <!-- Outer Wrapper Table -->
    <table class="email-bg notranslate" translate="no" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0" style="background-color: #f1f5f9; margin: 0 auto; padding: 32px 12px;">
        <tr>
            <td align="center" style="padding: 0;">

                <!-- Container Card (Clean, Modern, Single Surface) -->
                <table class="card-bg card-wrapper" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0" style="max-width: 480px; width: 100%; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 32px 28px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);">
                    
                    <!-- 1. Brand Logo / Text Header -->
                    <tr>
                        <td align="left" class="border-sep" style="padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;">
                            <span class="text-brand" style="font-size: 20px; font-weight: 800; color: #059669; letter-spacing: -0.5px; font-family: inherit;">
                                {{ config('app.name', 'HRIS SaaS') }}
                            </span>
                        </td>
                    </tr>

                    <!-- 2. Greeting & Intro Message -->
                    <tr>
                        <td align="left" style="padding-top: 24px;">
                            <h2 class="text-title" style="margin: 0 0 10px 0; font-size: 16px; font-weight: 600; color: #0f172a; font-family: inherit;">
                                Halo,
                            </h2>
                            <p class="text-main" style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569; font-family: inherit;">
                                @if ($type === 'reset_password')
                                    Kami menerima permintaan untuk mereset kata sandi akun <strong>{{ config('app.name', 'HRIS SaaS') }}</strong> Anda. Silakan gunakan kode verifikasi berikut:
                                @elseif ($type === 'change_email')
                                    Kami menerima permintaan untuk mengubah alamat email akun <strong>{{ config('app.name', 'HRIS SaaS') }}</strong> Anda ke alamat email ini. Silakan gunakan kode verifikasi berikut:
                                @else
                                    Terima kasih telah mendaftar di <strong>{{ config('app.name', 'HRIS SaaS') }}</strong>. Silakan gunakan kode verifikasi berikut untuk menyelesaikan pendaftaran akun Anda:
                                @endif
                            </p>

                            <!-- 3. Professional OTP Code Box (Clean, Sleek, Tanpa Icon Jam) -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0" style="margin: 22px 0;">
                                <tr>
                                    <td align="center" class="otp-box" style="background-color: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 22px 16px;">
                                        <div class="otp-label" style="font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #64748b; font-weight: 600; font-family: inherit;">
                                            KODE VERIFIKASI
                                        </div>
                                        <div class="otp-number notranslate" translate="no" style="font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace; font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #059669; padding-left: 10px; margin: 10px 0 8px 0; line-height: 1.1;">
                                            {{ $otp }}
                                        </div>
                                        <div class="otp-sub" style="font-size: 12px; font-weight: 500; color: #64748b; font-family: inherit;">
                                            Kode ini berlaku selama 5 menit
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- 4. Security Warning Notice -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0" style="margin-top: 18px;">
                                <tr>
                                    <td class="alert-bg" style="background-color: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 6px; padding: 12px 14px;">
                                        <p class="alert-msg" style="margin: 0; font-size: 12px; line-height: 1.5; color: #92400e; font-family: inherit;">
                                            <strong>Keamanan Akun:</strong> Jangan berikan kode ini kepada siapa pun. Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- 5. Minimalist Footer -->
                            <p class="footer-copy border-sep" style="margin: 28px 0 0 0; font-size: 11px; line-height: 1.6; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 18px; font-family: inherit;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'HRIS SaaS') }}. Seluruh hak cipta dilindungi.<br>
                                Email ini dikirim secara otomatis, mohon tidak membalas email ini.
                            </p>

                        </td>
                    </tr>

                </table>
                <!-- End Container Card -->

            </td>
        </tr>
    </table>
    <!-- End Outer Wrapper Table -->

</body>
</html>
