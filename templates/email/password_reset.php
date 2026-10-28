<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light">
<title>Password Reset</title>
</head>
<!-- Password reset email of the Scodezy framework (redesigned 2026-10-29). The code reads this file as text and fills
     {{password_reset_url}} and {{password_reset_url_html}}; no PHP in this file. Table layout + inline styles so it
     renders the same in Gmail, Outlook and phone mail apps. -->
<body style="margin:0; padding:0; background-color:#eef1f6; -webkit-text-size-adjust:100%;">
    <!-- Preview line shown in the inbox list -->
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">Reset your password with the button inside (valid 24 hours).</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef1f6;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <tr>
                        <td align="center" style="padding:0 0 18px 0;"><img src="https://static.myappstv.com/img/appstv-email-logo-dark.png" alt="APPSTV" style="max-width:180px; height:auto; border:0;"></td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 4px 18px rgba(30,42,74,0.08);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="height:6px; line-height:6px; font-size:0; background-color:#1b84ff; background-image:linear-gradient(90deg,#1b84ff,#7239ea);">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding:34px 36px 8px 36px;">
                                        <div style="font-size:24px; line-height:1.3; font-weight:700; color:#1e2a4a; margin:0 0 14px 0;">Reset your password</div>
                                        <div style="font-size:15px; line-height:1.6; color:#4b5675;">
                                            We received a request to reset the password of your account.
                                            Click the button below to choose a new one.
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:28px 36px 8px 36px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" bgcolor="#1b84ff" style="border-radius:10px;">
                                                    <a href="{{password_reset_url}}" target="_blank" style="display:inline-block; padding:15px 34px; font-size:16px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:10px;">Reset password</a>
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="font-size:13px; color:#78829d; margin-top:14px;">This link works once and expires in 24 hours.</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:22px 36px 30px 36px;">
                                        <div style="border-top:1px solid #eef0f5; padding-top:18px; font-size:12px; line-height:1.6; color:#99a1b7;">
                                            Button not working? Copy this address into your browser:<br>
                                            <span style="color:#1b84ff; word-break:break-all;">{{password_reset_url_html}}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 24px 0 24px; font-size:12px; line-height:1.6; color:#99a1b7;">
                            Did not ask for this? You can ignore this email - your password stays the same.<br>
                            Never forward this email: whoever opens the link can change the password.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
