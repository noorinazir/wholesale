<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_replies')) {
            $keepReplyIds = DB::table('email_replies')
                ->whereNotNull('message_id')
                ->selectRaw('MIN(id) as keep_id')
                ->groupBy('message_id')
                ->pluck('keep_id')
                ->toArray();

            if (!empty($keepReplyIds)) {
                DB::table('email_replies')
                    ->whereNotNull('message_id')
                    ->whereNotIn('id', $keepReplyIds)
                    ->delete();
            }

            Schema::table('email_replies', function (Blueprint $table) {
                $table->unique('message_id', 'email_replies_message_id_unique');
            });
        }

        if (Schema::hasTable('suppression_list')) {
            $keepSuppressionIds = DB::table('suppression_list')
                ->whereNotNull('email')
                ->selectRaw('MIN(id) as keep_id')
                ->groupBy('email')
                ->pluck('keep_id')
                ->toArray();

            if (!empty($keepSuppressionIds)) {
                DB::table('suppression_list')
                    ->whereNotIn('id', $keepSuppressionIds)
                    ->delete();
            }

            Schema::table('suppression_list', function (Blueprint $table) {
                $table->unique('email', 'suppression_list_email_unique');
            });
        }

        if (Schema::hasTable('smtp_settings') && !Schema::hasColumn('smtp_settings', 'last_imap_uid')) {
            Schema::table('smtp_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('last_imap_uid')->nullable()->after('last_inbox_check_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('email_replies')) {
            Schema::table('email_replies', function (Blueprint $table) {
                $table->dropUnique('email_replies_message_id_unique');
            });
        }

        if (Schema::hasTable('suppression_list')) {
            Schema::table('suppression_list', function (Blueprint $table) {
                $table->dropUnique('suppression_list_email_unique');
            });
        }

        if (Schema::hasTable('smtp_settings') && Schema::hasColumn('smtp_settings', 'last_imap_uid')) {
            Schema::table('smtp_settings', function (Blueprint $table) {
                $table->dropColumn('last_imap_uid');
            });
        }
    }
};
