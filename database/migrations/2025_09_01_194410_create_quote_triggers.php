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
                DECLARE v_tax DECIMAL(15,2) DEFAULT 0.00;
                DECLARE v_total DECIMAL(15,2) DEFAULT 0.00;
                DECLARE v_quote_discount DECIMAL(15,2) DEFAULT 0.00;
                DECLARE v_final_total DECIMAL(15,2) DEFAULT 0.00;
                
                -- Obtener descuento actual del quote
                SELECT discount INTO v_quote_discount 
                FROM quotes 
                WHERE id = p_quote_id;
                
                -- Calcular totales desde los items
                SELECT 
                    COALESCE(SUM(subtotal), 0.00),
                    COALESCE(SUM(tax_amount), 0.00),
                    COALESCE(SUM(total), 0.00)
                INTO v_subtotal, v_tax, v_total
                FROM quote_items 
                WHERE quote_id = p_quote_id;
                
                -- Aplicar descuento general del quote al subtotal
                SET v_final_total = v_total - (v_subtotal * (v_quote_discount / v_subtotal)) + v_tax;
                
                -- Actualizar totales en la tabla quotes
                UPDATE quotes 
                SET 
                    subtotal = v_subtotal,
                    tax = v_tax,
                    total = v_final_total,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = p_quote_id;
            END
        ');

        // TRIGGERS PARA QUOTES
        
        // Trigger BEFORE INSERT para quotes
        DB::unprepared('
            DROP TRIGGER IF EXISTS quotes_before_insert;
        ');
        
        DB::unprepared('
            CREATE TRIGGER quotes_before_insert
            BEFORE INSERT ON quotes
            FOR EACH ROW
            BEGIN
                -- Validaciones
                IF NEW.discount < 0 OR NEW.discount > NEW.subtotal THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "El descuento no puede ser negativo ni mayor al subtotal";
                END IF;
                
                IF NEW.valid_until <= CURDATE() THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "La fecha de validez debe ser posterior a hoy";
                END IF;
                
                -- Generar número de quote si no existe
                IF NEW.quote_number IS NULL OR NEW.quote_number = "" THEN
                    SET NEW.quote_number = CONCAT("COT-", YEAR(CURDATE()), "-", LPAD(MONTH(CURDATE()), 2, "0"), "-", LPAD(DAY(CURDATE()), 2, "0"), "-", LPAD((
                        SELECT COALESCE(MAX(SUBSTRING(quote_number, -6)), 0) + 1
                        FROM quotes 
                        WHERE quote_number LIKE CONCAT("COT-", YEAR(CURDATE()), "-", LPAD(MONTH(CURDATE()), 2, "0"), "-", LPAD(DAY(CURDATE()), 2, "0"), "-%")
                    ), 6, "0"));
                END IF;
                
                -- Establecer fecha de cotización si no existe
                IF NEW.quote_date IS NULL THEN
                    SET NEW.quote_date = CURRENT_TIMESTAMP;
                END IF;
                
                -- Asegurar timestamps
                IF NEW.created_at IS NULL THEN
                    SET NEW.created_at = CURRENT_TIMESTAMP;
                END IF;
                SET NEW.updated_at = CURRENT_TIMESTAMP;
            END
        ');

        // Trigger BEFORE UPDATE para quotes
        DB::unprepared('
            DROP TRIGGER IF EXISTS quotes_before_update;
        ');
        
        DB::unprepared('
            CREATE TRIGGER quotes_before_update
            BEFORE UPDATE ON quotes
            FOR EACH ROW
            BEGIN
                -- Validaciones
                IF NEW.discount < 0 OR NEW.discount > NEW.subtotal THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "El descuento no puede ser negativo ni mayor al subtotal";
                END IF;
                
                -- No permitir cambiar fecha de validez a una fecha pasada si el estado no es draft
                IF NEW.status != "draft" AND NEW.valid_until <= CURDATE() THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "No se puede establecer una fecha de validez pasada para cotizaciones enviadas";
                END IF;
                
                -- Marcar automáticamente como expirado si la fecha de validez ya pasó
                IF NEW.valid_until < CURDATE() AND NEW.status NOT IN ("expired", "approved", "rejected") THEN
                    SET NEW.status = "expired";
                END IF;
                
                -- Establecer fechas de estado automáticamente
                IF NEW.status = "sent" AND OLD.status != "sent" AND NEW.sent_at IS NULL THEN
                    SET NEW.sent_at = CURRENT_TIMESTAMP;
                END IF;
                
                IF NEW.status = "approved" AND OLD.status != "approved" AND NEW.approved_at IS NULL THEN
                    SET NEW.approved_at = CURRENT_TIMESTAMP;
                END IF;
                
                -- Si cambió el descuento, recalcular totales
                IF NEW.discount != OLD.discount THEN
                    -- El procedimiento update_quote_totals se encargará del cálculo
                    SET NEW.total = NEW.subtotal + NEW.tax - NEW.discount;
                END IF;
                
                -- Actualizar timestamp
                SET NEW.updated_at = CURRENT_TIMESTAMP;
            END
        ');

        // Trigger AFTER UPDATE para quotes (recalcular totales si cambió el descuento)
        DB::unprepared('
            DROP TRIGGER IF EXISTS quotes_after_update;
        ');
        
        DB::unprepared('
            CREATE TRIGGER quotes_after_update
            AFTER UPDATE ON quotes
            FOR EACH ROW
            BEGIN
                -- Si cambió el descuento general, recalcular totales
                IF NEW.discount != OLD.discount THEN
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
