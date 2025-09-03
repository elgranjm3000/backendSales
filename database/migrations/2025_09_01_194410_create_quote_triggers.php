<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
             $table->decimal('tax_amount', 15, 2)->default(0)->after('tax');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount');
        });
          DB::unprepared('
            DROP FUNCTION IF EXISTS calculate_quote_item_totals;
        ');
        
        DB::unprepared('
            CREATE FUNCTION calculate_quote_item_totals(
                p_quantity DECIMAL(15,3),
                p_unit_price DECIMAL(15,2),
                p_discount_percentage DECIMAL(5,2),
                p_tax_percentage DECIMAL(5,2)
            )
            RETURNS JSON
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE v_subtotal DECIMAL(15,2);
                DECLARE v_discount_amount DECIMAL(15,2);
                DECLARE v_tax_amount DECIMAL(15,2);
                DECLARE v_total DECIMAL(15,2);
                
                -- Calcular subtotal
                SET v_subtotal = p_quantity * p_unit_price;
                
                -- Calcular descuento
                SET v_discount_amount = v_subtotal * (p_discount_percentage / 100);
                
                -- Calcular base gravable después del descuento
                SET v_tax_amount = (v_subtotal - v_discount_amount) * (p_tax_percentage / 100);
                
                -- Calcular total
                SET v_total = v_subtotal - v_discount_amount + v_tax_amount;
                
                RETURN JSON_OBJECT(
                    "subtotal", v_subtotal,
                    "discount_amount", v_discount_amount,
                    "tax_amount", v_tax_amount,
                    "total", v_total
                );
            END
        ');

        // TRIGGERS PARA QUOTE_ITEMS
        
        // Trigger BEFORE INSERT para quote_items
        DB::unprepared('
            DROP TRIGGER IF EXISTS quote_items_before_insert;
        ');
        
        DB::unprepared('
            CREATE TRIGGER quote_items_before_insert
            BEFORE INSERT ON quote_items
            FOR EACH ROW
            BEGIN
                DECLARE v_calculations JSON;
                
                -- Validaciones
                IF NEW.quantity <= 0 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "La cantidad debe ser mayor a 0";
                END IF;
                
                IF NEW.unit_price < 0 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "El precio unitario no puede ser negativo";
                END IF;
                
                IF NEW.discount_percentage < 0 OR NEW.discount_percentage > 100 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "El porcentaje de descuento debe estar entre 0 y 100";
                END IF;
                
                IF NEW.tax_percentage < 0 OR NEW.tax_percentage > 100 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "El porcentaje de impuesto debe estar entre 0 y 100";
                END IF;
                
                -- Calcular totales automáticamente
                SET v_calculations = calculate_quote_item_totals(
                    NEW.quantity,
                    NEW.unit_price,
                    NEW.discount_percentage,
                    NEW.tax_percentage
                );
                
                SET NEW.subtotal = JSON_EXTRACT(v_calculations, "$.subtotal");
                SET NEW.discount_amount = JSON_EXTRACT(v_calculations, "$.discount_amount");
                SET NEW.tax_amount = JSON_EXTRACT(v_calculations, "$.tax_amount");
                SET NEW.total = JSON_EXTRACT(v_calculations, "$.total");
                
                -- Asegurar timestamps
                IF NEW.created_at IS NULL THEN
                    SET NEW.created_at = CURRENT_TIMESTAMP;
                END IF;
                SET NEW.updated_at = CURRENT_TIMESTAMP;
            END
        ');

        // Trigger BEFORE UPDATE para quote_items
        DB::unprepared('
            DROP TRIGGER IF EXISTS quote_items_before_update;
        ');
        
        DB::unprepared('
            CREATE TRIGGER quote_items_before_update
            BEFORE UPDATE ON quote_items
            FOR EACH ROW
            BEGIN
                DECLARE v_calculations JSON;
                
                -- Validaciones
                IF NEW.quantity <= 0 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "La cantidad debe ser mayor a 0";
                END IF;
                
                IF NEW.unit_price < 0 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "El precio unitario no puede ser negativo";
                END IF;
                
                IF NEW.discount_percentage < 0 OR NEW.discount_percentage > 100 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "El porcentaje de descuento debe estar entre 0 y 100";
                END IF;
                
                IF NEW.tax_percentage < 0 OR NEW.tax_percentage > 100 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "El porcentaje de impuesto debe estar entre 0 y 100";
                END IF;
                
                -- Solo recalcular si cambiaron los valores que afectan el total
                IF NEW.quantity != OLD.quantity 
                   OR NEW.unit_price != OLD.unit_price 
                   OR NEW.discount_percentage != OLD.discount_percentage 
                   OR NEW.tax_percentage != OLD.tax_percentage THEN
                
                    SET v_calculations = calculate_quote_item_totals(
                        NEW.quantity,
                        NEW.unit_price,
                        NEW.discount_percentage,
                        NEW.tax_percentage
                    );
                    
                    SET NEW.subtotal = JSON_EXTRACT(v_calculations, "$.subtotal");
                    SET NEW.discount_amount = JSON_EXTRACT(v_calculations, "$.discount_amount");
                    SET NEW.tax_amount = JSON_EXTRACT(v_calculations, "$.tax_amount");
                    SET NEW.total = JSON_EXTRACT(v_calculations, "$.total");
                END IF;
                
                -- Actualizar timestamp
                SET NEW.updated_at = CURRENT_TIMESTAMP;
            END
        ');

        // Trigger AFTER INSERT para quote_items (actualizar totales del quote)
        DB::unprepared('
            DROP TRIGGER IF EXISTS quote_items_after_insert;
        ');
        
        DB::unprepared('
            CREATE TRIGGER quote_items_after_insert
            AFTER INSERT ON quote_items
            FOR EACH ROW
            BEGIN
                CALL update_quote_totals(NEW.quote_id);
            END
        ');

        // Trigger AFTER UPDATE para quote_items (actualizar totales del quote)
        DB::unprepared('
            DROP TRIGGER IF EXISTS quote_items_after_update;
        ');
        
        DB::unprepared('
            CREATE TRIGGER quote_items_after_update
            AFTER UPDATE ON quote_items
            FOR EACH ROW
            BEGIN
                -- Solo actualizar si cambió algo que afecte los totales
                IF NEW.subtotal != OLD.subtotal 
                   OR NEW.discount_amount != OLD.discount_amount 
                   OR NEW.tax_amount != OLD.tax_amount 
                   OR NEW.total != OLD.total THEN
                    CALL update_quote_totals(NEW.quote_id);
                END IF;
            END
        ');

        // Trigger AFTER DELETE para quote_items (actualizar totales del quote)
        DB::unprepared('
            DROP TRIGGER IF EXISTS quote_items_after_delete;
        ');
        
        DB::unprepared('
            CREATE TRIGGER quote_items_after_delete
            AFTER DELETE ON quote_items
            FOR EACH ROW
            BEGIN
                CALL update_quote_totals(OLD.quote_id);
            END
        ');

        // Crear procedimiento para actualizar totales de quotes
        DB::unprepared('
            DROP PROCEDURE IF EXISTS update_quote_totals;
        ');
        DB::unprepared('
            CREATE PROCEDURE update_quote_totals(IN p_quote_id BIGINT)
            BEGIN
                DECLARE v_subtotal DECIMAL(15,2) DEFAULT 0.00;
                DECLARE v_tax_percentage DECIMAL(5,2) DEFAULT 0.00;
                DECLARE v_discount_percentage DECIMAL(5,2) DEFAULT 0.00;
                DECLARE v_discount_amount DECIMAL(15,2) DEFAULT 0.00;
                DECLARE v_tax_amount DECIMAL(15,2) DEFAULT 0.00;
                DECLARE v_total DECIMAL(15,2) DEFAULT 0.00;

                -- Obtener subtotal desde los items
                SELECT COALESCE(SUM(total), 0.00)
                INTO v_subtotal
                FROM quote_items
                WHERE quote_id = p_quote_id;

                -- Obtener porcentaje de impuesto y descuento
                SELECT tax, discount INTO v_tax_percentage, v_discount_percentage
                FROM quotes WHERE id = p_quote_id;

                -- Calcular montos
                SET v_discount_amount = v_subtotal * (v_discount_percentage / 100);
                SET v_tax_amount = (v_subtotal - v_discount_amount) * (v_tax_percentage / 100);

                -- Calcular total
                SET v_total = v_subtotal - v_discount_amount + v_tax_amount;

                -- Actualizar la tabla quotes
                UPDATE quotes
                SET
                    subtotal = v_subtotal,
                    discount_amount = v_discount_amount,
                    tax_amount = v_tax_amount,
                    total = v_total,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = p_quote_id;
            END
        ');

        DB::unprepared('
            DROP TRIGGER IF EXISTS quotes_after_update;
        ');
        DB::unprepared('
            CREATE TRIGGER quotes_after_update
            AFTER UPDATE ON quotes
            FOR EACH ROW
            BEGIN
                IF NEW.discount != OLD.discount OR NEW.tax != OLD.tax THEN
                    CALL update_quote_totals(NEW.id);
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
        $table->dropColumn(['tax_amount', 'discount_amount']);
    });
          // Eliminar triggers de quote_items
        DB::unprepared('DROP TRIGGER IF EXISTS quote_items_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS quote_items_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS quote_items_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS quote_items_after_update');
        DB::unprepared('DROP TRIGGER IF EXISTS quote_items_after_delete');
        
        // Eliminar triggers de quotes
        DB::unprepared('DROP TRIGGER IF EXISTS quotes_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS quotes_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS quotes_after_update');
        
        // Eliminar procedimientos y funciones
        DB::unprepared('DROP PROCEDURE IF EXISTS update_quote_totals');
    }
};
