<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmtpSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'host', 'port', 'encryption', 'username', 'password',
        'from_name', 'from_email', 'reply_to', 'is_active',
        'test_mode', 'test_mode_recipient', 'last_tested_at',
        'last_test_success',
        'imap_host', 'imap_port', 'imap_encryption',
        'imap_username', 'imap_password',
        'inbox_checking_enabled', 'last_inbox_check_at', 'last_imap_uid',
    ];

    protected $hidden = ['password', 'imap_password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'test_mode' => 'boolean',
            'last_tested_at' => 'datetime',
            'last_test_success' => 'boolean',
            'port' => 'integer',
            'imap_port' => 'integer',
            'inbox_checking_enabled' => 'boolean',
            'last_inbox_check_at' => 'datetime',
            'last_imap_uid' => 'integer',
        ];
    }

    public function getDecryptedPassword(): ?string
    {
        if (!$this->password) {
            return null;
        }
        try {
            return decrypt($this->password);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getDecryptedImapPassword(): ?string
    {
        if (!$this->imap_password) {
            return null;
        }
        try {
            return decrypt($this->imap_password);
        } catch (\Exception $e) {
            return null;
        }
    }
}
