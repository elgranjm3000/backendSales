<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Quote::with(['customer', 'company', 'items.product']);
        
        // Filtrar según el rol del usuario autenticado
        switch ($user->role) {
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                // Admin y Manager pueden ver todas las cotizaciones
                break;
            case \App\Enums\UserRole::COMPANY:
                // Company solo puede ver cotizaciones de sus compañías
                $companyIds = $user->companies->pluck('id');
                $query->whereIn('company_id', $companyIds);
                break;
            case \App\Enums\UserRole::SELLER:
                // Seller solo puede ver sus propias cotizaciones
                //$companyIds = $user->companies->pluck('id');                

                $companyIds = $user->sellers()->with('company')->pluck('company_id');                
                $myUser = $user->quotes->pluck('id');                
                $query->whereIn('company_id', $companyIds)
                      ->whereIn('id', $myUser);  
               // dd($query->toSql(), $query->getBindings());                    
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para listar cotizaciones'
                ], 403);
        }
        
       if ($request->has('name')) {
            $searchName = $request->name;
            $query->where(function ($q) use ($searchName) {
                $q->whereHas('company', function ($q2) use ($searchName) {
                    $q2->where('name', 'ilike', "%{$searchName}%")
                       ->orWhere('rif', 'ilike', "%{$searchName}%");
                });
            });
        }
        
        if ($request->has('document')) {
                $query->where('quote_number', 'ilike', "%{$request->document}%");
        }


        // Filtros adicionales
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }  
        
         if ($request->has('user_seller_id')) {
            $query->where('user_seller_id', $request->user_seller_id);
        }      

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filtro por estado de expiración
        if ($request->has('expired')) {
            if ($request->expired === 'true') {
                $query->expired();
            } else {
                $query->valid();
            }
        }

        // Filtros por periodo
        if ($request->has('today') && $request->today === 'true') {
            $query->today();
        }

        if ($request->has('this_month') && $request->this_month === 'true') {
            $query->thisMonth();
        }

        $quotes = $query->orderBy('quote_date', 'desc')
                       ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $quotes
        ]);
    }

   public function store(Request $request)
  {
     
      
      $user = auth()->user();

      $request->validate([
          'customer_id' => 'required|exists:customers,id',
          'company_id' => 'required|exists:companies,id',
          'items' => 'required|array|min:1',
          'items.*.product_id' => 'required|exists:products,id',
          'items.*.quantity' => 'required|numeric|min:0',
          'items.*.unit_price' => 'required|numeric|min:0',
          'items.*.buy_tax' => 'required|in:0,1',
          'valid_until' => 'nullable|date|after:today',
          'terms_conditions' => 'nullable|string',
          'notes' => 'nullable|string',
          'discount' => 'nullable|numeric|min:0|max:100'
      ]);

      // Verificar permisos sobre la compañía
     $canCreate = false;
      switch ($user->role) {
          case \App\Enums\UserRole::ADMIN:
          case \App\Enums\UserRole::MANAGER:
              $canCreate = true;
              break;
          case \App\Enums\UserRole::COMPANY:
              $canCreate = $user->companies->contains($request->company_id);
              break;
          case \App\Enums\UserRole::SELLER:
              $canCreate = $user->sellers()->where('company_id', $request->company_id)->exists();
              break;
      }

      if (!$canCreate) {
          return response()->json([
              'success' => false,
              'message' => 'No tienes permisos para crear cotizaciones en esta compañía'
          ], 403);
      }

      DB::beginTransaction();
      try {


          // ✨ CÁLCULO CORRECTO - MÉTODO DE IMPRESORA FISCAL
          $subtotal = 0;
          $taxableBase = 0;
          $exemptBase = 0;
          $totalTaxAmount = 0;

          foreach ($request->items as $item) {
              // Obtener datos del producto si no se proporcionan
              $product = \App\Models\Product::find($item['product_id']);

              // PASO 1: Calcular subtotal del item
              $itemSubtotal = round($item['quantity'] * $item['unit_price'], 2);

              // PASO 2: Calcular descuento sobre el subtotal
              $discountPercentage = $item['discount_percentage'] ?? 0;
              $itemDiscount = round($itemSubtotal * ($discountPercentage / 100), 2);

              // PASO 3: Calcular subtotal DESPUÉS del descuento
              $itemSubtotalAfterDiscount = round($itemSubtotal - $itemDiscount, 2);

              // PASO 4: Calcular IVA sobre el subtotal CON DESCUENTO
              $itemTaxAmount = 0;
              if ($item['buy_tax'] != 1) {  // Si no es exento
                  $aliquot = $item['aliquot'] ?? ($product->aliquot ?? 0);
                  $itemTaxAmount = round($itemSubtotalAfterDiscount * ($aliquot / 100), 2);
              }

              // PASO 5: Calcular total del item
              $itemTotal = round($itemSubtotalAfterDiscount + $itemTaxAmount, 2);

              $subtotal += $itemSubtotal;

              if ($item['buy_tax'] == 1) {
                  $exemptBase += $itemSubtotalAfterDiscount;
              } else {
                  $taxableBase += $itemSubtotalAfterDiscount;
              }

              $totalTaxAmount += $itemTaxAmount;
          }

          // PASO 6: Aplicar descuento general (% sobre subtotal)
          $discountPercentage = $request->discount ?? 0;
          $discountAmount = round($subtotal * $discountPercentage / 100, 2);

          // PASO 7: Calcular total final
          $total = round($subtotal - $discountAmount + $totalTaxAmount, 2);

          // ✨ DATOS PARA GUARDAR
          $quoteData = [
              'customer_id' => $request->customer_id,
              'company_id' => $request->company_id,
              'tax' => 16,
              'tax_amount' => $totalTaxAmount,
              'discount' => $discountPercentage,
              'discount_amount' => $discountAmount,
              'subtotal' => $subtotal,
              'total' => $total,
              'status' => $request->status ?? 'draft',
              'quote_date' => now(),
              'valid_until' => $request->valid_until,
              'terms_conditions' => $request->terms_conditions,
              'notes' => $request->notes,
              'metadata' => $request->metadata ?? [],
              'bcv_rate' => $request->bcv_rate ?? null,
              'bcv_date' => $request->bcv_date ?? null,
          ];

          if ($user->role == \App\Enums\UserRole::SELLER) {
              $quoteData['user_seller_id'] = $user->id;
          }

          $quote = Quote::create($quoteData);

          // ✨ PASO 8: Crear items con los mismos cálculos
          foreach ($request->items as $index => $item) {
              // Obtener datos del producto si no se proporcionan
              $product = \App\Models\Product::find($item['product_id']);

              // CÁLCULOS IDENTICOS AL PRIMER LOOP
              $itemSubtotal = round($item['quantity'] * $item['unit_price'], 2);
              $discountPercentage = $item['discount_percentage'] ?? 0;
              $itemDiscount = round($itemSubtotal * ($discountPercentage / 100), 2);
              $itemSubtotalAfterDiscount = round($itemSubtotal - $itemDiscount, 2);

              $itemTaxAmount = 0;
              $aliquot = $item['aliquot'] ?? ($product->aliquot ?? 0);
              if ($item['buy_tax'] != 1) {
                  // ✅ CORREGIDO: Calcular IVA sobre el subtotal CON DESCUENTO
                  $itemTaxAmount = round($itemSubtotalAfterDiscount * ($aliquot / 100), 2);
              }

              $itemTotal = round($itemSubtotalAfterDiscount + $itemTaxAmount, 2);

              $quote->items()->create([
                  'product_id' => $item['product_id'],
                  'quantity' => $item['quantity'],
                  'unit_price' => $item['unit_price'],
                  'total' => $itemTotal,
                  'discount_percentage' => $discountPercentage,
                  'discount_amount' => $itemDiscount,
                  'name' => $item['name'] ?? ($product->name ?? 'Producto'),
                  'description' => $item['description'] ?? ($product->description ?? null),
                  'buy_tax' => $item['buy_tax'],
                  'tax_percentage' => $aliquot,
                  'tax_amount' => $itemTaxAmount,
                  'type_price' => $item['type_price'] ?? 'default',
                  'item_type' => 'product',
                  'unit' => $product->unidad ?? 'pcs',
                  'sort_order' => $index + 1,
                  'subtotal' => $itemSubtotal,
              ]);
          }

          DB::commit();

          return response()->json([
              'success' => true,
              'message' => 'Cotización creada exitosamente',
              'data' => $quote->load(['customer', 'company', 'items.product']),
              'calculation_info' => [
                  'subtotal' => $subtotal,
                  'taxable_base' => $taxableBase,
                  'exempt_base' => $exemptBase,
                  'discount_percentage' => $discountPercentage,
                  'discount_amount' => $discountAmount,
                  'tax_amount' => $totalTaxAmount,
                  'total' => $total,
              ]
          ], 201);

      } catch (\Exception $e) {
          DB::rollBack();
          \Log::error('Error creating quote: ' . $e->getMessage());
          return response()->json([
              'success' => false,
              'message' => 'Error al crear la cotización: ' . $e->getMessage(),
              'debug' => [
                    'exception' => get_class($e),   // Qué tipo de error es (ej: InvalidArgumentException)
                    'file'      => $e->getFile(),    // En qué archivo ocurrió
                    'line'      => $e->getLine(),    // En qué línea exacta ocurrió
                    'trace'     => array_slice($e->getTrace(), 0, 5) // Los primeros 5 pasos del viaje del error
                ]
          ], 500);
      }
  }


    public function show($id)
    {
        $user = auth()->user();
        $quote = Quote::with(['customer', 'company', 'items.product'])->find($id);        
        $canView = $user->companies->contains($quote->company_id);
       

        // Verificar permisos
        $canView = false;
        switch ($user->role) {
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                $canView = true;
                break;
            case \App\Enums\UserRole::COMPANY:
                $canView = $user->companies->contains($quote->company_id);
                break;
            case \App\Enums\UserRole::SELLER:
                 //$canView = $user->sellers->contains($quote->company_id);
                 //dd($canView);
                 $canView = true;
                break;
        }

        if (!$canView) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver esta cotización'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $quote->load(['customer', 'company', 'items.product','seller'])
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $quote = Quote::find($id);

        if (!$quote) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);
        }

        // Debug logging
        \Log::info('Quote update attempt', [
            'quote_id' => $quote->id,
            'quote_company_id' => $quote->company_id,
            'user_id' => $user->id,
            'user_role' => $user->role->value
        ]);

        // Verificar permisos
        $canUpdate = false;
        switch ($user->role) {
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                $canUpdate = true;
                break;
            case \App\Enums\UserRole::COMPANY:
                $canUpdate = \App\Models\Company::where('id', $quote->company_id)
                    ->where('user_id', $user->id)
                    ->exists();
                \Log::info('COMPANY permission check', ['can_update' => $canUpdate]);
                break;
            case \App\Enums\UserRole::SELLER:
                $canUpdate = \App\Models\Company::where('id', $quote->company_id)
                    ->where('user_id', $user->id)
                    ->exists();
                break;
        }

        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para actualizar esta cotización'
            ], 403);
        }

        // Solo se pueden modificar presupuestos en borrador
        if (!$quote->canBeModified()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar una cotización que no está en borrador'
            ], 400);
        }

        // Validación más flexible - items son opcionales para actualizaciones parciales
        $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'company_id' => 'sometimes|exists:companies,id',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'valid_until' => 'nullable|date|after:today',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'status' => 'sometimes|string|in:draft,sent,approved,rejected,expired'
        ]);

        DB::beginTransaction();
        try {
            // Si se envían items, eliminar los existentes y crear los nuevos
            if ($request->has('items') && is_array($request->items)) {
                // Eliminar items existentes
                $quote->items()->delete();

                $subtotal = 0;

                // Recalcular con nuevos items
                foreach ($request->items as $item) {
                    $itemTotal = $item['quantity'] * $item['unit_price'];
                    if (isset($item['discount'])) {
                        $itemTotal -= $item['discount'];
                    }
                    $subtotal += $itemTotal;
                }

                $discount = $request->discount ?? 0;
                $tax = ($subtotal - $discount) * 0.18;
                $total = $subtotal + $tax - $discount;

                $quote->update([
                    'customer_id' => $request->customer_id ?? $quote->customer_id,
                    'company_id' => $request->company_id ?? $quote->company_id,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'discount' => $discount,
                    'total' => $total,
                    'valid_until' => $request->valid_until ?? $quote->valid_until,
                    'terms_conditions' => $request->terms_conditions,
                    'notes' => $request->notes
                ]);

                // Crear nuevos items
                foreach ($request->items as $item) {
                    $quote->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price'],
                        'discount' => $item['discount'] ?? 0
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Cotización actualizada exitosamente',
                    'data' => $quote->load(['customer', 'company', 'items.product'])
                ]);
            } else {
                // Actualización parcial sin items - solo actualizar campos proporcionados
                $updateData = [];

                if ($request->has('customer_id')) {
                    $updateData['customer_id'] = $request->customer_id;
                }
                if ($request->has('company_id')) {
                    $updateData['company_id'] = $request->company_id;
                }
                if ($request->has('valid_until')) {
                    $updateData['valid_until'] = $request->valid_until;
                }
                if ($request->has('terms_conditions')) {
                    $updateData['terms_conditions'] = $request->terms_conditions;
                }
                if ($request->has('notes')) {
                    $updateData['notes'] = $request->notes;
                }
                if ($request->has('discount')) {
                    $updateData['discount'] = $request->discount;
                }
                if ($request->has('status')) {
                    $updateData['status'] = $request->status;
                }

                // Actualizar subtotal, tax, total si se proporcionan
                if ($request->has('subtotal')) {
                    $updateData['subtotal'] = $request->subtotal;
                }
                if ($request->has('tax')) {
                    $updateData['tax'] = $request->tax;
                }
                if ($request->has('tax_amount')) {
                    $updateData['tax_amount'] = $request->tax_amount;
                }
                if ($request->has('total')) {
                    $updateData['total'] = $request->total;
                }

                if (!empty($updateData)) {
                    $quote->update($updateData);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Cotización actualizada exitosamente',
                    'data' => $quote->load(['customer', 'company', 'items.product'])
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->user();
        $quote = Quote::find($id);

        if (!$quote) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);
        }

        // Verificar permisos
        $canDelete = false;
        switch ($user->role) {
            case \App\Enums\UserRole::ADMIN:
                $canDelete = true;
                break;
            case \App\Enums\UserRole::MANAGER:
                $canDelete = true;
                break;
            case \App\Enums\UserRole::COMPANY:
                $canDelete = \App\Models\Company::where('id', $quote->company_id)
                    ->where('user_id', $user->id)
                    ->exists();
                break;
            case \App\Enums\UserRole::SELLER:
                $canDelete = \App\Models\Company::where('id', $quote->company_id)
                    ->where('user_id', $user->id)
                    ->exists();
                break;
        }

        if (!$canDelete) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar esta cotización'
            ], 403);
        }

        // Solo se pueden eliminar presupuestos en borrador
        if (!$quote->canBeModified()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cotización que no está en borrador'
            ], 400);
        }

        $quote->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cotización eliminada correctamente'
        ]);
    }

    // Acciones específicas de presupuestos
    public function send(Request $request, Quote $quote)
    {
        $user = auth()->user();

        // Verificar permisos
        $canSend = false;
        switch ($user->role) {
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                $canSend = true;
                break;
            case \App\Enums\UserRole::COMPANY:
                $canSend = $user->companies->contains($quote->company_id);
                break;
            case \App\Enums\UserRole::SELLER:
                $canSend = $user->companies->contains($quote->company_id);
                break;
        }

        if (!$canSend) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para enviar esta cotización'
            ], 403);
        }

        if (!$quote->canBeSent()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede enviar esta cotización'
            ], 400);
        }

        $quote->markAsSent();

        return response()->json([
            'success' => true,
            'message' => 'Cotización enviada correctamente',
            'data' => $quote->fresh()
        ]);
    }

    public function approve(Request $request, Quote $quote)
    {
        $user = auth()->user();

        // Solo admin, manager y company pueden aprobar
        if (!in_array($user->role, [\App\Enums\UserRole::ADMIN, \App\Enums\UserRole::MANAGER, \App\Enums\UserRole::COMPANY])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para aprobar cotizaciones'
            ], 403);
        }

        if ($user->role === \App\Enums\UserRole::COMPANY && !$user->companies->contains($quote->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para aprobar esta cotización'
            ], 403);
        }

        if (!$quote->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede aprobar esta cotización'
            ], 400);
        }

        $quote->approve();

        return response()->json([
            'success' => true,
            'message' => 'Cotización aprobada correctamente',
            'data' => $quote->fresh()
        ]);
    }

    public function reject(Request $request, Quote $quote)
    {
        $user = auth()->user();

        // Solo admin, manager y company pueden rechazar
        if (!in_array($user->role, [\App\Enums\UserRole::ADMIN, \App\Enums\UserRole::MANAGER, \App\Enums\UserRole::COMPANY])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para rechazar cotizaciones'
            ], 403);
        }

        if ($user->role === \App\Enums\UserRole::COMPANY && !$user->companies->contains($quote->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para rechazar esta cotización'
            ], 403);
        }

        if (!$quote->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede rechazar esta cotización'
            ], 400);
        }

        $quote->reject();

        return response()->json([
            'success' => true,
            'message' => 'Cotización rechazada correctamente',
            'data' => $quote->fresh()
        ]);
    }

    public function duplicate(Request $request, Quote $quote)
    {
        $user = auth()->user();

        // Verificar permisos
        $canDuplicate = false;
        switch ($user->role) {
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                $canDuplicate = true;
                break;
            case \App\Enums\UserRole::COMPANY:
                $canDuplicate = $user->companies->contains($quote->company_id);
                break;
            case \App\Enums\UserRole::SELLER:
                $canDuplicate = $user->companies->contains($quote->company_id);
                break;
        }

        if (!$canDuplicate) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para duplicar esta cotización'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $newQuote = Quote::create([
                'customer_id' => $quote->customer_id,
                'company_id' => $quote->company_id,
                'subtotal' => $quote->subtotal,
                'tax' => $quote->tax,
                'discount' => $quote->discount,
                'total' => $quote->total,
                'status' => Quote::STATUS_DRAFT,
                'quote_date' => now(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'terms_conditions' => $quote->terms_conditions,
                'notes' => 'Duplicado de: ' . $quote->quote_number
            ]);

            // Duplicar items
            foreach ($quote->items as $item) {
                $newQuote->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'discount' => $item->discount
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización duplicada correctamente',
                'data' => $newQuote->load(['customer', 'company', 'items.product'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al duplicar cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de cotizaciones
     */
    public function stats(Request $request)
    {
        $user = auth()->user();
        $query = Quote::query();

        // Filtrar según rol
        switch ($user->role) {
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                break;
            case \App\Enums\UserRole::COMPANY:
                $companyIds = $user->companies->pluck('id');
                $query->whereIn('company_id', $companyIds);
                break;
            case \App\Enums\UserRole::SELLER:
                $companyIds = $user->companies->pluck('id');
                $query->whereIn('company_id', $companyIds);
                break;
        }

        $stats = [
            'total' => $query->count(),
            'draft' => $query->clone()->where('status', Quote::STATUS_DRAFT)->count(),
            'sent' => $query->clone()->where('status', Quote::STATUS_SENT)->count(),
            'approved' => $query->clone()->where('status', Quote::STATUS_APPROVED)->count(),
            'rejected' => $query->clone()->where('status', Quote::STATUS_REJECTED)->count(),
            'expired' => $query->clone()->expired()->count(),
            'today' => $query->clone()->today()->count(),
            'this_month' => $query->clone()->thisMonth()->count(),
            'total_value' => $query->clone()->sum('total')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}