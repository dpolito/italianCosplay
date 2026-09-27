<?php

namespace App\Support;

final class AuditLogActionType
{
	public const LOGIN_FAILED = 'login_failed';
	public const LOGIN_SUCCESS = 'login_success';
	public const LOGOUT = 'logout';

	public const PASSWORD_RESET_REQUESTED = 'password_reset_requested';
	public const PASSWORD_RESET_COMPLETED = 'password_reset_completed';

	public const PROFILE_UPDATED = 'profile_updated';
	public const PROFILE_SETTINGS_UPDATED = 'profile_settings_updated';
	public const AVATAR_UPDATED = 'avatar_updated';
	public const COVER_UPDATED = 'cover_updated';

	public const EVENT_CREATED = 'event_created';
	public const EVENT_UPDATED = 'event_updated';
	public const EVENT_APPROVED = 'event_approved';
	public const EVENT_COPIED = 'event_copied';
	public const EVENT_DELETED = 'event_deleted';
	public const EVENT_AGENDA_UPDATED = 'event_agenda_updated';
	public const EVENT_AGENDA_REMOVED = 'event_agenda_removed';

	public const EVENT_MASTER_CREATED = 'event_master_created';
	public const EVENT_MASTER_UPDATED = 'event_master_updated';
	public const EVENT_MASTER_DELETED = 'event_master_deleted';
	public const EVENT_MASTER_CLAIM_REQUESTED = 'event_master_claim_requested';
	public const EVENT_MASTER_CLAIM_APPROVED = 'event_master_claim_approved';
	public const EVENT_MASTER_CLAIM_REJECTED = 'event_master_claim_rejected';
	public const ORGANIZATION_STATUS_UPDATED = 'organization_status_updated';
	public const ORGANIZATION_CREATED = 'organization_created';
	public const ORGANIZATION_UPDATED = 'organization_updated';
	public const ORGANIZATION_MEMBER_UPDATED = 'organization_member_updated';
	public const ORGANIZATION_MASTER_UPDATED = 'organization_master_updated';
	public const ORGANIZATION_INVITATION_CREATED = 'organization_invitation_created';
	public const ORGANIZATION_INVITATION_RESENT = 'organization_invitation_resent';
	public const ORGANIZATION_INVITATION_REVOKED = 'organization_invitation_revoked';
	public const ORGANIZATION_INVITATION_ACCEPTED = 'organization_invitation_accepted';
	public const ORGANIZATION_INVITATION_DECLINED = 'organization_invitation_declined';
	public const ORGANIZATION_EMAILS_SENT = 'organization_emails_sent';
	public const ORGANIZATION_EMAIL_TEST_SENT = 'organization_email_test_sent';
	public const LEGACY_INVITATION_EMAILS_IMPORTED = 'legacy_invitation_emails_imported';
	public const LEGACY_INVITATION_EMAILS_SENT = 'legacy_invitation_emails_sent';
	public const LEGACY_INVITATION_EMAIL_TEST_SENT = 'legacy_invitation_email_test_sent';
	public const USER_INVITATION_CREATED = 'user_invitation_created';
	public const USER_INVITATION_ACCEPTED = 'user_invitation_accepted';
	public const USER_INVITATION_BLOCKED = 'user_invitation_blocked';
	public const BREVO_WEBHOOK_FAILED = 'brevo_webhook_failed';

	public const BLOG_POST_CREATED = 'blog_post_created';
	public const BLOG_POST_UPDATED = 'blog_post_updated';
	public const BLOG_POST_DELETED = 'blog_post_deleted';

	public const GUEST_CREATED = 'guest_created';
	public const GUEST_REMOVED_FROM_EVENT = 'guest_removed_from_event';
	public const GUEST_UPDATED = 'guest_updated';
	public const GUEST_DELETED = 'guest_deleted';

	public const FAVORITE_ADDED = 'favorite_added';
	public const FAVORITE_REMOVED = 'favorite_removed';

	public const AD_PAYMENT_CREATED = 'ad_payment_created';
	public const AD_PAYMENT_SUCCESS = 'ad_payment_success';
	public const AD_PAYMENT_FAILED = 'ad_payment_failed';
	public const AD_PAYMENT_REFUNDED = 'ad_payment_refunded';
	public const AD_PAYMENT_WEBHOOK_RECEIVED = 'ad_payment_webhook_received';
	public const AD_PAYMENT_WEBHOOK_FAILED = 'ad_payment_webhook_failed';

	public const COSPLAY_PORTFOLIO_CREATED = 'cosplay_portfolio_created';
	public const COSPLAY_PORTFOLIO_UPDATED = 'cosplay_portfolio_updated';
	public const COSPLAY_PORTFOLIO_DELETED = 'cosplay_portfolio_deleted';
	public const COSPLAY_PORTFOLIO_VISIBILITY_UPDATED = 'cosplay_portfolio_visibility_updated';
	public const COSPLAY_EVENT_SELECTION_UPDATED = 'cosplay_event_selection_updated';
	public const COSPLAY_EVENT_SELECTION_REMOVED = 'cosplay_event_selection_removed';

	public const USER_CREATED = 'user_created';
	public const USER_UPDATED = 'user_updated';
	public const USER_DELETED = 'user_deleted';
	public const USER_DEACTIVATED = 'user_deactivated';

	public const SITE_SETUP_UPDATED = 'site_setup_updated';
	public const TELEGRAM_CHANNEL_MESSAGE_SENT = 'telegram_channel_message_sent';
	public const TELEGRAM_CHANNEL_MESSAGE_SCHEDULED = 'telegram_channel_message_scheduled';
	public const TELEGRAM_CHANNEL_MESSAGE_CANCELLED = 'telegram_channel_message_cancelled';

	public const FAQ_CATEGORY_CREATED = 'faq_category_created';
	public const FAQ_CATEGORY_UPDATED = 'faq_category_updated';
	public const FAQ_CATEGORY_DELETED = 'faq_category_deleted';
	public const FAQ_ITEM_CREATED = 'faq_item_created';
	public const FAQ_ITEM_UPDATED = 'faq_item_updated';
	public const FAQ_ITEM_DELETED = 'faq_item_deleted';
}
