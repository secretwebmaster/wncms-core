BEGIN TRANSACTION;
CREATE TABLE "advertisements" ("id" integer primary key autoincrement not null, "website_id" integer not null, "status" varchar not null, "expired_at" datetime, "name" varchar, "type" varchar not null, "cta_text" varchar, "url" varchar, "cta_text_2" varchar, "url_2" varchar, "remark" varchar, "text_color" varchar, "background_color" varchar, "code" text, "style" text, "position" varchar, "contact" varchar, "sort" integer, "created_at" datetime, "updated_at" datetime, foreign key("website_id") references "websites"("id") on delete cascade);

CREATE TABLE "channels" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "contact" varchar, "remark" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "clicks" ("id" integer primary key autoincrement not null, "clickable_type" varchar not null, "clickable_id" integer not null, "channel_id" integer, "name" varchar, "value" varchar, "ip" varchar, "referer" varchar, "parameters" text, "created_at" datetime, "updated_at" datetime, foreign key("channel_id") references "channels"("id") on delete cascade);

CREATE TABLE "comments" ("id" integer primary key autoincrement not null, "commentable_type" varchar not null, "commentable_id" integer not null, "status" varchar not null default 'visible', "user_id" integer, "parent_id" integer, "content" varchar not null, "created_at" datetime, "updated_at" datetime, "deleted_at" datetime, foreign key("user_id") references "users"("id") on delete cascade, foreign key("parent_id") references "comments"("id") on delete cascade);

CREATE TABLE "domain_aliases" ("id" integer primary key autoincrement not null, "website_id" integer, "domain" varchar not null, "remark" varchar, "created_at" datetime, "updated_at" datetime, foreign key("website_id") references "websites"("id") on delete cascade);

CREATE TABLE "failed_jobs" ("id" integer primary key autoincrement not null, "uuid" varchar not null, "connection" text not null, "queue" text not null, "payload" text not null, "exception" text not null, "failed_at" datetime not null default CURRENT_TIMESTAMP);

CREATE TABLE "job_batches" ("id" varchar not null, "name" varchar not null, "total_jobs" integer not null, "pending_jobs" integer not null, "failed_jobs" integer not null, "failed_job_ids" text not null, "options" text, "cancelled_at" integer, "created_at" integer not null, "finished_at" integer, primary key ("id"));

CREATE TABLE "jobs" ("id" integer primary key autoincrement not null, "queue" varchar not null, "payload" text not null, "attempts" integer not null, "reserved_at" integer, "available_at" integer not null, "created_at" integer not null);

CREATE TABLE "links" ("id" integer primary key autoincrement not null, "status" varchar not null default 'active', "slug" varchar not null, "name" varchar not null, "url" varchar not null, "description" text, "external_thumbnail" varchar, "clicks" integer default '0', "remark" varchar, "sort" integer, "color" varchar, "is_pinned" tinyint(1) default '0', "expired_at" datetime, "tracking_code" varchar, "slogan" varchar, "background" varchar, "contact" varchar, "is_recommended" tinyint(1) default '0', "hit_at" datetime, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "media" ("id" integer primary key autoincrement not null, "model_type" varchar not null, "model_id" integer not null, "uuid" varchar, "collection_name" varchar not null, "name" varchar not null, "file_name" varchar not null, "mime_type" varchar, "disk" varchar not null, "conversions_disk" varchar, "size" integer not null, "manipulations" text not null, "custom_properties" text not null, "generated_conversions" text not null, "responsive_images" text not null, "order_column" integer, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "menu_items" ("id" integer primary key autoincrement not null, "menu_id" integer not null, "parent_id" integer, "model_type" varchar, "model_id" varchar, "icon" varchar, "type" varchar, "name" varchar, "url" varchar, "is_new_window" tinyint(1) not null default '0', "is_mega_menu" tinyint(1) not null default '0', "sort" integer, "description" varchar, "created_at" datetime, "updated_at" datetime, foreign key("menu_id") references "menus"("id") on delete cascade, foreign key("parent_id") references "menu_items"("id") on delete cascade);

CREATE TABLE "menus" ("id" integer primary key autoincrement not null, "name" varchar not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);

CREATE TABLE "model_has_permissions" ("permission_id" integer not null, "model_type" varchar not null, "model_id" integer not null, foreign key("permission_id") references "permissions"("id") on delete cascade, primary key ("permission_id", "model_id", "model_type"));

CREATE TABLE "model_has_roles" ("role_id" integer not null, "model_type" varchar not null, "model_id" integer not null, foreign key("role_id") references "roles"("id") on delete cascade, primary key ("role_id", "model_id", "model_type"));

CREATE TABLE "model_has_websites" ("website_id" integer not null, "model_type" varchar not null, "model_id" integer not null, foreign key("website_id") references "websites"("id") on delete cascade, primary key ("website_id", "model_id", "model_type"));

CREATE TABLE "packages" ("id" integer primary key autoincrement not null, "package_id" varchar not null, "name" varchar not null, "description" varchar, "url" varchar, "author" varchar, "version" varchar not null default '1.0.0', "status" varchar not null default 'inactive', "path" varchar not null, "remark" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "page_builder_contents" ("id" integer primary key autoincrement not null, "page_id" integer not null, "builder_type" varchar not null default 'default', "version" integer not null default '1', "payload" text, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "page_templates" ("id" integer primary key autoincrement not null, "page_id" integer not null, "theme_id" varchar not null, "template_id" varchar not null, "value" text not null, "sort" integer, "created_at" datetime, "updated_at" datetime, foreign key("page_id") references "pages"("id") on delete cascade);

CREATE TABLE "pages" ("id" integer primary key autoincrement not null, "user_id" integer, "status" varchar not null default 'published', "visibility" varchar not null default 'public', "type" varchar not null default 'plain', "blade_name" varchar, "title" varchar not null, "slug" varchar not null, "content" text, "remark" varchar, "is_locked" tinyint(1) not null default '0', "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete cascade);

CREATE TABLE "parameters" ("id" integer primary key autoincrement not null, "name" varchar not null, "key" varchar not null, "remark" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "password_reset_tokens" ("email" varchar not null, "token" varchar not null, "created_at" datetime, primary key ("email"));

CREATE TABLE "permissions" ("id" integer primary key autoincrement not null, "name" varchar not null, "guard_name" varchar not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "plugins" ("id" integer primary key autoincrement not null, "plugin_id" varchar not null, "name" varchar not null, "description" varchar, "url" varchar, "author" varchar, "version" varchar not null default '1.0.0', "status" varchar not null default 'inactive', "path" varchar not null, "remark" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "posts" ("id" integer primary key autoincrement not null, "user_id" integer, "status" varchar not null default 'published', "visibility" varchar not null default 'public', "external_thumbnail" varchar, "slug" varchar not null, "title" varchar not null, "label" varchar, "excerpt" varchar, "content" text, "remark" varchar, "sort" integer, "password" varchar, "price" numeric, "is_pinned" tinyint(1) not null default '0', "is_recommended" tinyint(1) not null default '0', "is_dmca" tinyint(1) not null default '0', "published_at" datetime not null, "expired_at" datetime, "source" varchar, "ref_id" varchar, "deleted_at" datetime, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete cascade);

CREATE TABLE "records" ("id" integer primary key autoincrement not null, "type" varchar not null, "sub_type" varchar, "status" varchar, "message" text not null, "detail" text, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "role_has_permissions" ("permission_id" integer not null, "role_id" integer not null, foreign key("permission_id") references "permissions"("id") on delete cascade, foreign key("role_id") references "roles"("id") on delete cascade, primary key ("permission_id", "role_id"));

CREATE TABLE "roles" ("id" integer primary key autoincrement not null, "name" varchar not null, "guard_name" varchar not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "search_keywords" ("id" integer primary key autoincrement not null, "keyword" varchar not null, "locale" varchar, "count" varchar not null default '0', "created_at" datetime, "updated_at" datetime);

CREATE TABLE "sessions" ("id" varchar not null, "user_id" integer, "ip_address" varchar, "user_agent" text, "payload" text not null, "last_activity" integer not null, primary key ("id"));

CREATE TABLE "settings" ("id" integer primary key autoincrement not null, "key" varchar not null, "value" text, "type" varchar, "group" varchar not null default '', "created_at" datetime, "updated_at" datetime);

CREATE TABLE sqlite_sequence(name,seq);

CREATE TABLE "tag_keywords" ("id" integer primary key autoincrement not null, "tag_id" integer not null, "model_key" varchar, "name" varchar not null, "binding_field" varchar, "created_at" datetime, "updated_at" datetime, foreign key("tag_id") references "tags"("id") on delete cascade);

CREATE TABLE "taggables" ("tag_id" integer not null, "taggable_type" varchar not null, "taggable_id" integer not null, foreign key("tag_id") references "tags"("id") on delete cascade);

CREATE TABLE "tags" ("id" integer primary key autoincrement not null, "parent_id" integer, "name" varchar not null, "slug" varchar not null, "type" varchar, "group" varchar, "description" varchar, "icon" varchar, "sort" integer, "created_at" datetime, "updated_at" datetime, foreign key("parent_id") references "tags"("id") on delete set null);

CREATE TABLE "theme_options" ("id" integer primary key autoincrement not null, "website_id" integer not null, "theme" varchar not null, "key" varchar not null, "value" text, "created_at" datetime, "updated_at" datetime, foreign key("website_id") references "websites"("id") on delete cascade);

CREATE TABLE "traffic_summaries" ("id" integer primary key autoincrement not null, "website_id" integer, "model_type" varchar not null, "model_id" varchar not null, "period" varchar, "count" integer, "is_recorded" tinyint(1) default '0', "created_at" datetime, "updated_at" datetime, foreign key("website_id") references "websites"("id") on delete set null);

CREATE TABLE "traffics" ("id" integer primary key autoincrement not null, "website_id" integer, "model_type" varchar not null, "model_id" varchar not null, "url" varchar, "ip" varchar, "geo" varchar, "referrer" varchar, "created_at" datetime, "updated_at" datetime, foreign key("website_id") references "websites"("id") on delete set null);

CREATE TABLE "translations" ("id" integer primary key autoincrement not null, "translatable_type" varchar not null, "translatable_id" integer not null, "field" varchar not null, "locale" varchar not null, "value" text not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "user_website" ("id" integer primary key autoincrement not null, "user_id" integer not null, "website_id" integer not null, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete cascade, foreign key("website_id") references "websites"("id") on delete cascade);

CREATE TABLE "users" ("id" integer primary key autoincrement not null, "first_name" varchar, "last_name" varchar, "nickname" varchar, "username" varchar not null, "email" varchar, "email_verified_at" datetime, "last_login_at" datetime, "password" varchar not null, "api_token" varchar, "remember_token" varchar, "created_at" datetime, "updated_at" datetime, "referrer_id" integer, foreign key("referrer_id") references "users"("id") on delete set null);

CREATE TABLE "websites" ("id" integer primary key autoincrement not null, "user_id" integer, "domain" varchar not null, "site_name" varchar not null, "site_logo" varchar, "site_favicon" varchar, "site_slogan" varchar, "site_seo_keywords" varchar, "site_seo_description" varchar, "theme" varchar, "homepage" varchar, "remark" varchar, "meta_verification" text, "head_code" text, "body_code" text, "analytics" text, "license" varchar, "enabled_page_cache" tinyint(1) not null default '0', "enabled_data_cache" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete set null);

CREATE UNIQUE INDEX "channels_slug_unique" on "channels" ("slug");

CREATE INDEX "clicks_clickable_type_clickable_id_index" on "clicks" ("clickable_type", "clickable_id");

CREATE INDEX "comments_commentable_type_commentable_id_index" on "comments" ("commentable_type", "commentable_id");

CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs" ("uuid");

CREATE INDEX "jobs_queue_index" on "jobs" ("queue");

CREATE INDEX "links_tracking_code_index" on "links" ("tracking_code");

CREATE INDEX "media_model_type_model_id_index" on "media" ("model_type", "model_id");

CREATE UNIQUE INDEX "media_uuid_unique" on "media" ("uuid");

CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions" ("model_id", "model_type");

CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles" ("model_id", "model_type");

CREATE INDEX "model_has_websites_model_type_model_id_index" on "model_has_websites" ("model_type", "model_id");

CREATE UNIQUE INDEX "packages_package_id_unique" on "packages" ("package_id");

CREATE INDEX "page_builder_contents_page_id_index" on "page_builder_contents" ("page_id");

CREATE UNIQUE INDEX "pages_slug_unique" on "pages" ("slug");

CREATE INDEX "pages_title_index" on "pages" ("title");

CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions" ("name", "guard_name");

CREATE UNIQUE INDEX "plugins_plugin_id_unique" on "plugins" ("plugin_id");

CREATE UNIQUE INDEX "posts_slug_unique" on "posts" ("slug");

CREATE UNIQUE INDEX "roles_name_guard_name_unique" on "roles" ("name", "guard_name");

CREATE INDEX "sessions_last_activity_index" on "sessions" ("last_activity");

CREATE INDEX "sessions_user_id_index" on "sessions" ("user_id");

CREATE UNIQUE INDEX "settings_group_key_unique" on "settings" ("group", "key");

CREATE UNIQUE INDEX "taggables_tag_id_taggable_id_taggable_type_unique" on "taggables" ("tag_id", "taggable_id", "taggable_type");

CREATE INDEX "taggables_taggable_type_taggable_id_index" on "taggables" ("taggable_type", "taggable_id");

CREATE INDEX "tags_sort_index" on "tags" ("sort");

CREATE INDEX "tags_type_name_index" on "tags" ("type", "name");

CREATE UNIQUE INDEX "tags_type_slug_unique" on "tags" ("type", "slug");

CREATE UNIQUE INDEX "theme_options_website_id_theme_key_unique" on "theme_options" ("website_id", "theme", "key");

CREATE INDEX "translations_translatable_type_translatable_id_index" on "translations" ("translatable_type", "translatable_id");

CREATE UNIQUE INDEX "users_api_token_unique" on "users" ("api_token");

CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");

CREATE UNIQUE INDEX "websites_domain_unique" on "websites" ("domain");

COMMIT;
