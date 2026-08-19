<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use App\Models\SmtpSetting;

class VendorOutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailBody;
    public ?SmtpSetting $smtpSetting = null;

    public function __construct(string $subject, string $body, ?SmtpSetting $smtp = null)
    {
        $this->subject = $subject;
        $this->emailBody = $body;
        $this->smtpSetting = $smtp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    public function headers(): Headers
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return new Headers(
            messageId: 'wholesale-outreach-' . uniqid('', true) . '@' . $host,
            references: [config('mail.from.address')],
            text: [
                'X-Mailer' => 'Wholesale Outreach Platform',
                'X-Auto-Response-Suppress' => 'All',
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $body = str_replace(["\r\n", "\\r\\n", "\r", "\n"], "\n", $this->emailBody);
        $body = nl2br(e($body));
        $body = preg_replace('/(<br\s*\/?>\s*){3,}/i', '<br><br>', $body);

        $optOutText = '';
        if ($this->smtpSetting && $this->smtpSetting->reply_to) {
            $optOutText = '<p style="font-size:11px;color:#999;margin-top:30px;padding-top:15px;border-top:1px solid #eee;">If you prefer not to receive future wholesale inquiries from us, please let us know by replying to this email.</p>';
        }

        return "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
</head>
<body style=\"font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#333;max-width:600px;margin:0 auto;padding:20px;\">
    <div>{$body}</div>
    {$optOutText}
</body>
</html>";
    }

    private function buildText(): string
    {
        $text = $this->emailBody;

        if ($this->smtpSetting && $this->smtpSetting->reply_to) {
            $text .= "\n\n---\nIf you prefer not to receive future wholesale inquiries from us, please let us know by replying to this email.";
        }

        return $text;
    }
}
