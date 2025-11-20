<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116162149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE api_usage_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE cart_items_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE carts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE discounts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE notification_preferences_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE orders_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE password_reset_tokens_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE payments_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE product_features_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE products_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE subscription_plans_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE subscription_usage_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_profiles_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_subscriptions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE api_usage (id INT NOT NULL, user_subscription_id INT NOT NULL, endpoint VARCHAR(255) NOT NULL, exchange VARCHAR(100) DEFAULT NULL, calls_count INT NOT NULL, response_time INT DEFAULT NULL, response_size INT DEFAULT NULL, http_status VARCHAR(10) NOT NULL, metadata JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F47E0AA088C4EB53 ON api_usage (user_subscription_id)');
        $this->addSql('CREATE INDEX IDX_api_usage_user_date ON api_usage (user_subscription_id, created_at)');
        $this->addSql('CREATE INDEX IDX_api_usage_endpoint ON api_usage (endpoint)');
        $this->addSql('CREATE INDEX IDX_api_usage_date ON api_usage (created_at)');
        $this->addSql('COMMENT ON COLUMN api_usage.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE cart_items (id INT NOT NULL, cart_id INT NOT NULL, product_id INT NOT NULL, subscription_plan_id INT DEFAULT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, options JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BEF484451AD5CDBF ON cart_items (cart_id)');
        $this->addSql('CREATE INDEX IDX_BEF484454584665A ON cart_items (product_id)');
        $this->addSql('CREATE INDEX IDX_BEF484459B8CE200 ON cart_items (subscription_plan_id)');
        $this->addSql('CREATE UNIQUE INDEX cart_product_plan_unique ON cart_items (cart_id, product_id, subscription_plan_id)');
        $this->addSql('COMMENT ON COLUMN cart_items.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN cart_items.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE carts (id INT NOT NULL, user_id INT NOT NULL, discount_id INT DEFAULT NULL, total_amount NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, discount_amount NUMERIC(10, 2) DEFAULT NULL, session_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4E004AACA76ED395 ON carts (user_id)');
        $this->addSql('CREATE INDEX IDX_4E004AAC4C7C611F ON carts (discount_id)');
        $this->addSql('CREATE INDEX IDX_4E004AACA76ED3957B00651C ON carts (user_id, status)');
        $this->addSql('CREATE INDEX IDX_4E004AAC613FECDF ON carts (session_id)');
        $this->addSql('COMMENT ON COLUMN carts.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN carts.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE discounts (id INT NOT NULL, code VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, value NUMERIC(10, 2) NOT NULL, minimum_amount NUMERIC(10, 2) DEFAULT NULL, maximum_discount NUMERIC(10, 2) DEFAULT NULL, usage_limit INT DEFAULT NULL, usage_count INT NOT NULL, valid_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, valid_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FC5702B877153098 ON discounts (code)');
        $this->addSql('CREATE INDEX IDX_FC5702B877153098 ON discounts (code)');
        $this->addSql('CREATE INDEX IDX_FC5702B81B5771DD ON discounts (is_active)');
        $this->addSql('CREATE INDEX IDX_FC5702B87BD7DFB6 ON discounts (valid_from)');
        $this->addSql('CREATE INDEX IDX_FC5702B8FDEF8F78 ON discounts (valid_until)');
        $this->addSql('COMMENT ON COLUMN discounts.valid_from IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN discounts.valid_until IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN discounts.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN discounts.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE notification_preferences (id INT NOT NULL, user_id INT NOT NULL, email_notifications BOOLEAN NOT NULL, email_security_alerts BOOLEAN NOT NULL, push_notifications BOOLEAN NOT NULL, newsletter BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3CAA95B4A76ED395 ON notification_preferences (user_id)');
        $this->addSql('COMMENT ON COLUMN notification_preferences.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN notification_preferences.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE orders (id INT NOT NULL, cart_id INT NOT NULL, user_id INT NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, status VARCHAR(50) NOT NULL, payment_status VARCHAR(20) NOT NULL, payment_method VARCHAR(50) DEFAULT NULL, stripe_payment_intent_id VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_order_cart ON orders (cart_id)');
        $this->addSql('CREATE INDEX IDX_order_user ON orders (user_id)');
        $this->addSql('CREATE INDEX IDX_order_status ON orders (status)');
        $this->addSql('CREATE INDEX IDX_order_payment_status ON orders (payment_status)');
        $this->addSql('COMMENT ON COLUMN orders.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN orders.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE password_reset_tokens (id INT NOT NULL, user_id INT NOT NULL, token VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, used BOOLEAN NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3967A2165F37A13B ON password_reset_tokens (token)');
        $this->addSql('CREATE INDEX IDX_3967A216A76ED395 ON password_reset_tokens (user_id)');
        $this->addSql('COMMENT ON COLUMN password_reset_tokens.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN password_reset_tokens.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN password_reset_tokens.used_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE payments (id INT NOT NULL, order_id INT NOT NULL, payment_status VARCHAR(50) NOT NULL, amount NUMERIC(10, 2) NOT NULL, payment_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, transaction_id VARCHAR(255) DEFAULT NULL, gateway VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_payment_order ON payments (order_id)');
        $this->addSql('CREATE INDEX IDX_payment_status ON payments (payment_status)');
        $this->addSql('COMMENT ON COLUMN payments.payment_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE product_features (id INT NOT NULL, product_id INT NOT NULL, feature_key VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, feature_type VARCHAR(20) NOT NULL, configuration JSON DEFAULT NULL, is_active BOOLEAN NOT NULL, sort_order INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_product_feature_product ON product_features (product_id)');
        $this->addSql('CREATE INDEX IDX_product_feature_active ON product_features (is_active)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_product_feature_key ON product_features (product_id, feature_key)');
        $this->addSql('COMMENT ON COLUMN product_features.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE products (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, category VARCHAR(50) NOT NULL, product_type VARCHAR(20) NOT NULL, metadata JSON NOT NULL, is_available BOOLEAN NOT NULL, is_popular BOOLEAN NOT NULL, stock INT DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_product_category ON products (category)');
        $this->addSql('CREATE INDEX IDX_product_available ON products (is_available)');
        $this->addSql('CREATE INDEX IDX_product_popular ON products (is_popular)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_product_slug ON products (slug)');
        $this->addSql('COMMENT ON COLUMN products.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE subscription_plans (id INT NOT NULL, product_id INT NOT NULL, name VARCHAR(100) NOT NULL, price NUMERIC(10, 2) NOT NULL, billing_cycle VARCHAR(20) NOT NULL, tier_level INT NOT NULL, api_calls_limit INT DEFAULT NULL, rate_limit_per_minute INT DEFAULT NULL, allowed_exchanges JSON NOT NULL, allowed_endpoints JSON NOT NULL, features JSON NOT NULL, restrictions JSON NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_subscription_product ON subscription_plans (product_id)');
        $this->addSql('CREATE INDEX IDX_subscription_tier ON subscription_plans (tier_level)');
        $this->addSql('COMMENT ON COLUMN subscription_plans.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE subscription_usage (id INT NOT NULL, user_subscription_id INT NOT NULL, usage_date DATE NOT NULL, period_type VARCHAR(20) NOT NULL, period_start TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, period_end TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, total_api_calls INT NOT NULL, successful_calls INT NOT NULL, failed_calls INT NOT NULL, rate_limited_calls INT NOT NULL, endpoint_usage JSON NOT NULL, exchange_usage JSON NOT NULL, average_response_time INT DEFAULT NULL, total_data_transferred BIGINT NOT NULL, error_rate NUMERIC(5, 2) DEFAULT NULL, peak_usage_hour JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_subscription_usage_subscription ON subscription_usage (user_subscription_id)');
        $this->addSql('CREATE INDEX IDX_subscription_usage_period ON subscription_usage (period_start, period_end)');
        $this->addSql('CREATE INDEX IDX_subscription_usage_date ON subscription_usage (usage_date)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_subscription_usage_period ON subscription_usage (user_subscription_id, usage_date, period_type)');
        $this->addSql('COMMENT ON COLUMN subscription_usage.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_profiles (id INT NOT NULL, user_id INT NOT NULL, phone VARCHAR(20) DEFAULT NULL, bio TEXT DEFAULT NULL, company VARCHAR(100) DEFAULT NULL, position VARCHAR(100) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, avatar_url VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6BBD6130A76ED395 ON user_profiles (user_id)');
        $this->addSql('COMMENT ON COLUMN user_profiles.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_profiles.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_subscriptions (id INT NOT NULL, user_id INT NOT NULL, subscription_plan_id INT NOT NULL, status VARCHAR(20) NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, stripe_subscription_id VARCHAR(255) DEFAULT NULL, current_limits JSON NOT NULL, custom_features JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EAF927519B8CE200 ON user_subscriptions (subscription_plan_id)');
        $this->addSql('CREATE INDEX IDX_user_subscription_status ON user_subscriptions (status)');
        $this->addSql('CREATE INDEX IDX_user_subscription_user ON user_subscriptions (user_id)');
        $this->addSql('CREATE INDEX IDX_user_subscription_expires ON user_subscriptions (expires_at)');
        $this->addSql('COMMENT ON COLUMN user_subscriptions.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_subscriptions.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_subscriptions.cancelled_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_subscriptions.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(50) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, is_active BOOLEAN NOT NULL, is_email_verified BOOLEAN NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, email_confirmation_token VARCHAR(255) DEFAULT NULL, email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
        $this->addSql('CREATE INDEX IDX_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E9F85E0677 ON users (username)');
        $this->addSql('CREATE INDEX IDX_1483A5E91B5771DD ON users (is_active)');
        $this->addSql('CREATE INDEX IDX_1483A5E98B8E8428 ON users (created_at)');
        $this->addSql('COMMENT ON COLUMN users.email_verified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.last_login_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE api_usage ADD CONSTRAINT FK_F47E0AA088C4EB53 FOREIGN KEY (user_subscription_id) REFERENCES user_subscriptions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF484451AD5CDBF FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF484454584665A FOREIGN KEY (product_id) REFERENCES products (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF484459B8CE200 FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plans (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE carts ADD CONSTRAINT FK_4E004AACA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE carts ADD CONSTRAINT FK_4E004AAC4C7C611F FOREIGN KEY (discount_id) REFERENCES discounts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification_preferences ADD CONSTRAINT FK_3CAA95B4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE1AD5CDBF FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B328D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product_features ADD CONSTRAINT FK_7470B6844584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE subscription_plans ADD CONSTRAINT FK_CF5F99A24584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE subscription_usage ADD CONSTRAINT FK_2AD35F0388C4EB53 FOREIGN KEY (user_subscription_id) REFERENCES user_subscriptions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_profiles ADD CONSTRAINT FK_6BBD6130A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_subscriptions ADD CONSTRAINT FK_EAF92751A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_subscriptions ADD CONSTRAINT FK_EAF927519B8CE200 FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plans (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE api_usage_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE cart_items_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE carts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE discounts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE notification_preferences_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE orders_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE password_reset_tokens_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE payments_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE product_features_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE products_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE subscription_plans_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE subscription_usage_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE user_profiles_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE user_subscriptions_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE api_usage DROP CONSTRAINT FK_F47E0AA088C4EB53');
        $this->addSql('ALTER TABLE cart_items DROP CONSTRAINT FK_BEF484451AD5CDBF');
        $this->addSql('ALTER TABLE cart_items DROP CONSTRAINT FK_BEF484454584665A');
        $this->addSql('ALTER TABLE cart_items DROP CONSTRAINT FK_BEF484459B8CE200');
        $this->addSql('ALTER TABLE carts DROP CONSTRAINT FK_4E004AACA76ED395');
        $this->addSql('ALTER TABLE carts DROP CONSTRAINT FK_4E004AAC4C7C611F');
        $this->addSql('ALTER TABLE notification_preferences DROP CONSTRAINT FK_3CAA95B4A76ED395');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEE1AD5CDBF');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEEA76ED395');
        $this->addSql('ALTER TABLE password_reset_tokens DROP CONSTRAINT FK_3967A216A76ED395');
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT FK_65D29B328D9F6D38');
        $this->addSql('ALTER TABLE product_features DROP CONSTRAINT FK_7470B6844584665A');
        $this->addSql('ALTER TABLE subscription_plans DROP CONSTRAINT FK_CF5F99A24584665A');
        $this->addSql('ALTER TABLE subscription_usage DROP CONSTRAINT FK_2AD35F0388C4EB53');
        $this->addSql('ALTER TABLE user_profiles DROP CONSTRAINT FK_6BBD6130A76ED395');
        $this->addSql('ALTER TABLE user_subscriptions DROP CONSTRAINT FK_EAF92751A76ED395');
        $this->addSql('ALTER TABLE user_subscriptions DROP CONSTRAINT FK_EAF927519B8CE200');
        $this->addSql('DROP TABLE api_usage');
        $this->addSql('DROP TABLE cart_items');
        $this->addSql('DROP TABLE carts');
        $this->addSql('DROP TABLE discounts');
        $this->addSql('DROP TABLE notification_preferences');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE product_features');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE subscription_plans');
        $this->addSql('DROP TABLE subscription_usage');
        $this->addSql('DROP TABLE user_profiles');
        $this->addSql('DROP TABLE user_subscriptions');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
