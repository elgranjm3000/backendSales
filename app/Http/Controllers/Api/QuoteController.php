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
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                // Admin y Manager pueden ver todas las cotizaciones
                break;
            case User::ROLE_COMPANY:
                // Company solo puede ver cotizaciones de sus compañías
                $companyIds = $user->companies->pluck('id');
                $query->whereIn('company_id', $companyIds);
                break;
            case User::ROLE_SELLER:
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

        // Filtros adicionales
        if ($request->has('status')) {
            $query->where('status', $request->status);
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
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date|after:today',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0'
        ]);

        // Verificar permisos sobre la compañía
        $canCreate = false;
        switch ($user->role) {
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canCreate = true;
                break;
            case User::ROLE_COMPANY:
                $canCreate = $user->companies->contains($request->company_id);
                break;
            case User::ROLE_SELLER:
                // El seller debe pertenecer a la compañía
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
            $subtotal = 0;
            
            // Calcular subtotal
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                if (isset($item['discount'])) {
                    $itemTotal -= $item['discount'];
                }
                $subtotal += $itemTotal;
            }

            $discount = $request->discount ?? 0;
            $tax = 16; // IGV 18%
            $total = $subtotal;

            $quoteData = [
                'customer_id' => $request->customer_id,
                'company_id' => $request->company_id,
                'tax' => $tax,
                'discount' => $discount,
                'status' => Quote::STATUS_DRAFT,
                'quote_date' => now(),
                'valid_until' => $request->valid_until,
                'terms_conditions' => $request->terms_conditions,
                'notes' => $request->notes,
                'metadata' => $request->metadata ?? [],                
                'bcv_rate' => $request->bcv_rate ?? null,
                'bcv_date' => $request->bcv_date ?? null,
            ];

            if ($user->role == User::ROLE_SELLER) {
               $quoteData['user_seller_id'] = $user->id;
            }
            $quote = Quote::create($quoteData);

            // Crear items del presupuesto
            foreach ($request->items as $item) {
                $quote->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'discount_percentage' => $item['discount'] ?? 0,
                    'name' => $item['name'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
                'data' => $quote->load(['customer', 'company', 'items.product'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cotización: ' . $e->getMessage()
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
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canView = true;
                break;
            case User::ROLE_COMPANY:
                $canView = $user->companies->contains($quote->company_id);
                break;
            case User::ROLE_SELLER:
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

    public function update(Request $request, Quote $quote)
    {
        $user = auth()->user();

        // Verificar permisos
        $canUpdate = false;
        switch ($user->role) {
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canUpdate = true;
                break;
            case User::ROLE_COMPANY:
                $canUpdate = $user->companies->contains($quote->company_id);
                break;
            case User::ROLE_SELLER:
               $canUpdate = $user->companies->contains($quote->company_id);
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

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'company_id' => 'required|exists:companies,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date|after:today',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
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
                'customer_id' => $request->customer_id,
                'company_id' => $request->company_id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'valid_until' => $request->valid_until,
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

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Quote $quote)
    {
        $user = auth()->user();

        // Verificar permisos
        $canDelete = false;
        switch ($user->role) {
            case User::ROLE_ADMIN:
                $canDelete = true;
                break;
            case User::ROLE_MANAGER:
                $canDelete = true;
                break;
            case User::ROLE_COMPANY:
                $canDelete = $user->companies->contains($quote->company_id);
                break;
            case User::ROLE_SELLER:
                $canDelete = $user->companies->contains($quote->company_id);
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
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canSend = true;
                break;
            case User::ROLE_COMPANY:
                $canSend = $user->companies->contains($quote->company_id);
                break;
            case User::ROLE_SELLER:
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
        if (!in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_COMPANY])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para aprobar cotizaciones'
            ], 403);
        }

        if ($user->role === User::ROLE_COMPANY && !$user->companies->contains($quote->company_id)) {
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
        if (!in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_COMPANY])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para rechazar cotizaciones'
            ], 403);
        }

        if ($user->role === User::ROLE_COMPANY && !$user->companies->contains($quote->company_id)) {
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
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canDuplicate = true;
                break;
            case User::ROLE_COMPANY:
                $canDuplicate = $user->companies->contains($quote->company_id);
                break;
            case User::ROLE_SELLER:
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
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                break;
            case User::ROLE_COMPANY:
                $companyIds = $user->companies->pluck('id');
                $query->whereIn('company_id', $companyIds);
                break;
            case User::ROLE_SELLER:
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