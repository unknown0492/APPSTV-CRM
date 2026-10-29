<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light">
<title>{{heading}}</title>
</head>
<!-- Account email of the Scodezy framework (1.0.61): email change link / code, email changed, password changed.
     Filled by profileSendMail() (plugins/user_management/webservice/scodezy_my_profile.php); no PHP in this file.
     Placeholders: {{site_name}} {{heading}} {{intro}} {{action}} (button or code, may be empty) {{note}}.
     Table layout + inline styles so it renders the same in Gmail, Outlook and phone mail apps. -->
<body style="margin:0; padding:0; background-color:#eef1f6; -webkit-text-size-adjust:100%;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">{{heading}}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef1f6;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <tr>
                        <td align="center" style="padding:0 0 18px 0; font-size:20px; font-weight:700; color:#1e2a4a; letter-spacing:0.3px;">{{site_name}}</td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 4px 18px rgba(30,42,74,0.08);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="height:6px; line-height:6px; font-size:0; background-color:#1b84ff; background-image:linear-gradient(90deg,#1b84ff,#7239ea);">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding:34px 36px 8px 36px;">
                                        <div style="font-size:24px; line-height:1.3; font-weight:700; color:#1e2a4a; margin:0 0 14px 0;">{{heading}}</div>
                                        <div style="font-size:15px; line-height:1.6; color:#4b5675;">{{intro}}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:24px 36px 8px 36px;">{{action}}</td>
                                </tr>
                                <tr>
                                    <td style="padding:18px 36px 30px 36px;">
                                        <div style="border-top:1px solid #eef0f5; padding-top:16px; font-size:12px; line-height:1.6; color:#99a1b7;">{{note}}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 24px 0 24px; font-size:12px; line-height:1.6; color:#99a1b7;">
                            This email was sent automatically by {{site_name}}. Please do not reply.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
