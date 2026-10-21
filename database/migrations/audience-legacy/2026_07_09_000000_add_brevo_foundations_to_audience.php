<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audience_brevo_settings')) {
            Schema::create('audience_brevo_settings', function (Blueprint $table): void {
                $table->id();
                $table->boolean('is_enabled')->default(false);
                $table->text('api_key_encrypted')->nullable();
                $table->string('sender_name')->nullable();
                $table->string('sender_email')->nullable();
                $table->string('reply_to_email')->nullable();
                $table->unsignedBigInteger('default_folder_id')->nullable();
                $table->string('webhook_secret', 64)->nullable()->unique();
                $table->timestamp('last_connection_test_at')->nullable();
                $table->string('last_connection_test_status')->nullable();
                $table->text('last_connection_test_message')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('audience_segments')) {
            Schema::table('audience_segments', function (Blueprint $table): void {
                if (! Schema::hasColumn('audience_segments', 'brevo_list_id')) {
                    $table->unsignedBigInteger('brevo_list_id')->nullable()->index();
                }

                if (! Schema::hasColumn('audience_segments', 'brevo_synced_at')) {
                    $table->timestamp('brevo_synced_at')->nullable();
                }

                if (! Schema::hasColumn('audience_segments', 'brevo_sync_status')) {
                    $table->string('brevo_sync_status')->nullable()->index();
                }

                if (! Schema::hasColumn('audience_segments', 'brevo_sync_error')) {
                    $table->text('brevo_sync_error')->nullable();
                }
            });
        }

        if (Schema::hasTable('audience_contacts')) {
            Schema::table('audience_contacts', function (Blueprint $table): void {
                if (! Schema::hasColumn('audience_contacts', 'brevo_synced_at')) {
                    $table->timestamp('brevo_synced_at')->nullable();
                }

                if (! Schema::hasColumn('audience_contacts', 'brevo_sync_status')) {
                    $table->string('brevo_sync_status')->nullable()->index();
                }

                if (! Schema::hasColumn('audience_contacts', 'brevo_sync_error')) {
                    $table->text('brevo_sync_error')->nullable();
                }

                if (! Schema::hasColumn('audience_contacts', 'email_blacklisted_at')) {
                    $table->timestamp('email_blacklisted_at')->nullable();
                }

                if (! Schema::hasColumn('audience_contacts', 'hard_bounced_at')) {
                    $table->timestamp('hard_bounced_at')->nullable();
                }

                if (! Schema::hasColumn('audience_contacts', 'last_bounce_reason')) {
                    $table->text('last_bounce_reason')->nullable();
                }
            });
        }

        if (Schema::hasTable('segment_messages')) {
            Schema::table('segment_messages', function (Blueprint $table): void {
                if (! Schema::hasColumn('segment_messages', 'provider')) {
                    $table->string('provider')->default('smtp_lws')->index();
                }

                if (! Schema::hasColumn('segment_messages', 'brevo_campaign_id')) {
                    $table->unsignedBigInteger('brevo_campaign_id')->nullable()->index();
                }

                if (! Schema::hasColumn('segment_messages', 'brevo_status')) {
                    $table->string('brevo_status')->nullable()->index();
                }

                if (! Schema::hasColumn('segment_messages', 'brevo_created_at')) {
                    $table->timestamp('brevo_created_at')->nullable();
                }

                if (! Schema::hasColumn('segment_messages', 'brevo_sent_at')) {
                    $table->timestamp('brevo_sent_at')->nullable();
                }

                if (! Schema::hasColumn('segment_messages', 'brevo_last_sync_at')) {
                    $table->timestamp('brevo_last_sync_at')->nullable();
                }

                if (! Schema::hasColumn('segment_messages', 'brevo_error')) {
                    $table->text('brevo_error')->nullable();
                }

                if (! Schema::hasColumn('segment_messages', 'content_snapshot_html')) {
                    $table->longText('content_snapshot_html')->nullable();
                }

                if (! Schema::hasColumn('segment_messages', 'subject_snapshot')) {
                    $table->string('subject_snapshot')->nullable();
                }

                if (! Schema::hasColumn('segment_messages', 'sender_snapshot')) {
                    $table->json('sender_snapshot')->nullable();
                }
            });
        }

        if (! Schema::hasTable('audience_brevo_events')) {
            Schema::create('audience_brevo_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('segment_message_id')->nullable()->constrained('segment_messages')->nullOnDelete();
                $table->foreignId('segment_message_delivery_id')->nullable()->constrained('segment_message_deliveries')->nullOnDelete();
                $table->foreignId('audience_contact_id')->nullable()->constrained('audience_contacts')->nullOnDelete();
                $table->unsignedBigInteger('brevo_campaign_id')->nullable()->index();
                $table->string('email')->nullable()->index();
                $table->string('event_type')->index();
                $table->timestamp('event_date')->nullable();
                $table->json('raw_payload');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('segment_message_deliveries')) {
            Schema::table('segment_message_deliveries', function (Blueprint $table): void {
                if (! Schema::hasColumn('segment_message_deliveries', 'provider_status')) {
                    $table->string('provider_status')->nullable()->index();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'latest_event')) {
                    $table->string('latest_event')->nullable()->index();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'latest_event_at')) {
                    $table->timestamp('latest_event_at')->nullable();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'delivered_at')) {
                    $table->timestamp('delivered_at')->nullable();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'opened_at')) {
                    $table->timestamp('opened_at')->nullable();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'clicked_at')) {
                    $table->timestamp('clicked_at')->nullable();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'soft_bounced_at')) {
                    $table->timestamp('soft_bounced_at')->nullable();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'hard_bounced_at')) {
                    $table->timestamp('hard_bounced_at')->nullable();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'unsubscribed_at')) {
                    $table->timestamp('unsubscribed_at')->nullable();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'complained_at')) {
                    $table->timestamp('complained_at')->nullable();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'bounce_reason')) {
                    $table->text('bounce_reason')->nullable();
                }

                if (! Schema::hasColumn('segment_message_deliveries', 'brevo_raw_event_id')) {
                    $table->string('brevo_raw_event_id')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('segment_message_deliveries')) {
            Schema::table('segment_message_deliveries', function (Blueprint $table): void {
                $table->dropColumn([
                    'provider_status',
                    'latest_event',
                    'latest_event_at',
                    'delivered_at',
                    'opened_at',
                    'clicked_at',
                    'soft_bounced_at',
                    'hard_bounced_at',
                    'unsubscribed_at',
                    'complained_at',
                    'bounce_reason',
                    'brevo_raw_event_id',
                ]);
            });
        }

        Schema::dropIfExists('audience_brevo_events');

        if (Schema::hasTable('segment_messages')) {
            Schema::table('segment_messages', function (Blueprint $table): void {
                $table->dropColumn([
                    'provider',
                    'brevo_campaign_id',
                    'brevo_status',
                    'brevo_created_at',
                    'brevo_sent_at',
                    'brevo_last_sync_at',
                    'brevo_error',
                    'content_snapshot_html',
                    'subject_snapshot',
                    'sender_snapshot',
                ]);
            });
        }

        if (Schema::hasTable('audience_contacts')) {
            Schema::table('audience_contacts', function (Blueprint $table): void {
                $table->dropColumn([
                    'brevo_synced_at',
                    'brevo_sync_status',
                    'brevo_sync_error',
                    'email_blacklisted_at',
                    'hard_bounced_at',
                    'last_bounce_reason',
                ]);
            });
        }

        if (Schema::hasTable('audience_segments')) {
            Schema::table('audience_segments', function (Blueprint $table): void {
                $table->dropColumn([
                    'brevo_list_id',
                    'brevo_synced_at',
                    'brevo_sync_status',
                    'brevo_sync_error',
                ]);
            });
        }

        Schema::dropIfExists('audience_brevo_settings');
    }
};
