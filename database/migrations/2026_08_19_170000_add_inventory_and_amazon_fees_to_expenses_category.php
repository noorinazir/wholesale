<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: recreate table without CHECK constraint to allow new category values
            DB::statement('CREATE TABLE expenses_new AS SELECT * FROM expenses');
            DB::statement('DROP TABLE expenses');
            DB::statement('
                CREATE TABLE expenses (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    vendor_id INTEGER REFERENCES vendors(id) ON DELETE SET NULL,
                    product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
                    purchase_order_id INTEGER REFERENCES purchase_orders(id) ON DELETE SET NULL,
                    expense_number VARCHAR NOT NULL UNIQUE,
                    category VARCHAR NOT NULL DEFAULT "other",
                    description VARCHAR NOT NULL,
                    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                    currency VARCHAR(3) NOT NULL DEFAULT "USD",
                    expense_date DATE NOT NULL,
                    status VARCHAR NOT NULL DEFAULT "pending",
                    payment_method VARCHAR,
                    vendor_name VARCHAR,
                    receipt_url VARCHAR,
                    is_recurring BOOLEAN NOT NULL DEFAULT 0,
                    recurring_frequency VARCHAR,
                    notes TEXT,
                    metadata TEXT,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP
                )
            ');
            DB::statement('INSERT INTO expenses SELECT * FROM expenses_new');
            DB::statement('DROP TABLE expenses_new');
            DB::statement('CREATE INDEX expenses_vendor_id_index ON expenses(vendor_id)');
            DB::statement('CREATE INDEX expenses_product_id_index ON expenses(product_id)');
            DB::statement('CREATE INDEX expenses_category_index ON expenses(category)');
            DB::statement('CREATE INDEX expenses_expense_date_index ON expenses(expense_date)');
            DB::statement('CREATE INDEX expenses_status_index ON expenses(status)');
        } else {
            DB::statement("ALTER TABLE expenses MODIFY COLUMN category ENUM(
                'shipping', 'labeling', 'inventory', 'amazon_fees', 'fba_fees',
                'amazon_referral', 'advertising', 'storage', 'returns', 'supplies',
                'software', 'fees', 'other'
            ) DEFAULT 'other'");
        }
    }

    public function down(): void
    {
        // No safe way to restore enum constraint; leave as VARCHAR
    }
};
