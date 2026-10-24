<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Reset Password</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#1d4ed8;padding:20px 24px;text-align:center;">
                            <div style="color:#ffffff;font-size:18px;font-weight:bold;">SCP School</div>
                            <div style="color:#dbeafe;font-size:13px;margin-top:4px;">SMK Negeri 1 Bantul</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 24px;">
                            <div style="font-size:16px;font-weight:bold;color:#111827;margin-bottom:8px;">
                                Kode OTP Reset Password
                            </div>
                            <div style="font-size:14px;color:#4b5563;line-height:1.6;margin-bottom:20px;">
                                Gunakan kode di bawah untuk mereset password akun Anda.
                                Kode ini hanya berlaku selama 10 menit.
                            </div>

                            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:16px;text-align:center;margin-bottom:20px;">
                                <div style="font-size:12px;color:#1d4ed8;letter-spacing:1px;margin-bottom:6px;">KODE OTP</div>
                                <div style="font-size:28px;font-weight:bold;color:#1e3a8a;letter-spacing:6px;">
                                    {{ $otp }}
                                </div>
                            </div>

                            <div style="font-size:13px;color:#6b7280;line-height:1.6;">
                                Jangan bagikan kode ini kepada siapapun.
                                Jika Anda tidak meminta reset password, abaikan email ini.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 24px;background:#f9fafb;border-top:1px solid #e5e7eb;text-align:center;font-size:12px;color:#9ca3af;">
                            SCP School · SMK Negeri 1 Bantul
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>