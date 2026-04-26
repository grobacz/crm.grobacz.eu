-- CRM Database Initialization
-- Note: The database is already created via POSTGRES_DB env variable

-- Enable UUID extension for better IDs
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Basic schema for CRM entities
CREATE TABLE IF NOT EXISTS customer (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(255),
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    is_vip BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS contact (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    customer_id UUID REFERENCES customer(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    title VARCHAR(100),
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS deal (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    customer_id UUID REFERENCES customer(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    value DECIMAL(10, 2),
    status VARCHAR(50),
    close_date DATE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS lead (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    company VARCHAR(255),
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS activities (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    entity_type VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    entity_id UUID,
    message TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS call_log (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    target_type VARCHAR(20) NOT NULL,
    target_id UUID NOT NULL,
    target_name VARCHAR(255) NOT NULL,
    target_phone VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    started_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    ended_at TIMESTAMP WITH TIME ZONE,
    duration_seconds INTEGER
);

CREATE TABLE IF NOT EXISTS email_campaign (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(160) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    target_type VARCHAR(20) NOT NULL,
    target_segment VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    started_at TIMESTAMP WITH TIME ZONE,
    completed_at TIMESTAMP WITH TIME ZONE
);

CREATE TABLE IF NOT EXISTS email_campaign_recipient (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    campaign_id UUID NOT NULL REFERENCES email_campaign(id) ON DELETE CASCADE,
    recipient_type VARCHAR(20) NOT NULL,
    recipient_id UUID NOT NULL,
    recipient_name VARCHAR(255) NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    sent_at TIMESTAMP WITH TIME ZONE,
    opened_at TIMESTAMP WITH TIME ZONE,
    completed_at TIMESTAMP WITH TIME ZONE
);

CREATE TABLE IF NOT EXISTS category (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    parent_id UUID REFERENCES category(id) ON DELETE SET NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(160) UNIQUE NOT NULL,
    description TEXT,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS inventory_item (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    category_id UUID REFERENCES category(id) ON DELETE SET NULL,
    sku VARCHAR(120) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    unit VARCHAR(50) NOT NULL DEFAULT 'unit',
    stock_quantity INTEGER NOT NULL DEFAULT 0,
    reserved_quantity INTEGER NOT NULL DEFAULT 0,
    reorder_level INTEGER NOT NULL DEFAULT 0,
    cost DECIMAL(10, 2),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS pricing_list (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(120) NOT NULL,
    code VARCHAR(80) UNIQUE NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    description TEXT,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS pricing_list_item (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    pricing_list_id UUID NOT NULL REFERENCES pricing_list(id) ON DELETE CASCADE,
    inventory_item_id UUID NOT NULL REFERENCES inventory_item(id) ON DELETE CASCADE,
    price DECIMAL(10, 2) NOT NULL,
    compare_at_price DECIMAL(10, 2),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    CONSTRAINT uniq_pricing_list_inventory_item UNIQUE (pricing_list_id, inventory_item_id)
);

CREATE TABLE IF NOT EXISTS app_setting (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    setting_key VARCHAR(120) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL DEFAULT '',
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS app_user (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL DEFAULT '',
    role VARCHAR(30) NOT NULL DEFAULT 'user',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    avatar_color VARCHAR(7),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS notification (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES app_user(id) ON DELETE CASCADE,
    activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    CONSTRAINT uniq_notification_user_activity UNIQUE (user_id, activity_id)
);

-- Basic indexes
CREATE INDEX IF NOT EXISTS idx_customers_email ON customer(email);
CREATE INDEX IF NOT EXISTS idx_customers_status ON customer(status);
CREATE INDEX IF NOT EXISTS idx_contacts_customer_id ON contact(customer_id);
CREATE INDEX IF NOT EXISTS idx_deals_customer_id ON deal(customer_id);
CREATE INDEX IF NOT EXISTS idx_lead_status ON lead(status);
CREATE INDEX IF NOT EXISTS idx_lead_email ON lead(email);
CREATE INDEX IF NOT EXISTS idx_activities_created_at ON activities(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_activities_entity ON activities(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_call_log_started_at ON call_log(started_at DESC);
CREATE INDEX IF NOT EXISTS idx_call_log_target ON call_log(target_type, target_id);
CREATE UNIQUE INDEX IF NOT EXISTS uniq_call_log_active_call ON call_log ((status)) WHERE ended_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_email_campaign_created_at ON email_campaign(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_email_campaign_status ON email_campaign(status);
CREATE INDEX IF NOT EXISTS idx_email_campaign_recipient_campaign_id ON email_campaign_recipient(campaign_id);
CREATE INDEX IF NOT EXISTS idx_email_campaign_recipient_status ON email_campaign_recipient(status);
CREATE INDEX IF NOT EXISTS idx_categories_parent_id ON category(parent_id);
CREATE INDEX IF NOT EXISTS idx_categories_slug ON category(slug);
CREATE INDEX IF NOT EXISTS idx_inventory_items_category_id ON inventory_item(category_id);
CREATE INDEX IF NOT EXISTS idx_inventory_items_sku ON inventory_item(sku);
CREATE INDEX IF NOT EXISTS idx_inventory_items_active ON inventory_item(is_active);
CREATE INDEX IF NOT EXISTS idx_pricing_lists_code ON pricing_list(code);
CREATE INDEX IF NOT EXISTS idx_pricing_lists_default ON pricing_list(is_default);
CREATE INDEX IF NOT EXISTS idx_pricing_list_items_list_id ON pricing_list_item(pricing_list_id);
CREATE INDEX IF NOT EXISTS idx_pricing_list_items_inventory_id ON pricing_list_item(inventory_item_id);

ALTER TABLE IF EXISTS customers ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'active';
ALTER TABLE IF EXISTS customer ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'active';
ALTER TABLE IF EXISTS customers ADD COLUMN IF NOT EXISTS is_vip BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE IF EXISTS customer ADD COLUMN IF NOT EXISTS is_vip BOOLEAN NOT NULL DEFAULT FALSE;

CREATE INDEX IF NOT EXISTS idx_app_setting_key ON app_setting(setting_key);
CREATE INDEX IF NOT EXISTS idx_app_user_email ON app_user(email);
CREATE INDEX IF NOT EXISTS idx_app_user_status ON app_user(status);
CREATE INDEX IF NOT EXISTS idx_notification_user_id ON notification(user_id);
CREATE INDEX IF NOT EXISTS idx_notification_user_unread ON notification(user_id, is_read);

-- Seed data: default settings
INSERT INTO app_setting (setting_key, setting_value, description) VALUES
    ('company_name', 'CustomerHub Pro', 'Company display name'),
    ('company_email', 'contact@customerhub.example', 'Company contact email'),
    ('default_currency', 'USD', 'Default currency code'),
    ('date_format', 'YYYY-MM-DD', 'Date display format'),
    ('timezone', 'UTC', 'Application timezone'),
    ('items_per_page', '25', 'Items per page in lists')
ON CONFLICT (setting_key) DO NOTHING;

-- Seed data: default users
INSERT INTO app_user (name, email, role, status, avatar_color) VALUES
    ('Anna Kowalska', 'anna.kowalska@customerhub.example', 'admin', 'active', '#2563eb'),
    ('Marek Zielinski', 'marek.zielinski@customerhub.example', 'manager', 'active', '#059669'),
    ('Sarah Johnson', 'sarah.johnson@customerhub.example', 'manager', 'active', '#7c3aed'),
    ('Daniel Nowak', 'daniel.nowak@customerhub.example', 'user', 'active', '#dc2626'),
    ('Olivia Brown', 'olivia.brown@customerhub.example', 'user', 'active', '#ea580c'),
    ('Kamil Wrobel', 'kamil.wrobel@customerhub.example', 'user', 'active', '#0891b2')
ON CONFLICT (email) DO NOTHING;
