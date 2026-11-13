<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light">
<title>{{heading}}</title>
</head>
<!-- Service case e-mail of APPSTV CRM (1.0.28): a new case for a staff member, and the daily 9:00 list of their cases.
     Filled by appstv_case_mail_page() (plugins/appstv_warranty/webservice/service_case_mail_functions.php); no PHP here.
     Placeholders: {{site_name}} {{heading}} {{intro}} {{body}} (HTML) {{action}} (button, may be empty) {{note}}.
     Table layout + inline styles so it renders the same in Gmail, Outlook and phone mail apps. -->
<body style="margin:0; padding:0; background-color:#eef1f6; -webkit-text-size-adjust:100%;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">{{intro}}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef1f6;">
        <tr>
            <td align="center" style="padding:28px 10px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <tr>
                        <td align="center" style="padding:0 0 16px 0; font-size:20px; font-weight:700; color:#1e2a4a; letter-spacing:0.3px;">{{site_name}}</td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 4px 18px rgba(30,42,74,0.08);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="height:6px; line-height:6px; font-size:0; background-color:#0b5cad;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding:28px 28px 6px 28px;">
                                        <div style="font-size:22px; line-height:1.3; font-weight:700; color:#1e2a4a; margin:0 0 10px 0;">{{heading}}</div>
                                        <div style="font-size:15px; line-height:1.6; color:#3f4a66;">{{intro}}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 28px 6px 28px;">{{body}}</td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:18px 28px 6px 28px;">{{action}}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 28px 26px 28px;">
                                        <div style="border-top:1px solid #eef0f5; padding-top:14px; font-size:12px; line-height:1.6; color:#6b7590;">{{note}}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:18px 24px 0 24px; font-size:12px; line-height:1.6; color:#6b7590;">
                            This email was sent automatically by {{site_name}}. Please do not reply.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
