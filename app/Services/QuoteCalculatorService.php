<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteItem;

  class QuoteCalculatorService
  {
      /**
       * Calculate quote item totals using fiscal printer method
       *
       * @param float $quantity
       * @param float $unitPrice
       * @param float $discountPercentage
       * @param float $taxPercentage
       * @param bool $isExempt
       * @return array
       */
      public function calculateQuoteItemTotals(
          float $quantity,
          float $unitPrice,
          float $discountPercentage,
          float $taxPercentage,
          bool $isExempt = false
      ): array {
          // PASO 1: Calcular subtotal del item
          $subtotal = $quantity * $unitPrice;

          // PASO 2: Calcular descuento
          $discountAmount = $subtotal * ($discountPercentage / 100);

          // PASO 3: Calcular subtotal después del descuento
          $subtotalAfterDiscount = $subtotal - $discountAmount;

          // PASO 4: Calcular IVA (0 si es exento)
          $taxAmount = 0;
          if (!$isExempt) {
              $taxAmount = $subtotalAfterDiscount * ($taxPercentage / 100);
          }

          // PASO 5: Calcular total del item
          $total = $subtotalAfterDiscount + $taxAmount;

          return [
              'subtotal' => round($subtotal, 2),
              'subtotal_after_discount' => round($subtotalAfterDiscount, 2),
              'discount_amount' => round($discountAmount, 2),
              'tax_amount' => round($taxAmount, 2),
              'total' => round($total, 2),
          ];
      }

      /**
       * Calculate quote totals from items array
       *
       * @param array $items
       * @param float $globalDiscountPercentage
       * @return array
       */
      public function calculateQuoteTotals(array $items, float $globalDiscountPercentage = 0): array
      {
          $subtotal = 0;
          $taxableBase = 0;
          $exemptBase = 0;
          $totalTaxAmount = 0;

          foreach ($items as $item) {
              $product = \App\Models\Product::find($item['product_id']);

              // Obtener sale_tax y aliquot
              $saleTax = $item['sale_tax'] ?? $product->sale_tax ?? 'GRA';
              $aliquot = $item['aliquot'] ?? $product->aliquot ?? 16;

              // PASO 1: Calcular subtotal del item
              $itemSubtotal = $item['quantity'] * $item['unit_price'];

              // PASO 2: Calcular descuento del item
              $discountPercentage = $item['discount_percentage'] ?? $item['discount'] ?? 0;
              $itemDiscount = $itemSubtotal * ($discountPercentage / 100);

              // PASO 3: Calcular subtotal después del descuento
              $itemSubtotalAfterDiscount = $itemSubtotal - $itemDiscount;

              // PASO 4: Determinar si es exento
              $isExempt = $saleTax === "EX" || ($aliquot == 0 && $saleTax !== "06");

              // PASO 5: Calcular IVA
              $itemTaxAmount = 0;
              if (!$isExempt) {
                  $itemTaxAmount = $itemSubtotalAfterDiscount * ($aliquot / 100);
              }

              // PASO 6: Sumar a los totales
              $subtotal += $itemSubtotalAfterDiscount;

              if ($isExempt) {
                  $exemptBase += $itemSubtotalAfterDiscount;
              } else {
                  $taxableBase += $itemSubtotalAfterDiscount;
              }

              $totalTaxAmount += $itemTaxAmount;
          }

          // PASO 7: Aplicar descuento global
          $globalDiscountAmount = $subtotal * ($globalDiscountPercentage / 100);

          // PASO 8: Calcular totales finales
          $finalSubtotal = $subtotal - $globalDiscountAmount;
          $total = $finalSubtotal + $totalTaxAmount;

          return [
              'subtotal_before_discount' => round($subtotal, 2),
              'taxable_base' => round($taxableBase, 2),
              'exempt_base' => round($exemptBase, 2),
              'global_discount_percentage' => $globalDiscountPercentage,
              'global_discount_amount' => round($globalDiscountAmount, 2),
              'subtotal' => round($finalSubtotal, 2),
              'tax_amount' => round($totalTaxAmount, 2),
              'total' => round($total, 2),
          ];
      }

      /**
       * Update quote totals from its items
       *
       * @param int $quoteId
       * @return void
       */
      public function updateQuoteTotals(int $quoteId): void
      {
          $quote = Quote::lockForUpdate()->find($quoteId);
          if (!$quote) {
              return;
          }

          $items = QuoteItem::where('quote_id', $quoteId)->get();

          // Calcular totales usando el método correcto
          $totals = $this->calculateQuoteTotals(
              $items->toArray(),
              $quote->discount
          );

          $quote->update([
              'subtotal' => $totals['subtotal'],
              'discount_amount' => $totals['global_discount_amount'],
              'tax_amount' => $totals['tax_amount'],
              'total' => $totals['total'],
          ]);
      }

      /**
       * Validate quote item data
       *
       * @param array $data
       * @return void
       * @throws \InvalidArgumentException
       */
      public function validateQuoteItem(array $data): void
      {
          if ($data['quantity'] <= 0) {
              throw new \InvalidArgumentException('La cantidad debe ser mayor a 0');
          }
          if ($data['unit_price'] < 0) {
              throw new \InvalidArgumentException('El precio unitario no puede ser negativo');
          }

          $discount = $data['discount_percentage'] ?? $data['discount'] ?? 0;
          if ($discount < 0 || $discount > 100) {
              throw new \InvalidArgumentException('El porcentaje de descuento debe estar entre 0 y 100');
          }

          $tax = $data['tax_percentage'] ?? $data['aliquot'] ?? 0;
          if ($tax < 0 || $tax > 100) {
              throw new \InvalidArgumentException('El porcentaje de impuesto debe estar entre 0 y 100');
          }
      }

      /**
       * Determine if product is tax exempt
       *
       * @param string $saleTax
       * @param float $aliquot
       * @return bool
       */
      public function isExempt(string $saleTax, float $aliquot): bool
      {
          return $saleTax === "EX" || ($aliquot == 0 && $saleTax !== "06");
      }
  }
